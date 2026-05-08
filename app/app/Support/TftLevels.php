<?php

namespace App\Support;

class TftLevels
{
    public const VALUES = [3, 4, 5, 6, 7, 8, 9, 10];

    public static function values(): array
    {
        return self::VALUES;
    }

    public static function validationValues(): string
    {
        return implode(',', self::VALUES);
    }
}
