# Selenium tests

For more information see https://www.mediawiki.org/wiki/Selenium

## Setup

See https://www.mediawiki.org/wiki/MediaWiki-Docker/MinervaNeue

## Run all specs

    npm run selenium-test

## Run specific tests

Filter by file name:

    npm run selenium-test -- --spec tests/selenium/specs/[FILE-NAME]

Filter by file name and test name:

    npm run selenium-test -- --spec tests/selenium/specs/[FILE-NAME] --mochaOpts.grep [TEST-NAME]

# Migrating a test from Ruby to Node.js

Currently we are in the midst of porting our Ruby tests to Node.js.
When the tests/browser/features folder is empty, we are done and the whole tests/browser folder can be removed.

This is a slow process (porting a single test can take an entire afternoon).

## Step 1 - move feature file
Move the feature you want to port to Node.js
```
mv tests/browser/features/<name>.feature tests/selenium/features/
```
Example: https://gerrit.wikimedia.org/r/#/c/mediawiki/skins/MinervaNeue/+/501792/1/tests/selenium/features/editor_wikitext_saving.feature

## Step 2 - add boilerplate for missing steps
Run the feature in cucumber
```
npm run selenium-test-cucumber -- --spec tests/selenium/features/<name>.feature
```

You will get warnings such as:
```
Step "I go to a page that has languages" is not defined. You can ignore this error by setting cucumberOpts.ignoreUndefinedDefinitions as true.
```

For each missing step define them as one liners inside selenium/features/step_definitions/index.js

Create functions with empty bodies for each step.

Function names should be camel case without space, for example, `I go to a page that has languages` becomes `iGoToAPageThatHasLanguages`. Each function should be imported from a step file inside the features/step_definitions folder.

Rerun the test. If done correctly this should now pass.

Example: https://gerrit.wikimedia.org/r/#/c/mediawiki/skins/MinervaNeue/+/501792/1..2

## Step 3 - copy across Ruby function bodies

Copy across the body of the Ruby equivalent inside each function body in tests/browser/features/step_definitions as comments.

Example: https://gerrit.wikimedia.org/r/#/c/mediawiki/skins/MinervaNeue/+/501792/2..3

## Step 4 - rewrite Ruby to Node.js

Sadly there is no shortcut here. Reuse as much as you can. Work with the knowledge that the parts you are adding will aid the next browser test migration.

The docs are your friend: http://v4.webdriver.io/api/utility/waitForVisible.html

Example: https://gerrit.wikimedia.org/r/#/c/mediawiki/skins/MinervaNeue/+/501792/2..4

## Step 5 - Make it work without Cucumber

Now the tests should be passing when run the browser tests using wdio.conf.cucumber.js or `npm run selenium-test-cucumber`

The final step involves making these run with
`npm run selenium-test`

This is relatively straightforward and mechanical.

1) Copy the feature file to the specs folder
```
cp tests/selenium/features/editor_wikitext_saving.feature tests/selenium/specs/editor_wikitext_saving.js
```
2) Convert indents to tabs
3) Add `//` before any tags
4) Replace `Scenario: <name>` with `it( '<name>', () => {`
5) Add closing braces for all scenarios: `  } );`
6) Replace `Feature: <feature>` with `describe('<feature>)', () => {` and add closing brace.
7) Replace `Background:` with `beforeEach( () => {` and add closing brace.
8) Find and replace `Given `, `When `, `And `, `Then ` with empty strings.
9) At top of file copy and paste imports from `selenium/features/step_definitions/index.js` to top of your new file and rewrite their paths.
10) Relying on autocomplete (VisualStudio Code works great) replace all the lines with the imports
11) Drop unused imports from the file.
