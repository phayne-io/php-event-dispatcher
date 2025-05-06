<?php

/**
 * This file is part of phayne-io/php-event-dispatcher and is proprietary and confidential.
 * Unauthorized copying of this file, via any medium is strictly prohibited.
 *
 * @see       https://github.com/phayne-io/php-event-dispatcher for the canonical source repository
 * @copyright Copyright (c) 2024-2025 Phayne Limited. (https://phayne.io)
 */

declare(strict_types=1);

namespace Phayne\Event\Listener;

use Attribute;

/**
 * Class ListenerAfter
 *
 * @package Phayne\Event\Listener
 */
#[Attribute(Attribute::TARGET_FUNCTION | Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
final readonly class ListenerAfter implements ListenerAttribute
{
    public function __construct(public string $after, public ?string $id = null, public ?string $type = null)
    {
    }
}
