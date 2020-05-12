<?php

namespace Symfony\Bundle\MakerBundle\Tests\Util\yaml_php_convert_fixtures\FakeClass;

class SimpleFakeClassService
{
    private $str;

    public function __construct($str = 'jacques')
    {
        $this->str = $str;
    }
}
