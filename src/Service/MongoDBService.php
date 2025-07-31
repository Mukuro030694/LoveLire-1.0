<?php

namespace App\Service;

use Doctrine\ODM\MongoDB\DocumentManager;
use App\Document\BookCover;

class MongoDBService
{
    private DocumentManager $mongoManager;

    public function __construct(DocumentManager $mongoManager)
    {
        $this->mongoManager = $mongoManager;
    }

    public function updateBookCover(string $bookId, string $imageUrl): void
    {
        $bookCover = $this->mongoManager->getRepository(BookCover::class)->findOneBy(['bookId' => $bookId]);

        if (!$bookCover) {
            $bookCover = new BookCover();
            $bookCover->setBookId($bookId);
        }

        $bookCover->setImageUrl($imageUrl);
        $this->mongoManager->persist($bookCover);
        $this->mongoManager->flush();
    }
}
