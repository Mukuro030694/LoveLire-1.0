<?php

namespace App\Controller;

use App\Entity\AppUser;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\HttpFoundation\Request;


#[Route('/api/user')]
class UserController extends AbstractController
{
    #[Route('/delete/{id}', name: 'user_delete', methods: ['DELETE'])]
    public function deleteUser(
        string $id,
        EntityManagerInterface $entityManager,
        Security $security
    ): JsonResponse {
        /** @var AppUser $currentUser */
        $currentUser = $security->getUser();

        $userToDelete = $entityManager->getRepository(AppUser::class)->find($id);

        if (!$userToDelete) {
            return new JsonResponse(['error' => 'User not found'], Response::HTTP_NOT_FOUND);
        }

        // Check if the current user is trying to delete themselves or if they are an admin
        if ($currentUser->getId() !== $userToDelete->getId() && !$currentUser->hasRole('ROLE_ADMIN')) {
            return new JsonResponse(['error' => 'Access denied'], Response::HTTP_FORBIDDEN);
        }

        $entityManager->remove($userToDelete);
        $entityManager->flush();

        return new JsonResponse(['message' => 'User deleted successfully'], Response::HTTP_OK);
    }

    #[Route('/list-users', name: 'user_list', methods: ['GET'])]
    public function listUsers(
        EntityManagerInterface $entityManager,
        Security $security
    ): JsonResponse {
        /** @var AppUser $currentUser */
        $currentUser = $security->getUser();

        if (!$currentUser || !$currentUser->hasRole('ROLE_ADMIN')) {
            return new JsonResponse(['error' => 'Access denied'], Response::HTTP_FORBIDDEN);
        }

        $users = $entityManager->getRepository(AppUser::class)->findAll();

        $userData = array_map(function (AppUser $user) {
            return [
                'id' => $user->getId(),
                'username' => $user->getUsername(),
            ];
        }, $users);

        return new JsonResponse($userData, Response::HTTP_OK);
    }


}
