#!/bin/bash

# Downloads files which contain input and supposed output for some tests of WikiTexVC within this extension.

# The tests are deactivated by default. They can be found and activated with a flag in:
# - EnWikiFormulaeTest.php
# - ChemRegressionTest.php

# Downloads the file containing all english  wikipedia formula to the testfolder
FILEPATH=../tests/phpunit/unit/WikiTexVC/en-wiki-formulae-good.json
URL=https://raw.githubusercontent.com/wikimedia/mediawiki-services-texvcjs/ca9b33d3b5081ae78829af4c65322becb4f4a216/test/en-wiki-formulae-good.json
curl $URL -o $FILEPATH

# Downloads the file containing for chem-regression tests to the testfolder
FILEPATH=../tests/phpunit/unit/WikiTexVC/chem-regression.json
URL=https://raw.githubusercontent.com/wikimedia/mediawiki-services-texvcjs/fb56991251b8889b554fc42ef9fe4825bc35d0ed/test/chem-regression.json
curl $URL -o $FILEPATH

# Downloads the file containing reference renderings for all english wikipedia chem-regression tests to the testfolder
FILEPATH=../tests/phpunit/unit/WikiTexVC/en-wiki-formulae-good-reference.json
URL=https://zenodo.org/records/7494266/files/normalized.json
curl $URL -o $FILEPATH
