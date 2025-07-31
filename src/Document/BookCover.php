<?php

namespace App\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;

#[MongoDB\Document(collection: "book_covers")]
class BookCover
{
    #[MongoDB\Id]
    private ?string $id = null;

    #[MongoDB\Field(type: "string")]
    private string $bookId;

    #[MongoDB\Field(type: "string")]
    private string $imageUrl;

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getBookId(): string
    {
        return $this->bookId;
    }

    public function setBookId(string $bookId): self
    {
        $this->bookId = $bookId;
        return $this;
    }

    public function getImageUrl(): string
    {
        return $this->imageUrl;
    }

    public function setImageUrl(string $imageUrl): self
    {
        $this->imageUrl = $imageUrl;
        return $this;
    }
}
