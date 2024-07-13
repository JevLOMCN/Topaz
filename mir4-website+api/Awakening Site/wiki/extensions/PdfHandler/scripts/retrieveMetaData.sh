#!/bin/sh

# Get parameters from environment

export PDFHANDLER_INFO="${PDFHANDLER_INFO:-pdfinfo}"
export PDFHANDLER_TOTEXT="${PDFHANDLER_TOTEXT:-pdftotext}"

runInfo() {
	# Note in poppler 0.26 the -meta and page data options worked together,
	# but as of poppler 0.48 they must be queried separately.
	# https://bugs.freedesktop.org/show_bug.cgi?id=96801
	# Report metadata as UTF-8 text...and report XMP metadata
	"$PDFHANDLER_INFO" \
		-enc 'UTF-8' \
		-meta \
		file.pdf 1> meta

	# Check for errors and forward them
	if [ $? -ne 0 ]; then
		exit 1;
	fi

	# Report metadata as UTF-8 text...and report page sizes for all pages
	"$PDFHANDLER_INFO" \
		-enc 'UTF-8' \
		-l 9999999 \
		file.pdf 1> pages

	# Check for errors and forward them
	if [ $? -ne 0 ]; then
		exit 1;
	fi
}

runToText() {
	# CropBox defines the region that the PDF viewer application is expected to display or print.
	# pdftotext's -cropbox was only introduced in poppler 21.03.0
	# It also only works with -bbox so we cannot use it.
	# Some text that is not visible in the PDF might thus be included in the output
	"$PDFHANDLER_TOTEXT" \
		file.pdf - > text
	# Store exit code so we can use it later
	echo $? > text_exit_code
}

if [ -x "$(command -v $PDFHANDLER_INFO)" ]; then
	runInfo
fi

if [ -x "$(command -v $PDFHANDLER_TOTEXT)" ]; then
	runToText
fi
