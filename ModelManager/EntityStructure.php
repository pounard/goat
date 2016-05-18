<?php

namespace Momm\ModelManager;

/**
 * Represent a composite structure like table or row.
 */
class EntityStructure
{
    protected $primaryKey = [];
    protected $fields = [];
    protected $relation;
    protected $entityClass;

    /**
     * From the given field definition, expand it to missing defined fields
     * with null values for object hydratation
     *
     * @param mixed[] $fields
     * @param boolean $allowInvalid
     *
     * @return mixed[]
     */
    private function expandFields($fields, $allowInvalid = false)
    {
        if (!$allowInvalid) {
            foreach (array_keys($fields) as $name) {
                if (!$this->hasField($name)) {
                    throw new \InvalidArgumentException(sprintf("field '%s' is not defined", $name));
                }
            }
        }

        foreach (array_keys($this->fields) as $name) {
            if (!isset($fields[$name])) {
                $fields[$name] = null;
            }
        }

        return $fields;
    }

    private function checkEntity(EntityInterface $entity)
    {
        if (is_subclass_of($entity, $this->getEntityClass())) {
            throw new \InvalidArgumentException(sprintf("entity is not a '%s'", $this->getEntityClass()));
        }

        return $entity;
    }

    /**
     * Create and hydrate entity using the given values
     *
     * @param mixed[] $values
     *   Keys are field names, values are whatever are the values
     *
     * @return $this
     */
    public function hydrate(EntityInterface $entity, array $values)
    {
        // Allow invalid fields when hydrating, maybe the user wrote a complex
        // query allowing him to fetch additional data at once
        $values = $this->expandFields($values, true);

        if ($entity instanceof DefaultEntity) {
            $entity->defineAll($values);
        } else {
            // Leave the defineAll() for default entity, so that implementors
            // have a way to skip it if necessary, default entity is a bit too
            // flexible for some use cases
            $entity->setAll($values);
        }

        return $this;
    }

    /**
     * Create and hydrate entity using the given values
     *
     * @param mixed[] $values
     *   Keys are field names, values are whatever are the values
     *
     * @return EntityInterface
     */
    public function create(array $values = [])
    {
        $entityClass = $this->getEntityClass();
        $entity = new $entityClass();

        if ($values) {
            $this->hydrate($entity, $values);
        }

        return $entity;
    }

    /**
     * Extract field values as array
     *
     * @param string[] $fields
     *   Set this to extract only the listed fields
     *
     * @return mixed[]
     *   Keys are field names, values are whatever are the values
     */
    public function extract(EntityInterface $entity, array $fields = null)
    {
        $ret = [];

        $entityClass = $this->getEntityClass();
        if (is_subclass_of($entity, $entityClass)) {
            throw new \InvalidArgumentException(sprintf("entity is not a '%s'", $entityClass));
        }

        if (null === $fields) {
            $fields = array_keys($this->fields);
        }

        foreach ($fields as $name) {

            if (!$this->hasField($name)) {
                throw new \InvalidArgumentException(sprintf("unknown field '%s' for entity '%s'", $name));
            }

            $ret[$name] = $entity->get($name);
        }

        return $ret;
    }

    /**
     * Set entity class
     *
     * @param string $entityClass
     *
     * @return $this
     */
    public function setEntityClass($entityClass)
    {
        $this->entityClass = $entityClass;

        if (!class_exists($this->entityClass)) {
            throw new \LogicException(sprintf("class '%s' does not exists", $this->entityClass));
        }
        if (!is_subclass_of($this->entityClass, EntityInterface::class)) {
            throw new \LogicException(sprintf("class '%s' does not implements '%s'", $this->entityClass, EntityInterface::class));
        }

        return $this;
    }

    /**
     * Get entity class
     *
     * @return string
     */
    public function getEntityClass()
    {
        if (null === $this->entityClass) {
            throw new \LogicException(sprintf("missing entity class"));
        }


        return $this->entityClass;
    }

    /**
     * Add a complete definition
     *
     * @param string[] $definition
     *
     * @return $this
     */
    public function setDefinition(array $definition)
    {
        $this->fields = $definition;

        return $this;
    }

    /**
     * Add inherited structure
     *
     * @param RowStructure $parent
     *
     * @return $this
     */
    public function inherits(EntityStructure $parent)
    {
        foreach ($parent->getDefinition() as $field => $type) {
            $this->addField($field, $type);
        }

        return $this;
    }

    /**
     * Set or change the relation
     *
     * @param string $relation
     *
     * @return $this
     */
    public function setRelation($relation)
    {
        $this->relation = $relation;

        return $this;
    }

    /**
     * Set or change the primary key definition.
     *
     * @param string[] $primaryKey
     *
     * @return $this
     */
    public function setPrimaryKey(array $primaryKey)
    {
        $this->primaryKey = $primaryKey;

        return $this;
    }

    /**
     * Add a new field structure
     *
     * @param string $name
     * @param string $type
     *
     * @return $this
     */
    public function addField($name, $type)
    {
        $this->fields[$name] = $type;

        return $this;
    }

    /**
     * Return an array of all field names
     *
     * @return string[]
     */
    public function getFieldNames()
    {
        return array_keys($this->fields);
    }

    /**
     * Check if a field exist in the structure
     *
     * @param string $name
     *
     * @return boolean
     */
    public function hasField($name)
    {
        // I guess that fields must have a field, so a null in there is not
        // possible, hence isset() instead of array_key_exists()
        return isset($this->fields[$name]);
    }

    /**
     * Return the type associated with the field
     *
     * @param string $name
     *
     * @return string $type
     */
    public function getTypeFor($name)
    {
        if (!$this->hasField($name)) {
            throw new \InvalidArgumentException(sprintf("field '%s' is not defined in structure '%s'", $name, get_class($this)));
        }

        return $this->fields[$name];
    }

    /**
     * Return all fields and types
     *
     * @return string[]
     *   Keys are field names, values are associated field types
     */
    public function getDefinition()
    {
        return $this->fields;
    }

    /**
     * Return the relation name.
     *
     * @return string
     */
    public function getRelation()
    {
        if (!$this->relation) {
            throw new \LogicException("structure has no relation set");
        }

        return $this->relation;
    }

    /**
     * Does this project have a primary key
     *
     * @return boolean
     */
    public function hasPrimaryKey()
    {
        return !empty($this->primaryKey);
    }

    /**
     * Return the primary key definition.
     *
     * @return string[]
     */
    public function getPrimaryKey()
    {
        return $this->primaryKey;
    }
}
