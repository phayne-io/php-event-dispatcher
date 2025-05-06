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

use Override;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;

/**
 * Class DebugEventDispatcher
 *
 * @package Phayne\Event
 */
final readonly class DebugEventDispatcher implements EventDispatcherInterface
{
    public function __construct(
        protected EventDispatcherInterface $dispatcher,
        protected LoggerInterface $logger
    ) {
    }

    #[Override]
    public function dispatch(object $event): object
    {
        $this->logger->debug('Processing event of type {type}.', ['type' => get_class($event), 'event' => $event]);
        return $this->dispatcher->dispatch($event);
    }
}
