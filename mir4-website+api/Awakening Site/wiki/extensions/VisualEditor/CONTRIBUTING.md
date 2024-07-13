# Contributing to VisualEditor

Thank you for helping us develop VisualEditor!

We inherit the contribution guidelines from VisualEditor core. Be sure to read the
[Contribution guidelines](https://gerrit.wikimedia.org/g/VisualEditor/VisualEditor/+/master/CONTRIBUTING.md)
in the VisualEditor repository.


## Running tests

The VisualEditor plugins for MediaWiki can be tested within your MediaWiki install.

[Configure your wiki](https://www.mediawiki.org/wiki/Manual:JavaScript_unit_testing) to
allow running of tests. In `LocalSettings.php`, set:
```php
// https://www.mediawiki.org/wiki/Manual:JavaScript_unit_testing
$wgEnableJavaScriptTest = true;
```

Then open `http://URL_OF_MEDIAWIKI/index.php/Special:JavaScriptTest/qunit`
(for example, <http://localhost/w/index.php/Special:JavaScriptTest/qunit>).

Node-based code linting tests can be run locally using npm – run:

<pre lang="bash">
npm install && npm test
</pre>


## Pre-commit hook

A pre-commit git hook script exists which will help flag up any issues and avoid irritating code review steps for you and reviewers. Simply do:

<pre lang="bash">
ln -s bin/pre-commit.sh ../.git/hooks/pre-commit
</pre>
