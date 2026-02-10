<?php

namespace App\Http\Controllers;

use App\Models\Composition;
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
        $compositions = Composition::with('levels')->latest()->get();

        return Inertia::render('Compositions/Index', [
            'compositions' => $compositions->map(function (Composition $comp) {
                $highestLevel = $this->getHighestLevelWithContent($comp);
                
                return [
                    'id' => $comp->id,
                    'name' => $comp->name,
                    'notes' => $comp->notes,
                    'traits' => $highestLevel ? $this->calculateTraits($highestLevel->board_state) : [],
                    'champions' => $highestLevel ? $this->getChampionsWithThreeItems($highestLevel->board_state) : [],
                ];
            }),
            'tftData' => $this->tftData->all(),
        ]);
    }

    private function getHighestLevelWithContent(Composition $comp): ?CompositionLevel
    {
        return $comp->levels()
            ->orderByDesc('level')
            ->get()
            ->first(function ($level) {
                $state = is_string($level->board_state) 
                    ? json_decode($level->board_state, true) 
                    : $level->board_state;
                return !empty($state) && count($state) > 0;
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
                    foreach ($champion['traits'] as $traitId) {
                        if (is_string($traitId) || is_int($traitId)) {
                            if (!isset($traitCounts[$traitId])) {
                                $traitCounts[$traitId] = 0;
                            }
                            $traitCounts[$traitId]++;
                        }
                    }
                }
            }
        }

        $activeTraits = [];
        foreach ($traitCounts as $traitId => $count) {
            $trait = collect($allTraits)->firstWhere('id', $traitId);
            if ($trait && isset($trait['breakpoints'])) {
                foreach ($trait['breakpoints'] as $breakpoint) {
                    if ($count >= $breakpoint['min']) {
                        $activeTraits[] = [
                            'id' => $traitId,
                            'name' => $trait['name'],
                            'icon' => $trait['icon'] ?? null,
                            'count' => $count,
                            'style' => $breakpoint['style'] ?? 0,
                        ];
                        break;
                    }
                }
            }
        }

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

        return redirect()->route('compositions.index')
            ->with('success', 'Composição criada com sucesso!');
    }

    /**
     * Show the team builder for editing an existing composition.
     */
    public function edit(Composition $composition): Response
    {
        $composition->load('levels');

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

        return redirect()->route('compositions.edit', $composition);
    }

    /**
     * Delete a composition.
     */
    public function destroy(Composition $composition)
    {
        $composition->delete();
        return redirect()->route('compositions.index');
    }
}
