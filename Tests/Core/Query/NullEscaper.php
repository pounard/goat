<?php

namespace Goat\Tests\Core\Query;

use Goat\Core\Client\EscaperInterface;

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
        return $string;
    }

    /**
     * {@inheritdoc}
     */
    public function escapeLiteral($string)
    {
        return $string;
    }

    /**
     * {@inheritdoc}
     */
    public function escapeBlob($word)
    {
        return $word;
    }
}
