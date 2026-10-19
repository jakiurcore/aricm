## Hard Candy — build with the components, not from scratch

Bigloop's design system. Pastel fills on a brutal skeleton: 2px outlines, hard offset
shadows, zero radius, Space Mono for the machine and Karla for prose.

**31 real components ship here.** Import them — do not rebuild a button out of divs and
tokens. Tokens are for your own layout glue (grid, gap, spacing, a one-off surface),
not for re-implementing something in `components/`.

## Setup — two lines, and there is no provider

```jsx
import "@bigloop/hardcandy/styles.css";
import { HardCandyRoot, Panel, Button, StatusPill } from "@bigloop/hardcandy";

<HardCandyRoot>…</HardCandyRoot>
```

`HardCandyRoot` installs no React context — theming is CSS custom properties and
nothing else. It applies the ground, the text colour and the UI face to a subtree, and
you can skip it entirely by putting `class="hc-root"` on your own root element.

Themes resolve in three states. Stamp `data-theme="dark"` or `data-theme="light"` on
the root element, or leave it off and the OS decides. The stamp works on **any**
element, so a subtree can differ from the document — that is what `HardCandyRoot`'s
`theme` prop does.

## The one rule that matters

**The candy fills are identical in both themes and always carry a black label. What
flips is the outline.** Light has black outlines on lilac haze; dark has near-white
outlines on `#16141C`. Every candy hue is light, so black type sits on it at 11.9:1 or
better, which is why no fill token needs a second value.

Two things this forbids:

- **Never white on a candy fill.** White on bubblegum is 1.68:1.
- **Never a candy hue as text.** The fills are fills. Text is `var(--hc-text)`, or the
  black label the components already put on a fill.

## What to reach for

| Need | Component |
|---|---|
| Actions | `Button` (primary/secondary/ghost/danger × sm/md/lg, `loading`, `icon`), `IconButton`, `SegmentedControl` |
| Forms | `Field` (owns the label, hint and error), `TextInput`, `TextArea`, `Select`, `Checkbox`, `Switch` |
| Status | `StatusPill`, `SeverityStrip`, `GateMeter`, `ProgressBar`, `Spinner`, `CountBadge` |
| Surfaces | `Panel` (header/body/footer, `flat`, `bleed`), `Card`, `Modal`, `Toast`, `EmptyState` |
| Data | `DataTable`, `StepList`, `Diff`, `CodeBlock`, `InlineCode` |
| Chrome | `NavBar`, `Tabs` |
| Type & brand | `Heading`, `Text`, `Mark` |

Always put a form control inside `Field` — it owns the label association and the error
slot, so the controls stay presentational. Status always goes through `StatusPill` or
`SeverityStrip` rather than a coloured `Text`, because those two carry a **glyph shape
and a position** as well as a hue, which is what keeps the set readable in greyscale.

## Type: two roles, and the choice matters

`Text` defaults to Space Mono. Anything read at paragraph length takes
`variant="prose"`, which switches to Karla and caps the measure at 66ch — a monospace
at that length is a genuine reading cost, and getting this wrong is the fastest way to
make the system tiring. Use `variant="data"` for every column of digits; it applies
`tabular-nums`.

## Tokens — for your layout, not for re-skinning components

| Family | Tokens |
|---|---|
| Ground | `--hc-bg-base` `--hc-bg-raised` `--hc-bg-inset` `--hc-bg-sunken` |
| Text | `--hc-text` `--hc-text-muted` `--hc-text-faint` `--hc-text-on-fill` |
| Structure | `--hc-outline` `--hc-outline-soft` `--hc-border` `--hc-border-hair` `--hc-radius` |
| Status fills | `--hc-accent` `--hc-pass` `--hc-hold` `--hc-fail` `--hc-info` `--hc-queued` |
| Candy | `--hc-bubblegum` `--hc-mint` `--hc-butter` `--hc-coral` `--hc-sky` `--hc-lilac` |
| Elevation | `--hc-shadow-1` `--hc-shadow-2` `--hc-shadow-3` (hard offsets, never blurred) |
| Space | `--hc-space-1|2|3|4|5|6|8|10|14|20` = N×4px |
| Type | `--hc-font-display` `--hc-font-ui` `--hc-font-prose` `--hc-font-data` |
| Motion | `--hc-dur-fast|base|slow` (90/140/200ms) with `--hc-ease` |

`--hc-haze-*`, and the six `*-deep` pressed variants, are **primitives** — the source
the semantic tokens draw from. Never reference them directly.

Keep the accent scarce: on a run panel exactly one thing should wear `--hc-accent`,
whatever is moving right now.

## Where the truth lives

- `guidelines/docs/using-hard-candy.md` — setup, the accent rule, density, the type choice
- `guidelines/docs/qa-checklist.md` — the testable gates and the measured contrast audit
- `components/<group>/<Name>/<Name>.prompt.md` — per-component usage and examples
- `components/<group>/<Name>/<Name>.d.ts` — the real prop contract
- `_ds_bundle.css` — the whole system, tokens included, with the audit in a comment

## An idiomatic run panel

```jsx
<Panel
  title="Plan"
  actions={<StatusPill status="running">Step 03</StatusPill>}
  footer={
    <>
      <Button variant="ghost" size="sm">Open diff</Button>
      <Button variant="primary" size="sm">Hold run</Button>
    </>
  }
  bleed
>
  <div style={{ padding: "var(--hc-space-4)", display: "grid", gap: "var(--hc-space-4)" }}>
    <StepList
      steps={[
        { n: "01", label: "Read existing session store", state: "done" },
        { n: "02", label: "Draft rotation contract", state: "done" },
        { n: "03", label: "Implement refresh path", state: "running" },
        { n: "04", label: "Backfill migration", state: "queued" },
      ]}
    />
    <div style={{ display: "grid", gap: "var(--hc-space-2)" }}>
      <Text variant="micro">Spec conformance</Text>
      <GateMeter label="2 of 3 checks pass" checks={["pass", "pass", "hold"]} />
    </div>
  </div>
</Panel>
```

Note what the tokens are doing there: padding and gap only. Every control is a
component.

# HardCandy (@bigloop/hardcandy@0.1.0)

This design system is the published @bigloop/hardcandy React library, bundled as a single
browser global. All 31 components are the real upstream code.

## Where things are

- `_ds_bundle.js` — the whole-DS bundle at the project root; loads every component to `window.HardCandy`. First line is a `/* @ds-bundle: … */` metadata header.
- `styles.css` — the single stylesheet entry: it `@import`s the tokens, fonts, and component styles (`_ds_bundle.css`). Link this one file.
- `components/<group>/<Name>/<Name>.prompt.md` (example JSX + variants), `<Name>.d.ts` (types), `<Name>.html` (variant grid).
- `tokens/*.css` — CSS custom properties, names verbatim from upstream.
- `fonts/` — `@font-face` files + `fonts.css` (when the package ships fonts).
- `guidelines/` — the design system's own usage guidance (2 doc(s), see `guidelines/index.md`). Read these before composing larger layouts.

For a specific component, `read_file("components/<group>/<Name>/<Name>.prompt.md")`.

## Loading

Add these two lines to your page once (React must be on the page first):

```html
<link rel="stylesheet" href="styles.css">
<script src="_ds_bundle.js"></script>
```

Components are then available at `window.HardCandy.*`. Mount into a dedicated child node (e.g. `<div id="ds-root">`), not the host page's own React root, so the two trees don't collide:

```jsx
const { Button } = window.HardCandy;
ReactDOM.createRoot(document.getElementById('ds-root')).render(<Button />);
```

Wrap the tree in the provider — most components read theme/i18n from context:

```jsx
<HardCandyRoot>{children}</HardCandyRoot>
```

## Tokens

96 CSS custom properties from @bigloop/hardcandy. Names are
preserved verbatim from upstream. They are declared inside `_ds_bundle.css` (this DS ships one compiled stylesheet rather than separate token files).

- **color** (18): `--hc-text-display-xl`, `--hc-text-display-l`, `--hc-text-display-m`, …
- **spacing** (10): `--hc-space-1`, `--hc-space-2`, `--hc-space-3`, …
- **typography** (4): `--hc-font-display`, `--hc-font-ui`, `--hc-font-prose`, …
- **radius** (1): `--hc-radius`
- **shadow** (4): `--hc-shadow-1`, `--hc-shadow-2`, `--hc-shadow-3`, …
- **other** (59): `--hc-bubblegum`, `--hc-mint`, `--hc-butter`, …

## Components

### actions
- `Button` — The system's primary control. Outlined in 2px, lifted on a hard shadow, and it
- `IconButton` — A square 34px control carrying one glyph. Always takes an accessible name.
- `SegmentedControl` — A row of mutually exclusive options in one outlined shell. Use it for two to four

### surfaces
- `Card` — A lighter surface than Panel: one shadow level down, no header or footer
- `EmptyState` — What a surface shows before it has anything. The dashed outline is what tells
- `HardCandyRoot` — Applies the system's ground, text colour and UI face to a subtree.
- `Modal` — A centred dialog over a flat scrim. The header takes the accent fill, which
- `Panel` — The workhorse surface: an outlined box on a hard shadow, with an optional
- `Toast` — A transient notice. Carries a severity strip down its left edge so urgency

### forms
- `Checkbox` — A drawn checkbox  the native control cannot take a 2px outline and a hard
- `Field` — Label, control, and one line of help beneath it. The wrapper every form control
- `Select` — A native select with the platform chevron replaced, so the control matches the
- `Switch` — An on/off control for a setting that takes effect immediately. Use a Checkbox
- `TextArea` — A multi-line text control. Vertically resizable 96px minimum so it never
- `TextInput` — A single-line text control. Its shadow is inset, so it reads as a well

### data
- `CodeBlock` — Preformatted code that scrolls horizontally rather than wrapping, because a
- `DataTable` — A table that scrolls inside its own container, so a wide one never makes the
- `Diff` — A unified diff. Added lines take the mint fill, removed lines the coral, both
- `InlineCode` — A short code span or requirement tag inside running text. Sized in em so it
- `StepList` — The plan: a fixed four-column grid of number, glyph, label and state, so every

### status
- `CountBadge` — A small tabular count, for tab and nav labels. Tabular figures so a count
- `GateMeter` — One segment per check rather than a percentage bar, because 2 of 3 checks pass
- `ProgressBar` — A determinate or indeterminate bar. The fill carries its own 2px edge so it
- `SeverityStrip` — A 6px bar of status colour down the edge of a row, card or toast. The
- `Spinner` — A stepped ring. It advances in eight discrete frames rather than rotating
- `StatusPill` — The status of one thing, as a filled chip. Pass, hold, fail, running, queued

### typography
- `Heading` — Display type, set in Space Mono at 700 with tight negative tracking.
- `Text` — Body copy and labels. The important choice here is prose versus the rest:

### brand
- `Mark` — The Bigloop mark: a ring, because the loop is the product. Drawn as a filled

### navigation
- `NavBar` — The application chrome: mark, wordmark, primary navigation, actions. One 2px
- `Tabs` — Top-level view switching. The active tab is drawn as an actual tab: outlined on
