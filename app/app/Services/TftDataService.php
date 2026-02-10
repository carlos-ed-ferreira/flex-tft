<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;

class TftDataService
{
    private ?array $champions = null;
    private ?array $items = null;
    private ?array $traits = null;

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
        if ($this->champions === null) {
            $this->champions = $this->loadJson('tft/champions.json');
        }
        return $this->champions;
    }

    public function getItems(): array
    {
        if ($this->items === null) {
            $this->items = $this->loadJson('tft/items.json');
        }
        return $this->items;
    }

    public function getTraits(): array
    {
        if ($this->traits === null) {
            $this->traits = $this->loadJson('tft/traits.json');
        }
        return $this->traits;
    }

    private function loadJson(string $path): array
    {
        if (!Storage::disk('local')->exists($path)) {
            return [];
        }

        $content = Storage::disk('local')->get($path);
        return json_decode($content, true) ?? [];
    }
}
