[![Latest Stable Version]](https://packagist.org/packages/wikimedia/timestamp) [![License]](https://packagist.org/packages/wikimedia/timestamp)

Convertible Timestamp for PHP
===========================

This library provides a convenient wrapper around DateTime to
create, parse, and format timestamps.

Additional documentation about the library can be found on
[MediaWiki.org](https://www.mediawiki.org/wiki/Timestamp).

Supported formats
-----------------

On input, the following formats are recognized:

* A subset of [ISO 8601] basic and extended formats:
  * Years before 0000 or after 9999 are not supported.
  * Week and ordinal dates are not supported.
  * Accuracy to seconds is required. Fractions of a second are supported to microsecond resolution.
  * If the timezone is omitted, UTC is assumed.
  * As an extension, the 'T' may be replaced with a single space.
* As a signed integer (up to 13 digits) representing seconds since the Unix epoch.
  * Optionally with decimal seconds to microsecond resolution, using '.' as the decimal separator.
* [RFC 2822] format, including obsolete syntax.
  * CFWS tokens are not fully supported, use only FWS.
  * Note, per the RFC, all military timezones are considered as -0000.
  * As an extension, the timezone may be omitted entirely in which case UTC is assumed.
  * As an extension, anything after the first semicolon in the string is ignored.
* [RFC 850] format.
* [asctime] format.
* The `TS_MW`, `TS_DB`, `TS_POSTGRES`, `TS_ORACLE`, and `TS_EXIF` formats described below.

For output, the following conversions are predefined:

* `TS_DB`: MySQL datetime format: "2012-07-31 19:01:08"
* `TS_EXIF`: Exif 2.2 format: "2012:07:31 19:01:08"
* `TS_ISO_8601`: [ISO 8601] expanded format: "2012-07-31T19:01:08Z"
* `TS_ISO_8601_BASIC`: [ISO 8601] basic format: "20120731T190108Z"
* `TS_MW`: A 14-digit string: "20120731190108"
* `TS_ORACLE`: A default Oracle timestamp format: "31-07-2012 19:01:08.000000"
* `TS_POSTGRES`: PostgreSQL default timestamptz format: "2012-07-31 19:01:08+00"
* `TS_RFC2822`: [RFC 2822] format using an obsolete timezone: "Tue, 31 Jul 2012 19:01:08 GMT"
* `TS_UNIX`: Seconds since the Unix epoch (1970-01-01T00:00:00Z): "1343761268".
* `TS_UNIX_MICRO`: Seconds since the Unix epoch with microseconds: "1343761268.000000".

Usage
-----

```php
$ts = new ConvertibleTimestamp( '2012-07-31T19:01:08Z' );
$formatted = $ts->getTimestamp( TS_UNIX );

// Shorthand
$formatted = ConvertibleTimestamp::convert(
    TS_UNIX, '2012-07-31T19:01:08Z'
);

// Format using PHP date formatting codes
$formatted = $ts->format( 'Y-m-d H:i:s O' );
```


Running tests
-------------

    composer install --prefer-dist
    composer test


---
[Latest Stable Version]: https://poser.pugx.org/wikimedia/timestamp/v/stable.svg
[License]: https://poser.pugx.org/wikimedia/timestamp/license.svg
[ISO 8601]: https://en.wikipedia.org/wiki/ISO_8601
[RFC 2822]: https://www.rfc-editor.org/rfc/rfc2822.html#section-3.3
[RFC 850]: https://www.rfc-editor.org/rfc/rfc850.html#section-2.1.4
[asctime]: https://pubs.opengroup.org/onlinepubs/9699919799/functions/asctime.html
