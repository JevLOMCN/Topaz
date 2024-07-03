#!/bin/sh

# Escape Doxygen commands, used for Markdown files (T185728)
cat $1 | sed 's/\\/\\\\/g'

