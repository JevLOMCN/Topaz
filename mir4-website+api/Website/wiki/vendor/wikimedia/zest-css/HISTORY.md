# Release History

## zest-css 3.0.1 (2024-03-11)
* Bug fix: recursive CSS selectors like `:has` and `:is` can now use
  custom selectors.
* Bug fix: selector for empty attributes now works correctly.
* Ensure ::getElementsByTagName() and ::getElementsByClassName() work
  correctly on tag or class names that contain 'special characters'
  that need to be escaped in a CSS query (T357812).
* Improvements to test cases.
* Dependency updates (mediawiki/mediawiki-phan-config, phpunit,
  mediawiki/mediawiki-codesniffer)

## zest-css 3.0.0 (2023-02-27)
* Drop PHP 7.2 and PHP 7.3 support.
* PHP 8 compatibility fixes.
* Dependency updates.

## zest-css 2.0.2 (2021-10-14)
* Update wikimedia/remex-html to 2.3.2 (dev dependency)
* Add a compilation cache to speed up repeated matches.

## zest-css 2.0.1 (2021-08-07)
* Bug fix: in some cases the ` ` and `>` combinators, as well as the
  `:dir` and `:lang` selectors, could attempt to match against a
  DOMDocument or DOMDocumentFragment instead of a DOMElement.

## zest-css 2.0.0 (2021-07-22)
* Dependency updates
* API changes:
  * The context argument ("scoping root") can now be a DOMDocumentFragment
    as well as the previously-allowed DOMDocument or DOMElement.
  * Add optional "options" argument to main entry points to allow passing
    information to custom selectors, as well as to opt in to disable certain
    workaround and enable additional features.  Current "options" keys include:
    * `standardsMode`: set to `true` for a spec-compliant DOM implementation
    * `getElementsById`: pass a `callable(DOMNode,string):array` if your
     DOM implementation can index multiple elements with the same id, or
     `true` to force a slow full-tree search to guarantee that id selectors
     can return multiple results.
  * Return types which were DOMNodeList have been changed to generic array
    types, to accomodate workarounds needed by the PHP dom library.
  * The `ZestInst::getElementsById()`, `::getElementsByTagName()`, and
    `::getElementsByClassName()` methods are now virtual (not static) to
    allow clients to subclass and override them if more efficient
    implementations are available.
* Clients can now subclass ZestInst and override
  `ZestInst::newBadSelectorException()` in order to customize the exception
  that is thrown when a selector parse error occurs.
* Clients can now subclass ZestInst and override
  `ZestInst::isStandardsMode()` in order to force Zest into standards mode
  (or not).
* Sort results in document order in standards mode.
* Support `:scope` selector
* Bug fixes to ~= operator, which now accepts non-space whitespace as a
  separator and is stricter about match targets containing whitespace
* Rudimentary namespace selector support: `*|TAG` and `|TAG`.

## zest-css 1.1.5 (2021-03-23)
* Dependency updates
* Strip strict type checks from API so this library can be used with
  3rd-party DOM implementations

## zest-css 1.1.4 (2021-01-29)
* Dependency updates
* Make tests pass on PHP 8

## zest-css 1.1.3 (2020-04-20)
* Fix case-insensitive attribute value matching
* Various dependency updates

## zest-css 1.1.2 (2019-04-09)
* Bug fix in ::first-child selector

## zest-css 1.1.1 (2019-03-19)
* Improve documentation and update copyright information

## zest-css 1.1.0 (2019-03-15)
* Expose getElementsById / getElementsByTagName
* Allow passing options to Remex in test helpers

## zest-css 1.0.0 (2019-03-13)
* Initial release.
