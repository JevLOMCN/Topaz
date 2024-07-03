Peast
==========

[![Latest Stable Version](https://poser.pugx.org/mck89/peast/v/stable)](https://packagist.org/packages/mck89/peast)
[![Total Downloads](https://poser.pugx.org/mck89/peast/downloads)](https://packagist.org/packages/mck89/peast)
[![License](https://poser.pugx.org/mck89/peast/license)](https://packagist.org/packages/mck89/peast)
[![Build Status](https://github.com/mck89/peast/actions/workflows/test.yml/badge.svg)](https://github.com/mck89/peast/actions/workflows/test.yml)


**Peast** _(PHP ECMAScript Abstract Syntax Tree)_ is a PHP 5.4+ library that parses JavaScript code, according to [ECMAScript specification](http://www.ecma-international.org/publications/standards/Ecma-262.htm), and generates an abstract syntax tree following the [ESTree standard](https://github.com/estree/estree).

Installation
-------------
Include the following requirement to your composer.json:
```json
{
	"require": {
		"mck89/peast": "dev-master"
	}
}
```

Run `composer install` to install the package.

Then in your script include the autoloader and you can start using Peast:

```php
require_once "vendor/autoload.php";

$source = "var a = 1"; // Your JavaScript code
$ast = Peast\Peast::latest($source, $options)->parse(); // Parse it!
```

Known issues
-------------
When Xdebug is enabled and Peast is used to scan code that contains deeply nested functions, this fatal error can appear:
```
PHP Fatal error:  Maximum function nesting level of '512' reached, aborting!
```
or
```
PHP Warning:  Uncaught Error: Xdebug has detected a possible infinite loop, and aborted your script with a stack depth of '256' frames
```
To prevent this you can set `xdebug.max_nesting_level` to a higher value, such as 512.

Documentation
-------------
Read the documentation for more examples and explanations:

 1. [AST generation and tokenization](doc/ast-and-tokenization.md)
 2. [Tree Traversing](doc/tree-traversing.md)
 3. [Querying By Selector](doc/querying-by-selector.md)
 4. [Rendering](doc/rendering.md)

[Changelog](doc/changelog.md)
