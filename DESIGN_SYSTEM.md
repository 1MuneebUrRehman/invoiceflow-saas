# InvoiceFlow Design System

> Paste this into any project to reproduce the "InvoiceFlow" look & feel.
> Stack: Tailwind CSS v4 (CSS-first `@theme`, no `tailwind.config.js`).
> Keep the tokens, ratios, and recipes identical even if you change frameworks.

## 1. Design Philosophy

Calm, trustworthy, "fintech that gets you paid." Deep navy ink on cool off-white
surfaces, one restrained blue for all actions, generous whitespace, soft layered
shadows, rounded-but-not-playful corners, and a refined serif for display headings
paired with a clean grotesque for everything else. Color is used sparingly and always
carries meaning (status), never decoration.

## 2. Typography

| Role | Token | Stack |
|------|-------|-------|
| Body / UI | `--font-sans` | `"Public Sans", ui-sans-serif, system-ui, sans-serif` |
| Display / headings | `--font-display` | `"Fraunces", ui-serif, Georgia, serif` |

- Headings: `font-display font-semibold tracking-tight` (page ~`text-3xl`, section ~`text-lg`/`text-xl`).
- Body: `font-sans`, antialiased.
- Money/numbers: always `font-variant-numeric: tabular-nums` so figures align in columns.

## 3. Color Tokens

```css
@theme {
  --color-midnight:   #13283f; /* primary ink / text / headings */
  --color-lapis:      #1f5eae; /* primary action / links / brand blue */
  --color-lapis-deep: #17498a; /* hover/pressed state of lapis */
  --color-mist:       #f4f6fa; /* app background / subtle fills */
  --color-slate:      #5b6b7f; /* secondary / muted text */
  --color-verdant:    #2e7d5b; /* success / paid / positive */
  --color-garnet:     #b42318; /* danger / overdue / error */
}
```

Cards/panels use plain `white`. `white` is preserved in dark mode so buttons keep `text-white`.

## 4. Shape & Elevation

```css
@theme {
  --radius-field: 8px;  /* inputs, buttons, nav pills, chips */
  --radius-card:  14px; /* cards, panels, tables */
  --radius-modal: 20px; /* large modal surfaces */

  --shadow-card:       0 1px 2px rgb(19 40 63 / .05), 0 4px 16px rgb(19 40 63 / .05);
  --shadow-card-hover: 0 2px 4px rgb(19 40 63 / .06), 0 12px 32px rgb(19 40 63 / .1);
  --shadow-pop:        0 1px 2px rgb(19 40 63 / .05), 0 16px 48px -8px rgb(19 40 63 / .18);
}
```

Shadows are tinted with the midnight ink (not pure black) in light mode. Cards pair a
soft shadow with a hairline ring `ring-1 ring-midnight/5` (use `/10` when interactive)
and lift to `shadow-card-hover` on hover.

## 5. Dark Mode (CSS-variable override technique)

Toggle a `.dark` class on `<html>` and override the **same** token variables — don't
rewrite utilities.

```css
@custom-variant dark (&:where(.dark, .dark *));

.dark {
  color-scheme: dark;
  --color-midnight:   #c8d8ed;
  --color-mist:       #091526;
  --color-slate:      #8ba3c0;
  --color-lapis:      #4a90d6;
  --color-lapis-deep: #3a7bc4;
  --shadow-card:       0 1px 3px rgb(0 0 0 / .35), 0 4px 16px rgb(0 0 0 / .25);
  --shadow-card-hover: 0 2px 4px rgb(0 0 0 / .45), 0 12px 32px rgb(0 0 0 / .35);
  --shadow-pop:        0 1px 2px rgb(0 0 0 / .45), 0 16px 48px -8px rgb(0 0 0 / .55);
}

/* Card/panel fills must beat bg-white, so target by shadow class: */
.dark .shadow-card,
.dark .shadow-pop { background-color: #0f2035; }
```

**Surface map (dark):** cards `#0f2035` · app shell `#091526` · sticky header translucent
`#0b1a2e/90` + `backdrop-blur` · inputs `#0b1c2e` · borders switch `midnight/5` → `white/8`.

Prevent flash-of-wrong-theme with an inline `<head>` script that reads
`localStorage.theme` (`light` | `dark` | `system`) and adds `.dark` before first paint.
Provide a 3-way segmented toggle pill: `rounded-full bg-midnight/5 p-0.5`, active segment
`bg-white shadow-sm`.

## 6. Status Color Contract

Map statuses to tokens **one way** and reuse for badges, dots, charts, icons:

| Meaning | Token |
|---------|-------|
| neutral / draft / cancelled | `slate` |
| in-progress / sent / info | `lapis` |
| success / paid / positive | `verdant` |
| danger / overdue / error | `garnet` |

**Badge:** `inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium`
with a 10%-tint fill + solid text — e.g. `bg-verdant/10 text-verdant`,
`bg-garnet/10 text-garnet`, `bg-slate/10 text-slate`.

## 7. Component Recipes

- **Primary button:** `inline-flex items-center justify-center gap-2 rounded-field bg-lapis px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-lapis-deep focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-lapis disabled:opacity-60 disabled:cursor-not-allowed`
- **Secondary button:** white fill, `text-midnight`, `shadow-card ring-1 ring-midnight/10`, hover `shadow-card-hover ring-midnight/20`
- **Input:** `rounded-field border border-slate/25 bg-white px-3 py-2.5 text-sm text-midnight placeholder:text-slate/60 focus:border-lapis focus:ring-2 focus:ring-lapis/25 focus:outline-none` (dark: `bg-[#0b1c2e] border-white/12`)
- **Label:** `block text-sm font-medium text-midnight` · **Error:** `mt-1.5 text-sm text-garnet`
- **Stat card:** `rounded-card bg-white p-6 shadow-card ring-1 ring-midnight/5 transition hover:shadow-card-hover`; value `text-2xl font-semibold tracking-tight text-midnight`; label `text-sm font-medium text-slate`; `size-9` rounded-field icon chip tinted with the status token
- **Table:** white card wrapper, `border-b border-midnight/6` head with uppercase `text-xs font-semibold tracking-wide text-slate`, `divide-y divide-midnight/5` rows, row hover `hover:bg-mist/60`, link cells hover to `text-lapis`
- **Empty state:** dashed `border border-dashed border-slate/25` card, centered tinted icon circle, display-serif heading, slate sub-copy, primary CTA
- **Toast:** fixed bottom-right `rounded-card bg-white px-4 py-3 shadow-pop ring-1 ring-midnight/10`, leading status dot, slide-up fade
- **Modal:** `bg-midnight/40 backdrop-blur-sm` scrim, panel `rounded-card bg-white p-6 shadow-pop ring-1 ring-midnight/8`, leading status-tinted icon circle, scale+fade transitions
- **Sticky header:** `sticky top-0 z-40 border-b border-midnight/5 bg-white/88 backdrop-blur-md`; active link `bg-lapis/8 text-midnight`, inactive `text-slate hover:bg-mist hover:text-midnight`; avatar chip `bg-lapis/10 text-lapis` with initials
- **Logo mark:** `rounded-[10px] bg-gradient-to-br from-lapis to-lapis-deep text-white shadow-sm` square holding a line icon

## 8. Signature Flourishes

- **Ambient blur blobs:** large `rounded-full blur-3xl` shapes at low opacity behind hero/auth/dashboard content — `bg-lapis/10` and `bg-verdant/10` (≈ `/6` in dark), `pointer-events-none`, `-z-10`. The system's hero signature.
- **Mini bar chart:** bars `bg-lapis/25` (hover `bg-lapis/40`), empty `bg-slate/8`, dark midnight tooltip on hover.
- **Focus rings:** always `focus-visible:outline-2 outline-offset-2 outline-lapis`.
- **Transitions:** short & soft (~150–200ms).

## 9. Rules

1. Use only these tokens; no raw hex except the listed dark-mode surfaces.
2. Color = meaning (status), never decoration.
3. Every interactive element has hover + focus-visible states.
4. Headings serif (`font-display`), body sans (`font-sans`).
5. Cards = soft shadow + hairline ring + generous padding.
6. Support light/dark/system from day one via the variable-override technique.
7. Keep it calm: lots of whitespace, one accent blue, status colors only where they inform.

## Setup Checklist

- [ ] Install fonts: **Public Sans** (body) + **Fraunces** (display)
- [ ] Add the `@theme`, `@custom-variant dark`, and `.dark` overrides to your CSS entry
- [ ] Add the inline no-flash theme script to `<head>`
- [ ] Add the 3-way light/system/dark toggle
