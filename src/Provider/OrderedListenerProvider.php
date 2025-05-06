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

use Override;
use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\ListenerProviderInterface;
use ReflectionAttribute;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;
use Phayne\Event\Collection\OrderedCollection;
use Phayne\Event\Entry\ListenerEntry;
use Phayne\Event\Exception\ContainerMissingException;
use Phayne\Event\Exception\InvalidTypeException;
use Phayne\Event\Listener\Listener;
use Phayne\Event\Listener\ListenerAfter;
use Phayne\Event\Listener\ListenerAttribute;
use Phayne\Event\Listener\ListenerBefore;
use Phayne\Event\Listener\ListenerPriority;
use Phayne\Event\LIstener\ListenerProxy;
use Phayne\Event\SubscriberInterface;
use RuntimeException;

/**
 * Class OrderedListenerProvider
 *
 * @package Phayne\Event\Provider
 */
class OrderedListenerProvider implements ListenerProviderInterface, OrderedProviderInterface
{
    use ProviderUtilities;

    /**
     * @var OrderedCollection
     */
    protected OrderedCollection $listeners;

    public function __construct(private readonly ?ContainerInterface $container = null)
    {
        $this->listeners = new OrderedCollection();
    }

    /**
     * @return iterable<callable>
     */
    #[Override]
    public function getListenersForEvent(object $event): iterable
    {
        foreach ($this->listeners as $listener) {
            if ($event instanceof $listener->type) {
                yield $listener->listener;
            }
        }
    }

    #[Override]
    public function addListener(
        callable $listener,
        ?int $priority = null,
        ?string $id = null,
        ?string $type = null
    ): string {
        if ($attributes = $this->getAttributes($listener)) {
            $generatedId = '';
            // @todo We can probably do better than this in the next major.
            /** @var Listener |ListenerBefore | ListenerAfter | ListenerPriority $attrib */
            foreach ($attributes as $attrib) {
                $type = $type ?? $attrib->type ?? $this->getType($listener);
                $id = $id ?? $attrib->id ?? $this->getListenerId($listener);

                if ($attrib instanceof ListenerBefore) {
                    $generatedId = $this->listeners->addItemBefore(
                        $attrib->before,
                        new ListenerEntry($listener, $type),
                        $id
                    );
                } elseif ($attrib instanceof ListenerAfter) {
                    $generatedId = $this->listeners->addItemAfter(
                        $attrib->after,
                        new ListenerEntry($listener, $type),
                        $id
                    );
                } elseif ($attrib instanceof ListenerPriority) {
                    $generatedId = $this->listeners->addItem(
                        new ListenerEntry($listener, $type),
                        $attrib->priority ?? 0,
                        $id
                    );
                } else {
                    $generatedId = $this->listeners->addItem(
                        new ListenerEntry($listener, $type),
                        $priority ?? 0,
                        $id
                    );
                }
            }
            // Return the last id only, because that's all we can do.
            return $generatedId;
        }

        $type = $type ?? $this->getType($listener);
        $id = $id ?? $this->getListenerId($listener);

        return $this->listeners->addItem(new ListenerEntry($listener, $type), $priority ?? 0, $id);
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
            /** @var Listener | ListenerBefore | ListenerAfter | ListenerPriority $attrib */
            foreach ($attributes as $attrib) {
                $type = $type ?? $attrib->type ?? $this->getType($listener);
                $id = $id ?? $attrib->id ?? $this->getListenerId($listener);
                // The before-ness of this method call always overrides the attribute.
                $generatedId = $this->listeners->addItemBefore(
                    $before,
                    new ListenerEntry($listener, $type),
                    $id
                );
            }
            // Return the last id only, because that's all we can do.
            return $generatedId;
        }

        $type = $type ?? $this->getType($listener);
        $id = $id ?? $this->getListenerId($listener);

        return $this->listeners->addItemBefore($before, new ListenerEntry($listener, $type), $id);
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
            /** @var Listener | ListenerBefore | ListenerAfter | ListenerPriority $attrib */
            foreach ($attributes as $attrib) {
                $type = $type ?? $attrib->type ?? $this->getType($listener);
                $id = $id ?? $attrib->id ?? $this->getListenerId($listener);
                // The after-ness of this method call always overrides the attribute.
                $generatedId = $this->listeners->addItemAfter($after, new ListenerEntry($listener, $type), $id);
            }
            // Return the last id only, because that's all we can do.
            return $generatedId;
        }

        $type = $type ?? $this->getType($listener);
        $id = $id ?? $this->getListenerId($listener);

        return $this->listeners->addItemAfter($after, new ListenerEntry($listener, $type), $id);
    }

    #[Override]
    public function addListenerService(
        string $service,
        string $method,
        string $type,
        ?int $priority = null,
        ?string $id = null
    ): string {
        $id = $id ?? $service . '-' . $method;
        $priority = $priority ?? 0;
        return $this->addListener($this->makeListenerForService($service, $method), $priority, $id, $type);
    }

    #[Override]
    public function addListenerServiceBefore(
        string $before,
        string $service,
        string $method,
        string $type,
        ?string $id = null
    ): string {
        $id = $id ?? $service . '-' . $method;
        return $this->addListenerBefore($before, $this->makeListenerForService($service, $method), $id, $type);
    }

    #[Override]
    public function addListenerServiceAfter(
        string $after,
        string $service,
        string $method,
        string $type,
        ?string $id = null
    ): string {
        $id = $id ?? $service . '-' . $method;
        return $this->addListenerAfter($after, $this->makeListenerForService($service, $method), $id, $type);
    }

    #[Override]
    /**
     * @param class-string $class
     * @param string $service
     * @return void
     */
    public function addSubscriber(string $class, string $service): void
    {
        $proxy = $this->addSubscribersByProxy($class, $service);

        try {
            $methods = new ReflectionClass($class)->getMethods(ReflectionMethod::IS_PUBLIC);

            // Explicitly registered methods ignore all auto-registration mechanisms.
            $methods = array_filter($methods, static function (ReflectionMethod $refm) use ($proxy) {
                return ! in_array($refm->getName(), $proxy->registeredMethods());
            });

            /** @var ReflectionMethod $rMethod */
            foreach ($methods as $rMethod) {
                $this->addSubscriberMethod($rMethod, $class, $service);
            }
        } catch (ReflectionException $e) {
            throw new RuntimeException('Type error registering subscriber.', 0, $e);
        }
    }

    /**
     * @param class-string $class
     * @param string $service
     * @return ListenerProxy
     */
    protected function addSubscribersByProxy(string $class, string $service): ListenerProxy
    {
        $proxy = new ListenerProxy($this, $service, $class);

        // Explicit registration is opt-in.
        if (in_array(SubscriberInterface::class, class_implements($class))) {
            /** @var SubscriberInterface $class */
            $class::registerListeners($proxy);
        }
        return $proxy;
    }

    /**
     * @param ReflectionMethod $rMethod
     * @param class-string $class
     * @param string $service
     * @return void
     */
    protected function addSubscriberMethod(ReflectionMethod $rMethod, string $class, string $service): void
    {
        $methodName = $rMethod->getName();

        $attributes = array_map(
            static fn (ReflectionAttribute $attrib): object =>
            $attrib->newInstance(),
            $rMethod->getAttributes(ListenerAttribute::class, ReflectionAttribute::IS_INSTANCEOF)
        );

        if (count($attributes)) {
            // @todo We can probably do better than this in the next major.
            /** @var Listener|ListenerBefore|ListenerAfter|ListenerPriority $attrib */
            foreach ($attributes as $attrib) {
                $params = $rMethod->getParameters();
                $paramType = $params[0]->getType();

                /** @psalm-suppress UndefinedMethod */
                $type = $attrib->type ?? ($paramType?->getName());
                if (is_null($type)) {
                    throw InvalidTypeException::fromClassCallable($class, $methodName);
                }
                if ($attrib instanceof ListenerBefore) {
                    $this->addListenerServiceBefore($attrib->before, $service, $methodName, $type, $attrib->id);
                } elseif ($attrib instanceof ListenerAfter) {
                    $this->addListenerServiceAfter($attrib->after, $service, $methodName, $type, $attrib->id);
                } elseif ($attrib instanceof ListenerPriority) {
                    $this->addListenerService($service, $methodName, $type, $attrib->priority, $attrib->id);
                } else {
                    $this->addListenerService($service, $methodName, $type, null, $attrib->id);
                }
            }
        } elseif (str_starts_with($methodName, 'on')) {
            $params = $rMethod->getParameters();
            $type = $params[0]->getType();
            if (is_null($type)) {
                throw InvalidTypeException::fromClassCallable($class, $methodName);
            }
            /** @psalm-suppress UndefinedMethod */
            $this->addListenerService($service, $rMethod->getName(), $type->getName());
        }
    }

    /**
     * Creates a callable that will proxy to the provided service and method.
     *
     * @param string $serviceName
     *   The name of a service.
     * @param string $methodName
     *   A method on the service.
     * @return callable
     *   A callable that proxies to the provided method and service.
     */
    protected function makeListenerForService(string $serviceName, string $methodName): callable
    {
        if (null === $this->container) {
            throw new ContainerMissingException();
        }

        // We cannot verify the service name as existing at this time, as the container may be populated in any
        // order.  Thus the referenced service may not be registered now but could be registered by the time the
        // listener is called.

        // Fun fact: We cannot auto-detect the listener target type from a container without instantiating it, which
        // defeats the purpose of a service registration. Therefore this method requires an explicit event type. Also,
        // the wrapping listener must listen to just object.  The explicit $type means it will still get only
        // the right event type, and the real listener can still type itself properly.
        $container = $this->container;

        return static function (object $event) use ($serviceName, $methodName, $container): void {
            $container->get($serviceName)->$methodName($event);
        };
    }
}
