// Since tests.minerva.scripts does
// not pull in the entire module skins.minerva.scripts
// we have to stub certain templates to make it appear like its been loaded.
mw.template.add( 'skins.minerva.scripts', 'IssueNotice.mustache', '' );
module.exports = [
	require( './integration.test.js' ),
	require( './downloadPageAction.test.js' ),
	require( './page-issues/parser.test.js' ),
	require( './AB.test.js' ),
	require( './page-issues/index.test.js' ),
	require( './UriUtil.test.js' ),
	require( './TitleUtil.test.js' ),
	require( './watchstar.test.js' )
];
