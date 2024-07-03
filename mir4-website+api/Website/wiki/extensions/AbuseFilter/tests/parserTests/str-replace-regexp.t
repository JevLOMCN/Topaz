str_replace_regexp( "foobarbaz", "bar", "" ) === 'foobaz' &
str_replace_regexp( "foo1bar1baz", "\d", "" ) === 'foobarbaz' &
str_replace_regexp( "foobarbaz", "(bar)", "$1baz" ) === 'foobarbazbaz'
