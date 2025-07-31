<?php

namespace App\Controller;

use App\Entity\AppUser;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Cookie;




#[Route('/auth', name: 'app_auth')]
final class AuthController extends AbstractController
{
    #[Route('/auth_page', name: 'app_auth_page')]
    public function auth(): Response
    {
        return $this->render('auth.html.twig');
    }

    
    #[Route('/auth/logout', name: 'app__logout', methods: ['POST'])]
    public function logout(): Response
    {
        $cookie = Cookie::create('BEARER') 
            ->withValue('')
            ->withExpires(new \DateTime('-1 day'))
            ->withPath('/')
            ->withHttpOnly(true)
            ->withSecure(true) 
            ->withSameSite('lax'); 
        // Clear the session
        return new JsonResponse(['message' => 'Logged out'], Response::HTTP_OK, ['Set-Cookie' => $cookie->__toString()]);
    }

    #[Route('/register', name: 'app_register', methods: ['POST'])]
    public function register(Request $request, UserPasswordHasherInterface $passwordHasher, EntityManagerInterface $em): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        if (!isset($data['username'], $data['password'])) {
            return new JsonResponse(['error' => 'Username and password are required'], 400);
        }

        $existingUser = $em->getRepository(AppUser::class)->findOneBy(['username' => $data['username']]);
        if ($existingUser) {
            return new JsonResponse(['error' => 'User already exists'], 400);
        }

        $user = new AppUser();
        $user->setUsername($data['username']);
        $hashedPassword = $passwordHasher->hashPassword($user, $data['password']);
        $user->setPassword($hashedPassword);


        if (isset($data['roles']) && is_array($data['roles'])) {
            $user->setRoles($data['roles']);
        } else {

            $user->setRoles(['ROLE_USER']);
        }

        $em->persist($user);
        $em->flush();

        return new JsonResponse(['message' => 'User created'], 201);
    }

}
