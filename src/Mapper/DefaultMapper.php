<?php

declare(strict_types=1);

namespace Goat\Mapper;

use Goat\Error\GoatError;
use Goat\Error\QueryError;
use Goat\Mapper\Error\EntityNotFoundError;
use Goat\Mapper\Form\EntityDataMapper;
use Goat\Query\Expression;
use Goat\Query\ExpressionColumn;
use Goat\Query\ExpressionRelation;
use Goat\Query\SelectQuery;
use Goat\Query\Where;
use Goat\Runner\PagerResultIterator;
use Goat\Runner\ResultIteratorInterface;
use Goat\Runner\RunnerAwareTrait;
use Goat\Runner\RunnerInterface;
use Symfony\Component\Form\DataMapperInterface;

/**
 * Table mapper is a simple model implementation that works on an arbitrary
 * select query.
 */
class DefaultMapper implements MapperInterface
{
    use RunnerAwareTrait;

    private $class;
    private $columns = [];
    private $primaryKey = [];
    private $relation;
    private $relationAlias;

    /**
     * Default constructor
     */
    public function __construct(RunnerInterface $runner, string $class, array $primaryKey, string $relation, string $relationAlias = '', array $columns = [])
    {
        $this->class = $class;
        $this->columns = $columns;
        $this->primaryKey = $primaryKey;
        $this->runner = $runner;
        // Handles gracefully schema if specified using 'SCHEMA.RELATION' form
        $this->relation = new ExpressionRelation($relation, $relationAlias);
        // Alias is needed for column selection, fallback on relation name without schema
        $this->relationAlias = $this->relation->getAlias() ?? $this->relation->getName();
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
        return clone $this->relation;
    }

    /**
     * {@inheritdoc}
     */
    public function createSelect(bool $withColumns = true) : SelectQuery
    {
        $select = $this->getRunner()->select($this->relation);

        if ($withColumns) {
            if ($this->columns) {
                foreach ($this->columns as $column) {
                    $select->column(new ExpressionColumn($column, $this->relationAlias));
                }
            } else {
                $select->column(new ExpressionColumn('*', $this->relationAlias));
            }
        }

        return $select;
    }

    /**
     * Does this mapper has columns
     */
    public function hasColumns() : bool
    {
        return !empty($this->columns);
    }

    /**
     * Get selected relation columns
     *
     * @return string[]
     *   Column names
     */
    public function getColumns() : array
    {
        if (empty($this->columns)) {
            throw new GoatError(sprintf("%s mapper for entity %s has no columns defined", __CLASS__, $this->class));
        }

        return $this->columns;
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
        return empty($this->primaryKey) ? 0 : count($this->primaryKey);
    }

    /**
     * {@inheritdoc}
     */
    public function getPrimaryKey() : array
    {
        if (empty($this->primaryKey)) {
            throw new GoatError(sprintf("%s mapper for entity %s has no primary key defined", __CLASS__, $this->class));
        }

        return $this->primaryKey;
    }

    /**
     * {@inheritdoc}
     */
    public function createInstance(array $values)
    {
        $hydrator = $this->getRunner()->getHydratorMap()->get($this->getClassName());

        return $hydrator->createAndHydrateInstance($values);
    }

    /**
     * {@inheritdoc}
     */
    public function createDataMapper() : DataMapperInterface
    {
        if (!interface_exists('\\Symfony\\Component\\Form\\DataMapperInterface')) {
            throw new \RuntimeException(sprintf("Symfony form component is not installed, use 'composer require form' in flex, or 'composer require symfony/form' with any other framework"));
        }

        return new EntityDataMapper($this->getRunner()->getHydratorMap(), $this->getClassName());
    }

    /**
     * {@inheritdoc}
     */
    public function createInstanceFrom($entity)
    {
        return $this->createInstance($this->extractValues($entity));
    }

    /**
     * {@inheritdoc}
     */
    public function extractValues($entity, bool $withPrimaryKey = false) : array
    {
        $hydrator = $this->getRunner()->getHydratorMap()->get($this->getClassName());
        $values = $hydrator->extractValues($entity);

        if (!$withPrimaryKey) {
            foreach ($this->getPrimaryKey() as $column) {
                unset($values[$column]);
            }
        }

        if ($this->columns) {
            $values = array_intersect_key($values, array_flip($this->columns));
        }

        return $values;
    }

    /**
     * {@inheritdoc}
     */
    public function exists($criteria) : bool
    {
        $select = $this->createSelect(false);
        $select->columnExpression('1');

        if ($criteria) {
            $select->expression($this->createWhereWith($criteria));
        }

        return (bool)$select->range(1)->execute()->fetchField();
    }

    /**
     * {@inheritdoc}
     */
    public function findOne($id, $raiseErrorOnMissing = true)
    {
        $select = $this->createSelect();

        foreach ($this->expandPrimaryKey($id) as $column => $value) {
            $select->condition($column, $value);
        }

        $result = $select->range(1, 0)->execute([], ['class' => $this->class]);

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
