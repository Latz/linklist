<?php

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| Every test in tests/Unit runs against Tests\TestCase, which wires up
| Brain Monkey's per-test setUp()/tearDown() so WordPress functions used by
| the plugin can be stubbed/expected on a per-test basis.
|
*/

uses(Tests\TestCase::class)->in('Unit');

/*
|--------------------------------------------------------------------------
| Plugin bootstrap
|--------------------------------------------------------------------------
|
| linklist.php and render.php register hooks and (when is_admin()) load the
| settings screen as a side effect of being require_once'd. We load them
| exactly once here, inside a throwaway Brain Monkey context, stubbing the
| handful of WordPress functions that fire at load time so the require
| doesn't fatal. is_admin() is stubbed false so linklist-options.php never
| loads — none of the classes under test live there.
|
| linkExtractor() also depends on the real WP_HTML_Processor (WP core, not
| something Brain Monkey provides), so load that class family from the WP
| install this plugin sits inside before requiring the plugin itself.
|
*/

(function () {
    $wpIncludes = __DIR__ . '/../../../../wp-includes';

    foreach ( array(
        'class-wp-token-map.php',
    ) as $file ) {
        require_once $wpIncludes . '/' . $file;
    }

    foreach ( array(
        'class-wp-html-token.php',
        'class-wp-html-span.php',
        'class-wp-html-text-replacement.php',
        'class-wp-html-attribute-token.php',
        'class-wp-html-decoder.php',
        'class-wp-html-doctype-info.php',
        'class-wp-html-unsupported-exception.php',
        'class-wp-html-active-formatting-elements.php',
        'class-wp-html-open-elements.php',
        'class-wp-html-stack-event.php',
        'class-wp-html-processor-state.php',
        'class-wp-html-tag-processor.php',
        'class-wp-html-processor.php',
        'html5-named-character-references.php',
    ) as $file ) {
        require_once $wpIncludes . '/html-api/' . $file;
    }

    // WP_HTML_Processor/WP_HTML_Tag_Processor internally call a handful of
    // WordPress functions (translation/escaping helpers, not anything under
    // test) outside of any per-test Brain Monkey lifecycle, so they need
    // real, permanent definitions rather than per-test stubs.
    if ( ! function_exists( '__' ) ) {
        function __( $text, $domain = 'default' ) { return $text; }
    }
    if ( ! function_exists( '_doing_it_wrong' ) ) {
        function _doing_it_wrong( $function_name, $message, $version ) {}
    }
    if ( ! function_exists( 'esc_url' ) ) {
        function esc_url( $url ) { return $url; }
    }
    // get_extended() (WP core, wp-includes/post.php) has no WP-function
    // dependencies of its own, so it's cheaper to mirror its implementation
    // here than to pull in the rest of post.php's dependency chain.
    if ( ! function_exists( 'get_extended' ) ) {
        function get_extended( $post ) {
            if ( preg_match( '/<!--more(.*?)?-->/', $post, $matches ) ) {
                list($main, $extended) = explode( $matches[0], $post, 2 );
                $more_text = $matches[1];
            } else {
                $main = $post;
                $extended = '';
                $more_text = '';
            }
            $main = preg_replace( '/^[\s]*(.*)[\s]*$/', '\\1', $main );
            $extended = preg_replace( '/^[\s]*(.*)[\s]*$/', '\\1', $extended );
            $more_text = preg_replace( '/^[\s]*(.*)[\s]*$/', '\\1', $more_text );
            return array( 'main' => $main, 'extended' => $extended, 'more_text' => $more_text );
        }
    }

    Brain\Monkey\setUp();

    Brain\Monkey\Functions\when('is_admin')->justReturn(false);
    Brain\Monkey\Functions\when('get_option')->justReturn(false);
    Brain\Monkey\Functions\when('add_filter')->justReturn(true);
    Brain\Monkey\Functions\when('add_action')->justReturn(true);
    Brain\Monkey\Functions\when('register_activation_hook')->justReturn(true);

    require_once __DIR__ . '/../linklist.php';

    Brain\Monkey\tearDown();
})();

/*
|--------------------------------------------------------------------------
| Helpers
|--------------------------------------------------------------------------
*/

/**
 * Build a full linklist options array (as stored by get_option('linklist')),
 * merging in per-test overrides.
 */
function linklistOptions(array $overrides = []): array
{
    return array_merge([
        'post_active'   => 'on',
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
        'exceptions'    => [],
    ], $overrides);
}

/**
 * Stub get_option('linklist') to return the given options for the duration
 * of the current test.
 */
function stubLinklistOptions(array $overrides = []): array
{
    $options = linklistOptions($overrides);

    Brain\Monkey\Functions\when('get_option')
        ->justReturn($options);

    return $options;
}
