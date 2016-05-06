<?php

namespace Momm\Foundation\Session;

/**
 * This implement is higly experimental and may suffer from security risks,
 * please do not use it lightly
 */
class PreparedReadyConnection extends Connection
{
    /**
     * {@inheritdoc}
     */
    public function sendPrepareQuery($identifier, $sql)
    {
        $sql = $this->convertStuffFromPgSyntaxToPdoSyntax($sql);
        $pdo = $this->getPdo();

        // PDO will emulate prepared queries, so we will directly hit MySQL
        // with its own syntax, not sure this will really avoid potential SQL
        // injection, but at the very least it will allow to avoid injection via
        // parameters
        $pdo
            ->query(sprintf(
                "prepare %s from %s",
                $this->escapeIdentifier($identifier),
                $pdo->quote($sql)
            ))
        ;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function sendExecuteQuery($identifier, array $parameters = [], $sql = '')
    {
        $pdo = $this->getPdo();

        $name = 'a';
        $map = [];
        foreach ($parameters as $value) {
            $escapedName = $this->escapeIdentifier($name);
            $pdo->query(sprintf("set @%s = %s", $escapedName, $pdo->quote($value)));
            $map[] = '@' . $escapedName;
            ++$name;
        }

        $statement = $this->pdo->query(sprintf("execute %s using %s", $this->escapeIdentifier($identifier), join(', ', $map)));

        return new ResultHandler($statement);
    }
}
