Please see [the Parsoid project page](https://www.mediawiki.org/wiki/Parsoid)
for some information on how to get started with these tests and the current
parser architecture.

Install dependencies and run tests:

	$ cd ..  # if you are in this README file's directory
	$ npm test

Running parserTests.js
----------------------

For parserTests, you also need MediaWiki's parser test cases
(`parserTests.txt`).  Parsoid maintains its own fork of the MediaWiki
parser test cases, and we synchronize it from time to time.  You can
also specify a test case file as an argument, or symlink
`parserTests.txt` from a `mediawiki/core` git checkout.

	$ node bin/parserTests.js

Several options are available for parserTests:

	$ node bin/parserTests.js --help

Enjoy!

Running the dumpgrepper
-----------------------

The dumpgrepper utility is useful to search XML dumps for specific regexp
patterns. With a simple regexp, an enwiki dump can be grepped in ~20 minutes.

The grepper operates on actual wikitext (with XML encoding removed), so there is
no need to complicate regexps with entities. It supports JavaScript RegExps.

	$ npm install -g dumpgrepper

More information on [github](https://github.com/wikimedia/dumpgrepper) and the
[mediawiki wiki](https://www.mediawiki.org/wiki/Parsoid/DumpGrepper).
