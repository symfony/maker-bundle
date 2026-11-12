<?php

namespace App\Entity;

use App\Enum\Status;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class User
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column()]
    private ?int $id = null;

    #[ORM\Column(nullable: true, enumType: Status::class)]
    private ?array $statuses = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return Status[]|null
     */
    public function getStatuses(): ?array
    {
        return $this->statuses;
    }

    public function setStatuses(?array $statuses): static
    {
        $this->statuses = $statuses;

        return $this;
    }
}
