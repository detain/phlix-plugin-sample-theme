# Changelog — phlix-plugin-sample-theme

Format: [Keep a Changelog](https://keepachangelog.com/en/1.1.0/); versions follow
Semantic Versioning. Version markers (`plugin.json`, the `@since` lines in
`composer.json` prose and the class docblock) all still say **1.0.0**, and that
is honest: no git tag exists yet, the plugin catalog's release entry pins the
raw initial-release commit `1ea573b`, and everything since rides the dev
channel. Minting the first tag (and the catalog re-pin that follows) is an
owner/closeout decision — this file exists so the history it records is not
just in the git log.

## [Unreleased]

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
