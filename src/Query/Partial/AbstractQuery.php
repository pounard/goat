<?php

declare(strict_types=1);

namespace Goat\Query\Partial;

use Goat\Core\Error\GoatError;
use Goat\Driver\DriverAwareInterface;
use Goat\Driver\DriverAwareTrait;
use Goat\Query\ExpressionRelation;
use Goat\Query\Query;
use Goat\Runner\ResultIteratorInterface;

/**
 * Reprensents the basis of an SQL query.
 */
abstract class AbstractQuery implements Query, DriverAwareInterface
{
    use DriverAwareTrait;
    use AliasHolderTrait;

    private $relation;
    private $options = [];

    /**
     * Build a new query
     *
     * @param string|ExpressionRelation $relation
     *   SQL from statement relation name
     * @param string $alias
     *   Alias for from clause relation
     */
    public function __construct($relation, string $alias = null)
    {
        $this->relation = $this->normalizeRelation($relation, $alias);
    }

    /**
     * Get SQL from relation
     *
     * @return ExpressionRelation
     */
    final public function getRelation() : ExpressionRelation
    {
        return $this->relation;
    }

    /**
     * Set a single query options
     *
     * null value means reset to default.
     *
     * @param string $name
     * @param mixed $value
     */
    final public function setOption(string $name, $value)
    {
        if (null === $value) {
            unset($this->options[$name]);
        } else {
            $this->options[$name] = $value;
        }
    }

    /**
     * Set all options from
     *
     * null value means reset to default.
     *
     * @param array $options
     */
    final public function setOptions(array $options)
    {
        foreach ($options as $name => $value) {
            $this->setOption($name, $value);
        }
    }

    /**
     * Normalize user input
     *
     * @param null|string|array
     *
     * @return array
     */
    final private function buildOptions($options)
    {
        if ($options) {
            if (!is_array($options)) {
                $options = ['class' => $options];
            }
            $options = array_merge($this->options, $options);
        } else {
            $options = $this->options;
        }

        return $options;
    }

    /**
     * {@inheritdoc}
     */
    final public function execute(array $parameters = [], $options = null) : ResultIteratorInterface
    {
        if (!$this->driver) {
            throw new GoatError("this query has no reference to any connection, therefore cannot execute itself");
        }

        return $this->driver->query($this, $parameters, $this->buildOptions($options));
    }

    /**
     * {@inheritdoc}
     */
    public function perform(array $parameters = [], $options = null) : int
    {
        if (!$this->driver) {
            throw new GoatError("this query has no reference to any connection, therefore cannot execute itself");
        }

        return $this->driver->perform($this, $parameters, $this->buildOptions($options));
    }
}
