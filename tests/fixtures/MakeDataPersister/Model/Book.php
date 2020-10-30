<?php

namespace App\Model;

use ApiPlatform\Core\Annotation\ApiResource;

/**
 * @ApiResource()
 */
class Book
{
    private $id;

    private $title;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }
}
