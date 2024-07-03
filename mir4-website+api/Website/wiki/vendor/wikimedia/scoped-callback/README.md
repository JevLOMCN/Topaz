[![Latest Stable Version]](https://packagist.org/packages/wikimedia/scoped-callback) [![License]](https://packagist.org/packages/wikimedia/scoped-callback)

ScopedCallback
==============

The ScopedCallback class allows for running a function after the
instance goes out of scope. It can be useful for making sure
teardown or cleanup functions run even if exceptions are thrown.
It also makes for a cleaner API for developers, by not requiring
the callback to be called manually each time.

Additional documentation about the library can be found on
[MediaWiki.org](https://www.mediawiki.org/wiki/ScopedCallback).


Usage
-----

```php
use Wikimedia\ScopedCallback;
$sc = new ScopedCallback( [ $this, 'teardown' ] );
// Even if this throws an exception, the callback will run
// or it'll run at the end of the function
$this->fooBar();
// If you want to manually call the callback
ScopedCallback::consume( $sc );
// or
unset( $sc );
// If you want to prevent it from being called
ScopedCallback::cancel( $sc );
```


Running tests
-------------

    composer install --prefer-dist
    composer test


---
[Latest Stable Version]: https://poser.pugx.org/wikimedia/scoped-callback/v/stable.svg
[License]: https://poser.pugx.org/wikimedia/scoped-callback/license.svg
