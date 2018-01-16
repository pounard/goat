<?php

declare(strict_types=1);

namespace Goat\Mapper;

use Goat\Error\GoatError;
use Goat\Error\QueryError;
use Goat\Mapper\Error\EntityNotFoundError;
use Goat\Query\Expression;
use Goat\Query\ExpressionRelation;
use Goat\Query\SelectQuery;
use Goat\Query\Where;
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
    final protected function expandPrimaryKey($id) : array
    {
        if (!$this->primaryKey) {
            throw new QueryError("mapper has no primary key defined");
        }

        if (!is_array($id)) {
            $id = [$id];
        }
        if (count($id) !== count($this->primaryKey)) {
            throw new QueryError(sprintf("column count mismatch between primary key and user input, awaiting columns (in that order): '%s'", implode("', '", $this->primaryKey)));
        }

        $ret = [];

        $relationAlias = $this->getRelation()->getAlias();
        foreach (array_combine($this->primaryKey, $id) as $column => $value) {
            // Mapper can choose to actually already have prefixed the column
            // primary key using the alias, let's cover this use case too: this
            // might happen if either the original select query do need
            // deambiguation from the start, or if the API user was extra
            // precautionous.
            if (false === strpos($column, '.')) {
                $ret[$relationAlias.'.'.$column] = $value;
            } else {
                $ret[$column] = $value;
            }
        }

        return $ret;
    }

    /**
     * Build where from criteria
     *
     * @param array|Expression|Where $criteria
     *   This value might be either one of:
     *     - a simple key-value array that will be translated into a where
     *       clause using the AND statement, values can be anything including
     *       Expression or Where instances, if keys are integers, values must
     *       will be set using Where::expression() instead of Where::condition()
     *     - a Expression instance
     *     - an array of Expression instances
     *     - a Where instance
     *
     * @return Where
     */
    final protected function createWhereWith($criteria) : Where
    {
        if (!$criteria) {
            return new Where();
        }
        if ($criteria instanceof Where) {
            return $criteria;
        }
        if ($criteria instanceof Expression) {
            return (new Where())->expression($criteria);
        }

        if (is_array($criteria)) {
            $where = new Where();

            foreach ($criteria as $column => $value) {
                if (is_int($column)) {
                    $where->expression($value);
                } else {
                    // Because mappers might attempt to join with other tables
                    // they can arbitrarily use a table alias for the main
                    // relation: user may not know it, and just use field
                    // names here - if no column alias is set, arbitrarily
                    // prefix them with the relation alias.
                    // @todo
                    //   - does it really worth it ?
                    //   - if there is more than one alias, how to deal with
                    //     the fact that user might want to filter using
                    //     another column table ?
                    //   - in the end, if ok with those questions, implement
                    //     it and document it.

                    if (is_null($value)) {
                        $where->isNull($column);
                    } else {
                        $where->condition($column, $value);
                    }
                }
            }

            return $where;
        }

        throw new QueryError("criteria must be an instance of Where, Expression, or an key-value pairs array where keys are columns names and values are column value");
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
    public function getPrimaryKey() : array
    {
        if (!isset($this->primaryKey)) {
            throw new GoatError(sprintf("%s mapper for entity %s has no primary key defined", __CLASS__, $this->class));
        }

        return $this->primaryKey;
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

        if ($criteria) {
            $select->expression($this->createWhereWith($criteria));
        }

        return $select
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

        if ($criteria) {
            $select->expression($this->createWhereWith($criteria));
        }

        return $select
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

        if ($criteria) {
            $select->expression($this->createWhereWith($criteria));
        }
        $select->range($limit, ($page - 1) * $limit);

        $total = $select->getCountQuery()->execute()->fetchField();
        $result = $select->execute([], ['class' => $this->class]);

        return new PagerResultIterator($result, $total, $limit, $page);
    }
}
