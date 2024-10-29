<?php

namespace App\Enums;

use BackedEnum;

enum ProductStockEnum: string
{
    case OUT = 'OUT';
    case IN = 'IN';

    case INITIAL = 'INITIAL';

    public static function values(): array
    {
        $cases = self::cases();

        return isset($cases[0]) && $cases[0] instanceof BackedEnum
            ? array_column($cases, 'value')
            : array_column($cases, 'name');
    }
}
