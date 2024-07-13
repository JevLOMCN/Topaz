#!/bin/bash -eu

# This script builds a new gh-pages branch from latest master

read -p "This script will delete all untracked files in the VE folder. Continue (y/n)? " -n 1 -r
echo
if [[ ! $REPLY =~ ^[Yy]$ ]]
then
	exit 0
fi

cd "$(dirname $0)/.."
git fetch origin
git checkout -B gh-pages origin/master
git reset --hard origin/master

git clean -dffx
# Run npm-install to fetch qunitjs and build dist/
npm install

html='<!DOCTYPE html>
<meta charset="utf-8">
<title>VisualEditor</title>
<link rel=stylesheet href="lib/oojs-ui/oojs-ui-apex.css">
<link rel=stylesheet href="demos/ve/demo.css">
<style>
	article {
		margin: 1em auto;
		width: 45em;
		max-width: 80%;
		text-align: center;
	}
	article img {
		max-width: 100%;
	}
</style>
<article>
	<img src="demos/ve/VisualEditor-logo.svg" alt="VisualEditor logo">
	<div class="oo-ui-widget oo-ui-widget-enabled oo-ui-buttonElement oo-ui-buttonElement-framed oo-ui-labelElement oo-ui-buttonWidget"><a role="button" href="demos/ve/desktop-dist.html" tabindex="0" class="oo-ui-buttonElement-button"><span class="oo-ui-labelElement-label">Demo</span></a></div></a>
	<div class="oo-ui-widget oo-ui-widget-enabled oo-ui-buttonElement oo-ui-buttonElement-framed oo-ui-labelElement oo-ui-buttonWidget"><a role="button" href="tests/" tabindex="0" class="oo-ui-buttonElement-button"><span class="oo-ui-labelElement-label">Test suite</span></a></div>
</article>'
echo "$html" > index.html

# Disable Jekyll default settings for GitHub Pages
# as otherwise node_modules/qunitjs will not be published.
# https://help.github.com/articles/files-that-start-with-an-underscore-are-missing/
# https://www.bennadel.com/blog/3181-including-node-modules.htm
touch .nojekyll

git add index.html .nojekyll
git add -f node_modules/qunit dist/

git commit -m "Create gh-pages branch"
git push origin -f HEAD
