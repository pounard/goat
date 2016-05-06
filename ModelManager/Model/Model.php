<?php

namespace Momm\ModelManager\Model;

use PommProject\ModelManager\Model\Model as PommModel;

use PommProject\Foundation\Where;
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
            "insert into :relation (:fields) values (:values)",
            [
                ':relation'   => $this->getStructure()->getRelation(),
                ':fields'     => $this->getEscapedFieldList(array_keys($values)),
                ':values'     => join(',', $this->getParametersList($values))
            ]
        );

        $this->query($sql, array_values($values));

        return $this;
    }

    /**
     * {@inheritdoc}
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
            "update :relation set :update where :condition",
            [
                ':relation'   => $this->getStructure()->getRelation(),
                ':update'     => join(', ', $update_strings),
                ':condition'  => (string) $where,
            ]
        );

        $this->query($sql, array_merge(array_values($updates), $where->getValues()));

        // Sorry, but MySQL can't do RETURNING, so at least, let's just be signature compatible
        $entity = $this->findByPK($primary_key);
        if ($entity) {
            $entity->status(FlexibleEntityInterface::STATUS_EXIST);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function deleteWhere($where, array $values = [])
    {
        $connection = $this->getSession()->getConnection();

        if (!$where instanceof Where) {
            $where = new Where($where, $values);
        }

        // OH MY GOD - https://stackoverflow.com/a/1751282
        //   fountène, vite!
        $temporaryTableName = uniqid('id');
        $connection->executeAnonymousQuery("begin transaction");

        $sql = strtr(
            "create temporary table :temporary as select :projection from :relation where :condition",
            [
                ':temporary'  => $temporaryTableName,
                ':projection' => $this->createProjection()->formatFieldsWithFieldAlias(),
                ':relation'   => $this->getStructure()->getRelation(),
                ':condition'  => (string) $where,
            ]
        );
        $this->query($sql, $where->getValues());

        $sql = strtr(
            "delete from :relation where :condition",
            [
                ':relation'   => $this->getStructure()->getRelation(),
                ':condition'  => (string) $where,
            ]
        );
        $this->query($sql, $where->getValues());

        $collection = $this->query("select * from :temporary");
        $connection->executeAnonymousQuery(sprintf("drop table %s", $temporaryTableName));
        try {
            $connection->executeAnonymousQuery("commit");
        } catch (\Exception $e) {
            $connection->executeAnonymousQuery("rollback");
            throw $e;
        }

        foreach ($collection as $entity) {
            $entity->status(FlexibleEntityInterface::STATUS_NONE);
        }
        $collection->rewind();

        $connection->executeAnonymousQuery("commit");

        return $collection;
    }
}