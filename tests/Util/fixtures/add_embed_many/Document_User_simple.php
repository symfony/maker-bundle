<?php

namespace App\Document;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

#[ODM\Document]
class User
{
    #[ODM\Id]
    private ?string $id = null;

    #[ODM\EmbedMany(targetDocument: UserAvatarPhoto::class)]
    private Collection $avatarPhotos;

    public function __construct()
    {
        $this->avatarPhotos = new ArrayCollection();
    }


    public function getId(): ?string
    {
        return $this->id;
    }

    /**
     * @return Collection<int, UserAvatarPhoto>
     */
    public function getAvatarPhotos(): Collection
    {
        return $this->avatarPhotos;
    }

    public function addAvatarPhoto(UserAvatarPhoto $avatarPhoto): static
    {
        if (!$this->avatarPhotos->contains($avatarPhoto)) {
            $this->avatarPhotos->add($avatarPhoto);
        }

        return $this;
    }

    public function removeAvatarPhoto(UserAvatarPhoto $avatarPhoto): static
    {
        $this->avatarPhotos->removeElement($avatarPhoto);

        return $this;
    }
}
