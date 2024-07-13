[![Latest Stable Version]](https://packagist.org/packages/wikimedia/base-convert) [![License]](https://packagist.org/packages/wikimedia/base-convert)

Improved base_convert for PHP
=============================

PHP's `base_convert` function does not handle large numbers well. This
library does, and can also optionally zero-pad to a minimum column width.

It is a drop-in replacement for base_convert, supporting base 2 through 36,
and has additional features for padding and case.

The gmp and bcmath extensions are optionally used for improved performance.

Additional documentation about the library can be found on
[MediaWiki.org](https://www.mediawiki.org/wiki/base_convert).


Usage
-----

```php
// Using padding, outputs 01010
\Wikimedia\base_convert( 'a', '36', '2', '5' )
```


Running tests
-------------

    composer install --prefer-dist
    composer test


History
-------

This library was first introduced in [MediaWiki 1.7][] ([r14777][]),
and overhauled in [MediaWiki 1.21][] ([9b9daadc46][]). It was split
out of the MediaWiki codebase and published as an independent library
during the [MediaWiki 1.27][] development cycle.


---
[MediaWiki 1.7]: https://www.mediawiki.org/wiki/MediaWiki_1.7
[r14777]: https://www.mediawiki.org/wiki/Special:Code/MediaWiki/14777
[MediaWiki 1.21]: https://www.mediawiki.org/wiki/MediaWiki_1.21
[9b9daadc46]: https://gerrit.wikimedia.org/r/40552
[MediaWiki 1.27]: https://www.mediawiki.org/wiki/MediaWiki_1.27
[Latest Stable Version]: https://poser.pugx.org/wikimedia/base-convert/v/stable.svg
[License]: https://poser.pugx.org/wikimedia/base-convert/license.svg
