<?php

declare(strict_types=1);

namespace Goat\Runner;

/**
 * Implementation for the RunnerInterface interface
 */
trait RunnerAwareTrait
{
    /**
     * @var RunnerInterface
     */
    protected $runner;

    /**
     * Set runner
     *
     * @param RunnerInterface $runner
     */
    public function setRunner(RunnerInterface $runner)
    {
        $this->runner = $runner;
    }
}
