count("a,b,c,d") === 4 &
count(",", "a,b,c,d") === 3 &
count("", "abcd") === 0 &
count("a", "abab") === 2 &
count("ab", "abab") === 2 &
count("aa", "aaaaa") === 2 &
count( [ "a", "b", "c" ] ) === 3 &
count( [] ) === 0
