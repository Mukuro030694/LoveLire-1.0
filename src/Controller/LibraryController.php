<?php


namespace App\Controller;

use App\Entity\Book;
use App\Document\BookCover;
use Doctrine\ODM\MongoDB\DocumentManager as MongoDocumentManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Security;
use App\Entity\AppUser;

class LibraryController extends AbstractController
{
    #[Route('/library', name: 'library')]
    public function index(
        EntityManagerInterface $entityManager,
        MongoDocumentManager $documentManager,
        Security $security
    ): Response {
        /** @var AppUser $currentUser */
        $currentUser = $security->getUser();

        if (!$currentUser) {
            return $this->redirectToRoute('/'); 
        }
        //get books from PostgreSQL
        $books = $entityManager->getRepository(Book::class)->findBy(['userId' => $currentUser->getId()]);

        $booksWithCovers = [];
        //get covers from MongoDB
        foreach ($books as $book) {
            $cover = $documentManager->getRepository(BookCover::class)->findOneBy(['bookId' => $book->getId()->toRfc4122()]);

            $booksWithCovers[] = [
                'id' => $book->getId(),
                'title' => $book->getTitle(),
                'note' => $book->getNote(),
                'status' => $book->getStatus(),
                'comment' => $book->getComment(),
                'coverImage' => $cover ? $cover->getImageUrl() : null,
            ];
        }

        return $this->render('library.html.twig', [
            'books' => $booksWithCovers,
        ]);
    }
}
