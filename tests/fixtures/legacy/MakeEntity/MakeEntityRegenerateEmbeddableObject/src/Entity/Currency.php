<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Embeddable()
 */
class Currency
{
    /**
     * @var string
     *
     * @ORM\Column(name="currency", type="string")
     */
    private $currency;

    public function __construct($currency = null)
    {
        $this->currency = $currency;
    }

    /**
     * @return string
     */
    public function getCurrency()
    {
        return $this->currency;
    }

    /**
     * @param string $currency
     *
     * @return Currency
     */
    public function setCurrency($currency)
    {
        $this->currency = $currency;

        return $this;
    }
}
