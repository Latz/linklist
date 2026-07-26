<?php

use function Brain\Monkey\Functions\when;

describe('linklist_is_type_active', function () {
    it('is true when the option for the type is on', function () {
        stubLinklistOptions(['post_active' => 'on']);

        expect(linklist_is_type_active('post'))->toBeTrue();
    });

    it('is false when the option for the type is off', function () {
        stubLinklistOptions(['page_active' => '']);

        expect(linklist_is_type_active('page'))->toBeFalse();
    });

    it('is false for a post type with no matching option key', function () {
        stubLinklistOptions();

        expect(linklist_is_type_active('attachment'))->toBeFalse();
    });
});

describe('linklist_AddMetaBox', function () {
    it('only registers the meta box for active post types', function () {
        stubLinklistOptions(['post_active' => 'on', 'page_active' => '']);

        $registered = [];
        when('add_meta_box')->alias(function (...$args) use (&$registered) {
            $registered[] = $args[3];
        });

        linklist_AddMetaBox();

        expect($registered)->toBe(['post']);
    });

    it('registers the meta box for both types when both are active', function () {
        stubLinklistOptions(['post_active' => 'on', 'page_active' => 'on']);

        $registered = [];
        when('add_meta_box')->alias(function (...$args) use (&$registered) {
            $registered[] = $args[3];
        });

        linklist_AddMetaBox();

        expect($registered)->toBe(['post', 'page']);
    });
});

describe('linklist_add_posts_column', function () {
    it('adds the column when the type is active', function () {
        stubLinklistOptions(['post_active' => 'on']);

        $columns = linklist_add_posts_column(['title' => 'Title'], 'post');

        expect($columns)->toHaveKey('linklist');
    });

    it('leaves the columns untouched when the type is inactive', function () {
        stubLinklistOptions(['page_active' => '']);

        $columns = linklist_add_posts_column(['title' => 'Title'], 'page');

        expect($columns)->not->toHaveKey('linklist');
    });
});

describe('linklist_add_to_quick_edit_custom_box', function () {
    it('renders the dropdown when the type is active', function () {
        stubLinklistOptions(['post_active' => 'on']);
        when('wp_nonce_field')->justReturn('');
        when('esc_html_e')->alias(function ($text) { echo $text; });

        ob_start();
        linklist_add_to_quick_edit_custom_box('linklist', 'post');
        $output = ob_get_clean();

        expect($output)->toContain('linklist-selectbox');
    });

    it('renders nothing when the type is inactive', function () {
        stubLinklistOptions(['page_active' => '']);
        when('wp_nonce_field')->justReturn('');

        ob_start();
        linklist_add_to_quick_edit_custom_box('linklist', 'page');
        $output = ob_get_clean();

        expect($output)->toBe('');
    });
});

describe('linklist_add_to_bulk_edit_custom_box', function () {
    it('renders the dropdown when the type is active', function () {
        stubLinklistOptions(['post_active' => 'on']);
        when('wp_nonce_field')->justReturn('');
        when('esc_html_e')->alias(function ($text) { echo $text; });

        ob_start();
        linklist_add_to_bulk_edit_custom_box('linklist', 'post');
        $output = ob_get_clean();

        expect($output)->toContain('linklist-bulk-selectbox');
    });

    it('renders nothing when the type is inactive', function () {
        stubLinklistOptions(['page_active' => '']);
        when('wp_nonce_field')->justReturn('');

        ob_start();
        linklist_add_to_bulk_edit_custom_box('linklist', 'page');
        $output = ob_get_clean();

        expect($output)->toBe('');
    });
});
