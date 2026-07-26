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

describe('linklist_should_show_editor_control', function () {
    it('is false when the type is inactive, regardless of theme or block presence', function () {
        stubLinklistOptions(['post_active' => '']);
        when('wp_is_block_theme')->justReturn(true);
        when('has_block')->justReturn(false);

        expect(linklist_should_show_editor_control('post', (object) []))->toBeFalse();
    });

    it('is true on a classic theme regardless of a bound post or block presence', function () {
        stubLinklistOptions(['post_active' => 'on']);
        when('wp_is_block_theme')->justReturn(false);

        expect(linklist_should_show_editor_control('post'))->toBeTrue();
        expect(linklist_should_show_editor_control('post', (object) []))->toBeTrue();
    });

    it('is false on a block theme with no bound post', function () {
        stubLinklistOptions(['post_active' => 'on']);
        when('wp_is_block_theme')->justReturn(true);

        expect(linklist_should_show_editor_control('post'))->toBeFalse();
    });

    it('is true on a block theme when the bound post does not contain the block', function () {
        stubLinklistOptions(['post_active' => 'on']);
        when('wp_is_block_theme')->justReturn(true);
        when('has_block')->justReturn(false);

        expect(linklist_should_show_editor_control('post', (object) []))->toBeTrue();
    });

    it('is false on a block theme when the bound post already contains the block', function () {
        stubLinklistOptions(['post_active' => 'on']);
        when('wp_is_block_theme')->justReturn(true);
        when('has_block')->justReturn(true);

        expect(linklist_should_show_editor_control('post', (object) []))->toBeFalse();
    });
});

describe('linklist_AddMetaBox', function () {
    it('only registers the meta box for active post types', function () {
        stubLinklistOptions(['post_active' => 'on', 'page_active' => '']);
        when('wp_is_block_theme')->justReturn(false);

        $registered = [];
        when('add_meta_box')->alias(function (...$args) use (&$registered) {
            $registered[] = $args[3];
        });

        linklist_AddMetaBox('post');

        expect($registered)->toBe(['post']);
    });

    it('registers the meta box for both types when both are active', function () {
        stubLinklistOptions(['post_active' => 'on', 'page_active' => 'on']);
        when('wp_is_block_theme')->justReturn(false);

        $registered = [];
        when('add_meta_box')->alias(function (...$args) use (&$registered) {
            $registered[] = $args[3];
        });

        linklist_AddMetaBox('post');

        expect($registered)->toBe(['post', 'page']);
    });

    it('skips the meta box on a block theme when the bound post already contains the block', function () {
        stubLinklistOptions(['post_active' => 'on', 'page_active' => 'on']);
        when('wp_is_block_theme')->justReturn(true);
        when('has_block')->justReturn(true);

        $registered = [];
        when('add_meta_box')->alias(function (...$args) use (&$registered) {
            $registered[] = $args[3];
        });

        linklist_AddMetaBox('post', (object) []);

        expect($registered)->toBe([]);
    });

    it('still registers the meta box on a block theme when the bound post does not contain the block', function () {
        stubLinklistOptions(['post_active' => 'on', 'page_active' => 'on']);
        when('wp_is_block_theme')->justReturn(true);
        when('has_block')->justReturn(false);

        $registered = [];
        when('add_meta_box')->alias(function (...$args) use (&$registered) {
            $registered[] = $args[3];
        });

        linklist_AddMetaBox('post', (object) []);

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
    it('renders the dropdown when the type is active on a classic theme', function () {
        stubLinklistOptions(['post_active' => 'on']);
        when('wp_is_block_theme')->justReturn(false);
        when('wp_nonce_field')->justReturn('');
        when('esc_html_e')->alias(function ($text) { echo $text; });

        ob_start();
        linklist_add_to_quick_edit_custom_box('linklist', 'post');
        $output = ob_get_clean();

        expect($output)->toContain('linklist-selectbox');
    });

    it('renders nothing when the type is inactive', function () {
        stubLinklistOptions(['page_active' => '']);
        when('wp_is_block_theme')->justReturn(false);
        when('wp_nonce_field')->justReturn('');

        ob_start();
        linklist_add_to_quick_edit_custom_box('linklist', 'page');
        $output = ob_get_clean();

        expect($output)->toBe('');
    });

    it('renders nothing on a block theme even when the type is active', function () {
        stubLinklistOptions(['post_active' => 'on']);
        when('wp_is_block_theme')->justReturn(true);
        when('wp_nonce_field')->justReturn('');

        ob_start();
        linklist_add_to_quick_edit_custom_box('linklist', 'post');
        $output = ob_get_clean();

        expect($output)->toBe('');
    });
});

describe('linklist_add_to_bulk_edit_custom_box', function () {
    it('renders the dropdown when the type is active on a classic theme', function () {
        stubLinklistOptions(['post_active' => 'on']);
        when('wp_is_block_theme')->justReturn(false);
        when('wp_nonce_field')->justReturn('');
        when('esc_html_e')->alias(function ($text) { echo $text; });

        ob_start();
        linklist_add_to_bulk_edit_custom_box('linklist', 'post');
        $output = ob_get_clean();

        expect($output)->toContain('linklist-bulk-selectbox');
    });

    it('renders nothing when the type is inactive', function () {
        stubLinklistOptions(['page_active' => '']);
        when('wp_is_block_theme')->justReturn(false);
        when('wp_nonce_field')->justReturn('');

        ob_start();
        linklist_add_to_bulk_edit_custom_box('linklist', 'page');
        $output = ob_get_clean();

        expect($output)->toBe('');
    });

    it('renders nothing on a block theme even when the type is active', function () {
        stubLinklistOptions(['post_active' => 'on']);
        when('wp_is_block_theme')->justReturn(true);
        when('wp_nonce_field')->justReturn('');

        ob_start();
        linklist_add_to_bulk_edit_custom_box('linklist', 'post');
        $output = ob_get_clean();

        expect($output)->toBe('');
    });
});
