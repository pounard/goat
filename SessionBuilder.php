<?php

namespace Momm\Foundation;

use PommProject\Foundation\Inspector\InspectorPooler;
use PommProject\Foundation\QueryManager\QueryManagerPooler;
use PommProject\Foundation\PreparedQuery\PreparedQueryPooler;
use Momm\Foundation\Session\SessionBuilder as VanillaSessionBuilder;

use PommProject\Foundation\Converter\ConverterPooler;
use PommProject\Foundation\Converter\ConverterHolder;
use PommProject\Foundation\Session\Session;

class SessionBuilder extends VanillaSessionBuilder
{
    /**
     * {@inheritdoc}
     */
    protected function postConfigure(Session $session)
    {
        $session
            ->registerClientPooler(new PreparedQueryPooler)
            ->registerClientPooler(new QueryManagerPooler)
            ->registerClientPooler(new ConverterPooler(clone $this->converter_holder))
            ->registerClientPooler(new InspectorPooler)
            ;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    protected function initializeConverterHolder(ConverterHolder $converter_holder)
    {
        /*
        $converter_holder
            ->registerConverter('Array', new Converter\PgArray(), ['array'], false)
            ->registerConverter(
                'Boolean',
                new Converter\PgBoolean(),
                [
                    'bool',
                    'pg_catalog.bool',
                    'boolean',
                ],
                false
            )
            ->registerConverter(
                'Number',
                new Converter\PgNumber(),
                [
                    'int2', 'pg_catalog.int2',
                    'int4', 'pg_catalog.int4', 'int', 'integer',
                    'int8', 'pg_catalog.int8',
                    'numeric', 'pg_catalog.numeric',
                    'float4', 'pg_catalog.float4', 'float',
                    'float8', 'pg_catalog.float8',
                    'oid', 'pg_catalog.oid',
                ],
                false
            )
            ->registerConverter(
                'String',
                new Converter\PgString(),
                [
                    'varchar', 'pg_catalog.varchar',
                    'char', 'pg_catalog.char',
                    'text', 'pg_catalog.text',
                    'uuid', 'pg_catalog.uuid',
                    'tsvector', 'pg_catalog.tsvector',
                    'xml', 'pg_catalog.xml',
                    'bpchar', 'pg_catalog.bpchar',
                    'name', 'pg_catalog.name',
                    'character varying',
                    'regclass', 'pg_catalog.regclass',
                    'inet', 'pg_catalog.inet',
                    'cidr', 'pg_catalog.cidr',
                    'macaddr', 'pg_catalog.macaddr',
                ],
                false
            )
            ->registerConverter(
                'Timestamp',
                new Converter\PgTimestamp(),
                [
                    'timestamp', 'pg_catalog.timestamp',
                    'date', 'pg_catalog.date',
                    'time', 'pg_catalog.time',
                    'timestamptz', 'pg_catalog.timestamptz',
                ],
                false
            )
            ->registerConverter('Interval', new Converter\PgInterval(), ['interval', 'pg_catalog.interval'], false)
            ->registerConverter('Binary', new Converter\PgBytea(), ['bytea', 'pg_catalog.bytea'], false)
            ->registerConverter('Point', new Converter\Geometry\PgPoint(), ['point', 'pg_catalog.point'], false)
            ->registerConverter('Circle', new Converter\Geometry\PgCircle(), ['circle', 'pg_catalog.circle'], false)
            ->registerConverter(
                'JSON',
                new Converter\PgJson(),
                [
                    'json',
                    'jsonb',
                    'pg_catalog.json',
                    'pg_catalog.jsonb'
                ],
                false
            )
            ->registerConverter(
                'NumberRange',
                new Converter\PgNumRange(),
                [
                    'int4range', 'pg_catalog.int4range',
                    'int8range', 'pg_catalog.int8range',
                    'numrange', 'pg_catalog.numrange',
                ],
                false
            )
            ->registerConverter(
                'TsRange',
                new Converter\PgTsRange(),
                [
                    'tsrange',
                    'pg_catalog.tsrange',
                    'daterange',
                    'pg_catalog.daterange',
                    'tstzrange',
                    'pg_catalog.tstzrange',
                ],
                false
            )
            ;
*/
        return $this;
    }
}
