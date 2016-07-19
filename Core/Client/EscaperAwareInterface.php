<?php

namespace Momm\Core\Client;

interface EscaperAwareInterface
{
    /**
     * Set connection
     *
     * @param EscaperInterface $escaper
     *
     * @return $this
     */
    public function setEscaper(EscaperInterface $escaper);
}
