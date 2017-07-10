<?php

declare(strict_types=1);

namespace Goat\Mapper;

use Goat\Error\QueryError;
use Goat\Mapper\Error\EntityNotFoundError;
use Goat\Query\ExpressionRelation;
use Goat\Query\SelectQuery;
use Goat\Runner\PagerResultIterator;
use Goat\Runner\ResultIteratorInterface;
use Goat\Runner\RunnerAwareTrait;
use Goat\Runner\RunnerInterface;

/**
 * Table mapper is a simple model implementation that works on an arbitrary
 * select query.
 */
class SelectMapper implements MapperInterface
{
    use MapperTrait;
    use RunnerAwareTrait;

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
    public function __construct(RunnerInterface $runner, string $class, array $primaryKey, SelectQuery $query)
    {
        $this->runner = $runner;
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
        if (!$this->primaryKey) {
            throw new QueryError("mapper has no primary key defined");
        }

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
    public function getRunner() : RunnerInterface
    {
        return $this->runner;
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
    public function getRelation() : ExpressionRelation
    {
        return clone $this->select->getRelation();
    }

    /**
     * {@inheritdoc}
     */
    public function createSelect() : SelectQuery
    {
        return clone $this->select;
    }

    /**
     * {@inheritdoc}
     */
    public function hasPrimaryKey() : bool
    {
        return isset($this->primaryKey);
    }

    /**
     * {@inheritdoc}
     */
    public function getPrimaryKeyCount() : int
    {
        return isset($this->primaryKey) ? count($this->primaryKey) : 0;
    }

    /**
     * {@inheritdoc}
     */
    public function exists($criteria) : bool
    {
        // @todo replace columns using '1'
        $result = $this->findBy($criteria, 1, 0);

        return 0 < $result->count();
    }

    /**
     * {@inheritdoc}
     */
    public function findOne($id)
    {
        $select = $this->createSelect();

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
        $select = $this->createSelect();
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
        $select = $this->createSelect();

        return $select
            ->expression($this->createWhereWith($criteria))
            ->range($limit, $offset)
            ->execute([], ['class' => $this->class])
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function countBy($criteria) : int
    {
        $select = $this->createSelect();

        return $select
            ->expression($this->createWhereWith($criteria))
            ->getCountQuery()
            ->execute()
            ->fetchField()
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function paginate($criteria, int $limit = 0, int $page = 1) : PagerResultIterator
    {
        $select = $this->createSelect();

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
