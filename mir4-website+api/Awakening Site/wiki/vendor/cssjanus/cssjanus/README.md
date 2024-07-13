[![Packagist](https://img.shields.io/packagist/v/cssjanus/cssjanus.svg?style=flat)](https://packagist.org/packages/cssjanus/cssjanus)

# CSSJanus

Convert CSS stylesheets between left-to-right and right-to-left.

## Usage

```php
transform( string $css, bool $swapLtrInURL = false, bool $swapLeftInURL = false ) : string
```

Parameters;

* ``$css`` (string) Stylesheet to transform.
* ``$swapLtrInURL`` (boolean) Swap `ltr` to `rtl` direction in URLs.
* ``$swapLeftInURL`` (boolean) Swap `left` and `right` edges in URLs.

Example:

```php
$rtlCss = CSSJanus::transform( $ltrCss );
```

### Preventing flipping

If a rule is not meant to be flipped by CSSJanus, use a `/* @noflip */` comment to protect the rule.

```css
.rule1 {
  /* Will be converted to margin-right */
  margin-left: 1em;
}
/* @noflip */
.rule2 {
  /* Will be preserved as margin-left */
  margin-left: 1em;
}
```

## Port

This is a PHP port of the Node.js implementation of CSSJanus. Feature requests and bugs related
to the actual CSS transformation logic or test cases of it, should be submitted upstream
at <https://github.com/cssjanus/cssjanus>.

CSSJanus was originally a [Google project](http://code.google.com/p/cssjanus/).

## Contribute

* Issue tracker: <https://phabricator.wikimedia.org/tag/cssjanus/>
* Source code: <https://gerrit.wikimedia.org/g/mediawiki/libs/php-cssjanus>
* Submit patches via Gerrit: <https://www.mediawiki.org/wiki/Developer_account>
