<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class TftSyncCommand extends Command
{
    protected $signature = 'tft:sync {--set=16 : The TFT set number to sync}';
    protected $description = 'Sync TFT champion, item, and trait data from Community Dragon';

    private const CDRAGON_BASE = 'https://raw.communitydragon.org/latest/plugins/rcp-be-lol-game-data/global/default';
    private const COMPREHENSIVE_URL = 'https://raw.communitydragon.org/latest/cdragon/tft/en_us.json';
    private const ITEMS_URL = self::CDRAGON_BASE . '/v1/tftitems.json';
    private const TRAITS_URL = self::CDRAGON_BASE . '/v1/tfttraits.json';

    public function handle(): int
    {
        $setNumber = $this->option('set');
        $setPrefix = "TFT{$setNumber}_";
        $this->info("Syncing TFT data for Set {$setNumber} (prefix: {$setPrefix})...");

        // Ensure storage directory exists
        Storage::disk('local')->makeDirectory('tft');

        // Fetch comprehensive data (has correct costs)
        $this->info('Fetching comprehensive data from CDragon...');
        $compResponse = Http::timeout(120)->get(self::COMPREHENSIVE_URL);
        if (!$compResponse->ok()) {
            $this->error('Failed to fetch comprehensive data: ' . $compResponse->status());
            return Command::FAILURE;
        }
        $compData = $compResponse->json();

        $this->syncChampions($setNumber, $compData);
        $this->syncItems($setPrefix);
        $this->syncTraits($setPrefix);

        $this->info('TFT data sync complete!');
        return Command::SUCCESS;
    }

    private function syncChampions(string $setNumber, array $compData): void
    {
        $this->info('Processing champions...');

        $setData = $compData['sets'][$setNumber] ?? null;
        if (!$setData || empty($setData['champions'])) {
            $this->error("No champion data found for Set {$setNumber}");
            return;
        }

        $champions = [];

        foreach ($setData['champions'] as $champion) {
            $apiName = $champion['apiName'] ?? '';
            $cost = $champion['cost'] ?? 0;
            $name = $champion['name'] ?? '';
            $traits = $champion['traits'] ?? [];

            // Skip non-champion units (items, anvils, etc.) — they have no traits or very high costs
            if (empty($traits)) continue;
            if ($cost > 10) continue; // Mercenary chests etc.

            $iconPath = $this->convertIconPath($champion['squareIcon'] ?? $champion['icon'] ?? '');

            $traitsList = [];
            foreach ($traits as $traitName) {
                $traitsList[] = [
                    'name' => $traitName,
                ];
            }

            $champions[] = [
                'id' => $apiName,
                'name' => $name,
                'cost' => $cost,
                'traits' => $traitsList,
                'icon' => $iconPath,
            ];
        }

        // Sort by cost then name
        usort($champions, function ($a, $b) {
            return $a['cost'] <=> $b['cost'] ?: strcmp($a['name'], $b['name']);
        });

        Storage::disk('local')->put('tft/champions.json', json_encode($champions, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        $this->info('  → ' . count($champions) . ' champions saved.');
    }

    private function syncItems(string $setPrefix): void
    {
        $this->info('Fetching items...');
        $response = Http::timeout(30)->get(self::ITEMS_URL);

        if (!$response->ok()) {
            $this->error('Failed to fetch items: ' . $response->status());
            return;
        }

        $allItems = $response->json();
        $items = [];
        $seenIds = []; // Track seen IDs for deduplication

        // Detect the set number from prefix (e.g., "TFT13_" → "13")
        preg_match('/TFT(\d+)_/', $setPrefix, $matches);
        $setNumber = $matches[1] ?? '';

        foreach ($allItems as $item) {
            $nameId = $item['nameId'] ?? '';

            // Deduplicate by nameId
            if (isset($seenIds[$nameId])) continue;

            // Include: base components (TFT_Item_*), combined items (TFT_Item_*),
            // set-specific items/emblems, and artifact items
            $isBaseOrCombined = str_starts_with($nameId, 'TFT_Item_') && !str_contains($nameId, 'Augment');
            $isSetSpecific = str_starts_with($nameId, $setPrefix);
            $isArtifact = str_contains($nameId, 'Artifact');

            if (!$isBaseOrCombined && !$isSetSpecific) continue;

            // Skip augments, consumables, and internal items
            if (str_contains($nameId, 'Augment')) continue;
            if (str_contains($nameId, 'Consumable')) continue;
            if (str_contains($nameId, 'Debug')) continue;
            if (str_contains($nameId, 'Tutorial')) continue;

            $iconPath = $this->convertIconPath($item['squareIconPath'] ?? '');
            $name = $item['name'] ?? $nameId;

            // Skip items with empty/null names
            if (empty($name) || $name === 'null') continue;

            // Determine item category
            $category = 'combined';
            if ($this->isBaseComponent($nameId)) {
                $category = 'component';
            } elseif (str_contains($nameId, 'Emblem')) {
                $category = 'emblem';
            } elseif ($isArtifact) {
                $category = 'artifact';
            }

            $items[] = [
                'id' => $nameId,
                'name' => $name,
                'icon' => $iconPath,
                'category' => $category,
            ];
            $seenIds[$nameId] = true;
        }

        // Sort by category priority then name
        $categoryOrder = ['component' => 0, 'combined' => 1, 'emblem' => 2, 'artifact' => 3];
        usort($items, function ($a, $b) use ($categoryOrder) {
            $aCat = $categoryOrder[$a['category']] ?? 99;
            $bCat = $categoryOrder[$b['category']] ?? 99;
            return $aCat <=> $bCat ?: strcmp($a['name'], $b['name']);
        });

        Storage::disk('local')->put('tft/items.json', json_encode($items, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        $this->info('  → ' . count($items) . ' items saved.');
    }

    private function syncTraits(string $setPrefix): void
    {
        $this->info('Fetching traits...');
        $response = Http::timeout(30)->get(self::TRAITS_URL);

        if (!$response->ok()) {
            $this->error('Failed to fetch traits: ' . $response->status());
            return;
        }

        $allTraits = $response->json();
        $traits = [];

        // Detect the set string (e.g., "TFT13_" → "TFTSet13")
        preg_match('/TFT(\d+)_/', $setPrefix, $matches);
        $setString = 'TFTSet' . ($matches[1] ?? '');

        foreach ($allTraits as $trait) {
            $traitSet = $trait['set'] ?? '';
            if ($traitSet !== $setString) continue;

            $iconPath = $this->convertIconPath($trait['icon_path'] ?? '');

            $breakpoints = [];
            foreach ($trait['conditional_trait_sets'] ?? [] as $bp) {
                $breakpoints[] = [
                    'min' => $bp['min_units'] ?? 0,
                    'max' => $bp['max_units'] ?? 0,
                    'style' => $bp['style_name'] ?? '',
                ];
            }

            $traits[] = [
                'id' => $trait['trait_id'] ?? '',
                'name' => $trait['display_name'] ?? '',
                'icon' => $iconPath,
                'breakpoints' => $breakpoints,
            ];
        }

        usort($traits, fn($a, $b) => strcmp($a['name'], $b['name']));

        Storage::disk('local')->put('tft/traits.json', json_encode($traits, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        $this->info('  → ' . count($traits) . ' traits saved.');
    }

    /**
     * Convert CDragon asset path to full URL.
     */
    private function convertIconPath(string $path): string
    {
        if (empty($path)) return '';

        $cleanPath = str_replace('/lol-game-data/assets/', '', $path);
        $cleanPath = strtolower($cleanPath);

        return self::CDRAGON_BASE . '/' . $cleanPath;
    }

    /**
     * Check if an item is a base component.
     */
    private function isBaseComponent(string $nameId): bool
    {
        $components = [
            'TFT_Item_BFSword',
            'TFT_Item_ChainVest',
            'TFT_Item_GiantsBelt',
            'TFT_Item_NeedlesslyLargeRod',
            'TFT_Item_NegatronCloak',
            'TFT_Item_RecurveBow',
            'TFT_Item_SparringGloves',
            'TFT_Item_Spatula',
            'TFT_Item_TearOfTheGoddess',
        ];

        return in_array($nameId, $components);
    }
}
