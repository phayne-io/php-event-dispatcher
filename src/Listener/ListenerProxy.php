<?php

/**
 * This file is part of phayne-io/php-event-dispatcher and is proprietary and confidential.
 * Unauthorized copying of this file, via any medium is strictly prohibited.
 *
 * @see       https://github.com/phayne-io/php-event-dispatcher for the canonical source repository
 * @copyright Copyright (c) 2024-2025 Phayne Limited. (https://phayne.io)
 */

declare(strict_types=1);

namespace Phayne\Event\Listener;

use Fig\EventDispatcher\ParameterDeriverTrait;
use InvalidArgumentException;
use Phayne\Event\Exception\InvalidTypeException;
use Phayne\Event\Provider\OrderedProviderInterface;

/**
 * Class ListenerProxy
 *
 * @package Phayne\Event\Listener
 */
class ListenerProxy
{
    use ParameterDeriverTrait;

    protected array $registeredMethods = [];

    /**
     * @param OrderedProviderInterface $provider
     * @param string $serviceName
     * @param class-string $serviceClass
     */
    public function __construct(
        protected OrderedProviderInterface $provider,
        protected string $serviceName,
        protected string $serviceClass
    ) {
    }

    /**
     * Adds a method on a service as a listener.
     *
     * @param string $methodName
     *   The method name of the service that is the listener being registered.
     * @param ?int $priority
     *   The numeric priority of the listener. Higher numbers will trigger before lower numbers.
     * @param ?string $id
     *   The ID of this listener, so it can be referenced by other listeners.
     * @param ?string $type
     *   The class or interface type of events for which this listener will be registered.
     *
     * @return string
     *   The opaque ID of the listener.  This can be used for future reference.
     */
    public function addListener(
        string $methodName,
        ?int $priority = 0,
        ?string $id = null,
        ?string $type = null
    ): string {
        $type = $type ?? $this->serviceMethodType($methodName);
        $this->registeredMethods[] = $methodName;

        return $this->provider->addListenerService($this->serviceName, $methodName, $type, $priority, $id);
    }

    /**
     * Adds a service listener to trigger before another existing listener.
     *
     * Note: The new listener is only guaranteed to come before the specified existing listener. No guarantee is made
     * regarding when it comes relative to any other listener.
     *
     * @param string $pivotId
     *   The ID of an existing listener.
     * @param string $methodName
     *   The method name of the service that is the listener being registered.
     * @param ?string $id
     *   The ID of this listener, so it can be referenced by other listeners.
     * @param ?string $type
     *   The class or interface type of events for which this listener will be registered.
     *
     * @return string
     *   The opaque ID of the listener.  This can be used for future reference.
     */
    public function addListenerBefore(
        string $pivotId,
        string $methodName,
        ?string $id = null,
        ?string $type = null
    ): string {
        $type = $type ?? $this->serviceMethodType($methodName);
        $this->registeredMethods[] = $methodName;

        return $this->provider->addListenerServiceBefore($pivotId, $this->serviceName, $methodName, $type, $id);
    }

    /**
     * Adds a service listener to trigger before another existing listener.
     *
     * Note: The new listener is only guaranteed to come before the specified existing listener. No guarantee is made
     * regarding when it comes relative to any other listener.
     *
     * @param string $pivotId
     *   The ID of an existing listener.
     * @param string $methodName
     *   The method name of the service that is the listener being registered.
     * @param ?string $id
     *   The ID of this listener, so it can be referenced by other listeners.
     * @param ?string $type
     *   The class or interface type of events for which this listener will be registered.
     *
     * @return string
     *   The opaque ID of the listener.  This can be used for future reference.
     */
    public function addListenerAfter(
        string $pivotId,
        string $methodName,
        ?string $id = null,
        ?string $type = null
    ): string {
        $type = $type ?? $this->serviceMethodType($methodName);
        $this->registeredMethods[] = $methodName;

        return $this->provider->addListenerServiceAfter($pivotId, $this->serviceName, $methodName, $type, $id);
    }

    /**
     * @return array<string>
     */
    public function registeredMethods(): array
    {
        return $this->registeredMethods;
    }

    /**
     * Safely gets the required Type for a given method from the service class.
     *
     * @param string $methodName
     *   The method name of the listener being registered.
     *
     * @return string
     *   The type required by the listener.
     *
     * @throws InvalidTypeException
     *   If the method has invalid type-hinting, throws an error with a service/method trace.
     */
    protected function serviceMethodType(string $methodName): string
    {
        try {
            $type = $this->getParameterType([$this->serviceClass, $methodName]);
        } catch (InvalidArgumentException $exception) {
            throw InvalidTypeException::fromClassCallable($this->serviceClass, $methodName, $exception);
        }

        return $type;
    }
}
