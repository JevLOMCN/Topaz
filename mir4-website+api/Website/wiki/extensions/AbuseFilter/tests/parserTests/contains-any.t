contains_any("like anyone else", "else", "someone") &
contains_any("street fighter", "fight") &
!(contains_any('My foo is cute', 'bar', 'wtf')) &
contains_any([[1], [2,3]], 1)
