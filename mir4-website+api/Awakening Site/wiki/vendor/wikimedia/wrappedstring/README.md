WrappedString
=============

WrappedString is a small PHP library for compacting redundant string-wrapping
code in text output. The most common use-case is to eliminate redundant runs of
HTML open/close tags and JavaScript boilerplate.

Here is how you use it:

```php
use Wikimedia\WrappedString;

$buffer = [
	new WrappedString( '[foo]', '[', ']' ),
	new WrappedString( '[bar]', '[', ']' ),
];
$output = WrappedString::join( "\n", $buffer );
// Result: '[foobar]'
```

License
-------

The project is licensed under the MIT license.
