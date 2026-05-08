<?php

namespace App\Http\Controllers;

use App\Http\Requests\SaveCompositionRequest;
use App\Models\Composition;
use App\Services\CompositionViewDataService;
use App\Services\CompositionWriterService;
use App\Services\TftDataService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class CompositionController extends Controller
{
    public function __construct(
        private TftDataService $tftData,
        private CompositionWriterService $compositionWriter,
        private CompositionViewDataService $compositionViewData,
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
}
