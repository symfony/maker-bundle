<?php

namespace App\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

#[ODM\EmbeddedDocument]
class Money
{
    #[ODM\EmbedOne(targetDocument: Currency::class)]
    private Currency $currency;

    /**
     * @var int
     */
    #[ODM\Field(name: 'amount')]
    private ?int $amount;

    public function __construct($amount = null, Currency $currency = null)
    {
        $this->amount = $amount;
        $this->currency = $currency ?? new Currency();
    }
}
