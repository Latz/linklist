<?php

describe('BasicLinkList::stopCreate', function () {
    beforeEach(function () {
        global $post;
        $post = (object) ['post_content' => 'Just some text.'];
    });

    it('stops when posts are deactivated', function () {
        stubLinklistOptions(['post_active' => '']);
        $list = new BasicLinkList('content');

        expect($list->stopCreate())->toBe(1);
    });

    it('stops when a more-tag is present and post_more is enabled', function () {
        global $post;
        $post = (object) ['post_content' => 'Intro text <!--more--> rest of post.'];
        stubLinklistOptions(['post_active' => 'on', 'post_more' => 'on']);
        $list = new BasicLinkList('content');

        expect($list->stopCreate())->toBe(1);
    });

    it('continues when a more-tag is present but post_more is disabled', function () {
        global $post;
        $post = (object) ['post_content' => 'Intro text <!--more--> rest of post.'];
        stubLinklistOptions(['post_active' => 'on', 'post_more' => '', 'post_display' => '']);
        $list = new BasicLinkList('content');

        expect($list->stopCreate())->toBe(0);
    });

    it('stops when restricted to single post display', function () {
        stubLinklistOptions(['post_active' => 'on', 'post_more' => '', 'post_display' => 'on']);
        $list = new BasicLinkList('content');

        expect($list->stopCreate())->toBe(1);
    });

    it('continues by default', function () {
        stubLinklistOptions(['post_active' => 'on', 'post_more' => '', 'post_display' => '']);
        $list = new BasicLinkList('content');

        expect($list->stopCreate())->toBe(0);
    });
});

describe('PageLinkList::stopCreate', function () {
    it('stops when pages are deactivated', function () {
        global $numpages, $page;
        $numpages = 1;
        $page = 1;
        stubLinklistOptions(['page_active' => '']);
        $list = new PageLinkList('content');

        expect($list->stopCreate())->toBe(1);
    });

    it('stops on a non-last page of a split post when page_last is enabled', function () {
        global $numpages, $page;
        $numpages = 3;
        $page = 1;
        stubLinklistOptions(['page_active' => 'on', 'page_last' => 'on']);
        $list = new PageLinkList('content');

        expect($list->stopCreate())->toBe(1);
    });

    it('continues on the last page of a split post when page_last is enabled', function () {
        global $numpages, $page;
        $numpages = 3;
        $page = 3;
        stubLinklistOptions(['page_active' => 'on', 'page_last' => 'on']);
        $list = new PageLinkList('content');

        expect($list->stopCreate())->toBe(0);
    });

    it('continues on a split post when page_last is disabled', function () {
        global $numpages, $page;
        $numpages = 3;
        $page = 1;
        stubLinklistOptions(['page_active' => 'on', 'page_last' => '']);
        $list = new PageLinkList('content');

        expect($list->stopCreate())->toBe(0);
    });

    it('continues on a single (non-split) page', function () {
        global $numpages, $page;
        $numpages = 1;
        $page = 1;
        stubLinklistOptions(['page_active' => 'on', 'page_last' => 'on']);
        $list = new PageLinkList('content');

        expect($list->stopCreate())->toBe(0);
    });
});

describe('FeedLinkList::stopCreate', function () {
    it('stops when feeds are deactivated', function () {
        stubLinklistOptions(['feed_active' => '']);
        $list = new FeedLinkList('content');

        expect($list->stopCreate())->toBe(1);
    });

    it('continues when feeds are activated', function () {
        stubLinklistOptions(['feed_active' => 'on']);
        $list = new FeedLinkList('content');

        expect($list->stopCreate())->toBe(0);
    });
});
