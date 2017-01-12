<?php

namespace Goat\ModelManager;

trait StatefulEntityTrait
{
    private $status = EntityInterface::STATUS_NONE;

    /**
     * Is entity new
     *
     * @return bool
     */
    public function isNew()
    {
        return $this->status === EntityInterface::STATUS_NONE;
    }

    /**
     * Is entity modified
     *
     * @return bool
     */
    public function isModified()
    {
        return $this->status === EntityInterface::STATUS_MODIFIED;
    }

    /**
     * Set entity status
     *
     * @param int $status
     *
     * @return $this
     */
    public function toggleStatus($status = null)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Set the entity as modified, do nothing if status is none
     *
     * @return $this
     */
    public function touch()
    {
        $this->status |= EntityInterface::STATUS_MODIFIED;

        return $this;
    }
}
