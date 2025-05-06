<?php

/**
 * This file is part of phayne-io/php-event-dispatcher and is proprietary and confidential.
 * Unauthorized copying of this file, via any medium is strictly prohibited.
 *
 * @see       https://github.com/phayne-io/php-event-dispatcher for the canonical source repository
 * @copyright Copyright (c) 2024-2025 Phayne Limited. (https://phayne.io)
 */

declare(strict_types=1);

namespace Phayne\Event\Provider;

use Closure;
use InvalidArgumentException;
use IteratorAggregate;
use Override;
use Phayne\Event\Collection\OrderedCollection;
use Phayne\Event\Entry\ListenerEntry;
use Phayne\Event\Entry\ListenerFunctionEntry;
use Phayne\Event\Entry\ListenerServiceEntry;
use Phayne\Event\Entry\ListenerStaticMethodEntry;
use Phayne\Event\Listener\Listener;
use Phayne\Event\Listener\ListenerAfter;
use Phayne\Event\Listener\ListenerBefore;
use Phayne\Event\Listener\ListenerPriority;
use Phayne\Event\Listener\ListenerProxy;
use Phayne\Event\SubscriberInterface;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;
use RuntimeException;
use Traversable;

/**
 * Class ProviderBuilder
 *
 * @package Phayne\Event\Provider
 * @template T
 * @implements IteratorAggregate<T>
 */
class ProviderBuilder implements OrderedProviderInterface, IteratorAggregate
{
    use ProviderUtilities;

    protected OrderedCollection $listeners;

    public function __construct()
    {
        $this->listeners = new OrderedCollection();
    }

    #[Override]
    public function getIterator(): Traversable
    {
        yield from $this->listeners;
    }

    #[Override]
    public function addListener(
        callable $listener,
        ?int $priority = null,
        ?string $id = null,
        ?string $type = null
    ): string {
        if ($attributes = $this->getAttributes($listener)) {
            // @todo We can probably do better than this in the next major.
            /** @var Listener|ListenerBefore|ListenerAfter|ListenerPriority $attrib */
            foreach ($attributes as $attrib) {
                $type = $type ?? $attrib->type ?? $this->getType($listener);
                $id = $id ?? $attrib->id ?? $this->getListenerId($listener);
                $entry = $this->getListenerEntry($listener, $type);
                if ($attrib instanceof ListenerBefore) {
                    $generatedId = $this->listeners->addItemBefore($attrib->before, $entry, $id);
                } elseif ($attrib instanceof ListenerAfter) {
                    $generatedId = $this->listeners->addItemAfter($attrib->after, $entry, $id);
                } elseif ($attrib instanceof ListenerPriority) {
                    $generatedId = $this->listeners->addItem($entry, $attrib->priority ?? 0, $id);
                } else {
                    $generatedId = $this->listeners->addItem($entry, $priority ?? 0, $id);
                }
            }
            // Return the last id only, because that's all we can do.
            return $generatedId;
        }

        $entry = $this->getListenerEntry($listener, $type ?? $this->getParameterType($listener));
        $id = $id ?? $this->getListenerId($listener);

        return $this->listeners->addItem($entry, $priority ?? 0, $id);
    }

    #[Override]
    public function addListenerBefore(
        string $before,
        callable $listener,
        ?string $id = null,
        ?string $type = null
    ): string {
        if ($attributes = $this->getAttributes($listener)) {
            $generatedId = '';
            // @todo We can probably do better than this in the next major.
            /** @var Listener|ListenerBefore|ListenerAfter|ListenerPriority $attrib */
            foreach ($attributes as $attrib) {
                $type = $type ?? $attrib->type ?? $this->getType($listener);
                $id = $id ?? $attrib->id ?? $this->getListenerId($listener);
                $entry = $this->getListenerEntry($listener, $type);
                // The before-ness of this method takes priority over the attribute.
                $generatedId = $this->listeners->addItemBefore($before, $entry, $id);
            }
            // Return the last id only, because that's all we can do.
            return $generatedId;
        }

        $id = $id ?? $this->getListenerId($listener);
        $entry = $this->getListenerEntry($listener, $type ?? $this->getParameterType($listener));
        return $this->listeners->addItemBefore($before, $entry, $id);
    }

    #[Override]
    public function addListenerAfter(
        string $after,
        callable $listener,
        ?string $id = null,
        ?string $type = null
    ): string {
        if ($attributes = $this->getAttributes($listener)) {
            $generatedId = '';
            // @todo We can probably do better than this in the next major.
            /** @var Listener|ListenerBefore|ListenerAfter|ListenerPriority $attrib */
            foreach ($attributes as $attrib) {
                $type = $type ?? $attrib->type ?? $this->getType($listener);
                $id = $id ?? $attrib->id ?? $this->getListenerId($listener);
                $entry = $this->getListenerEntry($listener, $type);
                // The before-ness of this method takes priority over the attribute.
                $generatedId = $this->listeners->addItemBefore($after, $entry, $id);
            }
            // Return the last id only, because that's all we can do.
            return $generatedId;
        }

        $entry = $this->getListenerEntry($listener, $type ?? $this->getParameterType($listener));
        $id = $id ?? $this->getListenerId($listener);

        return $this->listeners->addItemAfter($after, $entry, $id);
    }

    #[Override]
    public function addListenerService(
        string $service,
        string $method,
        string $type,
        ?int $priority = null,
        ?string $id = null
    ): string {
        return $this->listeners->addItem(new ListenerServiceEntry($service, $method, $type), $priority ?? 0, $id);
    }

    #[Override]
    public function addListenerServiceBefore(
        string $before,
        string $service,
        string $method,
        string $type,
        ?string $id = null
    ): string {
        return $this->listeners->addItemBefore($before, new ListenerServiceEntry($service, $method, $type), $id);
    }

    #[Override]
    public function addListenerServiceAfter(
        string $after,
        string $service,
        string $method,
        string $type,
        ?string $id = null
    ): string {
        return $this->listeners->addItemAfter($after, new ListenerServiceEntry($service, $method, $type), $id);
    }

    #[Override]
    public function addSubscriber(string $class, string $service): void
    {
        // @todo This method is identical to the one in OrderedListenerProvider. Is it worth merging them?
        $proxy = new ListenerProxy($this, $service, $class);

        // Explicit registration is opt-in.
        if (in_array(SubscriberInterface::class, class_implements($class))) {
            /** @var SubscriberInterface $class */
            $class::registerListeners($proxy);
        }

        try {
            $rClass = new ReflectionClass($class);
            $methods = $rClass->getMethods(ReflectionMethod::IS_PUBLIC);

            foreach ($methods as $rMethod) {
                $methodName = $rMethod->getName();
                if (!in_array($methodName, $proxy->registeredMethods()) && str_starts_with($methodName, 'on')) {
                    $params = $rMethod->getParameters();
                    /** @psalm-suppress PossiblyNullReference, UndefinedMethod */
                    $type = $params[0]->getType()->getName();
                    /** @psalm-suppress PossiblyNullReference, UndefinedMethod */
                    $this->addListenerService($service, $rMethod->getName(), $type);
                }
            }
        } catch (ReflectionException $e) {
            throw new RuntimeException('Type error registering subscriber.', 0, $e);
        }
    }

    protected function getListenerEntry(callable $listener, string $type): ListenerEntry
    {
        // We can't serialize a closure.
        if ($listener instanceof Closure) {
            throw new InvalidArgumentException('Closures cannot be used in a compiled listener provider.');
        }
        // String means it's a function name, and that's safe.
        if (is_string($listener)) {
            return new ListenerFunctionEntry($listener, $type);
        }
        // This is how we recognize a static method call.
        if (is_array($listener) && isset($listener[0]) && is_string($listener[0])) {
            return new ListenerStaticMethodEntry($listener[0], $listener[1], $type);
        }
        // Anything else isn't safe to serialize, so reject it.
        throw new InvalidArgumentException('That callable type cannot be used in a compiled listener provider.');
    }
}
