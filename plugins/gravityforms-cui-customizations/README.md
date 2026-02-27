# GravityForms CUI Customizations

Custom plugin that provides Gravity Forms integrations for the Concordia Irvine WordPress site.

## Primary purpose

- Dynamically populate Gravity Forms Dropdowns with Programs (the `program` CPT) so form fields stay in sync with site content.
  - Additionally: a dependent Degree Level dropdown is supported — select a degree level to filter the Programs dropdown in the same form.

- Added server-side universal RFI prefill for Gravity Forms when rendered on a Program single page.
  - Fields can be prefilled by adding CSS classes (`populate-program-title`, `populate-degree-level`) to single-line text or hidden fields.
  - Alternatively, enable GF dynamic population and use parameter names `program_title` and `degree_level`.
  - Implemented in `includes/class-gfcui-program-rfi.php`.

## Caching note

- Program query results are cached server-side using WordPress transients (default TTL 24 hours). Transients are automatically cleared when a Program is created, updated, trashed, or deleted.

## Building assets

This plugin uses webpack to build frontend assets (JS + SCSS). To build locally:

```bash
cd wp-content/plugins/gravityforms-cui-customizations
npm install
npm run build    # production build
# or for development/watch:
npm run dev
```

Built files are written to `assets/dist/` and the plugin will enqueue them if present.
