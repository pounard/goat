<?php

declare(strict_types=1);

namespace Goat\Converter;

use Goat\Converter\Impl\BooleanConverter;
use Goat\Converter\Impl\DecimalConverter;
use Goat\Converter\Impl\IntegerConverter;
use Goat\Converter\Impl\IntervalConverter;
use Goat\Converter\Impl\StringConverter;
use Goat\Converter\Impl\TimestampConverter;
use Goat\Error\ConfigurationError;

/**
 * Converter map contains references to all existing converters and is the
 * central point of all native to SQL or SQL to native type conversion.
 *
 * For speed, this implementation will proceed to primitive, SQL common and
 * a few engine specific types convertion.
 */
class DefaultConverter implements ConverterInterface
{
    /**
     * Get default converter map
     *
     * Please note that definition order is significant, some converters
     * canProcess() method may short-circuit some others, the current
     * definition order is kept during converters registration.
     *
     * @return array
     *   Keys are type identifiers, values are arrays containing:
     *     - first value is the converter class name
     *     - second value is a type aliases array
     */
    public static function getDefautConverterMap() : array
    {
        /*
         * Mapping from PostgreSQL 9.2
         *
        bigint 	int8 	signed eight-byte integer
        bigserial 	serial8 	autoincrementing eight-byte integer
        ## bit [ (n) ] 	  	fixed-length bit string
        ## bit varying [ (n) ] 	varbit 	variable-length bit string
        boolean 	bool 	logical Boolean (true/false)
        ## box 	  	rectangular box on a plane
        bytea 	  	binary data ("byte array")
        character [ (n) ] 	char [ (n) ] 	fixed-length character string
        character varying [ (n) ] 	varchar [ (n) ] 	variable-length character string
        ## cidr 	  	IPv4 or IPv6 network address
        ## circle 	  	circle on a plane
        date 	  	calendar date (year, month, day)
        double precision 	float8 	double precision floating-point number (8 bytes)
        ## inet 	  	IPv4 or IPv6 host address
        integer 	int, int4 	signed four-byte integer
        ## interval [ fields ] [ (p) ] 	  	time span
        ## json 	  	JSON data
        ## line 	  	infinite line on a plane
        ## lseg 	  	line segment on a plane
        ## macaddr 	  	MAC (Media Access Control) address
        ## money 	  	currency amount
        numeric [ (p, s) ] 	decimal [ (p, s) ] 	exact numeric of selectable precision
        ## path 	  	geometric path on a plane
        ## point 	  	geometric point on a plane
        ## polygon 	  	closed geometric path on a plane
        real 	float4 	single precision floating-point number (4 bytes)
        smallint 	int2 	signed two-byte integer
        smallserial 	serial2 	autoincrementing two-byte integer
        serial 	serial4 	autoincrementing four-byte integer
        text 	  	variable-length character string
        time [ (p) ] [ without time zone ] 	  	time of day (no time zone)
        time [ (p) ] with time zone 	timetz 	time of day, including time zone
        timestamp [ (p) ] [ without time zone ] 	  	date and time (no time zone)
        timestamp [ (p) ] with time zone 	timestamptz 	date and time, including time zone
        ## tsquery 	  	text search query
        ## tsvector 	  	text search document
        ## txid_snapshot 	  	user-level transaction ID snapshot
        ## uuid 	  	universally unique identifier
        ## xml 	  	XML data
         */

        return [
            'timestampz' => [TimestampConverter::class, ['timestamp', 'datetime']],
            'timez' => [TimestampConverter::class, ['time']],
            // 'varchar' => [StringConverter::class, ['character', 'char', 'text']],
            'date' => [TimestampConverter::class, []],
            // 'boolean' => [BooleanConverter::class, ['bool']],
            // 'bigint' => [IntegerConverter::class, ['int8']],
            // 'bigserial' => [IntegerConverter::class, ['serial8']],
            // 'integer' => [IntegerConverter::class, ['int', 'int4']],
            // 'serial' => [IntegerConverter::class, ['serial4']],
            // 'smallint' => [IntegerConverter::class, ['int2', 'smallserial', 'serial2']],
            // 'double' => [DecimalConverter::class, ['float8']],
            // 'numeric' => [DecimalConverter::class, ['decimal']],
            // 'real' => [DecimalConverter::class, ['float4']],
            'interval' => [IntervalConverter::class, []],
            'bytea' => [StringConverter::class, ['blob']],
        ];
    }

    private $aliasMap = [];
    private $converters = [];
    private $debug = false;
    private $fallback;

    /**
     * Default constructor
     */
    public function __construct()
    {
        $this->setFallback(new StringConverter());
    }

    /**
     * Toggle debug mode
     *
     * @param bool $debug
     */
    public function setDebug(bool $debug = true)
    {
        $this->debug = $debug;
    }

    /**
     * Set fallback converter
     *
     * @param string $fallback
     *
     * @return $this
     */
    public function setFallback(ConverterInterface $converter)
    {
        $this->fallback = $converter;

        return $this;
    }

    /**
     * Register a converter
     *
     * @param string $type
     * @param ConverterInterface $instance
     * @param string[] $aliases
     *
     * @return $this
     */
    public function register(string $type, ConverterInterface $instance, array $aliases = [], $allowOverride = false)
    {
        if (is_array($type)) {
            trigger_error(sprintf("registering type as array is outdate and will be removed, while registering %s", implode(', ', $type)), E_USER_DEPRECATED);

            $aliases = $type;
            $type = array_shift($aliases);
        }

        if (!$allowOverride && isset($this->converters[$type])) {
            $message = sprintf("type '%s' is already defined, using '%s' converter class", $type, get_class($this->converters[$type]));
            if ($this->debug) {
                throw new ConfigurationError($message);
            } else {
                trigger_error($message, E_USER_WARNING);
            }
        }

        $this->converters[$type] = $instance;

        if ($aliases) {
            foreach ($aliases as $alias) {

                $message = null;
                if (isset($this->converters[$alias])) {
                    $message = sprintf("alias '%s' for type '%s' is already defined as a type, using '%s' converter class", $alias, $type, get_class($this->converters[$type]));
                } else if (!$allowOverride && isset($this->aliasMap[$alias])) {
                    $message = sprintf("alias '%s' for type '%s' is already defined, for type '%s'", $alias, $type, get_class($this->aliasMap[$type]));
                }
                if ($message) {
                    if ($this->debug) {
                        throw new ConfigurationError($message);
                    } else {
                        trigger_error($message, E_USER_WARNING);
                    }
                }

                $this->aliasMap[$alias] = $type;
            }
        }

        return $this;
    }

    /**
     * Get converter for type
     *
     * @param string $type
     *
     * @return ConverterInterface
     */
    final protected function get($type)
    {
        if (isset($this->aliasMap[$type])) {
            $type = $this->aliasMap[$type];
        }

        if (!isset($this->converters[$type])) {
            if ($this->debug || !$this->fallback) {
                throw new ConfigurationError(sprintf("no converter registered for type '%s'", $type));
            }

            return $this->fallback;
        }

        return $this->converters[$type];
    }



    /**
     * {@inheritdoc}
     */
    public function fromSQL(string $type, $value)
    {
        switch ($type) {

            // Serial (integers)
            case 'bigserial':
            case 'serial':
            case 'serial2':
            case 'serial4':
            case 'serial8':
            case 'smallserial':
            // Integers
            case 'bigint':
            case 'int':
            case 'int2':
            case 'int4':
            case 'int8':
            case 'integer':
            case 'smallint':
                return (int)$value;

            // Strings
            case 'char':
            case 'character':
            case 'text':
            case 'varchar':
                return $value;

            // Flaoting point numbers and decimals
            case 'decimal':
            case 'double':
            case 'float4':
            case 'float8':
            case 'numeric':
            case 'real':
                return (float)$value;

            // Booleans
            case 'bool':
            case 'boolean':
                if (!$value || 'f' === $value || 'F' === $value || 'FALSE' === strtolower($value)) {
                    return false;
                }
                return (bool)$value;
        }

        return $this->get($type)->fromSQL($type, $value);
    }

    /**
     * {@inheritdoc}
     */
    public function toSQL(string $type, $value) : string
    {
        switch ($type) {

            // Serial (integers)
            case 'bigserial':
            case 'serial':
            case 'serial2':
            case 'serial4':
            case 'serial8':
            case 'smallserial':
            // Integers
            case 'bigint':
            case 'int':
            case 'int2':
            case 'int4':
            case 'int8':
            case 'integer':
            case 'smallint':
                return (string)(int)$value;

            // Strings
            case 'char':
            case 'character':
            case 'text':
            case 'varchar':
                return (string)$value;

            // Flaoting point numbers and decimals
            case 'decimal':
            case 'double':
            case 'float4':
            case 'float8':
            case 'numeric':
            case 'real':
                return (string)(float)$value;

            // Booleans
            case 'bool':
            case 'boolean':
                return $value ? 't' : 'f';
        }

        return $this->get($type)->toSQL($type, $value);
    }

    /**
     * {@inheritdoc}
     */
    public function needsCast(string $type) : bool
    {
        switch ($type) {

            // Serial (integers)
            case 'bigserial':
            case 'serial':
            case 'serial2':
            case 'serial4':
            case 'serial8':
            case 'smallserial':
            // Integers
            case 'bigint':
            case 'int':
            case 'int2':
            case 'int4':
            case 'int8':
            case 'integer':
            case 'smallint':
                return false;

            // Strings
            case 'char':
            case 'character':
            case 'text':
            case 'varchar':
                return false;

            // Flaoting point numbers and decimals
            case 'decimal':
            case 'double':
            case 'float4':
            case 'float8':
            case 'numeric':
            case 'real':
                return true;

            // Booleans
            case 'bool':
            case 'boolean':
                return true;
        }

        return $this->get($type)->needsCast($type);
    }

    /**
     * {@inheritdoc}
     */
    public function cast(string $type)
    {
        switch ($type) {

            // Serial (integers)
            case 'bigserial':
            case 'serial':
            case 'serial2':
            case 'serial4':
            case 'serial8':
            case 'smallserial':
            // Integers
            case 'bigint':
            case 'int':
            case 'int2':
            case 'int4':
            case 'int8':
            case 'integer':
            case 'smallint':
                return;

            // Strings
            case 'char':
            case 'character':
            case 'text':
            case 'varchar':
                return false;

            // Flaoting point numbers and decimals
            case 'decimal':
            case 'double':
            case 'float4':
            case 'float8':
            case 'numeric':
            case 'real':
                return;

            // Booleans
            case 'bool':
            case 'boolean':
                return;
        }

        return $this->get($type)->cast($type);
    }

    /**
     * {@inheritdoc}
     */
    public function canProcess($value) : bool
    {
        if (null === $value) {
            return false;
        }

        foreach ($this->converters as $converter) {
            if ($converter->canProcess($value)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Is there a type registered with this name
     *
     * @param string $type
     * @param bool $allowAliases
     *
     * @return bool
     */
    public function typeExists(string $type, bool $allowAliases = true) : bool
    {
        return isset($this->converters[$type]) || ($allowAliases && isset($this->aliasMap[$type]));
    }

    /**
     * Is the given type an alias
     *
     * @param string $type
     *
     * @return bool
     */
    public function isTypeAlias(string $type) : bool
    {
        return isset($this->aliasMap[$type]);
    }

    /**
     * Proceed to optimistic conversion using the first converter that accepts
     * the given value
     *
     * @param mixed $value
     *
     * @return mixed
     */
    public function guess($value)
    {
        if (null === $value) {
            return null;
        }

        if (is_int($value) || is_string($value)) {
            return (string)$value;
        }
        if (is_string($value)) {
            return $value;
        }
        if (is_bool($value)) {
            return $value ? 't' : 'f';
        }
        if (is_float($value) || is_numeric($value)) {
            return (string)(float)$value;
        }
        if ($value instanceof \DateTimeInterface) {
            return $this->get('timestampz')->toSQL('timestampz', $value);
        }

        foreach ($this->converters as $type => $converter) {
            if ($converter->canProcess($value)) {
                return $converter->toSQL($type, $value);
            }
        }

        return $value;
    }
}
