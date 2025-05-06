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

use Error;
use IteratorAggregate;
use Override;
use Phayne\Event\Exception\MissingItemException;
use Traversable;

use function array_map;
use function krsort;
use function uniqid;

/**
 * Class OrderedCollection
 *
 * @package Phayne\Event\Collection
 * @template T
 * @implements IteratorAggregate<T>
 */
class OrderedCollection implements IteratorAggregate
{
    protected array $items = [];

    /**
     * @var array<OrderedItem>
     *
     * A list of the items in the collection indexed by ID. Order is undefined.
     */
    protected array $itemLookup = [];

    protected bool $sorted = false;

    /** @var array<OrderedItem> */
    protected array $toPrioritize = [];

    /**
     * Adds an item to the collection with a given priority.  (Higher numbers come first.)
     *
     * @param mixed $item
     *   The item to add. May be any data type.
     * @param int $priority
     *   The priority order of the item. Higher numbers will come first.
     * @param ?string $id
     *   An opaque string ID by which this item should be known. If it already exists a counter suffix will be added.
     *
     * @return string
     *   An opaque ID string uniquely identifying the item for future reference.
     */
    public function addItem(mixed $item, int $priority = 0, ?string $id = null): string
    {
        $id = $this->enforceUniqueId($id);

        $item = OrderedItem::createWithPriority($item, $priority, $id);

        $this->items[$priority][] = $item;
        $this->itemLookup[$id] = $item;

        $this->sorted = false;

        return $id;
    }

    /**
     * Adds an item to the collection before another existing item.
     *
     * Note: The new item is only guaranteed to get returned before the existing item. No guarantee is made
     * regarding when it will be returned relative to any other item.
     *
     * @param string $pivotId
     *   The existing ID of an item in the collection.
     * @param mixed $item
     *   The new item to add.
     * @param ?string $id
     *   An opaque string ID by which this item should be known. If it already exists a counter suffix will be added.
     *
     * @return string
     *   An opaque ID string uniquely identifying the new item for future reference.
     */
    public function addItemBefore(string $pivotId, mixed $item, ?string $id = null): string
    {
        $id = $this->enforceUniqueId($id);

        // If the item this new item is pivoting off of is already defined, add it normally.
        if (isset($this->itemLookup[$pivotId])) {
            // Because high numbers come first, we have to ADD one to get the new item to be returned first.
            return $this->addItem($item, $this->itemLookup[$pivotId]->priority + 1, $id);
        }

        // Otherwise, we still add it but flag it as one to revisit later to determine the priority.
        $item = OrderedItem::createBefore($item, $pivotId, $id);

        $this->toPrioritize[] = $item;
        $this->itemLookup[$id] = $item;

        $this->sorted = false;

        return $id;
    }

    /**
     * Adds an item to the collection after another existing item.
     *
     * Note: The new item is only guaranteed to get returned after the existing item. No guarantee is made
     * regarding when it will be returned relative to any other item.
     *
     * @param string $pivotId
     *   The existing ID of an item in the collection.
     * @param mixed $item
     *   The new item to add.
     * @param ?string $id
     *   An opaque string ID by which this item should be known. If it already exists a counter suffix will be added.
     *
     * @return string
     *   An opaque ID string uniquely identifying the new item for future reference.
     */
    public function addItemAfter(string $pivotId, mixed $item, ?string $id = null): string
    {
        $id = $this->enforceUniqueId($id);

        // If the item this new item is pivoting off of is already defined, add it normally.
        if (isset($this->itemLookup[$pivotId])) {
            // Because high numbers come first, we have to SUBTRACT one to get the new item to be returned first.
            return $this->addItem($item, $this->itemLookup[$pivotId]->priority - 1, $id);
        }

        // Otherwise, we still add it but flag it as one to revisit later to determine the priority.
        $item = OrderedItem::createAfter($item, $pivotId, $id);

        $this->toPrioritize[] = $item;
        $this->itemLookup[$id] = $item;

        $this->sorted = false;

        return $id;
    }

    /**
     * {@inheritdoc}
     *
     * @return Traversable<mixed>
     *
     * Note: Because of how iterator_to_array() works, you MUST pass `false` as the second parameter to that function
     * if calling it on the return from this object.  If not, only the last list's worth of values will be included in
     * the resulting array.
     */
    #[Override]
    public function getIterator(): Traversable
    {
        if (! $this->sorted) {
            $this->prioritizePendingItems();
            krsort($this->items);
            $this->sorted = true;
        }

        return (function () {
            foreach ($this->items as $itemList) {
                yield from array_map(static function (OrderedItem $item) {
                    return $item->item;
                }, $itemList);
            }
        })();
    }

    protected function prioritizePendingItems(): void
    {
        foreach ($this->toPrioritize as $item) {
            if (isset($item->before)) {
                if (!isset($this->itemLookup[$item->before])) {
                    throw new MissingItemException(sprintf(
                        'Cannot add item %s before non-existent item %s',
                        $item->id,
                        $item->before
                    ));
                }
                $priority = $this->itemLookup[$item->before]->priority + 1;
                $this->items[$priority][] = $item;
            } elseif (isset($item->after)) {
                if (!isset($this->itemLookup[$item->after])) {
                    throw new MissingItemException(sprintf(
                        'Cannot add item %s after non-existent item %s',
                        $item->id,
                        $item->after
                    ));
                }
                $priority = $this->itemLookup[$item->after]->priority - 1;
                $this->items[$priority][] = $item;
            } else {
                throw new Error('No, seriously, how did you get here?');
            }
        }

        $this->toPrioritize = [];
    }

    /**
     * Ensures a unique ID for all items in the collection.
     *
     * @param string|null $id
     *   The proposed ID of an item, or null to generate a random string.
     *
     * @return string
     *   A confirmed unique ID string.
     */
    protected function enforceUniqueId(?string $id): string
    {
        $candidateId = $id ?? uniqid('', true);
        $counter = 1;

        while (isset($this->itemLookup[$candidateId])) {
            $candidateId = $id . '-' . $counter++;
        }

        return $candidateId;
    }
}
