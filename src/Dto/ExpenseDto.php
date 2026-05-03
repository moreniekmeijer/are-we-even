<?php

namespace App\Dto;

class ExpenseDto
{
    public ?int $id = null;
    public ?string $description = null;
    public ?string $amount = null;
    public ?\DateTimeInterface $createdAt = null;
    public ?int $userId = null;
    public ?string $userEmail = null;
    public ?string $userName = null;
}
