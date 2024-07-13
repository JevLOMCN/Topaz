#!/usr/bin/env bash

if [ $1 = "--reverse" ]; then
	MLDIR=$2
else
	MLDIR=$1
fi

JSDIR=$MLDIR/lib
CSSDIR=$MLDIR/css
IMGDIR=$MLDIR/img
HOOKSFILE=$MLDIR/hooks.txt
LOCALMLDIR=resources/multilightbox

if [ $1 = "--reverse" ]; then
	cp $LOCALMLDIR/*.js $JSDIR
	cp $LOCALMLDIR/multilightbox.css $CSSDIR
	cp img/close.svg img/fullscreen.svg img/defullscreen.svg $IMGDIR
	cp $LOCALMLDIR/hooks.txt $HOOKSFILE
else
	cp $JSDIR/* resources/multilightbox/
	cp $CSSDIR/* resources/multilightbox/
	cp $IMGDIR/* img/
	cp $HOOKSFILE resources/multilightbox/
fi
