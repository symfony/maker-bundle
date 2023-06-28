<?php

namespace App\Document;

use Doctrine\Common\Collections\Collection;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\Types\Type;

class User
{
    private ?string $id = null;

    private ?string $firstName = null;

    private ?\DateTimeInterface $createdAt = null;

    private Collection $avatars;


}
