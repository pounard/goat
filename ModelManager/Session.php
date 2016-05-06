<?php

namespace Momm\ModelManager;

use PommProject\ModelManager\Model\Model;
use PommProject\ModelManager\ModelLayer\ModelLayer;
use PommProject\Foundation\Session\Session as FoundationSession;

class Session extends FoundationSession
{
    /**
     * Get model instance
     *
     * @param string $class
     *
     * @return Model
     */
    public function getModel($class)
    {
        return $this->getClientUsingPooler('model', $class);
    }

    /**
     * Get model layer instance
     *
     * @param string $class
     *
     * @return ModelLayer
     */
    public function getModelLayer($class)
    {
        return $this->getClientUsingPooler('model_layer', $class);
    }
}
