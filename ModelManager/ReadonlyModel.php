<?php

namespace Goat\ModelManager;

use Goat\Core\Client\ConnectionAwareInterface;
use Goat\Core\Client\ConnectionAwareTrait;
use Goat\Core\Client\ConnectionInterface;
use Goat\Core\Client\PagerResultIterator;
use Goat\Core\Query\ExpressionRaw;
use Goat\Core\Query\SelectQuery;
use Goat\Core\Query\Where;
use Goat\Core\Query\ExpressionColumn;

class ReadonlyModel implements ConnectionAwareInterface
{
    use ConnectionAwareTrait;

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
        $this->setConnection($connection);
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
     * Get relation alias for queries
     *
     * @return string
     */
    protected function getRelationAlias()
    {
        return 'entity';
    }

    /**
     * Create select query that matches the entity structure
     *
     * @return SelectQuery
     */
    protected function createSelectQuery()
    {
        $relation = $this->structure->getRelation();
        $relationAlias = $this->getRelationAlias();
        $select = $this->connection->select($relation, $relationAlias);

        foreach ($this->structure->getFieldNames() as $column) {
            $select->column(new ExpressionColumn($column, $relationAlias));
        }

        return $select;
    }

    /**
     * Find entities
     *
     * Return all elements from a relation.
     *
     * @param Where $where
     *   Either an array of values (as conditions) or a Where instance
     *
     * @return EntityInterface[]
     */
    public function findAll(Where $where = null)
    {
        $select = $this->createSelectQuery();

        if ($where) {
            $select->expression($where);
        }

        return new EntityIterator($select->execute(), $this->structure);
    }

    /**
     * From the given entity, get the primary key array
     *
     * @param EntityInterface $entity
     *
     * @return mixed[] $primaryKey
     */
    protected function getEntityPrimaryKey(EntityInterface $entity)
    {
        $definition = $this->structure->getPrimaryKey();

        if (!$definition) {
            throw new \LogicException("primary key is not defined, findByPK is disabled");
        }

        $ret = [];

        foreach ($definition as $name) {
            if ($entity->has($name)) {
                $ret[$name] = $entity->get($name);
            } else {
                $ret[$name] = null;
            }
        }

        return $ret;
    }

    /**
     * From the given primary key value, get the according Where instance
     *
     * @param mixed|mixed[] $primaryKey
     *
     * @return Where
     */
    protected function getPrimaryKeyWhere($primaryKey)
    {
        $definition = $this->structure->getPrimaryKey();

        if (!$definition) {
            throw new \LogicException("primary key is not defined, findByPK is disabled");
        }

        $where = new Where();

        if (!is_array($primaryKey)) {
            if (1 < count($definition)) {
                throw new \InvalidArgumentException(sprintf("primary key %d multiple columns, only 1 given", count($definition)));
            }
            $primaryKey = [reset($definition) => $primaryKey];
        } else {
            $this->checkPrimaryKey($primaryKey);
        }

        $relationAlias = $this->getRelationAlias();
        foreach ($primaryKey as $column => $value) {
            $where->isEqual(new ExpressionColumn($column, $relationAlias), $value);
        }

        return $where;
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
        $select = $this->createSelectQuery();
        $select->expression($this->getPrimaryKeyWhere($primaryKey));

        $result = new EntityIterator($select->execute(), $this->structure);

        foreach ($result as $entity) {
            return $entity;
        }
    }

    /**
     * Return the number of records matching a condition
     *
     * @param Where $where
     *
     * @return int
     */
    public function countWhere(Where $where = null)
    {
        $relation = $this->structure->getRelation();
        $alias = 'entity';
        $select = $this->connection->select($relation, $alias);

        if ($where) {
            $select->expression($where);
        }

        return $select
            ->column(new ExpressionRaw('count(*)'), 'count')
            ->execute()
            ->fetchField('count')
        ;
    }

    /**
     * Check if rows matching the given condition do exist or not
     *
     * @param mixed $where
     *
     * @return bool
     */
    public function existWhere(Where $where = null)
    {
        $relation = $this->structure->getRelation();
        $alias = 'entity';
        $select = $this->connection->select($relation, $alias);

        if ($where) {
            $select->expression($where);
        }

        return (bool)$select
            ->column(new ExpressionRaw('1'), 'one')
            ->range(1, 0)
            ->execute()
            ->fetchField()
        ;
    }

    /**
     * Alias of ::findAll() but it will get you a nice pager
     *
     * @param Where $where
     * @param string $limit
     * @param string $page
     */
    public function findAllWithPager(Where $where = null, $limit = 100, $page = 1)
    {
        $offset = $limit * ($page - 1);
        $select = $this->createSelectQuery()->range($limit, $offset);

        if ($where) {
            $select->expression($where);
        }

        return new PagerResultIterator(
            new EntityIterator($select->execute(), $this->structure),
            $this->countWhere($where),
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
