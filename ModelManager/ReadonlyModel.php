<?php

namespace Momm\ModelManager;

use Momm\Core\Query\Where;
use Momm\Core\Client\ConnectionInterface;

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
     * @return EntityInterface[]
     */
    protected function query($sql, array $values = [], Projection $projection = null)
    {
        if ($projection === null) {
            $projection = $this->createProjection();
        }

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
        if ($where) {
              $sql = strtr(
                  "select :projection from :relation where :condition :suffix",
                  [
                      ':projection' => $this->createProjection(),
                      ':relation'   => $this->getStructure()->getRelation(),
                      ':condition'  => $where,
                      ':suffix'     => $suffix,
                  ]
              );
              $args = $where->getArguments();
        } else {
            $sql = strtr(
                "select :projection from :relation :suffix",
                [
                    ':projection' => $this->createProjection(),
                    ':relation'   => $this->getStructure()->getRelation(),
                    ':suffix'     => $suffix,
                ]
            );
            $args = [];
        }

        return $this->query($sql, $args);
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

        // @todo validate primary key
        if (!is_array($primaryKey)) {
            if (1 < count($definition)) {
                throw new \InvalidArgumentException(sprintf("primary key %d multiple columns, only 1 given", count($definition)));
            }

            $where = new Where();
            $where->addWhere(reset($definition), $primaryKey, '=');
        } else {
            // @todo I don't like this
            $this->checkPrimaryKey($primaryKey);

            foreach ($primaryKey as $column => $value) {
                $where->addWhere($column, $value, '=');
            }
        }

        $sql = strtr(
            "select :projection from :relation where :condition :suffix limit 1 offset 0",
            [
                ':projection' => $this->createProjection(),
                ':relation'   => $this->getStructure()->getRelation(),
                ':condition'  => $where,
            ]
        );

        $iterator = $this->query($sql, $where->getValues());

        return empty($iterator) ? null : current($iterator);
    }

    /**
     * Return the number of records matching a condition
     *
     * @param Where $where
     * @param array $values
     *
     * @return int
     */
    public function countWhere(Where $where)
    {
        $sql = strtr(
            "select count(*) as result from :relation where :condition",
            [
                ':relation'   => $this->getStructure()->getRelation(),
                ':condition'  => $where,
            ]
        );

        return (int)$this->fetchSingleValue($sql, $where);
    }

    /**
     * Check if rows matching the given condition do exist or not
     *
     * @param mixed $where
     * @param array $values
     *
     * @return bool
     */
    public function existWhere(Where $where)
    {
        $sql = strtr(
            "select exists (select 1 from :relation where :condition) as result",
            [
                ':relation'   => $this->getStructure()->getRelation(),
                ':condition'  => $where,
            ]
        );

        return (bool)$this->fetchSingleValue($sql, $where);
    }

    /**
     * Fetch a single value from the first row
     *
     * @param string  $sql
     * @param Where $where
     *
     * @return mixed
     */
    protected function fetchSingleValue($sql, Where $where)
    {
        $result = $this->connection->query($sql, $where->getArguments());

        foreach ($result as $row) {
            return reset($row);
        }
    }

    /**
     * paginateFindWhere
     *
     * Paginate a query.
     *
     * @access public
     * @param  Where    $where
     * @param  int      $item_per_page
     * @param  int      $page
     * @param  string   $suffix
     * @return Pager
     *
    public function paginateFindWhere(Where $where, $item_per_page, $page = 1, $suffix = '')
    {
        $projection = $this->createProjection();

        return $this->paginateQuery(
            $this->getFindWhereSql($where, $projection, $suffix),
            $where->getValues(),
            $this->countWhere($where),
            $item_per_page,
            $page,
            $projection
        );
    }
     */

    /**
     * paginateQuery
     *
     * Paginate a SQL query.
     * It is important to note it adds limit and offset at the end of the given
     * query.
     *
     * @access  protected
     * @param   string       $sql
     * @param   array        $values parameters
     * @param   int          $count
     * @param   int          $item_per_page
     * @param   int          $page
     * @param   Projection   $projection
     * @throws  \InvalidArgumentException if pager args are invalid.
     * @return  Pager
     *
    protected function paginateQuery($sql, array $values, $count, $item_per_page, $page = 1, Projection $projection = null)
    {
        if ($page < 1) {
            throw new \InvalidArgumentException(
                sprintf("Page cannot be < 1. (%d given)", $page)
            );
        }

        if ($item_per_page <= 0) {
            throw new \InvalidArgumentException(
                sprintf("'item_per_page' must be strictly positive (%d given).", $item_per_page)
            );
        }

        $offset = $item_per_page * ($page - 1);
        $limit  = $item_per_page;

        return new Pager(
            $this->query(
                sprintf("%s offset %d limit %d", $sql, $offset, $limit),
                $values,
                $projection
            ),
            $count,
            $item_per_page,
            $page
        );
    }
     */

    /**
     * getFindWhereSql
     *
     * This is the standard SQL query to fetch instances from the current
     * relation.
     *
     * @access protected
     * @param  Where        $where
     * @param  Projection   $projection
     * @param  string       $suffix
     * @return string
     *
    protected function getFindWhereSql(Where $where, Projection $projection, $suffix = '')
    {
        return strtr(
            'select :projection from :relation where :condition :suffix',
            [
                ':projection' => $projection->formatFieldsWithFieldAlias(),
                ':relation'   => $this->getStructure()->getRelation(),
                ':condition'  => (string) $where,
                ':suffix'     => $suffix,
            ]
        );
    }
     */

    /**
     * Check if model has a primary key
     *
     * @return boolean
     */
    protected function hasPrimaryKey()
    {
        $primaryKeys = $this->getStructure()->getPrimaryKey();

        return !empty($primaryKeys);
    }

    /**
     * Check if the given values fully describe a primary key
     *
     * @param array $values
     *
     * @return $this
     *
     * @throws \InvalidArgumentException
     */
    protected function checkPrimaryKey(array $values)
    {
        if (!$this->hasPrimaryKey()) {
            throw new \InvalidArgumentException("Attached structure has no primary key.");
        }

        foreach ($this->getStructure()->getPrimaryKey() as $key) {
            if (!isset($values[$key])) {
                throw new \InvalidArgumentException(
                    sprintf(
                        "Key '%s' is missing to fully describes the primary key {%s}.",
                        $key,
                        join(', ', $this->getStructure()->getPrimaryKey())
                    )
                );
            }
        }

        return $this;
    }
}
