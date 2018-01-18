<?php

declare(strict_types=1);

namespace Goat\Debug;

use Goat\Driver\AbstractDriverProxy;
use Goat\Driver\DriverInterface;
use Goat\Runner\ResultIteratorInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Driver proxy that triggers debugging behavior upon drivers and results
 *
 * @codeCoverageIgnore
 */
class DebugDriverProxy extends AbstractDriverProxy
{
    private $driver;
    private $validator;

    /**
     * Default constructor
     */
    public function __construct(DriverInterface $driver, ValidatorInterface $validator = null)
    {
        $this->driver = $driver;
        $this->validator = $validator;
    }

    /**
     * {@inheritdoc}
     */
    public function isDebugEnabled() : bool
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function query($query, array $parameters = null, $options = null) : ResultIteratorInterface
    {
        $result = $this->getInnerDriver()->query($query, $parameters, $options);

        if ($this->validator) {
            return new DebugResultIterator($result, $this->validator);
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function executePreparedQuery(string $identifier, array $parameters = null, $options = null) : ResultIteratorInterface
    {
        $result = $this->getInnerDriver()->executePreparedQuery($identifier, $parameters, $options);

        if ($this->validator) {
            return new DebugResultIterator($result, $this->validator);
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    protected function getInnerDriver() : DriverInterface
    {
        return $this->driver;
    }
}
