#!/usr/bin/env bash

# If the VE core sub-module was touched
if git diff --quiet --cached lib/ve; then

    GITBRANCH=`git rev-parse --abbrev-ref HEAD`;

    # … and it doesn't look like
    if [[ $GITBRANCH != "sync-repos" ]]; then
        echo "VE core sub-module was touched but commit isn't from 'sync-repos'.";
        exit 1;
    fi

fi

# Stash any uncommited changes
git stash -q --keep-index

npm install || git stash pop -q && exit 1
npm test && git add -u .docs/* || git stash pop -q && exit 1

# Re-apply any uncommited changes
git stash pop -q
