<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Embeddable]
class Money
{
    /**
     * @var Currency
     */
    #[ORM\Embedded()]
    private Currency $currency;

    /**
     * @var int
     */
    #[ORM\Column(name: 'amount')]
    private int $amount;

    public function __construct($amount = null, Currency $currency = null)
    {
        $this->amount = $amount;
        $this->currency = $currency ?? new Currency();
    }
}
