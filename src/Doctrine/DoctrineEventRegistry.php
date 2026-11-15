<?php

/*
 * This file is part of the Symfony MakerBundle package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Bundle\MakerBundle\Doctrine;

use Doctrine\Migrations\Event\MigrationsEventArgs;
use Doctrine\Migrations\Event\MigrationsVersionEventArgs;
use Doctrine\Migrations\Events as MigrationsEvents;
use Doctrine\ORM\Events;
use Doctrine\ORM\Tools\ToolEvents;

/**
 * @internal
 */
class DoctrineEventRegistry
{
    private array $lifecycleEvents;

    private ?array $eventsMap = null;

    public function __construct()
    {
        $this->lifecycleEvents = [
            Events::prePersist => true,
            Events::postPersist => true,
            Events::preUpdate => true,
            Events::postUpdate => true,
            Events::preRemove => true,
            Events::postRemove => true,
            Events::preFlush => true,
            Events::postLoad => true,
        ];
    }

    public function isLifecycleEvent(string $event): bool
    {
        return isset($this->lifecycleEvents[$event]);
    }

    /**
     * Returns all known event names.
     */
    public function getAllEvents(): array
    {
        return array_keys($this->getEventsMap());
    }

    /**
     * Attempts to get the event class for a given event.
     */
    public function getEventClassName(string $event): ?string
    {
        return $this->getEventsMap()[$event]['event_class'] ?? null;
    }

    /**
     * Attempts to find the class that defines the given event name as a constant.
     */
    public function getEventConstantClassName(string $event): ?string
    {
        return $this->getEventsMap()[$event]['const_class'] ?? null;
    }

    private function getEventsMap(): array
    {
        return $this->eventsMap ??= self::findEvents();
    }

    private static function findEvents(): array
    {
        $eventsMap = [];

        foreach ((new \ReflectionClass(Events::class))->getConstants(\ReflectionClassConstant::IS_PUBLIC) as $event) {
            $eventsMap[$event] = [
                'const_class' => Events::class,
                'event_class' => \sprintf('Doctrine\ORM\Event\%sEventArgs', ucfirst($event)),
            ];
        }

        foreach ((new \ReflectionClass(ToolEvents::class))->getConstants(\ReflectionClassConstant::IS_PUBLIC) as $event) {
            $eventsMap[$event] = [
                'const_class' => ToolEvents::class,
                'event_class' => \sprintf('Doctrine\ORM\Tools\Event\%sEventArgs', substr($event, 4)),
            ];
        }

        if (class_exists(MigrationsEvents::class)) {
            foreach ((new \ReflectionClass(MigrationsEvents::class))->getConstants(\ReflectionClassConstant::IS_PUBLIC) as $event) {
                $eventsMap[$event] = [
                    'const_class' => MigrationsEvents::class,
                    'event_class' => str_contains($event, 'Version') ? MigrationsVersionEventArgs::class : MigrationsEventArgs::class,
                ];
            }
        }

        ksort($eventsMap);

        return $eventsMap;
    }
}
