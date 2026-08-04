<?php

/**
 * Dev-only stub of the host server's theme-source contract.
 *
 * @copyright 2026 Joe Huss <detain@interserver.net>
 * @license   MIT
 */

declare(strict_types=1);

namespace Phlix\Theming;

/**
 * Dev-only stub of `Phlix\Theming\ThemeSourceInterface`.
 *
 * Unlike `LifecycleInterface`, this contract does **not** live in
 * `detain/phlix-shared` — it is declared by the host server at
 * `src/Theming/ThemeSourceInterface.php` and is on the classpath only inside a
 * running Phlix server. Composer therefore cannot supply it, so the plugin's
 * own suite and its PHPStan run need this minimal, byte-compatible copy.
 *
 * `tests/bootstrap.php` loads it only when the real interface is absent, and
 * `phpstan.neon` picks it up through `scanDirectories` (which INTRODUCES a
 * symbol; `stubFiles` would only override one PHPStan can already resolve, and
 * is silently useless for a symbol it cannot find at all).
 *
 * Keep in sync with the host definition if that contract ever changes.
 *
 * @internal Tests/analysis only — never autoloaded into production.
 */
interface ThemeSourceInterface
{
    /**
     * The canonical, stable name of this theme source.
     */
    public function themeSourceName(): string;

    /**
     * The raw theme payloads this source contributes.
     *
     * @return list<array<array-key, mixed>>
     */
    public function providedThemes(): array;
}
