<?php

namespace Symfony\Bundle\MakerBundle\Tests\tmp\current_project\src\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

#[ODM\Document]
class UserAvatar
{
    #[ODM\Id]
    private ?int $id = null;

    #[ODM\ReferenceOne(targetDocument: User::class, cascade: ['persist', 'remove'], inversedBy: 'avatars')]
    private ?User $user = null;
}
