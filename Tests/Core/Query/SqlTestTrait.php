<?php

namespace Goat\Tests\Core\Query;

trait SqlTestTrait
{
    protected function normalize($string)
    {
        $string = preg_replace('@\s*(\(|\))\s*@ms', '$1', $string);
        $string = preg_replace('@\s*,\s*@ms', ',', $string);
        $string = preg_replace('@\s+@ms', ' ', $string);
        $string = strtolower($string);
        $string = trim($string);

        return $string;
    }

    public function assertSameSql($expected, $actual)
    {
        return $this->assertSame(
            $this->normalize($expected),
            $this->normalize($actual)
        );
    }
}