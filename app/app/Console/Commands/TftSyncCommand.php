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

    // --- Canonical base components (your app expects these) ---
    private const BASE_COMPONENTS = [
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

    // --- Support / Tactician items excluded from output (keep stable) ---
    private const TACTICIAN_ITEMS = [
        'TFT_Item_TacticiansRing',
        'TFT_Item_ForceOfNature',
        'TFT_Item_TacticiansScepter',
    ];

    private const SUPPORT_ITEMS = [
        'TFT_Item_Chalice',
        'TFT_Item_ChonccsChalice',
        'TFT_Item_ChonccsCrown',
        'TFT_Item_ChonccsSpork',
        'TFT_Item_LocketOfTheIronSolari',
        'TFT_Item_ZekesHerald',
        'TFT_Item_SupportKnightsVow',
        'TFT_Item_Moonstone',
        'TFT_Item_AegisOfTheLegion',
        'TFT_Item_BansheesVeil',
        'TFT_Item_RadiantVirtue',
        'TFT_Item_SentinelSwarm',
        'TFT_Item_TitanicHydra',
        'TFT_Item_EternalFlame',
    ];

    /**
     * Bilgewater placeholders / NYI that appear in static exports but are not equipable in-game.
     * Keep isolated and explicit (fallback only; prefer metadata when available).
     */
    private const BILGE_BLOCKLIST_NAMES = [
        "brigand's dice",
        "captain's hat",
        "dreadway cannon",
        "haunted spyglass",
    ];

    private const BILGE_BLOCKLIST_ID_FRAGMENTS = [
        'brigandsdice',
        'captainshat',
        'dreadwaycannon',
        'hauntedspyglass',
    ];

    /**
     * --- Set 16 artifact pool hotfix ---
     * Force include these into "artifact" even if metadata fails to match.
     * (Names are normalized with normalizeKey())
     */
    private const ARTIFACT_FORCE_INCLUDE_NAMES = [
        "crown of demacia",
        "death's defiance",
        "flickerblades",
        "gambler's blade",
        "hullcrusher",
        "infinity force",
        "mogul's mail",
        "sniper's focus",
        "the darkin aegis",
        "the darkin bow",
        "the darkin scythe",
        "the darkin staff",
        "zhonya's paradox",
        "gold collector",
    ];

    /**
     * Force include (id fragments) – covers cases where name shows as "The Collector"
     * and/or the canonical ID is like TFT4_Item_OrnnTheCollector.
     */
    private const ARTIFACT_FORCE_INCLUDE_ID_FRAGMENTS = [
        'thecollector',
    ];

    /**
     * Force exclude artifacts that are leaking in your Set 16 export.
     * Includes your typos to be safe.
     */
    private const ARTIFACT_FORCE_EXCLUDE_NAMES = [
        "corrupt vampiric scepter",
        "forbidden idol",
        "forbbiden idol",
        "innervating locket",
        "lesser mirrored persona",
        "mending echoes",
        "mirrored persona",
        "shadow puppet",
        "spectral cutlass",
        "suspicious trench coat",
        "undending despair",
        "unending despair",
    ];

    public function handle(): int
    {
        $setNumber = (string)$this->option('set');
        $setPrefix = "TFT{$setNumber}_";

        Storage::disk('local')->makeDirectory('tft');

        $compResponse = Http::timeout(120)->get(self::COMPREHENSIVE_URL);
        if (!$compResponse->ok()) {
            $this->error('Failed to fetch comprehensive data: ' . $compResponse->status());
            return Command::FAILURE;
        }

        $compData = $compResponse->json();
        if (!is_array($compData)) {
            $this->error('Comprehensive data response is not an array');
            return Command::FAILURE;
        }

        $this->syncChampions($setNumber, $compData);
        $this->syncItems($setNumber, $setPrefix, $compData);
        $this->syncTraits($setPrefix);

        return Command::SUCCESS;
    }

    private function syncChampions(string $setNumber, array $compData): void
    {
        $setData = $compData['sets'][$setNumber] ?? null;
        if (!$setData || empty($setData['champions'])) {
            $this->error("No champion data found for Set {$setNumber}");
            return;
        }

        $champions = [];

        foreach ($setData['champions'] as $champion) {
            $apiName = (string)($champion['apiName'] ?? '');
            $cost = (int)($champion['cost'] ?? 0);
            $name = (string)($champion['name'] ?? '');
            $traits = $champion['traits'] ?? [];

            if (empty($traits)) continue;
            if ($cost > 10) continue;

            $iconPath = $this->convertIconPath((string)($champion['squareIcon'] ?? $champion['icon'] ?? ''));

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

        usort($champions, function ($a, $b) {
            return $a['cost'] <=> $b['cost'] ?: strcmp($a['name'], $b['name']);
        });

        Storage::disk('local')->put(
            'tft/champions.json',
            json_encode($champions, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
        );
    }

    private function syncItems(string $setNumber, string $setPrefix, array $compData): void
    {
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

        $compIndex = $this->buildComprehensiveItemIndex($compData, $setNumber);

        $items = [];
        $seenIds = [];

        // Dedupe-by-name ONLY for artifacts (fixes Gambler's Blade / Mogul's Mail duplicates)
        $artifactNameIndex = []; // nameKey => index in $items
        $artifactNameScore = []; // nameKey => score

        foreach ($v1Items as $item) {
            if (!is_array($item)) continue;

            $nameId = (string)($item['nameId'] ?? '');
            if ($nameId === '' || isset($seenIds[$nameId])) continue;

            if ($this->shouldSkipV1Item($nameId)) {
                $seenIds[$nameId] = true;
                continue;
            }

            $name = (string)($item['name'] ?? $nameId);
            if ($name === '' || $name === 'null') {
                $seenIds[$nameId] = true;
                continue;
            }

            $nameKey = $this->normalizeKey($name);

            // If in the artifact blacklist, cut immediately (regardless of meta/category)
            if ($this->isArtifactForceExcluded($nameKey)) {
                $seenIds[$nameId] = true;
                continue;
            }

            $meta = $compIndex[$nameId] ?? null;

            // Critical: If meta is missing, only allow likely artifacts (Ornn/Artifact) OR force-include list
            if (!$meta) {
                $looksArtifact = $this->isLikelyArtifactId($nameId) || $this->isArtifactForceIncluded($nameKey, $nameId);
                if (!$looksArtifact) {
                    $seenIds[$nameId] = true;
                    continue;
                }
            }

            $category = $this->determineItemCategory($nameId, $meta, $setNumber, $setPrefix);

            // Force include the listed artifacts even if classification fails
            if ($this->isArtifactForceIncluded($nameKey, $nameId)) {
                $category = 'artifact';
            }

            if ($category === null) {
                $seenIds[$nameId] = true;
                continue;
            }

            // Bilge: keep deterministic blocklist
            if ($category === 'bilgewater' && $this->shouldSkipBilgeItem($nameId, $name, $meta)) {
                $seenIds[$nameId] = true;
                continue;
            }

            // Safety: if blacklist somehow lands in artifact, cut again
            if ($category === 'artifact' && $this->isArtifactForceExcluded($nameKey)) {
                $seenIds[$nameId] = true;
                continue;
            }

            $payload = [
                'id' => $nameId,
                'name' => $name,
                'icon' => $this->convertIconPath((string)($item['squareIconPath'] ?? '')),
                'category' => $category,
                'recipe' => $this->getRecipeFromMeta($meta),
            ];

            // Artifact-only dedupe by name (choose the "best" duplicate)
            if ($category === 'artifact') {
                $score = $this->artifactPriorityScore($nameId, $meta);

                if (!isset($artifactNameIndex[$nameKey])) {
                    $artifactNameIndex[$nameKey] = count($items);
                    $artifactNameScore[$nameKey] = $score;
                    $items[] = $payload;
                } else {
                    if ($score > ($artifactNameScore[$nameKey] ?? -1)) {
                        $idx = $artifactNameIndex[$nameKey];
                        $items[$idx] = $payload;
                        $artifactNameScore[$nameKey] = $score;
                    }
                }

                $seenIds[$nameId] = true;
                continue;
            }

            $items[] = $payload;
            $seenIds[$nameId] = true;
        }

        $categoryOrder = ['component' => 0, 'combined' => 1, 'bilgewater' => 2, 'emblem' => 3, 'artifact' => 4];
        usort($items, function ($a, $b) use ($categoryOrder) {
            $aCat = $categoryOrder[$a['category']] ?? 99;
            $bCat = $categoryOrder[$b['category']] ?? 99;
            return $aCat <=> $bCat ?: strcmp($a['name'], $b['name']);
        });

        Storage::disk('local')->put(
            'tft/items.json',
            json_encode($items, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
        );
    }

    private function syncTraits(string $setPrefix): void
    {
        $response = Http::timeout(60)->get(self::TRAITS_URL);

        if (!$response->ok()) {
            $this->error('Failed to fetch traits: ' . $response->status());
            return;
        }

        $allTraits = $response->json();
        if (!is_array($allTraits)) {
            $this->error('Traits response is not an array');
            return;
        }

        $traits = [];

        preg_match('/TFT(\d+)_/', $setPrefix, $matches);
        $setString = 'TFTSet' . ($matches[1] ?? '');

        foreach ($allTraits as $trait) {
            if (!is_array($trait)) continue;

            $traitSet = (string)($trait['set'] ?? '');
            if ($traitSet !== $setString) continue;

            $iconPath = $this->convertIconPath((string)($trait['icon_path'] ?? ''));

            $breakpoints = [];
            foreach (($trait['conditional_trait_sets'] ?? []) as $bp) {
                if (!is_array($bp)) continue;

                $breakpoints[] = [
                    'min' => (int)($bp['min_units'] ?? 0),
                    'max' => (int)($bp['max_units'] ?? 0),
                    'style' => (string)($bp['style_name'] ?? ''),
                ];
            }

            $traits[] = [
                'id' => (string)($trait['trait_id'] ?? ''),
                'name' => (string)($trait['display_name'] ?? ''),
                'icon' => $iconPath,
                'breakpoints' => $breakpoints,
            ];
        }

        usort($traits, fn($a, $b) => strcmp($a['name'], $b['name']));

        Storage::disk('local')->put(
            'tft/traits.json',
            json_encode($traits, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
        );
    }

    private function buildComprehensiveItemIndex(array $compData, string $setNumber): array
    {
        $index = [];
        $candidateLists = [];

        if (!empty($compData['items'])) {
            $candidateLists[] = $compData['items'];
        }

        if (!empty($compData['sets'][$setNumber]['items'])) {
            $candidateLists[] = $compData['sets'][$setNumber]['items'];
        }

        foreach ($candidateLists as $candidate) {
            if (!is_array($candidate)) continue;

            if (!array_is_list($candidate)) {
                foreach ($candidate as $k => $v) {
                    if (!is_array($v)) continue;
                    $this->indexComprehensiveItem($index, $v, (string)$k);
                }
                continue;
            }

            foreach ($candidate as $v) {
                if (!is_array($v)) continue;
                $this->indexComprehensiveItem($index, $v, '');
            }
        }

        return $index;
    }

    private function indexComprehensiveItem(array &$index, array $v, string $fallbackKey): void
    {
        $apiName = (string)($v['apiName'] ?? $v['nameId'] ?? $fallbackKey);
        if ($apiName !== '') $index[$apiName] = $v;

        $nameId = (string)($v['nameId'] ?? '');
        if ($nameId !== '') $index[$nameId] = $v;
    }

    private function determineItemCategory(string $nameId, ?array $meta, string $setNumber, string $setPrefix): ?string
    {
        // ---- Artifact ----
        $isArtifact =
            str_contains($nameId, 'Artifact') ||
            (is_array($meta) && (
                ($meta['isArtifact'] ?? false) === true ||
                $this->metaHasTag($meta, ['artifact', 'ornn'])
            ));

        if ($isArtifact) return 'artifact';

        // ---- Base component ----
        if ($this->isBaseComponent($nameId)) return 'component';

        $from = $this->getFromForRecipe($nameId, $meta);
        $hasSpatula = in_array('TFT_Item_Spatula', $from, true) || str_contains($nameId, 'Spatula');

        $grantsTrait = is_array($meta) && (
            !empty($meta['trait']) ||
            !empty($meta['traits']) ||
            !empty($meta['grantsTrait']) ||
            !empty($meta['grantTrait'])
        );

        // ---- Emblem ----
        $isEmblem =
            $hasSpatula ||
            str_contains($nameId, 'Emblem') ||
            (is_array($meta) && $this->metaHasTag($meta, ['emblem']));

        if ($isEmblem) {
            if (str_starts_with($nameId, $setPrefix . 'Item_') && str_contains($nameId, 'EmblemItem')) {
                return 'emblem';
            }
            return null;
        }

        // ---- Core combined ----
        $isCore =
            count($from) === 2 &&
            !$hasSpatula &&
            $this->isBaseComponent($from[0]) &&
            $this->isBaseComponent($from[1]);

        if ($isCore) return 'combined';

        // ---- Bilgewater / Black Market ----
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

        if ($looksSetScoped) {
            if (str_contains($nameId, 'Item_Piltover_')) return null;
            if (str_contains($nameId, 'Upgrade')) return null;
            if (str_contains($nameId, 'Refresh')) return null;
            if (str_contains($nameId, 'Reroll')) return null;
            if (str_contains($nameId, 'Duplicator')) return null;
            if (str_contains($nameId, 'FirstFree')) return null;
        }

        if ($looksTraitLinked) {
            if ($looksSetScoped) return 'bilgewater';

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

    private function shouldSkipV1Item(string $nameId): bool
    {
        if (str_contains($nameId, 'Augment')) return true;
        if (str_contains($nameId, 'ChampionItem')) return true;

        if (preg_match('/_(AD|AP|AS|ADAP|Health|ArmorMR)Tier\d+$/', $nameId)) return true;

        if ($this->isTacticianItem($nameId)) return true;
        if ($this->isSupportItem($nameId)) return true;

        return false;
    }

    private function shouldSkipBilgeItem(string $nameId, string $name, ?array $meta): bool
    {
        if ($this->metaSaysNotEquipable($meta)) return true;

        $nameLower = $this->normalizeKey($name);
        if (in_array($nameLower, self::BILGE_BLOCKLIST_NAMES, true)) return true;

        $idLower = $this->normalizeKey($nameId);
        foreach (self::BILGE_BLOCKLIST_ID_FRAGMENTS as $frag) {
            if (str_contains($idLower, $frag)) return true;
        }

        return false;
    }

    private function metaSaysNotEquipable(?array $meta): bool
    {
        if (!is_array($meta)) return false;

        $truthyKeys = [
            'isNotEquipable',
            'notEquipable',
            'isNonEquipable',
            'nonEquipable',
            'isDisabled',
            'disabled',
            'isDeprecated',
            'deprecated',
            'isHidden',
            'hidden',
            'isTutorial',
            'tutorial',
            'isNYI',
            'nyi',
        ];

        foreach ($truthyKeys as $k) {
            if (($meta[$k] ?? false) === true) return true;
        }

        return false;
    }

    private function getFromForRecipe(string $nameId, ?array $meta): array
    {
        if (!is_array($meta)) return [];

        $from = $meta['composition'] ?? $meta['from'] ?? $meta['components'] ?? $meta['recipe'] ?? [];
        if (!is_array($from)) return [];

        return array_values(array_filter($from, fn($v) => is_string($v) && $v !== ''));
    }

    private function getRecipeFromMeta(?array $meta): array
    {
        if (!is_array($meta)) return [];

        $recipe = $meta['composition'] ?? [];
        return is_array($recipe) ? $recipe : [];
    }

    private function isTacticianItem(string $nameId): bool
    {
        return in_array($nameId, self::TACTICIAN_ITEMS, true);
    }

    private function isSupportItem(string $nameId): bool
    {
        return in_array($nameId, self::SUPPORT_ITEMS, true);
    }

    /**
     * Normalize string keys (lowercase, trim, normalize apostrophes and whitespace)
     */
    private function normalizeKey(string $value): string
    {
        $v = strtolower(trim($value));
        $v = str_replace(["’", "‘", "´", "`"], ["'", "'", "'", "'"], $v);
        $v = preg_replace('/\s+/', ' ', $v) ?? $v;
        return $v;
    }

    /**
     * "Likely artifact" ids that may not contain literal "Artifact" but are Ornn artifacts etc.
     */
    private function isLikelyArtifactId(string $nameId): bool
    {
        return str_contains($nameId, 'Ornn') || str_starts_with($nameId, 'TFT_Item_Artifact_');
    }

    private function isArtifactForceIncluded(string $nameKey, string $nameId): bool
    {
        if (in_array($nameKey, self::ARTIFACT_FORCE_INCLUDE_NAMES, true)) {
            return true;
        }

        $idLower = $this->normalizeKey($nameId);
        foreach (self::ARTIFACT_FORCE_INCLUDE_ID_FRAGMENTS as $frag) {
            if ($frag !== '' && str_contains($idLower, $frag)) {
                return true;
            }
        }

        return false;
    }

    private function isArtifactForceExcluded(string $nameKey): bool
    {
        return in_array($nameKey, self::ARTIFACT_FORCE_EXCLUDE_NAMES, true);
    }

    /**
     * Used to choose the best duplicate when multiple IDs share the same artifact name.
     */
    private function artifactPriorityScore(string $nameId, ?array $meta): int
    {
        $score = 0;

        if (str_contains($nameId, 'Ornn')) $score += 3;
        if (str_contains($nameId, 'Artifact')) $score += 2;
        if (is_array($meta) && ($meta['isArtifact'] ?? false) === true) $score += 2;
        if (is_array($meta) && $this->metaHasTag($meta, ['artifact', 'ornn'])) $score += 1;

        return $score;
    }

    private function convertIconPath(string $path): string
    {
        if (empty($path)) return '';

        $cleanPath = str_replace('/lol-game-data/assets/', '', $path);
        $cleanPath = strtolower($cleanPath);

        $cleanPath = preg_replace('/\.(tex|dds)$/', '.png', $cleanPath);

        return self::CDRAGON_BASE . '/' . $cleanPath;
    }

    private function isBaseComponent(string $nameId): bool
    {
        return in_array($nameId, self::BASE_COMPONENTS, true);
    }
}
