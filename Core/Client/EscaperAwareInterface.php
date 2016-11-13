<?php

namespace Goat\Core\Client;

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
