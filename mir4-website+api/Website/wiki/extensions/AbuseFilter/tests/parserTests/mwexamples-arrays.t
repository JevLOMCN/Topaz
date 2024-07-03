/* Examples from [[mw:Extension:AbuseFilter/Rules format#Arrays]] */

my_array := [ 5, 6, 7, 10];
my_array[0] == 5 &
length(my_array) == 4 &
int( my_array ) === 4 &
float( my_array ) === 4.0 &
string(my_array) == "5\n6\n7\n10\n" &
5 in my_array == true &
'5' in my_array == true &
'5\n6' in my_array == true &
1 in my_array == true & (
my_array[] := 57;
my_array === [ 5, 6, 7, 10, 57 ]
) & (
my_array[2] := 42;
my_array === [ 5, 6, 42, 10, 57 ]
)
