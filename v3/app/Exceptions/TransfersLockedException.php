<?php

namespace App\Exceptions;

/**
 * Thrown when a transfer is attempted while transfers are disabled.
 */
class TransfersLockedException extends \RuntimeException
{
    /**
     * Create a new TransfersLockedException instance.
     */
    public function __construct(string $message = 'Transfers are currently disabled.')
    {
        parent::__construct($message);
    }
}
