#!/bin/bash

# Ensure that no more uses of $node -> nodeName creep into our repository...
# we should be using DOMCompat::nodeName( $node )
# See I7579cbd07df7650f4c7105cc9dbdc87ea294bded

BIN=$(dirname $0)
# put this string together in a somewhat awkward way so that it doesn't
# trigger a match itself.
grepPhrase="nodeName"
grepPhrase=">$grepPhrase"
badWords='$node-'"$grepPhrase"

# If any of these commands fail, something is wrong!
set -ev

# Ensure lint output (exactly 1 line) is present!
x=`git grep "$grepPhrase" $BIN/.. | wc -l`
if [ ! $x -eq 1 ]
then
    echo "Uses of $badWords found! :("
    git grep "$grepPhrase" $BIN/..
    exit -1
fi
echo "No new uses of $badWords found! :)"
