<?php

namespace App\Controller;

use App\Entity\AppUser;
use App\Entity\Book;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ODM\MongoDB\DocumentManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Security;
use App\Document\BookCover;

class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function home(
        EntityManagerInterface $entityManager,
        DocumentManager $dm,
        Security $security
    ): Response {
        $user = $security->getUser();

        if ($user instanceof AppUser) {
            $userId = (string) $user->getId();
            $books = $entityManager->getRepository(Book::class)->findBy(['userId' => $userId]);
        } else {
            $books = $entityManager->getRepository(Book::class)->createQueryBuilder('b')
                ->setMaxResults(10)
                ->getQuery()
                ->getResult();
        }

        $bookIds = [];
        $userIds = [];
        foreach ($books as $book) {
            $bookIds[] = (string) $book->getId();
            $userIds[] = $book->getUserId();
        }

        // Debug
        dump('Books IDs from SQL:', $bookIds);

        $collection = $dm->getDocumentCollection(BookCover::class);
        $images = $collection->find(['bookId' => ['$in' => $bookIds]])->toArray();

        // Debug
        dump('Images from Mongo:', $images);

        $imageMap = [];
        foreach ($images as $img) {
            // Debug
            dump('Mongo image bookId:', (string) $img['bookId']);
            $imageMap[(string) $img['bookId']] = $img['imageUrl'];
        }

        // Debug
        dump('User IDs from books:', $userIds);

        $userRepo = $entityManager->getRepository(AppUser::class);
        $userMap = [];
        foreach ($userRepo->findBy(['id' => $userIds]) as $userEntity) {
            $userMap[(string) $userEntity->getId()] = $userEntity->getUsername();
        }

        // Debug
        dump('User Map:', $userMap);

        $booksWithIdString = [];
        foreach ($books as $book) {
            $id = (string) $book->getId();
            $uid = (string) $book->getUserId();
            $booksWithIdString[] = [
                'book' => $book,
                'idString' => $id,
                'cover' => $imageMap[$id] ?? null,
                'username' => $userMap[$uid] ?? 'Utilisateur inconnu',
            ];
        }


        return $this->render('home.html.twig', [
            'books' => $booksWithIdString,
        ]);
    }
}
