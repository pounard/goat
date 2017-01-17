<?php

declare(strict_types=1);

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
