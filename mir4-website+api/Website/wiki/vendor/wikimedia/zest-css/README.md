# zest.php

__zest.php__ is a fast, lightweight, extensible CSS selector engine for PHP.

Zest was designed to be very concise while still supporting CSS3/CSS4
selectors and remaining fast.

This is a port to PHP of the [zest.js](https://github.com/chjj/zest)
selector library.  Since that project hasn't been updated in a while,
bugfixes have been taken from the copy of zest included in the
[domino](https://github.com/fgnass/domino/pulls) DOM library.

Report issues on [Phabricator](https://phabricator.wikimedia.org/maniphest/task/edit/form/1/?projects=Parsoid&title=Zest:%20).

## Usage

```php
use Wikimedia\Zest\Zest;

$els = Zest::find('section! > div[title="hello" i] > :local-link /href/ h1', $doc);
```

## Install

This package is [available on Packagist](https://packagist.org/packages/wikimedia/zest-css):

```bash
$ composer require wikimedia/zest-css
```

## API

Functions below which take an `opts` array can be passed additional options
which affect match results.  This are available from within custom selectors
(see below).  At the moment, the standard selectors support the following
options:
* `standardsMode` (`bool`): if present and true, various PHP workarounds
  will be disabled in favor of calling methods defined in web standards.
* `getElementsById` (`true|callable(DOMNode,string):array<DOMElement>`):
  if set to `true` then an optimization will be disabled to ensure that
  Zest can return multiple elements for ID selectors if IDs are not unique
  in the document.  If set to a `callable` that takes a context node and
  an ID string and returns an array of Elements, a third-party DOM
  implementation can support an efficient index allowing multiple
  elements to share the same ID.

All methods will throw the exception returned by
 `ZestInst::newBadSelectorException()` (by default, a new
 `InvalidArgumentException`) if the selector fails to parse.

#### `Zest::find( string $selector, $context, array $opts = [] ): array`</dt>
This is equivalent to the standard
DOM method [`ParentNode#querySelectorAll()`](https://developer.mozilla.org/en-US/docs/Web/API/ParentNode/querySelectorAll).

#### `Zest::matches( $element, string $selector, array $opts = [] ): bool`
This is equivalent to the standard
DOM method [`Element#matches()`](https://developer.mozilla.org/en-US/docs/Web/API/Element/matches).

Since the PHP implementations of
[`DOMDocument::getElementById`](http://php.net/manual/en/domdocument.getelementbyid.php)
and
[`DOMDocument#getElementsByTagName`](http://php.net/manual/en/domdocument.getelementsbytagname.php)
have some performance and spec-compliance issues, Zest also exports useful
performant and correct versions of these:

#### `Zest::getElementsById( $contextNode, string $id, array $opts = [] ): array`
This is equivalent to the standard DOM method
[`Document#getElementById()`](https://developer.mozilla.org/en-US/docs/Web/API/Document/getElementById)
(although you can use any context node, not just the top-level document).
In addition, with the proper support from the DOM implementation, this can
return more than one matching element.

#### `Zest::getElementsByTagName( $contextNode, string $tagName, array $opts = [] ): array`
This is equivalent to the standard DOM method [`Element#getElementsByTagName()`](https://developer.mozilla.org/en-US/docs/Web/API/Element/getElementsByTagName),
although you can use a `DocumentFragment` as the `$contextNode`.

## Extension

It is possible to add your own selectors, operators, or combinators.
These are added to an instance of `ZestInst`, so they don't affect other
instances of Zest or the static `Zest::find`/`Zest::matches` methods.
The `ZestInst` class has non-static versions of all the static methods
available on `Zest`.

### Adding a simple selector

Adding simple selectors is fairly straight forward. Only the addition of pseudo
classes and attribute operators is possible. (Adding your own "style" of
selector would require changes to the core logic.)

Here is an example of a custom `:name` selector which will match for an
element's `name` attribute: e.g. `h1:name(foo)`. Effectively an alias
for `h1[name=foo]`.

```php
use Wikimedia\Zest\ZestInst;

$z = new ZestInst;
$z->addSelector1( ':name', function( string $param ):callable {
  return function ( $el, array $opts ) use ( $param ):bool {
    if ($el->getAttribute('name') === $param) return true;
    return false;
  };
} );

// Use it!
$z->find( 'h1:name(foo)', $document );
```

If you wish to add selectors which depend on global properties (such as
`:target`) you can add the global information to `$opts` and it will be
made available when your selector function is called.

__NOTE__: if your pseudo-class does not take a parameter, use `addSelector0`.

### Adding an attribute operator

```php
$z = new ZestInst;
// `$attr` is the attribute
// `$val` is the value to match
$z->addOperator( '!=', function( string $attr, string $val ):bool {
  return $attr !== $val;
} );

// Use it!
$z->find( 'h1[name != "foo"]', $document );
```

### Adding a combinator

Adding a combinator is a bit trickier. It may seem confusing at first because
the logic is upside-down. Zest interprets selectors from right to left.

Here is an example how a parent combinator could be implemented:

```js
$z = new ZestInst;
$z->addCombinator( '<', function( callable $test ): callable {
  return function( $el, array $opts ) use ( $test ): ?DOMNode {
    // `$el` is the current element
    $el = $el->firstChild;
    while ($el) {
      // return the relevant element
      // if it passed the test
      if ($el->nodeType === 1 && call_user_func($test, $el, $opts)) {
        return $el;
      }
      $el = $el->nextSibling;
    }
    return null;
  };
} );

// Use it!
$z->find( 'h1 < section', $document );
```

The `$test` function tests whatever simple selectors it needs to look for, but
it isn't important what it does. The most important part is that you return
the relevant element once it's found.


## Tests

```bash
$ composer test
```

## License and Credits

The original zest codebase is
(c) Copyright 2011-2012, Christopher Jeffrey.

The port to PHP was initially done by C. Scott Ananian and is
(c) Copyright 2019 Wikimedia Foundation.

Additional code and functionality is
(c) Copyright 2020-2021 Wikimedia Foundation.

Both the original zest codebase and this port are distributed under
the MIT license; see LICENSE for more info.
