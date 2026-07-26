<?php
/*
Plugin Name: LinkList
Description: Adds a list of mentioned links at the end of the post, page or feed.
Plugin URI: http://wordpress.org/extend/plugins/linklist/
Version: 0.6
Requires at least: 6.4
Tested up to: 6.6
Stable tag: trunk
Text Domain: linklist
Author: Lutz Schr&ouml;er
Author URI: http://elektroelch.net/blog
*/


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
					if ($processor->is_tag_closer()) {
						array_pop($excludedDivs);
					} else {
						// exact match against the div's full class attribute, matching the
						// previous DOMDocument-based behaviour (no per-class-token matching)
						$excludedDivs[] = ! empty($this->options['exceptions'])
							&& in_array($processor->get_attribute('class'), $this->options['exceptions']);
					}
					continue;
				}

				if ('A' !== $tag || $processor->is_tag_closer() || in_array(true, $excludedDivs, true)) {
					continue;
				}

				$href = $processor->get_attribute('href');
				if (! is_string($href)) {
					continue;
				}

				// accumulate the link's inner HTML until its closing tag; HTML
				// doesn't allow nested <a> elements, so the next </a> is always
				// this anchor's own closer
				$inner = '';
				while ($processor->next_token()) {
					if ('#tag' === $processor->get_token_type() && 'A' === $processor->get_tag() && $processor->is_tag_closer()) {
						break;
					}
					$inner .= $processor->serialize_token();
				}

				if ( (strpos($inner, '<img') === false) // avoid pure image links
					&& (strpos($href, '#more-'.$post->ID) === false)  // avoid <!--more--> links
					&& (! in_array(array($href, $inner), $linkArray))) { // avoid double entries
						array_push($linkArray, array($href, $inner));
				}
			} //while

		 return $linkArray;
		} //linkExtractor()
		/* ------------------------------------------------------------------------ */
		public function __construct($content) {
			$this->content = $content;
            $this->options = get_option('linklist');
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
		// build the linklist HTML for $this->content, without appending it.
		// $overrides may supply 'style', 'prolog', 'sep', 'sort', 'minlinks' to
		// take precedence over the stored $this->prefix-prefixed option for this
		// context; omit/null a key to fall back to the stored option.
		public function buildList($overrides = array()) {

			$opt = function($key) use ($overrides) {
				return (isset($overrides[$key]) && $overrides[$key] !== '' && $overrides[$key] !== null)
					? $overrides[$key]
					: $this->options[$this->prefix . $key];
			};

      		$this->linklist = $this->linkExtractor($this->content);
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
if ( !class_exists('SingleLinkList') ) {
	class SingleLinkList extends LinkList{

		/* ------------------------------------------------------------------------ */
		public function __construct($content) {
			parent::__construct($content);
			$this->prefix = 'post_';
		}
	} //class SingleLinkList
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
function create_linklist($content) {
 global $options, $post;

 // the Link List block already renders the list in place; don't also append it
 if ($post && has_block('linklist/linklist', $post)) {
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

}  //create_linklist


/* --------------------------------------------------------------------------- */
function llactivate() {

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
function linklist_CreateMetaBoxContent($object) {


	wp_nonce_field(basename(__FILE__), "linklist-meta-box-nonce");

	$post_meta = get_post_meta($object->ID, "linklist-display", true);

	// if no post meta is available get the default value from the options

	if ($post_meta == '0') {
		echo '<label for="linklist-display"><input id="linklist-display" name="linklist-display" type="checkbox" value="true">';
	} else {
		echo '<label for="linklist-display"><input id="linklist-display" name="linklist-display" type="checkbox" value="true"  checked="checked">';
	}

	printf('&nbsp%s</label>', __('Display Linklist'));
}
/* --------------------------------------------------------------------------- */
function linklist_AddMetaBox() {

	$screens = array( 'post', 'page' );
	foreach ( $screens as $screen ) {
		add_meta_box('linklist-meta-box', 'Linklist', 'linklist_CreateMetaBoxContent', $screen, 'side', 'default', null);
	}

}
/* --------------------------------------------------------------------------- */
function save_linklist_meta_box($post_id) {

	// check if the form was submitted corrected
	if  ( (! isset($_POST["linklist-meta-box-nonce"])   || (! wp_verify_nonce($_POST["linklist-meta-box-nonce"], basename(__FILE__))))
	     && (! isset($_POST["linklist-quick-edit-nonce"]) || (! wp_verify_nonce($_POST["linklist-quick-edit-nonce"], basename(__FILE__))))
		) {
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
		update_post_meta($post_id, 'linklist-display', 'yes' == $_POST['linklist-selectbox'] ? 1:0);
	} else { // save edit post page
		update_post_meta($post_id, 'linklist-display', isset($_POST['linklist-display']) ? 1 : 0);
	}
}
/* ------------------------------------------------------------------------------------------------------------------ */
function linklist_add_posts_column( $columns, $post_type ) {
	$types = array('post', 'page');
	if (in_array( $post_type, $types) ) {
		$columns[ 'linklist' ] = 'Linklist';
	}
	return $columns;
}/* ------------------------------------------------------------------------------------------------------------------ */
function linklist_populate_columns( $column_name, $post_id) {
	if ($column_name == 'linklist') {
		$id      = sprintf('id="linklist-%s"', $post_id);
		$image   = sprintf('<img src="%s" %s height="24" width="24">', plugins_url( 'check.png', __FILE__ ), $id);
		$display = get_post_meta($post_id, 'linklist-display', true) == ('0'|'') ? '': $image;

		printf('<div id="linklist-%s">%s</div>', $post_id, $display);
	} //if
} //link_list_populate_columns
/* ------------------------------------------------------------------------------------------------------------------ */
function linklist_add_to_quick_edit_custom_box($column_name, $post_type) {
global $post_id;

	wp_nonce_field(basename(__FILE__), "linklist-quick-edit-nonce");

	$types = array('post', 'page');
	if (in_array( $post_type, $types) ) {
		?><fieldset class="inline-edit-col-right">
			<div class="inline-edit-group">
				<label>
					<span class="title">Linklist</span>

					<select name="linklist-selectbox" id="linklist-selectbox">
					<option value="yes"> <?php _e('Display'); ?></option>
					<option value="no"> <?php _e('Hide'); ?></option>
					</select>


				</label>
			</div>
		</fieldset><?php
	}
}
/* ------------------------------------------------------------------------------------------------------------------ */
function linklist_add_to_bulk_edit_custom_box($column_name, $post_type) {
	global $post_id;

	wp_nonce_field(basename(__FILE__), "linklist-quick-edit-nonce");

	$types = array('post', 'page');
	if (in_array( $post_type, $types) ) {
		?><fieldset class="inline-edit-col-right">
		<div class="inline-edit-group">
			<label>
				<span class="title">Linklist</span>

				<select name="linklist-selectbox" id="linklist-bulk-selectbox">
					<option value="nochange" selected="selected">&mdash; No Change &mdash;</option>
					<option value="yes"> <?php _e('Display'); ?></option>
					<option value="no"> <?php _e('Hide'); ?></option>
				</select>


			</label>
		</div>
		</fieldset><?php
	}
}
/* ------------------------------------------------------------------------------------------------------------------ */
function linklist_enqueue_edit_scripts() {
	wp_enqueue_script( 'linklist-admin-edit', plugins_url( 'linklist.js', __FILE__ ), array( 'jquery', 'inline-edit-post' ), '', true );
}
/* ------------------------------------------------------------------------------------------------------------------ */
function linklist_save_bulk_edit() {
	$post_ids = ( isset( $_POST[ 'post_ids' ] ) && !empty( $_POST[ 'post_ids' ] ) ) ? $_POST[ 'post_ids' ] : array();
	$linklist_state = ( isset( $_POST[ 'linklist_state' ] ) && !empty( $_POST[ 'linklist_state' ] ) ) ? $_POST[ 'linklist_state' ] : null;

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
	register_activation_hook( __FILE__, 'llactivate' );

	// add per post display support
	add_action( 'add_meta_boxes', 'linklist_AddMetaBox');
	add_action( "save_post", "save_linklist_meta_box", 10, 1);
	add_filter( 'manage_posts_columns', 'linklist_add_posts_column', 10, 2 );
	add_action( 'manage_posts_custom_column', 'linklist_populate_columns', 10, 2 );
	add_action( 'bulk_edit_custom_box', 'linklist_add_to_bulk_quick_edit_custom_box', 10, 2 );
	add_action( 'quick_edit_custom_box', 'linklist_add_to_quick_edit_custom_box', 10, 2 );
	add_action( 'bulk_edit_custom_box', 'linklist_add_to_bulk_edit_custom_box', 10, 2 );
	add_action( 'admin_print_scripts-edit.php', 'linklist_enqueue_edit_scripts' );
	add_action( 'wp_ajax_linklist_save_bulk_edit', 'linklist_save_bulk_edit');
}

$priority = get_option('linklist_priority');
if (! $priority) {
    $priority = 10;
}

add_filter('the_content', 'create_linklist', $priority);
