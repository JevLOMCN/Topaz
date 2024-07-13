#!/bin/bash -eu

# This script generates a commit that updates our copy of UnicodeJS

if [ -n "${2:-}" ]
then
	# Too many parameters
	echo >&2 "Usage: $0 [<version>]"
	exit 1
fi

REPO_DIR=$(cd "$(dirname $0)/.."; pwd) # Root dir of the git repo working tree
TARGET_DIR="lib/unicodejs" # Destination relative to the root of the repo
NPM_DIR=$(mktemp -d 2>/dev/null || mktemp -d -t 'update-unicodejs') # e.g. /tmp/update-unicodejs.rI0I5Vir

# Prepare working tree
cd "$REPO_DIR"
git reset -- $TARGET_DIR
git checkout -- $TARGET_DIR
git fetch origin
git checkout -B upstream-unicodejs origin/master

# Fetch upstream version
cd $NPM_DIR
if [ -n "${1:-}" ]
then
	npm install "unicodejs@$1"
else
	npm install unicodejs
fi

UNICODEJS_VERSION=$(node -e 'console.log(require("./node_modules/unicodejs/package.json").version);')
if [ "$UNICODEJS_VERSION" == "" ]
then
	echo 'Could not find UnicodeJS version'
	exit 1
fi

# Copy file(s)
rsync --force ./node_modules/unicodejs/dist/unicodejs.js "$REPO_DIR/$TARGET_DIR"

# Clean up temporary area
rm -rf "$NPM_DIR"

# Generate commit
cd $REPO_DIR

COMMITMSG=$(cat <<END
Update UnicodeJS to v$UNICODEJS_VERSION

Release notes:
 https://gerrit.wikimedia.org/g/unicodejs/+/$UNICODEJS_VERSION/History.md
END
)

# Stage deletion, modification and creation of files. Then commit.
git add --update $TARGET_DIR
git add $TARGET_DIR
git commit -m "$COMMITMSG"
