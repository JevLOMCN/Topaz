# Changelog

## 4.1.0

Fixed:
* Only replace spaces between HTML tags - [T348402](https://phabricator.wikimedia.org/T348402)
* Avoid ungreedy pcre matching - [T345319](https://phabricator.wikimedia.org/T345319)

Changed:
* Use `InvalidArgumentException` instead of `Exception`

Others:
* Upgrade phpunit to 9.6.16 - [T342110](https://phabricator.wikimedia.org/T342110)
* Upgrade mediawiki/mediawiki-phan-config to 0.14.0
* Upgrade mediawiki/mediawiki-codesniffer to v43.0.0
* Fix casing of MediaWiki in a comment

## 4.0.3

Fixed:
* Fixed a regression in HtmlFormatter::getText() when passed an element

## 4.0.2

Fixed:
* ReturnTypeWillChange attribute added to more overridden functions to ease migration.

## 4.0.1

Fixed:
* ReturnTypeWillChange attribute added to overridden functions to ease migration.

## 4.0.0

The library now requires PHP 7.4 or later.

Fixed:
* HTML is parsed as whole document to avoid encoding issues.

Changed:
* Return values added to functions.
* Types added to function parameters.

Removed:
* PHP 7.2 and 7.3 are no longer supported.

## 3.0.1

Fixed:
* Made comment stripping independent of tag flattening.

## 3.0.0

Changed:
* Don't strip comments from HTML by default. Comments are still
  removed when `flattenAllTags` is called. Comments can also be
  removed by using `setRemoveComments`.

## 2.0.1

Added PHP 8 support.

## 2.0.0

The library now requires PHP 7.2 or later.

Added:
* Improve documentation typehints for HtmlFormatter class methods.

Changed:
* Use PSR-4 autoloading instead of a classmap.

Removed:
* PHP 5.5, 5.6, 7.0, and 7.1 are no longer supported.

Fixed:
* A CSS class selector passed to `HtmlFormatter::remove()` could previously
  remove unrelated elements, due to `.foo` wrongly matching `class="no-foo"`
  or `class="foo-bar"`. This has been fixed. ([T231160](https://phabricator.wikimedia.org/T231160))

## 1.0.2

Removed:
* Remove the superfuluous fallback for `mb_convert_encoding`.
  This package already has a dependency on `ext-mbstring`.

Fixed:
* Use SPDX 3.0 license identifier in `composer.json`.

## 1.0.1

Removed:
* Remove dependency on `ext-intl`.

## 1.0.0

Initial release
