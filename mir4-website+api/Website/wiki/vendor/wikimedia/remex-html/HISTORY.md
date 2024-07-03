# Release History

## RemexHtml 4.1.0 (2024-03-16)
* In DOMBuilder, make ::insertNode() protected to allow clients to
  support the special HTML5 insertion logic for <template> nodes.
* Workaround PHP bug which limits the set of characters allowed for
  tag and attribute names even more than the XML spec does.
* Refine exception types thrown in PropGuard and TreeBuilder.
* build: Updating mediawiki/mediawiki-codesniffer to 43.0.0.
* build: Update PHPUnit to 9.6.16.

## RemexHtml 4.0.1 (2023-08-29)
* build: Drop claimed support for utfnormal 1.x and 2.x, we require PHP 7.4 (James D. Forrester)
* Add support for wikimedia/utfnormal 4.0.0 (Derick Alangi)
* build: Switch phan to special library mode (James D. Forrester)
* build: Cleanup of PHPUnit 8.x compatibility (Umherirrender)
* build: Updating mediawiki/mediawiki-codesniffer to 41.0.0 (libraryupgrader)
* Bump version after release (C. Scott Ananian)

## RemexHtml 4.0.0 (2023-02-24)
* Drop PHP 7.2 and PHP 7.3 support.
* Update PHPUnit dependency.

## RemexHtml 3.0.3 (2022-12-21)
* Workaround PHP bug which decodes entities when setting attribute values.
  (T324408, https://github.com/php/php-src/pull/10132 )

## RemexHtml 3.0.2 (2022-06-27)
* Specify return types to make PHP 8.1 happy.

## RemexHtml 3.0.1 (2021-11-19)
* Fix duplicate sourceLength output for <tr></table>.
* In DOMBuilder, catch invalid character errors from createAttribute.

## RemexHtml 3.0.0 (2021-10-25)
* Removed the RemexHtml\ namespace aliases.
* Added Attributes::clone()
* Added Dispatcher::flushTableText().

## RemexHtml 2.3.2 (2021-08-07)
* Changed package namespace from RemexHtml to Wikimedia\RemexHtml to match
  package name.  PHP's `class_alias` has been used so that existing code
  using the old namespace will continue to work, but this is now deprecated;
  it is expected the next major release of RemexHtml will remove the aliases.
* Fix handling of <body> tag in "after head" state that would incorrectly
  result in a parse error being raised.
* Made DOMBuilder::createNode protected (rather than private) so that
  standards-compliant DOM implementations can override it.

## RemexHtml 2.3.1 (2021-04-20)
* Don't pass null arguments to DOMImplementation::createDocument(): nulls
  are technically allowed and converted to the empty string, but this is
  deprecated legacy behavior.

## RemexHtml 2.3.0 (2021-02-05)
* Allow use of third-party DOM implementations (like wikimedia/dodo)
  via the new `domImplementation` parameter to DOMBuilder.

## RemexHtml 2.2.2 (2021-01-30)
* Support wikimedia/utfnormal ^3.0.1

## RemexHtml 2.2.1 (2021-01-11)
* Various minor changes for PHP 8.0 support.
* Remove dead code about old phpunit version

## RemexHtml 2.2.0 (2020-04-29)
* Update dependencies.
* Fix warnings emitted by PHP 7.4.
* Bug fix in TreeBuilder\ForeignAttributes::offsetGet().
* Drop PHP 7.0/7.1 and HHVM support; require PHPUnit 8.

## RemexHtml 2.1.0 (2019-09-16)
* Call the non-standard \DOMElement::setIdAttribute() method by default.
* Add scriptingFlag option to Tokenizer, and make it true by default.
* Attributes bug fixes.
* Added RelayTreeHandler and RelayTokenHandler for subclassing convenience.
* Normalize text nodes during tree building, to match HTML parsing spec.

## RemexHtml 2.0.3 (2019-05-10)
* Don't decode char refs if ignoreCharRefs is set, even if they are simple.
  (This fixes a regression introduced in 2.0.2.)
* Performance improvements to character entity decoding and tokenizer
  preprocessing.

## RemexHtml 2.0.2 (2019-03-13)
* Performance improvements to tokenization and tree building.
* Provide an option to suppress namespace for HTML elements, working around
  a performance bug in PHP's dom_reconcile_ns (T217708).

## RemexHtml 2.0.1 (2018-10-15)
* Don't double-decode HTML entities when running on PHP (not HHVM) (T207088).

## RemexHtml 2.0.0 (2018-08-13)
* Drop support for PHP < 7.0.
* Remove descendant nodes when we get an endTag() event (T200827).
* Improved tracing.
* Added NullTreeHandler and NullTokenHandler.

## RemexHtml 1.0.3 (2018-02-28)
* Drop support for PHP < 5.5.

## RemexHtml 1.0.2 (2018-01-01)
* Fix linked list manipulation in CachedScopeStack (T183379).

## RemexHtml 1.0.1 (2017-03-14)
* Fix missing breaks in switch statements.

## RemexHtml 1.0.0 (2017-02-24)
* Initial release.
