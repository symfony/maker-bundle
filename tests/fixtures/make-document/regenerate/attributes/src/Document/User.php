<?php

namespace App\Document;

use Doctrine\Common\Collections\Collection;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\Types\Type;

#[ODM\Document]
class User
{
    #[ODM\Id]
    private ?string $id = null;

    #[ODM\Field(nullable: true)]
    private ?string $firstName = null;

    #[ODM\Field(type: Type::DATE, nullable: true)]
    private ?\DateTimeInterface $createdAt = null;

    #[ODM\ReferenceMany(targetDocument: UserAvatar::class, mappedBy: 'user')]
    private Collection $avatars;

    public function getId()
    {
        return $this->id;
    }

    public function getFirstName()
    {
        return $this->firstName;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }
}
