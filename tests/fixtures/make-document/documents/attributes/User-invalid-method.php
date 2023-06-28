<?php

namespace App\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

#[ODM\Document]
class User
{
    #[ODM\Id]
    private ?string $id = null;

    #[ODM\Field(nullable: true)]
    private ?string $firstName = null;

    public function getId()
    {
        return $this->id;
    }

    public function setFirstName()
    {
        throw new \Exception('This does not work!');
    }
}
