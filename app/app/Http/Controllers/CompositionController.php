<?php

namespace App\Http\Controllers;

use App\Models\Composition;
use App\Models\CompositionDisposition;
use App\Models\CompositionLevel;
use App\Services\TftDataService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CompositionController extends Controller
{
    private const LEVELS = [3, 4, 5, 6, 7, 8, 9, 10];

    public function __construct(
        private TftDataService $tftData,
    ) {}

    /**
     * List all compositions (home page).
     */
    public function index(): Response
    {
        $compositions = Composition::with(['levels', 'dispositions'])->orderBy('name')->get();

        return Inertia::render('Compositions/Index', [
            'compositions' => $compositions->map(function (Composition $comp) {
                $highestLevel = $this->getHighestLevelWithContent($comp);
                
                return [
                    'id' => $comp->id,
                    'name' => $comp->name,
                    'notes' => $comp->notes,
                    'traits' => $highestLevel ? $this->calculateTraits($highestLevel->board_state) : [],
                    'champions' => $highestLevel ? $this->getChampionsWithThreeItems($highestLevel->board_state) : [],
                    'dispositions' => $comp->dispositions->map(fn ($d) => [
                        'type' => $d->type,
                        'champion_ids' => $d->champion_ids,
                        'star_level' => $d->star_level,
                        'trait_id' => $d->trait_id,
                        'trait_count' => $d->trait_count,
                        'item_ids' => $d->item_ids,
                        'priority' => $d->priority,
                    ]),
                ];
            }),
            'tftData' => $this->tftData->all(),
        ]);
    }

    private function getHighestLevelWithContent(Composition $comp): ?CompositionLevel
    {
        return $comp->levels
            ->sortByDesc('level')
            ->first(function ($level) {
                $state = is_string($level->board_state) 
                    ? json_decode($level->board_state, true) 
                    : $level->board_state;
                return is_array($state) && count($state) > 0;
            });
    }

    private function getChampionsWithThreeItems($boardState): array
    {
        $state = is_string($boardState) ? json_decode($boardState, true) : $boardState;
        
        if (empty($state)) return [];

        $champions = [];
        foreach ($state as $cell) {
            if (isset($cell['championId']) && isset($cell['items']) && count($cell['items']) === 3) {
                $champions[] = [
                    'id' => $cell['championId'],
                    'items' => $cell['items'],
                ];
            }
        }
        return $champions;
    }

    private function calculateTraits($boardState): array
    {
        $state = is_string($boardState) ? json_decode($boardState, true) : $boardState;
        
        if (empty($state)) return [];

        $traitCounts = [];
        $allTraits = $this->tftData->getTraits();
        $allChampions = collect($this->tftData->getChampions())->keyBy('id');

        foreach ($state as $cell) {
            if (isset($cell['championId'])) {
                $champion = $allChampions->get($cell['championId']);
                if ($champion && isset($champion['traits']) && is_array($champion['traits'])) {
                    foreach ($champion['traits'] as $trait) {
                        $traitName = is_array($trait) ? ($trait['name'] ?? null) : $trait;
                        if ($traitName) {
                            if (!isset($traitCounts[$traitName])) {
                                $traitCounts[$traitName] = 0;
                            }
                            $traitCounts[$traitName]++;
                        }
                    }
                }
            }
        }

        // Map style strings to numbers for frontend
        $styleMap = [
            'kBronze' => 1,
            'kSilver' => 2,
            'kGold' => 3,
            'kChromatic' => 4,
            'kUnique' => 4,
        ];

        $activeTraits = [];
        foreach ($traitCounts as $traitName => $count) {
            $trait = collect($allTraits)->firstWhere('name', $traitName);
            if ($trait && isset($trait['breakpoints'])) {
                // Find the highest matching breakpoint
                $matchedBreakpoint = null;
                foreach ($trait['breakpoints'] as $breakpoint) {
                    if ($count >= $breakpoint['min']) {
                        $matchedBreakpoint = $breakpoint;
                    }
                }
                if ($matchedBreakpoint) {
                    $styleRaw = $matchedBreakpoint['style'] ?? 0;
                    $style = is_string($styleRaw) ? ($styleMap[$styleRaw] ?? 0) : $styleRaw;
                    
                    $activeTraits[] = [
                        'id' => $trait['id'],
                        'name' => $trait['name'],
                        'icon' => $trait['icon'] ?? null,
                        'count' => $count,
                        'style' => $style,
                    ];
                }
            }
        }

        // Sort by style desc, then count desc
        usort($activeTraits, function ($a, $b) {
            return $b['style'] <=> $a['style'] ?: $b['count'] <=> $a['count'];
        });

        return $activeTraits;
    }

    /**
     * Show the team builder for creating a new composition.
     */
    public function create(): Response
    {
        // Build empty levels structure
        $levels = collect(self::LEVELS)->map(fn (int $level) => [
            'level' => $level,
            'board_state' => (object) [],
        ])->all();

        return Inertia::render('Compositions/Editor', [
            'composition' => null,
            'levels' => $levels,
            'dispositions' => [],
            'tftData' => $this->tftData->all(),
        ]);
    }

    /**
     * Store a new composition.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'notes' => 'nullable|string',
            'levels' => 'required|array',
            'levels.*.level' => 'required|integer|in:' . implode(',', self::LEVELS),
            'levels.*.board_state' => 'present',
            'dispositions' => 'nullable|array',
            'dispositions.*.type' => 'required|in:champion,trait,item',
            'dispositions.*.champion_ids' => 'nullable|array',
            'dispositions.*.champion_ids.*' => 'string',
            'dispositions.*.star_level' => 'nullable|integer|min:1|max:2',
            'dispositions.*.trait_id' => 'nullable|string',
            'dispositions.*.trait_count' => 'nullable|integer|min:1',
            'dispositions.*.item_ids' => 'nullable|array',
            'dispositions.*.item_ids.*' => 'string',
            'dispositions.*.priority' => 'nullable|integer',
        ]);

        // Convert empty arrays to empty objects for JSON storage
        $validated['levels'] = array_map(function($levelData) {
            if (is_array($levelData['board_state']) && empty($levelData['board_state'])) {
                $levelData['board_state'] = new \stdClass();
            }
            return $levelData;
        }, $validated['levels']);

        $composition = Composition::create([
            'name' => $validated['name'],
            'notes' => $validated['notes'] ?? null,
        ]);

        foreach ($validated['levels'] as $levelData) {
            $composition->levels()->create([
                'level' => $levelData['level'],
                'board_state' => $levelData['board_state'],
            ]);
        }

        $this->syncDispositions($composition, $validated['dispositions'] ?? []);

        return redirect()->route('compositions.index')
            ->with('success', 'Composição criada com sucesso!');
    }

    /**
     * Show the team builder for editing an existing composition.
     */
    public function edit(Composition $composition): Response
    {
        $composition->load(['levels', 'dispositions']);

        $levels = collect(self::LEVELS)->map(function (int $level) use ($composition) {
            $existingLevel = $composition->levels->firstWhere('level', $level);
            return [
                'level' => $level,
                'board_state' => $existingLevel ? $existingLevel->board_state : (object) [],
            ];
        })->all();

        return Inertia::render('Compositions/Editor', [
            'composition' => [
                'id' => $composition->id,
                'name' => $composition->name,
                'notes' => $composition->notes,
            ],
            'levels' => $levels,
            'dispositions' => $composition->dispositions->map(fn ($d) => [
                'type' => $d->type,
                'champion_ids' => $d->champion_ids,
                'star_level' => $d->star_level,
                'trait_id' => $d->trait_id,
                'trait_count' => $d->trait_count,
                'item_ids' => $d->item_ids,
                'priority' => $d->priority,
            ]),
            'tftData' => $this->tftData->all(),
        ]);
    }

    /**
     * Update an existing composition.
     */
    public function update(Request $request, Composition $composition)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'notes' => 'nullable|string',
            'levels' => 'required|array',
            'levels.*.level' => 'required|integer|in:' . implode(',', self::LEVELS),
            'levels.*.board_state' => 'present',
            'dispositions' => 'nullable|array',
            'dispositions.*.type' => 'required|in:champion,trait,item',
            'dispositions.*.champion_ids' => 'nullable|array',
            'dispositions.*.champion_ids.*' => 'string',
            'dispositions.*.star_level' => 'nullable|integer|min:1|max:2',
            'dispositions.*.trait_id' => 'nullable|string',
            'dispositions.*.trait_count' => 'nullable|integer|min:1',
            'dispositions.*.item_ids' => 'nullable|array',
            'dispositions.*.item_ids.*' => 'string',
            'dispositions.*.priority' => 'nullable|integer',
        ]);

        $composition->update([
            'name' => $validated['name'],
            'notes' => $validated['notes'] ?? null,
        ]);

        foreach ($validated['levels'] as $levelData) {
            $composition->levels()->updateOrCreate(
                ['level' => $levelData['level']],
                ['board_state' => $levelData['board_state']],
            );
        }

        $this->syncDispositions($composition, $validated['dispositions'] ?? []);

        return redirect()->route('compositions.index')->with('success', 'Composição atualizada com sucesso!');
    }

    /**
     * Delete a composition.
     */
    public function destroy(Composition $composition)
    {
        $composition->delete();
        return redirect()->route('compositions.index');
    }

    /**
     * Duplicate a composition.
     */
    public function duplicate(Composition $composition)
    {
        $composition->load(['levels', 'dispositions']);

        $newComposition = Composition::create([
            'name' => $composition->name . ' (cópia)',
            'notes' => $composition->notes,
        ]);

        foreach ($composition->levels as $level) {
            $newComposition->levels()->create([
                'level' => $level->level,
                'board_state' => $level->board_state,
            ]);
        }

        foreach ($composition->dispositions as $disp) {
            $newComposition->dispositions()->create([
                'type' => $disp->type,
                'champion_ids' => $disp->champion_ids,
                'star_level' => $disp->star_level,
                'trait_id' => $disp->trait_id,
                'trait_count' => $disp->trait_count,
                'item_ids' => $disp->item_ids,
                'priority' => $disp->priority,
            ]);
        }

        return redirect()->route('compositions.edit', $newComposition)
            ->with('success', 'Composição duplicada com sucesso!');
    }

    /**
     * Sync dispositions for a composition (delete + recreate).
     */
    private function syncDispositions(Composition $composition, array $dispositions): void
    {
        $composition->dispositions()->delete();

        foreach ($dispositions as $i => $disp) {
            $composition->dispositions()->create([
                'type' => $disp['type'],
                'champion_ids' => $disp['champion_ids'] ?? null,
                'star_level' => $disp['star_level'] ?? null,
                'trait_id' => $disp['trait_id'] ?? null,
                'trait_count' => $disp['trait_count'] ?? null,
                'item_ids' => $disp['item_ids'] ?? null,
                'priority' => $disp['priority'] ?? $i,
            ]);
        }
    }
}
