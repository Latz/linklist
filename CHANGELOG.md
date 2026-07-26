# Changelog

## [Unreleased]

### Major
- **[Major]** Rewrote link extraction to use `WP_HTML_Processor` (WP 6.4+) instead of a hand-rolled regex plus `DOMDocument`, fixing a PHP 8+ fatal in list sorting (`usort()` was calling a method as if static) along the way. Requires WordPress 6.4+ now.
- **[Major]** Added a full automated test suite: Pest (PHP, with Brain Monkey) and Vitest (JS) covering link extraction, list rendering, display gating, the block editor UI, and the admin quick/bulk-edit script.

### Minor
- Replaced legacy sanitization/escaping with WordPress core functions throughout: `wp_unslash()`/`sanitize_text_field()` instead of `addslashes()`, `esc_attr()` instead of `htmlentities()`/`stripslashes()`, `esc_url()` on rendered link hrefs, `absint()` for the priority setting, `wp_die()` instead of `die()`, and `get_extended()` for `<!--more-->` detection (now also handles `<!--more Custom Text-->`).
- Fixed a PHP 8.2+ deprecation warning from an undeclared dynamic property (`LinkList::$options`).

### Removed
- Removed `yst_plugin_tools.php` and its `Yoast_Plugin_Admin` base class — unrelated boilerplate from an old Yoast plugin template (an unused dashboard widget pulling a defunct Feedburner feed, a plugin-interop hook for a different third-party plugin, a donate box, and a broken enqueue for a CSS file that never existed). The admin settings page (`LinkList_Admin`) is now self-contained.
