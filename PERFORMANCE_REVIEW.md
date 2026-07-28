# Performance Review — Link List

**Date:** 2026-07-28
**Scope:** PHP request path (`linklist.php`, `render.php`) and block editor JS (`src/edit.js`), read-only audit, no code changes in this pass.
**Method:** manual code review + verification of cited line numbers against current source; cross-checked against `tests/Unit/LinklistCacheTest.php`, `tests/Unit/RenderBlockTest.php`, `tests/Unit/StopCreateTest.php`.

## Already optimized (baseline)

These were addressed in recent commits and are not flagged as issues below.

- **Extracted-links cache** — `LinkList::getCachedLinks()` (`linklist.php:171-183`) caches the tokenizer output in post meta (`_linklist_cache`), keyed by `$this->post_id`. Correctly distinguishes "no cache yet" (`''`) from "cached empty result" (`[]`). Invalidated on save via `linklist_invalidate_linklist_cache()` (`linklist.php:513-518`), which runs unconditionally (not admin-gated) so it also fires for block-editor REST saves, and is skipped under `DOING_AUTOSAVE`. Fully covered by `tests/Unit/LinklistCacheTest.php`.
- **`has_block()` memoization** — `linklist_post_has_block()` (`linklist.php:365-378`) caches the result of `has_block()` (which reparses the block tree via `parse_blocks()`) in a static array keyed by `spl_object_id($post)` for the life of the request. Avoids redundant reparsing across the multiple call sites that check this per post. Covered by tests (object-identity keying verified, no cross-post conflation).
- **Editor preview debounce** — `src/edit.js:13, 60-80`. Only continuous-typing attributes (`prolog`, `sep`, `minlinks`) are debounced 300ms via `useDebounce`; discrete `SelectControl` changes (`style`, `sort`, `lastpage`) bypass the debounce and update immediately, with `debouncedSetPreviewAttributes.cancel()` called to avoid a stale debounced update landing after an immediate one.
- **Minimal block footprint** — `supports: { html: false }` only (no `align`/`color`/`spacing` supports pulling in extra machinery); dynamic render (`save: () => null`), so output isn't duplicated into `post_content`; production JS bundle (`build/index.js`) is ~2.9KB, no code-splitting needed at this size.

## Findings

### 1. `get_option('linklist')` has no request-level memoization (Medium) — **attempted, reverted**
**Location:** `linklist.php:122` (`LinkList::__construct()`), `linklist.php:463` (`linklist_is_type_active()`)

Unlike `has_block()`, every `LinkList`/`SingleLinkList`/`PageLinkList`/`FeedLinkList` construction re-fetches `get_option('linklist')`. This happens once per `the_content` filter invocation per post in a loop, *and* once per Link List block instance rendered on that post (`render.php` constructs a fresh `PageLinkList`/`SingleLinkList` per block). `linklist_is_type_active()` is called from `linklist_should_show_editor_control()`, which fires per-row in the Quick Edit / bulk-edit admin column rendering — so on a post list screen with N rows, that's N re-fetches.

WordPress's Options API serves autoloaded options from an in-memory cache, so this is not a DB round-trip in the common case — but it's still repeated array access/lookup overhead per call.

**Implementation attempted:** added a `linklist_get_options()` static-cache wrapper mirroring `linklist_post_has_block()`, and routed both call sites through it. **Reverted** after it broke 26 tests: `tests/Unit/StopCreateTest.php` (and others) call `stubLinklistOptions()` with different option values across multiple test cases within a single PHP process, and a static cache doesn't observe those changes after its first fill. This isn't just a test artifact — the same staleness risk exists in production: `linklist-options.php:201-208` calls `update_option('linklist', ...)` and then `get_option('linklist')` again in the *same request* to redisplay the just-saved settings. A static cache with no invalidation hook would serve stale data there too, unlike the `_linklist_cache` post-meta cache (which has a working `save_post` invalidation path) or `has_block()`'s memoization (which is safe because a post's block content can't change mid-request).

**Conclusion:** not implemented. The correct fix would require either invalidating the cache inside `update_option('linklist')` callers (extra coupling for a cache whose underlying `get_option()` call is already fast) or leaving it alone. Given the option is autoloaded and already in-memory-cached by WordPress core, the risk/benefit doesn't justify adding a second, weaker cache layer on top. Left as-is.

### 2. No persistent object-cache/transient layer (Low — informational)
**Location:** plugin-wide

All caching is either post-meta (`_linklist_cache`) or PHP static-variable request memoization (`linklist_post_has_block()`). Nothing uses WordPress's persistent object cache API (`wp_cache_*`) or transients. This means `has_block()`'s cost is paid again on every fresh page load — unavoidable without persisting a flag (e.g., alongside `_linklist_cache`).

**Recommendation:** Not a current defect; only worth revisiting if a persistent object cache (Redis/Memcached) is confirmed in the deployment target and profiling shows `has_block()`/option lookups as measurably hot.

### 3. Multiple `LinkList` instantiations per post (Low)
**Location:** `render.php` render callback + `linklist.php:381` `linklist_create_linklist()`

A post with both classic `the_content` output and one or more Link List blocks (or multiple blocks) triggers multiple `LinkList` subclass constructions, each re-running `get_option()`/`get_the_ID()`. The expensive part (link extraction) is already protected by the post-meta cache, so the residual cost here is just construction overhead — largely resolved by fixing #1.

### 4. `linklist_populate_columns()` — one `get_post_meta` call per row per column (Low — unconfirmed)
**Location:** `linklist.php:551`

Admin post-list-table column rendering reads post meta per row per column with no explicit `update_meta_cache()` batching. WP's list table query typically primes the meta cache for all visible rows automatically, which would make this a cache hit rather than a fresh DB query — but this hasn't been confirmed for this specific column-rendering hook.

**Recommendation:** Spot-check with Query Monitor (or similar) on a post list screen with the column enabled before deciding whether batching is needed. Do not change speculatively.

### 5. Bulk-edit AJAX handler loops `update_post_meta` per selected post (Low — expected)
**Location:** `linklist_save_bulk_edit()`, `linklist.php:629-646`

One `update_post_meta` write per selected post ID, no batch-size cap. This is inherent to the bulk-edit feature and bounded by how many posts an admin selects. Flagging only in case "select all across pages" in the list table can produce very large `$post_ids` arrays — worth a sanity check on the JS side, not necessarily a PHP fix.

### 6. JS bundle / build tooling (Informational, no action)
**Location:** `vite.config.js`, `build/`

`vite.config.js` is test-only (Vitest + `@wordpress/*` mocks for JSDOM unit tests); the shipped bundle is produced by `@wordpress/scripts`' default webpack config (no custom `webpack.config.js` present). Bundle size (~2.9KB) is small enough that chunking/minification tuning isn't warranted at this scope.

### 7. Debounce mixed-key edge case (Informational, not a bug)
**Location:** `src/edit.js:69-72`

If a debounced key (e.g. `prolog`) and a non-debounced key (e.g. `style`) change in the same effect tick, `shouldDebounce` requires *all* changed keys to be debounce-eligible, so the update renders immediately rather than debouncing. This is the conservative/correct choice (never delays a discrete control's feedback) and matches the intent documented in the code comment — noted for completeness, no change recommended.

## Out of scope / explicitly not issues
- No `WP_Query` or `get_posts()` calls exist anywhere in the plugin — no N+1 query risk from custom queries.
- `linklist-options.php` admin settings page reads/writes options only on its own screen load or POST submit — low traffic, not a concern.
- `linklist_register_block()` (`render.php:16-27`) runs on every `init` but only does a cheap `file_exists()` + `register_block_type()` — standard block registration cost, not a plugin-specific issue.

## Summary
Two prior optimization passes (link-extraction caching, `has_block()` memoization, preview debounce) already cover the highest-impact paths. Finding #1's memoization was implemented and then reverted after it proved unsafe (test failures + real mid-request staleness risk against `linklist-options.php`'s save-then-redisplay flow) — no safe code change was available. Findings #2, #4, #5, #6, #7 are informational, "confirm before changing," or already-correct-by-design, so no code changes apply to them either. Net result of this pass: no code changes; all 91 existing PHPUnit tests still pass unchanged.
