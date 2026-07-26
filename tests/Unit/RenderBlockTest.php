<?php

use function Brain\Monkey\Functions\when;

beforeEach(function () {
    when('apply_filters')->returnArg(2);
});

afterEach(function () {
    global $post, $numpages, $page;
    $post = null;
    $numpages = null;
    $page = null;
});

it('returns an empty string when there is no global post', function () {
    global $post;
    $post = null;

    expect(linklist_render_block([]))->toBe('');
});

it('renders a single post using SingleLinkList', function () {
    global $post;
    $post = (object) [
        'ID' => 1,
        'post_type' => 'post',
        'post_content' => '<a href="https://a.test">A</a>',
    ];
    stubLinklistOptions(['post_active' => 'on', 'post_more' => '', 'post_display' => '', 'post_minlinks' => 0]);

    expect(linklist_render_block([]))->toContain('https://a.test');
});

it('renders a page using PageLinkList', function () {
    global $post, $numpages, $page;
    $numpages = 1;
    $page = 1;
    $post = (object) [
        'ID' => 1,
        'post_type' => 'page',
        'post_content' => '<a href="https://a.test">A</a>',
    ];
    stubLinklistOptions(['page_active' => 'on', 'page_last' => 'on', 'page_minlinks' => 0]);

    expect(linklist_render_block([]))->toContain('https://a.test');
});

it('returns an empty string when stopCreate() short-circuits', function () {
    global $post, $numpages, $page;
    $numpages = 1;
    $page = 1;
    $post = (object) [
        'ID' => 1,
        'post_type' => 'page',
        'post_content' => '<a href="https://a.test">A</a>',
    ];
    stubLinklistOptions(['page_active' => '']);

    expect(linklist_render_block([]))->toBe('');
});

it('maps the style attribute to a buildList override', function () {
    global $post;
    $post = (object) [
        'ID' => 1,
        'post_type' => 'post',
        'post_content' => '<a href="https://a.test">A</a><a href="https://b.test">B</a>',
    ];
    stubLinklistOptions(['post_active' => 'on', 'post_more' => '', 'post_display' => '', 'post_minlinks' => 0, 'post_style' => 'rbol']);

    $html = linklist_render_block(['style' => 'rbul']);

    expect($html)->toContain('<ul>')->and($html)->not->toContain('<ol>');
});

it('maps the prolog attribute to a buildList override', function () {
    global $post;
    $post = (object) [
        'ID' => 1,
        'post_type' => 'post',
        'post_content' => '<a href="https://a.test">A</a>',
    ];
    stubLinklistOptions(['post_active' => 'on', 'post_more' => '', 'post_display' => '', 'post_minlinks' => 0, 'post_prolog' => 'Stored:']);

    expect(linklist_render_block(['prolog' => 'Custom prolog:']))->toContain('Custom prolog:');
});

it('maps the sep attribute together with an inline style', function () {
    global $post;
    $post = (object) [
        'ID' => 1,
        'post_type' => 'post',
        'post_content' => '<a href="https://a.test">A</a><a href="https://b.test">B</a>',
    ];
    stubLinklistOptions(['post_active' => 'on', 'post_more' => '', 'post_display' => '', 'post_minlinks' => 0]);

    $html = linklist_render_block(['style' => 'rbli', 'sep' => ' | ']);

    expect($html)->toContain('A</a> | <a href="https://b.test">B');
});

it('maps sort=off to disable sorting', function () {
    global $post;
    $post = (object) [
        'ID' => 1,
        'post_type' => 'post',
        'post_content' => '<a href="https://b.test">banana</a><a href="https://a.test">Apple</a>',
    ];
    stubLinklistOptions(['post_active' => 'on', 'post_more' => '', 'post_display' => '', 'post_minlinks' => 0, 'post_style' => 'rbul', 'post_sort' => 'on']);

    $html = linklist_render_block(['sort' => 'off']);

    expect(strpos($html, 'banana'))->toBeLessThan(strpos($html, 'Apple'));
});

it('maps a non-negative minlinks attribute to a buildList override', function () {
    global $post;
    $post = (object) [
        'ID' => 1,
        'post_type' => 'post',
        'post_content' => '<a href="https://a.test">A</a>',
    ];
    stubLinklistOptions(['post_active' => 'on', 'post_more' => '', 'post_display' => '', 'post_minlinks' => 0]);

    expect(linklist_render_block(['minlinks' => 5]))->toBe('');
});

it('ignores the block default minlinks sentinel of -1', function () {
    global $post;
    $post = (object) [
        'ID' => 1,
        'post_type' => 'post',
        'post_content' => '<a href="https://a.test">A</a>',
    ];
    stubLinklistOptions(['post_active' => 'on', 'post_more' => '', 'post_display' => '', 'post_minlinks' => 0]);

    expect(linklist_render_block(['minlinks' => -1]))->toContain('https://a.test');
});
