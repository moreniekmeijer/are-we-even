<?php

namespace App\Mapper;

use App\Dto\ExpenseDto;
use App\Entity\Expense;

class ExpenseMapper
{
    public function toDto(Expense $expense): ExpenseDto
    {
        $dto = new ExpenseDto();
        $dto->id = $expense->getId();
        $dto->description = $expense->getDescription();
        $dto->amount = $expense->getAmount();
        $dto->createdAt = $expense->getCreatedAt();
        
        if ($expense->getPayer()) {
            $dto->userId = $expense->getPayer()->getId();
            $dto->userEmail = $expense->getPayer()->getEmail();
            $dto->userName = $expense->getPayer()->getName();
        }

        return $dto;
    }
}
