<?= "<?php\n" ?>

namespace <?= $namespace; ?>;

use Symfony\Contracts\EventDispatcher\Event;

final class <?= $class_name ?> extends Event
{
    public function __construct() {}
}