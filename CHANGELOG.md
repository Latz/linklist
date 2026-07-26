# Changelog

## [Unreleased]

### Major
- **[Major]** Rewrote link extraction to use `WP_HTML_Processor` (WP 6.4+) instead of a hand-rolled regex plus `DOMDocument`, fixing a PHP 8+ fatal in list sorting (`usort()` was calling a method as if static) along the way. Requires WordPress 6.4+ now.
- **[Major]** Added a full automated test suite: Pest (PHP, with Brain Monkey) and Vitest (JS) covering link extraction, list rendering, display gating, the block editor UI, and the admin quick/bulk-edit script.

### Minor
- Replaced legacy sanitization/escaping with WordPress core functions throughout: `wp_unslash()`/`sanitize_text_field()` instead of `addslashes()`, `esc_attr()` instead of `htmlentities()`/`stripslashes()`, `esc_url()` on rendered link hrefs, `absint()` for the priority setting, `wp_die()` instead of `die()`, and `get_extended()` for `<!--more-->` detection (now also handles `<!--more Custom Text-->`).
- Fixed a PHP 8.2+ deprecation warning from an undeclared dynamic property (`LinkList::$options`).
- Removed duplicated row-building code from the admin settings page (`LinkList_Admin::config_page()`): extracted `checkbox_row()`, `text_row()`, and `style_row()` helpers reused across the posts/pages/feed sections, fixing a SonarQube duplication finding (14.3% → near 0%). Verified byte-identical rendered output.
- Fixed the settings box rendering flush against the "Need support?" sidebar box on the options page (the two `.postbox-container` divs floated with no gap between them); wrapped them in a flex container with `gap` instead.
- Modernized `linklist.js`: replaced `new Array()` with a literal, switched to strict equality and template literals, and documented the two event-handler functions with JSDoc `@listens`.

### Removed
- Removed `yst_plugin_tools.php` and its `Yoast_Plugin_Admin` base class — unrelated boilerplate from an old Yoast plugin template (an unused dashboard widget pulling a defunct Feedburner feed, a plugin-interop hook for a different third-party plugin, a donate box, and a broken enqueue for a CSS file that never existed). The admin settings page (`LinkList_Admin`) is now self-contained.
