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

use Phayne\Event\Entry\CompilableListenerEntryInterface;

/**
 * Class ProviderCompiler
 *
 * @package Phayne\Event\Provider
 */
class ProviderCompiler
{
    public function compile(
        ProviderBuilder $listeners,
        mixed $stream,
        string $class = 'CompiledListenerProvider',
        string $namespace = '\\Phayne\\Event\\Compiled'
    ): void {
        fwrite($stream, $this->createPreamble($class, $namespace));

        /** @var CompilableListenerEntryInterface $listenerEntry */
        foreach ($listeners as $listenerEntry) {
            $item = $this->createEntry($listenerEntry);
            fwrite($stream, $item);
        }

        fwrite($stream, $this->createClosing());
    }

    protected function createEntry(CompilableListenerEntryInterface $listenerEntry): string
    {
        return var_export($listenerEntry->getProperties(), true) . ',' . PHP_EOL;
    }

    protected function createPreamble(string $class, string $namespace): string
    {
        return <<<END
<?php
declare(strict_types=1);

namespace {$namespace};

use Teeps\Event\CompiledListenerProviderBase;
use Psr\EventDispatcher\EventInterface;

class {$class} extends CompiledListenerProviderBase
{
  protected const array LISTENERS = [
END;
    }

    protected function createClosing(): string
    {
        return <<<'END'
    ];
}
END;
    }
}
