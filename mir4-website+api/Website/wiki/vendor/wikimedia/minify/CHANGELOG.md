# Changelog

## 2.7.0 (2023-11-14)

* JavaScriptMinifier: Add basic ES2017 async-await syntax support (Hannah Okwelum)
* JavaScriptMinifier: Update debug() to remove $lastType (Timo Tijhof)

## 2.5.1 (2023-10-06)

* Fix source map output for multi-line templates. (Tim Starling) [T348280](https://phabricator.wikimedia.org/T348280)

## 2.5.0 (2023-08-11)

Added:

* bin: Add `css-remap` command. (Timo Tijhof)
* IdentityMinifierState and JavaScriptMinifier::createIdentityMinifier(). (Tim Starling)
* MinifierState::ensureNewline()
* IndexMap class, for generating a combined source map

## 2.4.0 (2023-03-20)

Added:

* JavaScriptMinifier: Add ES2016 syntax support. (Siddharth VP) [T277675](https://phabricator.wikimedia.org/T277675)

Changed:

* package: Require `ext-fileinfo` in composer.json. (Sam Reed)

## 2.3.0 (2022-04-19)

Added:

* JavaScriptMinifier: Add support for source maps. (Tim Starling) [T47514](https://phabricator.wikimedia.org/T47514)
   Use via the `createMinifier()` and `createSourceMapState()` methods,
   see `/tests/data/sourcemap/combine.php` for an example.
* bin: Add `jsmap-web` and `jsmap-raw` commands. (Tim Starling)

Fixed:

* JavaScriptMinifier: Fix handling of property on dotless number literal. (Timo Tijhof) [T303827](https://phabricator.wikimedia.org/T303827)

## 2.2.6 (2021-11-18)

Fixed:

* JavaScriptMinifier: Correctly recognize `\` in template strings. (Roan Kattouw) [T296058](https://phabricator.wikimedia.org/T296058)

## 2.2.5 (2021-10-20)

Fixed:

* bin: Fix `minify` CLI autoload when run from a vendor directory. (Timo Tijhof)

## 2.2.4 (2021-07-28)

Fixed:

* JavaScriptMinifer: Recognize `...` as a single token. (Roan Kattouw) [T287526](https://phabricator.wikimedia.org/T287526)

## 2.2.3 (2021-06-07)

Fixed:

* JavaScriptMinifer: Fix handling of `.delete` as object property. (Roan Kattouw) [T283244](https://phabricator.wikimedia.org/T283244)

## 2.2.2 (2021-05-07)

Fixed:

* CSSMin: Fix remapping of path-only URL when base dir is server-less root. (Timo Tijhof) [T282280](https://phabricator.wikimedia.org/T282280)

## 2.2.1 (2021-03-15)

Fixed:

* JavaScriptMinifier: Fix handling of keywords used as object properties. (Roan Kattouw) [T277161](https://phabricator.wikimedia.org/T277161)

## 2.2.0 (2021-03-09)

Added:

* JavaScriptMinifier: Add ES6 syntax support. (Roan Kattouw) [T272882](https://phabricator.wikimedia.org/T272882)
* JavaScriptMinifier: Support true/false minification in more situations. (Roan Kattouw)
* bin: Add `minify` CLI. (Timo Tijhof)

Changed:

* JavaScriptMinifier: Improve latency through various optimisations. (Daimona Eaytoy)

Fixed:

* JavaScriptMinifier: Fix semicolon insertion logic for `throw new Error`. (Roan Kattouw)

## 2.1.0 (2021-02-12)

Added:

* CSSMin: Add global class alias for `CSSMin` for MediaWiki compatibility.
  This is deprecated on arrival and will be removed in a future major release.

## 2.0.0 (2021-02-08)

This release requires PHP 7.2+, and drops support for Internet Explorer 6-10.

Added:

* CSSMin: Support multiple `url()` values in one rule. (Bartosz Dziewoński)
* CSSMin: Support embedding of SVG files. (m4tx)

Removed:

* JavaScriptMinifier: Remove support for the `$statementsOnOwnLine` option.
* JavaScriptMinifier: Remove support for the `$maxLineLength` option.
* CSSMin: Remove data URI fallback, previously for IE 6 and IE 7 support.

Changed:

* CSSMin: Reduce SVG embed size by using URL-encoding instead of base64-encoding. (Bartosz Dziewoński)
* CSSMin: Improve SVG compression by preserving safe literals. (Roan Kattouw, Volker E, Fomafix)

Fixed:

* CSSMin: Fix non-embedded URLs that are proto-relative or have query part. (Bartosz Dziewoński) [T60338](https://phabricator.wikimedia.org/T60338)
* CSSMin: Avoid corruption when CSS comments contain curly braces. (Stephan Gambke) [T62077](https://phabricator.wikimedia.org/T62077)
* CSSMin: Avoid corrupting parenthesis and quotes in URLs. (Timo Tijhof) [T60473](https://phabricator.wikimedia.org/T60473)
* CSSMin: Skip remapping for special `url(#default#behaviorName)` values. (Julien Girault)
* JavaScriptMinifier: Fix "Uninitialized offset" in string and regexp parsing. (Timo Tijhof) [T75556](https://phabricator.wikimedia.org/T75556)
* JavaScriptMinifier: Fix "Uninitialized offset" in regexp char class parsing. (Timo Tijhof) [T75556](https://phabricator.wikimedia.org/T75556)
* JavaScriptMinifier: Fix possible broken `return` statement after a ternary in a property value. (Timo Tijhof) [T201606](https://phabricator.wikimedia.org/T201606)

## 1.0.0 (2011-11-23)

Initial release, originally bundled with MediaWiki 1.19.

