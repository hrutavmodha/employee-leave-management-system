<?php

namespace App\Exceptions;

class InsufficientLeaveBalanceException extends DomainException
{
    protected int $year;
    protected float $remainingDays;
    protected float $requestedDays;

    public function __construct(int $year, float $remainingDays, float $requestedDays, string $message = "")
    {
        $this->year = $year;
        $this->remainingDays = $remainingDays;
        $this->requestedDays = $requestedDays;

        if ($message === "") {
            $message = "Insufficient balance. User has {$remainingDays} days for year {$year}, but requested {$requestedDays}.";
        }

        parent::__construct($message);
    }

    public function getYear(): int
    {
        return $this->year;
    }

    public function getRemainingDays(): float
    {
        return $this->remainingDays;
    }

    public function getRequestedDays(): float
    {
        return $this->requestedDays;
    }
}
