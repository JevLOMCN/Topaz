# Changelog

## 4.0.1 (2021-08-04)

Fixed:

* Throw when creating WrappedString with invalid prefix or suffix. (Timo Tijhof) [T118663](https://phabricator.wikimedia.org/T118663)

## 4.0.0 (2020-02-29)

Removed:

* Drop support for PHP 5.5, 5.7, 7.0, 7.1, and HHVM. Require PHP 7.2+. (James D. Forrester)

## 3.0.1 (2018-06-05)

Fixed:

* Fix handling of empty lists. (Timo Tijhof) [T196496](https://phabricator.wikimedia.org/T196496)

## 3.0.0 (2018-05-23)

Removed:

* Remove compat layer for `WrappedString\` namespace. (Reedy)

Fixed:

* Fix merging of nested lists. (Timo Tijhof)

## 2.3.0 (2017-12-30)

Added:

* Change namespace from `WrappedString\` to `Wikimedia\`, with compat aliases. (Reedy)

## 2.2.0 (2016-07-21)

Changed:

* Improve combination logic to merge across lists and non-lists. (Timo Tijhof)

## 2.1.1 (2016-07-07)

Fixed:

* Fix merging of incompatible WrappedStringList objects. (Timo Tijhof)

## 2.1.0 (2016-07-07)

Added:

* Add WrappedStringList class. (Timo Tijhof)

## 2.0.0 (2015-07-30)

Changed:

* [BREAKING CHANGE] Require `$value` to include the specified prefix and suffix. (Timo Tijhof)

Added:

* Support mixing primitive strings with WrappedString objects. (Timo Tijhof)

## 1.0.0 (2015-07-29)

Initial release.
