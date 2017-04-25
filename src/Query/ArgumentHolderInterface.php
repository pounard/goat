<?php

declare(strict_types=1);

namespace Goat\Query;

interface ArgumentHolderInterface
{
    /**
     * Get query arguments
     *
     * Those arguments will be later converted by the driven prior to the
     * query being sent to the backend; for this to work type cast information
     * must lie into the query
     *
     * @return ArgumentBag
     */
    public function getArguments() : ArgumentBag;
}
