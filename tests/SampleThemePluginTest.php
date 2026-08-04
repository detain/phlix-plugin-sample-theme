<?php

/**
 * Tests for the sample ui-theme plugin.
 *
 * @copyright 2026 Joe Huss <detain@interserver.net>
 * @license   MIT
 */

declare(strict_types=1);

namespace Phlix\PluginSampleTheme\Tests;

use Phlix\PluginSampleTheme\SampleThemePlugin;
use Phlix\Shared\Plugin\LifecycleInterface;
use Phlix\Theming\ThemeSourceInterface;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

/**
 * What this suite can and cannot prove, stated up front.
 *
 * The authoritative checks on a theme payload live in the HOST
 * (`Phlix\Theming\ThemeTokenAllowlist` + `ThemeTokenValidator`), and this repo
 * deliberately does not depend on the server, so those classes are not
 * available here. Re-typing the 53-name allowlist or the value grammar into
 * this file would be a second hand transcription that drifts silently — the
 * exact failure mode phlix-server's own S228 finding is about.
 *
 * So this suite asserts only what the PLUGIN owns, and asserts it as
 * PROPERTIES rather than as a copy of the host's tables:
 *
 *  - the shipped `plugin.json` and the shipped class agree with each other;
 *  - no token value contains a CSS construct the host grammar has no
 *    production for (`var(`, `url(`, `;`, `}`, …) — the real authoring
 *    mistake, since colors.css writes the built-ins as `var()` chains;
 *  - the `extends` topology the sample exists to demonstrate is intact;
 *  - the 13 alias tokens with no `var()` consumers in the shipped SPA stay
 *    omitted, so a later edit cannot quietly re-inflate the payload;
 *  - `providedThemes()` honours the resident-memory contract (pure, stable).
 *
 * The host-side proof — that these payloads survive the real validator and
 * register through the real `ThemeSourceRegistry` — lives in phlix-server's
 * `tests/Unit/Plugins/SampleThemePluginTest.php`, where the production classes
 * actually exist.
 */
final class SampleThemePluginTest extends TestCase
{
    /**
     * Theme ids the host reserves for its own built-ins. A plugin claiming one
     * would silently replace a shipped theme, and the host's validator rejects
     * it — mirrored here so the mistake is caught before install.
     *
     * @var list<string>
     */
    private const RESERVED_IDS = ['nocturne', 'daylight', 'midnight'];

    /**
     * The `--color-*` aliases measured (2026-08-04) to have ZERO `var()`
     * consumers in the shipped SPA. Setting them is a no-op that only inflates
     * the payload, so this sample omits them on purpose; this list is the guard
     * that keeps them omitted.
     *
     * @var list<string>
     */
    private const DEAD_ALIASES = [
        '--color-primary',
        '--color-primary-hover',
        '--color-primary-active',
        '--color-surface-hover',
        '--color-surface-elevated',
        '--color-surface-active',
        '--color-text-secondary',
        '--color-text-subtle',
        '--color-border-subtle',
        '--color-error-bg',
        '--color-success-bg',
        '--color-warning-bg',
        '--color-info-bg',
    ];

    /**
     * The manifest as it ships, through PHP's own JSON parser.
     *
     * @return array<string, mixed>
     */
    private function manifest(): array
    {
        $json = file_get_contents(__DIR__ . '/../plugin.json');
        self::assertIsString($json, 'plugin.json must be readable.');

        $decoded = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        self::assertIsArray($decoded);

        /** @var array<string, mixed> $decoded */
        return $decoded;
    }

    /**
     * Every theme payload, keyed by id.
     *
     * @return array<string, array<array-key, mixed>>
     */
    private function themesById(): array
    {
        $byId = [];
        foreach ((new SampleThemePlugin())->providedThemes() as $payload) {
            $id = $payload['id'] ?? null;
            self::assertIsString($id);
            $byId[$id] = $payload;
        }

        return $byId;
    }

    /**
     * One theme's token map, narrowed at RUNTIME rather than by an
     * `@var` annotation — the shape claim is what the suite is checking, so
     * asserting it beats asserting it away.
     *
     * @return array<string, string>
     */
    private function tokensOf(string $id): array
    {
        $themes = $this->themesById();
        self::assertArrayHasKey($id, $themes);

        $tokens = $themes[$id]['tokens'] ?? null;
        self::assertIsArray($tokens);

        $narrowed = [];
        /** @var mixed $value */
        foreach ($tokens as $key => $value) {
            self::assertIsString($key, 'A token name must be a string.');
            self::assertIsString($value, "Token {$key} on {$id} must be a string.");
            $narrowed[$key] = $value;
        }

        return $narrowed;
    }

    public function testTheManifestDeclaresAUiThemePointingAtTheShippedClass(): void
    {
        $manifest = $this->manifest();

        self::assertSame('phlix-plugin-sample-theme', $manifest['name']);
        self::assertSame('ui-theme', $manifest['type']);
        self::assertSame(SampleThemePlugin::class, $manifest['entry']);
        self::assertSame([], $manifest['events']);
        self::assertNull($manifest['signature']);
        // The theme-source capability arm landed in 0.44.0; declaring anything
        // lower would let the plugin install onto a server that cannot register
        // it, where it would appear enabled and contribute nothing.
        self::assertSame('0.44.0', $manifest['phlix_min_server_version']);
    }

    public function testTheManifestEntryClassActuallyExistsAndIsAutoloadable(): void
    {
        $manifest = $this->manifest();
        self::assertIsString($manifest['entry']);
        /** @var class-string $entry */
        $entry = $manifest['entry'];

        // Resolved through the composer autoloader, i.e. exactly how the host
        // finds it after `composer install` inside var/plugins/<name>/.
        self::assertTrue(class_exists($entry), "Manifest entry class {$entry} must be autoloadable.");
    }

    public function testTheEntryClassImplementsBothContractsTheLoaderArmNeeds(): void
    {
        $plugin = new SampleThemePlugin();

        // PluginLoader::wire() refuses anything that is not a LifecycleInterface
        // BEFORE it looks for the theme capability, so both are required for the
        // plugin to be enable-able at all.
        self::assertInstanceOf(LifecycleInterface::class, $plugin);
        self::assertInstanceOf(ThemeSourceInterface::class, $plugin);
    }

    public function testTheSourceNameIsAStableLowercaseSlug(): void
    {
        $plugin = new SampleThemePlugin();

        self::assertSame(SampleThemePlugin::SOURCE_NAME, $plugin->themeSourceName());
        // The host keys provenance on this and validates it with this pattern;
        // a name it rejects fails the whole enable.
        self::assertMatchesRegularExpression(
            '/^[a-z0-9](?:[a-z0-9._-]{0,62}[a-z0-9])?\z/',
            $plugin->themeSourceName(),
        );
    }

    public function testTheLifecycleHooksAreCheapNoOps(): void
    {
        $plugin = new SampleThemePlugin();

        // onEnable() runs synchronously on a resident worker thread at boot for
        // every worker; anything but a no-op here stalls the fleet.
        $plugin->onEnable($this->createMock(ContainerInterface::class));
        $plugin->onDisable();

        self::assertSame([], $plugin->subscribedEvents());
    }

    public function testProvidedThemesIsPureSoItIsSafeOnAResidentWorker(): void
    {
        $plugin = new SampleThemePlugin();

        $first = $plugin->providedThemes();
        $second = $plugin->providedThemes();

        // Identical value AND no growth: the payload must not be memoised into
        // anything that accumulates across repeated enable cycles.
        self::assertSame($first, $second);
        self::assertCount(2, $first);
    }

    public function testEveryPayloadUsesOnlyTheFiveKeysTheHostAccepts(): void
    {
        foreach ((new SampleThemePlugin())->providedThemes() as $payload) {
            self::assertSame(
                ['id', 'name', 'dark', 'extends', 'tokens'],
                array_keys($payload),
                'The host rejects an unknown payload key outright.',
            );
            self::assertIsBool($payload['dark'], '"dark" is checked with is_bool(); 1 or "true" is refused.');
            self::assertIsString($payload['name']);
            self::assertIsArray($payload['tokens']);
        }
    }

    public function testThemeIdsAreUniqueSlugsAndNeverClaimABuiltIn(): void
    {
        $ids = array_keys($this->themesById());

        self::assertSame($ids, array_values(array_unique($ids)));
        foreach ($ids as $id) {
            self::assertMatchesRegularExpression('/^[a-z0-9](?:[a-z0-9-]{0,62}[a-z0-9])?\z/', $id);
            self::assertNotContains($id, self::RESERVED_IDS, "Theme id {$id} is reserved by the host.");
        }
    }

    /**
     * The whole point of shipping two themes: one chain the SPA must resolve
     * (plugin → built-in) and one the server can (plugin → plugin).
     */
    public function testTheSampleExercisesBothKindsOfExtendsChain(): void
    {
        $themes = $this->themesById();

        self::assertSame('midnight', $themes['sample-dusk']['extends']);
        self::assertContains($themes['sample-dusk']['extends'], self::RESERVED_IDS);

        self::assertSame('sample-dusk', $themes['sample-dusk-high-contrast']['extends']);
        self::assertNotContains($themes['sample-dusk-high-contrast']['extends'], self::RESERVED_IDS);
    }

    /**
     * If the high-contrast variant ever grew its own `--bg`, the sample would
     * stop proving that a client has to flatten the chain from the LIST
     * response rather than reading `/api/v1/themes/{id}`.
     */
    public function testTheHighContrastVariantInheritsItsBackgroundFromItsBase(): void
    {
        $contrast = $this->tokensOf('sample-dusk-high-contrast');
        $dusk = $this->tokensOf('sample-dusk');

        self::assertArrayNotHasKey('--bg', $contrast);
        self::assertArrayHasKey('--text', $contrast);
        self::assertArrayHasKey('--bg', $dusk);
    }

    /**
     * `sample-dusk` deliberately leaves the status colours to the base so the
     * cascade is visible in the rendered UI; a client reading `--error` gets
     * midnight's value, not one this plugin invented.
     */
    public function testStatusColoursAreLeftToTheBaseTheme(): void
    {
        $dusk = $this->tokensOf('sample-dusk');

        $status = [
            '--error', '--error-bg',
            '--success', '--success-bg',
            '--warning', '--warning-bg',
            '--info', '--info-bg',
        ];

        foreach ($status as $token) {
            self::assertArrayNotHasKey($token, $dusk);
        }
    }

    /**
     * The S226 decision, pinned: aliases nothing reads stay out.
     */
    public function testNoThemeSetsAnAliasTokenTheSpaDoesNotRead(): void
    {
        foreach (array_keys($this->themesById()) as $id) {
            $tokens = $this->tokensOf($id);
            foreach (self::DEAD_ALIASES as $dead) {
                self::assertArrayNotHasKey(
                    $dead,
                    $tokens,
                    "Theme {$id} sets {$dead}, which no shipped SPA rule reads.",
                );
            }
        }
    }

    /**
     * The value-side authoring trap, asserted as a property.
     *
     * colors.css writes the built-in themes as `var()` chains, so the natural
     * mistake when copying values out of it is to bring the `var()` along. The
     * host grammar has no production for it — or for `;`, `}`, `url()`,
     * comments or newlines — so such a value fails the whole plugin enable.
     */
    public function testNoTokenValueCarriesACssConstructTheHostGrammarRejects(): void
    {
        foreach (array_keys($this->themesById()) as $id) {
            $tokens = $this->tokensOf($id);

            self::assertNotSame([], $tokens);

            foreach ($tokens as $key => $value) {
                self::assertStringStartsWith('--', $key, "Token {$key} on {$id} is not a custom property.");
                self::assertMatchesRegularExpression('/^--[a-z0-9-]+\z/', $key);

                self::assertSame(trim($value), $value, "Token {$key} on {$id} has surrounding whitespace.");
                self::assertLessThanOrEqual(128, strlen($value), "Token {$key} on {$id} exceeds the host's value cap.");

                foreach (['var(', 'url(', '/*', ';', '}', '{', '\\', "\n", "\t"] as $forbidden) {
                    self::assertStringNotContainsString(
                        $forbidden,
                        $value,
                        "Token {$key} on {$id} contains \"{$forbidden}\"; "
                        . 'the host grammar has no production for it.',
                    );
                }
            }
        }
    }

    /**
     * Token counts, so adding or removing one is a loud, deliberate change
     * rather than a silent payload drift.
     */
    public function testTheShippedTokenCountsAreExactlyWhatTheReadmeDocuments(): void
    {
        self::assertCount(28, $this->tokensOf('sample-dusk'));
        self::assertCount(8, $this->tokensOf('sample-dusk-high-contrast'));
    }
}
