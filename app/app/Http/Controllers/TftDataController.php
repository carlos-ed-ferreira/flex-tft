<?php

namespace App\Http\Controllers;

use App\Services\TftDataService;
use Illuminate\Http\JsonResponse;

class TftDataController extends Controller
{
    public function __construct(
        private TftDataService $tftData,
    ) {}

    /**
     * Return all TFT data as JSON.
     */
    public function index(): JsonResponse
    {
        return response()->json($this->tftData->all());
    }
}
