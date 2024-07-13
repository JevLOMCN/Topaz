// TODO: Is there a way to set this only for our tests, rather than globally?
QUnit.dump.maxDepth = 999;

require( './utils.test.js' );
require( './parser.test.js' );
require( './modifier.test.js' );
require( './ThreadItem.test.js' );
