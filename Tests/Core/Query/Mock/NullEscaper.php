<?php

namespace Goat\Tests\Core\Query\Mock;

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
    public function escapeIdentifier(string $string) : string
    {
        return '"' . $string . '"';
    }

    /**
     * {@inheritdoc}
     */
    public function escapeIdentifierList($strings) : string
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
    public function escapeLiteral(string $string) : string
    {
        return "'" . $string . "'";
    }

    /**
     * {@inheritdoc}
     */
    public function escapeBlob(string $word) : string
    {
        return '#' . $word . '#';
    }
}
