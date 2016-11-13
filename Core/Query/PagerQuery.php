<?php

namespace Goat\Core\Query;

use Goat\Core\Client\ConnectionInterface;

/**
 * Represents a paginated query
 */
class SelectQuery
{
    private $connection;
    private $projection;
    private $sql;

    /**
     * Build a new query
     *
     * @param string $relation
     *   SQL from statement relation name
     * @param ConnectionInterface $connection
     *   Connection, so that it can really do query
     */
    public function __construct(
        $relation,
        ConnectionInterface $connection
    ) {
        $this->connection = $connection;
        $this->projection = new Projection();
        $this->where = new Where();
    }

    public function execute()
    {
        $sql = <<<EOT
select :projection from :relation where :where :suffix
EOT;
        $sql = strtr($sql, [
            ':projection' => $this->projection,
            ':relation'   => $this->projection->getRelation(),
        ]);
    }
}
