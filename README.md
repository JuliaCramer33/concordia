## Concordia — WordPress Full Site Editing Rebuild

A modern WordPress rebuild built with Full Site Editing (FSE) and a block‑first architecture. This codebase delivers a production‑ready, brand‑aligned site with a full design system, custom content behaviors, analytics/optimization integrations, and a professional front‑end and build pipeline.

---

## What’s in This Stack

- **Concordia theme** — Custom block (FSE) theme with `theme.json` design tokens, fluid typography/spacing, SCSS pipeline, Webpack JS bundle, and strong editor parity.
- **Theme integrations** — The Events Calendar (TEC) FSE routing/templates, Search & Filter styling, Gravity Forms (GF) foundation overrides.
- **Custom blocks & patterns** — Accordion (core Details), Video Overlay (YouTube lazy), Hero variants, Spotlights.
- **UX & SEO** — Yoast SEO, Redirection, Search & Filter, Two‑Factor, Query Monitor (typical setup).
- **Design system** — Brand palette and tokens, fluid type/spacing scales, custom block styles (buttons, underline hovers), utilities (z‑index, height).

---

## Tech Stack

| Layer | Technologies |
|---|---|
| **CMS** | WordPress 6.5+ (FSE) |
| **Theme** | Block theme, `theme.json` v3, PHP 7.4+ |
| **Styles** | SCSS, Sass, PostCSS (Autoprefixer), Stylelint |
| **Scripts** | Vanilla JS, Webpack 5, ESLint, Prettier |
| **Local** | Local WP (recommended) |

---

## Prerequisites

- Node.js ≥ 18  
- PHP ≥ 7.4  
- Local WP (or an equivalent local WordPress stack)

---

## Spinning Up the Site

### 1) Local WordPress (Local WP recommended)
- Add/open the site in Local so the document root is `app/public`.
- Ensure DB creds match your Local environment.
- Start the site and complete WP install (or point at your existing DB).

### 2) Theme: install dependencies and build

```bash
cd app/public/wp-content/themes/concordia
npm install
npm run build
```

For watch (live SCSS/JS builds with linting):

```bash
npm run dev
```

---

## Project Structure (high level)

```
wp-content/
  themes/concordia/
    assets/
      scss/              # SCSS source (foundation, navigation, components, templates)
      js/                # JS source (utils, video overlay, editor)
    dist/                # Built CSS/JS
    inc/                 # Theme PHP (integrations, setup, enqueue, etc.)
    parts/               # Header/footer
    patterns/            # Block patterns (accordion, video-overlay, heroes, spotlights)
    templates/           # FSE templates (page, single, single-tribe_events, etc.)
    theme.json           # Design tokens and global styles
    functions.php        # Loads inc/*
```

---

## Key Theme Features and Customizations

### FSE templates and layout
- `templates/single-tribe_events.html` — FSE page shell for TEC single events (header/footer, widths) so events follow the page layout.
- Templates use `alignfull` groups with constrained content where appropriate; strong editor parity.

### The Events Calendar (TEC)
- FSE routing enabled so WP’s template hierarchy is used:
  ```php
  add_filter( 'tribe_events_views_v2_use_wp_template_hierarchy', '__return_true' );
  ```
- Avoids classic TEC wrappers (e.g., `#tribe-events-pg-template`) so the block theme controls layout.
- Optional PHP override for the block single event wrapper at `tribe/events/single-event-blocks.php` (kept minimal when FSE routing is active).

### Navigation polish (on‑page “section” nav)
- Scoped styles using the same animated underline pattern as header nav, in white:
  - Tight wrap spacing (column/row gap).
  - No default underline; uses `::before` sweep underline on hover/focus/current.
  - Applied by adding a class to that Navigation block or via targeted aria‑label selectors.

### Video Overlay pattern (YouTube lazy)
- Pattern: `patterns/video-overlay/pattern.php`  
- JS: `assets/js/src/video-overlay.js`
- Highlights:
  - Proper aspect reservation (`aspect-ratio`) to prevent CLS.
  - Play button hit area and hover behavior tuned to avoid edge clipping.
  - Ready for `youtube-nocookie`, `loading="lazy"`, `referrerPolicy`, and focus handoff.

### Skip links and in‑page anchors
- JS: `assets/js/src/utils/skip-links.js`
- Enhancements:
  - On skip/anchor navigation, auto‑open any ancestor core Details/Accordion (`<details>`) before scrolling/focusing.
  - Handles when the target is a `<details>` or `<summary>` (opens first, scrolls to summary for better focus).
  - Respects `prefers-reduced-motion`; uses CSS `scroll-padding-top` for sticky header offsets.

### Forms (Gravity Forms + Search & Filter)
- Forms baseline styles centralized using theme tokens; duplicate rules reduced.
- GF Foundation vertical gap override (global):
  ```css
  .gform-theme--foundation { --gf-form-gap-y: 20px !important; }
  .gform-theme--foundation .gform_fields {
    grid-row-gap: var(--gf-form-gap-y, 20px) !important;
    row-gap: var(--gf-form-gap-y, 20px) !important;
  }
  ```
- GF buttons normalized with a “sweep” hover overlay honoring GF local color tokens.
- Search & Filter submit styled with the same sweep behavior and token‑based colors.

### Utilities and tokens
- Z‑index utilities (responsive): `.z-10`, `.z-tablet-10`, `.z-desktop-10`, etc.
- Height utilities (responsive): `.h-0`, `.h-auto`, `.h-desktop-0`, `.h-desktop-auto`, etc.
- Global accessible focus rings and visually‑hidden helpers.
- `theme.json` tokens for brand colors, typography, spacing.

---

## Development Commands (theme)

| Command | Purpose |
|---|---|
| `npm run build` | Production build: Sass → CSS (Autoprefixer), Webpack JS |
| `npm run dev` | Watch Sass/JS with Stylelint/ESLint |
| `npm run lint:js` | ESLint |
| `npm run stylelint:check` | Stylelint |

---

## Git/SSH Setup (multi‑account)

Use SSH host aliases in `~/.ssh/config`:

```
Host github-kanahoma
  HostName github.com
  User git
  IdentityFile ~/.ssh/id_rsa
  IdentitiesOnly yes

Host github-personal
  HostName github.com
  User git
  IdentityFile ~/.ssh/id_ed25519_personal
  IdentitiesOnly yes
```

- Work remote: `git@github-kanahoma:OWNER/REPO.git`  
- Personal remote: `git@github-personal:USERNAME/REPO.git`  
- Test: `ssh -T git@github-kanahoma` / `ssh -T git@github-personal`

---

## Troubleshooting

- **Events page constrained or missing footer**  
  Ensure `single-tribe_events.html` exists and the FSE routing filter is active. Clear caches and hard refresh; confirm the FSE “Single (Event)” template is applied in the Editor.

- **Anchor links into accordions don’t open**  
  Rebuild JS; confirm `skip-links.js` is bundled and loaded.

- **Video overlay hover icon clipping**  
  Rebuild CSS; play button container and pseudo‑element sizes prevent clipping.

- **Gravity Forms spacing too large**  
  Confirm Foundation class is present; the global `--gf-form-gap-y` override reduces row spacing site‑wide.

---

This README gives outside developers the high‑signal overview to spin up, understand the architecture, and work effectively in the codebase.

