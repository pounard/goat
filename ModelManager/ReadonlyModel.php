<?php

namespace Momm\ModelManager;

use PommProject\ModelManager\Model\Projection;

class ReadonlyModel
{
    /**
     * @var EntityStructure
     */
    protected $structure;

    /**
     * Default constructor
     *
     * @param EntityStructure $structure
     */
    public function __construct(EntityStructure $structure)
    {
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
     * findAll
     *
     * Return all elements from a relation. If a suffix is given, it is append
     * to the query. This is mainly useful for "order by" statements.
     * NOTE: suffix is inserted as is with NO ESCAPING. DO NOT use it to place
     * "where" condition nor any untrusted params.
     *
     * @access public
     * @param  string             $suffix
     * @return CollectionIterator
     */
    public function findAll($suffix = null)
    {
        $sql = strtr(
            "select :fields from :table :suffix",
            [
                ':fields' => $this->createProjection()->formatFieldsWithFieldAlias(),
                ':table'  => $this->getStructure()->getRelation(),
                ':suffix' => $suffix,
            ]
        );

        return $this->query($sql);
    }

    /**
     * findWhere
     *
     * Perform a simple select on a given condition
     * NOTE: suffix is inserted as is with NO ESCAPING. DO NOT use it to place
     * "where" condition nor any untrusted params.
     *
     * @access public
     * @param  mixed              $where
     * @param  array              $values
     * @param  string             $suffix order by, limit, etc.
     * @return CollectionIterator
     */
    public function findWhere($where, array $values = [], $suffix = '')
    {
        if (!$where instanceof Where) {
            $where = new Where($where, $values);
        }

        return $this->query($this->getFindWhereSql($where, $this->createProjection(), $suffix), $where->getValues());
    }

    /**
     * findByPK
     *
     * Return an entity upon its primary key. If no entities are found, null is
     * returned.
     *
     * @access public
     * @param  array          $primary_key
     * @return FlexibleEntityInterface
     */
    public function findByPK(array $primary_key)
    {
        $where = $this
            ->checkPrimaryKey($primary_key)
            ->getWhereFrom($primary_key)
            ;

        $iterator = $this->findWhere($where);

        return $iterator->isEmpty() ? null : $iterator->current();
    }

    /**
     * countWhere
     *
     * Return the number of records matching a condition.
     *
     * @access public
     * @param  string|Where $where
     * @param  array        $values
     * @return int
     */
    public function countWhere($where, array $values = [])
    {
        $sql = sprintf(
            "select count(*) as result from %s where :condition",
            $this->getStructure()->getRelation()
        );

        return $this->fetchSingleValue($sql, $where, $values);
    }

    /**
     * existWhere
     *
     * Check if rows matching the given condition do exist or not.
     *
     * @access public
     * @param  mixed $where
     * @param  array $values
     * @return bool
     */
    public function existWhere($where, array $values = [])
    {
        $sql = sprintf(
            "select exists (select true from %s where :condition) as result",
            $this->getStructure()->getRelation()
        );

        return $this->fetchSingleValue($sql, $where, $values);
    }

    /**
     * fetchSingleValue
     *
     * Fetch a single value named « result » from a query.
     * The query must be formatted with ":condition" as WHERE condition
     * placeholder. If the $where argument is a string, it is turned into a
     * Where instance.
     *
     * @access protected
     * @param  string       $sql
     * @param  mixed        $where
     * @param  array        $values
     * @return mixed
     */
    protected function fetchSingleValue($sql, $where, array $values)
    {
        if (!$where instanceof Where) {
            $where = new Where($where, $values);
        }

        $sql = str_replace(":condition", (string) $where, $sql);

        return $this
            ->getSession()
            ->getClientUsingPooler('query_manager', '\PommProject\Foundation\PreparedQuery\PreparedQueryManager')
            ->query($sql, $where->getValues())
            ->current()['result']
            ;
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
     */
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
     */
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
     */
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

    /**
     * hasPrimaryKey
     *
     * Check if model has a primary key
     *
     * @access protected
     * @return bool
     */
    protected function hasPrimaryKey()
    {
        $primaryKeys = $this->getStructure()->getPrimaryKey();

        return !empty($primaryKeys);
    }

    /**
     * checkPrimaryKey
     *
     * Check if the given values fully describe a primary key. Throw a
     * ModelException if not.
     *
     * @access private
     * @param  array $values
     * @throws ModelException
     * @return $this
     */
    protected function checkPrimaryKey(array $values)
    {
        if (!$this->hasPrimaryKey()) {
            throw new ModelException(
                sprintf(
                    "Attached structure '%s' has no primary key.",
                    get_class($this->getStructure())
                )
            );
        }

        foreach ($this->getStructure()->getPrimaryKey() as $key) {
            if (!isset($values[$key])) {
                throw new ModelException(
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

    /**
     * getWhereFrom
     *
     * Build a condition on given values.
     *
     * @access protected
     * @param  array $values
     * @return Where
     */
    protected function getWhereFrom(array $values)
    {
        $where = new Where();

        foreach ($values as $field => $value) {
            $where->andWhere(
                sprintf(
                    "%s = $*::%s",
                    $this->escapeIdentifier($field),
                    $this->getStructure()->getTypeFor($field)
                ),
                [$value]
            );
        }

        return $where;
    }

    /**
     * query
     *
     * Execute the given query and return a Collection iterator on results. If
     * no projections are passed, it will use the default projection using
     * createProjection() method.
     *
     * @access protected
     * @param  string             $sql
     * @param  array              $values
     * @param  Projection         $projection
     * @return CollectionIterator
     */
    protected function query($sql, array $values = [], Projection $projection = null)
    {
        if ($projection === null) {
            $projection = $this->createProjection();
        }

        $result = $this
            ->getSession()
            ->getClientUsingPooler('prepared_query', $sql)
            ->execute($values)
            ;

        $collection = new CollectionIterator(
            $result,
            $this->getSession(),
            $projection
        );

        return $collection;
    }

    /**
     * createDefaultProjection
     *
     * This method creates a projection based on the structure definition of
     * the underlying relation. It may be used to shunt parent createProjection
     * call in inherited classes.
     * This method can be used where a projection that sticks to table
     * definition is needed like recursive CTEs. For normal projections, use
     * createProjection instead.
     *
     * @access public
     * @return Projection
     */
    final public function createDefaultProjection()
    {
        return new Projection($this->flexible_entity_class, $this->structure->getDefinition());
    }

    /**
     * createProjection
     *
     * This is a helper to create a new projection according to the current
     * structure.Overriding this method will change projection for all models.
     *
     * @access  public
     * @return  Projection
     */
    public function createProjection()
    {
        return $this->createDefaultProjection();
    }

    /**
     * checkFlexibleEntity
     *
     * Check if the given entity is an instance of this model's flexible class.
     * If not an exception is thrown.
     *
     * @access protected
     * @param  FlexibleEntityInterface $entity
     * @throws \InvalidArgumentException
     * @return Model          $this
     */
    protected function checkFlexibleEntity(FlexibleEntityInterface $entity)
    {
        if (!($entity instanceof $this->flexible_entity_class)) {
            throw new \InvalidArgumentException(sprintf(
                "Entity class '%s' is not a '%s'.",
                get_class($entity),
                $this->flexible_entity_class
            ));
        }

        return $this;
    }

    /**
     * escapeLiteral
     *
     * Handy method to escape strings.
     *
     * @access protected
     * @param  string $string
     * @return string
     */
    protected function escapeLiteral($string)
    {
        return $this
            ->getSession()
            ->getConnection()
            ->escapeLiteral($string);
    }

    /**
     * escapeLiteral
     *
     * Handy method to escape strings.
     *
     * @access protected
     * @param  string $string
     * @return string
     */
    protected function escapeIdentifier($string)
    {
        return $this
            ->getSession()
            ->getConnection()
            ->escapeIdentifier($string);
    }

    /**
     * executeAnonymousQuery
     *
     * Handy method for DDL statements.
     *
     * @access protected
     * @param  string $sql
     * @return Model  $this
     */
    protected function executeAnonymousQuery($sql)
    {
        $this
            ->getSession()
            ->getConnection()
            ->executeAnonymousQuery($sql);

        return $this;
    }
}
