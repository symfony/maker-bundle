<?php

namespace Symfony\Bundle\MakerBundle\Tests\Doctrine\fixtures\source_project\src\Entity;

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
}
