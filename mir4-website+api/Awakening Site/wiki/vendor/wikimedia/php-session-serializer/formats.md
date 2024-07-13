PHP session serialization formats
=================================

These are the currently available formats in basic PHP.

In the older formats, you can see the legacy of PHP's old "register random
global variables for the session" mechanism in the fact that they have the
ability to indicate unset-but-present as a distinct value.


php
---

This is PHP's default format. Set values are encoded as
`{$key}|{$serializedValue}`, while unset values are `{$key}!` and placed at the
end of the string. An attempt to insert unset-but-present in the middle of the
string will append it to the next key (since 5.2.6 or earlier, anyway).

For example, `foo|i:1;baz|s:6:"string";bar!` encodes session data with
'foo' = 1, 'baz' = "string", and 'bar' is present but unset.

Due to these delimiters, keys may not contain pipe (`|`) or bang (`!`) characters.
Attempting to serialize a `$_SESSION` array containing these characters will
fail.

Keys also may not be numeric. Any numeric entries in `$_SESSION` will be ignored.

There is no value length or delimiter.


php_binary
----------

Keys are encoded as a byte length (up to 127) followed by the indicated number
of bytes. If the variable is unset, the high bit of the length is set.
Otherwise, the serialized value follows immediately after the end of the key.

Depending on the version of PHP, an unset-but-present might ignore the key,
might set the key to null, or might stop processing the rest of the string
entirely. This library takes the first option.

For example, `\x03fooi:1;\x03bazs:6:"string";\x83bar` encodes session data with
'foo' = 1, 'baz' = "string", and 'bar' is present but unset.

Due to the size of the length field, keys may not be more than 127 bytes long.
Entries in `$_SESSION` with longer keys are ignored.

Keys also may not be numeric. Any numeric entries in `$_SESSION` will be ignored.

There is no value length or delimiter.


php_serialize
-------------

This is the most reliable format, added in PHP 5.5.4. The format is just
`$_SESSION` passed to the standard [`serialize()`][serialize] function. It does
not have the ability to indicate unset-but-present.

For example, `a:2:{s:3:"foo";i:1;s:3:"baz";s:6:"string";}` encodes session data
with 'foo' = 1 and 'baz' = "string".

Unlike other formats, numeric keys are allowed and are stored correctly.

When decoding the session, PHP does not check that the encoded string encodes
an array, and will happily set `$_SESSION` to other types. It will refuse to
re-serialize such a `$_SESSION`, however.


wddx
----

When WDDX support is compiled into PHP, the WDDX format may be used to store
session data. This format, however, cannot represent the full range of PHP data
types (e.g. `INF`, `NAN`, data structures containing references) and so is not
likely to be particularly useful unless you're trying to share saved session
data with code in some other language that has WDDX support.



---
[serialize]: https://php.net/manual/en/function.serialize.php
