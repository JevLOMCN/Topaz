[Excimer](https://www.mediawiki.org/wiki/Excimer) is an extension for PHP which
provides flexible userspace timers.

This library provides a global request timeout concept, ideally using Excimer,
but with a fallback to set_time_limit() if Excimer is not available.

Typical usage:

```
use Wikimedia\RequestTimeout\RequestTimeout;

RequestTimeout::singleton()->setWallTimeLimit( 20 );
```

This sets a timer for 20 seconds of wall clock time. When it expires, a
TimeoutException will be thrown.

It is possible to query the amount of time remaining:

```
if ( RequestTimeout::singleton()->getWallTimeRemaining() > 5 ) {
	do_slow_thing();
} else {
	do_fast_thing();
}
```

This works in the fallback mode.

The library provides a critical section concept. If a critical section is
active, timeouts will be queued, rather than immediately thrown. The timeout
exception will be thrown once no critical section is open.

```
$csp = RequestTimeout::singleton()->createCriticalSectionProvider( 5 );
$csp->enter( __METHOD__ );
try {
	do_something();
} finally {
	$csp->exit( __METHOD__ );
}
```

It is important to always exit a critical section. If the code in the critical
section can throw an error, try/finally can be used to ensure that the critical
section is exited. Alternatively we provide a scope variable model:

```
function foo() {
	$scope = $csp->scopedEnter( __METHOD__ );
	do_something();
}
```

The critical section exits when the scope object is destroyed. However, with
this method, it is important to not terminate the request during the critical
section, for example by calling `exit()`, or by keeping the scope object in
global variable after the function returns. The library may throw an exception
from a destructor during request shutdown, which causes a PHP fatal error.

Critical sections are not functional in the fallback mode.
