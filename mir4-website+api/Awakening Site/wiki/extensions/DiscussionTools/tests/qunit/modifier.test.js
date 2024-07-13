var
	testUtils = require( './testUtils.js' ),
	Parser = require( 'ext.discussionTools.init' ).Parser,
	modifier = require( 'ext.discussionTools.init' ).modifier;

QUnit.module( 'mw.dt.modifier', QUnit.newMwEnvironment() );

require( '../cases/modified.json' ).forEach( function ( caseItem ) {
	var testName = '#addListItem/#removeAddedListItem (' + caseItem.name + ')';
	// This should be one test with many cases, rather than multiple tests, but the cases are large
	// enough that processing all of them at once causes timeouts in Karma test runner.
	var skipTests = require( '../skip.json' )[ 'cases/modified.json' ];
	if ( skipTests.indexOf( caseItem.name ) !== -1 ) {
		QUnit.skip( testName );
		return;
	}
	QUnit.test( testName, function ( assert ) {
		var dom = ve.createDocumentFromHtml( require( '../' + caseItem.dom ) ),
			expected = ve.createDocumentFromHtml( require( '../' + caseItem.expected ) ),
			config = require( caseItem.config ),
			data = require( caseItem.data );

		testUtils.overrideMwConfig( config );

		var expectedHtml = testUtils.getThreadContainer( expected ).innerHTML;
		var reverseExpectedHtml = testUtils.getThreadContainer( dom ).innerHTML;

		var container = testUtils.getThreadContainer( dom );
		var title = mw.Title.newFromText( caseItem.title );
		var threadItemSet = new Parser( data ).parse( container, title );
		var comments = threadItemSet.getCommentItems();

		// Add a reply to every comment. Note that this inserts *all* of the replies, unlike the real
		// thing, which only deals with one at a time. This isn't ideal but resetting everything after
		// every reply would be super slow.
		var nodes = [];
		comments.forEach( function ( comment ) {
			var node = modifier.addListItem( comment, caseItem.replyIndentation || 'invisible' );
			node.textContent = 'Reply to ' + comment.id;
			nodes.push( node );
		} );

		// Uncomment this to get updated content for the "modified HTML" files, for copy/paste:
		// console.log( container.innerHTML );

		var actualHtml = container.innerHTML;

		assert.strictEqual(
			actualHtml,
			expectedHtml,
			comments.length + ' replies added'
		);

		// Now discard the replies and verify we get the original document back.
		nodes.forEach( function ( node ) {
			modifier.removeAddedListItem( node );
		} );

		var reverseActualHtml = container.innerHTML;
		assert.strictEqual(
			reverseActualHtml,
			reverseExpectedHtml,
			nodes.length + ' replies removed'
		);
	} );
} );

QUnit.test( '#addReplyLink', function ( assert ) {
	var cases = require( '../cases/reply.json' );

	cases.forEach( function ( caseItem ) {
		var dom = ve.createDocumentFromHtml( require( '../' + caseItem.dom ) ),
			expected = ve.createDocumentFromHtml( require( '../' + caseItem.expected ) ),
			config = require( caseItem.config ),
			data = require( caseItem.data );

		testUtils.overrideMwConfig( config );

		var expectedHtml = testUtils.getThreadContainer( expected ).innerHTML;

		var container = testUtils.getThreadContainer( dom );
		var title = mw.Title.newFromText( caseItem.title );
		var threadItemSet = new Parser( data ).parse( container, title );
		var comments = threadItemSet.getCommentItems();

		// Add a reply link to every comment.
		comments.forEach( function ( comment ) {
			var linkNode = document.createElement( 'a' );
			linkNode.textContent = 'Reply';
			linkNode.href = '#';
			modifier.addReplyLink( comment, linkNode );
		} );

		// Uncomment this to get updated content for the "reply HTML" files, for copy/paste:
		// console.log( container.innerHTML );

		var actualHtml = container.innerHTML;

		assert.strictEqual(
			actualHtml,
			expectedHtml,
			caseItem.name
		);
	} );
} );

QUnit.test( '#unwrapList', function ( assert ) {
	var cases = require( '../cases/unwrap.json' );

	cases.forEach( function ( caseItem ) {
		var container = document.createElement( 'div' );

		container.innerHTML = caseItem.html;
		modifier.unwrapList( container.childNodes[ caseItem.index || 0 ] );

		assert.strictEqual(
			container.innerHTML,
			caseItem.expected,
			caseItem.name
		);
	} );
} );

QUnit.test( 'sanitizeWikitextLinebreaks', function ( assert ) {
	var cases = require( '../cases/sanitize-wikitext-linebreaks.json' );

	cases.forEach( function ( caseItem ) {
		assert.strictEqual(
			modifier.sanitizeWikitextLinebreaks( caseItem.wikitext ),
			caseItem.expected,
			caseItem.msg
		);
	} );
} );

// TODO:
// * addSiblingListItem
