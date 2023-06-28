<?php

namespace App\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations AS ODM;

#[ODM\Document]
class UserAvatarPhoto
{
    #[ODM\Id]
    private ?string $id = null;

    public function getId()
    {
        return $this->id;
    }
}
