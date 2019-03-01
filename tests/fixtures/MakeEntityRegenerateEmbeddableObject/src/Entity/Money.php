<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Embeddable
 */
class Money
{
    /**
     * @var Currency
     *
     * @ORM\Embedded(class="App\Entity\Currency")
     */
    private $currency;

    /**
     * @var int
     *
     * @ORM\Column(name="amount", type="integer")
     */
    private $amount;

    public function __construct($amount = null, Currency $currency = null)
    {
        $this->amount = $amount;
        $this->currency = $currency ?? new Currency();
    }
}
