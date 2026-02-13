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
        $setNumber = (string)$this->option('set');
        $setPrefix = "TFT{$setNumber}_";
        $this->info("Syncing TFT data for Set {$setNumber} (prefix: {$setPrefix})...");

        // Ensure storage directory exists
        Storage::disk('local')->makeDirectory('tft');

        // Fetch comprehensive data (needed for item recipes/tags + correct champ costs)
        $this->info('Fetching comprehensive data from CDragon...');
        $compResponse = Http::timeout(120)->get(self::COMPREHENSIVE_URL);
        if (!$compResponse->ok()) {
            $this->error('Failed to fetch comprehensive data: ' . $compResponse->status());
            return Command::FAILURE;
        }
        $compData = $compResponse->json();

        $this->syncChampions($setNumber, $compData);
        $this->syncItems($setNumber, $setPrefix, $compData);
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

            // Skip non-champion units (items, anvils, etc.)
            if (empty($traits)) continue;
            if ($cost > 10) continue;

            $iconPath = $this->convertIconPath($champion['squareIcon'] ?? $champion['icon'] ?? '');

            $traitsList = [];
            foreach ($traits as $traitName) {
                $traitsList[] = ['name' => $traitName];
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

    /**
     * Items:
     * - Use v1/tftitems.json for name+icon
     * - Use cdragon/tft/en_us.json for recipe/tags/metadata to categorize correctly
     *
     * Categories output:
     * - combined   (craftable combined items, no spatula) - 39 items
     * - emblem     (spatula / emblem items, Set 16 only) - 22 items
     * - bilgewater (black market / trait items equipable) - 8 items
     * - artifact   (ornn/artifacts) - 33 items
     */
    private function syncItems(string $setNumber, string $setPrefix, array $compData): void
    {
        $this->info('Fetching items (v1 list)...');
        $v1Response = Http::timeout(60)->get(self::ITEMS_URL);
        if (!$v1Response->ok()) {
            $this->error('Failed to fetch v1 items: ' . $v1Response->status());
            return;
        }

        $v1Items = $v1Response->json();
        if (!is_array($v1Items)) {
            $this->error('v1 items response is not an array');
            return;
        }

        // Index comprehensive items by apiName/nameId (for recipe/tags)
        $this->info('Indexing comprehensive items (for recipes/tags)...');
        $compIndex = $this->buildComprehensiveItemIndex($compData, $setNumber);

        $items = [];
        $seenIds = [];

        foreach ($v1Items as $item) {
            $nameId = (string)($item['nameId'] ?? '');
            if ($nameId === '') continue;

            // Deduplicate
            if (isset($seenIds[$nameId])) continue;

            // Skip obvious non-item buckets from v1
            if (str_contains($nameId, 'Augment')) continue;
            if (str_contains($nameId, 'Consumable')) continue;
            if (str_contains($nameId, 'Debug')) continue;
            if (str_contains($nameId, 'Tutorial')) continue;
            if (str_contains($nameId, 'Quest')) continue;
            if (str_contains($nameId, 'Explorer')) continue;
            if (str_contains($nameId, 'XerathZap')) continue;
            if (str_contains($nameId, 'Teamup')) continue;
            if (str_contains($nameId, 'UnstableTreasureChest')) continue;
            if (str_contains($nameId, 'UnusableSlot')) continue;
            if (str_contains($nameId, 'Grant')) continue;
            if (str_contains($nameId, 'ChampionItem')) continue;

            // Skip region buff items like _ADTIER, etc.
            if (preg_match('/_(AD|AP|AS|ADAP|Health|ArmorMR)Tier\d+$/', $nameId)) continue;

            // Skip known tactician/support items (you previously excluded these; keep stable)
            if ($this->isTacticianItem($nameId)) continue;
            if ($this->isSupportItem($nameId)) continue;

            // Pull comprehensive metadata for classification
            $meta = $compIndex[$nameId] ?? null;

            // If meta is missing, classification will be unreliable (v1 doesn’t include recipe/tags).
            // We only allow “obvious artifacts” by name as a fallback.
            if (!$meta) {
                if (!str_contains($nameId, 'Artifact')) continue;
            }

            $category = $this->determineItemCategory($nameId, $meta, $setNumber, $setPrefix);
            if ($category === null) continue;

            $iconPath = $this->convertIconPath((string)($item['squareIconPath'] ?? ''));
            $name = (string)($item['name'] ?? $nameId);

            if ($name === '' || $name === 'null') continue;

            $items[] = [
                'id' => $nameId,
                'name' => $name,
                'icon' => $iconPath,
                'category' => $category,
                'recipe' => is_array($meta) ? ($meta['composition'] ?? []) : [],
            ];

            $seenIds[$nameId] = true;
        }

        // Sort by category priority then name
        $categoryOrder = ['component' => 0, 'combined' => 1, 'bilgewater' => 2, 'emblem' => 3, 'artifact' => 4];
        usort($items, function ($a, $b) use ($categoryOrder) {
            $aCat = $categoryOrder[$a['category']] ?? 99;
            $bCat = $categoryOrder[$b['category']] ?? 99;
            return $aCat <=> $bCat ?: strcmp($a['name'], $b['name']);
        });

        Storage::disk('local')->put('tft/items.json', json_encode($items, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        // Stats
        $counts = ['component' => 0, 'combined' => 0, 'bilgewater' => 0, 'emblem' => 0, 'artifact' => 0];
        foreach ($items as $it) {
            $counts[$it['category']] = ($counts[$it['category']] ?? 0) + 1;
        }

        $this->info('  → ' . count($items) . ' items saved.');
        $this->info('    component: ' . ($counts['component'] ?? 0) . ' (target: 9)');
        $this->info('    combined: ' . ($counts['combined'] ?? 0) . ' (target: 39)');
        $this->info('    bilgewater: ' . ($counts['bilgewater'] ?? 0) . ' (target: 8)');
        $this->info('    emblem: ' . ($counts['emblem'] ?? 0) . ' (target: 22)');
        $this->info('    artifact: ' . ($counts['artifact'] ?? 0) . ' (target: 33)');
    }

    private function syncTraits(string $setPrefix): void
    {
        $this->info('Fetching traits...');
        $response = Http::timeout(60)->get(self::TRAITS_URL);

        if (!$response->ok()) {
            $this->error('Failed to fetch traits: ' . $response->status());
            return;
        }

        $allTraits = $response->json();
        $traits = [];

        // Detect the set string (e.g., "TFT16_" → "TFTSet16")
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
     * Build an index of item metadata from the comprehensive JSON.
     * We store multiple possible keys (apiName/nameId) pointing to the same meta.
     */
    private function buildComprehensiveItemIndex(array $compData, string $setNumber): array
    {
        $index = [];

        // Try common shapes first
        $candidateLists = [];

        // Most common (global list/dict)
        if (!empty($compData['items'])) {
            $candidateLists[] = $compData['items'];
        }

        // Some builds may also include set-scoped items list
        if (!empty($compData['sets'][$setNumber]['items'])) {
            $candidateLists[] = $compData['sets'][$setNumber]['items'];
        }

        foreach ($candidateLists as $candidate) {
            // Normalize dict->list
            if (is_array($candidate) && !array_is_list($candidate)) {
                foreach ($candidate as $k => $v) {
                    if (!is_array($v)) continue;
                    $apiName = (string)($v['apiName'] ?? $v['nameId'] ?? $k);
                    if ($apiName !== '') {
                        $index[$apiName] = $v;
                    }
                    $nameId = (string)($v['nameId'] ?? '');
                    if ($nameId !== '') {
                        $index[$nameId] = $v;
                    }
                }
            } elseif (is_array($candidate) && array_is_list($candidate)) {
                foreach ($candidate as $v) {
                    if (!is_array($v)) continue;
                    $apiName = (string)($v['apiName'] ?? $v['nameId'] ?? '');
                    if ($apiName !== '') {
                        $index[$apiName] = $v;
                    }
                    $nameId = (string)($v['nameId'] ?? '');
                    if ($nameId !== '') {
                        $index[$nameId] = $v;
                    }
                }
            }
        }

        return $index;
    }

    /**
     * Decide whether an item is one of the 4 allowed categories; otherwise return null.
     */
    private function determineItemCategory(string $nameId, ?array $meta, string $setNumber, string $setPrefix): ?string
    {
        // ---- Artifact ----
        $isArtifact =
            str_contains($nameId, 'Artifact') ||
            (is_array($meta) && (
                ($meta['isArtifact'] ?? false) === true ||
                $this->metaHasTag($meta, ['artifact', 'ornn'])
            ));

        if ($isArtifact) {
            return 'artifact';
        }

        // ---- Base component ----
        if ($this->isBaseComponent($nameId)) {
            return 'component';
        }

        // From/components for recipe checks (fallback across different comp schemas)
        $from = [];
        if (is_array($meta)) {
            $from = $meta['composition'] ?? $meta['from'] ?? $meta['components'] ?? $meta['recipe'] ?? [];
            if (!is_array($from)) $from = [];
        }

        $hasSpatula = in_array('TFT_Item_Spatula', $from, true) || str_contains($nameId, 'Spatula');

        // Grants trait (emblem-ish signals)
        $grantsTrait =
            is_array($meta) && (
                !empty($meta['trait']) ||
                !empty($meta['traits']) ||
                !empty($meta['grantsTrait']) ||
                !empty($meta['grantTrait'])
            );

        // ---- Emblem ----
        // Primary: uses spatula OR explicit "Emblem" naming OR tag "emblem"
        $isEmblem =
            $hasSpatula ||
            str_contains($nameId, 'Emblem') ||
            (is_array($meta) && $this->metaHasTag($meta, ['emblem']));

        if ($isEmblem) {            // Only Set 16 emblems (strict filtering)
            if (str_starts_with($nameId, $setPrefix . 'Item_') && str_contains($nameId, 'EmblemItem')) {
                return 'emblem';
            }
            return null;
        }

        // ---- Core items ----
        // Core = 2 base components combined item, without spatula
        $isCore =
            count($from) === 2 &&
            !$hasSpatula &&
            $this->isBaseComponent($from[0]) &&
            $this->isBaseComponent($from[1]);

        if ($isCore) {
            return 'combined';
        }

        // ---- Bilgewater / Black Market items ----
        // Heuristics:
        // - set-scoped item names (TFT16_Item_...) that are equipable and NOT emblem/core/artifact
        // - OR any item that clearly associates to traits but isn't emblem (no spatula) and isn't core
        $looksSetScoped = str_starts_with($nameId, $setPrefix . 'Item_');

        $associatedTraits = [];
        if (is_array($meta)) {
            $associatedTraits = $meta['associatedTraits'] ?? $meta['traits'] ?? $meta['trait'] ?? [];
            if (is_string($associatedTraits) && $associatedTraits !== '') $associatedTraits = [$associatedTraits];
            if (!is_array($associatedTraits)) $associatedTraits = [];
        }

        $looksTraitLinked =
            $looksSetScoped ||
            !empty($associatedTraits) ||
            $grantsTrait ||
            (is_array($meta) && $this->metaHasTag($meta, ['trait', 'traititem', 'blackmarket', 'black_market']));

        // Avoid set-scoped “perks/rewards” that aren’t equipable:
        if ($looksSetScoped) {
            // Common non-equipable/perk families you were already skipping; keep the intent, but tighter.
            if (str_contains($nameId, 'Item_Piltover_')) return null;
            if (str_contains($nameId, 'Upgrade')) return null;
            if (str_contains($nameId, 'Refresh')) return null;
            if (str_contains($nameId, 'Reroll')) return null;
            if (str_contains($nameId, 'Duplicator')) return null;
            if (str_contains($nameId, 'FirstFree')) return null;
        }

        if ($looksTraitLinked) {
            // Keep bilgewater items scoped to the current set to avoid leaking old sets.
            if ($looksSetScoped) {
                return 'bilgewater';
            }

            // If it's not set-scoped, only accept if metadata strongly says it's trait-linked.
            if (!empty($associatedTraits) || $this->metaHasTag($meta ?? [], ['trait', 'traititem', 'blackmarket', 'black_market'])) {
                return 'bilgewater';
            }
        }

        return null;
    }

    private function metaHasTag(array $meta, array $tagsWanted): bool
    {
        $tags = $meta['itemTags'] ?? $meta['tags'] ?? [];
        if (!is_array($tags)) return false;

        $tagsLower = array_map(fn($t) => strtolower((string)$t), $tags);
        foreach ($tagsWanted as $tw) {
            if (in_array(strtolower($tw), $tagsLower, true)) return true;
        }

        return false;
    }

    private function isTacticianItem(string $nameId): bool
    {
        // Tactician items (not in your intended output)
        if ($nameId === 'TFT_Item_TacticiansRing') return true;
        if ($nameId === 'TFT_Item_ForceOfNature') return true;
        if ($nameId === 'TFT_Item_TacticiansScepter') return true;

        return false;
    }

    private function isSupportItem(string $nameId): bool
    {
        // Support items (not in your intended output)
        if ($nameId === 'TFT_Item_Chalice') return true;
        if ($nameId === 'TFT_Item_ChonccsChalice') return true;
        if ($nameId === 'TFT_Item_ChonccsCrown') return true;
        if ($nameId === 'TFT_Item_ChonccsSpork') return true;
        if ($nameId === 'TFT_Item_LocketOfTheIronSolari') return true;
        if ($nameId === 'TFT_Item_ZekesHerald') return true;
        if ($nameId === 'TFT_Item_SupportKnightsVow') return true;
        if ($nameId === 'TFT_Item_Moonstone') return true;
        if ($nameId === 'TFT_Item_AegisOfTheLegion') return true;
        if ($nameId === 'TFT_Item_BansheesVeil') return true;
        if ($nameId === 'TFT_Item_RadiantVirtue') return true;
        if ($nameId === 'TFT_Item_SentinelSwarm') return true;
        if ($nameId === 'TFT_Item_TitanicHydra') return true;
        if ($nameId === 'TFT_Item_EternalFlame') return true;

        return false;
    }

    /**
     * Convert CDragon asset path to full URL.
     */
    private function convertIconPath(string $path): string
    {
        if (empty($path)) return '';

        $cleanPath = str_replace('/lol-game-data/assets/', '', $path);
        $cleanPath = strtolower($cleanPath);

        // Convert .tex/.dds texture files to .png
        $cleanPath = preg_replace('/\.(tex|dds)$/', '.png', $cleanPath);

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

        return in_array($nameId, $components, true);
    }
}
