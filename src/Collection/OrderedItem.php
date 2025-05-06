<?php

/**
 * This file is part of phayne-io/php-event-dispatcher and is proprietary and confidential.
 * Unauthorized copying of this file, via any medium is strictly prohibited.
 *
 * @see       https://github.com/phayne-io/php-event-dispatcher for the canonical source repository
 * @copyright Copyright (c) 2024-2025 Phayne Limited. (https://phayne.io)
 */

declare(strict_types=1);

namespace Phayne\Event\Collection;

/**
 * Class OrderedItem
 *
 * @package Phayne\Event\Collection
 */
class OrderedItem
{
    public ?string $before = null;

    public ?string $after = null;

    final public function __construct(public mixed $item = null, public int $priority = 0, public string $id = '')
    {
    }

    public static function createWithPriority($item, int $priority, string $id): self
    {
        $new = new static();
        $new->item = $item;
        $new->priority = $priority;
        $new->id = $id;

        return $new;
    }

    public static function createBefore(mixed $item, string $pivotId, string $id): self
    {
        $new = new static();
        $new->item = $item;
        $new->before = $pivotId;
        $new->id = $id;

        return $new;
    }

    public static function createAfter(mixed $item, string $pivotId, string $id): self
    {
        $new = new static();
        $new->item = $item;
        $new->after = $pivotId;
        $new->id = $id;

        return $new;
    }
}
