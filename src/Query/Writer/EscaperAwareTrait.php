<?php

declare(strict_types=1);

namespace Goat\Query\Writer;

/**
 * Base implementation for the EscaperAwareInterface
 */
trait EscaperAwareTrait
{
    /**
     * @var EscaperInterface
     */
    protected $escaper;

    /**
     * Set escaper
     *
     * @param EscaperInterface $escaper
     */
    public function setEscaper(EscaperInterface $escaper)
    {
        $this->escaper = $escaper;
    }
}
