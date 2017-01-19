<?php

declare(strict_types=1);

namespace Goat\Mapper;

use Goat\Core\Client\ConnectionAwareTrait;
use Goat\Core\Client\PagerResultIterator;
use Goat\Core\Client\ResultIteratorInterface;
use Goat\Core\Error\QueryError;
use Goat\Core\Query\Query;
use Goat\Core\Query\SelectQuery;
use Goat\Mapper\Error\EntityNotFoundError;
use Goat\Core\Client\ConnectionInterface;

/**
 * Table mapper is a simple model implementation that works on an arbitrary
 * select query.
 */
class SelectMapper implements MapperInterface
{
    use ConnectionAwareTrait;
    use MapperTrait;

    /**
     * @var string
     */
    private $class;

    /**
     * @var SelectQuery
     */
    private $select;

    /**
     * @var string[]
     */
    private $primaryKey = [];

    /**
     * Default constructor
     *
     * @param string $class
     *   Default class to use for hydration
     * @param string[] $primaryKey
     *   Primary key column names
     * @param SelectQuery $query
     *   Select query that loads entities
     */
    public function __construct(ConnectionInterface $connection, string $class, array $primaryKey, SelectQuery $query)
    {
        $this->connection = $connection;
        $this->class = $class;
        $this->primaryKey = $primaryKey;
        $this->select = $query;
    }

    /**
     * Expand primary key item
     *
     * @param mixed $id
     *
     * @return array
     *   Keys are column names, values
     */
    private function expandPrimaryKey($id) : array
    {
        if (!is_array($id)) {
            $id = [$id];
        }
        if (count($id) !== count($this->primaryKey)) {
            throw new QueryError(sprintf("column count mismatch between primary key and user input, awaiting columns: '%s'", implode("', '", $this->primaryKey)));
        }

        return array_combine($this->primaryKey, $id);
    }

    /**
     * {@inheritdoc}
     */
    public function getConnection() : ConnectionInterface
    {
        return $this->connection;
    }

    /**
     * {@inheritdoc}
     */
    public function getClassName() : string
    {
        return $this->class;
    }

    /**
     * {@inheritdoc}
     */
    public function findOne($id)
    {
        $select = clone $this->select;

        foreach ($this->expandPrimaryKey($id) as $column => $value) {
            $select->condition($column, $value);
        }

        $result = $select->range(1, 0)->execute([], ['class' => $this->class]);

        if ($result->count()) {
            return $result->fetch();
        }

        throw new EntityNotFoundError();
    }

    /**
     * {@inheritdoc}
     */
    public function findAll(array $idList, bool $raiseErrorOnMissing = false) : ResultIteratorInterface
    {
        $select = clone $this->select;
        $orWhere = $select->getWhere()->or();

        foreach ($idList as $id) {
            $pkWhere = $orWhere->and();
            foreach ($this->expandPrimaryKey($id) as $column => $value) {
                $pkWhere->condition($column, $value);
            }
        }

        return $select->execute([], ['class' => $this->class]);
    }

    /**
     * {@inheritdoc}
     */
    public function findFirst($criteria, bool $raiseErrorOnMissing = false)
    {
        $result = $this->findBy($criteria, 1, 0);

        if ($result->count()) {
            return $result->fetch();
        }

        if ($raiseErrorOnMissing) {
            throw new EntityNotFoundError();
        }
    }

    /**
     * {@inheritdoc}
     */
    public function findBy($criteria, int $limit = 0, int $offset = 0) : ResultIteratorInterface
    {
        $select = clone $this->select;

        return $select
            ->expression($this->createWhereWith($criteria))
            ->range($limit, $offset)
            ->execute([], ['class' => $this->class])
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function paginate($criteria, int $limit = 0, int $page = 1) : PagerResultIterator
    {
        $select = clone $this->select;

        $select
            ->expression(
                $this->createWhereWith($criteria)
            )
            ->range($limit, ($page - 1) * $limit)
        ;

        $total = $select->getCountQuery()->execute()->fetchField();
        $result = $select->execute([], ['class' => $this->class]);

        return new PagerResultIterator($result, $total, $limit, $page);
    }
}
