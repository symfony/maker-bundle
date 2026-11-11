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

    public function getNullableProp(): ?string
    {
        return $this->nullableProp;
    }

    public function setNullableProp(string $nullableProp): static
    {
        $this->nullableProp = $nullableProp;

        return $this;
    }

    public function getUnionProp(): ?string
    {
        return $this->unionProp;
    }

    public function setUnionProp(string $unionProp): static
    {
        $this->unionProp = $unionProp;

        return $this;
    }

    public function getUntypedProp(): ?string
    {
        return $this->untypedProp;
    }

    public function setUntypedProp(string $untypedProp): static
    {
        $this->untypedProp = $untypedProp;

        return $this;
    }

    public function getUntypedArrayProp(): array
    {
        return $this->untypedArrayProp;
    }

    public function setUntypedArrayProp(array $untypedArrayProp): static
    {
        $this->untypedArrayProp = $untypedArrayProp;

        return $this;
    }

    public function getNotNullableProp(): string
    {
        return $this->notNullableProp;
    }

    public function setNotNullableProp(string $notNullableProp): static
    {
        $this->notNullableProp = $notNullableProp;

        return $this;
    }

    public function getNotNullablePropOfNullableColumn(): string
    {
        return $this->notNullablePropOfNullableColumn;
    }

    public function setNotNullablePropOfNullableColumn(string $notNullablePropOfNullableColumn): static
    {
        $this->notNullablePropOfNullableColumn = $notNullablePropOfNullableColumn;

        return $this;
    }
}
