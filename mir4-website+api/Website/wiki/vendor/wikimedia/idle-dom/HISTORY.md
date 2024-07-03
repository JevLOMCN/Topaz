# Release History

## IDLeDOM 1.0.0 (2022-12-07)
* The WebIDL PHP binding has been altered to allow implementations to measure
  offsets and lengths in unicode code points for the attributes of
  `CharacterData`, instead of using UTF-16 code units.
* Support for PHP 7.2 and PHP 7.3 has been dropped.
* Various dependencies have been updated for PHP 8 compatibility.
* Properly encode `unsigned long` constants from IDL as PHP's (signed)
  `int` type for compatibility with 32-bit PHP systems (T324489).

## IDLeDOM 0.10.0 (2021-08-07)
* The second and third arguments to DOMImplementation::createDocumentType()
  are now optional, for PHP compatibility.
* If the final argument to Document::createElement() or
  Document::createElementNS() is a string, interpretation may be
  implementation-dependent (PHP compatibility).

## IDLeDOM 0.9.0 (2021-08-02)
* The second argument to Node::insertBefore() is now optional, for
  PHP compatibility.
* Add PHP-compatible properties on Document:
  * ::preserveWhiteSpace
  * ::formatOutput
  * ::validateOnParse
  * ::strictErrorChecking

## IDLeDOM 0.8.0 (2021-07-30)
* Add DocumentFragment::appendXML() for PHP compatibility.
* Fix name of PHP-compatible Document::loadHTML() method.
* Clarify the return types of Document::loadHTML()/Document::loadXML().

## IDLeDOM 0.7.2 (2021-07-26)
* Don't export .phan directory in composer package.

## IDLeDOM 0.7.1 (2021-07-16)
* Add `_getMissingProp`/`_setMissingProp` extension points for
  DOM implementations to support additional/alternative properties.

## IDLeDOM 0.7.0 (2021-07-04)
* Add CSS Object Model (CSSOM) interfaces.
* Add Location interface.
* Support [PutForwards] extended attribute.

## IDLeDOM 0.6.0 (2021-06-14)
* Map WebIDL enumeration types to PHP string type (previously enumerations
  were mapped to an integer type).
* Map WebIDL dictionary types to an abstract class instead of an interface
  type.  This allows IDLeDOM to provide a static `cast` method for the
  dictionary, which is all that most implementations need.
* Add interfaces for GlobalEventHandlers,
  DocumentAndElementEventHandlers, and WindowEventHandlers, although at
  this time only the `onload` event handler is defined in order to
  avoid bloating the `__get`/`__set` helpers.

## IDLeDOM 0.5.1 (2021-05-20)
* Suppress type hints on DOMException::getMessage() and
  DOMException::getCode() so that you can override a built-in
  \Exception class.
* Support pair iterators.
* Add URL and URLSearchParams interfaces.

## IDLeDOM 0.5.0 (2021-05-19)
* Add HTML extensions to Document interface.
* Add extensions from the DOM Parsing and Serialization specification.
* Add Document::{load,loadXML,saveHTML,saveXML} which are commonly-used
  PHP-specific methods for compatibility with PHP code written for
  `DOMDocument`.

## IDLeDOM 0.4.1 (2021-05-12)
* Add DOMException interface.
* Add interfaces for WebIDL "simple exceptions".
* Annotate `id`, `className`, and `slot` attributes of Element for reflection.
* Add `Element::setIdAttribute`, `Element::setIdAttributeNode`, and
  `Element::setIdAttributeNS` for compatibility with PHP code written for
  `DOMElement`.

## IDLeDOM 0.4.0 (2021-05-11)
* Add interfaces for HTML IDL, in particular the HTML*Element classes.
* Support "unnamed" getter/setters/deleters/stringifiers.
* Return return types for cast() helper methods, in order to accommodate
  the weak covariant return type checks in PHP 7.2.
* Generate helper functions for attributes with the [Reflect] extended
  attribute (which reflect an HTML element attribute).
* Add Document::encoding setter, for compatibility with PHP's
  DOMDocument.  This is marked as [PHPExtension].

## IDLeDOM 0.3.0 (2021-04-12)
* Use interface (instead of class) for enumerations.  This allows
  DOM implementations to easily define a class implementing the interface
  to bring the constants into the implementation's namespace.
* The helper traits now define __isset() and __unset() for appropriate
  interfaces and dictionaries.
* Support the [LegacyNullToEmptyString] extended attribute, by broadening
  type to allow `null` where appropriate.

## IDLeDOM 0.2.0 (2021-02-12)
* Strip explicit PHP type hints for DOM interface types, in order to
  accomodate the weak covariant/contravariance checks in PHP 7.2.
* Make NodeList, HTMLCollection, NamedNodeMap, and DOMTokenList
  implement \Countable so they can be used with the PHP `count()` function.
* Add an `iterable` to HTMLCollection and NamedNodeMap so they can be
  used in a PHP `foreach` statement, like the built-in \DOMNamedNodeMap.
* Stubs are now standalone (don't stub out methods from parent interfaces),
  and don't declare abstract methods (these were shadowing inherited method
  from superclasses).

## IDLeDOM 0.1.1 (2021-02-11)
* Replace HTMLSlotElement/EventHandler/DOMHighRestTimeStamp stubs with
  proper definitions.
* Replace stub method to ::_unimplemented() to avoid DOM name conflicts.

## IDLeDOM 0.1.0 (2021-02-10)
Initial release.
