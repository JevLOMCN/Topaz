[![Latest Stable Version]](https://packagist.org/packages/wikimedia/php-session-serializer) [![License]](https://packagist.org/packages/wikimedia/php-session-serializer)

php-session-serializer
======================

php-session-serializer is a PHP library that provides methods that work like
PHP's [session_encode][phpencode] and [session_decode][phpdecode]
functions, but don't mess with the `$_SESSION` superglobal.

It supports the `php`, `php_binary`, and `php_serialize` serialize handlers.
`wddx` is not supported, since it is inferior to `php` and `php_binary`.


Usage
-----

```php
use Wikimedia\PhpSessionSerializer;

// (optional) Send logs to a PSR-3 logger
PhpSessionSerializer::setLogger( $logger )

// (optional) Ensure that session.serialize_handler is set to a usable value
PhpSessionSerializer::setSerializeHandler();

// Encode session data
$string = PhpSessionSerializer::encode( $array );

// Decode session data
$array = PhpSessionSerializer::decode( $string );
```

Running tests
-------------

    composer install --prefer-dist
    composer test


History
-------

This library was created to support custom session handler [read][] and
[write][] methods that are more useful than blindly storing the serialized data
that PHP gives to custom handlers.


---
[phpencode]: https://php.net/manual/en/function.session-encode.php
[phpdecode]: https://php.net/manual/en/function.session-decode.php
[read]: https://php.net/manual/en/sessionhandlerinterface.read.php
[write]: https://php.net/manual/en/sessionhandlerinterface.write.php
[Latest Stable Version]: https://poser.pugx.org/wikimedia/php-session-serializer/v/stable.svg
[License]: https://poser.pugx.org/wikimedia/php-session-serializer/license.svg
