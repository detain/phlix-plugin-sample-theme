# phlix-plugin-sample-theme

A complete, installable **`ui-theme`** plugin — the reference for shipping a
theme to the Phlix SPA. Fork it, rename it, change the token values —
[Renaming your fork](#renaming-your-fork) enumerates every identifier the
rename touches.

```
phlix-plugin-sample-theme/
├── composer.json               # PSR-4 autoload for the entry class
├── composer.lock               # COMMITTED — the S440 gate audits the whole lock
├── plugin.json                 # manifest: type "ui-theme", entry FQCN
├── CHANGELOG.md                # release history; version markers carry git coords
├── LICENSE                     # MIT — the copyright line is a fork-rename target
├── .github/workflows/test.yml  # CI; the composer-audit leg is the LAST job (guard-pinned)
├── scripts/
│   └── security-audit-check.php  # the S440 gate — whole-lock audit, capable of failing
├── src/SampleThemePlugin.php   # LifecycleInterface + ThemeSourceInterface
├── dev-stubs/                  # host-only contracts, for THIS repo's CI only
├── tests/
│   ├── bootstrap.php
│   ├── SampleThemePluginTest.php   # payload laws: counts, grammar, contrast, aliases
│   └── SecurityAuditCheckTest.php  # guard: pins the gate script + workflow against neutering
├── phpunit.xml
├── phpstan.neon
└── phpcs.xml                   # tool configs (scripts/ exclusions are documented in both)
```

Four of those artifacts exist because the estate requires a theme to ship its
own gate: `scripts/security-audit-check.php`, its guard test, the committed
lock, and the audit-last workflow are what a fork must KEEP, not just rename.

## Installing it

### The supported route: the plugin catalog

The Phlix server installs plugins from a **catalog** (`plugins.json`). A catalog
entry pins an exact commit (`ref`) and the sha256 of that commit's tarball
(`artifactSha256`), and the server verifies those bytes before it extracts
anything. Without a pin, `PluginLoader::install()` refuses outright — that is a
deliberate default-deny, not a bug to work around.

**Admin UI** — *Admin → Plugins* (`/app/admin/plugins`), find **Sample Theme**,
press **Install**, then flip its switch to enable it. (The page posts the
catalog entry's `repo` URL to `POST /api/v1/admin/plugins/install`; the server
matches that URL against the catalog and supplies the pin itself.)

**CLI** — the same thing, from the server root:

```bash
php bin/phlix plugin:install https://github.com/detain/phlix-plugin-sample-theme
php bin/phlix plugin:enable  phlix-plugin-sample-theme
php bin/phlix plugin:list
```

Two details that are easy to get wrong:

* **Pass the repository URL, not the plugin name.** `pinFor()` will match a bare
  `phlix-plugin-sample-theme` against the catalog and find the pin, but the
  source string itself is then handed to the installer unchanged, and
  `phlix-plugin-sample-theme` is not something it can download. The **`repo`
  URL** is rewritten to `…/archive/<pinned-ref>.tar.gz`; the name is not.
* **No environment override is needed.** `PHLIX_PLUGINS_ALLOW_UNVERIFIED=1`
  exists for *un-pinned* sources. A catalog entry is pinned, so it is not
  required — if you find yourself reaching for it, the catalog entry is the
  thing that is wrong.

### A local build, for developing your own theme

While you are iterating on a fork there is no catalog entry yet. Build a
tarball and install it over `file://`, which is exempt from the pin requirement
because the bytes are already yours:

```bash
tar --exclude=vendor --exclude=.git -czf /tmp/my-theme.tar.gz phlix-plugin-my-theme/
php bin/phlix plugin:install file:///tmp/my-theme.tar.gz
php bin/phlix plugin:enable  phlix-plugin-my-theme
```

A `.zip` works identically. The archive may have a single top-level directory
(the shape `git archive` and GitHub's codeload produce) — the installer lifts
its contents up automatically.

### What does **not** work: a bare directory

```bash
# WRONG — this has never worked, in any version.
php bin/phlix plugin:install examples/plugins/phlix-plugin-sample-theme
```

```
Unsupported plugin source extension for examples/plugins/phlix-plugin-sample-theme
 — expected .zip, .tar.gz, .tgz, or .json.
```

`SourceUrlResolver::normalize()` passes a plain path through unchanged, and
`HttpInstaller::fetchInto()` dispatches on the extension. A directory has none,
so it falls through to that throw. Pack it first (above). The host *does* have a
`installFromDirectory()` entry point, but no CLI command or admin route is wired
to it — it is used by the integration tests.

## How registration works

There is no manifest `theme` key and no `onEnable()` wiring. The entry class
implements `Phlix\Theming\ThemeSourceInterface`, and `PluginLoader::enable()`
registers it off the `instanceof` — the same one-arm capability pattern the
metadata- and subtitle-source plugins use. **Excerpt, not a template:** the
three `LifecycleInterface` methods are elided here (the theme has nothing to
do in any of them) — paste the complete class from `src/SampleThemePlugin.php`,
never this sketch:

```php
final class SampleThemePlugin implements LifecycleInterface, ThemeSourceInterface
{
    public function themeSourceName(): string { return 'sample-theme'; }

    public function providedThemes(): array
    {
        return [[
            'id'      => 'sample-dusk',
            'name'    => 'Sample Dusk',
            'dark'    => true,
            'extends' => 'midnight',
            'tokens'  => ['--bg' => '#05060a', /* … */],
        ]];
    }
}
```

Disabling the plugin deregisters exactly the ids it contributed. The manifest's
`"type": "ui-theme"` is documentation for humans and for the admin UI's category
label — **the loader never reads it.** A class that implements the interface is
registered whatever its manifest says; a class that does not implement it is not
registered however it is labelled.

## The two rules a theme must obey

1. **Keys** must be on `Phlix\Theming\ThemeTokenAllowlist` — the 53 semantic
   colour tokens of `@phlix/tokens/src/css/colors.css`. Layout tokens (density,
   spacing, radius, shadow, motion) are **not** settable: a theme recolours the
   UI, it never moves it.
2. **Values** must be a hex colour, `rgb()/rgba()/hsl()/hsla()` over numeric
   arguments, a bare number, `transparent`, or `currentColor` — *in full*.
   `var(--x)`, `url(…)`, `;`, `}` and every other CSS construct are rejected by
   having no production in the grammar, not by a blocklist.

   ⚠ **This is the trap.** The built-in themes are written as `var()` chains in
   colors.css, so copying a value straight out of that file gives you
   `var(--amber-500)` — which the validator refuses. **A plugin ships
   literals.** This repo's `testNoTokenValueCarriesACssConstructTheHostGrammarRejects`
   is the guard against re-introducing one.

A payload that breaks either rule fails the whole plugin enable. Registration is
all-or-nothing and never silent.

## Allowlisted ≠ read: why this sample sets 28 tokens, not 53

Setting a custom property nothing reads is a no-op. Thirteen of the 53
allowlisted names are `--color-*` aliases the host's S228 finding named dead,
on a shared census (first measured 2026-08-04 from the shipped SPA;
re-censused 2026-09-05 across every repo's `var()` surface — phlix-server's
`ThemeTokenAllowlist` records it): twelve have **zero `var()` consumers
estate-wide**, and the thirteenth — `--color-primary` — is read by exactly
one surface, the dev-only preview page `phlix-ui/src/dev/swatches.html`,
which ships to no client. The thirteen:

```
--color-primary          --color-surface-hover      --color-error-bg
--color-primary-hover    --color-surface-elevated   --color-success-bg
--color-primary-active   --color-surface-active     --color-warning-bg
--color-text-secondary   --color-border-subtle      --color-info-bg
--color-text-subtle
```

This sample omits all thirteen on purpose, and a test keeps them omitted. The
nine aliases that *are* still read — `--color-bg`, `--color-surface`,
`--color-text`, `--color-text-muted`, `--color-border` and the four status
aliases — are set wherever the corresponding modern token is overridden.

If you fork this plugin, keep that habit: re-hue through the six semantic
`--accent*` tokens and the surface/text/border ramps, and only add an alias you
can point at a real `var()` consumer.

Two of those tokens carry **inverse roles** that are constantly confused —
including by sibling themes in this estate's own cohort.
**`--accent-text`** is the accent hue used *as foreground*: text and icons
painted on `--bg`/`--surface`, so it must clear AA body contrast (4.5:1) there.
**`--text-on-accent`** is the ink used *on accent fills*: button labels and
selected states, read against the accent, not against the page. They are never
equal — making `--accent-text` the on-fill value renders accent-on-accent text
at ≈1:1 (one green-hued theme family shipped exactly that bug as focus text and
initials).

## What the two sample themes demonstrate

| Theme | `extends` | Point |
| --- | --- | --- |
| `sample-dusk` | `midnight` (built-in) | The base is applied by mechanism: the SPA sets `data-theme="midnight"` — whose real values then come from its bundled stylesheet — and layers these tokens over it with `el.style.setProperty()`. The server does know `midnight`'s map and publishes it in the list; the wire just never flattens chains — `tokens` is always the theme's own map. Status colours are intentionally not overridden, so they fall through from the base. |
| `sample-dusk-high-contrast` | `sample-dusk` (plugin) | A plugin→plugin chain: no stylesheet knows the base, so the SPA merges it from data. Only the LIST endpoint (`GET /api/v1/themes`) carries every link, which is why the SPA reads the list and never `/themes/{id}`. |

## Seeing it in the SPA

1. Install **and enable** the plugin — a fresh install lands *disabled*.
2. `GET /api/v1/themes` (authenticated) now lists five themes: the three
   built-ins plus these two, each with `builtIn`, `source` and its own `tokens`
   map.
3. *Settings → Appearance* shows five swatches; picking one applies it live and
   caches the resolved token map so the next page load paints it with no flash.

If the picker still shows three, the plugin is installed but not enabled, or the
worker has not re-attached it — `php bin/phlix plugin:list` tells you which.

## Renaming your fork

Every identifier a cloner must change, in the order a grep finds them. A fork
that renames only the display name is *still* `sample-theme` to the host — and
item 7 makes that variant dangerous, not cosmetic.

1. **composer.json** — `name` (`detain/phlix-plugin-sample-theme`),
   `homepage`, `description`, `keywords`.
2. **plugin.json** — `name`. This is the string `php bin/phlix plugin:enable`
   (and `disable`/`uninstall`) take as its argument; the server's install
   directory is derived from it.
3. **plugin.json** — `entry` FQCN (`Phlix\\PluginSampleTheme\\SampleThemePlugin`).
   It must move together with items 4 and 6 or the loader's
   `class_exists($manifest->entry)` fails at enable.
4. **Namespace `Phlix\PluginSampleTheme`** — in `src/` and `tests/`, *and* the
   string embedded in `scripts/security-audit-check.php` (`@package` and the
   docblock cite of the guard test at :102/:115) — the guard test reads the
   script's own text, so the rename must land in both.
5. **autoload-dev namespace** `Phlix\PluginSampleTheme\Tests` (composer.json).
6. **Class name and files** — `SampleThemePlugin` in
   `src/SampleThemePlugin.php` and `tests/SampleThemePluginTest.php` (PSR-4
   requires class↔filename agreement).
7. **`SOURCE_NAME`** (`src/SampleThemePlugin.php:96`, currently
   `'sample-theme'`) — **the rename that fails silently.** Read the note below
   before you skip it.
8. **Theme ids and display names** — `sample-dusk`,
   `sample-dusk-high-contrast`, `Sample Dusk`, `Sample Dusk (High Contrast)`.
   "Sample" is placeholder copy: shipping it means users pick "Sample Dusk"
   from your server's appearance picker. Ids collide *loudly* (see below); the
   display name is pure branding, so change both. (The admin plugin list's
   title — "Sample Theme" in the walkthrough above — lives in neither: it is
   minted when the catalog curates your repo into phlix-plugins'
   `plugins.json`, so expect to change it there, at curation, not here.)
9. **The S440 guard token** — `S440SAMPTHEMEGATEX6B2` in
   `tests/SecurityAuditCheckTest.php` (constant, equality assertion, and the
   "Lane token:" header line). Every theme repo in the estate was minted a
   unique token when the guard was propagated; a fork mints its own too — it
   identifies *your* lane in CI logs.
10. **Housekeeping** — regenerate `composer.lock` (`composer update`, never
    hand-edit), the `LICENSE` copyright line, this README's title and URLs,
    and anything in `.github/workflows/` that names the repo.

**Why item 7 is the dangerous one.** `ThemeSourceRegistry` keys provenance by
source name, and re-registering a name **replaces** the previous contribution
(drop-then-add, never append — `phlix-server/src/Theming/ThemeSourceRegistry.php:41`).
Two enabled plugins both named `sample-theme` therefore *silently delete each
other's themes* whenever either enables. Duplicate theme **ids** at least fail
loudly: registration throws `"… is already registered by …"` (:121). The quiet
failure mode is exactly the identifier "Fork it, rename it" would never prompt
you to touch, which is why it is spelled out here.

**The fork-inverse of "Keep it constant across versions."** The docblock above
`SOURCE_NAME` tells the *reference* plugin to never change it — that law
applies to a fork **after** its one mandatory rename: change it once, when you
fork, and then keep *that* value constant forever, because users already
installed under it and disable/uninstall remove exactly its ids.

## Developing

```bash
composer install
vendor/bin/phpunit
vendor/bin/phpstan analyse -c phpstan.neon    # level 9
vendor/bin/phpcs --standard=phpcs.xml         # PSR-12
```

`dev-stubs/` holds minimal copies of the two host contracts
(`Phlix\Shared\Plugin\LifecycleInterface`, which ships in `detain/phlix-shared`,
and `Phlix\Theming\ThemeSourceInterface`, which ships in **no** Composer package
at all — it is declared by the server). `tests/bootstrap.php` loads each only
when the real one is absent, and `phpstan.neon` picks them up via
`scanDirectories`. They exist so this repo's CI can run standalone; they are
never used inside a real server, where the host supplies both.

## Licence

MIT, matching the rest of the Phlix plugin/interop surface. (Phlix's
applications are MPL-2.0; plugins, clients and interop libraries are MIT.)
