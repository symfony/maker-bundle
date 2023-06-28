<?php

namespace Symfony\Bundle\MakerBundle\Tests\tmp\current_project\src\Document;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

#[ODM\Document]
class User
{
    #[ODM\Id]
    private ?int $id = null;

    #[ODM\ReferenceMany(targetDocument: UserAvatar::class, mappedBy: 'user')]
    private Collection $avatars;

    #[ODM\ReferenceOne(targetDocument: UserProfile::class, mappedBy: 'user')]
    private ?UserProfile $userProfile = null;

    public function getId(): ?int
    {
        // custom comment
        return $this->id;
    }

    public function customMethod()
    {
        return '';
    }

    public function setUserProfile(?UserProfile $userProfile)
    {
        $this->userProfile = $userProfile;
    }
}
