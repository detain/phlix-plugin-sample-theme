<?php

/**
 * Dev-only stub of the host server's lifecycle contract.
 *
 * @copyright 2026 Joe Huss <detain@interserver.net>
 * @license   MIT
 */

declare(strict_types=1);

namespace Phlix\Shared\Plugin;

use Psr\Container\ContainerInterface;

/**
 * Dev-only stub of `Phlix\Shared\Plugin\LifecycleInterface`.
 *
 * The canonical definition ships in `detain/phlix-shared`
 * (`src/Plugin/LifecycleInterface.php`) and is resolved by the host
 * application's autoloader in an installed plugin. This copy exists only for
 * the case where the plugin is checked out and analysed without that package
 * present; `tests/bootstrap.php` loads it only when the real interface is
 * absent.
 *
 * @internal Tests/analysis only — never autoloaded into production.
 */
interface LifecycleInterface
{
    public function onEnable(ContainerInterface $container): void;

    public function onDisable(): void;

    /**
     * @return array<class-string, string|callable>
     */
    public function subscribedEvents(): array;
}
