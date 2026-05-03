<?php

namespace App\Dto;

class DashboardDataDto
{
    public float $currentUserTotal = 0.0;
    public float $partnerTotal = 0.0;
    public string $partnerName = 'Partner';
    
    /**
     * Positive means the partner owes the current user.
     * Negative means the current user owes the partner.
     */
    public float $balance = 0.0;
    
    public float $needleAngle = 0.0;

    /** @var ExpenseDto[] */
    public array $recentExpenses = [];
}
