AST generation and tokenization
==========

AST generation
-------------
To generate AST (abstract syntax tree) for your JavaScript code just write:

```php
$source = "var a = 1"; //JavaScript code
$ast = Peast\Peast::latest($source, $options)->parse();
```

The previous code generates this structure:
```
Peast\Syntax\Node\Program
    getSourceType() => "script"
    getBody() => array(
        Peast\Syntax\Node\VariableDeclaration
            getKind() => "var"
            getDeclarations() => array(
                Peast\Syntax\Node\VariableDeclarator
                    getId() => Peast\Syntax\Node\Identifier
                        getName() => "a"
                    getInit() => Peast\Syntax\Node\NumericLiteral
                        getFormat() => "decimal"
                        getValue() => 1
            )
    )
```

Tokenization
-------------
To tokenize your JavaScript code just write:

```php
$source = "var a = 1"; //JavaScript code
$tokens = Peast\Peast::latest($source, $options)->tokenize();
```

This function produces an array of tokens from your code:
```
array(
    Peast\Syntax\Token
        getType() => "Keyword"
        getValue() => "var"
    Peast\Syntax\Token
        getType() => "Identifier"
        getValue() => "a"
    Peast\Syntax\Token
        getType() => "Punctuator"
        getValue() => "="
    Peast\Syntax\Token
        getType() => "Numeric"
        getValue() => "1"
)
```

EcmaScript version
-------------
Peast can parse different versions of EcmaScript, you can choose the version by using the relative method on the main class.
Available methods are:
* ```Peast::ES2015(source, options)``` or ```Peast::ES6(source, options)```: parse using EcmaScript 2015 (ES6) syntax
* ```Peast::ES2016(source, options)``` or ```Peast::ES7(source, options)```: parse using EcmaScript 2016 (ES7) syntax
* ```Peast::ES2017(source, options)``` or ```Peast::ES8(source, options)```: parse using EcmaScript 2017 (ES8) syntax
* ```Peast::ES2018(source, options)``` or ```Peast::ES9(source, options)```: parse using EcmaScript 2018 (ES9) syntax
* ```Peast::ES2019(source, options)``` or ```Peast::ES10(source, options)```: parse using EcmaScript 2019 (ES10) syntax
* ```Peast::ES2020(source, options)``` or ```Peast::ES11(source, options)```: parse using EcmaScript 2020 (ES11) syntax
* ```Peast::ES2021(source, options)``` or ```Peast::ES12(source, options)```: parse using EcmaScript 2021 (ES12) syntax
* ```Peast::ES2022(source, options)``` or ```Peast::ES13(source, options)```: parse using EcmaScript 2022 (ES13) syntax
* ```Peast::ES2023(source, options)``` or ```Peast::ES14(source, options)```: parse using EcmaScript 2023 (ES14) syntax
* ```Peast::ES2024(source, options)``` or ```Peast::ES15(source, options)```: parse using EcmaScript 2024 (ES15) syntax
* ```Peast::latest(source, options)```: parse using the latest EcmaScript syntax version implemented

Options
-------------

In the examples above you may have noticed the `$options` parameter. This parameter is an associative array that specifies parsing settings for the parser. Available options are:
* "sourceType": this can be one of the source type constants defined in the Peast class:
    * `Peast\Peast::SOURCE_TYPE_SCRIPT`: this is the default source type and indicates that the code is a script, this means that `import` and `export` keywords are not parsed
    * `Peast\Peast::SOURCE_TYPE_MODULE`: this indicates that the code is a module and it activates the parsing of `import` and `export` keywords
* "comments" (from version 1.5): enables comments parsing and attaches the comments to the nodes in the tree. You can get comments attached to nodes using `getLeadingComments` and `getTrailingComments` methods.
* "jsx" (from version 1.8): enables parsing of JSX syntax.
* "sourceEncoding": to specify the encoding of the code to parse, if not specified the parser will assume UTF-8.
* "strictEncoding": if false the parser will handle invalid UTF8 characters in the source code by replacing them with the character defined in the "mbstring.substitute_character" ini setting, otherwise it will throw an exception. (available from version 1.9.4)

Differences from ESTree
-------------

There is only one big difference from ESTree: parenthesized expressions. This type of expressions have been introduced to let the user know if when an expression is wrapped in round brackets. For example `(a + b)` is a parenthesized expression and generates a ParenthesizedExpression node.

From version 1.3, literals have their own classes: `StringLiteral`, `NumericLiteral`, `BooleanLiteral` and `NullLiteral`.

From version 1.8, when parsing JSX, 2 new token types are emitted: `JSXIdentifier`, that represents a valid JSX identifier, and `JSXText`, that represents text inside JSX elements and fragments.

From version 1.13.7, the new `rawName` property has been added to `Identifiers` nodes. This property reports the raw name of the identifier with unconverted unicode escape sequences.
