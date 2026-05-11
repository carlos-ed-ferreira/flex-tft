<?php

namespace App\Support;

class TftPlannerChampionCodes
{
    public static function championToCode(): array
    {
        return [
            'TFT17_MasterYi' => '00f',
            'TFT17_Kindred' => '013',
            'TFT17_Fiora' => '01e',
            'TFT17_Maokai' => '01f',
            'TFT17_Gragas' => '024',
            'TFT17_Urgot' => '02f',
            'TFT17_TahmKench' => '031',
            'TFT17_Belveth' => '04f',
        ];
    }

    public static function codeToChampion(): array
    {
        return array_flip(self::championToCode());
    }
}
