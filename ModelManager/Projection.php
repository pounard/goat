<?php

namespace Momm\ModelManager;

use Momm\Core\Query\Projection as CoreProjection;

/**
 * Model manager project is based upon an entity structure
 */
class Projection extends CoreProjection
{
    /**
     * Default constructor
     *
     * @param EntityStructure $structure
     * @param string $tableAlias
     */
    public function __construct(EntityStructure $structure = null, $tableAlias = null)
    {
        parent::__construct($tableAlias);

        if ($structure) {
            foreach ($structure->getDefinition() as $column => $type) {
                $this->setField($column, null, $type);
            }
        }
    }
}
