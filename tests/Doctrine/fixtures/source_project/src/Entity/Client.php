<?php

namespace Symfony\Bundle\MakerBundle\Tests\tmp\current_project\src\Entity;

use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class Client extends BaseClient
{
    use TimestampableTrait;

    /**
     * @var string
     */
    #[ORM\Column]
    private ?string $apiKey = null;

    /**
     * @var Collection<int, Tag>
     */
    #[ORM\ManyToMany(targetEntity: Tag::class)]
    private Collection $tags;

    #[ORM\Embedded()]
    private Embed $embed;
}
