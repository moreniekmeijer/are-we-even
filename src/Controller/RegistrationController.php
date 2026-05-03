<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\RegistrationFormType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

class RegistrationController extends AbstractController
{
    #[Route('/register', name: 'app_register')]
    public function register(Request $request, UserPasswordHasherInterface $userPasswordHasher, Security $security, EntityManagerInterface $entityManager): Response
    {
        $token = $request->query->get('token');
        
        $user = new User();
        // If there's a token, we could pre-fill the email but we don't know it securely without querying the DB here
        // Let's just process the form as usual.
        
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var string $plainPassword */
            $plainPassword = $form->get('plainPassword')->getData();

            $user->setPassword($userPasswordHasher->hashPassword($user, $plainPassword));

            $entityManager->persist($user);
            $entityManager->flush();

            // Process invite token if present
            if ($token) {
                $invite = $entityManager->getRepository(\App\Entity\RelationInvite::class)->findOneBy(['token' => $token, 'status' => 'pending']);
                if ($invite) {
                    $relation = new \App\Entity\Relation();
                    $relation->setUser1($invite->getInviter());
                    $relation->setUser2($user);
                    $entityManager->persist($relation);
                    
                    $invite->setStatus('accepted');
                    $user->setLastViewedRelation($relation);
                    
                    $entityManager->flush();
                }
            }

            return $security->login($user, 'form_login', 'main');
        }

        return $this->render('registration/register.html.twig', [
            'registrationForm' => $form,
        ]);
    }
}
