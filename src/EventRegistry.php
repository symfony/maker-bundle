<?php

/*
 * This file is part of the Symfony MakerBundle package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Bundle\MakerBundle;

use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\AuthenticationEvents;
use Symfony\Component\Security\Http\SecurityEvents;
use Symfony\Component\Workflow\WorkflowEvents;

/**
 * @internal
 */
class EventRegistry
{
    private static array $eventsMap = [];

    public function __construct(
        private EventDispatcherInterface $eventDispatcher,
    ) {
        self::$eventsMap = array_flip([
            ...ConsoleEvents::ALIASES,
            ...KernelEvents::ALIASES,
            ...(class_exists(AuthenticationEvents::class) ? AuthenticationEvents::ALIASES : []),
            ...(class_exists(SecurityEvents::class) ? SecurityEvents::ALIASES : []),
            ...(class_exists(WorkflowEvents::class) ? WorkflowEvents::ALIASES : []),
            ...(class_exists(FormEvents::class) ? FormEvents::ALIASES : []),
        ]);
    }

    /**
     * Returns all known event names in the system.
     */
    public function getAllActiveEvents(): array
    {
        $activeEvents = [];
        foreach (self::$eventsMap as $eventName => $eventClass) {
            if (!class_exists($eventClass)) {
                continue;
            }

            $activeEvents[] = $eventName;
        }

        $listeners = $this->eventDispatcher->getListeners();

        foreach (array_keys($listeners) as $listenerKey) {
            if (!isset(self::$eventsMap[$listenerKey])) {
                self::$eventsMap[$listenerKey] = $this->getEventClassName($listenerKey);
            }
        }

        $activeEvents = array_unique(array_merge($activeEvents, array_keys($listeners)));

        asort($activeEvents);

        return $activeEvents;
    }

    /**
     * Attempts to get the event class for a given event.
     */
    public function getEventClassName(string $event): ?string
    {
        // if the event is already a class name, use it
        if (class_exists($event)) {
            return $event;
        }

        if (isset(self::$eventsMap[$event])) {
            return self::$eventsMap[$event];
        }

        $listeners = $this->eventDispatcher->getListeners($event);
        if (empty($listeners)) {
            return null;
        }

        foreach ($listeners as $listener) {
            if (!\is_array($listener) || 2 !== \count($listener)) {
                continue;
            }

            $reflectionMethod = new \ReflectionMethod($listener[0], $listener[1]);
            $args = $reflectionMethod->getParameters();
            if (!$args) {
                continue;
            }

            if (null !== $type = $args[0]->getType()) {
                $type = $type instanceof \ReflectionNamedType ? $type->getName() : null;

                // ignore an "object" type-hint
                if ('object' === $type) {
                    continue;
                }

                return $type;
            }
        }

        return null;
    }

    public function listActiveEvents(array $events): array
    {
        foreach ($events as $key => $event) {
            $events[$key] = sprintf('%s (<fg=yellow>%s</>)', $event, self::$eventsMap[$event]);
        }

        return $events;
    }
}
