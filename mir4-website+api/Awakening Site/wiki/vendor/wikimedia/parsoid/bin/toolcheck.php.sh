#!/bin/bash

# Perform a basic crash test.
# This used to be the
# `parsoidsvc-{repository}-parse-tool-check-trusty`
# test on jenkins. (T141481)

BIN=$(dirname $0)

# If any of these commands fail, something is wrong!
set -ev

php $BIN/parse.php --mock --version

echo "Foo" | php $BIN/parse.php --mock --wt2html
echo "Foo" | php $BIN/parse.php --mock --wt2html --pageBundle
echo "Foo" | php $BIN/parse.php --mock --wt2wt
echo "Foo" | php $BIN/parse.php --mock --html2wt
echo "Foo" | php $BIN/parse.php --mock --html2html

function tempfile () {
  mktemp "${TMPDIR:-/tmp}/$1.XXXXXX"
}

# Check --selser too!
TMPWT=$(tempfile wt)
TMPORIG=$(tempfile orig)
TMPEDIT=$(tempfile edit)
TMPPB=$(tempfile pb)

# inline data-parsoid
echo "<p>foo</p><p>boo</p>" | tee $TMPWT | php $BIN/parse.php --mock | tee $TMPORIG |
    sed -e "s/foo/bar/g" > $TMPEDIT
php $BIN/parse.php --mock --selser --oldtextfile $TMPWT --oldhtmlfile $TMPORIG < $TMPEDIT

# data-parsoid in separate files
php $BIN/parse.php --mock --pboutfile $TMPPB < $TMPWT |
    tee $TMPORIG | sed -e "s/foo/bar/g" > $TMPEDIT
php $BIN/parse.php --mock --pbinfile $TMPPB --selser \
    --oldtextfile $TMPWT --oldhtmlfile $TMPORIG < $TMPEDIT

# Linting
echo "<div>foo" | php $BIN/parse.php --mock --linting

# Ensure lint output (exactly 1 line) is present!
x=`echo "<div>foo" | php $BIN/parse.php --mock --linting 2>&1 > /dev/null | wc -l`
if [ ! $x -eq 1 ]
then
	exit -1
fi

# clean up
/bin/rm $TMPWT $TMPORIG $TMPEDIT $TMPPB
