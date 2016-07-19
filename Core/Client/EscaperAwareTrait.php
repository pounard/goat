<?php

namespace Momm\Core\Client;

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
     *
     * @return $this
     */
    public function setEscaper(EscaperInterface $escaper)
    {
        $this->escaper = $escaper;

        return $this;
    }
}
