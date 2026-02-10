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
                return [
                    'id' => $comp->id,
                    'name' => $comp->name,
                    'notes' => $comp->notes,
                    'levels' => $comp->levels->map(fn (CompositionLevel $level) => [
                        'level' => $level->level,
                        'championCount' => $level->champion_count,
                    ]),
                    'updated_at' => $comp->updated_at->diffForHumans(),
                ];
            }),
        ]);
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
            'levels.*.board_state' => 'required',
        ]);

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

        return redirect()->route('compositions.edit', $composition);
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
            'levels.*.board_state' => 'required',
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
