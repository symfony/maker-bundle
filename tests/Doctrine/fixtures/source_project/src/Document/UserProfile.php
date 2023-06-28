<?php

namespace Symfony\Bundle\MakerBundle\Tests\tmp\current_project\src\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

#[ODM\Document]
class UserProfile
{
    #[ODM\Id]

    private ?int $id = null;

    #[ODM\ReferenceOne(targetDocument: User::class, cascade: ['persist', 'remove'], inversedBy: 'userProfile')]
    private ?User $user = null;
}
