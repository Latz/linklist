# LinkList

A WordPress plugin that automatically appends a list of the links mentioned in a post, page, or feed item — with a Gutenberg block for placing it manually, per-post/page/feed on/off toggles, and quick/bulk-edit support.

Requires WordPress 6.4+. See [`readme.txt`](readme.txt) for the WordPress.org plugin listing and [`CHANGELOG.md`](CHANGELOG.md) for unreleased changes.

## Features

- Auto-appends a link list to posts, pages, and feed items via the `the_content` filter
- A `linklist/linklist` Gutenberg block for placing the list manually, with per-block style/prolog/separator/sort/minlinks overrides
- Per-post/page "Display Linklist" toggle (classic meta box, quick edit, bulk edit) — automatically hidden on block themes once the block is already used in a post
- Three list styles: ordered, unordered, or inline character-separated
- Minimum link count threshold before the list is shown
- Divs can be excepted from link harvesting by class name
- Adjustable content filter priority, for compatibility with other plugins that modify post content
- A `linklist` filter hook to programmatically alter the rendered list

## Installation

1. Upload the `linklist` folder to `/wp-content/plugins/`.
2. Activate the plugin from the WordPress admin panel.
3. Configure it under **Settings → LinkList**.

## Usage

The link list is appended automatically. To turn it off for a specific post or page, deselect the "Display Linklist" checkbox in the post/page editor, or use Quick Edit / Bulk Edit on the posts/pages list. To place the list at a specific point instead of the end of the content, insert the "Link List" block.

Style the output with CSS via the `.linklist` / `.linklistheader` classes:

```css
.linklist { /* the list wrapper */ }
.linklistheader { /* the prolog text */ }
```

Or hook into the `linklist` filter to change the rendered HTML programmatically:

```php
add_filter( 'linklist', function ( $list ) {
    return $list;
} );
```

## Development

```bash
composer install
npm install

composer test      # Pest (PHP)
npm test            # Vitest (JS)
composer phpstan    # static analysis
npm run build        # build the Gutenberg block
```

## License

GPLv2 or later. See [LICENSE](https://www.gnu.org/licenses/gpl-2.0.html).
