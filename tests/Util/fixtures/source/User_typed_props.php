<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class User
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column()]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $nullableProp = null;

    #[ORM\Column(length: 255)]
    private string|null $unionProp = null;

    #[ORM\Column(length: 255)]
    private $untypedProp;

    #[ORM\Column]
    private $untypedArrayProp = [];

    #[ORM\Column(length: 255)]
    private string $notNullableProp;

    #[ORM\Column(length: 255, nullable: true)]
    private string $notNullablePropOfNullableColumn;

    public function getId(): ?int
    {
        return $this->id;
    }
}
