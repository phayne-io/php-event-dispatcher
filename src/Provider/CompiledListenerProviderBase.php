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
use Phayne\Event\Entry\ListenerFunctionEntry;
use Phayne\Event\Entry\ListenerServiceEntry;
use Phayne\Event\Entry\ListenerStaticMethodEntry;
use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\ListenerProviderInterface;
use RuntimeException;

/**
 * Class CompiledListenerProviderBase
 *
 * @package Phayne\Event\Provider
 */
class CompiledListenerProviderBase implements ListenerProviderInterface
{
    protected const array LISTENERS = [];

    public function __construct(protected readonly ContainerInterface $container)
    {
    }

    #[Override]
    public function getListenersForEvent(object $event): iterable
    {
        $count = count(static::LISTENERS);
        $ret = [];

        for ($i = 0; $i < $count; ++$i) {
            $listener = static::LISTENERS[$i];

            if ($event instanceof $listener['type']) {
                // Turn this into a match() in PHP 8.
                $ret[] = match ($listener['entryType']) {
                    ListenerFunctionEntry::class => $listener['listener'],
                    ListenerStaticMethodEntry::class => [$listener['class'], $listener['method']],
                    ListenerServiceEntry::class => function (object $event) use ($listener): void {
                        $this->container->get($listener['serviceName'])->{$listener['method']}($event);
                    },
                    default => throw new RuntimeException(sprintf(
                        'No such listener type found in compiled container definition: %s',
                        $listener['entryType']
                    )),
                };
            }
        }

        return $ret;
    }
}
