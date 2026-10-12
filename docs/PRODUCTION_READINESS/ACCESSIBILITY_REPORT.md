# Accessibility report — SupportFlow Cloud (WCAG 2.2 AA target)

Method: code-level audit of public pages, auth, portal and core staff flows +
in-suite render coverage + automated structural suite (`AccessibilityTest`: lang,
title, h1, main landmark, skip link, associated labels, img alt, control names,
table headers across 19 pages — green). No axe/pa11y run and no full
assistive-technology pass has been performed — full compliance is NOT claimed.

## Implemented

- Skip-to-content links + `id="main-content"` on app and portal layouts.
- Visible `:focus-visible` styling (global stylesheet).
- Every form control has a text label; validation errors announced via
  `role="alert"` regions; success/error toasts use `role="status"`/`alert`.
- Tables use `<th scope>`; nav uses `aria-label`; decorative emoji are
  presentation-adjacent to text (no information by colour/emoji alone — status
  badges pair colour with text).
- `prefers-reduced-motion` respected; theme toggle is a real `<button>` with
  `aria-label`; touch targets ≥ 40px on primary actions.
- Zoom/reflow: Tailwind responsive layouts, no fixed-pixel containers on core flows.
- Auth/portal input modes (`inputmode="numeric"` on TOTP), autocomplete left to
  browser defaults, password managers unaffected.
- Skip links + `main` landmarks on marketing and guest layouts (added after the
  automated suite flagged them); all search/filter/bulk/file controls labelled.

## Exceptions / follow-up (P1 before GA)

1. Manual screen-reader pass (NVDA + VoiceOver) over Journeys A–C, I — not done.
2. Modal/focus-trap audit — the app uses inline forms, no custom dialogs; confirm
   on any added dialog component.
3. Colour-contrast spot check with a meter on badge palette — code review only.
4. Full Spanish (and any RTL) layout review when translation expands.
