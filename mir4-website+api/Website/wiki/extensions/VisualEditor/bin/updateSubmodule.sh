#!/bin/bash -eu

# This script generates a commit that updates the lib/ve submodule
# ./bin/updateSubmodule.sh        updates to master
# ./bin/updateSubmodule.sh hash   updates to specified hash

# cd to the VisualEditor directory
cd $(cd $(dirname $0)/..; pwd)

# Check that both working directories are clean
if git status -uno --ignore-submodules | grep -i changes > /dev/null
then
	echo >&2 "Working directory must be clean"
	exit 1
fi
cd lib/ve
if git status -uno --ignore-submodules | grep -i changes > /dev/null
then
	echo >&2 "lib/ve working directory must be clean"
	exit 1
fi
cd ../..

# Use 'gerrit' if it exists, otherwise 'origin'
MW_REMOTE=$(git remote | grep -w gerrit || echo origin)

git fetch $MW_REMOTE
# Create sync-repos branch if needed and reset it to master
git checkout -B sync-repos $MW_REMOTE/master
git submodule update
cd lib/ve

CORE_REMOTE=$(git remote | grep -w gerrit || echo origin)

git fetch $CORE_REMOTE

# Figure out what to set the submodule to
if [ -n "${1:-}" ]
then
	TARGET="$1"
	TARGETDESC="$1"
else
	TARGET=$CORE_REMOTE/master
	TARGETDESC="master ($(git rev-parse --short $CORE_REMOTE/master))"
fi

# Generate commit summary
# TODO recurse
NEWCHANGES=$(git log ..$TARGET --oneline --no-merges --topo-order --reverse --color=never)
TASKS=$(git log ..$TARGET --no-merges --format=format:%B | grep "Bug: T" | sort | uniq)
  ADDED_I18N_KEYS=$(git diff HEAD..$TARGET -- i18n/en.json | grep '^\+' | grep --color=never -v '^\+\+\+' | sed -E 's/^\+\s*"([^"]+)":.*/\1/' | sed 's/^/- /' || :)
DELETED_I18N_KEYS=$(git diff HEAD..$TARGET -- i18n/en.json | grep '^\-' | grep --color=never -v '^\-\-\-' | sed -E 's/^\-\s*"([^"]+)":.*/\1/' | sed 's/^/- /' || :)

# Ensure script continues if grep "fails" (returns nothing) with || : (due to -e flag in bash)
  ADDED_FILES=$(git diff HEAD..$TARGET --name-only --diff-filter=A | grep --color=never -E "\.(js|css|less)$" | sed 's/^/- /' || :)
DELETED_FILES=$(git diff HEAD..$TARGET --name-only --diff-filter=D | grep --color=never -E "\.(js|css|less)$" | sed 's/^/- /' || :)

COMMITMSG="Update VE core submodule to $TARGETDESC

New changes:
$NEWCHANGES"

if [ -n "$ADDED_I18N_KEYS" ]; then
    COMMITMSG+="

Added i18n keys:
$ADDED_I18N_KEYS"
fi

if [ -n "$DELETED_I18N_KEYS" ]; then
    COMMITMSG+="

Deleted i18n keys:
$DELETED_I18N_KEYS"
fi

if [ -n "$ADDED_FILES" ]; then
    COMMITMSG+="

Added files:
$ADDED_FILES"
fi

if [ -n "$DELETED_FILES" ]; then
    COMMITMSG+="

Deleted files:
$DELETED_FILES"
fi

COMMITMSG+="

$TASKS"

# Check out master of VE core
git checkout $TARGET

# Commit
cd ../..
git commit lib/ve -m "$COMMITMSG" > /dev/null
if [ "$?" == "1" ]
then
	echo >&2 "No changes"
else
	cat >&2 <<END


Created commit:

$COMMITMSG
END
fi
