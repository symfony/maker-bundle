<?php

/*
 * This file is part of the Symfony MakerBundle package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use App\Entity\Task;
use App\Form\Data\TaskData;
use Doctrine\Common\Annotations\AnnotationReader;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * @requires PHP 7.1
 */
class GeneratedDtoTest extends KernelTestCase
{
    public function testGeneratedDto()
    {
        // the Entity has a composite Id - test that the attributes are both omitted from the DTO
        $this->assertClassNotHasAttribute('id', TaskData::class);
        $this->assertClassNotHasAttribute('group', TaskData::class);
    }
}
