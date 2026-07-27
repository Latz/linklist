<?php

namespace Tests;

use Brain\Monkey;
use PHPUnit\Framework\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Monkey\setUp();

        // Default to "no current post" so LinkList's link-cache lookup
        // (keyed by post ID) is skipped by default; tests exercising the
        // cache explicitly stub get_the_ID()/get_post_meta()/etc.
        Monkey\Functions\when( 'get_the_ID' )->justReturn( false );
    }

    protected function tearDown(): void
    {
        Monkey\tearDown();
        parent::tearDown();
    }
}
