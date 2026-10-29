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
     * The whole point of shipping two themes: the two `extends` chains land
     * through different mechanisms — a built-in base arrives as a
     * `data-theme` stylesheet switch, a plugin base is merged from the LIST
     * response and applied with `setProperty`. Both links come from the same
     * list; the wire never flattens, because `tokens` is each theme's own map.
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
        self::assertCount(9, $this->tokensOf('sample-dusk-high-contrast'));
    }

    /**
     * WCAG AA floor for the faintest ink, measured FROM THE PAYLOAD HEXES —
     * no host CSS needed, so this test travels with the payload.
     *
     * `--text-faint` is not decorative in the shipped SPA: phlix-ui's
     * `AuthField.vue` renders `::placeholder` in `--text-faint` on an input
     * background of `color-mix(in srgb, var(--surface) 80%, transparent)`
     * laid over whatever page background the form sits on (`--bg`,
     * `--surface`, or a `--surface-2` panel). The value this sample shipped
     * from its first commit — `#465061` — measures 2.39:1 there:
     * decoration-grade ink on an interactive control.
     *
     * The high-contrast variant must carry its OWN faintest tier: it inherits
     * dusk's backgrounds but raises its ink, and a variant that forgot the
     * tier would leave placeholders at the dusk value inside an otherwise
     * raised-contrast form. Both variants are pinned against every background
     * derivable from the payload — raw and composited.
     */
    public function testTheFaintInkClearsWcagAaOnEveryBackgroundItRendersOn(): void
    {
        $dusk = $this->tokensOf('sample-dusk');
        $highContrast = $this->tokensOf('sample-dusk-high-contrast');

        self::assertArrayHasKey(
            '--text-faint',
            $highContrast,
            'The high-contrast variant must override the faintest tier, not inherit it.'
        );

        // Raw ink is the caption/hint role — it sits on the page's opaque
        // grounds (--bg, --surface). --surface-2 is NOT asserted raw on
        // purpose: the value that would clear 4.5:1 there (luminance ≥
        // 0.2259) is brighter than --text-subtle (0.2235) — the ramp would
        // have to collapse to satisfy it. On --surface-2 the hosted faint
        // roles are inputs, which always arrive composited (the law below);
        // a raw-faint caption on the elevated panel is a design change that
        // must re-derive this arithmetic, not a default the sample can pin.
        $grounds = ['--bg', '--surface'];

        foreach (['sample-dusk' => $dusk, 'sample-dusk-high-contrast' => $highContrast] as $id => $tokens) {
            foreach (['--bg', '--surface', '--surface-2'] as $pageKey) {
                $page = $dusk[$pageKey];
                $input = self::composite($dusk['--surface'], 0.8, $page);

                $backgrounds = ['composited input' => $input];
                if (in_array($pageKey, $grounds, true)) {
                    $backgrounds[$pageKey] = $page;
                }

                foreach ($backgrounds as $label => $background) {
                    $ratio = self::contrast($tokens['--text-faint'], $background);
                    self::assertGreaterThanOrEqual(
                        4.5,
                        $ratio,
                        sprintf(
                            '%s: --text-faint %s on %s %s is %.2f:1 — below the 4.5:1 body-text floor.',
                            $id,
                            $tokens['--text-faint'],
                            $label,
                            $background,
                            $ratio
                        )
                    );
                }
            }
        }
    }

    /**
     * The four text tiers must stay a STRICT luminance ramp — text > muted >
     * subtle > faint — in both themes. Pulling one tier into its neighbour to
     * pass a contrast floor is how ramps silently die; raise the whole tier
     * instead and keep the ordering provable.
     */
    public function testTheTextTiersFormAStrictLuminanceRamp(): void
    {
        foreach (['sample-dusk', 'sample-dusk-high-contrast'] as $id) {
            $tokens = $this->tokensOf($id);

            $previous = null;
            foreach (['--text', '--text-muted', '--text-subtle', '--text-faint'] as $tier) {
                $luminance = self::luminance($tokens[$tier]);

                if ($previous !== null) {
                    self::assertLessThan(
                        $previous,
                        $luminance,
                        $id . ': ramp tier ' . $tier . ' is not dimmer than the tier above it.'
                    );
                }
                $previous = $luminance;
            }
        }
    }

    /**
     * WCAG 2.x relative luminance of a `#rrggbb` hex.
     */
    private static function luminance(string $hex): float
    {
        $linear = [];
        foreach ([0, 1, 2] as $i) {
            $channel = hexdec(substr($hex, 1 + 2 * $i, 2)) / 255.0;
            $linear[] = $channel <= 0.03928 ? $channel / 12.92 : (($channel + 0.055) / 1.055) ** 2.4;
        }

        return 0.2126 * $linear[0] + 0.7152 * $linear[1] + 0.0722 * $linear[2];
    }

    /**
     * WCAG contrast ratio between two `#rrggbb` hexes.
     */
    private static function contrast(string $first, string $second): float
    {
        $luminanceFirst = self::luminance($first);
        $luminanceSecond = self::luminance($second);

        return (max($luminanceFirst, $luminanceSecond) + 0.05) / (min($luminanceFirst, $luminanceSecond) + 0.05);
    }

    /**
     * The 8-bit sRGB result of `color-mix(in srgb, $layer <alpha>, transparent)`
     * composited over an opaque page: layer × alpha + page × (1 − alpha) per
     * channel — the arithmetic a browser performs for the host's input rule.
     */
    private static function composite(string $layer, float $alpha, string $page): string
    {
        $hex = '#';
        foreach ([0, 1, 2] as $i) {
            $channel = $alpha * hexdec(substr($layer, 1 + 2 * $i, 2))
                + (1.0 - $alpha) * hexdec(substr($page, 1 + 2 * $i, 2));
            $hex .= str_pad(dechex((int) round($channel)), 2, '0', STR_PAD_LEFT);
        }

        return $hex;
    }
}
