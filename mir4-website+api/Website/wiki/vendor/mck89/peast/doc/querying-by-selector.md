Querying By Selector
==========
**_From version 1.12_**

Inspired by [esquery](https://github.com/estools/esquery) project, Peast allows you to query the generated AST using a syntax similar to CSS selectors.
This can be done using the **Query** class:
```php
//Generate the AST
$ast = Peast\Peast::latest($source, $options)->parse();
//Query the AST
$query = new Peast\Query($ast, $options);
$query->find("Literal[value='search me']");
```

Or you can use the shortcut method on the `Program` node (the one returned by Peast parser):
```php
//Generate the AST
$ast = Peast\Peast::latest($source, $options)->parse();
//Query the AST
$query = $ast->query("Literal[value='search me']");
```

The shortcut method returns a Query instance.

Options
-------------
The Query class constructor takes an optional associative array of options.
Available options are:
* "encoding": to specify the encoding of the selector, if not specified the parser will assume UTF-8.

Methods
-------------
### find
The `find` method searches node that match the given selector starting from the current matching nodes.
For example this code matches `FunctionDeclaration` nodes and then finds all the descendant `Literal` nodes:
```php
$ast->query("FunctionDeclaration")->find("Literal");
```

### filter
The `filter` method filters the current matching nodes and keeps only those that respect the given selector.
For example this code matches `Literal` nodes and then keeps only the nodes whose value is 2:
```php
$ast->query("FunctionDeclaration")->filter("[value=2]");
```

### count
The `count` method returns the number of current matching nodes.
You can also use the builtin `count` function:
```php
$ast->query("FunctionDeclaration")->count();
//Or
count($ast->query("FunctionDeclaration"));
```

### get
The `get` method returns the node at the given index:
```php
//Returns the first matching node
$ast->query("FunctionDeclaration")->get(0);
```

Iteration
-------------
You can use the Query object in a foreach to loop all the matching nodes:
```php
foreach ($ast->query("FunctionDeclaration") as $node) {
    //...
}
```

Selectors syntax
-------------
Note that Peast tries to preserve the order of the nodes in the AST, but that is not always possible, so you shouldn't rely on that.

### Filter by type
You can filter nodes by their type simply writing it.
For example `Literal` matches all the nodes whose type is Literal.

### Filter by attribute
You can filter nodes by their attributes writing the name and optionally the value inside square brackets.
There are several types of attribute filters:
* `[value]` matches all the nodes that have a `value` attribute, without checking its value
* `[value="test"]` matches all the nodes whose `value` attribute equals to the string "test"
* `[value^="test"]` matches all the nodes whose `value` attribute starts with the string "test"
* `[value*="test"]` matches all the nodes whose `value` attribute contains the string "test"
* `[value$="test"]` matches all the nodes whose `value` attribute ends with the string "test"
* `[value>2]` matches all the nodes whose `value` attribute is greater than 2
* `[value>=2]` matches all the nodes whose `value` attribute is greater or equals to 2
* `[value<2]` matches all the nodes whose `value` attribute is lower than 2
* `[value<=2]` matches all the nodes whose `value` attribute is lower or equals to 2

In attributes filters the type is very important because a selector like `[value="2"]` will match a node whose `value` attribute is the string "2" but not 2 as number.
Available types are:
* Strings: `[value="a"]` or `[value='a']`
* Integer numbers: `[value=123]` or `[value=0xFFF]` or `[value=0b11011]` or `[value=0o77]`
* Decimal numbers: `[value=1.23]`
* Booleans: `[value=true]` or `[value=false]`
* Null: `[value=null]`

Strings can be escaped using the backslash character, for example `[value='That\'s great']` will find a value that equals to the string "That's great".

You can perform case-insensitive comparison using this syntax `[value='search' i]`.

You can also search using a regexp in this way: `[value=/test\d+/i]`.

Sometimes it's useful to check also inner attributes, you can do it by separating attributes name with a dot.
For example `FunctionDeclaration[id.name='funcName']` matches all `FunctionDeclaration` whose `id` attribute has a `name` attribute with the value "funcName".

### Filter by pseudo selector
Pseudo selectors begin with `:` and can optionally accept arguments wrapped in parentheses.
There are 3 groups of pseudo selectors:

###### Simple pseudo selector
These selectors don't accept any argument.
* `:first-child` matches nodes that are the first child of their parent
* `:last-child` matches nodes that are the first child of their parent
* `:pattern` matches nodes that implement the `Pattern` interface
* `:statement` matches nodes that implement the `Statement` interface
* `:expression` matches nodes that implement the `Expression` interface
* `:declaration` matches nodes that implement the `Declaration` interface

###### Positional pseudo selector
These selectors accept a number or a An+B syntax, where A represents the step and B is the starting offset.
Remember that the index is 1-based, so the first node is 1.
You can read more about the arguments accepted by these select on [MDN](https://developer.mozilla.org/en-US/docs/Web/CSS/:nth-child).
* `:nth-child` matches nodes that respects the given index in their parent children list. For example: `:nth-child(5n+3)` matches every 5th node starting from the 3rd one.
* `:nth-last-child` matches nodes that respects the given index in their parent children list, starting from the end. For example: `:nth-last-child(1)` matches a node that is the last child of its parent.
You can also use `even` and `odd` as arguments to match even and odds nodes.

###### Inner selector pseudo selector
These selectors accept a inner selector.
* `:is` matches a node that respect the given selector. For example `Literal:is([value=2], [value=3])` matches `Literal` nodes whose `value` is 2 or 3
* `:not` matches a node that do not respect the given selector. For example `Literal:not([value=2], [value=3])` matches `Literal` nodes whose `value` is not 2 or 3
* `:has` matches a node whose descendant match the given selector. For example `AssignmentExpression:has(Literal[value="string""])` matches `AssignmentExpression` nodes that contain `Literal` nodes whose `value` is "string"

### Combinators
Combinators are used for match other nodes relative to the current.
* Descendant: the space can be used to match descendant nodes. For example `AssignmentExpression Literal` matches `Literal` nodes inside `AssignmentExpression` nodes, even if they are not direct children
* Children: the `>` character can be used to match child nodes. For example `ArrayExpression > Literal` matches a `Literal` nodes that are children of `ArrayExpression` nodes
* Adjacent Sibling: the `+` character can be used to match nodes that follow other nodes. For example `FunctionDeclaration + VariableDeclaration` matches the first `VariableDeclaration` nodes that follow `FunctionDeclaration` nodes
* General Sibling: the `~` character can be used to match all the nodes that follow other nodes. For example `FunctionDeclaration ~ VariableDeclaration` matches all the `VariableDeclaration` nodes that follow `FunctionDeclaration` nodes

### Groups
A selector can contain multiple selector groups separated by commas.
For example: `Literal, ArrayExpression` match all the `Literal` and `ArrayExpression` nodes.