<?php

it('compares link labels case-insensitively using natural order', function () {
    stubLinklistOptions();
    $list = new SingleLinkList('content');

    expect($list->linklist_sorter(['#', 'apple'], ['#', 'Banana']))->toBeLessThan(0)
        ->and($list->linklist_sorter(['#', 'Banana'], ['#', 'apple']))->toBeGreaterThan(0)
        ->and($list->linklist_sorter(['#', 'Apple'], ['#', 'apple']))->toBe(0);
});

it('orders numeric-looking labels naturally', function () {
    stubLinklistOptions();
    $list = new SingleLinkList('content');

    expect($list->linklist_sorter(['#', 'item2'], ['#', 'item10']))->toBeLessThan(0);
});
