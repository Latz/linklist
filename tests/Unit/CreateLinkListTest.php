<?php

use function Brain\Monkey\Functions\when;

beforeEach(function () {
    global $post;
    $post = (object) ['ID' => 1, 'post_content' => ''];
    when('apply_filters')->returnArg(2);
    when('get_the_ID')->justReturn(1);
});

it('returns the content unchanged when per-post display is disabled', function () {
    when('get_post_meta')->justReturn('0');
    stubLinklistOptions(['post_active' => 'on', 'post_more' => '', 'post_display' => '']);
    $content = '<a href="https://a.test">A</a>';
    $list = new SingleLinkList($content);

    expect($list->createLinkList())->toBe($content);
});

it('returns the content unchanged when stopCreate() short-circuits', function () {
    when('get_post_meta')->justReturn('');
    stubLinklistOptions(['post_active' => '']);
    $content = '<a href="https://a.test">A</a>';
    $list = new BasicLinkList($content);

    expect($list->createLinkList())->toBe($content);
});

it('returns the content unchanged when buildList() has nothing to render', function () {
    when('get_post_meta')->justReturn('');
    stubLinklistOptions(['post_active' => 'on', 'post_more' => '', 'post_display' => '', 'post_minlinks' => 5]);
    $content = '<a href="https://a.test">A</a>';
    $list = new SingleLinkList($content);

    expect($list->createLinkList())->toBe($content);
});

it('appends the rendered list to the content', function () {
    when('get_post_meta')->justReturn('');
    stubLinklistOptions([
        'post_active' => 'on',
        'post_more' => '',
        'post_display' => '',
        'post_minlinks' => 0,
        'post_style' => 'rbul',
        'post_prolog' => 'Links:',
    ]);
    $content = '<a href="https://a.test">A</a>';
    $list = new SingleLinkList($content);

    $result = $list->createLinkList();

    expect($result)->toStartWith($content)
        ->and($result)->toContain('<div class="linklist">')
        ->and(strlen($result))->toBeGreaterThan(strlen($content));
});
