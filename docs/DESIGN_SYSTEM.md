# Design system — Precision Digital Operations (v2)

Single source of truth: `public/css/precision.css` + Blade components in
`resources/views/components/` + layouts in `resources/views/layouts/`.

## Tokens

Midnight navy foundation (`--pf-navy-950…700`), ice-cyan accent
(`--pf-ice-300…600`), indigo highlights, off-white surfaces, hairline borders.
Typography: Space Grotesk (display), Inter (body), IBM Plex Mono (instrument
labels/eyebrows). Radii 6/10/16px, two elevation levels, no glassmorphism.

## Components

`x-btn` (primary/ghost/quiet/danger/bright), `x-card` (flat, hairline, gradient
title tick), `x-badge` (dot + label, never emoji), `x-field` (label wraps the
control for implicit association), `x-icon` (32-stroke SVG set), tables
(`pf-table`, responsive card transformation under 720px), stat blocks, section
heads, rails, mini-UI previews, org tabs, alerts, empty states, wordmark.

## Migration bridge

Views not yet rewritten keep working Tailwind utilities; `precision.css`
contains a scoped, documented bridge (tables, legacy blue links) at
specificity one level above utilities, no `!important`. When a view is
rewritten, replace control classes with `pf-input` / `pf-select` / `pf-textarea`.

## 3D hero

`public/js/service-engine.js` + Three.js 0.160 ES module via import map,
lazy-loaded on intersection. Guarantees: DPR cap 1.75, pause off-screen and on
tab hide, single static frame under `prefers-reduced-motion`, full GPU disposal
on pagehide, static SVG fallback (`marketing/partials/engine-fallback`) on WebGL
failure or after a 9s timeout, no-JS fallback visible by default.

## Themes

`dark` class on `<html>` (persisted `sf-theme`, OS default). Every component
specifies both themes; the guest card forces dark context for contrast.
