strpos( "foobarfoo", "foo" ) === 0 &
strpos( "foobarfoo", "" ) === -1 &
strpos( "foobarfoo", "foo", 1 ) === 6 &
strpos( "foobarfoo", "lol" ) === -1 &
/* Offset not contained in the haystack */
strpos( "foo", "o", 123456 ) === -1
