<?php

namespace Goat\Tests\Core\Query;

use Goat\Core\Client\EscaperInterface;
use Goat\Core\Error\GoatError;

/**
 * Does escape pretty much nothing.
 */
class NullEscaper implements EscaperInterface
{
    /**
     * {@inheritdoc}
     */
    public function escapeIdentifier($string)
    {
        return '"' . $string . '"';
    }

    /**
     * {@inheritdoc}
     */
    public function escapeIdentifierList($strings)
    {
        if (!$strings) {
            throw new GoatError("cannot not format an empty identifier list");
        }
        if (!is_array($strings)) {
            $strings = [$strings];
        }

        return implode(', ', array_map([$this, 'escapeIdentifier'], $strings));
    }

    /**
     * {@inheritdoc}
     */
    public function escapeLiteral($string)
    {
        return "'" . $string . "'";
    }

    /**
     * {@inheritdoc}
     */
    public function escapeBlob($word)
    {
        return '#' . $word . '#';
    }
}
