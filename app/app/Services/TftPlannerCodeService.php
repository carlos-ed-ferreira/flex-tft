<?php

namespace App\Services;

use App\Support\TftPlannerChampionCodes;
use InvalidArgumentException;

class TftPlannerCodeService
{
    private const FORMAT_VERSION = '02';

    private const SET = 'TFTSet17';

    private const SLOT_COUNT = 10;

    private const SLOT_LENGTH = 3;

    private const EMPTY_SLOT = '000';

    public function encode(array $championIds): string
    {
        $championIds = $this->normalizeChampionIds($championIds);

        if (empty($championIds)) {
            throw new InvalidArgumentException('A composição precisa ter pelo menos um campeão para exportar.');
        }

        if (count($championIds) > self::SLOT_COUNT) {
            throw new InvalidArgumentException('O planner aceita no máximo 10 campeões.');
        }

        $championToCode = TftPlannerChampionCodes::championToCode();
        $slots = [];

        foreach ($championIds as $championId) {
            if (! isset($championToCode[$championId])) {
                throw new InvalidArgumentException("O campeão {$championId} ainda não possui código do planner.");
            }

            $slots[] = $championToCode[$championId];
        }

        while (count($slots) < self::SLOT_COUNT) {
            $slots[] = self::EMPTY_SLOT;
        }

        return self::FORMAT_VERSION . implode('', $slots) . self::SET;
    }

    public function decode(string $code): array
    {
        $normalizedCode = preg_replace('/\s+/', '', trim($code));
        $payloadLength = self::SLOT_COUNT * self::SLOT_LENGTH;

        if (! preg_match('/^([0-9a-fA-F]{2})([0-9a-fA-F]{' . $payloadLength . '})(TFTSet\d+)$/', $normalizedCode, $matches)) {
            throw new InvalidArgumentException('Informe um código válido do planner do TFT.');
        }

        if ($matches[1] !== self::FORMAT_VERSION) {
            throw new InvalidArgumentException('Esta versão do código do planner ainda não é suportada.');
        }

        if ($matches[3] !== self::SET) {
            throw new InvalidArgumentException('Este set do planner ainda não é suportado.');
        }

        $codeToChampion = TftPlannerChampionCodes::codeToChampion();
        $championIds = [];

        foreach (str_split(strtolower($matches[2]), self::SLOT_LENGTH) as $slot) {
            if ($slot === self::EMPTY_SLOT) {
                continue;
            }

            if (! isset($codeToChampion[$slot])) {
                throw new InvalidArgumentException("O código {$slot} ainda não está mapeado para importação.");
            }

            $championIds[] = $codeToChampion[$slot];
        }

        if (empty($championIds)) {
            throw new InvalidArgumentException('O código do planner não possui campeões.');
        }

        return $championIds;
    }

    private function normalizeChampionIds(array $championIds): array
    {
        $normalized = [];

        foreach ($championIds as $championId) {
            $championId = trim((string) $championId);

            if ($championId === '' || isset($normalized[$championId])) {
                continue;
            }

            $normalized[$championId] = $championId;
        }

        return array_values($normalized);
    }
}
