<?php

declare(strict_types=1);

namespace Goat\Runner;

/**
 * Reprensents an object that needs a runner
 */
interface RunnerAwareInterface
{
    /**
     * Set driver
     *
     * @param RunnerInterface $runner
     */
    public function setRunner(RunnerInterface $runner);
}
