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
 * Server-side render callback for the linklist/linklist block. Builds the
 * linklist HTML for the current post's content, applying any per-block
 * attribute overrides on top of the site-wide linklist settings.
 *
 * @param array $attributes Block attributes (style, prolog, sep, sort, minlinks).
 * @return string Linklist HTML, or '' if nothing should be rendered.
 */
function linklist_render_block( $attributes ) {
	global $post;

	if ( ! $post ) {
		return '';
	}

	if ( $post->post_type === 'page' ) {
		$linklist = new PageLinkList( $post->post_content );
	} else {
		$linklist = new SingleLinkList( $post->post_content );
	}

	if ( $linklist->stopCreate() ) {
		return '';
	}

	$overrides = array();

	if ( ! empty( $attributes['style'] ) ) {
		$overrides['style'] = $attributes['style'];
	}

	if ( ! empty( $attributes['prolog'] ) ) {
		$overrides['prolog'] = $attributes['prolog'];
	}

	if ( ! empty( $attributes['sep'] ) ) {
		$overrides['sep'] = $attributes['sep'];
	}

	if ( isset( $attributes['sort'] ) && $attributes['sort'] === 'on' ) {
		$overrides['sort'] = 'on';
	} elseif ( isset( $attributes['sort'] ) && $attributes['sort'] === 'off' ) {
		$overrides['sort'] = false;
	}

	if ( isset( $attributes['minlinks'] ) && (int) $attributes['minlinks'] >= 0 ) {
		$overrides['minlinks'] = (int) $attributes['minlinks'];
	}

	return $linklist->buildList( $overrides );
}
