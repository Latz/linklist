<?php

beforeEach(function () {
    global $post;
    $post = (object) ['ID' => 1];
});

it('extracts a single link with its href and text', function () {
    stubLinklistOptions();
    $content = '<p>See <a href="https://example.com">Example</a> for more.</p>';
    $list = new SingleLinkList($content);

    expect($list->linkExtractor($content))->toBe([
        ['https://example.com', 'Example'],
    ]);
});

it('extracts multiple links in document order', function () {
    stubLinklistOptions();
    $content = '<a href="https://a.test">A</a> and <a href="https://b.test">B</a>';
    $list = new SingleLinkList($content);

    expect($list->linkExtractor($content))->toBe([
        ['https://a.test', 'A'],
        ['https://b.test', 'B'],
    ]);
});

it('returns an empty array when there are no links', function () {
    stubLinklistOptions();
    $content = '<p>No links here.</p>';
    $list = new SingleLinkList($content);

    expect($list->linkExtractor($content))->toBe([]);
});

it('ignores links that wrap a pure image', function () {
    stubLinklistOptions();
    $content = '<a href="https://img.test"><img src="pic.jpg"></a>';
    $list = new SingleLinkList($content);

    expect($list->linkExtractor($content))->toBe([]);
});

it('ignores the more-tag anchor for the current post', function () {
    global $post;
    $post = (object) ['ID' => 42];
    stubLinklistOptions();
    $content = '<a href="https://example.com/#more-42">Continue reading</a>';
    $list = new SingleLinkList($content);

    expect($list->linkExtractor($content))->toBe([]);
});

it('keeps a more-tag-shaped anchor belonging to a different post', function () {
    global $post;
    $post = (object) ['ID' => 42];
    stubLinklistOptions();
    $content = '<a href="https://example.com/#more-99">Continue reading</a>';
    $list = new SingleLinkList($content);

    expect($list->linkExtractor($content))->toBe([
        ['https://example.com/#more-99', 'Continue reading'],
    ]);
});

it('deduplicates identical href/text pairs', function () {
    stubLinklistOptions();
    $content = '<a href="https://example.com">Example</a> <a href="https://example.com">Example</a>';
    $list = new SingleLinkList($content);

    expect($list->linkExtractor($content))->toBe([
        ['https://example.com', 'Example'],
    ]);
});

it('keeps links with the same href but different text', function () {
    stubLinklistOptions();
    $content = '<a href="https://example.com">First</a> <a href="https://example.com">Second</a>';
    $list = new SingleLinkList($content);

    expect($list->linkExtractor($content))->toBe([
        ['https://example.com', 'First'],
        ['https://example.com', 'Second'],
    ]);
});

it('strips excluded divs before extracting links', function () {
    stubLinklistOptions(['exceptions' => ['skip-me']]);
    $content = '<div class="skip-me"><a href="https://excluded.test">Excluded</a></div>'
        . '<a href="https://kept.test">Kept</a>';
    $list = new SingleLinkList($content);

    expect($list->linkExtractor($content))->toBe([
        ['https://kept.test', 'Kept'],
    ]);
});

it('leaves content untouched when exceptions is empty', function () {
    stubLinklistOptions(['exceptions' => []]);
    $content = '<div class="skip-me"><a href="https://kept.test">Kept</a></div>';
    $list = new SingleLinkList($content);

    expect($list->linkExtractor($content))->toBe([
        ['https://kept.test', 'Kept'],
    ]);
});
