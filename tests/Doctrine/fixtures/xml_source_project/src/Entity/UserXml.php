<?php

/*
 * This file is part of the Symfony MakerBundle package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Bundle\MakerBundle\Tests\tmp\current_project_xml\src\Entity;

class UserXml
{
    private $id;

    public function getId(): ?int
    {
        // custom comment
        return $this->id;
    }
}
