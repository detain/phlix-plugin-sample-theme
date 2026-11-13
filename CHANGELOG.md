# Changelog — phlix-plugin-sample-theme

Format: [Keep a Changelog](https://keepachangelog.com/en/1.1.0/); versions follow
Semantic Versioning. Both version markers — `plugin.json`'s `version` and
`SampleThemePlugin`'s class docblock `@since` (`composer.json` carries no
version and no prose `@since` marker) — say **1.0.0**, and since 2026-11-02
that matches the shipped artifact: annotated tag `v1.0.0` exists on origin as
tag object `4a94748`, peeling to `8fc6557` (the repo tip at tag time), and the
plugin catalog carries a `1.0.0` release entry for that commit alongside its
`dev` entry. This file exists so the history it records is not just in the git
log; the correction entry below documents the pre-tag status paragraph this
header carried until 2026-11-07 and what it missed.

## [Unreleased]

### Changed
- CI: actions/checkout bumped v6 to v7 (both jobs) and PHP 8.5 added to the phpunit matrix (8.3 floor unchanged). Composer dependencies re-verified at latest compatible (phpunit 12.5, phpstan 2.3.5, phpcs 4.x; `composer update --dry-run` is a no-op); detain/phlix-shared stays on its ^0.54.0 pin. setup-php and codacy action SHAs already at latest releases.
- Dependencies: require-dev upgraded to phpunit/phpunit ^12.5 (12.5.44, was 10.5; phpunit.xml already conforms to the 12 schema) and phpstan/phpstan ^2.3.5; lock refreshed. detain/phlix-shared pin untouched. GitHub Actions already on latest majors. The guard-test message assertions the previous change left stale are now mirrored.
- Audit gate (MAL-17): `scripts/security-audit-check.php` messages no longer print the slash-stem plurals "advisory/ies" / "repository/ies"; they pluralise correctly (1 advisory, 2 advisories). Estate family wave; test mirrors updated in lockstep.

### Fixed — faint-law premise re-anchor, LICENSE attribution, 5.27 drift (2026-11-12)
- The `--text-faint` docblocks (dusk + high-contrast inline comments, and the
  faint law test's docblock and exclusion comment) cited phlix-ui
  `AuthField.vue` as the placeholder consumer; at ui v0.99.27 AuthField paints
  its placeholder in `--text-subtle`. Re-anchored to the live consumer set —
  SyncPlayModal (composited input over the modal's `--surface-2` panel),
  CommandPalette (transparent input on `--surface-2`), Login/Signup divider +
  hint and the Logs update note (page grounds), IndexRail (disabled). The raw
  `--surface-2` exclusion is re-derived without the old "inputs there arrive
  composited" premise, which CommandPalette falsifies: it now stands on the
  shipped value's own arithmetic (0.201203 < 0.220773, 4.17:1) as a stated,
  unpinned gap. No token value moves; every asserted ground still passes.
- `LICENSE` line 3 named "Phlix contributors" while every `@copyright` file
  header names Joe Huss; aligned to the file headers, and the README fork
  checklist now names both as one rename target.
- The majors-era `#75839a` subtle measurement was cited as 5.28 on `--bg` in
  the source comment and the subtle law docblock; it measures 5.27.
  *(review theme-sample SMP-1, SMP-2, SMP-3)*

### Changed — `detain/phlix-shared` re-pin `^0.21` → `^0.54.0` (2026-11-06)
- The estate-wide shared re-pin wave landed here: the constraint the W-min152
  batch parked as an owner-family sweep call (SAMPLE-06) is that sweep, so the
  reference plugin now sits on the same shared line as every other consumer.
  A targeted `composer update detain/phlix-shared` rotated exactly one lock
  node — `0.21.0` → `v0.54.0`, source/dist reference `12994652` → `55f3e680`
  (the tagged v0.54.0 peel); shared's `require`/`require-dev` blocks are
  byte-identical between the two tags, so the 33 locked packages held with
  0 installs and 0 removals. Gates are identical parent-to-tip (phpunit
  49/1569, phpstan, phpcs, whole-lock audit pass): the shared declaration this
  plugin implements, `Phlix\Shared\Plugin\LifecycleInterface`, is
  signature-identical across the jump (the two tags differ in one docblock
  summary line), and `ThemeSourceInterface` is the repo's own `dev-stubs/`
  seam the pin never touches — which is exactly the pin-hygiene point a
  reference repo has to keep honest. *(review theme-sample__combined
  SAMPLE-06; estate re-pin)*

### Fixed — README rename-checklist coordinate + repo-tree completeness (2026-11-06)
- The rename walkthrough cited `SOURCE_NAME` at `src/SampleThemePlugin.php:96`;
  the constant is declared at `:102` on the current tree — W-min151/154-era
  docblock growth pushed it off the cited line, so `:96` is now the summary
  line of the docblock above it. Re-anchored to `:102`: the walkthrough is
  precisely where a forker copies coordinates from. The neighbouring host
  cites (`ThemeSourceRegistry.php:41`, `:121`) were re-verified exact on the
  server's current tree and left untouched. *(review theme-sample__combined m04)*
- The repo tree block gained the tracked `.gitignore` line it was missing —
  the root file the W-min152 batch itself edited (it stopped ignoring the
  committed lock the S440 story requires), annotated so no forker can read
  the hygiene excludes as lock-ignoring. *(review theme-sample__combined i05)*

### Fixed — release-state truth for the v1.0.0 tag (2026-11-07, recording 2026-11-02)
- The status paragraph above asserted three things the 2026-11-02 estate tag
  wave had displaced: that no git tag existed, that the catalog release entry
  pinned the raw initial-release commit `1ea573b`, and that everything since
  rode the dev channel. Measured on origin now: annotated tag `v1.0.0` is tag
  object `4a947485182671a6f80c341d0751ec9fca7b77c3` peeling to
  `8fc6557939348d527b5c7704c95d9c99574968b5`, cut 2026-11-02 per the owner
  tag-wave authorization ("FIRST tag on this repo; plugin.json 1.0.0 at tip;
  S440 guard suite (T-A/T-B/T-C hardened reference family) live"); the catalog
  (`phlix-plugins/plugins.json`) holds a `1.0.0` release entry and a `dev`
  entry for this repo, both on ref `8fc6557` with artifact sha256 `766b92b7…`.
- Consequence for the history below: every batch listed under [Unreleased] —
  W-min151 through W-min154 and the 2026-10-29/30 majors cluster — is inside
  the tagged 1.0.0 artifact. The historical [1.0.0] section dated 2026-08-04
  keeps its verbatim body (its counts re-measure true: 28 base tokens;
  the variant's override grew 8 to 9); its heading names only the initial
  commit behind that first release entry, not the tagged one.
  *(review theme-sample__combined M01)*
- The header's marker enumeration named "the `@since` lines in
  `composer.json` prose": `composer.json` carries no `@since` and no version —
  the repo-wide grep hits only `src/SampleThemePlugin.php:91`. Repaired in the
  paragraph above. *(review theme-sample__combined m03)*

### Fixed — review-campaign minors, theme vein batch W-min154 (2026-10-30)
- `--text-subtle` raised `#75839a` → `#7f8da7`: the shipped value measured
  4.54:1 on `--surface-2` and 4.01:1 on `--surface-3` — body-text ink below
  the 4.5:1 AA floor on the grounds its real roles (card captions, menu
  meta) live on. The review's suggested `#7d8ba4` measures 4.47:1 on
  surface-3, still under, so the destination was re-derived: `#7f8da7`
  measures 6.05/5.72/5.21/4.60 across the four grounds and keeps the text
  luminance ramp strict. Pinned by
  `testTheSubtleInkClearsWcagAaOnEveryGround` (measured red at the old value
  before the fix shipped); the faint-tier window comment the move
  invalidated was re-derived in the same pass. WIRE BODY change: joins the
  open phlix-server re-vendor + `GOLDEN_MD5` rider from W-min151 — one
  re-vendor serves both. *(M-THEME-SAMPLE-11)*

### Changed — review-campaign minors, theme vein batch W-min153 (2026-10-30)
- The dead-alias census is restated to the host's refined record everywhere it
  appears (class docblock, `DEAD_ALIASES` guard docblock, README): twelve of
  the thirteen aliases have zero `var()` consumers estate-wide; the thirteenth,
  `--color-primary`, is read only by the dev-only preview page
  `phlix-ui/src/dev/swatches.html` — which ships to no client, so the omission
  policy (all 13 stay out; guard test unchanged) is untouched. The former
  blanket "13 have zero consumers" and the lone "2026-08-04" date had drifted
  from phlix-server's 2026-09-05 re-census; the two records now share one
  sentence. *(M-THEME-SAMPLE-05, -17)*
- Payload alias-block comment: "The nine dead `--color-*` aliases are omitted"
  → "thirteen" (nine is the count of the LIVE aliases kept, thirteen the dead
  ones omitted). *(found en route under -05)*
- `testNoThemeSetsAnAliasTokenTheSpaDoesNotRead` attribution S226 → S228:
  S226 was the catalog ticket (phlix-docs RESUME record); the server prose
  credits S228 with naming the dead aliases, as this file already cited at its
  header. Forks grep these ids. *(M-THEME-SAMPLE-14)*

### Fixed — review-campaign minors, theme vein batch W-min152 (2026-10-30)
- `.gitignore` stopped ignoring `/composer.lock`. The lock is the artifact the
  S440 whole-lock audit gate reads, so leaving it git-ignored meant the gate
  audited a file the repo did not ship; the committed lock now shows its churn
  instead of hiding it. *(SAMPLE-02)*
- The `scripts/` exclusion in `phpcs.xml` and `phpstan.neon` is documented as
  deliberate law, not oversight: this repo's copy of
  `scripts/security-audit-check.php` is the S440 family identity anchor (the
  byte-template the ten sibling theme repos were ported from), it carries a
  121-character line, and wrapping it or dropping the exclusion is an anchor
  move for all eleven repos at once. The script is not unverified meanwhile —
  `tests/SecurityAuditCheckTest.php` drives its verdict branches as
  subprocesses. *(SAMPLE-01)*
- README repo tree rebuilt true-complete: `scripts/`, the workflow, the
  committed lock, `LICENSE`, both test files and this CHANGELOG listed with
  real per-file lines replacing the glyph shorthand. *(SAMPLE-08, -12)*
- Parked as before, unchanged dispositions: SAMPLE-03 (the `1ea573b` date
  conflation lives only in the two S440 bright-line files — family-anchor
  hold) and SAMPLE-06 (the `detain/phlix-shared` `^0.21` pin — an owner-family
  sweep call, not a sample-only bump). *(recorded for batch completeness; no
  code change)*

### Fixed — review-campaign minors, theme vein batch W-min151 (2026-10-30)
- `--accent-ring` alpha re-derived for this theme's own accent: `0.55` was
  nocturne's amber-derived value transplanted onto a dimmer blue and measures
  2.93:1 on `--surface-3`, under the 3:1 non-text floor. `0.65` measures
  3.98/3.94/3.79/3.53 across the four grounds. Pinned by
  `testTheFocusRingClearsThreeToOneOnEveryGround` (measured red at the old
  value before the fix shipped). *(M-THEME-SAMPLE-09)*
- Default-variant border trade-off documented inline (1.35:1 hairline is a
  mood choice with a focus-ring mitigation, not an oversight; the
  high-contrast variant proves the 3.33:1 path) and the border-luminance
  hierarchy pinned by `testTheBorderTiersFormAStrictLuminanceRamp`, so a
  future single-token "visibility lift" cannot invert the ramp.
  *(M-THEME-SAMPLE-10)*

### Added — review-campaign minors, theme vein batch W-min151 (2026-10-30)
- This CHANGELOG, the repo's fourth teaching surface: a version marker is
  worth nothing unless the history behind it is readable. *(M-THEME-SAMPLE-15)*

### Changed — review-campaign minors, theme vein batch W-min151 (2026-10-30)
- `SampleThemePlugin::subscribedEvents()` return annotation now matches the
  host contract it implements (`array<class-string, string|callable>`, keys
  are event FQCNs) instead of narrowing to `string` — a forker adding a
  closure subscription copies a truth. *(M-THEME-SAMPLE-13)*
- README prose: the `ui-theme` showcase fence is labelled an excerpt (it is
  not paste-safe — the three `LifecycleInterface` methods are elided); the
  fork-rename list records that the admin-visible plugin title is minted at
  catalog curation, not in the repo. *(M-THEME-SAMPLE-16, -18)*

### Fixed — review-campaign majors (2026-10-29/30, `b756fe0..0daf018`)
- The two-theme rationale now tells the truth about server knowledge (built-in
  token maps ship in the LIST response; the wire just never flattens).
- `--text-faint` raised to AA on its real render grounds and carried into the
  high-contrast variant.
- Added the "Renaming your fork" checklist, including the silent-replace
  `SOURCE_NAME` hazard, and the `--accent-text` vs `--text-on-accent`
  inverse-role gloss; corrected the surface-2 carve-out arithmetic to
  measured truth.

## [1.0.0] — 2026-08-04 (`1ea573b`)

### Added
- Initial release: `SampleThemePlugin` — reference `ui-theme` plugin
  registering `sample-dusk` (28 tokens; the high-contrast variant has grown
  its `--text-faint` override since, now 9) and `sample-dusk-high-contrast`
  through `ThemeSourceInterface`, with the dead-alias omission law
  (`13` zero-consumer tokens left unset) pinned by tests.

## Post-1.0.0 history before this CHANGELOG existed
- `0d8769a` (2026-09-05) — S440: committed `composer.lock`,
  `scripts/security-audit-check.php` (whole-lock audit gate) and its guard
  suite; CI runs the gate as the last job, uncapturable.
- `856900e` (2026-10-07) — C2-review guard hardening (T-A run-line equality,
  T-B `AUDIT_ARGUMENTS` spawn pin); the hardened template was then propagated
  byte-identical to the ten sibling theme repos.
- `ba81333` (2026-10-08) — CI pinned to `ubuntu-26.04` ahead of the
  `ubuntu-latest` rollover.
- `9277412` (2026-10-20) — dependency security update (lock-only).
