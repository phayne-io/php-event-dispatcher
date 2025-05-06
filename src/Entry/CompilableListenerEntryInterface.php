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

/**
 * Interface CompilableListenerEntryInterface
 *
 * @package Phayne\Event\Entry
 */
interface CompilableListenerEntryInterface
{
    /**
     * Extracts relevant information for the listener.
     *
     * @internal
     *
     * @return array
     */
    public function getProperties(): array;
}
