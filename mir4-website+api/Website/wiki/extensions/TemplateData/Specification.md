# TemplateData Specification

## Living Standard

**This version**  
https://phabricator.wikimedia.org/diffusion/ETDA/browse/master/Specification.md

**Editors**  
Timo Tijhof, Trevor Parscal, James D. Forrester, Marielle Volz, Moriel Schottlender, C.Scott Ananian, eranroz, Adam Wight

***

## 1 Introduction

This document specifies the structure that TemplateData blobs must follow.

The key words "MUST", "MUST NOT", "REQUIRED", "SHOULD", "SHOULD NOT", "RECOMMENDED", "NOT RECOMMENDED", "MAY", and "OPTIONAL" in this document are to be interpreted as described in [RFC 2119](http://tools.ietf.org/html/rfc2119).

## 2 Terminology

### 2.1 Template

A page on a MediaWiki website that can be transcluded into other pages. Refer to [mediawiki.org](https://www.mediawiki.org/wiki/Help:Templates) for documentation about what templates are capable of and how to create or use them.

### 2.2 Parameter

A key–value pair associated with the transclusion of a template in order to alter the behaviour of a template (and by extend, the content it will return as part of the transclusion).

### 2.3 Author

A program that creates and modifies TemplateData blobs, and distributes them to TemplateData consumer programs.

### 2.4 Consumer

A program interpreting TemplateData blobs (supplied by a TemplateData author).

### 2.5 User

A person using a TemplateData consumer program.

## 3 Structures

Authors MUST ensure TemplateData blobs have exactly one top-most structure of key–value pairs (hereafter referred to as "objects" having "properties") that follow the specification for the "Root" structure, as specified in section 3.1 below.

All objects share the following requirements:

1. Authors MUST ensure they only have properties described in this specification. Properties not specified here MUST not be present. Authors MUST ensure that properties and structures marked as "Required" are present. Other properties MAY be omitted.

2. Authors MUST ensure objects do not have multiple properties with the same key.

Requirements for non-Required ("optional") properties only apply if the property is present.

### 3.1 Root

#### 3.1.1 `description`
* Value: `null` or `InterfaceText`

#### 3.1.2 `params`
* Required
* Value: `Object` containing `Param` objects, see 3.2

Describes each of the template's parameters to a User.

Authors MUST ensure that the `params` object maps parameter names to `Param` objects.

#### 3.1.3 `paramOrder`
* Value: `Array` of `string`

The logical order of the parameters.

Authors MUST ensure the `paramOrder` array contains all parameter names exactly once.

Consumers SHOULD display the parameters in this order.

#### 3.1.4 `sets`
* Required
* Value: `Array` of `Set` objects, see 3.3

List of groups of parameters that can be used together.

Authors MUST ensure that the `sets` object contains only `Set` objects. Authors MAY include a parameter in multiple `Set` objects. Authors are NOT REQUIRED to reference each parameter in at least one `Set` object.

A Consumer MAY encourage users to interact with parameters in a `Set` together (e.g. add, edit, or remove those parameters at once).

#### 3.1.5 `maps`
* Required
* Value: `Object` containing `Map` objects, see 3.6

An object describing which parameter(s) specific Consumers SHOULD use for some purpose.

The `maps` property contains key–value pairs where the key identifies a given Consumer, and the value a `Map` object.

Consumers are NOT REQUIRED to have a corresponding `Map` object.

Consumers that look for a `Map` SHOULD publicly document their identifier key.

Authors MUST ensure that the `maps` object contains only `Map` objects. Authors MAY include a parameter in multiple `Map` objects. Authors are NOT REQUIRED to reference each parameter in at least one `Map` object.

#### 3.1.6 `format`
* Value: `null` or `FormatString` (see 3.7) or `string` of either `'inline'` or `'block'`
* Default: `null`

How the template's wikitext representation SHOULD be laid out. Authors MAY choose to use this parameter to express that a template will be better understood by other human readers of the wikitext representation if a template is in one form or the other.

If the parameter is set to `'block'`, it MUST be interpreted as the format string `'{{_\n| _ = _\n}}'`.  If the parameter is set to `'inline'` it MUST be interpreted as the format string `'{{_|_=_}}'`.

If the parameter is not null, Consumers SHOULD create a wikitext representation corresponding to the given format string, as described in section 3.7.

If the parameter is set to `null`, Consumers SHOULD create the same representation as for `'inline'` format for new template transclusions, and SHOULD attempt to use the same formatting for new parameters as for existing ones for existing transclusions that are edited.

In the absence of the parameter being set, the system will supply `null` as a fallback value.

### 3.2 Param
* Value: `Object`

#### 3.2.1 `label`
* Value: `null` or `InterfaceText`
* Default: Key of this `Param` object as referenced from `Root.params`.

Label of this parameter.

Authors are RECOMMENDED to provide unique labels for each parameter.

Consumers SHOULD use this when referring to a parameter in the interface for users.

#### 3.2.2 `description`
* Value: `null` or `InterfaceText`

Explanatory text describing the purpose of this parameter to a User.

Authors are RECOMMENDED to give each parameter a description. Authors MAY use include hints as to how to the format the parameter value.

For example, a template parameter called `name` might have as its description _"The name of the author of the book, to positively identify and attribute the claim. The name should be given as it would appear in the author's native language, script and culture."_.

Consumers SHOULD provide this description to a the User when selecting and altering parameter values.

#### 3.2.3 `required`
* Value: `boolean`
* Default: `false`

Whether the template being described requires this parameter to be present in order to function properly. For example, a template used to render a hyperlink to a book viewer might mark its "ISBN" parameter as required, whereas it might consider a parameter "publication date" to be optional.

Authors SHOULD only set this flag if the template will not function correctly without this parameter being set.

Consumers SHOULD encourage the User to fill in these parameters, and MAY prevent users from transcluding a template when a required parameter has not been set.

#### 3.2.4 `suggested`
* Value: `boolean`
* Default: `false`

Whether the parameter is recommended to be set to an explicit value for the template transclusion to be useful. This status is less strong an indicator than `required`. For example, in a book template the author's name might be `suggested`, whereas the title might be `required`.

Authors MUST NOT use `suggested` for parameters without which the template will not function correctly.

Consumers MAY encourage users to fill in a suggested parameter, but SHOULD NOT prevent users from transcluding a template when a suggested parameter is not set.

#### 3.2.5 `deprecated`
* Value: `boolean` or `string`
* Default: `false`

Whether this parameter is discouraged from usage. Seting to `false` indicates the parameter is not deprecated.

Authors are RECOMMENDED to, when marking a parameter as deprecated, provide a `string` of explanatory text describing why the parameter is deprecated (and what parameter or parameters the user should use instead). Alternatively, Authors MAY use the value `true` in absence of explanatory text.

#### 3.2.6 `aliases`
* Value: `Array` of `integer` or `string`
* Default: `[]`

An array of alternative names for the parameter.

Authors MUST NOT provide a `Param` object in `Root.params` for any alias. To describe a discouraged parameter with additional information, authors SHOULD describe the parameter as deprecated instead of alias.

Consumers SHOULD display aliases where entered as secondary to the primary name.

#### 3.2.7 `type`
* Value: `Type`, see 3.4

The kind of value the template expects to be associated with this parameter.

Consumers MAY provide user interfaces that restrict or simplify entering values based on the parameter type.

#### 3.2.8 `inherits`
* Value: `string`

The key of another parameter from which this parameter will inherit properties. Local properties will override the inherited properties.

Authors MUST ensure that the value matches the key of a `Param` object in `Root.params`.

#### 3.2.9 `autovalue`
* Value: `null` or `string`

A dynamically-generated default value in wikitext, such as today's date or the editing user's name; this will often involve wikitext substitution, such as `{{subst:CURRENTYEAR}}`.

Consumers MUST insert a parameter's autovalue by default if this parameter is used in a transclusion, and SHOULD indicate this value to the user and give them the opportunity to change the value.

#### 3.2.10 `default`
* Value: `null` or `InterfaceText`

The default value in wikitext (or description thereof) of a parameter as assumed by the template when the parameter is not present in a transclusion.

Consumers SHOULD indicate this default value to the user when inserting or editing a template.

#### 3.2.11 `suggestedvalues`
* Value: `Array` of `string`
* Default: `[]`

A list of commonly used, suggested values.

Consumers SHOULD provide this list to the user when filling out the field, but MAY implement suggested values only for some field types.

Consumers MUST allow the user to enter free-form values not on this list.

#### 3.2.12 `example`
* Value: `null` or `InterfaceText`

An example text for the parameter, to help users fill in the proper value.

### 3.3 Set
* Value: `Object`

#### 3.3.1 label
* Required
* Value: `InterfaceText`

A label for this set.

Authors are RECOMMENDED to ensure these are unique and distinguishable from other labels.

#### 3.3.2 params
* Required
* Value: `Array` of `string`

A list of one or more `Root.params` keys.

### 3.4 Type
* Value: `string`
* Default: `'unknown'`

One of the following:

- `unknown`<br/>
  When no type is specified.
- `string`<br/>
  Any textual value.
- `number`<br/>
  Any numerical value (without decimal points or thousand separators).
- `boolean`<br/>
  A boolean value ('1' for true, '0' for false, '' for unknown).
- `date`<br/>
  A date in ISO 8601 format, e.g. "2014-05-09" or "2014-05-09T16:01:12Z".
- `url`<br/>
  A URL, including protocol, e.g. "http://www.example.org",
  "https://example.org", or "//example.org".
- `wiki-page-name`<br/>
  A valid MediaWiki page name for the current wiki. Doesn't have to exist,
  but if not, should be a valid page name to create.
- `wiki-user-name`<br/>
  A valid MediaWiki user name for the current wiki. Doesn't have to exist,
  but if not, should be a valid user name to create. Should not include any
  localised or standard namespace prefix ("Foo" not "User:Foo").
- `wiki-file-name`<br/>
  A valid MediaWiki file name for the current wiki. Doesn't have to exist,
  but if not, should be a valid file name to upload. Should not include any
  localised or standard namespace prefix ("Foo" not "File:Foo").
- `wiki-template-name`<br/>
  A valid MediaWiki template name for the current wiki. Doesn't have to exist,
  but if not, should be a valid template name to create. Should not include any
  localised or standard namespace prefix ("Foo" not "Template:Foo").
- `content`<br/>
  Page content (such as text style, links and images etc.).
- `unbalanced-wikitext`<br/>
  Raw wikitext that should not be treated as standalone content because it
  is unbalanced (eg. templates concatenating incomplete wikitext as a bigger
  whole such as `{{echo|before=<u>|after=</u>}}`)
- `line`<br/>
  Short text field - use for names, labels, and other short-form fields.

### 3.5 InterfaceText
* Value: `string` or `Object`

A free-form string (no wikitext) in the content-language of the wiki, or,
an object containing those strings keyed by language code.

### 3.6 Map
* Value: `Object`

Each key in a `Map` object can be arbitrary. The value must match a parameter name, a list of parameter names, or list containing lists of parameter names.

The key corresponds to the name of a Consumer variable that relates to the specified parameter(s).

### 3.7 FormatString
* Value: `string`

A format string describes how whitespace should be added to a template instantiation in wikitext.  A format string looks like `{{_|_=_}}`, with optional whitespace and extended underscores.  Formally, its structure is given by the following grammar:
```
FormatString = StartFormat ParameterFormat EndFormat
StartFormat = nl? "{{" ws* Hole
ParameterFormat = nl? ws* "|" nl? ws* Hole ws* "=" ws* Hole
EndFormat = nl? ws* "}}" nl?
Hole = "_"+
ws = " "
nl = "\n"
```

To format a template invocation according to the format string, first split it in three parts, corresponding to `StartFormat`, `ParameterFormat`, and `EndFormat` in the grammar above.  In the following, to "replace the `Hole`" with some value means to first pad a non-empty value with spaces on the right until it is at least as many characters long as the replaced underscore sequence.  Zero-length values are not padded.

Begin with `StartFormat`, and replace the `Hole` with the name of the template to create the "output string".  If `StartFormat` begins with a newline and template is already at the start of a line (the character preceding this template invocation is a newline or the template is at the start of the output), delete the initial newline from the output string.

For each parameter, if `ParameterFormat` begins with a newline and the last line in the output string is nothing but whitespace and comments, then delete the newline from the beginning of the `ParameterFormat`.  Then append `ParameterFormat` to the output string after replacing the first `Hole` with the name of the parameter and the second `Hole` with the value of the parameter.

Finally, if `EndFormat` begins with a newline and the last line in the output string is nothing but whitespace and comments, then delete the newline from the beginning of the `EndFormat`.  Or, if there were no parameters, also delete the newline from the beginning of the `EndFormat`.  Append the `EndFormat` to the output string.

Some example format strings:

Inline formatting: `{{_|_=_}}`
```
{{Foo|bar=baz|qux=quux}}{{Bar}}
```

Block formatting: `{{_\n| _ = _\n}}`
```
{{Foo
| bar = baz
| qux = quux
}}{{Bar}}
```

No space before the parameter name, each template on its own line: `\n{{_\n|_ = _\n}}\n`
```
{{Foo
|bar = baz
|qux = quux
}}
{{Bar}}
```

Indent each parameter: `{{_\n |_ = _\n}}`
```
{{Foo
 |bar = baz
 |qux = quux
}}{{Bar}}
```

Align all parameter names to a given length: `{{_\n|_______________ = _\n}}\n`
```
{{Foo
|bar             = baz
|qux             = quux
|veryverylongparameter = bat
}}
{{Bar}}
```

Pipe characters at the end of the previous line: `{{_|\n  _______________ = _}}`
```
{{Foo|
  bar             = baz|
  qux             = quux}}{{Bar}}
```

Inline style with more spaces, must be at start of line: `\n{{_ | _ = _}}`
```
{{Foo | bar = baz | qux = quux}}
{{Bar }}
```

## 4 Examples

### 4.1 The "Unsigned" template

```json
{
	"description": "Label unsigned comments in a conversation.",
	"params": {
		"user": {
			"label": "User's name",
			"type": "wiki-user-name",
			"required": true,
			"description": "User name of person who forgot to sign their comment.",
			"aliases": ["1"]
		},
		"date": {
			"label": "Date",
			"suggested": true,
			"description": {
				"en": "Timestamp of when the comment was posted, in YYYY-MM-DD format."
			},
			"aliases": ["2"],
			"autovalue": "{{subst:#time:Y-m-d}}"
		},
		"year": {
			"label": "Year",
			"type": "number"
		},
		"month": {
			"label": "Month",
			"inherits": "year"
		},
		"day": {
			"label": "Day",
			"inherits": "year"
		},
		"comment": {
			"required": false
		}
	},
	"sets": [
		{
			"label": "Date",
			"params": ["year", "month", "day"]
		}
	],
	"maps": {
		"ExampleConsumer": {
			"foo": "user",
			"bar": ["year", "month", "day"],
			"quux": [
				"date",
				["day", "month"],
				["month", "year"],
				"year"
			]
		}
	}
}
```

Example transclusions
```
{{unsigned|JohnDoe|2012-10-18}}

{{unsigned|user=JohnDoe|year=2012|month=10|day=18|comment=blabla}}
```
