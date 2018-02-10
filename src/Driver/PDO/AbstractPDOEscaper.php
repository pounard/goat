<?php

declare(strict_types=1);

namespace Goat\Driver\PDO;

use Goat\Query\Writer\EscaperBase;

/**
 * PDO based SQL escaper
 */
abstract class AbstractPDOEscaper extends EscaperBase
{
    /**
     * @var \PDO
     */
    private $pdo;

    public function __construct(\PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Get PDO instance, connect if not connected
     *
     * @return \PDO
     */
    protected function getPdo() : \PDO
    {
        return $this->pdo;
    }

    /**
     * {@inheritdoc}
     */
    public function escapeLiteral(string $string) : string
    {
        return $this->getPdo()->quote($string, \PDO::PARAM_STR);
    }

    /**
     * {@inheritdoc}
     */
    public function escapeLike(string $string) : string
    {
        return addcslashes($string, '\%_');
    }

    /**
     * {@inheritdoc}
     */
    public function escapeBlob(string $word) : string
    {
        return $this->getPdo()->quote($word /*, \PDO::PARAM_LOB */);
    }

    /**
     * {@inheritdoc}
     */
    public function unescapeBlob(string $blob) : string
    {
        return $blob;
    }
}
