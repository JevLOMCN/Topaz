[![Latest Stable Version]](https://packagist.org/packages/wikimedia/normalized-exception) [![License]](https://packagist.org/packages/wikimedia/normalized-exception)

NormalizedException
=====================

A minimal library to facilitate PSR-3-friendly exception handling.

Additional documentation about the library can be found on
[MediaWiki.org](https://www.mediawiki.org/wiki/NormalizedException).


Usage
-----

```php
use Wikimedia\NormalizedException\INormalizedException;
use Wikimedia\NormalizedException\NormalizedExceptionTrait;

class MyException extends SomeException implements INormalizedException {
	use NormalizedExceptionTrait;

	private $normalizedMessage;
	private $context;

	public function __construct( string $normalizedMessage, array $context = [] ) {
		$this->getMessageFromNormalizedMessage( $normalizedMessage, $context );
		$this->normalizedMessage = $normalizedMessage;
		$this->context = $context;
	}
}
```

```php
throw new MyException( 'Invalid value: {value}', [ 'value' => $value ] );
```

Running tests
-------------

```
composer install --dev
composer test
```

History
-------

This library was split out of [MediaWiki][] changeset [670465][] during the
MediaWiki 1.37 development cycle.


---
[MediaWiki]: https://www.mediawiki.org/wiki/MediaWiki
[670465]: https://gerrit.wikimedia.org/r/c/mediawiki/core/+/670465
[Latest Stable Version]: https://poser.pugx.org/wikimedia/normalized-exception/v/stable.svg
[License]: https://poser.pugx.org/wikimedia/normalized-exception/license.svg
