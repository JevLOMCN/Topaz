RunningStat
===========

RunningStat computes the central tendency, shape, and extrema of a set of
points online, in constant space. It uses a neat one-pass algorithm for
calculating variance, described here:
<https://en.wikipedia.org/wiki/Algorithms_for_calculating_variance#On-line_algorithm>

This particular implementation adapts a sample C++ implementation by John D.
Cook to PHP. See also:

* <http://www.johndcook.com/standard_deviation.html>
* <http://www.johndcook.com/skewness_kurtosis.html>

RunningStat instances can be combined. The resultant RunningStat has the same
state it would have had if it had been used to accumulate each point. This
property is attractive because it allows separate threads of execution to
process a stream in parallel. More importantly, individual points can be
accumulated in stages, without loss of fidelity, at intermediate points in the
aggregation process. JavaScript profiling samples can be accumulated in the
user's browser and be combined with measurements from other browsers on the
profiling data aggregator. Functions that are called multiple times in the
course of a profiled web request can be accumulated in MediaWiki prior to being
transmitted to the profiling data aggregator.

Usage
-----
Here is how you use it:

```php
use Wikimedia\RunningStat;

$rstat = new RunningStat();
foreach ( [
  49.7168, 74.3804,  7.0115, 96.5769, 34.9458,
  36.9947, 33.8926, 89.0774, 23.7745, 73.5154,
  86.1322, 53.2124, 16.2046, 73.5130, 10.4209,
  42.7299, 49.3330, 47.0215, 34.9950, 18.2914,
] as $sample ) {
  $rstat->addObservation( $sample );
}


printf(
  "n = %d; min = %.2f; max = %.2f; mean = %.2f; variance = %.2f; stddev = %.2f\n",
  count( $rstat ),
  $rstat->min,
  $rstat->max,
  $rstat->getMean(),
  $rstat->getVariance(),
  $rstat->getStdDev()
);
// Output:
// n = 20; min = 7.01; max = 96.58; mean = 47.59; variance = 725.71; stddev = 26.94
```

License
-------
GPL-2.0-or-later
