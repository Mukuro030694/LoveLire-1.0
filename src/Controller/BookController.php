<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Security\Core\Security;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ODM\MongoDB\DocumentManager;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use App\Document\BookCover;
use App\Entity\Book;
use App\Entity\AppUser;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\String\Slugger\SluggerInterface;
use App\Service\MongoDBService;





#[Route('/book')]
final class BookController extends AbstractController
{

    #[Route('/add', name: 'book_add', methods: ['POST'])]
    public function addBook(
        Request $request,
        EntityManagerInterface $entityManager,
        DocumentManager $mongoManager,
        Security $security,
        ParameterBagInterface $params
    ): JsonResponse {
        /** @var AppUser|null $user */
        $user = $security->getUser();
        if (!$user) {
            return new JsonResponse(['error' => 'User not authenticated'], Response::HTTP_UNAUTHORIZED);
        }

        $isJson = str_contains($request->headers->get('Content-Type', ''), 'application/json');
        $isMultipart = str_contains($request->headers->get('Content-Type', ''), 'multipart/form-data');

        if ($isJson) {
            $data = json_decode($request->getContent(), true);

            if (empty($data['title'])) {
                return new JsonResponse(['error' => 'Title is required'], Response::HTTP_BAD_REQUEST);
            }

            $book = new Book();
            $book->setTitle($data['title']);
            $book->setNote($data['note'] ?? null);
            $book->setStatus($data['status'] ?? null);
            $book->setComment($data['comment'] ?? null);
            $book->setUserId($user->getId());

            $entityManager->persist($book);
            $entityManager->flush();

            return new JsonResponse([
                'message' => 'Book added successfully',
                'bookId' => $book->getId()
            ], Response::HTTP_CREATED);
        }

        if ($isMultipart) {
            $title = $request->request->get('title');
            if (empty($title)) {
                return new JsonResponse(['error' => 'Title is required'], Response::HTTP_BAD_REQUEST);
            }

            $status = $request->request->get('status');
            $note = $request->request->get('note');
            $comment = $request->request->get('comment');
            $coverFile = $request->files->get('coverImage');

            $book = new Book();
            $book->setTitle($title);
            $book->setStatus($status);
            $book->setNote($note);
            $book->setComment($comment);
            $book->setUserId($user->getId());
            $book->setUsername($user->getUsername());

            $entityManager->persist($book);
            $entityManager->flush();

            if ($coverFile) {
                $uploadsDir = $params->get('kernel.project_dir') . '/var/uploads/covers';
                if (!is_dir($uploadsDir)) {
                    mkdir($uploadsDir, 0777, true);
                }

                $extension = $coverFile->guessExtension() ?? 'jpg';
                $uniqueFilename = Uuid::v4()->toRfc4122() . '.' . $extension;

                try {
                    $coverFile->move($uploadsDir, $uniqueFilename);
                    $imageUrl = '/uploads/covers/' . $uniqueFilename;

                    $bookCover = new BookCover();
                    $bookCover->setBookId($book->getId());
                    $bookCover->setImageUrl($imageUrl);

                    $mongoManager->persist($bookCover);
                    $mongoManager->flush();
                    $mongoManager->clear();

                    // Debug: check if BookCover is persisted
                    if (!$bookCover->getId()) {
                        throw new \Exception('BookCover not persisted in MongoDB');
                    }
                } catch (\Exception $e) {
                    return new JsonResponse(['error' => 'Image upload failed: ' . $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
                }
            }

            return new JsonResponse([
                'message' => 'Book with image added successfully',
                'bookId' => $book->getId()
            ], Response::HTTP_CREATED);
        }

        return new JsonResponse(['error' => 'Unsupported Content-Type'], Response::HTTP_UNSUPPORTED_MEDIA_TYPE);
    }



    #[Route('/delete/{id}', name: 'book_delete', methods: ['DELETE'])]
    public function deleteBook(
        string $id,
        EntityManagerInterface $entityManager,
        Security $security
    ): JsonResponse {
        /** @var AppUser $user */
        $user = $security->getUser();

        $book = $entityManager->getRepository(Book::class)->find($id);

        if (!$book) {
            return new JsonResponse(['error' => 'Book not found'], Response::HTTP_NOT_FOUND);
        }

        // Check if the user is the owner of the book or an admin
        if ((string) $book->getUserId() !== (string) $user->getId() && !$user->hasRole('ROLE_ADMIN')) {
            return new JsonResponse(['error' => 'You are not the owner of this book'], Response::HTTP_FORBIDDEN);
        }


        $entityManager->remove($book);
        $entityManager->flush();

        return new JsonResponse(['message' => 'Book deleted successfully'], Response::HTTP_OK);
    }


    #[Route('/modify/{id}', name: 'book_modify', methods: ['PUT', 'POST'])]
    public function modifyBook(
        string $id,
        Request $request,
        EntityManagerInterface $entityManager,
        Security $security,
        MongoDBService $mongoDBService,
        SluggerInterface $slugger
    ): JsonResponse {
        /** @var AppUser $user */
        $user = $security->getUser();

        $book = $entityManager->getRepository(Book::class)->find($id);

        if (!$book) {
            return new JsonResponse(['error' => 'Book not found'], Response::HTTP_NOT_FOUND);
        }

        if (
            (string) $book->getUserId() !== (string) $user->getId()
            && !in_array('ROLE_ADMIN', $user->getRoles(), true)
        ) {
            return new JsonResponse(['error' => 'Access denied'], Response::HTTP_FORBIDDEN);
        }

        $title = $request->request->get('title');
        $note = $request->request->get('note');
        $status = $request->request->get('status');
        $comment = $request->request->get('comment');

        if ($title !== null) {
            $book->setTitle($title);
        }
        if ($note !== null) {
            $book->setNote($note);
        }
        if ($status !== null) {
            $book->setStatus($status);
        }
        if ($comment !== null) {
            $book->setComment($comment);
        }

        // book cover image handling
        $coverImage = $request->files->get('coverImage');
        if ($coverImage) {
            $originalFilename = pathinfo($coverImage->getClientOriginalName(), PATHINFO_FILENAME);
            $safeFilename = $slugger->slug($originalFilename);
            $newFilename = $safeFilename . '-' . uniqid() . '.' . $coverImage->guessExtension();

            $coverImage->move($this->getParameter('covers_directory'), $newFilename);

            // Update the image path in MongoDB
            $mongoDBService->updateBookCover((string) $book->getId(), '/uploads/covers/' . $newFilename);
        }

        $entityManager->flush();

        return new JsonResponse([
            'message' => 'Book modified successfully',
            'bookId' => $book->getId()
        ]);
    }



    #[Route('/status/{id}', name: 'book_change_status', methods: ['PATCH'])]
    public function changeBookStatus(
        string $id,
        Request $request,
        EntityManagerInterface $entityManager,
        Security $security
    ): JsonResponse {
        /** @var AppUser $user */
        $user = $security->getUser();
        $book = $entityManager->getRepository(Book::class)->find($id);

        if (!$book) {
            return new JsonResponse(['error' => 'Book not found'], Response::HTTP_NOT_FOUND);
        }

        if ((string) $book->getUserId() !== (string) $user->getId()) {
            return new JsonResponse(['error' => 'Access denied'], Response::HTTP_FORBIDDEN);
        }

        $data = json_decode($request->getContent(), true);
        if (empty($data['status'])) {
            return new JsonResponse(['error' => 'Status is required'], Response::HTTP_BAD_REQUEST);
        }

        $book->setStatus($data['status']);
        $entityManager->flush();

        return new JsonResponse(['message' => 'Status updated successfully'], Response::HTTP_OK);
    }





    #[Route('/rate/{id}', name: 'book_rate', methods: ['PATCH'])]
    public function rateBook(
        string $id,
        Request $request,
        EntityManagerInterface $entityManager,
        Security $security
    ): JsonResponse {
        /** @var AppUser $user */
        $user = $security->getUser();

        $book = $entityManager->getRepository(Book::class)->find($id);

        if (!$book) {
            return new JsonResponse(['error' => 'Book not found'], Response::HTTP_NOT_FOUND);
        }

        if ((string) $book->getUserId() !== (string) $user->getId()) {
            return new JsonResponse(['error' => 'Access denied'], Response::HTTP_FORBIDDEN);
        }

        $data = json_decode($request->getContent(), true);

        if (!isset($data['note']) || !is_int($data['note']) || $data['note'] < 1 || $data['note'] > 5) {
            return new JsonResponse(['error' => 'Note must be an integer between 1 and 5'], Response::HTTP_BAD_REQUEST);
        }

        $book->setNote($data['note']);
        $entityManager->flush();

        return new JsonResponse(['message' => 'Note updated successfully'], Response::HTTP_OK);
    }



    #[Route('/filter', name: 'book_filter', methods: ['GET'])]
    public function filterBooks(Request $request, EntityManagerInterface $entityManager, DocumentManager $mongoManager, Security $security): Response
    {
        /** @var AppUser $user */
        $user = $security->getUser();

        $status = $request->query->get('status', null);
        $note = $request->query->get('note', null);
        $direction = strtoupper($request->query->get('title_order', 'ASC'));
        $sortBy = 'title';

        $allowedDirections = ['ASC', 'DESC'];
        if (!in_array($direction, $allowedDirections, true)) {
            $direction = 'ASC';
        }

        $qb = $entityManager->createQueryBuilder();
        $qb->select('b')
            ->from(Book::class, 'b');

        if (!$security->isGranted('ROLE_ADMIN')) {
            $qb->where('b.userId = :userId')
                ->setParameter('userId', $user->getId());
        }

        if ($status !== null && $status !== '') {
            $qb->andWhere('b.status = :status')
                ->setParameter('status', $status);
        }

        if ($note !== null && $note !== '') {
            $qb->andWhere('b.note = :note')
                ->setParameter('note', (int) $note);
        }

        $qb->orderBy('b.' . $sortBy, $direction);

        $books = $qb->getQuery()->getResult();

        // Получаем все id книг
        $bookIds = array_map(fn($book) => $book->getId(), $books);

        // Подгружаем обложки из Mongo
        $covers = $mongoManager->createQueryBuilder(BookCover::class)
            ->field('bookId')->in($bookIds)
            ->getQuery()
            ->execute();

        $coversByBookId = [];
        foreach ($covers as $cover) {
            $coversByBookId[(string) $cover->getBookId()] = $cover->getImageUrl();
        }

        foreach ($books as $book) {
            $bookId = (string) $book->getId();
            $book->coverImage = $coversByBookId[$bookId] ?? null;
        }


        return $this->render('library.html.twig', [
            'books' => $books,
        ]);
    }


    #[Route('/test-mongo', name: 'test_mongo')]
    public function testMongo(DocumentManager $mongoManager): Response
    {
        $cover = new BookCover();
        $cover->setBookId('test-book-id');
        $cover->setImageUrl('/uploads/covers/test.jpg');

        $mongoManager->persist($cover);
        $mongoManager->flush();

        return new Response('Saved to Mongo: ' . $cover->getId());
    }






}
