<?php

namespace App\Service;

use App\Dto\DashboardDataDto;
use App\Dto\ExpenseDto;
use App\Entity\Expense;
use App\Entity\Relation;
use App\Entity\User;
use App\Mapper\ExpenseMapper;
use Doctrine\ORM\EntityManagerInterface;

class ExpenseService
{
    private EntityManagerInterface $em;
    private ExpenseMapper $mapper;

    public function __construct(EntityManagerInterface $em, ExpenseMapper $mapper)
    {
        $this->em = $em;
        $this->mapper = $mapper;
    }

    public function addExpense(User $payer, Relation $relation, string $description, string $amount): Expense
    {
        $expense = new Expense();
        $expense->setDescription($description);
        $expense->setAmount($amount);
        $expense->setPayer($payer);
        $expense->setRelation($relation);

        $this->em->persist($expense);
        $this->em->flush();

        return $expense;
    }

    public function getDashboardData(User $currentUser, ?Relation $activeRelation): ?DashboardDataDto
    {
        if (!$activeRelation) {
            return null;
        }

        $expenses = $this->em->getRepository(Expense::class)->findBy(['relation' => $activeRelation], ['createdAt' => 'DESC']);

        $myTotal = 0.0;
        $partnerTotal = 0.0;
        $recentDtos = [];

        $partnerUser = $activeRelation->getOtherUser($currentUser);
        $partnerName = $partnerUser ? $partnerUser->getName() : 'Partner';
        
        foreach ($expenses as $expense) {
            $amount = (float) $expense->getAmount();
            $expensePayer = $expense->getPayer();
            
            if ($expensePayer->getId() === $currentUser->getId()) {
                $myTotal += $amount;
            } else {
                $partnerTotal += $amount;
            }

            // Only take the first 10 for recent expenses
            if (count($recentDtos) < 10) {
                $recentDtos[] = $this->mapper->toDto($expense);
            }
        }

        $dto = new DashboardDataDto();
        $dto->currentUserTotal = $myTotal;
        $dto->partnerTotal = $partnerTotal;
        $dto->partnerName = $partnerName;
        
        $dto->balance = $myTotal - $partnerTotal;
        
        $absBalance = abs($dto->balance);
        $angle = 0;
        if ($absBalance >= 1) {
            $angle = (log($absBalance, 2) / 10.0) * 90.0;
        }
        if ($angle > 90) {
            $angle = 90.0;
        }
        $dto->needleAngle = $dto->balance < 0 ? -$angle : $angle;
        
        $dto->recentExpenses = $recentDtos;

        return $dto;
    }
}
