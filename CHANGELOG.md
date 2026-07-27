# Changelog

## [Unreleased]

### Major
- **[Major]** Rewrote link extraction to use `WP_HTML_Processor` (WP 6.4+) instead of a hand-rolled regex plus `DOMDocument`, fixing a PHP 8+ fatal in list sorting (`usort()` was calling a method as if static) along the way. Requires WordPress 6.4+ now.
- **[Major]** Added a full automated test suite: Pest (PHP, with Brain Monkey) and Vitest (JS) covering link extraction, list rendering, display gating, the block editor UI, and the admin quick/bulk-edit script.
- **[Major]** Added a `linklist/linklist` Gutenberg block (`render.php`, `src/edit.js`, `src/index.js`, `src/block.json`) for placing the link list manually instead of relying solely on the automatic `the_content` append, with per-block overrides for style, prolog text, separator, sort order, and minimum link count. `linklist_create_linklist()` skips the automatic append via `has_block()` whenever the block is already present in a post, avoiding duplicate output. The classic "Display Linklist" meta box, quick edit, and bulk edit are automatically hidden on block themes once a post already contains the block.
- **[Major]** Fixed a CSRF gap: the bulk-edit AJAX handler (`linklist_save_bulk_edit`, hooked to `wp_ajax_linklist_save_bulk_edit`) had no nonce verification at all, and the JS never sent one. Added `wp_localize_script()` to hand the JS a nonce, `check_ajax_referer()` verification on the PHP side, and sanitized `post_ids`/`linklist_state` instead of using them raw from `$_POST`.

### Minor
- Replaced legacy sanitization/escaping with WordPress core functions throughout: `wp_unslash()`/`sanitize_text_field()` instead of `addslashes()`, `esc_attr()` instead of `htmlentities()`/`stripslashes()`, `esc_url()` on rendered link hrefs, `absint()` for the priority setting, `wp_die()` instead of `die()`, and `get_extended()` for `<!--more-->` detection (now also handles `<!--more Custom Text-->`).
- Fixed a PHP 8.2+ deprecation warning from an undeclared dynamic property (`LinkList::$options`).
- Removed duplicated row-building code from the admin settings page (`LinkListAdmin::config_page()`): extracted `checkbox_row()`, `text_row()`, and `style_row()` helpers reused across the posts/pages/feed sections, fixing a SonarQube duplication finding (14.3% → near 0%). Verified byte-identical rendered output.
- Fixed the settings box rendering flush against the "Need support?" sidebar box on the options page (the two `.postbox-container` divs floated with no gap between them); wrapped them in a flex container with `gap` instead.
- Modernized `linklist.js`: replaced `new Array()` with a literal, switched to strict equality and template literals, and documented the two event-handler functions with JSDoc `@listens`.
- Fixed `post_active` being silently ignored for the Gutenberg block and the classic single-post view: `SingleLinkList` (used by both) never overrode `stopCreate()`, so the site-wide "Display linklist in posts" toggle had no effect there, only on archive/home listings. `post_more`/`post_display` intentionally remain archive-only, per their documented behavior ("prevents display on the main blog page").
- Fixed a real bug: the quick-edit and bulk-edit `<select>` fields both used `id="linklist-selectbox"` — invalid duplicate IDs in the same DOM. Bulk edit is now `id="linklist-bulk-selectbox"`.
- Fixed a dead hook registration: `bulk_edit_custom_box` was wired to a callback name that never existed anywhere in the codebase (a typo merging two real function names), silently doing nothing; removed the dead duplicate.
- Fixed a latent bug in `buildList()`'s style switch: an unrecognized style value left `$start`/`$end` undefined; added a proper default case. Also fixed a redundant, unreachable null check in its override-lookup closure (both found by PHPStan level 5).
- Renamed unprefixed global functions to avoid collision risk with other plugins/themes, a WordPress.org submission requirement: `create_linklist` → `linklist_create_linklist`, `llactivate` → `linklist_activate`, `save_linklist_meta_box` → `linklist_save_meta_box`.
- Added missing direct-access guards (`if (!defined('ABSPATH')) exit;`) to `linklist.php`, `linklist-options.php`, and `render.php`.
- Fixed missing/incomplete i18n: added the `linklist` text domain to translation calls that were missing it, and switched output-bound calls to `esc_html_e()`/`esc_html__()`.
- Fixed missing output escaping in the settings-page postbox rendering and the admin list-table column (now uses `absint()`/`esc_url()`).
- Synced plugin header and `readme.txt` metadata that had drifted years out of date (Version, Requires at least, Tested up to, Stable tag, License/License URI), and added the WP.org-required short description line.
- Added dev tooling: PHPStan (with a custom `WP_HTML_Processor` stub, since phpstan-wordpress doesn't cover the WP 6.4+ HTML API), WordPress Plugin Check (`bin/plugin-check.sh`), Trivy vulnerability/secret scanning (`bin/trivy-check.sh`), and a SonarCloud scan script (`bin/sonar-scanner.sh`).

### Removed
- Removed `yst_plugin_tools.php` and its `Yoast_Plugin_Admin` base class — unrelated boilerplate from an old Yoast plugin template (an unused dashboard widget pulling a defunct Feedburner feed, a plugin-interop hook for a different third-party plugin, a donate box, and a broken enqueue for a CSS file that never existed). The admin settings page (`LinkListAdmin`) is now self-contained.
