<?php

namespace Momm\Foundation\PreparedQuery;

use PommProject\Foundation\PreparedQuery\PreparedQueryPooler as PommPreparedQueryPooler;

/**
 * The only reason why we'd need to override this is because of the
 * PreparedQuery::shutdown() function, sad kitten is even more sad than
 * in the previous class.
 */
class PreparedQueryPooler extends PommPreparedQueryPooler
{
    /**
     * {@inheritdoc}
     */
    public function createClient($sql)
    {
        return new PreparedQuery($sql);
    }
}
