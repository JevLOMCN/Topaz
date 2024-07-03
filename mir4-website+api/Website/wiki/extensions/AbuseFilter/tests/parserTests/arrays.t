array1 := [ 'a', 'b', 'c', ];
array2 := [];
array2[] := 'd';
array2[] := 'g';
array2[] := 'f';
array2[1] := 'e';

array3 := array1 + array2;
array4 := [ [ 1, 2, 3 ], [ 4, 5, 6 ] ];

(string(array3) == "a\nb\nc\nd\ne\nf\n" & !('b' in array2) & array1 contains 'c' & [ false, !(1;0), null ][1] & length(array3) == 6 &
	array4[1][1] == 5 )
