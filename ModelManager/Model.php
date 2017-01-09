<?php

namespace Goat\ModelManager;

use Goat\Core\Query\Where;

/**
 * Goat base model, in opposition to Pomm default Model implementation, this
 * one will implement write queries, whether or not you like it, trait conflict
 * resolution was giving very wrong code indirections.
 */
class Model extends ReadonlyModel
{
    /**
     * Create a new entity
     *
     * It won't be persisted (yet)
     *
     * @param array $values
     *   Arbitrary values
     *
     * @return EntityInterface
     */
    public function createEntity(array $values = [])
    {
        return $this->structure->create($values);
    }

    /**
     * @todo I should document this...
     */
    protected function getCommaSeparatedIdentifierList($identifiers)
    {
        return implode(', ', array_map([$this->connection, 'escapeIdentifier'], $identifiers));
    }

    /**
     * @todo I should document this...
     */
    protected function getCommaSeparatedArgumentList($fields)
    {
        $ret = [];

        foreach ($fields as $name) {
            $type = $this->structure->getTypeFor($name);

            if ($type) {
                $ret[] = '$*::' . $type;
            } else {
                $ret[] = '$*';
            }
        }

        return implode(', ', $ret);
    }

    /**
     * Insert entity into database
     *
     * @param EntityInterface $entity
     */
    public function insertOne(EntityInterface $entity)
    {
        // For the sake of consistency, we need to update the current entity
        // instance for the users, but there is one problem, we cannot fetch
        // it other than using the last insert id function, so, we are going to
        // that, but using a multiple primary key won't work...

        // Warning: this is a bit hackish, but is definitely legit: when
        // inserting and entity, it has to have a primary key, when using MySQL
        // you could either:
        //   - use a serial (auto_increment) in order to fetch last insert id
        //   - use a non-serial, case in which the entity must carry the value
        //   - use a combination of both, case in which only one field can be
        //     a serial
        // in all cases, we can reconstruct the primary key from what we have,
        // load back the row, and complete the entity from what we selected.
        $primaryKey = $this->structure->getPrimaryKey();
        $guessedKey = [];
        $updated    = null;

        // First, find if there is a serial inserted, note that the entity
        // structure must know it either we are fucked.
        $serial = null;
        foreach ($primaryKey as $name) {
            if ('serial' === $this->structure->getTypeFor($name)) {
                $serial = $name;
                break;
            }
        }

        $values = $this->structure->extract($entity);
        if ($serial) {
            // Remove serial values from the values to insert
            unset($values[$serial]);
        }

        $query = $this
            ->connection
            ->insertValues($this->getStructure()->getRelation())
            ->columns(array_keys($values))
            ->values(array_values($values))
        ;

        if ($this->connection->supportsReturning()) {
            foreach ($this->structure->getFieldNames() as $name) {
                $query->returning($name);
            }

            $updated = $query->execute()->fetch();

        } else {
            // RETURNING emulation
            $query->execute();

            if ($serial) {
                // Gotcha serial! Fetch the last inserted identifier
                $guessedKey[$serial] = $this->connection->query("select last_insert_id()")->fetchField();
            }

            // Having a serial or not, we must find the other values
            foreach ($primaryKey as $name) {
                // Using has() here allow to skip automatically inserted values
                // which in real life using MySQL can only be one serial per table
                if ($entity->has($name)) {
                    $guessedKey[$name] = $entity->get($name);
                }
            }

            if (!$guessedKey) {
                // For some reason, it might not be OK, but I supposed that this
                // could happen in only 2 cases:
                //  - structure is wrongly defined: 99% of chances our generated
                //    query failed too, so I won't treat this case
                //  - there is no primary key: sorry, but I'll just not update the
                //    entity since MySQL can do RETURNING statements
                return;
            }

            $updated = $this->findByPK($guessedKey);
            $updated = $updated ? $updated->getAll() : null;
        }

        if ($updated) {
            $entity->setAll($updated);
        }
    }

    /**
     * Insert a new entity in the database. The entity is passed by reference.
     * It is updated with values returned by the database (ie, default values).
     *
     * @todo dependending on implemented fields, values () might be different
     *   so I do need to fix that
     *
     * @param EntityInterface[] $entities
     */
    public function insertAll($entities)
    {
        foreach ($entities as $entity) {
            $this->insertOne($entity);
        }
    }

    /**
     * Update a single entity
     *
     * @param EntityInterface $entity
     */
    public function update(EntityInterface $entity)
    {
        return $this->updateByPk($this->getEntityPrimaryKey($entity), $entity->getAll());
    }

    /**
     * Update a set of entities
     *
     * @param EntityInterface[] $entities
     */
    public function updateAll($entities)
    {
        foreach ($entities as $entity) {
            $this->update($entity);
        }
    }

    /**
     * Update entities where
     *
     * @param Where $where
     * @param mixed[] $updates
     *   Arbitrary values
     *
     * @return int
     *   Number of items being updated, if you want to load them after update
     *   use the ::findAll() method using the same Where condition, this method
     *   will not do it automatically since you may update thousands of entries
     *   at once
     */
    public function updateWhere(Where $where, array $updates)
    {
        return $this
            ->connection
            ->update($this->structure->getRelation(), $this->getRelationAlias())
            ->sets($updates)
            ->expression($where)
            ->execute()
            ->countRows()
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function updateByPk($primaryKey, array $updates)
    {
        $where = $this->getPrimaryKeyWhere($primaryKey);

        $this
            ->connection
            ->update($this->structure->getRelation(), $this->getRelationAlias())
            ->sets($updates)
            ->expression($where)
            ->execute()
        ;

        // Sorry, but MySQL can't do RETURNING, so at least, let's just be
        // signature compatible
        return $this->findByPK($primaryKey);
    }

    /**
     * Delete a single entity
     *
     * @param EntityInterface $entity
     */
    public function delete(EntityInterface $entity)
    {
        throw new \Exception("Not implemented yet");
    }

    /**
     * Delete a single entity using its primary key
     *
     * @param mixed|mixed[] $primaryKey
     */
    public function deleteByPk($primaryKey)
    {
        $where = $this->getPrimaryKeyWhere($primaryKey);

        return $this->deleteWhere($where);
    }

    /**
     * Delete entities where
     *
     * @param Where $where
     *
     * @return int
     *   Number of items being deleted
     */
    public function deleteWhere(Where $where)
    {
        throw new \Exception("Not implemented yet");
    }
}
