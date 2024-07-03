# Release History

## v4.1.1
* Catch a ValueError from DateTime::createFromFormat

## v4.1.0
* Add add(), sub() methods for date interval arithmetic
* setFakeTime: add $step parameter
* Add microtime() function
* Fix documentation for ConvertibleTimestamp.php

## v4.0.0
* Remove HHVM support code from setTimestamp()
* Drop PHP 7.0/7.1 and HHVM support
* Handle 2-digit years per RFC 2626

## v3.0.0
* BREAKING CHANGE: the library is now stricter about rejecting some invalid
  formats such as "Wed, 22 May 2019 12:00:00 +1 day" (which is a valid date
  spec in some tools but not in ConvertibleTimestamp which does not accept
  relative date modifiers) or "Wed, 22 May 2019 12:00:00 A potato" (where
  the trailing nonsense got silently ignored before this change).
* Time zones are handled more consistently and more correctly.
* Fix some bugs certain formats had with pre-Unix-epoch dates.
* Relax ISO 8601 syntax: allow space instead of T
* Improve ISO 8601 syntax compliance: accept comma as decimal separator,
  accept non-Z timezones.
* ConvertibleTimestamp::convert can take a DateTime now.

## v2.2.0
* Add ConvertibleTimestamp::time(), which works like the time() built-in but
  can be mocked in tests.

## v2.1.1
* Fix timezone handling in TS\_POSTGRES. Before, it generated a format that
  was accepted by Postgres but differed from what Postgres itself generates.

## v2.1.0
* Introduce a mock clock for unit testing.

## v2.0.0
* BREAKING CHANGE: drop PHP 5 support (HHVM in PHP 5 mode is still supported).
* Support microtime for Unix and Oracle formats.

## v1.0.0
* Initial commit
