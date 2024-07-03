equals_to_any( "foo", "bar", "foo", "pizza" ) &
equals_to_any( 15, 3, 77, 18, 15 ) &
equals_to_any( "", 3, 77, 18, 15, "duh" ) === false &
equals_to_any( "", 3, 77, 18, 15, "duh", "" ) &
equals_to_any( true, 1, "true" ) === false &
equals_to_any( "1", 1, [ 1 ], true ) === false &
equals_to_any( [ 1, "1" ], 1, "1" ) === false &
equals_to_any( [ 1, 2, 3 ], [ 1, 2, 3 ] ) &
equals_to_any( [ 1, 2, 3 ], [ 3, 2, 1 ] ) === false &
equals_to_any( [ "foo", "bar" ], [ ] ) === false &
equals_to_any( [ "foo", "bar" ], [ "foo", "bar" ] ) &
equals_to_any( [], [] ) &
equals_to_any( [ 0 ], [] ) === false
