Wikimedia ObjectFactory
=======================

Construct objects from configuration instructions.

It can be used statically, or as a service wrapping a PSR-11 service container
for lazy instantiation of objects with dependency injection.

Specification array
-------------------

Contents of the specification array are as follows:

    'factory' => callable,
    'class' => string,

The specification array must contain either a 'class' key with string value
that specifies the class name to instantiate or a 'factory' key with a
callable (is_callable() === true). If both are passed, 'factory' takes
precedence but an InvalidArgumentException will be thrown if the resulting
object is not an instance of the named class.

    'args' => array,
    'closure_expansion' => bool, // default true
    'spec_is_arg' => bool, // default false
    'services' => (string|null)[], // default empty
    'optional_services' => (string|null)[], // default empty

The 'args' key, if provided, specifies arguments to pass to the constructor/callable.
Values in 'args' which are Closure instances will be expanded by invoking
them with no arguments before passing the resulting value on to the
constructor/callable. This can be used to pass live objects to the
constructor/callable. This behavior can be suppressed by adding
closure_expansion => false to the specification.

If 'spec_is_arg' => true is in the specification, 'args' is ignored. The
entire spec array is passed to the constructor/callable instead.

If 'services' is supplied and non-empty (and a service container is available),
the named services are requested from the PSR-11 service container and
prepended before 'args'. `null` values in 'services' are passed to the constructor
unchanged.

Optional services declared via 'optional_services' are handled the same,
except that if the service is not available from the service container
`null` is passed as a parameter instead. Optional services are appended
directly after the normal required services

If any extra arguments are passed in the options to getObjectFromSpec() or
createObject(), these are prepended before the 'services' and 'args'.
These are passed using the `extraArgs` key, and any Closure instances that
are included this way are never expanded, regardless of whether the main
argument list is expanded.

    'calls' => array

The specification may also contain a 'calls' key that describes method
calls to make on the newly created object before returning it. This
pattern is often known as "setter injection". The value of this key is
expected to be an associative array with method names as keys and
argument lists as values. The argument list will be expanded (or not)
in the same way as the 'args' key for the main object.

Note these calls are not passed the extra arguments.

Installation
------------

```
$ composer require wikimedia/object-factory
```

Usage
-----

```php
<?php

$specs = [
	// Simple constructor based injection
	'testDB' => [
		'class' => PDO::class,
			'args' => [
				'mysql:dbname=testdb;host=127.0.0.1',
				'dbuser',
				'dbpass',
			],
	],
];

$db = ObjectFactory::getObjectFromSpec( $specs['testDB'] ):
```

License
-------
Wikimedia ObjectFactory is licensed under the GNU General Public License,
version 2 and any later version (GPL-2.0-or-later). See the
[`COPYING`](COPYING) file for more details.
