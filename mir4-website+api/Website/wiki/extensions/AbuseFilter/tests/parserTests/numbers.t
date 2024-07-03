/* Things that are NOT numbers; */
0xfoo := 'foobar';
0b10bar := 'bar';

0xfoo === 'foobar' & 0b10bar === 'bar' &

/* Actual numbers */
0x1A === 0x1a & 0x1a === 26 & 0xa === 10 & 0b11111111 === 255 & 0o123 === 83 & 0x123 === 291 & 0xF === 15 &
0o10 === 8 & 0o1 === 1 & 0b101010 === 42 & 0b101010 === 0x2a & 0x2a === 0o52
