<?php

namespace App\Http\Controllers;

use App\Models\Composition;
use App\Services\TftDataService;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class SimulatorController extends Controller
{
    public function __construct(
        private TftDataService $tftData,
    ) {}

    /**
     * Show the opening simulator page.
     */
    public function index(): Response
    {
        $query = Composition::with(['dispositions'])
            ->has('dispositions')
            ->where('is_global', true);

        if (Auth::check()) {
            $query = Composition::with(['dispositions'])
                ->has('dispositions')
                ->where(function ($q) {
                    $q->where('is_global', true)
                        ->orWhere('user_id', Auth::id());
                });
        }

        $compositions = $query->orderBy('name')->get()->map(function (Composition $comp) {
            return [
                'id' => $comp->id,
                'name' => $comp->name,
                'is_global' => $comp->is_global,
                'user_id' => $comp->user_id,
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
        });

        return Inertia::render('Simulator/Index', [
            'compositions' => $compositions,
            'tftData' => $this->tftData->all(),
        ]);
    }
}
