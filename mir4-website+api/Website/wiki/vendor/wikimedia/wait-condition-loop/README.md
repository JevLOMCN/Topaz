[![Latest Stable Version]](https://packagist.org/packages/wikimedia/wait-condition-loop) [![License]](https://packagist.org/packages/wikimedia/wait-condition-loop)

Wait Condition Loop for PHP
===========================

This class is used for waiting on a condition to be reached, with the ability
to specify a timeout. The condition is considered reached when the condition callback
returns CONDITION_REACHED or true. CONDITION_ABORTED can also be used to stop the loop.

Additionally, "work" callbacks can be injected to prepare useful work instead of simply
having the current thread sleep or block on I/O. The loop will run one of these callbacks
on each iteration of checking the condition callback, as long as there are any left to run.

The loop class will automatically either retry the condition or usleep() before retrying it,
depending on CPU usage. Low CPU usage and significant real-time passage is used to detect
whether the condition callback appears to use blocking I/O. Use of usleep() will not occur
until all of the "work" callbacks have run. This means that the condition callback can
either be an "instant" CPU-bound check or a blocking I/O call with a small timeout. Both
cases should automatically work without CPU intensive spin loops.

Additional documentation about the library can be found on
[mediawiki.org](https://www.mediawiki.org/wiki/WaitConditionLoop).


Usage
-----

```php
// Pre-compute some value that will be needed later
$result = null;
$workCallback = function () use ( &$result ) {
    $result = ( $result !== null ) ? $result : $this->doWork();

    return $result
}

$loop = new WaitConditionLoop(
    function () use ( ... ) {
        if ( ... ) {
            // Condition reached; stop loop
            return WaitConditionLoop::CONDITION_REACHED;
        }
        // Condition not reached; keep checking
        return WaitConditionLoop::CONDITION_CONTINUE;
    },
    3.0, // timeout in seconds
    [ $workCallback ]
);
$status = $loop->invoke(); // CONDITION_* constant

// Call $workCallback as needed later
```

Running tests
-------------

    composer install
    composer test


---
[Latest Stable Version]: https://poser.pugx.org/wikimedia/wait-condition-loop/v/stable.svg
[License]: https://poser.pugx.org/wikimedia/wait-condition-loop/license.svg
