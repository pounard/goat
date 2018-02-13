<?php

declare(strict_types=1);

namespace Goat\Driver;

use Goat\Converter\ConverterAwareTrait;
use Goat\Converter\ConverterInterface;
use Goat\Converter\DefaultConverter;
use Goat\Error\QueryError;
use Goat\Query\QueryFactoryRunnerTrait;
use Goat\Query\Writer\EscaperAwareTrait;
use Goat\Query\Writer\EscaperInterface;
use Goat\Query\Writer\FormatterInterface;
use Goat\Runner\ResultIteratorInterface;
use Goat\Runner\RunnerTrait;

/**
 * Default implementation for connection, it handles for you:
 *
 *  - transaction handling, with security check for not creating a transaction
 *    twice at the same time; it uses weak references if the PHP weakref
 *    extension is enabled;
 *
 *  - query builders creation, you don't need to override any of this except for
 *    very peculiar drivers;
 *
 *  - query parameters rewriting and conversion, this is a tricky one but it's
 *    thoroughly tested: you should not rewrite this by yourself.
 */
abstract class AbstractDriver implements DriverInterface
{
    use ConverterAwareTrait;
    use EscaperAwareTrait;
    use QueryFactoryRunnerTrait;
    use RunnerTrait;

    private $debug = false;
    private $databaseInfo;
    protected $configuration = [];
    protected $converter;
    protected $dsn;
    protected $formatter;

    /**
     * Constructor
     *
     * @param Dsn $dsn
     * @param string[] $configuration
     */
    public function __construct(Dsn $dsn, array $configuration = [])
    {
        $this->dsn = $dsn;
        $this->configuration = $configuration;
        $this->escaper = $this->createEscaper();
        $this->formatter = $this->createFormatter();

        // Register an empty instance for the converter, in case.
        $this->setConverter(new DefaultConverter());
    }

    /**
     * {@inheritdoc}
     */
    public function setDebug(bool $debug = true)
    {
        $this->debug = $debug;
    }

    /**
     * {@inheritdoc}
     */
    public function isDebugEnabled() : bool
    {
        return $this->debug;
    }

    /**
     * Destructor, enforces connection close on dispose
     */
    public function __destruct()
    {
        $this->close();
    }

    /**
     * {@inheritdoc}
     */
    public function setConverter(ConverterInterface $converter)
    {
        $this->converter = $converter;
        $this->formatter->setConverter($converter);
    }

    /**
     * Create SQL formatter
     *
     * @return FormatterInterface
     */
    abstract protected function createFormatter() : FormatterInterface;

    /**
     * Create SQL escaper
     *
     * @return EscaperInterface
     */
    abstract protected function createEscaper() : EscaperInterface;

    /**
     * Fetch database information
     *
     * @return array
     *   Must contain the following key:
     *     -name: database server name
     *     - version: database server version
     *   It might contain abitrary other keys:
     *     - build
     *     - ...
     */
    abstract protected function fetchDatabaseInfo() : array;

    /**
     * Load database information
     */
    private function loadDatabaseInfo()
    {
        if (!$this->databaseInfo) {
            $this->databaseInfo = $this->fetchDatabaseInfo();
        }
    }

    /**
     * Get database server information
     *
     * @return string[]
     */
    final public function getDatabaseInfo() : array
    {
        $this->loadDatabaseInfo();

        return $this->databaseInfo;
    }

    /**
     * {@inheritdoc}
     */
    final public function getDatabaseName() : string
    {
        $this->loadDatabaseInfo();

        return $this->databaseInfo['name'];
    }

    /**
     * {@inheritdoc}
     */
    final public function getDatabaseVersion() : string
    {
        $this->loadDatabaseInfo();

        return $this->databaseInfo['version'];
    }

    /**
     * {@inheritdoc}
     */
    final public function getDriverName() : string
    {
        return $this->dsn->getDriver();
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
     * Do create iterator
     *
     * @param mixed[] $constructorArgs
     *   Driver specific parameters
     */
    abstract protected function doCreateResultIterator(...$constructorArgs) : ResultIteratorInterface;

    /**
     * Create the result iterator instance
     *
     * @param string[] $options
     *   Query options
     * @param mixed[] $constructorArgs
     *   Driver specific parameters
     *
     * @return ResultIteratorInterface
     */
    final protected function createResultIterator($options = null, ...$constructorArgs) : ResultIteratorInterface
    {
        $result = $this->doCreateResultIterator(...$constructorArgs);
        $result->setConverter($this->converter);

        if ($options) {
            if (is_string($options)) {
                $options = ['class' => $options];
            } else if (!is_array($options)) {
                throw new QueryError("options must be a valid class name or an array of options");
            }
        }

        if (isset($options['class'])) {
            // Class can be either an alias or a valid class name, the hydrator
            // will proceed with all runtime checks to ensure that.
            $result->setHydrator($this->hydratorMap->get($options['class']));
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function truncateTables($relationNames)
    {
        if (!$relationNames) {
            throw new QueryError("cannot not truncate no tables");
        }

        // SQL-92 implementation - only one table at a time
        if (!is_array($relationNames)) {
            $relationNames = [$relationNames];
        }

        foreach ($relationNames as $relation) {
            $this->perform(sprintf("truncate %s", $this->getEscaper()->escapeIdentifier($relation)));
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getEscaper() : EscaperInterface
    {
        return $this->escaper;
    }
}
