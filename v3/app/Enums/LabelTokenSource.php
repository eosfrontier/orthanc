<?php

namespace App\Enums;

/**
 * The source type for a label token, determining how inventory is affected at creation time.
 *
 * - Mint: no inventory impact until the token is claimed.
 * - Burn: items are deducted from the source character's inventory at creation.
 */
enum LabelTokenSource: string
{
    case Mint = 'mint';
    case Burn = 'burn';
}
