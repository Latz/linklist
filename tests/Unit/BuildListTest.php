<?php

use function Brain\Monkey\Functions\when;

beforeEach(function () {
    global $post;
    $post = (object) ['ID' => 1];
    when('apply_filters')->returnArg(2);
});

it('returns an empty string when there are no links', function () {
    stubLinklistOptions();
    $list = new SingleLinkList('<p>No links.</p>');

    expect($list->buildList())->toBe('');
});

it('renders an unordered list', function () {
    stubLinklistOptions(['post_style' => 'rbul', 'post_prolog' => 'Links:']);
    $content = '<a href="https://a.test">A</a><a href="https://b.test">B</a>';
    $list = new SingleLinkList($content);

    expect($list->buildList())->toBe(
        '<div class="linklist"><span class=linklistheader">Links:</span>'
        . '<ul><li><a href="https://a.test">A</a></li><li><a href="https://b.test">B</a></li></ul></div>'
    );
});

it('renders an ordered list', function () {
    stubLinklistOptions(['post_style' => 'rbol', 'post_prolog' => 'Links:']);
    $content = '<a href="https://a.test">A</a><a href="https://b.test">B</a>';
    $list = new SingleLinkList($content);

    expect($list->buildList())->toBe(
        '<div class="linklist"><span class=linklistheader">Links:</span>'
        . '<ol><li><a href="https://a.test">A</a></li><li><a href="https://b.test">B</a></li></ol></div>'
    );
});

it('renders a separator-joined inline list and trims the trailing separator', function () {
    stubLinklistOptions(['post_style' => 'rbli', 'post_sep' => ', ', 'post_prolog' => 'Links:']);
    $content = '<a href="https://a.test">A</a><a href="https://b.test">B</a>';
    $list = new SingleLinkList($content);

    expect($list->buildList())->toBe(
        '<div class="linklist"><span class=linklistheader">Links:</span>'
        . '<a href="https://a.test">A</a>, <a href="https://b.test">B</a></div>'
    );
});

it('does not render below the configured minimum link count', function () {
    stubLinklistOptions(['post_minlinks' => 2]);
    $content = '<a href="https://a.test">A</a>';
    $list = new SingleLinkList($content);

    expect($list->buildList())->toBe('');
});

it('renders when the link count meets the configured minimum', function () {
    stubLinklistOptions(['post_minlinks' => 2, 'post_style' => 'rbul', 'post_prolog' => 'Links:']);
    $content = '<a href="https://a.test">A</a><a href="https://b.test">B</a>';
    $list = new SingleLinkList($content);

    expect($list->buildList())->not->toBe('');
});

it('sorts links naturally and case-insensitively when sort is enabled', function () {
    stubLinklistOptions(['post_style' => 'rbul', 'post_prolog' => '', 'post_sort' => 'on']);
    $content = '<a href="https://b.test">banana</a><a href="https://a.test">Apple</a><a href="https://c.test">cherry</a>';
    $list = new SingleLinkList($content);

    $html = $list->buildList();

    $applePos = strpos($html, 'Apple');
    $bananaPos = strpos($html, 'banana');
    $cherryPos = strpos($html, 'cherry');

    expect($applePos)->toBeLessThan($bananaPos)
        ->and($bananaPos)->toBeLessThan($cherryPos);
});

it('keeps original order when sort is disabled', function () {
    stubLinklistOptions(['post_style' => 'rbul', 'post_prolog' => '', 'post_sort' => '']);
    $content = '<a href="https://b.test">banana</a><a href="https://a.test">Apple</a>';
    $list = new SingleLinkList($content);

    $html = $list->buildList();

    expect(strpos($html, 'banana'))->toBeLessThan(strpos($html, 'Apple'));
});

it('applies the linklist filter to the assembled HTML', function () {
    stubLinklistOptions(['post_style' => 'rbul', 'post_prolog' => '']);
    when('apply_filters')->justReturn('FILTERED');
    $content = '<a href="https://a.test">A</a>';
    $list = new SingleLinkList($content);

    expect($list->buildList())->toBe('FILTERED');
});

it('prefers overrides over stored options per-key', function () {
    stubLinklistOptions(['post_style' => 'rbol', 'post_prolog' => 'Stored:']);
    $content = '<a href="https://a.test">A</a><a href="https://b.test">B</a>';
    $list = new SingleLinkList($content);

    $html = $list->buildList(['style' => 'rbul']);

    expect($html)->toContain('<ul>')
        ->and($html)->not->toContain('<ol>')
        ->and($html)->toContain('Stored:');
});

it('falls back to the stored option when an override is empty or null', function () {
    stubLinklistOptions(['post_style' => 'rbol', 'post_prolog' => 'Stored:']);
    $content = '<a href="https://a.test">A</a><a href="https://b.test">B</a>';
    $list = new SingleLinkList($content);

    $html = $list->buildList(['style' => '', 'prolog' => null]);

    expect($html)->toContain('<ol>')
        ->and($html)->toContain('Stored:');
});

it('allows an explicit false override to disable sorting', function () {
    stubLinklistOptions(['post_style' => 'rbul', 'post_prolog' => '', 'post_sort' => 'on']);
    $content = '<a href="https://b.test">banana</a><a href="https://a.test">Apple</a>';
    $list = new SingleLinkList($content);

    $html = $list->buildList(['sort' => false]);

    expect(strpos($html, 'banana'))->toBeLessThan(strpos($html, 'Apple'));
});

it('allows a minlinks override of zero to take precedence', function () {
    stubLinklistOptions(['post_minlinks' => 5]);
    $content = '<a href="https://a.test">A</a>';
    $list = new SingleLinkList($content);

    expect($list->buildList(['minlinks' => 0]))->not->toBe('');
});
