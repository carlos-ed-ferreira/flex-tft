<?php

namespace App\Http\Controllers;

use App\Http\Requests\ImportPlannerCodeRequest;
use App\Http\Requests\SaveCompositionRequest;
use App\Models\Composition;
use App\Models\CompositionLevel;
use App\Services\CompositionViewDataService;
use App\Services\CompositionWriterService;
use App\Services\TftDataService;
use App\Services\TftPlannerCodeService;
use App\Support\TftLevels;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use InvalidArgumentException;
use stdClass;

class CompositionController extends Controller
{
    private const BOARD_COLS = 7;

    public function __construct(
        private TftDataService $tftData,
        private CompositionWriterService $compositionWriter,
        private CompositionViewDataService $compositionViewData,
        private TftPlannerCodeService $plannerCode,
    ) {}

    public function index(): Response
    {
        $compositions = Composition::global()
            ->with(['levels', 'dispositions', 'user'])
            ->orderBy('name')
            ->get();

        return Inertia::render('Compositions/Index', [
            'compositions' => $compositions->map(fn (Composition $composition) => $this->compositionViewData->card($composition)),
            'tftData' => $this->tftData->all(),
        ]);
    }

    public function myIndex(): Response
    {
        $compositions = Composition::forUser(Auth::id())
            ->with(['levels', 'dispositions', 'user'])
            ->orderBy('name')
            ->get();

        return Inertia::render('Compositions/MyIndex', [
            'compositions' => $compositions->map(fn (Composition $composition) => $this->compositionViewData->card($composition)),
            'tftData' => $this->tftData->all(),
        ]);
    }

    public function show(Composition $composition): Response
    {
        $this->authorizeViewing($composition);

        $composition->load(['levels', 'dispositions', 'user']);

        return Inertia::render('Compositions/Show', [
            ...$this->compositionViewData->show($composition),
            'tftData' => $this->tftData->all(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Compositions/Editor', [
            'composition' => null,
            'levels' => $this->compositionViewData->emptyLevels(),
            'dispositions' => [],
            'tftData' => $this->tftData->all(),
        ]);
    }

    public function store(SaveCompositionRequest $request): RedirectResponse
    {
        $this->compositionWriter->createForUser((int) Auth::id(), $request->compositionData());

        return redirect()->route('compositions.my')
            ->with('success', 'Composição criada com sucesso!');
    }

    public function edit(Composition $composition): Response
    {
        $this->authorizeOwnership($composition);

        $composition->load(['levels', 'dispositions']);

        return Inertia::render('Compositions/Editor', [
            ...$this->compositionViewData->editor($composition),
            'tftData' => $this->tftData->all(),
        ]);
    }

    public function update(SaveCompositionRequest $request, Composition $composition): RedirectResponse
    {
        $this->authorizeOwnership($composition);

        $this->compositionWriter->update($composition, $request->compositionData());

        return redirect()->route('compositions.my')->with('success', 'Composição atualizada com sucesso!');
    }

    public function destroy(Composition $composition): RedirectResponse
    {
        $this->authorizeOwnership($composition);

        $composition->delete();

        return redirect()->route('compositions.my');
    }

    public function duplicate(Composition $composition): RedirectResponse
    {
        $this->authorizeOwnership($composition);

        $newComposition = $this->compositionWriter->duplicateForUser($composition, (int) Auth::id());

        return redirect()->route('compositions.edit', $newComposition)
            ->with('success', 'Composição duplicada com sucesso!');
    }

    public function import(Composition $composition): RedirectResponse
    {
        if (! $composition->is_global) {
            abort(403);
        }

        $this->compositionWriter->importForUser($composition, (int) Auth::id());

        return redirect()->route('compositions.my')
            ->with('success', 'Composição importada com sucesso!');
    }

    public function exportPlannerCode(Composition $composition): JsonResponse
    {
        $this->authorizeViewing($composition);

        $composition->loadMissing('levels');
        $level = $this->levelForPlannerExport($composition);

        if (! $level) {
            return response()->json([
                'message' => 'A composição não possui campeões na versão 1 para exportar.',
            ], 422);
        }

        try {
            $code = $this->plannerCode->encode($this->championIdsFromBoardState($level->board_state ?? []));
        } catch (InvalidArgumentException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json([
            'code' => $code,
            'level' => $level->level,
            'version' => $level->version,
        ]);
    }

    public function importPlannerCode(ImportPlannerCodeRequest $request): RedirectResponse
    {
        try {
            $championIds = $this->plannerCode->decode($request->validated()['code']);
        } catch (InvalidArgumentException $exception) {
            throw ValidationException::withMessages([
                'code' => $exception->getMessage(),
            ]);
        }

        $composition = $this->compositionWriter->createForUser((int) Auth::id(), $this->plannerImportData($championIds));

        return redirect()->route('compositions.edit', $composition)
            ->with('success', 'Código do planner importado com sucesso!');
    }

    private function authorizeViewing(Composition $composition): void
    {
        if ($composition->is_global) {
            return;
        }

        if (! Auth::check()) {
            abort(403);
        }

        $this->authorizeOwnership($composition);
    }

    private function authorizeOwnership(Composition $composition): void
    {
        $user = Auth::user();

        $isAdmin = ($user->role ?? null) === 'a';

        if (! $user || ((int) $user->id !== (int) $composition->user_id && ! $isAdmin)) {
            abort(403);
        }
    }

    private function levelForPlannerExport(Composition $composition): ?CompositionLevel
    {
        return $composition->levels
            ->filter(fn (CompositionLevel $level) => $level->version === 1 && ! empty($this->championIdsFromBoardState($level->board_state ?? [])))
            ->sortByDesc('level')
            ->first();
    }

    private function championIdsFromBoardState(array $boardState): array
    {
        uksort($boardState, fn (string|int $first, string|int $second) => $this->boardCellIndex((string) $first) <=> $this->boardCellIndex((string) $second));

        $championIds = [];

        foreach ($boardState as $cell) {
            $cell = (array) $cell;
            $championId = trim((string) ($cell['championId'] ?? ''));

            if ($championId === '' || ($cell['isSummon'] ?? false) || isset($championIds[$championId])) {
                continue;
            }

            $championIds[$championId] = $championId;
        }

        return array_values($championIds);
    }

    private function boardCellIndex(string $key): int
    {
        if (preg_match('/^(\d+)-(\d+)$/', $key, $matches)) {
            return ((int) $matches[1] * self::BOARD_COLS) + (int) $matches[2];
        }

        if (ctype_digit($key)) {
            return (int) $key;
        }

        return PHP_INT_MAX;
    }

    private function plannerImportData(array $championIds): array
    {
        $targetLevel = min(10, max(3, count($championIds)));

        return [
            'name' => 'Composição importada do planner',
            'notes' => null,
            'levels' => array_map(fn (int $level) => [
                'level' => $level,
                'version' => 1,
                'label' => null,
                'board_state' => $level === $targetLevel ? $this->plannerBoardState($championIds) : new stdClass,
            ], TftLevels::values()),
            'dispositions' => [],
        ];
    }

    private function plannerBoardState(array $championIds): array
    {
        $boardState = [];

        foreach ($championIds as $index => $championId) {
            $row = intdiv($index, self::BOARD_COLS);
            $col = $index % self::BOARD_COLS;

            $boardState["{$row}-{$col}"] = [
                'championId' => $championId,
                'items' => [],
            ];
        }

        return $boardState;
    }
}
