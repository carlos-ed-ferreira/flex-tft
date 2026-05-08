<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class DatabaseHealthService
{
    public function status(): array
    {
        $variables = $this->fetchVariables();
        $status = $this->fetchStatus();

        return [
            'version' => $variables['version'],
            'max_connections' => (int) $variables['max_connections'],
            'open_connections' => (int) $status['Threads_connected'],
        ];
    }

    private function fetchVariables(): array
    {
        $rows = DB::select("SHOW VARIABLES WHERE Variable_name IN ('version', 'max_connections')");

        return collect($rows)->pluck('Value', 'Variable_name')->all();
    }

    private function fetchStatus(): array
    {
        $rows = DB::select("SHOW STATUS WHERE Variable_name = 'Threads_connected'");

        return collect($rows)->pluck('Value', 'Variable_name')->all();
    }
}
