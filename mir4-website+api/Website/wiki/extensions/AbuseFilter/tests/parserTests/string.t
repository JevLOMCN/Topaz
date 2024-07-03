"a\tb" === "a	b" &
"a\qb" === "a\qb" &
"a\"b" === 'a"b' &
"a\rb" !== "a\r\nb" &
"\x66\x6f\x6f" === "foo" &
"some\xstring" === "some\\xstring" &
"some\vstring" === "some\\vstring" &
/* T238475 */
'\x{}' === '\x' + '{}' &
length('\x{}') === 4 &
'foobar' rlike '[\x{61}-\x{7a}]'
