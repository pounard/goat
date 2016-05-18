<?php

namespace Momm\ModelManager;

use Momm\Core\Client\ConnectionInterface;
use Momm\Core\Query\Where;
use Momm\Core\Query\Pager;

class ReadonlyModel
{
    /**
     * @var ConnectionInterface
     */
    protected $connection;

    /**
     * @var EntityStructure
     */
    protected $structure;

    /**
     * Default constructor
     *
     * @param EntityStructure $structure
     */
    public function __construct(ConnectionInterface $connection, EntityStructure $structure)
    {
        $this->connection = $connection;
        $this->structure = $structure;
    }

    /**
     * Get structure
     *
     * @return EntityStructure
     */
    public function getStructure()
    {
        return $this->structure;
    }

    /**
     * Creates projection based on structure fields
     *
     * @return Projection
     */
    final public function createProjection()
    {
        return new Projection($this->structure);
    }

    /**
     * Execute query and return entities
     *
     * @param string $sql
     * @param array $values
     * @param Projection $projection
     *
     * @return EntityIterator|EntityInterface[]
     */
    protected function query($sql, array $values = [], Projection $projection = null)
    {
        $result = $this->connection->query($sql, $values);

        return new EntityIterator($result, $this->structure);
    }

    /**
     * Find entities using SQL suffix
     *
     * Return all elements from a relation. 
     *
     * @param Where $where
     *   Either an array of values (as conditions) or a Where instance
     * @param string $suffix
     *   If given, it is append to the query. This is mainly useful for
     *   "order by" statements.
     *   NOTE: suffix is inserted as is with NO ESCAPING. DO NOT use it to place
     *   "where" condition nor any untrusted params.
     *
     * @return EntityInterface[]
     */
    public function findAll(Where $where = null, $suffix = '')
    {
        if (!$where) {
            $where = new Where();
        }

        $sql = strtr(
            "select :projection from :relation where :condition :suffix",
            [
                ':projection' => $this->createProjection(),
                ':relation'   => $this->getStructure()->getRelation(),
                ':condition'  => $where,
                ':suffix'     => $suffix,
            ]
        );

        return $this->query($sql, $where->getArguments());
    }

    /**
     * Load single instance using primary key
     *
     * @param mixed|mixed[] $primaryKey
     *   If primary key is a single column, you may pass a single value here
     *   but for all other cases you must pass an array of values, keyed using
     *   the primary key column names
     *
     * @return EntityInterface
     */
    public function findByPK($primaryKey)
    {
        $definition = $this->structure->getPrimaryKey();
        $where = new Where();

        if (!is_array($primaryKey)) {
            if (1 < count($definition)) {
                throw new \InvalidArgumentException(sprintf("primary key %d multiple columns, only 1 given", count($definition)));
            }

            $where = new Where();
            $where->isEqual(reset($definition), $primaryKey);
        } else {
            $this->checkPrimaryKey($primaryKey);

            foreach ($primaryKey as $column => $value) {
                $where->isEqual($column, $value);
            }
        }

        $sql = strtr(
            "select :projection from :relation where :condition limit 1 offset 0",
            [
                ':projection' => $this->createProjection(),
                ':relation'   => $this->getStructure()->getRelation(),
                ':condition'  => $where,
            ]
        );

        $result = $this->query($sql, $where->getArguments());

        foreach ($result as $entity) {
            return $entity;
        }
    }

    /**
     * Return the number of records matching a condition
     *
     * @param Where $where
     * @param string $suffix
     *
     * @return int
     */
    public function countWhere(Where $where = null, $suffix = '')
    {
        if (!$where) {
            $where = new Where();
        }

        $sql = strtr(
            "select count(*) as result from :relation where :condition :suffix",
            [
                ':relation'   => $this->getStructure()->getRelation(),
                ':condition'  => $where,
                ':suffix'     => $suffix,
            ]
        );

        return (int)$this->query($sql, $where->getArguments())->fetchField();
    }

    /**
     * Check if rows matching the given condition do exist or not
     *
     * @param mixed $where
     * @param array $values
     *
     * @return bool
     */
    public function existWhere(Where $where = null, $suffix = '')
    {
        if (!$where) {
            $where = new Where();
        }

        $sql = strtr(
            "select exists (select 1 from :relation where :condition) as result",
            [
                ':relation'   => $this->getStructure()->getRelation(),
                ':condition'  => $where,
            ]
        );

        return (bool)$this->query($sql, $where->getArguments())->fetchField();
    }

    /**
     * Alias of ::findAll() but it will get you a nice pager
     *
     * @param Where $where
     * @param string $suffix
     * @param string $limit
     * @param string $page
     */
    public function findAllWithPager(Where $where = null, $suffix = '', $limit = 100, $page = 1)
    {
        return new Pager(
            $this->findAll($where, $suffix . sprintf(' limit %d offset %d', $limit, $limit * ($page - 1))),
            $this->countWhere($where, $suffix),
            $limit,
            $page
        );
    }

    /**
     * Check if the given values fully describe a primary key
     *
     * @param array $values
     *
     * @throws \InvalidArgumentException
     */
    protected function checkPrimaryKey(array $values)
    {
        $primaryKey = $this->structure->getPrimaryKey();

        if (!$primaryKey) {
            throw new \InvalidArgumentException("structure has no primary key");
        }
        if (count($primaryKey) !== count($values)) {
            throw new \InvalidArgumentException("primary key count mismatch");
        }

        foreach ($this->structure->getPrimaryKey() as $key) {
            if (!isset($values[$key])) {
                throw new \InvalidArgumentException(
                    sprintf(
                        "key '%s' is missing to fully describes the primary key (%s).",
                        $key,
                        implode(', ', $primaryKey)
                    )
                );
            }
        }

        return $this;
    }
}
