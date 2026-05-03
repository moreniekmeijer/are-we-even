<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Entity\Relation;
use App\Service\ExpenseService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;

final class UserController extends AbstractController
{
    #[Route('/user', name: 'app_user')]
    public function index(Request $request, ExpenseService $expenseService, EntityManagerInterface $em): Response
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $relations = $em->getRepository(Relation::class)->createQueryBuilder('r')
            ->where('r.user1 = :u OR r.user2 = :u')
            ->setParameter('u', $user)
            ->getQuery()
            ->getResult();

        $activeRelation = $user->getLastViewedRelation();
        if (!$activeRelation && count($relations) > 0) {
            $activeRelation = $relations[0];
            $user->setLastViewedRelation($activeRelation);
            $em->flush();
        }

        if ($request->isMethod('POST') && $activeRelation) {
            $token = $request->request->get('_csrf_token');
            if (!$this->isCsrfTokenValid('add-expense', $token)) {
                $this->addFlash('error', 'Invalid CSRF token.');
                return $this->redirectToRoute('app_user');
            }

            $amount = $request->request->get('amount');
            $description = $request->request->get('description');
            if ($amount && $description) {
                $expenseService->addExpense($user, $activeRelation, $description, $amount);
                $this->addFlash('success', 'Expense added successfully.');
                return $this->redirectToRoute('app_user');
            }
        }

        $dashboardData = $expenseService->getDashboardData($user, $activeRelation);

        return $this->render('user/index.html.twig', [
            'dashboardData' => $dashboardData,
            'activeRelation' => $activeRelation,
            'relations' => $relations,
        ]);
    }
}
