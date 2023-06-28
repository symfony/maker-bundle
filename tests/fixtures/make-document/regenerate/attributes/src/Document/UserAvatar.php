<?php

namespace App\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

#[ODM\Document]
class UserAvatar
{
    #[ODM\Id]

    private ?string $id = null;

    #[ODM\ReferenceOne(targetDocument: User::class, cascade: ['persist', 'remove'], inversedBy: 'avatars')]
    private ?User $user = null;

    public function getId()
    {
        return $this->id;
    }
}
