<?php

it('extracts links from the full post content when page_last is enabled', function () {
    global $post;
    $post = (object) [
        'ID' => 1,
        'post_content' => '<a href="https://full.test">Full</a>',
    ];
    stubLinklistOptions(['page_last' => 'on']);

    $list = new PageLinkList('<a href="https://current-page.test">Current page only</a>');

    expect($list->linkExtractor($list->content))->toBe([
        ['https://full.test', 'Full'],
    ]);
});

it('extracts links from only the current page content when page_last is disabled', function () {
    global $post;
    $post = (object) [
        'ID' => 1,
        'post_content' => '<a href="https://full.test">Full</a>',
    ];
    stubLinklistOptions(['page_last' => '']);

    $list = new PageLinkList('<a href="https://current-page.test">Current page only</a>');

    expect($list->linkExtractor($list->content))->toBe([
        ['https://current-page.test', 'Current page only'],
    ]);
});
