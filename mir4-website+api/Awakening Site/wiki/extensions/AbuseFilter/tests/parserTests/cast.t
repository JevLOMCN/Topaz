(string(1) === "1") &
(int("1") === 1) &
(float(1) === 1.0) &
bool(1) & !bool(0) &
bool([]) === false &
bool( [false] ) === true &
bool( [1,2,3,4,5,6] ) === true &
float([]) === 0.0 &
float( [false] ) === 1.0 &
float( [1,2,3,4,5,6] ) === 6.0 &
int([]) === 0 &
int( [false] ) === 1 &
int( [1,2,3,4,5,6] ) === 6 &
true + true === 2 &
null - null === 0 &
true * false === 0 &
163 % true === 0
