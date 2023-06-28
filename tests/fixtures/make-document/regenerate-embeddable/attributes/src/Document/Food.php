<?php

namespace App\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

#[ODM\Document]
class Food
{
    #[ODM\Id]
    private ?string $id = null;

    #[ODM\Field(name: 'title')]
    private ?string $title = null;

    #[ODM\EmbedOne(targetDocument: Recipe::class)]
    private Recipe $recipe;
}
