<?php

declare(strict_types=1);

namespace Goat\Query\Writer;

use Goat\Core\Error\GoatError;

/**
 * Base implementation for escapers
 */
abstract class EscaperBase implements EscaperInterface
{
    /**
     * {@inheritdoc}
     */
    final public function escapeIdentifierList($strings) : string
    {
        if (!$strings) {
            throw new GoatError("cannot not format an empty identifier list");
        }
        if (!is_array($strings)) {
            $strings = [$strings];
        }

        return implode(', ', array_map([$this, 'escapeIdentifier'], $strings));
    }
}
