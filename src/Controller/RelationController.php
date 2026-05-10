<?php

namespace App\Controller;

use App\Entity\Relation;
use App\Entity\RelationInvite;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Routing\Attribute\Route;

final class RelationController extends AbstractController
{
    #[Route('/invite', name: 'app_invite', methods: ['POST'])]
    public function invite(Request $request, EntityManagerInterface $em, MailerInterface $mailer): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $token = $request->request->get('_csrf_token');
        if (!$this->isCsrfTokenValid('send-invite', $token)) {
            $this->addFlash('error', 'Invalid CSRF token.');
            return $this->redirectToRoute('app_user');
        }

        $email = $request->request->get('email');
        if (!$email) {
            $this->addFlash('error', 'Please provide an email.');
            return $this->redirectToRoute('app_user');
        }

        if ($email === $user->getEmail()) {
            $this->addFlash('error', 'You cannot invite yourself!');
            return $this->redirectToRoute('app_user');
        }

        // Check if user already exists
        $existingUser = $em->getRepository(User::class)->findOneBy(['email' => $email]);
        if ($existingUser) {
            // Check if relation already exists
            $existingRelation = $em->getRepository(Relation::class)->createQueryBuilder('r')
                ->where('(r.user1 = :u1 AND r.user2 = :u2) OR (r.user1 = :u2 AND r.user2 = :u1)')
                ->setParameter('u1', $user)
                ->setParameter('u2', $existingUser)
                ->getQuery()
                ->getOneOrNullResult();

            if (!$existingRelation) {
                $relation = new Relation();
                $relation->setUser1($user);
                $relation->setUser2($existingUser);
                $em->persist($relation);

                $user->setLastViewedRelation($relation);
                $em->flush();

                $this->addFlash('success', 'Relation created with ' . $existingUser->getName() . '!');
            } else {
                $this->addFlash('info', 'You are already connected to this user.');
            }
            return $this->redirectToRoute('app_user');
        }

        // Create invite
        $token = bin2hex(random_bytes(32));
        $invite = new RelationInvite();
        $invite->setInviter($user);
        $invite->setInviteeEmail($email);
        $invite->setToken($token);

        $em->persist($invite);
        $em->flush();

        // Send Email
        $emailMessage = (new TemplatedEmail())
            ->from('areweevenapp@gmail.com')
            ->to($email)
            ->subject('You have been invited to Are We Even!')
            ->htmlTemplate('emails/invite.html.twig')
            ->context([
                'inviterName' => $user->getName(),
                'token' => $token,
            ]);

        try {
            $mailer->send($emailMessage);
            $this->addFlash('success', 'Invitation sent to ' . $email . '!');
        } catch (\Exception $e) {
            $this->addFlash('error', 'Could not send email. SMTP might not be configured.');
        }

        return $this->redirectToRoute('app_user');
    }

    #[Route('/switch-relation/{id}', name: 'app_switch_relation')]
    public function switchRelation(Relation $relation, EntityManagerInterface $em): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        if ($relation->getUser1() === $user || $relation->getUser2() === $user) {
            $user->setLastViewedRelation($relation);
            $em->flush();
        }
        return $this->redirectToRoute('app_user');
    }
}
