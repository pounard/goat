<?php

namespace Momm\ModelManager\Model;

use PommProject\ModelManager\Model\Model as PommModel;

use PommProject\Foundation\Where;
use PommProject\ModelManager\Exception\ModelException;
use PommProject\ModelManager\Model\CollectionIterator;
use PommProject\ModelManager\Model\FlexibleEntity\FlexibleEntityInterface;
use PommProject\ModelManager\Model\ModelTrait\WriteQueries;

/**
 * Momm base model, in opposition to Pomm default Model implementation, this
 * one will implement write queries, whether or not you like it, trait conflict
 * resolution was giving very wrong code indirections.
 */
class Model extends PommModel
{
    use WriteQueries;

    /**
     * {@inheritdoc}
     */
    public function existWhere($where, array $values = [])
    {
        $sql = sprintf(
            "select exists (select 1 from %s where :condition limit 1 offset 0) as result",
            $this->getStructure()->getRelation()
        );

        return $this->fetchSingleValue($sql, $where, $values);
    }

    /**
     * insertOne
     *
     * Insert a new entity in the database. The entity is passed by reference.
     * It is updated with values returned by the database (ie, default values).
     *
     * @access public
     * @param  FlexibleEntityInterface  $entity
     * @return Model                    $this
     */
    public function insertOne(FlexibleEntityInterface &$entity)
    {
        $values = $entity->fields(
            array_intersect(
                array_keys($this->getStructure()->getDefinition()),
                array_keys($entity->extract())
            )
        );
        $sql = strtr(
            "insert into :relation (:fields) values (:values) returning :projection",
            [
                ':relation'   => $this->getStructure()->getRelation(),
                ':fields'     => $this->getEscapedFieldList(array_keys($values)),
                ':projection' => $this->createProjection()->formatFieldsWithFieldAlias(),
                ':values'     => join(',', $this->getParametersList($values))
            ]
        );

        $entity = $this
            ->query($sql, array_values($values))
            ->current()
            ->status(FlexibleEntityInterface::STATUS_EXIST)
        ;

        return $this;
    }

    /**
     * updateByPk
     *
     * Update a record and fetch it with its new values. If no records match
     * the given key, null is returned.
     *
     * @access public
     * @param  array          $primary_key
     * @param  array          $updates
     * @throws ModelException
     * @return FlexibleEntityInterface
     */
    public function updateByPk(array $primary_key, array $updates)
    {
        $where = $this
            ->checkPrimaryKey($primary_key)
            ->getWhereFrom($primary_key)
        ;
        $parameters = $this->getParametersList($updates);
        $update_strings = [];

        foreach (array_keys($updates) as $field_name) {
            $update_strings[] = sprintf(
                "%s = %s",
                $this->escapeIdentifier($field_name),
                $parameters[$field_name]
            );
        }

        $sql = strtr(
            "update :relation set :update where :condition returning :projection",
            [
                ':relation'   => $this->getStructure()->getRelation(),
                ':update'     => join(', ', $update_strings),
                ':condition'  => (string) $where,
                ':projection' => $this->createProjection()->formatFieldsWithFieldAlias(),
            ]
        );

        $iterator = $this->query($sql, array_merge(array_values($updates), $where->getValues()));

        if ($iterator->isEmpty()) {
            return null;
        }

        return $iterator->current()->status(FlexibleEntityInterface::STATUS_EXIST);
    }

    /**
     * deleteWhere
     *
     * Delete records by a given condition. A collection of all deleted entries is returned.
     *
     * @param        $where
     * @param  array $values
     * @return CollectionIterator
     */
    public function deleteWhere($where, array $values = [])
    {
        if (!$where instanceof Where) {
            $where = new Where($where, $values);
        }

        $sql = strtr(
            "delete from :relation where :condition returning :projection",
            [
                ':relation'   => $this->getStructure()->getRelation(),
                ':condition'  => (string) $where,
                ':projection' => $this->createProjection()->formatFieldsWithFieldAlias(),
            ]
        );

        $collection = $this->query($sql, $where->getValues());
        foreach ($collection as $entity) {
            $entity->status(FlexibleEntityInterface::STATUS_NONE);
        }
        $collection->rewind();

        return $collection;
    }
}