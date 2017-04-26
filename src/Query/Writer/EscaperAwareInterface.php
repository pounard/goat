<?php

declare(strict_types=1);

namespace Goat\Query\Writer;

interface EscaperAwareInterface
{
    /**
     * Set escaper
     *
     * @param EscaperInterface $escaper
     */
    public function setEscaper(EscaperInterface $escaper);
}
