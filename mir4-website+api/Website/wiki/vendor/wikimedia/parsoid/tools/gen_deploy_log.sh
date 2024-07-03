#!/bin/bash

if [ $# -lt 2 ]
then
	echo "USAGE: $0 <git-ref-1> <git-ref-2>"
	echo "Ex: $0 v0.14.0-a2 v0.14.0-a3"
	echo "Ex: $0 f9af2a72 master"
	exit 1
fi

# 1. Get git log between the requested commits
# 2. Get lines +2 after date and bug. This pulls in the commit heading which shows 2nd line after date and all Bug: lines
# 3. Get rid of anything not relevant from output of 2.
# 4. Initial sed processing to strip whitespace, add "* " on all lines except "Bug: " lines, convert "Bug: " lines to [[phab: ..]] links
#    - In the edge case that a commit heading starts with a %, the "* " will not be appended.
# 5. Further sed processing to collapse phab links onto previous lines
git log "$1".."$2" | grep -E -A+2 "^\s*(Date|Bug):\s*" | grep -E -v "Change-Id:|Depends-On:|Date:|Co-Authored-by:|Needed-By:|Follow-Up:|Follows-Up:|Followup-To:|--|^\n*$" | sed 's/^[ \t]*//g;s/^Bug:/%%%:/g;s/^[^%]/* &/g;s/%%%:\s*\(.*\)/[[phab:\1|\1]]/g;' | sed -e ':a' -e 'N;$!ba' -e 's/\n*\[\[phab/, [[phab/g;'
