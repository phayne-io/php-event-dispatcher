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
use Psr\EventDispatcher\ListenerProviderInterface;

/**
 * Class CallbackProvider
 *
 * @package Phayne\Event\Provider
 */
class CallbackProvider implements ListenerProviderInterface
{
    /**
     * @var array<string, array<string>>
     */
    protected array $callbacks = [];

    /**
     * @return iterable<callable>
     */
    #[Override]
    public function getListenersForEvent(object $event): iterable
    {
        if (! $event instanceof CallbackEventInterface) {
            return [];
        }

        $subject = $event->getSubject();

        foreach ($this->callbacks as $type => $callbacks) {
            if ($event instanceof $type) {
                foreach ($callbacks as $callback) {
                    if (method_exists($subject, $callback)) {
                        yield [$subject, $callback];
                    }
                }
            }
        }
    }

    public function addCallbackMethod(string $type, string $method): self
    {
        $this->callbacks[$type][] = $method;
        return $this;
    }
}
