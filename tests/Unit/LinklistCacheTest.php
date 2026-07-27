<?php

use function Brain\Monkey\Functions\expect as expectFunction;
use function Brain\Monkey\Functions\when;

describe('LinkList link extraction cache', function () {
    beforeEach(function () {
        global $post;
        $post = (object) ['ID' => 42, 'post_content' => ''];
        when('apply_filters')->returnArg(2);
        when('get_the_ID')->justReturn(42);
    });

    it('reuses a cached links array instead of re-parsing the content', function () {
        // content has no real links, but the "cache" claims one -- if this
        // shows up in the output, buildList() used the cache rather than
        // re-running linkExtractor() on $content.
        when('get_post_meta')->justReturn([['https://cached.test', 'Cached']]);
        stubLinklistOptions(['post_style' => 'rbul', 'post_prolog' => '']);

        $list = new SingleLinkList('<p>No real links here.</p>');

        expectFunction('update_post_meta')->never();

        $html = $list->buildList();

        expect($html)->toContain('https://cached.test');
    });

    it('computes and stores the links array on a cache miss', function () {
        when('get_post_meta')->justReturn('');
        stubLinklistOptions(['post_style' => 'rbul', 'post_prolog' => '']);

        $stored = null;
        when('update_post_meta')->alias(function ($post_id, $key, $value) use (&$stored) {
            $stored = $value;
            return true;
        });

        $content = '<a href="https://fresh.test">Fresh</a>';
        $list = new SingleLinkList($content);
        $list->buildList();

        expect($stored)->toBe([['https://fresh.test', 'Fresh']]);
    });

    it('skips the cache entirely when there is no current post', function () {
        when('get_the_ID')->justReturn(false);
        when('get_post_meta')->justReturn('');
        stubLinklistOptions(['post_style' => 'rbul', 'post_prolog' => '']);

        expectFunction('get_post_meta')->never();
        expectFunction('update_post_meta')->never();

        $content = '<a href="https://fresh.test">Fresh</a>';
        $list = new SingleLinkList($content);

        expect($list->buildList())->toContain('https://fresh.test');
    });
});

describe('linklist_invalidate_linklist_cache', function () {
    it('deletes the cached links for the saved post', function () {
        $deleted = null;
        when('delete_post_meta')->alias(function ($post_id, $key) use (&$deleted) {
            $deleted = [$post_id, $key];
            return true;
        });

        linklist_invalidate_linklist_cache(42);

        expect($deleted)->toBe([42, '_linklist_cache']);
    });
});

describe('linklist_post_has_block', function () {
    it('memoizes has_block() per post object within the request', function () {
        $post = (object) ['ID' => 7];
        expectFunction('has_block')->once()->andReturn(true);

        $first = linklist_post_has_block($post);
        $second = linklist_post_has_block($post);

        expect($first)->toBeTrue()
            ->and($second)->toBeTrue();
    });

    it('does not conflate two different post objects', function () {
        $postA = (object) ['ID' => 1];
        $postB = (object) ['ID' => 2];

        when('has_block')->alias(fn ($block, $post) => $post === $postB);

        expect(linklist_post_has_block($postA))->toBeFalse();
        expect(linklist_post_has_block($postB))->toBeTrue();
    });
});
