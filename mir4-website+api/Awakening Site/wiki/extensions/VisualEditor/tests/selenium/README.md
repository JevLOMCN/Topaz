# Selenium tests

For more information see https://www.mediawiki.org/wiki/Selenium

## Setup

See https://www.mediawiki.org/wiki/MediaWiki-Docker/Extension/VisualEditor

## Run all tests

    npm run selenium-test

## Run specific tests

Filter by file name:

    npm run selenium-test -- --spec tests/selenium/specs/[FILE-NAME]

Filter by file name and test name:

    npm run selenium-test -- --spec tests/selenium/specs/[FILE-NAME] --mochaOpts.grep [TEST-NAME]
