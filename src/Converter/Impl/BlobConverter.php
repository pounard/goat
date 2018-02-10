<?php

declare(strict_types=1);

namespace Goat\Converter\Impl;

use Goat\Converter\ConverterInterface;
use Goat\Query\Writer\EscaperInterface;

/**
 * This implementation will fit with any driver.
 *
 * Because it needs the escaper to be created a new instance, it's up to each
 * database driver to register their own blob converters with the "bytea" and
 * "blob" types at the very least.
 */
class BlobConverter implements ConverterInterface
{
    private $escaper;

    /**
     * This breaks isolation between converters and the driver, and it should
     * not be set from here
     */
    public function setEscaper(EscaperInterface $escaper)
    {
        $this->escaper = $escaper;
    }

    /**
     * {@inheritdoc}
     */
    public function fromSQL(string $type, $value)
    {
        return $this->escaper->unescapeBlob((string)$value);
    }

    /**
     * {@inheritdoc}
     */
    public function toSQL(string $type, $value) : string
    {
        return $this->escaper->escapeBlob((string)$value);
    }

    /**
     * {@inheritdoc}
     */
    public function needsCast(string $type) : bool
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function cast(string $type)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function canProcess($value) : bool
    {
        return false; // avoid it to allow flase positives when guess() method is called
    }
}
