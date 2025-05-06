<?php

/**
 * This file is part of phayne-io/php-event-dispatcher and is proprietary and confidential.
 * Unauthorized copying of this file, via any medium is strictly prohibited.
 *
 * @see       https://github.com/phayne-io/php-event-dispatcher for the canonical source repository
 * @copyright Copyright (c) 2024-2025 Phayne Limited. (https://phayne.io)
 */

declare(strict_types=1);

namespace Phayne\Event\Exception;

use InvalidArgumentException;
use ReflectionClass;
use ReflectionException;
use ReflectionFunction;
use Throwable;

/**
 * Class InvalidTypeException
 *
 * @package Phayne\Event\Exception
 */
final class InvalidTypeException extends InvalidArgumentException
{
    protected static string $baseMessage = 'Function does not specify a valid type';

    /**
     * @param class-string $class
     */
    public static function fromClassCallable(string $class, string $method, ?Throwable $previous = null): self
    {
        $message = InvalidTypeException::$baseMessage;

        try {
            $reflector = new ReflectionClass($class);
            $message .= sprintf(' (%s::%s)', $reflector->getName(), $method);
        } catch (ReflectionException) {
            $message .= " ((unknown class)::{$method})";
        }

        return new self($message, 0, $previous);
    }

    public static function fromFunctionCallable(callable $function, ?Throwable $previous = null): self
    {
        $message = InvalidTypeException::$baseMessage;

        if (is_string($function) || $function instanceof \Closure) {
            try {
                $reflector = new ReflectionFunction($function);
                $message .= sprintf(' (%s:%s)', $reflector->getFileName(), $reflector->getStartLine());
            } catch (ReflectionException) {
            }
        }

        return new self($message, 0, $previous);
    }
}
