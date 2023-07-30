<?php

namespace App\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

#[ODM\Document]
class User
{
    #[ODM\Id]
    private ?string $id = null;

    #[ODM\EmbedOne(targetDocument: UserAvatarPhoto::class)]
    private UserAvatarPhoto $avatarPhoto;

    public function __construct()
    {
        $this->avatarPhoto = new UserAvatarPhoto();
    }


    public function getId(): ?string
    {
        return $this->id;
    }

    public function getAvatarPhoto(): UserAvatarPhoto
    {
        return $this->avatarPhoto;
    }

    public function setAvatarPhoto(UserAvatarPhoto $avatarPhoto): static
    {
        $this->avatarPhoto = $avatarPhoto;

        return $this;
    }
}
