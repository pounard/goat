<?php

declare(strict_types=1);

namespace Goat\Debug;

use Goat\Converter\ConverterMap;
use Goat\Hydrator\HydratorInterface;
use Goat\Runner\ResultIteratorInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Debug result iterator at runtime
 *
 * @codeCoverageIgnore
 */
final class DebugResultIterator implements ResultIteratorInterface
{
    private $result;
    private $validator;

    /**
     * Default constructor
     */
    public function __construct(ResultIteratorInterface $result, ValidatorInterface $validator)
    {
        $this->result = $result;
        $this->validator = $validator;
    }

    /**
     * Validate row using the validator and raise exception if invalid
     *
     * @param mixed $row
     *   The hydrated row
     *
     * @return mixed
     *   The very same hydrated row
     *
     * @throws \Goat\Debug\RowValidationError
     */
    private function validateRow($row)
    {
        $violations = $this->validator->validate($row);

        if ($violations->count()) {
            $typeMap = [];
            foreach ($this->result->getColumnNames() as $name) {
                $typeMap[$name] = $this->result->getColumnType($name);
            }

            throw new RowValidationError($violations, $typeMap, $row);
        }

        return $row;
    }

    /**
     * {@inheritdoc}
     */
    public function setConverter(ConverterMap $converter)
    {
        return $this->result->setConverter($converter);
    }

    /**
     * {@inheritdoc}
     */
    public function setHydrator(HydratorInterface $hydrator)
    {
        return $this->result->setHydrator($hydrator);
    }

    /**
     * {@inheritdoc}
     */
    public function count()
    {
        return $this->result->count();
    }

    /**
     * {@inheritdoc}
     */
    public function getIterator()
    {
        foreach ($this->result as $key => $row) {
            yield $key => $this->validateRow($row);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function setKeyColumn(string $name)  : ResultIteratorInterface
    {
        $this->result->setKeyColumn($name);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function countColumns() : int
    {
        return $this->result->countColumns();
    }

    /**
     * {@inheritdoc}
     */
    public function countRows() : int
    {
        return $this->result->countRows();
    }

    /**
     * {@inheritdoc}
     */
    public function columnExists(string $name) : bool
    {
        return $this->result->columnExists($name);
    }

    /**
     * {@inheritdoc}
     */
    public function getColumnNames() : array
    {
        return $this->result->getColumnNames();
    }

    /**
     * {@inheritdoc}
     */
    public function getColumnType(string $name) : string
    {
        return $this->result->getColumnType($name);
    }

    /**
     * {@inheritdoc}
     */
    public function getColumnName(int $index) : string
    {
        return $this->result->getColumnName($index);
    }

    /**
     * {@inheritdoc}
     */
    public function fetchField($name = null)
    {
        return $this->result->fetchField($name);
    }

    /**
     * {@inheritdoc}
     */
    public function fetchColumn($name = null)
    {
        return $this->result->fetchColumn($name);
    }

    /**
     * {@inheritdoc}
     */
    public function fetch()
    {
        return $this->validateRow($this->result->fetch());
    }
}
