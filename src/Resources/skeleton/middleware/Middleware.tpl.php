<?= "<?php\n" ?>

namespace <?= $namespace; ?>;

use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Middleware\MiddlewareInterface;
use Symfony\Component\Messenger\Middleware\StackInterface;

final class <?= $class_name; ?> implements MiddlewareInterface
{
    public function handle(Envelope $envelope, StackInterface $stack): Envelope
    {
        // ...
        return $stack->next()->handle($envelope, $stack);
    }
}
