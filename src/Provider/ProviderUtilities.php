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
use Fig\EventDispatcher\ParameterDeriverTrait;
use InvalidArgumentException;
use ReflectionAttribute;
use ReflectionClass;
use ReflectionException;
use ReflectionFunction;
use ReflectionObject;
use Phayne\Event\Exception\InvalidTypeException;
use Phayne\Event\Listener\ListenerAttribute;

use function array_map;
use function get_class;
use function is_array;
use function is_object;
use function is_string;

/**
 * Trait ProviderUtilities
 *
 * @package Phayne\Event\Provider
 */
trait ProviderUtilities
{
    use ParameterDeriverTrait;

    /**
     * @param callable $listener
     * @return array<ListenerAttribute>
     * @throws ReflectionException
     */
    protected function getAttributes(callable $listener): array
    {
        $ref = null;

        if ($this->isFunctionCallable($listener)) {
            $ref = new ReflectionFunction($listener(...));
        } elseif ($this->isClassCallable($listener)) {
            /** @psalm-suppress InvalidArrayOffset, InvalidArrayAccess */
            [$class, $method] = $listener;
            $ref = new ReflectionClass($class)->getMethod($method);
        } elseif ($this->isObjectCallable($listener)) {
            /** @psalm-suppress InvalidArrayOffset, InvalidArrayAccess */
            [$class, $method] = $listener;
            $ref = new ReflectionObject($class)->getMethod($method);
        }

        if (! $ref) {
            return [];
        }

        $attribs = $ref->getAttributes(ListenerAttribute::class, ReflectionAttribute::IS_INSTANCEOF);

        return array_map(fn(ReflectionAttribute $attrib) => $attrib->newInstance(), $attribs);
    }

    /**
     * Tries to get the type of a callable listener.
     *
     * If unable, throws an exception with information about the listener whose type could not be fetched.
     *
     * @param callable $listener
     *   The callable from which to extract a type.
     *
     * @return string
     *   The type of the first argument.
     */
    protected function getType(callable $listener): string
    {
        try {
            $type = $this->getParameterType($listener);
        } catch (InvalidArgumentException $exception) {
            if ($this->isClassCallable($listener) || $this->isObjectCallable($listener)) {
                /** @psalm-suppress InvalidArrayAccess */
                throw InvalidTypeException::fromClassCallable($listener[0], $listener[1], $exception);
            }
            if ($this->isFunctionCallable($listener) || $this->isClosureCallable($listener)) {
                throw InvalidTypeException::fromFunctionCallable($listener, $exception);
            }
            throw new InvalidTypeException($exception->getMessage(), $exception->getCode(), $exception);
        }
        return $type;
    }

    /**
     * Derives a predictable ID from the listener if possible.
     *
     * It's OK for this method to return null, as OrderedCollection will
     * generate a random ID if necessary.  It will also handle duplicates
     * for us.  This method is just a suggestion.
     *
     * @param callable $listener
     *   The listener for which to derive an ID.
     *
     * @return string|null
     *   The derived ID if possible or null if no reasonable ID could be derived.
     */
    protected function getListenerId(callable $listener): ?string
    {
        if ($this->isFunctionCallable($listener)) {
            /** @psalm-suppress InvalidCast */
            return (string)$listener;
        }

        if ($this->isClassCallable($listener)) {
            /** @psalm-suppress InvalidArrayAccess */
            return $listener[0] . '::' . $listener[1];
        }

        if (is_array($listener) && is_object($listener[0])) {
            return get_class($listener[0]) . '::' . $listener[1];
        }

        return null;
    }

    /**
     * Determines if a callable represents a function.
     *
     * Or at least a reasonable approximation, since a function name may not be defined yet.
     *
     * @return bool
     *  True if the callable represents a function, false otherwise.
     */
    protected function isFunctionCallable(callable $callable): bool
    {
        // We can't check for function_exists() because it may be included later by the time it matters.
        return is_string($callable);
    }

    /**
     * Determines if a callable represents a method on an object.
     *
     * @return bool
     *  True if the callable represents a method object, false otherwise.
     */
    protected function isObjectCallable(callable $callable): bool
    {
        return is_array($callable) && is_object($callable[0]);
    }

    /**
     * Determines if a callable represents a closure/anonymous function.
     *
     * @return bool
     *  True if the callable represents a closure object, false otherwise.
     */
    protected function isClosureCallable(callable $callable): bool
    {
        return $callable instanceof Closure;
    }
}
