# UnicodeJS Release History

## v13.0.3 / 2021-04-23
* Always check prevCodepoint is non-null (Ed Sanders)

## v13.0.2 / 2021-01-14
* Use "this" instead of "wordbreak" to make wordbreak extendable (Mehmet Coskun)

## v13.0.1 / 2020-10-08
* Use next/prevCodepoint for checking for sot/eot (Ed Sanders)
* Use local wordbreak cached variable consistently (Ed Sanders)
* Set code coverage requirement to 100% with one inline ignore (Ed Sanders)
* Wordbreak: Drop rule WB14 (Ed Sanders)

## v13.0.0 / 2020-05-27
* Update data to Unicode 13.0.0 (Ed Sanders)
* Update data to Unicode 12.1.0 (Ed Sanders)
* Rewrite grapheme break without regexes (Ed Sanders)

## v12.0.0 / 2019-05-29
We now number our releases based on Unicode codepoint releases.

* [BREAKING CHANGE] Go back to storing strings as code units (Ed Sanders)
* Update to Unicode 9.0.0 (Ed Sanders)
* Update to Unicode 10.0.0 (James D. Forrester)
* Update to Unicode 11.0.0 (Ed Sanders)
* Update to Unicode 12.0.0 (Ed Sanders)
* Add official grapheme break tests (Ed Sanders)

## v0.2.2 / 2018-09-22
* Add UMD wrapper (Ed Sanders)

## v0.2.1 / 2018-02-11
* Bump copyright year (James D. Forrester)

## v0.2.0 / 2017-11-14
* Build automated wordbreak tests from official test data (Ed Sanders)

## v0.1.6 / 2016-12-09
* Update to Unicode 8 (Ed Sanders)
* Remove duplicated check for surrogates (Ed Sanders)

## v0.1.5 / 2015-07-02
* Duck typing test for isBreak (David Chan)
* Update generated data for Unicode 8.0.0 (David Chan)
* Strong directionality support (David Chan)

## v0.1.4 / 2015-03-18
* Add isBreak surrogate pair support for code unit strings (David Chan)

## v0.1.3 / 2015-02-04
* Bump copyright notices to 2015 (James D. Forrester)
* Word character class regex (David Chan)

## v0.1.2 / 2014-12-04
* Fix lots of spelling mistakes and typos (Ed Sanders)
* Update to Unicode 7.0.0 (Ed Sanders)

## v0.1.1 / 2014-08-12
* readme: Document release process (James D. Forrester)

## v0.1.0 / 2014-08-12
* build: Make into a built library with test infrastructure (James D. Forrester)
* Initial import of UnicodeJS namespace from VisualEditor (James D. Forrester)
