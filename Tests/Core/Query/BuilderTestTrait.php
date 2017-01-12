<?php

namespace Goat\Tests\Core\Query;

use Goat\Core\Query\SqlFormatter;

trait BuilderTestTrait
{
    private function normalize($string)
    {
        $string = preg_replace('@\s*(\(|\))\s*@ms', '$1', $string);
        $string = preg_replace('@\s*,\s*@ms', ',', $string);
        $string = preg_replace('@\s+@ms', ' ', $string);
        $string = strtolower($string);
        $string = trim($string);

        return $string;
    }

    protected function assertSameSql($expected, $actual)
    {
        return $this->assertSame(
            $this->normalize($expected),
            $this->normalize($actual)
        );
    }

    protected function createStandardSQLFormatter()
    {
        return new SqlFormatter(new NullEscaper());
    }
}
