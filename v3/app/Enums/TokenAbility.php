<?php

namespace App\Enums;

enum TokenAbility: string
{
    case Read = 'storage:read';
    case Write = 'storage:write';
    case Admin = 'storage:admin';

    /** @return array<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
