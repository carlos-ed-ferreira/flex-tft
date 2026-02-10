<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;

class TftDataService
{
    /**
     * Get all TFT data as a single array.
     */
    public function all(): array
    {
        return [
            'champions' => $this->getChampions(),
            'items' => $this->getItems(),
            'traits' => $this->getTraits(),
        ];
    }

    public function getChampions(): array
    {
        return $this->loadJson('tft/champions.json');
    }

    public function getItems(): array
    {
        return $this->loadJson('tft/items.json');
    }

    public function getTraits(): array
    {
        return $this->loadJson('tft/traits.json');
    }

    private function loadJson(string $path): array
    {
        $disk = Storage::disk('local');
        $fullPath = $disk->path($path);
        
        if (!file_exists($fullPath)) {
            return [];
        }

        $content = file_get_contents($fullPath);
        return json_decode($content, true) ?? [];
    }
}
