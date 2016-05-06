<?php

namespace Momm\Foundation\PreparedQuery;

use PommProject\Foundation\PreparedQuery\PreparedQuery as PommPreparedQuery;

/**
 * The only reason why we'd need to override this is because of the shutdown()
 * function, sad kitten is sad.
 */
class PreparedQuery extends PommPreparedQuery
{
    private $is_prepared = false;

    public function prepare()
    {
        parent::prepare();

        $this->is_prepared = true;
    }

    /**
     * {@inheritdoc}
     */
    public function shutdown()
    {
        if ($this->is_prepared === true) {
            $this
                ->getSession()
                ->getConnection()
                ->executeAnonymousQuery(sprintf(
                    "deallocate prepare %s",
                    $this->getSession()->getConnection()->escapeIdentifier($this->getClientIdentifier())
                ))
            ;

            $this->is_prepared = false;
        }
    }
}
