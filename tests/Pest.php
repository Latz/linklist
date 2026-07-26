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
| doesn't fatal. is_admin() is stubbed false so linklist-options.php (and
| its Yoast_Plugin_Admin dependency) never loads — none of the classes under
| test live there.
|
*/

(function () {
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
