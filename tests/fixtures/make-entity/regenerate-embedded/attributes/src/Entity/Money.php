<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Embeddable]
class Money
{
    #[ORM\Embedded()]
    private Currency $currency;

    /**
     * @var int
     */
    #[ORM\Column(name: 'amount')]
    private ?int $amount;

    public function __construct($amount = null, ?Currency $currency = null)
    {
        $this->amount = $amount;
        $this->currency = $currency ?? new Currency();
    }
}
