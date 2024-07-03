Changelog
==========

#### 1.16.2
* Fixed bug where a regex that started with `/=` raised a syntax error

#### 1.16.1
* Fixed bug where, in some situations, scanner allowed invalid characters after a slash

#### 1.16.0
* Implemented ES2024 parser, no new syntax features have been introduced
* Fixed bug when parsing sequence expressions using older ES versions

#### 1.15.4
* Fixed rendering of `let` and `const` inside `switch` cases that always require brackets

#### 1.15.3
* Fixed rendering of `let` and `const` inside `if`, `for` and `while` that always require brackets

#### 1.15.2
* Fixed bug where async keyword was lost when rendering an object async method

#### 1.15.1
* Fixed bug on hashbang comment detection

#### 1.15.0
* Implemented ES2022 parser with hashbang comments

#### 1.14.0
* Implemented comments rendering

#### 1.13.11
* Fixed a case of conditional expression parsed as a wrong optional chain

#### 1.13.10
* Added support for php 8.1
* Fixed parsing of multiline template literals in return statements

#### 1.13.9
* Implemented parsing of arbitrary module namespace identifier names

#### 1.13.8
* Fixed identifiers bug introduced in the last release

#### 1.13.7
* Implemented parsing of top level await
* Implemented parsing of `#field in obj` syntax
* Implemented parsing of class static block
* Aligned invalid octal numbers checks to the specification
* __BC break__: identifier tokens' value now report their raw name, this means that unicode escape sequences are reported as they are written in the code, without converting them to their corresponding characters. Identifier nodes have a new property called `rawName` that contains their raw name, including unconverted unicode escape sequences, while the `name` property still contains the converted value as before. Renderer now prints `rawName` for identifiers to prevent automatic conversion of escaped keywords.

#### 1.13.6
* Fixed parsing of adjacent JSX expressions
* Implemented parsing of JSX fragments inside elements

#### 1.13.5
* Fixed parsing of `get` and `set` as property names and class fields
* Fixed parsing of dot after number with exponential notation

#### 1.13.4
* Fixed bug when parsing surrogate pairs in php 7.4+

#### 1.13.3
* Added support for surrogate pairs in strings and templates

#### 1.13.2
* Fixed bug when parsing spread operator inside objects returned by arrow functions

#### 1.13.1
* Major performance improvements to parsing and tokenization

#### 1.13.0
* Implemented ES2022 parser with class fields and private class methods

#### 1.12.0
* Added options array to Traverser constructor and shortcut method on nodes
* Added Query class

#### 1.11.0
* Implemented ES2021 parser with logical assignment operators and numeric separators

#### 1.10.4
* Implemented parsing of coalescing operator
* Implemented parsing of optional chaining
* Fixed bug when parsing a semicolon on a new line after break and continue statements

#### 1.10.3
* Implemented parsing of `import.meta` syntax
* Implemented parsing of BigIntLiteral as objects keys

#### 1.10.2
* Implemented parsing of `export * as ns from "source"` syntax
* Fixed Renderer so that it won't trust computed flag in MemberExpression if property is not an Identifier

#### 1.10.1
* Fixed parsing of semicolon after do-while statement

#### 1.10.0
* Implemented ES2020 parser with dynamic import and BigInt
* Implemented handling of UTF-8 and UTF-16 BOM when parsing the source
* Fixed wrong rendering of unary and update expressions inside binary expressions in compact mode
* __BC break__: major refactoring to delete all parsers except the base one and replace them with new Features classes that specify enabled parser features. This will remove duplicated code and makes the parser easier to extend with new features.

#### 1.9.4
* Handled invalid UTF-8 characters in the source code by throwing an exception or replacing them with a substitution character by setting the new strictEncoding option to false
* Fixed bug when rendering object properties with equal key and value

#### 1.9.3
* Fixed another bug when rendering nested "if" statements with Compact formatter

#### 1.9.2
* Fixed rendering of nested "if" statements with Compact formatter

#### 1.9.1
* Fixed rendering of arrow functions that generates invalid code

#### 1.9
* Added ES2019 parser

#### 1.8.1
* Fixed parsing of regular expressions by disabling scan errors inside them
* Added LSM utility class to handle correctly punctuators and strings stop characters

#### 1.8
* Implemented parsing of JSX syntax

#### 1.7
* Implemented missing features of es2018: object rest and spread, async generators and async iteration

#### 1.6
* Fixed a lot of bugs and now Peast is compatible with all the [ECMAScript official tests](https://github.com/tc39/test262) for the implemented features. You can test Peast against ECMAScript tests using the [peast-test262](https://github.com/mck89/peast-test262) repository.
* Added ES2018 parser

#### 1.5
* Enabled JSON serialization of nodes and tokens using json_encode()
* Added parsing and handling of comments

#### 1.4
* Since EcmaScript dropped support for ES(Number) in favour of ES(Year) versions:
    * `ES6` namespace have been replaced by `ES2015`
    * `Peast::ES2015` method have been added to Peast main class, `Peast::ES6` method still exists to preserve BC and calls `Peast::ES2015` internally
    * `ES7` namespace have been replaced by `ES2016`
    * `Peast::ES2016` method have been added to Peast main class, `Peast::ES7` method still exists to preserve BC and calls `Peast::ES2016` internally
    * `Peast::latest` method have been added to Peast main class to allow parsing with the latest EcmaScript version implemented
* Added ES2017 parser

#### 1.3
* Refactored parser to make it more extensible
* More accurate parsing of identifiers
* Added parsing of HTML comments if source is not a module
* Added some validations:
    * Disallowed legacy octal escape syntax (\07) in templates
    * Disallowed legacy octal escape syntax (\07) in strings if strict mode
    * Disallowed legacy octal syntax (077) for numbers if strict mode
    * Disallowed `delete` followed by single identifiers in strict mode
    * Disallowed labelled function declarations in strict mode
    * Allowed `if (...) function () {}` syntax if not in strict mode
* __BC break__: removed Function_ and Class_ interfaces and traits and replaced them with abstract classes
* __BC break__: if sourceEncoding is not specified, the parser won't try to autodetect it, but will assume UTF-8
* __BC break__: Literal is now an abstract class that is extended by the new classes for literals: StringLiteral, NumericLiteral, BooleanLiteral and NullLiteral

#### 1.2
* Added Renderer class

#### 1.1
* Added Traverser class

#### 1.0
* First release with ES6 and ES7 parsers
