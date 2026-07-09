<?php

namespace App\Enums;

/**
 * Abilities that can be assigned to Sanctum API tokens.
 *
 * Each case maps to a permission scope used by the RequireAbility middleware.
 * - Read:  GET access on all storage endpoints
 * - Write: POST/PATCH/DELETE on inventory, transfer, and label claim
 * - Admin: CRUD categories & item types; bulk mint; settings; create labels
 */
enum TokenAbility: string
{
    case Read = 'storage:read';
    case Write = 'storage:write';
    case Admin = 'storage:admin';

    /**
     * Get all ability strings as a flat array.
     *
     * Useful for artisan commands and validation (e.g. token issuing).
     *
     * @return array<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
