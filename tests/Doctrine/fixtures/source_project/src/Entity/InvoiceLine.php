<?php

namespace Symfony\Bundle\MakerBundle\Tests\tmp\current_project\src\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class InvoiceLine
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    public function __construct(
        #[ORM\ManyToOne(inversedBy: 'lines')]
        #[ORM\JoinColumn(nullable: false)]
        private Invoice $invoice,

        #[ORM\Column(length: 255)]
        private string $label,

        #[ORM\Column(length: 255, nullable: true)]
        private ?string $note = null,
    ) {
    }
}
