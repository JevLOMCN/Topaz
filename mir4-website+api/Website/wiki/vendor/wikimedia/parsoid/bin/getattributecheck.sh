#!/bin/bash

# Ensure that no more uses of $node -> getAttribute creep into our repository...
# we should be using DOMCompat::getAttribute( $node )

BIN=$(dirname $0)
# put this string together in a somewhat awkward way so that it doesn't
# trigger a match itself.
grepPhrase="getAttribute"
badWords='$node->'"$grepPhrase"'('
grepPhrase="[-]>$grepPhrase[(]"

# If any of these commands fail, something is wrong!
set -ev

# exclude DOMCompat
FILES=$(git grep -l '[-]>')

# Ensure exactly 1 line is present (in DOMCompat::getAttribute itself)
x=`git grep "$grepPhrase" $FILES | wc -l`
if [ ! $x -eq 1 ]
then
    echo "Uses of $badWords found! :("
    git grep -P "$grepPhrase" $FILES
    exit -1
fi
echo "No new uses of $badWords found! :)"
