#!/usr/bin/env bash
found=0

for svgfile in `find resources -type f -name "*.svg"`; do
	outfile="$svgfile.tmp"
	echo -n "Checking compression: $svgfile ... "
	node_modules/.bin/svgo --config .svgo.config.js -i "$svgfile" -o "$outfile" -q
	if [ -f $outfile ]; then
		if [ "$(wc -c < "$svgfile")" -gt "$(wc -c < "$outfile")" ]; then
			echo "File $svgfile is not compressed."
			found=$((found + 1))
		fi
		rm "$outfile"
	fi
done

if [ $found -gt 0 ]; then
	echo "Found $found uncompressed SVG files. Please compress the files and re-submit the patch."
	exit 1
fi
