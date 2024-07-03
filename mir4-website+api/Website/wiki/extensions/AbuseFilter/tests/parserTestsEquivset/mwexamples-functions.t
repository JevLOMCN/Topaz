/* Examples from [[mw:Extension:AbuseFilter/Rules format#Functions]] */

length( "Wikipedia" ) === 9 &
lcase( "WikiPedia" ) === 'wikipedia' &
ccnorm( "w1k1p3d14" ) === 'WIKIPEDIA' &
ccnorm( "ωɨƙɩᑭƐƉ1α" ) === 'WIKIPEDIA' &
ccnorm_contains_any( "w1k1p3d14", "wiKiP3D1A", "foo", "bar" ) === true &
ccnorm_contains_any( "w1k1p3d14", "foo", "bar", "baz" ) === false &
ccnorm_contains_any( "w1k1p3d14 is 4w3s0me", "bar", "baz", "some" ) === true &
ccnorm( "ìíîïĩїį!ľ₤ĺľḷĿ" ) === 'IIIIIII!LLLLLL' &
norm( "!!ω..ɨ..ƙ..ɩ..ᑭᑭ..Ɛ.Ɖ@@1%%α!!" ) === 'WIKIPEDAIA' &
norm( "F00  B@rr" ) === 'FOBAR' &
rmdoubles( "foobybboo" ) === 'fobybo' &
specialratio( "Wikipedia!" ) === 0.1 &
count( "foo", "foofooboofoo" ) === 3 &
count( "foo,bar,baz" ) === 3 &
rmspecials( "FOOBAR!!1" ) === 'FOOBAR1' &
rescape( "abc* (def)" ) === 'abc\* \(def\)' &
str_replace( "foobarbaz", "bar", "-" ) === 'foo-baz' &
ip_in_range( "127.0.10.0", "127.0.0.0/12" ) === true &
contains_any( "foobar", "x", "y", "f" ) === true &
get_matches( "(foo?ba+r) is (so+ good)", "fobaaar is soooo good to eat" ) === ['fobaaar is soooo good', 'fobaaar', 'soooo good']