<?php

namespace Goat\Core\Query;

final class Query
{
    const JOIN_NATURAL = 1;
    const JOIN_LEFT = 2;
    const JOIN_LEFT_OUTER = 3;
    const JOIN_INNER = 4;

    const ORDER_ASC = 1;
    const ORDER_DESC = 2;
    const NULL_IGNORE = 0;
    const NULL_LAST = 1;
    const NULL_FIRST = 2;

    private function __construct()
    {
    }
}
