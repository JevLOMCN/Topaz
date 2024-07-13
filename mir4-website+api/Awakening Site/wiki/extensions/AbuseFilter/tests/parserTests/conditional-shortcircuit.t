if ( 1 === 1 ) then
( var1 := 1 )
else
( var1 := 2 )
end;

var2 := 0;
if ( 1 === 1 ) then
( var2 := 1 )
else
( var2 := 2 )
end;

var4 := 'foo';
if ( 1=== 1 ) then
( var3 := 1; false & ( var4 := 'foobar' ) )
else
( var3 := 2 )
end;

if ( 1 === 1 ) then
( var5 := 1 )
else
( ( ( var5 := 2 ) ) )
end;


if ( 1 === 1 ) then
( var6 := 1 )
else
(
 if ( 1 == 1 ) then
  ( var6 := 2 )
 else
  ( var6 := 3 )
 end;
)
end;


var7 := 'foo';
if ( 1=== 1 ) then
(0)
else
( false & ( var7 := 'foobar' ) )
end;

var8 := 'foo';
false & (
  if ( 1 == 1 ) then
    (var8 := 3)
  else
    (var8 := 4)
  end;
);

var9 := 'foo';
if ( 1=== 1 ) then
  ( false & ( var9 := 'foobar' ) )
end;

var1 === 1 &
var2 === 1 &
var3 === 1 &
var5 === 1 &
var6 === 1 &
var7 === 'foo' &
var8 === 'foo' &
var9 === 'foo'
