# phlix-plugin-sample-theme

A complete, installable **`ui-theme`** plugin — the reference for shipping a
theme to the Phlix SPA. Fork it, rename it, change the token values.

```
phlix-plugin-sample-theme/
├── composer.json              # PSR-4 autoload for the entry class
├── plugin.json                # manifest: type "ui-theme", entry FQCN
├── src/SampleThemePlugin.php  # LifecycleInterface + ThemeSourceInterface
├── dev-stubs/                 # host-only contracts, for THIS repo's CI only
├── tests/                     # the plugin's own suite
└── phpunit.xml · phpstan.neon · phpcs.xml
```

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
metadata- and subtitle-source plugins use:

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
allowlisted names are `--color-*` aliases left over from a migration that has
since finished, and they have **zero `var()` consumers in the shipped SPA**:

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

## What the two sample themes demonstrate

| Theme | `extends` | Point |
| --- | --- | --- |
| `sample-dusk` | `midnight` (built-in) | The base's real values live in the SPA's stylesheet, not on the server. The SPA sets `data-theme="midnight"` and layers these tokens over it with `el.style.setProperty()`. Status colours are intentionally not overridden, so they fall through from the base. |
| `sample-dusk-high-contrast` | `sample-dusk` (plugin) | A plugin→plugin chain. Only the LIST endpoint (`GET /api/v1/themes`) carries every link, which is why the SPA reads the list and never `/themes/{id}`. |

## Seeing it in the SPA

1. Install **and enable** the plugin — a fresh install lands *disabled*.
2. `GET /api/v1/themes` (authenticated) now lists five themes: the three
   built-ins plus these two, each with `builtIn`, `source` and its own `tokens`
   map.
3. *Settings → Appearance* shows five swatches; picking one applies it live and
   caches the resolved token map so the next page load paints it with no flash.

If the picker still shows three, the plugin is installed but not enabled, or the
worker has not re-attached it — `php bin/phlix plugin:list` tells you which.

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
