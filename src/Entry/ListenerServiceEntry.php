<?php

/**
 * This file is part of phayne-io/php-event-dispatcher and is proprietary and confidential.
 * Unauthorized copying of this file, via any medium is strictly prohibited.
 *
 * @see       https://github.com/phayne-io/php-event-dispatcher for the canonical source repository
 * @copyright Copyright (c) 2024-2025 Phayne Limited. (https://phayne.io)
 */

declare(strict_types=1);

namespace Phayne\Event\Entry;

use Override;

/**
 * Class ListenerServiceEntry
 *
 * @package Phayne\Event\Entry
 */
readonly class ListenerServiceEntry implements CompilableListenerEntryInterface
{
    public function __construct(
        public string $serviceName,
        public string $method,
        public string $type
    ) {
    }

    #[Override]
    public function getProperties(): array
    {
        return [
            'entryType' => static::class,
            'serviceName' => $this->serviceName,
            'method' => $this->method,
            'type' => $this->type,
        ];
    }
}
