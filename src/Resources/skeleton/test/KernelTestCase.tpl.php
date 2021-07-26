<?= "<?php\n" ?>

namespace <?= $namespace; ?>;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class <?= $class_name ?> extends KernelTestCase
{
    public function testSomething(): void
    {
        $kernel = self::bootKernel();

        $this->assertSame('test', $kernel->getEnvironment());
        //$routerService = self::getContainer()->get('router');
        //$myCustomService = self::getContainer()->get(CustomService::class);
    }
}
