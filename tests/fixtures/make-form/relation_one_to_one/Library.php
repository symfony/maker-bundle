<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class Library
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    #[ORM\Column()]
    private ?int $id = null;

    #[ORM\OneToOne(mappedBy: 'library', targetEntity: Librarian::class)]
    private Librarian $librarian;

    #[ORM\Column(name: 'name', length: 255)]
    private string $name;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getLibrarian(): Librarian
    {
        return $this->librarian;
    }

    public function setLibrarian(Librarian $librarian): static
    {
        $this->librarian = $librarian;
        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }
    public function setName(?string $name): static
    {
        $this->name = $name;
        return $this;
    }
}
