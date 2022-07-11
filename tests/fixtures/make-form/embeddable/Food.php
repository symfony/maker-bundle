<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class Food
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column()]
    private ?int $id = null;

    #[ORM\Column]
    private ?string $title = null;

    /**
     * @ORM\Embedded(class=Receipt::class)
     */
    private $receipt;

    public function __construct()
    {
        $this->receipt = new Receipt();
    }

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return mixed
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @param mixed $title
     */
    public function setTitle($title)
    {
        $this->title = $title;
    }

    /**
     * @return Receipt
     */
    public function getReceipt()
    {
        return $this->receipt;
    }

    /**
     * @param Receipt $receipt
     */
    public function setRecept(Receipt $receipt)
    {
        $this->receipt = $receipt;
    }
}
