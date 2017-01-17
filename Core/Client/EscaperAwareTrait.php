<?php

declare(strict_types=1);

namespace Goat\Core\Client;

trait EscaperAwareTrait
{
    /**
     * @var EscaperInterface
     */
    protected $escaper;

    /**
     * Set connection
     *
     * @param EscaperInterface $escaper
     */
    public function setEscaper(EscaperInterface $escaper)
    {
        $this->escaper = $escaper;
    }
}
