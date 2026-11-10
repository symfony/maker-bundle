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
    /**
     * Event classes dispatched by their own dispatcher, which is not configured with the
     * event aliases. Each form builder creates its own dispatcher, so a subscriber
     * attached to a form builder is never called when it is keyed by the event class
     * name: form events must be referenced by their constant.
     */
    private const EVENT_CLASSES_WITHOUT_ALIAS_RESOLUTION = [
        FormEvents::class,
    ];

    private static array $eventsMap = [];
    private static array $eventConstants = [];
    private static array $listenerEventClasses = [];

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
        self::$eventConstants = self::buildEventConstants();
        self::$listenerEventClasses = [];
    }

    /**
     * @return array<string, array{class-string, string}>
     */
    private static function buildEventConstants(): array
    {
        $eventConstants = [];

        foreach (self::EVENT_CLASSES_WITHOUT_ALIAS_RESOLUTION as $eventClass) {
            if (!class_exists($eventClass)) {
                continue;
            }

            foreach ((new \ReflectionClass($eventClass))->getConstants() as $constantName => $eventName) {
                if (\is_string($eventName)) {
                    $eventConstants[$eventName] = [$eventClass, $constantName];
                }
            }
        }

        return $eventConstants;
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
                self::$listenerEventClasses[$listenerKey] = $this->getEventClassName($listenerKey);
            }
        }

        $activeEvents = array_unique(array_merge($activeEvents, array_keys($listeners)));

        asort($activeEvents);

        return $activeEvents;
    }

    /**
     * Returns the class holding the constant and the constant name to use to
     * reference the event, or null when the event can be referenced by its class.
     *
     * Only events dispatched by a dispatcher that does not resolve the aliases
     * need a constant: the dispatcher that dispatches them is not the one
     * configured with the event aliases.
     *
     * @return array{class-string, string}|null
     */
    public function getEventConstant(string $event): ?array
    {
        return self::$eventConstants[$event] ?? null;
    }

    /**
     * Tells whether the event name is a known event of a Symfony component.
     *
     * Unlike getEventClassName(), this does not resolve events through the
     * registered listeners, so it only returns true for events that have a
     * registered alias. Those events can safely be referenced by their class.
     */
    public function isKnownEvent(string $event): bool
    {
        return isset(self::$eventsMap[$event]);
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
            $events[$key] = \sprintf('%s (<fg=yellow>%s</>)', $event, self::$eventsMap[$event] ?? self::$listenerEventClasses[$event] ?? '');
        }

        return $events;
    }
}
