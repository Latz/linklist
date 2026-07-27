=== LinkList ===
Contributors: Lutz Schroeer
Tags: links
Requires at least: 6.4
Tested up to: 7.0
Stable tag: 0.7
Author: Lutz Schroeer
Version: 0.7
Author URI: http://elektroelch.de/blog/
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Adds a list of mentioned links at the end of the post, page or feed.

== Description ==

LinkList adds a list of mentioned links at the end of the post, page or feed.

== Installation ==

1. Download the plugin and unzip it.
2. Upload the folder lonklist/ to your /wp-content/plugins/ folder.
3. Activate the plugin from your WordPress admin panel.
4. Installation finished.

== Usage ==
Linklist automatically puts a list of all link at the end of the post or page. If you want to exclude an
individual page/post from displaying the list you can de-select the "Display Linklist" checkbox on the
left side of the edit screen.

If you want to change the display of the link list for an already existing post/page you can use
the Quick Edit or Bulk Edit option.

On block themes, you can also add the "Link List" block directly wherever you want the list to
appear, with per-block overrides for style, prolog text, separator, sort order, and minimum link
count. When the block is present, the automatic append at the end of the content is skipped.

== Settings ==
LinkList provides a varietey of settings to tweak the list to your needs. The settings are
divided into three parts (posts, pages and feeds).

= General settings =
Here you can define if the linklist should be display on posts, pages and/or feeds at all.


= Posts settings =
* Content to put in front of list
Text to be displayed in front of the linklist

* Style of list
You can choose between three different styles:
- Ordered list:
    1. link
    2. link
    3. link
    4. ...
   
- Unordered list
    * link
    * link
    * link
    * ...
   
- Char separated list
    link, link, link, link
   
  The separating character is defined in "Separator character(s)"
  
* Separating character(s)
Character(s) used to separate the links if "char separated list" is chosen above.

* Minimum links
Minimum number of links mentioned in the post for the list to be displayed.

* Sorting
Sort the links alphabetically. This function has an issue with international characters (e.g.
German umlauts).

* More tag
Prevents the display of the link list on the main blog page.

* Single post
Prevents the display of the link list on the main blog page.

* Last page only
If you have devided your post into several parts using <!--nextpage--> the link list is only displayed on the last page.
This list will contain all links of the post. If the list is displayed at the end of every part only the links of that
part are displayed. The settings of "Minimum links" applies to every part separately.

* Exceptions
You can except divs from being harvested for links. Enter a comma separated kist of divs to be excluded.

* Priority
There are many other plugins messing around with the post content. By altering the priority of the LinkList you can  change the position where the list appearts. 1 means high priority, 20 means low priority, default is 10.

= Pages settings =

* Content to put in front of list
Text to be displayed in front of the linklist

* Style of list
You can choose between three different styles:
- Ordered list:
    1. link
    2. link
    3. link
    4. ...
   
- Unordered list
    * link
    * link
    * link
    * ...
   
- Char separated list
    link, link, link, link
   
  The separating character is defined in "Separator character(s)"
  
* Separating character(s)
Character(s) used to separate the links if "char separated list" is chosen above.

* Minimum links
Minimum number of links mentioned in the post for the list to be displayed.

* Sorting
Sort the links alphabetically. This function has an issue with international characters (e.g.
German umlauts).

* Last page only
If you have devided your post into several parts using <!--nextpage--> the link list is only displayed on the last page.
This list will contain all links of the post. If the list is displayed at the end of every part only the links of that
part are displayed. The settings of "Minimum links" applies to every part separately.


= Feed settings =
* Content to put in front of list
Text to be displayed in front of the linklist

* Style of list
You can choose between three different styles:
- Ordered list:
    1. link
    2. link
    3. link
    4. ...
   
- Unordered list
    * link
    * link
    * link
    * ...
   
- Char separated list
    link, link, link, link
   
  The separating character is defined in "Separator character(s)"
  
* Separating character(s)
Character(s) used to separate the links if "char separated list" is chosen above.

* Minimum links
Minimum number of links mentioned in the post for the list to be displayed.

* Sorting
Sort the links alphabetically. This function has an issue with international characters (e.g.
German umlauts).

= Styling LinkList =
You can style the link list with CSS:

<div class="linklist">
  <span class="linklistheader">
    Content to put in front of list
  </span>
</div>

= Filter =
You can programmatically change the content of the linklist by adding a filter:

<?php
  add_filter('linklist', 'my_linklist');

  function my_linklist($list) {
    [...]
  }


== Credits ==
* Joost de Valk (Yoast) for his plugin tools (yst_plugin_tools.php). http://yoast.com/
* Tami Mize for assuming display option for individual posts or pages

      
== Changelog ==
= v0.7 =
+ Cached extracted-link parsing per post, invalidated on save, so link extraction no longer re-runs on every render of a post/page/feed item; also memoized the Gutenberg-block-presence check per request.
+ Rewrote link extraction to use WordPress's own HTML parser (WP_HTML_Processor) instead of a hand-rolled regex plus DOMDocument, fixing a PHP 8+ fatal in list sorting (usort() was calling a method as if static) along the way. Requires WordPress 6.4+ now.
+ Added a full automated test suite: Pest (PHP, with Brain Monkey) and Vitest (JS) covering link extraction, list rendering, display gating, the block editor UI, and the admin quick/bulk-edit script.
+ Added a Link List Gutenberg block for placing the list manually, with per-block overrides for style, prolog text, separator, sort order, and minimum link count. The automatic append is skipped whenever the block is already in a post, and the classic "Display Linklist" admin controls are hidden on block themes once a post already contains the block.
+ Fixed a CSRF gap: the bulk-edit AJAX handler had no nonce verification at all. Added nonce generation/verification and proper sanitization of the submitted post IDs and state.
* Replaced legacy sanitization/escaping with WordPress core functions throughout: wp_unslash()/sanitize_text_field() instead of addslashes(), esc_attr() instead of htmlentities()/stripslashes(), esc_url() on rendered link hrefs, absint() for the priority setting, wp_die() instead of die(), and get_extended() for <!--more--> detection (now also handles <!--more Custom Text-->).
* Fixed a PHP 8.2+ deprecation warning from an undeclared dynamic property (LinkList::$options).
* Removed duplicated row-building code in the admin settings page (posts/pages/feed sections), and fixed the settings box rendering flush against the "Need support?" sidebar box.
* Modernized linklist.js (const/let, template literals, strict equality) and documented its event handlers with JSDoc.
* Fixed post_active being silently ignored for the Gutenberg block and the classic single-post view; the site-wide "Display linklist in posts" toggle now applies there too (post_more/post_display intentionally remain limited to the main blog page, per their documented behavior).
* Fixed the quick-edit and bulk-edit dropdowns sharing the same invalid duplicate HTML id.
* Fixed a dead admin hook registration left over from a typo, and a latent bug where an unrecognized list style left the wrapper markup undefined.
* Renamed unprefixed global functions to avoid collisions with other plugins/themes, added missing direct file-access guards, completed missing translation text domains, and fixed missing output escaping in a few places -- WordPress.org plugin-directory submission requirements.
* Synced the plugin header and this changelog's version/compatibility fields, which had drifted years out of date.
* Removed jQuery from the admin bulk/quick-edit script in favor of vanilla JS and fetch(), and fixed the block editor's live preview feeling slow when switching list style or typing prolog/separator text.
* Fixed several DeepSource static-analysis findings (JS lint rules, PHP constant visibility, analyzer configuration).
- Removed yst_plugin_tools.php and its Yoast_Plugin_Admin base class — unrelated boilerplate from an old Yoast plugin template (an unused dashboard widget pulling a defunct Feedburner feed, a plugin-interop hook for a different third-party plugin, a donate box, and a broken enqueue for a CSS file that never existed). The admin settings page (LinkListAdmin) is now self-contained.

= v0.5 =
+ Added display option for individual posts (inl. Quick and Bulk edit)


= v0.4 =
+ Added possibility to except divs in content from link harvesting
+ Added option to seet priority of LinkList

= v0.3 =
* Fixed "Strict standards" notice in PHP 5.5
* Checked for 3.7 compatibility

= v0.2 =
+ Added: filter for link list (11 SEP 2012)

= v0.1 =
Initial release (15 AUG 2009)
