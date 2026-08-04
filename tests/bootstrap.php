<?php

/**
 * Test bootstrap for the plugin PHPUnit suite.
 *
 * @copyright 2026 Joe Huss <detain@interserver.net>
 * @license   MIT
 */

declare(strict_types=1);

/**
 * Test bootstrap for the plugin's own PHPUnit suite.
 *
 * A theme plugin's runtime depends on TWO host contracts:
 *
 *  - `Phlix\Shared\Plugin\LifecycleInterface` — published in
 *    `detain/phlix-shared`, so `composer install` normally supplies it.
 *  - `Phlix\Theming\ThemeSourceInterface` — declared by the SERVER, in no
 *    Composer package at all. Nothing but a running Phlix server can supply it.
 *
 * In an installed plugin (`var/plugins/phlix-plugin-sample-theme/`) both are on
 * the host's classpath. Tested in isolation from this repo, the second one
 * never is — hence the stubs, each registered only when the real contract is
 * absent, so a run inside a server checkout still exercises the real thing.
 */

require __DIR__ . '/../vendor/autoload.php';

if (!interface_exists(\Phlix\Shared\Plugin\LifecycleInterface::class)) {
    require __DIR__ . '/../dev-stubs/LifecycleInterface.php';
}

if (!interface_exists(\Phlix\Theming\ThemeSourceInterface::class)) {
    require __DIR__ . '/../dev-stubs/ThemeSourceInterface.php';
}
