# Contributing

> READ: [Documentation](https://www.mediawiki.org/wiki/Extension:Math)

These steps outline the development process for the MediaWiki Math extension.

The Math extension manages the rendering of mathematical expressions in wikis such as Wikipedia or private MediaWiki installations.

The wikitext tag math (e.g.,
`<math display=block>E=mc^2</math>`) specifies mathematical expressions in the LaTeX dialect
[texvc.](https://github.com/wikimedia/texvcjs/blob/master/lib/parser.pegjs)
The most recent version of the Math extension converts these tags to mathematical expressions that can be
displayed by most browsers using
[mathoid](https://gitlab.wikimedia.org/repos/mediawiki/services/mathoid).

## Workflow overview

The workflow for changing math-related aspects is the following.

1. Create a ticket and describe the problem.
   (What is "broken"?
   What should be improved?
   Who is suffering from the current situation?
   Who would benefit from the improvement?)
2. (Optional) Propose a solution. (Create a mock for a solution. Estimate the effort.)
3. Get Feedback from the community
4. Get familiar with the codebase, and the environment to plan testing and implementation
5. Implement changes, considering dependencies
6. Get code review
7. Wait for a merge
8. Enable the feature in on the [beta cluster](https://www.mediawiki.org/wiki/Beta_Cluster).
9. Enable the feature in production (appropriate communities)


Note that for rendering math the following dependent software packages are required
* texvcjs
* texvcinfo (depends on texvcjs)
* mathjax
* mathjax-node (depends on mathjax)
* mathoid (depends on mathjax-node, texvcinfo)
* restbase (depends on mathoid)
* Math extension (depends on either restbase, or [mathoid and a database])
* (MathSearch extension depends on Extension math)
### Step 1 (Ticket creation)

> READ: [How to report a bug](https://www.mediawiki.org/wiki/How_to_report_a_bug)

Math specific points:
* If possible, please make a screenshot.
* Create a LaTeX/PDF file to demonstrate what you expect.

Running examples:
1. Have [numbered formulae](https://phabricator.wikimedia.org/T7600).
2. Support for [uppercase greek letters](https://phabricator.wikimedia.org/T218295).
3. Deprecate [non-standard texvc commands](https://phabricator.wikimedia.org/T197842).

### Step 2 (Define success criteria)

Describe the idea of how to solve the problem.

* Are there examples / external software that has solved the problem?
* Consider drawing a mock (screenshot).
* Consider technical and security restrictions

Examples:
1. Numbered equations are challenging since the math extension is not aware of the context. A solution could be to use [CSS counters](https://developer.mozilla.org/en-US/docs/Web/CSS/CSS_Lists_and_Counters/Using_CSS_counters).
2.  Whitelist the appropriate LaTeX commands in the texvc grammar.
3. Show a warning if any of the non-standard commands are used.

### Step 3 (Obtain commuinity consenus)

Send an email to the [math community group](https://meta.wikimedia.org/wiki/Wikimedia_Community_User_Group_Math) with your request grant at least two weeks for the community to reply.

Examples:
1. not yet done (Updated 2019-Nov-14) (not a good example)
2. See http://openmath.org/pipermail/wiki-l/2019-August/000000.html
3. fixed before the community wishlist was established (not a good example).

### Step 4 (Become a developer)
> NOTE: You'll need [Gerrit access](https://www.mediawiki.org/wiki/Developer_access) to contribute code to this repository.

Get familiar with the code and figure out where you need to add your changes and how you could test them.

Understand the current code and see which components have to be changed in which order.

Examples:
1. Possible tests: PHPUnit tests that check if the CSS attribute is present. Selenium tests that check if numbers are rendered. Find the Math extension code position that outputs HTML and find the CSS styles shipped with math. Understand how the math extension handles options. Understand PHPUnit and Selenium tests.
2. Define test cases in MediaWiki, mathoid, and texvcjs. Implement node module texvcjs in and release to the npm bump version number of texvcinfo and implement changes in mathoid.
3. Set up a bot to replace the old command. Add pseudo latex package to discover formulae that contain deprecated macros. Implement changes in texvcjs, texvcinfo, mathoid, and the math extension.

### Step 5

* Take your time implementing tests and components.
* Make sure to consider [best practices for MediaWiki extensions.](https://www.mediawiki.org/wiki/Best_practices_for_extensions)

Examples:
1. Not yet implemented
2. [Step 1: Grammar changes](https://github.com/wikimedia/texvcjs/commit/5b2ce833481616ffcd10502422f7b828a48043f9#diff-84bc28ec886369e8d279d3c006b1134d)
3. [Step 1: Remove commands from the visual editor](https://gerrit.wikimedia.org/r/#/c/mediawiki/extensions/Math/+/440737/)

### Step 6
* Plan time for code review
* React to each point that reviewers point you to
* Do not self merge your changes
* Be prepared for multiple iterations

Examples:
1. Not done yet
2. Feedback on [Version bump](https://github.com/wikimedia/texvcjs/pull/33#issuecomment-531713364) for grammar change to allow for NPM release.
3. See, for example, comment on the [commit message](https://gerrit.wikimedia.org/r/#/c/mediawiki/extensions/Math/+/440737/) in
###  A note to the deployment workflow
* Even after the code review has passed, it might take some time for the changes to get merged and deployed. 

Examples:
1. Bug filed in 2006 nothing happened so far
2. Bug filed in Jun 2018, mostly implemented but still not finalized.
3. Bug filed on 2019-Mar-14. Feature available on Wikipedia.org on 2019-11-13

### A note on larger changes and feature additions
* For larger changes, submit changes to Wikimedia config to enable first in the beta cluster

1. Consider adding a feature flag to isolate your changes so they could be tested intensely before going live.
2. If your code includes a feature flag and is merged and deployed, you need to enable it on the beta cluster.
   This is done by adding a change to the settings file for the cluster.
   Find help for doing that in #wikimedia-tech
3. ... (to be continued)

## Updating Translations
Translations are edited through translatewiki.net. To edit a translation,
[visit this page,](https://translatewiki.net/w/i.php?title=Special:Translate&filter=&group=ext-math&optional=1&task=custom&language=de&action=translate)
select the language you wish to translate to and proceed to edit a translation. The translations will be merged into the repository automatically by a bot (l10n-bot).
