<?php

namespace Symfony\Bundle\MakerBundle\Tests\tmp\current_project\src\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class Client extends BaseClient
{
    use TimestampableTrait;

    /**
     * @var string
     */
    #[ORM\Column(type: Types::STRING)]
    private $apiKey;

    #[ORM\ManyToMany(targetEntity: Tag::class)]
    private $tags;

    #[ORM\Embedded(class: Embed::class)]
    private $embed;

    public function __construct()
    {
        parent::__construct();
        $this->embed = new Embed();
        $this->tags = new ArrayCollection();
    }

    public function getEmbed(): Embed
    {
        return $this->embed;
    }

    public function setEmbed(Embed $embed): self
    {
        $this->embed = $embed;

        return $this;
    }

    public function getApiKey(): ?string
    {
        return $this->apiKey;
    }

    public function setApiKey(string $apiKey): self
    {
        $this->apiKey = $apiKey;

        return $this;
    }

    /**
     * @return Collection<int, Tag>
     */
    public function getTags(): Collection
    {
        return $this->tags;
    }

    public function addTag(Tag $tag): self
    {
        if (!$this->tags->contains($tag)) {
            $this->tags[] = $tag;
        }

        return $this;
    }

    public function removeTag(Tag $tag): self
    {
        $this->tags->removeElement($tag);

        return $this;
    }
}
