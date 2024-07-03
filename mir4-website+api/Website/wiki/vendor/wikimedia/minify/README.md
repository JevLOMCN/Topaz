# wikimedia/minify

Minify is a PHP library for minification of JavaScript code and CSS stylesheets.

## Quick start

Install using [Composer](https://getcomposer.org/), from [Packagist.org](https://packagist.org/packages/wikimedia/minify):

```
composer require wikimedia/minify
```

## Usage

```php
use Wikimedia\Minify\JavaScriptMinifier;

$input = '
/**
 * @param a
 * @param b
 */
function sum(a, b) {
	// Add it up!
	return a + b;
}
';

$output = JavaScriptMinifier::minify( $input );
// Result:
// function sum(a,b){return a+b;}
```

```php
use Wikimedia\Minify\CSSMin;

$input = '
.foo,
.bar {
	/* comment */
	prop: value;
}
';

$output = CSSMin::minify( $input );
// Result:
// .foo,.bar{prop:value}
```

## Known limitations

The following trade-offs were made for improved runtime performance and code
simplicity. If they cause problems in real-world applications without trivial
workarounds, please let us know!

* [T37492](https://phabricator.wikimedia.org/T37492): In CSS, content within quoted
  strings that looks like source code are sometimes minified.

* [T287631](https://phabricator.wikimedia.org/T287631): In CSS, writing a URL
  over multiple lines with escaped line-breaks is not supported.

## Contribute

* Issue tracker: <https://phabricator.wikimedia.org/tag/wikimedia-minify/>
* Source code: <https://gerrit.wikimedia.org/g/mediawiki/libs/Minify>
* Submit patches via Gerrit: <https://www.mediawiki.org/wiki/Developer_account>

## See also

* High-level documentation: <https://www.mediawiki.org/wiki/ResourceLoader/Architecture>
