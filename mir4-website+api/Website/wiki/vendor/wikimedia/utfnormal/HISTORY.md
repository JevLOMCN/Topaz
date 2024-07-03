## 3.0.1 / 2021-01-30 ##
* Workaround mb_chr( 0 ) bug in PHP 7.2 (Kunal Mehta)
  * Utils::codepointToUtf8() is no longer deprecated.

## 3.0.0 / 2021-01-30 ##
* Replace a couple of methods with native functions (Max Semenik)
  * Utils::codepointToUtf8() is deprecated in favor of mb_chr()
  * Utils::utf8ToCodepoint() is deprecated in favor of mb_ord()
* Require mbstring PHP extension (Kunal Mehta)
* Drop support for PHP < 7.2, HHVM (James D. Forrester)
* Mark all constants as public (Reedy)

## 2.0.0 / 2018-02-26 ##
* Drop support for PHP 5.3 and PHP 5.4 (Timo Tijhof)
* Update to Unicode 8.0.0 (Brad Jorsch)

## 1.1.0 / 2016-10-05 ##
* Update to Unicode 6.3.0 (Brad Jorsch)

## 1.0.3 / 2015-08-29 ##
* Add description to composer.json (MarcoAurelio)
* Update repo bootstrap for libraries (Timo Tijhof)
* Add COPYING (Kunal Mehta)

## 1.0.2 / 2015-03-11 ##
* Use an explicit path when require'ing UtfNormalDataK.inc (Kunal Mehta)

## 1.0.1 / 2015-03-11 ##
* Ignore unnecessary files when exporting package (Kunal Mehta)

## 1.0.0 / 2015-03-06 ##
* Initial release, split from MediaWiki (Brion Vibber & Kunal Mehta)
