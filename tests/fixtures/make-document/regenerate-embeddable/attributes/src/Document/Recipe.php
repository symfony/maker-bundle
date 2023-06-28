<?php

namespace App\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

#[ODM\EmbeddedDocument]
class Recipe
{
    #[ODM\Field]
    private ?string $ingredients = null;

    #[ODM\Field()]
    private ?string $steps = null;
}
