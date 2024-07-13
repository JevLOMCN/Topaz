[![Latest Stable Version]](https://packagist.org/packages/wikimedia/idle-dom) [![License]](https://packagist.org/packages/wikimedia/idle-dom)

[![Run on Repl.it](https://repl.it/badge/github/wikimedia/mediawiki-libs-idledom)](https://repl.it/github/wikimedia/mediawiki-libs-idledom)

IDLeDOM
=====================

IDLeDOM is a set of PHP interfaces for the [WHATWG DOM spec](https://dom.spec.whatwg.org/)
automatically generated from the [WebIDL](https://heycam.github.io/webidl/) sources in the spec,
using a [PHP binding for WebIDL](./WebIDL.md).

The PHP binding is described in [WebIDL.md](./WebIDL.md).  It is
intended to be largely compatible with the ad hoc binding used for the
PHP built-in [DOM
extension](https://www.php.net/manual/en/book.dom.php).  Explicit
getter and setter functions are used for attributes, but PHP [magic
methods](https://www.php.net/manual/en/language.oop5.magic.php) are
used to allow property-style access.  The best performance will be
obtained by using the explicit getters and setters, however.

IDLeDOM is *not* a DOM implementation, it is only a set of interfaces.
An actual DOM implementation, like
[`mediawiki/dodo`](https://packagist.org/packages/wikimedia/dodo) will
implement the interfaces defined by IDLeDOM.  Client code can be
written to work with any DOM implementation which follows the IDLeDOM
PHP binding for WebIDL.

IDLeDOM does contain stubs and helper traits which can greatly aid a
DOM implementation.  Including stub traits in your DOM implementation
will ensure that new methods added to IDLeDOM will be stubbed out by
default (throwing a user-defined exception), instead of breaking your
implementation due to new unimplemented interface methods.  Including
the helper traits in your DOM implementation will provide
implementations of certain PHP magic methods and IDL "attribute
reflection" attributes.

Additional documentation about the library can be found on
[MediaWiki.org](https://www.mediawiki.org/wiki/IDLeDOM).

Install
-------

This package is [available on Packagist](https://packagist.org/packages/wikimedia/idle-dom):

```bash
$ composer require wikimedia/idle-dom
```

Usage
-----

Use the interface types in `Wikimedia\IDLeDOM\` when you wish to be
compatible with any PHP DOM implementation.  For example, assuming
your DOM implementation gives you an object implementing
`Wikimedia\IDLeDOM\DOMImplementation`, called `$domImpl`:

```php
$doc = $domImpl->createHTMLDocument( 'My HTML Document );
$doc->getBody()->setInnerHTML( '<p>Whee!!</p> );
// This is equivalent, but slightly slower:
$doc->body->innerHTML = '<p>Whee!!</p>';

// Use the interface type in type hints for max compatibility
$f = function( \Wikimedia\IDLeDOM\Document $doc ): string {
	return $doc->getTitle(); // or $doc->title
};
```

Writing a new DOM implementation
--------------------------------

To write a new DOM implementation you will be implementing the
interface types in `Wikimedia\IDLeDOM`.  This library provides
two traits for most interfaces in order to ease the task:

1. The helper trait in `Wikimedia\IDLeDOM\Helper` will implement the
magic `__get` and `__set` methods for interfaces and dictionaries,
`ArrayAccess` methods for dictionaries, the magic `__invoke` method
for callback classes, and `cast` methods for callbacks and
dictionaries to turn a `callable` and associative-array, respectively,
into the proper callback or dictionary type.  The helper will
implement `Countable` and `IteratorAggregate` where appropriate.  For
IDL interfaces which reflect Element attributes, the helper class will
also implement these reflected interface attributes in terms of
`Element::getAttribute()`, `Element::hasAttribute()`, and
`Element::setAttribute()` calls.

2. The stub trait in `Wikimedia\IDLeDOM\Stub` will stub out all
methods of the interface by implementing them to throw the
exception returned by `::_unimplemented()` (which can be your
own subclass of `DOMException` or whatever you like).  This
helps bootstrap a DOM implementation and ensures that new
methods can be added to the DOM spec, and by extension to the
`IDLeDOM` interfaces, without breaking code which implements
these interfaces.

Putting these together, the first few lines of a typical
DOM implementation will look something like this:

```php
// Your implementations of DOM mixins are traits, like this:
trait NonElementParentNode /* implements \Wikimedia\IDLeDOM\NonElementParentNode */ {
	use \Wikimedia\IDLeDOM\Stub\NonElementParentNode;

	// Your code here...
}

// DOM interfaces are classes:
class Document extends Node implements \Wikimedia\IDLeDOM\Document {
	// DOM mixins: these are your code, but they include
	// the appropriate IDLeDOM stubs
	use DocumentOrShadowRoot;
	use NonElementParentNode;
	use ParentNode;
	use XPathEvaluatorBase;

	// Stub out methods not yet implemented.
	use \Wikimedia\IDLeDOM\Stub\Document;

	// Helper functions from IDLeDOM
	// (Note that the Document helper will also include reflected
	// attribute accessors for the mixin classes; you don't need
	// to include helper traits in your implementations of DOM mixins)
	use \Wikimedia\IDLeDOM\Helper\Document;

	protected function _unimplemented() : \Exception {
		return new UnimplementedException(); // your own exception type
	}

	// Your code here...
}
```

Hacking on IDLeDOM
------------------

To regenerate the interfaces in `src/` from the WebIDL sources in `spec/`:

    composer build

To run tests:

    composer test

## License and Credits

The initial version of this code was written by C. Scott Ananian and is
Copyright (c) 2021 Wikimedia Foundation.

This code is distributed under the MIT license; see
[LICENSE](./LICENSE) for more info.

---
[Latest Stable Version]: https://poser.pugx.org/wikimedia/idle-dom/v/stable.svg
[License]: https://poser.pugx.org/wikimedia/idle-dom/license.svg

