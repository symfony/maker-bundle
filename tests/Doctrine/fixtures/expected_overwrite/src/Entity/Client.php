<?php

namespace Symfony\Bundle\MakerBundle\Tests\tmp\current_project\src\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 */
class Client extends BaseClient
{
    use TimestampableTrait;

    /**
     * @ORM\Column(type="string")
     * @var string
     */
    private $apiKey;

    /**
     * @ORM\ManyToMany(targetEntity="Tag")
     */
    private $tags;

    /**
     * @ORM\Embedded(class="Embed")
     */
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
     * @return Collection|Tag[]
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
        if ($this->tags->contains($tag)) {
            $this->tags->removeElement($tag);
        }

        return $this;
    }
}
