<?= "<?php\n" ?>

namespace <?= $namespace; ?>;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class <?= $class_name ?> extends KernelTestCase
{
    public function testSomething(): void
    {
        $kernel = self::bootKernel();

        $this->assertSame('test', $kernel->getEnvironment());
        // $routerService = <?= $use_legacy_container_property ? 'self::$container' : 'static::getContainer()'; ?>->get('router');
        // $myCustomService = <?= $use_legacy_container_property ? 'self::$container' : 'static::getContainer()'; ?>->get(CustomService::class);
    }
}
