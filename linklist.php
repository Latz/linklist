<?php
/*
Plugin Name: LinkList
Description: Adds a list of mentioned links at the end of the post, page or feed.
Plugin URI: http://wordpress.org/extend/plugins/linklist/
Version: 0.7
Requires at least: 6.4
Tested up to: 7.0
Stable tag: trunk
Text Domain: linklist
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
Author: Lutz Schr&ouml;er
Author URI: http://elektroelch.net/blog
*/

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}


/*  This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
--------------------------------------------------------------------------- */

if ( !class_exists('LinkList') ) {
	class LinkList {
		public $content;
		public $linklist;
		public $prefix;
		public $options;
		public $post_id;

		/* ------------------------------------------------------------------------ */
		public function linkExtractor($content){
			global $post;

			$processor = WP_HTML_Processor::create_fragment($content);
			$linkArray = array();
			$excludedDivs = array(); // stack of bools: is each open DIV ancestor excluded?

			while ($processor->next_token()) {
				if ('#tag' !== $processor->get_token_type()) {
					continue;
				}

				$tag = $processor->get_tag();

				if ('DIV' === $tag) {
					$this->trackDivExclusion($processor, $excludedDivs);
					continue;
				}

				if ('A' !== $tag || $processor->is_tag_closer() || in_array(true, $excludedDivs, true)) {
					continue;
				}

				$href = $processor->get_attribute('href');
				if (! is_string($href)) {
					continue;
				}

				$inner = $this->collectAnchorInnerHtml($processor);

				if ($this->isHarvestableLink($inner, $href, $linkArray, $post->ID)) {
					array_push($linkArray, array($href, $inner));
				}
			} //while

		 return $linkArray;
		} //linkExtractor()
		/* ------------------------------------------------------------------------ */
		// Push/pop the DIV-exclusion stack for one DIV open/close token, tracking
		// whether the current DIV ancestor chain matches an excepted class.
		private function trackDivExclusion($processor, array &$excludedDivs) {
			if ($processor->is_tag_closer()) {
				array_pop($excludedDivs);
				return;
			}

			// exact match against the div's full class attribute, matching the
			// previous DOMDocument-based behaviour (no per-class-token matching)
			$excludedDivs[] = ! empty($this->options['exceptions'])
				&& in_array($processor->get_attribute('class'), $this->options['exceptions']);
		}
		/* ------------------------------------------------------------------------ */
		// Accumulate an anchor's inner HTML until its closing tag; HTML doesn't
		// allow nested <a> elements, so the next </a> is always this anchor's own
		// closer.
		private function collectAnchorInnerHtml($processor) {
			$inner = '';
			while ($processor->next_token()) {
				if ('#tag' === $processor->get_token_type() && 'A' === $processor->get_tag() && $processor->is_tag_closer()) {
					break;
				}
				$inner .= $processor->serialize_token();
			}
			return $inner;
		}
		/* ------------------------------------------------------------------------ */
		// Whether a discovered link should be kept: not a pure image link, not the
		// post's own <!--more--> anchor, and not already collected.
		private function isHarvestableLink($inner, $href, array $linkArray, $post_id) {
			return strpos($inner, '<img') === false // avoid pure image links
				&& strpos($href, '#more-' . $post_id) === false // avoid <!--more--> links
				&& ! in_array(array($href, $inner), $linkArray); // avoid double entries
		}
		/* ------------------------------------------------------------------------ */
		public function __construct($content) {
			$this->content = $content;
            $this->options = get_option('linklist');
            $this->post_id = get_the_ID();
		} //linklist()
		/* ------------------------------------------------------------------------ */
		public function stopCreate() {
			return 0;
		}
		/* -------------------------------------------------------------------------- */
		public function linklist_sorter($a, $b) {
			return strnatcasecmp( $a[1], $b[1] );
		}
		/* ------------------------------------------------------------------------ */
    	public function createLinkList() {

		    // if the user has deslected to display the list only return the content
		    if (get_post_meta( get_the_ID(), 'linklist-display', true ) == '0') {
			    return $this->content;
		    }

			if ($this->stopCreate()) {
			  return $this->content;
			}

			$list = $this->buildList();
			if ($list === '') {
			  return $this->content;
			}

			$this->content .= $list;

		  return $this->content;

		} //createLinkList()
		/* ------------------------------------------------------------------------ */
		// Extracted links depend only on post content, not on the style/prolog/
		// sort overrides buildList() takes, so they're cached once per post in
		// post meta -- linkExtractor()'s HTML tokenizer pass is the expensive
		// part of buildList(), and would otherwise re-run on every render (once
		// per archive-page post, plus once per Link List block instance on that
		// post). Invalidated on save via linklist_invalidate_linklist_cache().
		private function getCachedLinks() {
			if (! $this->post_id) {
				return $this->linkExtractor($this->content);
			}

			$cached = get_post_meta($this->post_id, '_linklist_cache', true);
			if ('' !== $cached) {
				return $cached;
			}

			$links = $this->linkExtractor($this->content);
			update_post_meta($this->post_id, '_linklist_cache', $links);
			return $links;
		}
		/* ------------------------------------------------------------------------ */
		// build the linklist HTML for $this->content, without appending it.
		// $overrides may supply 'style', 'prolog', 'sep', 'sort', 'minlinks' to
		// take precedence over the stored $this->prefix-prefixed option for this
		// context; omit/null a key to fall back to the stored option.
		public function buildList($overrides = array()) {

			$opt = function($key) use ($overrides) {
				return (isset($overrides[$key]) && $overrides[$key] !== '')
					? $overrides[$key]
					: $this->options[$this->prefix . $key];
			};

      		$this->linklist = $this->getCachedLinks();
			if (! $this->linklist) {
			  return '';
			}

			 // min number of links
			if (sizeof($this->linklist) < $opt('minlinks') ) {
				return '';
			}

     		if ($opt('sort')) {
				usort($this->linklist, array($this, 'linklist_sorter'));
     		}

			$list = '<div class="linklist"><span class=linklistheader">' .
			$opt('prolog') . '</span>';

			$del_start = "<li>";
			$del_end = "</li>";

			switch ($opt('style')) {
				case 'rbul': $start = "<ul>";
										 $end   = "</ul>";
										 break;
				case 'rbli': $start = "";
										 $end   = "";
										 $del_start = "";
	  								 $del_end = $opt('sep');
										 break;
		        case 'rbol':
		        default: $start = "<ol>";
					 					 $end   = "</ol>";
										 break;
		  } //switch

		  $list .= $start;
		  foreach ($this->linklist as $link) {
		    $list .= $del_start . '<a href="' . esc_url($link[0]) . '">' . $link[1].'</a>'.$del_end;
		  }

		  // remove last separator
		  if ($opt('style') == "rbli") {
		    $list = substr($list, 0, strlen($opt('sep')) * -1);
		  }

		  $list .= $end . "</div>";
		  $list = apply_filters('linklist', $list);

		  return $list;

		} //buildList()

	} //class LinkList
} //if

/* =========================================================================== */
if ( !class_exists('PageLinkList') ) {
	class PageLinkList extends LinkList{

		public $prefix;

		/* ------------------------------------------------------------------------ */
		public function __construct($content) {
			parent::__construct($content);
			$this->prefix = 'page_';
		}
		/* ------------------------------------------------------------------------ */
		public function stopCreate() {
			global $numpages, $page;

		if (! $this->options['page_active']) {
			return 1;
		}

		  if ($numpages > 1 && $this->options['page_last'] && ($numpages != $page)) { //splitted page or post, display only on last page
				return 1;
		  }

			return 0;  //default
		}
		/* ------------------------------------------------------------------------ */
		public function linkExtractor($content) {
		  global $post;
			if ($this->options['page_last']) {
			  return parent::linkExtractor($post->post_content);
      } else {
			  return parent::linkExtractor($this->content);
      }
		}
	} //class PageLinkList
} //if

/* =========================================================================== */
if ( !class_exists('FeedLinkList') ) {
	class FeedLinkList extends LinkList {
		/* ------------------------------------------------------------------------ */
		public function stopCreate() {
			if (! $this->options['feed_active']) {
				return 1;
			}
			return 0;  //default
		}
		/* ------------------------------------------------------------------------ */
		public function __construct($content) {
			parent::__construct($content);
			$this->prefix = 'feed_';
		}
		/* ------------------------------------------------------------------------ */
  } //class FeedLinkList
}//if
/* =========================================================================== */
if ( !class_exists('BasicLinkList') ) {
	class BasicLinkList extends LinkList{

		/* -------------------------------------------------------------------------- */
		public function hasMoreLink() {
			global $post;
			return '' !== get_extended( $post->post_content )['extended'];
		}
 		/* ------------------------------------------------------------------------ */
		public function stopCreate() {

			if (! $this->options['post_active']) {
				return 1;
			}

			if ($this->hasMoreLink() && $this->options[$this->prefix . 'more']) {
				return 1;
			}

			if ($this->options['post_display']) {
			  return 1;
			}

			return 0;
		}
 		/* ------------------------------------------------------------------------ */
		public function __construct($content) {
			parent::__construct($content);
			$this->prefix = 'post_';
		}
	} //class BasicLinkList
} //if
/* =========================================================================== */
if ( !class_exists('SingleLinkList') ) {
	// Extends BasicLinkList to reuse hasMoreLink()/the post_ prefix, but
	// overrides stopCreate(): post_more/post_display are documented as
	// "prevents display on the main blog page" -- they gate the archive/loop
	// context (BasicLinkList), not the single-post permalink view or a
	// manually-placed block, which only respect the post_active master toggle.
	class SingleLinkList extends BasicLinkList {
		/* ------------------------------------------------------------------------ */
		public function stopCreate() {
			return ! $this->options['post_active'] ? 1 : 0;
		}
	} //class SingleLinkList
} //if
/* =========================================================================== */
/**
 * Whether $post already contains the Link List block, memoized per post ID
 * for the current request -- has_block() reparses the full block tree via
 * parse_blocks() on every call, and this is checked from multiple call
 * sites for the same post within a single request.
 */
function linklist_post_has_block( $post ) {
	static $cache = array();

	// Keyed by object identity, not $post->ID: guards against themes/plugins
	// that run the_content more than once for the same $post instance
	// (a well-known WP gotcha), without assuming every caller passes a
	// real WP_Post with a populated ID.
	$key = spl_object_id( $post );

	if ( ! isset( $cache[ $key ] ) ) {
		$cache[ $key ] = has_block( 'linklist/linklist', $post );
	}

	return $cache[ $key ];
}
/* ------------------------------------------------------------------------------------------------------------------ */
function linklist_create_linklist($content) {
 global $options, $post;

 // the Link List block already renders the list in place; don't also append it
 if ($post && linklist_post_has_block($post)) {
   return $content;
 }

 if (is_page()) {
   $linklist = new PageLinkList($content);
 } elseif (is_single()) {
   $linklist = new SingleLinkList($content);
 } elseif (is_feed()) {
   $linklist = new FeedLinkList($content);
 } else {
   $linklist = new BasicLinkList($content);
 }

return $linklist->createLinkList();

}  //linklist_create_linklist


/* --------------------------------------------------------------------------- */
function linklist_activate() {

	if (get_option('linklist')) {
	  return;
	}
	$options = ['post_active'   => 'on',
                'page_active'   => 'on',
                'feed_active'   => 'on',
                'post_prolog'   => 'Links in this post:',
                'page_prolog'   => 'Links on this page:',
                'feed_prolog'   => 'Links:',
                'post_style'    => 'rbol',
                'page_style'    => 'rbol',
                'feed_style'    => 'rbol',
                'post_display'  => '',
                'page_display'  => '',
                'post_more'     => 'on',
                'page_more'     => 'on',
                'post_minlinks' => 0,
                'page_minlinks' => 0,
                'feed_minlinks' => 0,
                'post_sep'      => ', ',
                'page_sep'      => ', ',
                'feed_sep'      => ', ',
                'post_sort'     => 'on',
                'page_sort'     => 'on',
                'feed_sort'     => 'on',
                'post_last'     => 'on',
                'page_last'     => 'on',
                'exceptions'    => array()
    ];
	update_option('linklist', $options);
}

/* --------------------------------------------------------------------------- */
function linklist_create_meta_box_content($object) {


	wp_nonce_field(basename(__FILE__), "linklist-meta-box-nonce");

	$post_meta = get_post_meta($object->ID, "linklist-display", true);

	// if no post meta is available get the default value from the options

	if ($post_meta == '0') {
		echo '<label for="linklist-display"><input id="linklist-display" name="linklist-display" type="checkbox" value="true">';
	} else {
		echo '<label for="linklist-display"><input id="linklist-display" name="linklist-display" type="checkbox" value="true"  checked="checked">';
	}

	printf('&nbsp%s</label>', esc_html__('Display Linklist', 'linklist'));
}
/* --------------------------------------------------------------------------- */
/**
 * Whether LinkList is active for the given post type, per the plugin's
 * General settings (post_active/page_active in get_option('linklist')).
 */
function linklist_is_type_active( $post_type ) {
	$options = get_option( 'linklist' );
	return ! empty( $options[ $post_type . '_active' ] );
}
/* --------------------------------------------------------------------------- */
/**
 * Whether the "Display Linklist" editor control should be shown: the type
 * must be active per plugin settings, and either the active theme isn't a
 * block theme, or (when a specific post is known) that post doesn't
 * already contain the linklist/linklist block, mirroring the has_block()
 * guard linklist_create_linklist() uses to skip the classic auto-append.
 */
function linklist_should_show_editor_control( $post_type, $post = null ) {
	if ( ! linklist_is_type_active( $post_type ) ) {
		return false;
	}

	if ( ! wp_is_block_theme() ) {
		return true;
	}

	return $post && ! linklist_post_has_block( $post );
}
/* --------------------------------------------------------------------------- */
function linklist_add_meta_box( $post_type, $post = null ) {
	if ( linklist_should_show_editor_control( $post_type, $post ) ) {
		add_meta_box('linklist-meta-box', 'Linklist', 'linklist_create_meta_box_content', $post_type, 'side', 'default', null);
	}
}
/* --------------------------------------------------------------------------- */
/**
 * Whether the current request carries a valid LinkList save nonce, from
 * either the classic meta box or quick edit.
 */
function linklist_has_valid_save_nonce() {
	$meta_box_nonce   = isset($_POST["linklist-meta-box-nonce"]) ? sanitize_text_field(wp_unslash($_POST["linklist-meta-box-nonce"])) : '';
	$quick_edit_nonce = isset($_POST["linklist-quick-edit-nonce"]) ? sanitize_text_field(wp_unslash($_POST["linklist-quick-edit-nonce"])) : '';

	if ($meta_box_nonce && wp_verify_nonce($meta_box_nonce, basename(__FILE__))) {
		return true;
	}

	return $quick_edit_nonce && wp_verify_nonce($quick_edit_nonce, basename(__FILE__));
}
/* ------------------------------------------------------------------------------------------------------------------ */
/**
 * Clears the cached extracted-links list for a post so the next render
 * recomputes it. Runs unconditionally on save_post (not nested under
 * is_admin()) so it also fires for block-editor saves, which go through
 * the REST API rather than a classic admin request.
 */
function linklist_invalidate_linklist_cache( $post_id ) {
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}

	delete_post_meta( $post_id, '_linklist_cache' );
}
/* ------------------------------------------------------------------------------------------------------------------ */
function linklist_save_meta_box($post_id) {

	if ( ! linklist_has_valid_save_nonce() ) {
		return $post_id;
	}

	if( ! current_user_can('edit_post', $post_id)) {
		return $post_id;
	}

	if ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
		return $post_id;
	}

	// save quick edit data
	if (isset($_POST["linklist-quick-edit-nonce"])) // save quick edit
	{
		$selectbox_value = isset($_POST['linklist-selectbox']) ? sanitize_text_field(wp_unslash($_POST['linklist-selectbox'])) : '';
		update_post_meta($post_id, 'linklist-display', 'yes' == $selectbox_value ? 1:0);
	} else { // save edit post page
		update_post_meta($post_id, 'linklist-display', isset($_POST['linklist-display']) ? 1 : 0);
	}
}
/* ------------------------------------------------------------------------------------------------------------------ */
function linklist_add_posts_column( $columns, $post_type ) {
	if ( linklist_is_type_active( $post_type ) ) {
		$columns[ 'linklist' ] = 'Linklist';
	}
	return $columns;
}/* ------------------------------------------------------------------------------------------------------------------ */
function linklist_populate_columns( $column_name, $post_id) {
	if ($column_name == 'linklist') {
		$post_id = absint( $post_id );
		$image   = sprintf(
			'<img src="%s" id="linklist-%d" height="24" width="24">',
			esc_url( plugins_url( 'check.png', __FILE__ ) ),
			$post_id
		);
		$display = get_post_meta($post_id, 'linklist-display', true) == ('0'|'') ? '': $image;

		printf('<div id="linklist-%d">%s</div>', $post_id, $display); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- $display is pre-built HTML (an <img> tag with esc_url()'d src and an integer id) from trusted, developer-authored sources, not user input.
	} //if
} //link_list_populate_columns
/* ------------------------------------------------------------------------------------------------------------------ */
function linklist_add_to_quick_edit_custom_box($column_name, $post_type) {
global $post_id;

	// this hook fires once per registered custom column, not just this plugin's
	if ( 'linklist' !== $column_name ) {
		return;
	}

	wp_nonce_field(basename(__FILE__), "linklist-quick-edit-nonce");

	if ( linklist_should_show_editor_control( $post_type ) ) {
		?><fieldset class="inline-edit-col-right">
			<div class="inline-edit-group">
				<label>
					<span class="title">Linklist</span>

					<select name="linklist-selectbox" id="linklist-selectbox">
					<option value="yes"> <?php esc_html_e('Display', 'linklist'); ?></option>
					<option value="no"> <?php esc_html_e('Hide', 'linklist'); ?></option>
					</select>


				</label>
			</div>
		</fieldset><?php
	}
}
/* ------------------------------------------------------------------------------------------------------------------ */
function linklist_add_to_bulk_edit_custom_box($column_name, $post_type) {
	global $post_id;

	// this hook fires once per registered custom column, not just this plugin's
	if ( 'linklist' !== $column_name ) {
		return;
	}

	wp_nonce_field(basename(__FILE__), "linklist-quick-edit-nonce");

	if ( linklist_should_show_editor_control( $post_type ) ) {
		?><fieldset class="inline-edit-col-right">
		<div class="inline-edit-group">
			<label>
				<span class="title">Linklist</span>

				<select name="linklist-selectbox" id="linklist-bulk-selectbox">
					<option value="nochange" selected="selected">&mdash; No Change &mdash;</option>
					<option value="yes"> <?php esc_html_e('Display', 'linklist'); ?></option>
					<option value="no"> <?php esc_html_e('Hide', 'linklist'); ?></option>
				</select>


			</label>
		</div>
		</fieldset><?php
	}
}
/* ------------------------------------------------------------------------------------------------------------------ */
function linklist_enqueue_edit_scripts() {
	wp_enqueue_script( 'linklist-admin-edit', plugins_url( 'linklist.js', __FILE__ ), array( 'inline-edit-post' ), '0.7', true );
	wp_localize_script( 'linklist-admin-edit', 'linklistBulkEdit', array(
		'nonce' => wp_create_nonce( 'linklist_save_bulk_edit' ),
	) );
}
/* ------------------------------------------------------------------------------------------------------------------ */
function linklist_save_bulk_edit() {
	check_ajax_referer( 'linklist_save_bulk_edit', 'nonce' );

	$post_ids = isset( $_POST[ 'post_ids' ] ) ? array_map( 'absint', (array) wp_unslash( $_POST[ 'post_ids' ] ) ) : array();
	$linklist_state = isset( $_POST[ 'linklist_state' ] ) ? sanitize_text_field( wp_unslash( $_POST[ 'linklist_state' ] ) ) : '';

	if (empty ($post_ids)) {
		return;
	}
	if ($linklist_state == 'nochange') {
		return;
	}

	foreach ($post_ids as $post_id) {
		update_post_meta($post_id, 'linklist-display', 'yes' == $linklist_state? 1 : 0);
	}

}
/* ------------------------------------------------------------------------------------------------------------------ */

require_once 'render.php';

if (is_admin()) {
    require_once 'linklist-options.php';
	register_activation_hook( __FILE__, 'linklist_activate' );

	// add per post display support
	add_action( 'add_meta_boxes', 'linklist_add_meta_box', 10, 2 );
	add_action( "save_post", "linklist_save_meta_box", 10, 1);
	add_filter( 'manage_posts_columns', 'linklist_add_posts_column', 10, 2 );
	add_action( 'manage_posts_custom_column', 'linklist_populate_columns', 10, 2 );
	add_action( 'quick_edit_custom_box', 'linklist_add_to_quick_edit_custom_box', 10, 2 );
	add_action( 'bulk_edit_custom_box', 'linklist_add_to_bulk_edit_custom_box', 10, 2 );
	add_action( 'admin_print_scripts-edit.php', 'linklist_enqueue_edit_scripts' );
	add_action( 'wp_ajax_linklist_save_bulk_edit', 'linklist_save_bulk_edit');
}

$linklist_priority = get_option('linklist_priority');
if (! $linklist_priority) {
    $linklist_priority = 10;
}

add_filter('the_content', 'linklist_create_linklist', $linklist_priority);

// registered unconditionally (not nested under is_admin()) so it also
// fires for block-editor saves, which go through the REST API
add_action( 'save_post', 'linklist_invalidate_linklist_cache', 10, 1 );
