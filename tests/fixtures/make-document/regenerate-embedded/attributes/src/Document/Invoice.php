<?php

namespace App\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

#[ODM\Document]
class Invoice
{
    #[ODM\Id]
    private ?string $id = null;

    #[ODM\Field(name: 'title')]
    private ?string $title = null;

    #[ODM\EmbedOne(targetDocument: Money::class)]
    private Money $total;
}
