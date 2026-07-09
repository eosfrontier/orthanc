<?php

namespace App\Enums;

/**
 * Actions recorded in the storage audit log.
 *
 * Each case maps to a distinct inventory operation that produces a log entry.
 */
enum LogAction: string
{
    case Mint = 'mint';
    case Burn = 'burn';
    case Transfer = 'transfer';
    case BrokerFee = 'broker_fee';
    case LabelClaim = 'label_claim';
    case LabelBurn = 'label_burn';
}
