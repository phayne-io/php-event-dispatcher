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

/**
 * Interface OrderedProviderInterface
 *
 * @package Phayne\Event\Provider
 */
interface OrderedProviderInterface
{
    /**
     * Adds a listener to the provider.
     *
     * A Listener, ListenerBefore, or ListenerAfter attribute on the listener may also provide
     * the priority, id, or type.  Values specified in the method call take priority over the attribute.
     *
     * @param callable $listener
     *   The listener to register.
     * @param ?int $priority
     *   The numeric priority of the listener. Higher numbers will trigger before lower numbers.
     * @param ?string $id
     *   The identifier by which this listener should be known. If not specified one will be generated.
     * @param ?string $type
     *   The class or interface type of events for which this listener will be registered. If not provided
     *   it will be derived based on the type hint of the listener.
     *
     * @return string
     *   The opaque ID of the listener.  This can be used for future reference.
     */
    public function addListener(
        callable $listener,
        ?int $priority = null,
        ?string $id = null,
        ?string $type = null
    ): string;

    /**
     * Adds a listener to trigger before another existing listener.
     *
     * Note: The new listener is only guaranteed to come before the specified existing listener. No guarantee is made
     * regarding when it comes relative to any other listener.
     *
     * A Listener, ListenerBefore, or ListenerAfter attribute on the listener may also provide
     * the id or type.  The $before parameter specified here will always be used and the type of attribute ignored.
     *
     * @param string $before
     *   The ID of an existing listener.
     * @param callable $listener
     *   The listener to register.
     * @param ?string $id
     *   The identifier by which this listener should be known. If not specified one will be generated.
     * @param ?string $type
     *   The class or interface type of events for which this listener will be registered. If not provided
     *   it will be derived based on the type hint of the listener.
     *
     * @return string
     *   The opaque ID of the listener.  This can be used for future reference.
     */
    public function addListenerBefore(
        string $before,
        callable $listener,
        ?string $id = null,
        ?string $type = null
    ): string;

    /**
     * Adds a listener to trigger after another existing listener.
     *
     * Note: The new listener is only guaranteed to come after the specified existing listener. No guarantee is made
     * regarding when it comes relative to any other listener.
     *
     * A Listener, ListenerBefore, or ListenerAfter attribute on the listener may also provide
     * the id or type.  The $after parameter specified here will always be used and the type of attribute ignored.
     *
     * @param string $after
     *   The ID of an existing listener.
     * @param callable $listener
     *   The listener to register.
     * @param ?string $id
     *   The identifier by which this listener should be known. If not specified one will be generated.
     * @param ?string $type
     *   The class or interface type of events for which this listener will be registered. If not provided
     *   it will be derived based on the type hint of the listener.
     *
     * @return string
     *   The opaque ID of the listener.  This can be used for future reference.
     */
    public function addListenerAfter(
        string $after,
        callable $listener,
        ?string $id = null,
        ?string $type = null
    ): string;

    /**
     * Adds a method on a service as a listener.
     *
     * This method does not support attributes, as the class name is unknown at registration.
     *
     * @param string $service
     *   The name of a service on which this listener lives.
     * @param string $method
     *   The method name of the service that is the listener being registered.
     * @param string $type
     *   The class or interface type of events for which this listener will be registered.
     * @param ?int $priority
     *   The numeric priority of the listener. Higher numbers will trigger before lower numbers.
     * @param ?string $id
     *   The identifier by which this listener should be known. If not specified one will be generated.
     *
     * @return string
     *   The opaque ID of the listener.  This can be used for future reference.
     */
    public function addListenerService(
        string $service,
        string $method,
        string $type,
        ?int $priority = null,
        ?string $id = null
    ): string;

    /**
     * Adds a service listener to trigger before another existing listener.
     *
     * Note: The new listener is only guaranteed to come before the specified existing listener. No guarantee is made
     * regarding when it comes relative to any other listener.
     *
     * This method does not support attributes, as the class name is unknown at registration.
     *
     * @param string $before
     *   The ID of an existing listener.
     * @param string $service
     *   The name of a service on which this listener lives.
     * @param string $method
     *   The method name of the service that is the listener being registered.
     * @param string $type
     *   The class or interface type of events for which this listener will be registered.
     * @param ?string $id
     *   The identifier by which this listener should be known. If not specified one will be generated.
     *
     * @return string
     *   The opaque ID of the listener.  This can be used for future reference.
     */
    public function addListenerServiceBefore(
        string $before,
        string $service,
        string $method,
        string $type,
        ?string $id = null
    ): string;

    /**
     * Adds a service listener to trigger before another existing listener.
     *
     * Note: The new listener is only guaranteed to come before the specified existing listener. No guarantee is made
     * regarding when it comes relative to any other listener.
     *
     * This method does not support attributes, as the class name is unknown at registration.
     *
     * @param string $after
     *   The ID of an existing listener.
     * @param string $service
     *   The name of a service on which this listener lives.
     * @param string $method
     *   The method name of the service that is the listener being registered.
     * @param string $type
     *   The class or interface type of events for which this listener will be registered.
     * @param ?string $id
     *   The identifier by which this listener should be known. If not specified one will be generated.
     *
     * @return string
     *   The opaque ID of the listener.  This can be used for future reference.
     */
    public function addListenerServiceAfter(
        string $after,
        string $service,
        string $method,
        string $type,
        ?string $id = null
    ): string;

    /**
     * Registers all listener methods on a service as listeners.
     *
     * A method on the specified class is a listener if:
     * - It is public.
     * - It's name is in the form on*.  onUpdate(), onUserLogin(), onHammerTime() will all be registered.
     * - It has a Listener/ListenerBefore/ListenerAfter attribute.
     *
     * The event type the listener is for will be derived from the type hint in the method signature.
     *
     * @param class-string $class
     *   The class name to be registered as a subscriber.
     * @param string $service
     *   The name of a service in the container.
     */
    public function addSubscriber(string $class, string $service): void;
}
