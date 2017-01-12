<?php

namespace Goat\Core\Client;

interface EscaperAwareInterface
{
    /**
     * Set connection
     *
     * @param EscaperInterface $escaper
     */
    public function setEscaper(EscaperInterface $escaper);
}
