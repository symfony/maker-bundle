<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class Librarian
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    #[ORM\Column()]
    private ?int $id = null;

    #[ORM\Column(name: 'name', length: 255)]
    private ?string $name = null;

    #[ORM\OneToOne(mappedBy: 'librarian', targetEntity: Library::class)]
    private Library $library;

    public function getId()
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getLibrary(): Library
    {
        return $this->library;
    }
    public function setLibrary(Library $library): static
    {
        $this->library = $library;
        return $this;
    }
}
