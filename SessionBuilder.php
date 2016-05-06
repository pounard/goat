<?php

namespace Momm\Foundation;

use PommProject\Foundation\Inspector\InspectorPooler;
use Momm\Foundation\Converter\MyNumber;
use Momm\Foundation\Converter\MyTimestamp;
use Momm\Foundation\PreparedQuery\PreparedQueryPooler;
use Momm\Foundation\Session\SessionBuilder as VanillaSessionBuilder;

use PommProject\Foundation\Converter\ConverterPooler;
use PommProject\Foundation\Converter\ConverterHolder;
use PommProject\Foundation\Listener\ListenerPooler;
use PommProject\Foundation\Observer\ObserverPooler;
use PommProject\Foundation\QueryManager\QueryManagerPooler;
use PommProject\Foundation\Session\Session;

use PommProject\Foundation\Converter\PgArray as PommPgArray;
use PommProject\Foundation\Converter\PgString as PommPgString;

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
            ->registerClientPooler(new ObserverPooler())
            ->registerClientPooler(new InspectorPooler)
            ->registerClientPooler(new ListenerPooler())
            ;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    protected function initializeConverterHolder(ConverterHolder $converter_holder)
    {
        $converter_holder
            ->registerConverter('Array', new PommPgArray(), ['array'], false)
// @todo I do need to emulate this with tinyint(1|2)
//            ->registerConverter(
//                 'Boolean',
//                 new Converter\PgBoolean(),
//                 [
//                     'bool',
//                     'pg_catalog.bool',
//                     'boolean',
//                 ],
//                 false
//            )
            ->registerConverter(
                'Number',
                new MyNumber(),
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
                new PommPgString(),
                [
                    'varchar', 'pg_catalog.varchar',
                    'text', 'pg_catalog.text',
                    'uuid', 'pg_catalog.uuid',
                ],
                false
            )
            ->registerConverter(
                'Timestamp',
                new MyTimestamp(),
                [
                    'datetime', 'pg_catalog.timestamp',
                    'date', 'pg_catalog.date',
                    'time', 'pg_catalog.time',
                    'timestamp', 'pg_catalog.timestamp',
                ],
                false
            )
        ;

        return $this;
    }
}
