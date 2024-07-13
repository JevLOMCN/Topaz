#!/bin/bash

domain=$1
title=$2
wt=$3

# Parsoid/PHP test
php bin/parse.php --domain $1 --pageName '$2' < $3 > $3.php.html
sed 's/<\/body>/<!--BOO--><\/body>/g;'    < $3.php.html > $3.php.new.html
php bin/parse.php --domain $1 --pageName '$2' --selser --oldtextfile $3 --oldhtmlfile $3.php.html < $3.php.new.html > $3.php.new

# Parsoid/JS test
node bin/parse.js --domain $1 --pageName '$2' < $3 > $3.js.html
sed 's/<\/body>/<!--BOOBOO--><\/body>/g;' < $3.js.html  > $3.js.new.html
node bin/parse.js --domain $1 --pageName '$2' --selser --oldtextfile $3 --oldhtmlfile $3.js.html  < $3.js.new.html  > $3.js.new

diff $3.php.new $3.js.new
