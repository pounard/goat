<?php

namespace Goat\ModelManager;

use Goat\Core\Query\Projection as CoreProjection;

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

            if ($structure->hasPrimaryKey()) {
                foreach ($structure->getPrimaryKey() as $column) {
                    $this->setField($column);
                }
            }

            foreach ($structure->getDefinition() as $column => $type) {
                $this->setField($column, null, $type);
            }
        }
    }
}
