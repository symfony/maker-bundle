<?php

namespace Symfony\Bundle\MakerBundle\Tests\tmp\current_project_xml\src\Document;

use Doctrine\ODM\MongoDB\Types\Type;

class XOther
{
    private ?int $id = null;

    public function getId()
    {
        return $this->id;
    }


}
