#!/bin/bash -eu

# This script generates a structured git log of commits to the VisualEditor-MediaWiki repository,
# and walks the submodule updates to the lib/ve submodule and the OOjs and OOjs UI pull-through
# build commits to detail all changes since a given branch point.

# Using `git branch -a | grep wmf | sort -V` to automatically pick the latest branch version would
# be nice here, but doesn't work because Mac OS X's version of sort is too old.

# cd to the VisualEditor directory
cd $(cd $(dirname $0)/..; pwd)

# Ensure input is correct
if [ -z "${1:-}" ]
then
	echo >&2 "Usage: listRecentCommits.sh <startBranch>"
	exit 1
fi
STARTHASH=`git rev-parse $1`
if [ "$?" -ne "0" ]
then
	echo >&2 "Parameter is not a valid git branch"
	exit 1
fi

echo "Listing changes since '$1' (hash: $STARTHASH)"
echo ""

LOCALCHANGES=`git log $1.. --oneline --no-merges --reverse --color=never |
	egrep --color=never -v '(translatewiki|BrowserTest)'`

# Iterate over lines matching "Update VE core submodule"
while read -r CHANGE
do
	printf "$CHANGE\n"

	if [[ $CHANGE == *"Update VE core submodule"* ]]
	then
		CHANGEHASH=`cut -f1 -d' ' <<< $CHANGE`

		SUBCHANGES=`git log --format=%B -n1 $CHANGEHASH -- |
			sed -n -e '/New changes/,/^$/p' |
			tail -n +2 |
			sed -e '$ d' |
			grep --color=never -v 'translatewiki'`
		while read -r SUBCHANGE
		do
			printf "\t$SUBCHANGE\n"
		done <<< "$SUBCHANGES"

		# Extra new-line between sub-module pulls for clarity
		printf "\n"
	fi
done <<< "$LOCALCHANGES"
exit
