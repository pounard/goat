<?php

declare(strict_types=1);

namespace Goat\Debug;

use Symfony\Component\Validator\ConstraintViolationListInterface;

/**
 * This exception will only happen in debug mode, do never ever catch it
 *
 * @codeCoverageIgnore
 */
class RowValidationError extends \RuntimeException
{
    /**
     * Default constructor
     */
    public function __construct(ConstraintViolationListInterface $violations, array $typeMap, $row)
    {
        parent::__construct(
            $this->buildMessage($violations, $typeMap, $row)
        );
    }

    /**
     * Build a comprehensible message
     */
    private function buildMessage(ConstraintViolationListInterface $violations, array $typeMap, $row) : string
    {
        $messages = [];

        if ('object' === ($objectType = \gettype($row))) {
            $objectType = \get_class($row);
        }

        /** @var \Symfony\Component\Validator\ConstraintViolationInterface $violation */
        foreach ($violations as $violation) {
            $messages[] = \sprintf(
                "'%s': %s",
                $violation->getPropertyPath(),
                $violation->getMessage()
            );
        }

        return \sprintf(
            "There was %d violation(s) while hydrating with '%s' class: please ensure the SQL query result column types matches the hydrated object constraints: %s",
            $violations->count(),
            $objectType,
            \implode(", ", $messages)
        );
    }
}
