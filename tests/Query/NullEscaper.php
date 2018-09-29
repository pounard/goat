<?php

declare(strict_types=1);

namespace Goat\Tests\Query;

use Goat\Error\GoatError;
use Goat\Query\Writer\EscaperInterface;

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
        if (!\is_array($strings)) {
            $strings = [$strings];
        }

        return \implode(', ', \array_map([$this, 'escapeIdentifier'], $strings));
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
    public function escapeLike(string $string) : string
    {
        return addcslashes($string, '\%_');
    }

    /**
     * {@inheritdoc}
     */
    public function escapeBlob(string $word) : string
    {
        return '#' . $word . '#';
    }

    /**
     * {@inheritdoc}
     */
    public function unescapeBlob($resource) : ?string
    {
        return substr(substr($resource, -1), 1);
    }

    /**
     * {@inheritdoc}
     */
    public function getEscapeSequences() : array
    {
        return ['"', "'"];
    }
}
