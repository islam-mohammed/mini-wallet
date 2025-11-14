<?php

namespace App\Domain\Wallet\Exceptions;

use RuntimeException;

class InsufficientBalanceException extends RuntimeException
{
    public function __construct(string $message = 'Insufficient balance for this transfer.')
    {
        parent::__construct($this->message);
    }
}
