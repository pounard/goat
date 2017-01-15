# Performance considerations

## SQL formatter

### implode() + sprintf() VS string concatenation

Benchmarks have carefully written, extending the standard SQL formatter in order
to rewrite it to use PHP string concatenation instead of ``sprintf()`` +
``implode()``` calls.

You can look at the ``\Goat\Benchmark\SqlFormatterBenchmark`` class.


### Notes

You should take a few things into consideration:

 *  because the SQL formatter reference implementation will continue to evolve,
    and the concatenation based test implementation will not, the benchmark is
    outdated and biased;

 *  only methods linked to the select query formatting have been rewritten
    since it's the only benchmarked query building, it's also the most complex
    one so I guess that's fair to test only this one;

 *  where statement formatting has *not* been rewritten, due to complexity.


### Results

Here are the raw results collected at the time:

```
 > ./vendor/bin/phpbench run --report=aggregate --revs=500 --iterations=200 --warmup=3

PhpBench 0.13.0. Running benchmarks.
Using configuration file: /var/www/goat/phpbench.json

\Goat\Benchmark\SqlFormatterBenchmark

    benchNormalSelect             I199 P0   [μ Mo]/r: 96.670 96.152 (μs)    [μSD μRSD]/r: 1.908μs 1.97%
    benchConcatSelect             I199 P0   [μ Mo]/r: 91.857 91.526 (μs)    [μSD μRSD]/r: 1.251μs 1.36%

2 subjects, 400 iterations, 1,000 revs, 0 rejects
(best [mean mode] worst) = 88.836 [94.264 93.839] 97.218 (μs)
⅀T: 37,705.472μs μSD/r 1.580μs μRSD/r: 1.668%
suite: 133c58330e1143dcd42feb997b8a4d8aa4c6a06d, date: 2017-01-15, stime: 12:00:35
+-----------------------+-------------------+--------+--------+------+-----+------------+----------+----------+----------+-----------+---------+--------+--------+
| benchmark             | subject           | groups | params | revs | its | mem_peak   | best     | mean     | mode     | worst     | stdev   | rstdev | diff   |
+-----------------------+-------------------+--------+--------+------+-----+------------+----------+----------+----------+-----------+---------+--------+--------+
| SqlFormatterBenchmark | benchNormalSelect |        | []     | 500  | 200 | 1,474,312b | 93.008μs | 96.670μs | 96.152μs | 109.402μs | 1.908μs | 1.97%  | +5.24% |
| SqlFormatterBenchmark | benchConcatSelect |        | []     | 500  | 200 | 1,474,312b | 88.836μs | 91.857μs | 91.526μs | 97.218μs  | 1.251μs | 1.36%  | 0.00%  |
+-----------------------+-------------------+--------+--------+------+-----+------------+----------+----------+----------+-----------+---------+--------+--------+
```

This one of the many runs, and one with the most significant difference.


### Conclusion

Regarding the test results and notes we can conclude that:

 *  5% difference over the most significant test run is not enough to trigger a
    full rewrite of the SQL formatter;

 *  we may gain more by rewriting the most complex part: the where statement
    formatting;

 *  **by looking at the code, the string concatenation version is much less**
    **readable and maintainable, since it's a critical part to maintain, 5%**
    **gain on this very specific code path is not enough to sacrifice code**
    **readability over performance**;

 *  you may also consider that in various profiling traces, relative time of
    query formatting represents only a few percent of the whole query time
    which reduces this performance gain to only *5% of a few percent*.

**We will NOT try to improve the SQL formatter by changing implode()**
**+ sprintf() calls to string concatenation.**
