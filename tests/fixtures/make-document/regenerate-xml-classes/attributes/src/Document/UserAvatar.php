<?php

namespace App\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

class UserAvatar
{
    private ?string $id = null;

    private ?User $user = null;

    public function getId()
    {
        return $this->id;
    }
}
