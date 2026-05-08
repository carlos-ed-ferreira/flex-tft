<?php

namespace App\Http\Controllers;

use App\Services\DatabaseHealthService;
use Illuminate\Http\JsonResponse;

class DatabaseHealthController extends Controller
{
    public function __construct(
        private DatabaseHealthService $databaseHealth,
    ) {}

    public function index(): JsonResponse
    {
        return response()->json($this->databaseHealth->status());
    }
}
