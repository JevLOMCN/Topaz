contains_all("the foo is on the bar", "foo", "is on", "bar") &
!(contains_all(['foo', 'bar', 'hey'], 'foo', 'bar', 'sup')) &
contains_all([1, 2, 3], '1', '2', '3') &
contains_all(
	'base',
	'b',
	'a',
	's',
	'e',
)
