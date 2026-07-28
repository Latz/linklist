<?php
/* ---------------------------------------------------------------------------
render.php — registers the "Link List" Gutenberg block and renders it by
reusing the LinkList/SingleLinkList/PageLinkList classes from linklist.php.
--------------------------------------------------------------------------- */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Registers the linklist/linklist block from its build output.
 *
 * @listens init
 */
function linklist_register_block() {
	$build_dir = __DIR__ . '/build';

	if ( ! file_exists( $build_dir . '/block.json' ) ) {
		return;
	}

	register_block_type( $build_dir, array(
		'render_callback' => 'linklist_render_block',
	) );
}
add_action( 'init', 'linklist_register_block' );

/**
 * Maps an 'on'/'off' block attribute (as used by the sort/lastpage
 * SelectControls) to the truthy/false override value LinkList::buildList()
 * and stopCreate() expect, or null if the attribute is unset/anything else
 * (meaning "fall back to the stored option").
 *
 * @param array  $attributes Block attributes.
 * @param string $key Attribute key to read.
 * @return string|false|null 'on', false, or null.
 */
function linklist_map_onoff_attribute( $attributes, $key ) {
	if ( ! isset( $attributes[ $key ] ) ) {
		return null;
	}

	if ( $attributes[ $key ] === 'on' ) {
		return 'on';
	}

	if ( $attributes[ $key ] === 'off' ) {
		return false;
	}

	return null;
}

/**
 * Server-side render callback for the linklist/linklist block. Builds the
 * linklist HTML for the current post's content, applying any per-block
 * attribute overrides on top of the site-wide linklist settings.
 *
 * @param array $attributes Block attributes (style, prolog, sep, sort, minlinks, lastpage).
 * @return string Linklist HTML, or '' if nothing should be rendered.
 */
function linklist_render_block( $attributes ) {
	global $post;

	if ( ! $post ) {
		return '';
	}

	$is_page = $post->post_type === 'page';
	$linklist = $is_page
		? new PageLinkList( $post->post_content )
		: new SingleLinkList( $post->post_content );

	// "Last page only" (page_last) only exists on PageLinkList; it has no
	// effect on posts, so the override is only ever built for pages.
	$stop_overrides = array();
	if ( $is_page ) {
		$lastpage = linklist_map_onoff_attribute( $attributes, 'lastpage' );
		if ( null !== $lastpage ) {
			$stop_overrides['lastpage'] = $lastpage;
		}
	}

	if ( $linklist->stopCreate( $stop_overrides ) ) {
		return '';
	}

	return $linklist->buildList( linklist_build_overrides( $attributes ) );
}

/**
 * Copies $attributes[$key] into $overrides[$key] when it's set and non-empty,
 * leaving $overrides untouched otherwise (falling back to the stored option).
 *
 * @param array  $overrides  Overrides array, passed by reference.
 * @param array  $attributes Block attributes.
 * @param string $key        Attribute/override key to copy through.
 */
function linklist_add_passthrough_override( array &$overrides, array $attributes, $key ) {
	if ( ! empty( $attributes[ $key ] ) ) {
		$overrides[ $key ] = $attributes[ $key ];
	}
}

/**
 * Builds the buildList() override array from block attributes: style/prolog/
 * sep pass through when non-empty, sort maps through
 * linklist_map_onoff_attribute(), and minlinks passes through when it's not
 * the block default's unset sentinel.
 *
 * @param array $attributes Block attributes.
 * @return array Overrides for LinkList::buildList().
 */
function linklist_build_overrides( $attributes ) {
	$overrides = array();

	foreach ( array( 'style', 'prolog', 'sep' ) as $key ) {
		linklist_add_passthrough_override( $overrides, $attributes, $key );
	}

	$sort = linklist_map_onoff_attribute( $attributes, 'sort' );
	if ( null !== $sort ) {
		$overrides['sort'] = $sort;
	}

	if ( isset( $attributes['minlinks'] ) && (int) $attributes['minlinks'] >= 0 ) {
		$overrides['minlinks'] = (int) $attributes['minlinks'];
	}

	return $overrides;
}
