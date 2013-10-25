<?php
/*
Plugin Name: Linklist
Description: Adds a list of mentioned links at the end of the post, page or feed.
Plugin URI: http://wordpress.org/extend/plugins/linklist/
Version: 0.2
Requires at least: 2.9
Tested up to: 3.4
Stable tag: trunk
Author: Lutz Schr&ouml;er
Author URI: http://elektroelch.de/blog
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
		var $content;
		var $linklist;
		var $prefix;

		/* ------------------------------------------------------------------------ */
		function linkExtractor($content){
			global $post;
			$linkArray = array();
			if ( (preg_match_all('/<a\s+.*?href=[\"\']?([^\"\' >]*)[\"\']?[^>]*>(.*?)<\/a>/i',
						$content,$matches,PREG_SET_ORDER)))
				foreach($matches as $match) {
				if ( (strpos($match[0], '<img') <= 0) // avoid pure image links
					&& (strpos($match[0], '#more-'.$post->ID) <= 0)  // avoid <!--more--> links
					&& (! in_array(array($match[1],$match[2]), $linkArray))) // avoid double entries
						array_push($linkArray,array($match[1],$match[2]));
			} //if
		 return $linkArray;
		} //linkExtractor()
		/* ------------------------------------------------------------------------ */
		function LinkList($content) {
			$this->options = get_option('linklist');
			$this->content = $content;
		} //linklist()
		/* ------------------------------------------------------------------------ */
		function stopCreate() {
			return 0;
		}
		/* -------------------------------------------------------------------------- */
		function linklist_sorter($a, $b) {
			return strnatcasecmp( $a[1], $b[1] );
		}
		/* ------------------------------------------------------------------------ */
    	function createLinkList() {

			if ($this->stopCreate())
			  return $this->content;

      		$this->linklist = $this->linkExtractor($this->content);
			if (! $this->linklist)
			  return $this->content;

			 // min number of links
			if (sizeof($this->linklist) < $this->options[$this->prefix . 'minlinks'] )
				return $this->content;

     		if ($this->options[$this->prefix . 'sort'])
				usort($this->linklist, array('LinkList', 'linklist_sorter'));

			$list = '<div class="linklist"><span class=linklistheader">' .
			$this->options[$this->prefix . 'prolog'] . '</span>';

			$del_start = "<li>";
			$del_end = "</li>";

			switch ($this->options[$this->prefix . 'style']) {
				case 'rbul': $start = "<ul>";
										 $end   = "</ul>";
										 break;
		    case 'rbol': $start = "<ol>";
					 					 $end   = "</ol>";
										 break;
				case 'rbli': $start = "";
										 $end   = "";
										 $del_start = "";
	  								 $del_end = $this->options[$this->prefix . 'sep'];
										 break;
		  } //switch

		  $list .= $start;
		  foreach ($this->linklist as $link)
		    $list .= $del_start . '<a href="' . $link[0] . '">' . $link[1].'</a>'.$del_end;

		  // remove last separator
		  if ($this->options[$this->prefix . 'style'] == "rbli")
		    $list = substr($list, 0, strlen($this->options[$this->prefix . 'sep']) * -1);

		  $list .= $end . "</div>";
		  $list = apply_filters('linklist', $list);
		  $this->content .= $list;

			return $this->content;

		} //createLinkList()

	} //class LinkList
} //if

/* =========================================================================== */
if ( !class_exists('PageLinkList') ) {
	class PageLinkList extends LinkList{

		var $prefix;

		/* ------------------------------------------------------------------------ */
		function PageLinkList($content) {
			parent::LinkList($content);
			$this->prefix = 'page_';
		}
		/* ------------------------------------------------------------------------ */
		function stopCreate() {
			global $numpages, $page;

		if (! $this->options['page_active'])
			return 1;

		  if ($numpages > 1) //splitted page or post
			{
				// exit if display only on last page
				if ($this->options['page_last'] && ($numpages != $page))
					return 1;
			}

			return 0;  //default
		}
		/* ------------------------------------------------------------------------ */
		function linkExtractor($content) {
		  global $post;
			if ($this->options['page_last'])
			  return parent::linkExtractor($post->post_content);
      else
			  return parent::linkExtractor($this->content);
		}
	} //class PageLinkList
} //if

/* =========================================================================== */
if ( !class_exists('SingleLinkList') ) {
	class SingleLinkList extends LinkList{

		/* ------------------------------------------------------------------------ */
		function SingleLinkList($content) {
			parent::LinkList($content);
			$this->prefix = 'post_';
		}
	} //class SingleLinkList
} //if
/* =========================================================================== */
if ( !class_exists('FeedLinkList') ) {
	class FeedLinkList extends LinkList {
		/* ------------------------------------------------------------------------ */
		function stopCreate() {
			if (! $this->options['feed_active'])
				return 1;
			return 0;  //default
		}
		/* ------------------------------------------------------------------------ */
		function FeedLinkList($content) {
			parent::LinkList($content);
			$this->prefix = 'feed_';
		}
		/* ------------------------------------------------------------------------ */
  } //class FeedLinkList
}//if
/* =========================================================================== */
if ( !class_exists('BasicLinkList') ) {
	class BasicLinkList extends LinkList{

		/* -------------------------------------------------------------------------- */
		function hasMoreLink() {
			global $post;
			return strpos($post->post_content, '<!--more-->');
		}
 		/* ------------------------------------------------------------------------ */
		function stopCreate() {

			if (! $this->options['post_active'])
				return 1;

			if ($this->hasMoreLink())
		    if ($this->options[$this->prefix . 'more'])
					return 1;

			if ($this->options['post_display'])
			  return 1;

			return 0;
		}
 		/* ------------------------------------------------------------------------ */
		function BasicLinkList($content) {
			parent::LinkList($content);
			$this->prefix = 'post_';
		}
	} //class BasicLinkList
} //if
/* =========================================================================== */
function create_linklist($content) {
 if (is_page())
   $linklist = new PageLinkList($content);
 elseif (is_single())
   $linklist = new SingleLinkList($content);
 elseif (is_feed())
   $linklist = new FeedLinkList($content);
 else
   $linklist = new BasicLinkList($content);

return $linklist->createLinkList();

}  //create_linklist


/* --------------------------------------------------------------------------- */
function llactivate() {

	if (get_option('linklist'))
	  return;
	$options = array('post_active'=>'on',	'page_active'=>'on', 'feed_active'=>'on',
					 'post_prolog'=>'Links in this post:',
					 'page_prolog'=>'Links on this page:',
					 'feed_prolog'=>'Links:',
					 'post_style'=>'rbol', 'page_style'=>'rbol', 'feed_style'=>'rbol',
					 'post_display'=>'', 'page_display'=>'',
					 'post_more'=>'on', 'page_more'=>'on',
					 'post_minlinks'=>0, 'page_minlinks'=>0, 'feed_minlinks'=>0,
					 'post_sep'=>', ', 'page_sep'=>', ', 'feed_sep'=>', ',
					 'post_sort'=>'on', 'page_sort'=>'on', 'feed_sort'=>'on',
					 'post_last'=>'on', 'page_last'=>'on'
					);
	update_option('linklist', $options);
}
/* --------------------------------------------------------------------------- */

if (is_admin()) {
  require_once('linklist-options.php');
	register_activation_hook( __FILE__, 'llactivate' );
}

add_filter('the_content', 'create_linklist');
