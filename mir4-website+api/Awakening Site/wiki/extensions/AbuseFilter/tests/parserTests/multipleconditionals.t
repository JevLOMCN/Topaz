/* For T152281 */
v1 := 0; v2 := 0;
if ( 1 == 1 ) then
(
  v1 := 1; v2 := 1;
)
else
(
  v1 := 2; v2 := 2;
)
end;

if ( 1 == 1 ) then
  ( v1 := 1; )
else
(
  v1 := 2; v2 := 2;
)
end;

if ( 1 == 1 ) then
  ( v1 := 2 === 2 ? 1 : 3; )
else
(
  v1 := 'x' === 'y' ? 2 : 3;
  v2 := v1 ** 2;
)
end;
