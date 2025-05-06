<?php

/**
 * This file is part of phayne-io/php-event-dispatcher and is proprietary and confidential.
 * Unauthorized copying of this file, via any medium is strictly prohibited.
 *
 * @see       https://github.com/phayne-io/php-event-dispatcher for the canonical source repository
 * @copyright Copyright (c) 2024-2025 Phayne Limited. (https://phayne.io)
 */

declare(strict_types=1);

namespace Phayne\Event;

use Exception;
use Override;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\EventDispatcher\ListenerProviderInterface;
use Psr\EventDispatcher\StoppableEventInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Class Dispatcher
 *
 * @package Phayne\Event
 */
final readonly class Dispatcher implements EventDispatcherInterface
{
    public function __construct(
        protected ListenerProviderInterface $provider,
        protected LoggerInterface $logger = new NullLogger()
    ) {
    }

    #[Override]
    public function dispatch(object $event): object
    {
        if ($event instanceof StoppableEventInterface && $event->isPropagationStopped()) {
            return $event;
        }

        foreach ($this->provider->getListenersForEvent($event) as $listener) {
            try {
                $listener($event);

                if ($event instanceof StoppableEventInterface && $event->isPropagationStopped()) {
                    break;
                }
            } catch (Exception $e) {
                $this->logger->warning('Unhandled exception thrown from listener while processing event.', [
                    'event' => $event,
                    'exception' => $e,
                ]);

                throw $e;
            }
        }

        return $event;
    }
}
