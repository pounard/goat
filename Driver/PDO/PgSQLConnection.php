<?php

namespace Goat\Driver\PDO;

use Goat\Core\Transaction\Transaction;
use Goat\Core\Error\QueryError;

class PgSQLConnection extends AbstractConnection
{
    /**
     * Send PDO configuration
     */
    protected function sendConfiguration(array $configuration)
    {
        $pdo = $this->getPdo();

        foreach ($configuration as $key => $value) {
            $pdo->query(sprintf(
                "SET %s TO %s",
                $this->escapeIdentifier($key),
                $this->escapeLiteral($value)
            ));
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    protected function writeCast($placeholder, $type)
    {
        // No surprises there, PostgreSQL is very straight-forward and just
        // uses the datatypes as it handles it. Very stable and robust.
        return sprintf("%s::%s", $placeholder, $type);
    }

    /**
     * {@inheritdoc}
     */
    public function supportsReturning()
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsDeferingConstraints()
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function doStartTransaction($isolationLevel = Transaction::REPEATABLE_READ)
    {
        $ret = new PgSQLTransaction($isolationLevel);
        $ret->setConnection($this);

        return $ret;
    }

    /**
     * {@inheritdoc}
     */
    public function truncateTables($relations)
    {
        if (!$relations) {
            throw new QueryError("cannot not truncate no tables");
        }

        $this->perform(sprintf("truncate %s", $this->escapeIdentifierList($relations)));
    }

    /**
     * {@inheritdoc}
     */
    public function escapeIdentifier($string)
    {
        return '"' . str_replace('"', '\\"', $string) . '"';
    }

    /**
     * {@inheritdoc}
     */
    public function setClientEncoding($encoding)
    {
        // https://www.postgresql.org/docs/9.3/static/multibyte.html#AEN34087
        // @todo investigate differences between versions
        $this
            ->getPdo()
            ->query(
                sprintf(
                    "SET CLIENT_ENCODING TO %s",
                    $this->escapeLiteral($encoding)
                )
            )
        ;
    }
}
