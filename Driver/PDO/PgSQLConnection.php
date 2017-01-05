<?php

namespace Goat\Driver\PDO;

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
    protected function writeCast($type)
    {
        return "?::%s";
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
