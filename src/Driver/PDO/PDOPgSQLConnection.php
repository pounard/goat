<?php

declare(strict_types=1);

namespace Goat\Driver\PDO;

use Goat\Converter\ConverterInterface;
use Goat\Driver\PgSQL\PgSQLConverter;
use Goat\Error\QueryError;
use Goat\Query\Driver\PDOPgSQLFormatter;
use Goat\Query\Writer\EscaperInterface;
use Goat\Query\Writer\FormatterInterface;
use Goat\Runner\Transaction;

class PDOPgSQLConnection extends AbstractPDOConnection
{
    /**
     * {@inheritdoc}
     */
    public function setConverter(ConverterInterface $converter)
    {
        parent::setConverter(new PgSQLConverter($converter));
    }

    /**
     * {@inheritdoc}
     */
    protected function createFormatter() : FormatterInterface
    {
        return new PDOPgSQLFormatter($this->getEscaper());
    }

    /**
     * {@inheritdoc}
     */
    protected function createEscaper() : EscaperInterface
    {
        return new PDOPgSQLEscaper($this->getPdo());
    }

    /**
     * Send PDO configuration
     */
    protected function sendConfiguration(array $configuration)
    {
        $pdo = $this->getPdo();

        foreach ($configuration as $key => $value) {
            $pdo->query(sprintf(
                "SET %s TO %s",
                $this->getEscaper()->escapeIdentifier($key),
                $this->getEscaper()->escapeLiteral($value)
            ));
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    protected function fetchDatabaseInfo() : array
    {
        $row = $this->getPdo()->query("select version();")->fetch(\PDO::FETCH_ASSOC);

        // Example string to parse:
        //   PostgreSQL 9.2.9 on x86_64-unknown-linux-gnu, compiled by gcc (GCC) 4.4.7 20120313 (Red Hat 4.4.7-4), 64-bit
        $string = reset($row);
        $pieces = explode(', ', $string);
        $server = explode(' ', $pieces[0]);

        return [
            'name'    => $server[0],
            'version' => $server[1],
            'arch'    => $pieces[2],
            'os'      => $server[3],
            'build'   => $pieces[1],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function supportsReturning() : bool
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsDeferingConstraints() : bool
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    protected function doStartTransaction(int $isolationLevel = Transaction::REPEATABLE_READ) : Transaction
    {
        return new PgSQLTransaction($this, $isolationLevel);
    }

    /**
     * {@inheritdoc}
     */
    public function truncateTables($relationNames)
    {
        if (!$relationNames) {
            throw new QueryError("cannot not truncate no tables");
        }

        $this->perform(sprintf("truncate %s", $this->getEscaper()->escapeIdentifierList($relationNames)));
    }

    /**
     * {@inheritdoc}
     */
    public function escapeIdentifier(string $string) : string
    {
        return '"' . str_replace('"', '""', $string) . '"';
    }

    /**
     * {@inheritdoc}
     */
    public function setClientEncoding(string $encoding)
    {
        // https://www.postgresql.org/docs/9.3/static/multibyte.html#AEN34087
        // @todo investigate differences between versions
        $this
            ->getPdo()
            ->query(
                sprintf(
                    "SET CLIENT_ENCODING TO %s",
                    $this->getEscaper()->escapeLiteral($encoding)
                )
            )
        ;
    }
}
