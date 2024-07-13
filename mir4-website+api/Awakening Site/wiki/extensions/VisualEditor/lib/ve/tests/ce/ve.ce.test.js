/*!
 * VisualEditor ContentEditable tests.
 *
 * @copyright See AUTHORS.txt
 */

QUnit.module( 've.ce' );

/* Tests */

QUnit.test( 'getDomHash/getDomText (with ve.dm.Converter)', function ( assert ) {
	var cases = [
		{
			msg: 'Nested annotations',
			html: '<p><span>a<b><a href="#">b</a></b><span> </span><i>c</i>d</span></p>',
			hash: '<DIV><P><SPAN>#<B><A>#</A></B><SPAN>#</SPAN><I>#</I>#</SPAN></P></DIV>',
			text: 'ab cd'
		},
		{
			msg: 'Inline nodes produce snowmen',
			html: '<p>Foo <span rel="ve:Alien">Alien</span> bar</p>',
			hash: '<DIV><P>#<SPAN>#</SPAN>#</P></DIV>',
			text: 'Foo ☃☃ bar'
		},
		{
			msg: 'About grouped aliens produce one pair of snowmen',
			html: ve.dm.example.singleLine`
				<p>
					Foo
					 <span about="g1" rel="ve:Alien">Alien</span>
					<span about="g1" rel="ve:Alien">Aliens</span>
					<span about="g1" rel="ve:Alien">Alien³</span>
					 bar
				</p>
			`,
			hash: '<DIV><P>#<SPAN>#</SPAN><SPAN>#</SPAN><SPAN>#</SPAN>#</P></DIV>',
			text: 'Foo ☃☃ bar'
		},
		{
			msg: 'Block slugs are ignored',
			html: '<table><tr><td>Foo</td></tr></table>',
			hash: '<DIV><TABLE><TBODY><TR><TD><P>#</P></TD></TR></TBODY></TABLE></DIV>',
			text: 'Foo'
		}
	];

	cases.forEach( function ( caseItem ) {
		var view = ve.test.utils.createSurfaceViewFromHtml( caseItem.html );
		var documentView = view.getDocument();
		assert.strictEqual( ve.ce.getDomHash( documentView.getDocumentNode().$element[ 0 ] ), caseItem.hash, 'getDomHash: ' + caseItem.msg );
		assert.strictEqual( ve.ce.getDomText( documentView.getDocumentNode().$element[ 0 ] ), caseItem.text, 'getDomText: ' + caseItem.msg );
		view.destroy();
	} );
} );

QUnit.test( 'getDomHash/getDomText (without ve.dm.Converter)', function ( assert ) {
	var cases = [
		{
			msg: 'Block slugs are ignored',
			html: '<div><p>foo</p><div class="ve-ce-branchNode-blockSlug">x</div><p>bar</p></div>',
			hash: '<DIV><P>#</P><P>#</P></DIV>',
			text: 'foobar'
		},
		{
			msg: 'Cursor holders are ignored',
			html: '<div><p>foo</p><div class="ve-ce-cursorHolder">x</div><p>bar</p></div>',
			hash: '<DIV><P>#</P><P>#</P></DIV>',
			text: 'foobar'
		}
	];

	var view = ve.test.utils.createSurfaceViewFromHtml( '' );
	var element = view.getDocument().getDocumentNode().$element[ 0 ];

	cases.forEach( function ( caseItem ) {
		element.innerHTML = caseItem.html;
		assert.strictEqual( ve.ce.getDomHash( element.firstChild ), caseItem.hash, 'getDomHash: ' + caseItem.msg );
		assert.strictEqual( ve.ce.getDomText( element.firstChild ), caseItem.text, 'getDomText: ' + caseItem.msg );
	} );

	view.destroy();
} );

QUnit.test( 'getOffset', function ( assert ) {
	var cases = [
		{
			msg: 'Empty paragraph',
			html: '<p></p>',
			// CE HTML summary:
			// <p><span [inlineSlug]><img /></span></p>
			// Linmod:
			// [<p>, </p>]
			expected: [
				0,
				1, 1, 1, 1, 1,
				2
			]
		},
		{
			msg: 'Annotations',
			html: '<p><i><b>Foo</b></i></p>',
			// Linmod:
			// [<p>, F, o, o, </p>]
			expected: [
				0,
				1, 1, 1, 1,
				2,
				3,
				4, 4, 4, 4,
				5
			]
		},
		{
			msg: 'Multiple siblings',
			html: '<p><b><i>Foo</i><s><u>Bar</u><span>Baz</span></s></b></p>',
			// Linmod:
			// [<p>, F, o, o, B, a, r, B, a, z, </p>]
			expected: [
				0,
				1, 1, 1, 1,
				2,
				3,
				4, 4, 4, 4, 4, 4,
				5,
				6,
				7, 7, 7, 7, 7,
				8,
				9,
				10, 10, 10, 10, 10,
				11
			]
		},
		{
			msg: 'Annotated alien',
			html: '<p>Foo<b><span rel="ve:Alien">Bar</span></b>Baz</p>',
			// CE HTML summary;
			// <p>Foo<b><span [alien]>Bar</span></b>Baz</p>
			// Linmod:
			// [<p>, F, o, o, <alineinline>, </alineinline>, B, a, z, </p>]
			expected: [
				0,
				1, 1,
				2,
				3,
				4, 4, 4, 4, 4, 4, 4, 4, 4,
				6, 6, 6,
				7,
				8,
				9, 9,
				10
			]
		},
		{
			msg: 'Block alien',
			html: '<p>Foo</p><div rel="ve:Alien">Bar</div><p>Baz</p>',
			// Linmod:
			// [<p>, F, o, o, </p>, <alienBlock>, </alienBlock>, <p>, B, a, z, </p>]
			expected: [
				0,
				1, 1,
				2,
				3,
				4, 4,
				5,
				6, 6, 6, 6, 6, 6,
				7,
				8, 8,
				9,
				10,
				11, 11,
				12
			]
		},
		{
			msg: 'Table with block slugs',
			html: '<table><tr><td>Foo</td></tr></table>',
			// CE HTML summary;
			// <div [slug]>(ignored)</div>
			// <table><tbody><tr><td>
			//  <p>Foo</p>
			// </td></tr></tbody></table>
			// <div [slug]>(ignored)</div>
			// Linmod:
			// [<table>, <tbody>, <tr>, <td>, <p>, F, o, o, </p>, </td>, </tr>, </tbody>, </table>]
			expected: [
				0, 0,
				1,
				2,
				3,
				4,
				5, 5,
				6,
				7,
				8, 8,
				9,
				10,
				11,
				12,
				13, 13
			]
		},
		{
			msg: 'Paragraph with inline slugs',
			html: '<p><span rel="ve:Alien">Foo</span><span rel="ve:Alien">Bar</span><br></p>',
			// CE HTML summary:
			// <p><span [inlineSlug]><img /></span><span [alien]>Foo</span>
			// <span [inlineSlug]><img /></span><span [alien]>Bar</span>
			// <span [inlineSlug]><img /></span><br></br><span [inlineSlug]><img /></span></p>
			// Linmod:
			// [<p>, <alineinline>, </alineinline>, <alineinline>, </alineinline>, <break>, </break>, </p>]
			expected: [
				0,
				1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1,
				3, 3, 3, 3, 3, 3, 3, 3, 3, 3, 3,
				5, 5, 5, 5, 5,
				6,
				7, 7, 7, 7, 7,
				8
			]
		}
	];

	function testOffsets( parent, testCase, expectedIndex ) {
		var i;
		switch ( parent.nodeType ) {
			case Node.ELEMENT_NODE:
				for ( i = 0; i <= parent.childNodes.length; i++ ) {
					expectedIndex++;
					assert.strictEqual(
						ve.ce.getOffset( parent, i ),
						testCase.expected[ expectedIndex ],
						testCase.msg + ': offset ' + i + ' in <' + parent.nodeName.toLowerCase() + '>'
					);
					if ( parent.childNodes[ i ] && !$( parent.childNodes[ i ] ).hasClass( 've-ce-branchNode-blockSlug' ) ) {
						expectedIndex = testOffsets( parent.childNodes[ i ], testCase, expectedIndex );
					}
				}
				break;
			case Node.TEXT_NODE:
				for ( i = 0; i <= parent.data.length; i++ ) {
					expectedIndex++;
					assert.strictEqual(
						ve.ce.getOffset( parent, i ),
						testCase.expected[ expectedIndex ],
						testCase.msg + ': offset ' + i + ' in "' + parent.data + '"'
					);
				}
				break;
		}
		return expectedIndex;
	}

	cases.forEach( function ( caseItem ) {
		var view = ve.test.utils.createSurfaceViewFromHtml( caseItem.html ),
			documentView = view.getDocument();

		testOffsets( documentView.getDocumentNode().$element[ 0 ], caseItem, -1 );
		view.destroy();
	} );
} );

// TODO: ve.ce.getOffsetOfSlug

QUnit.test( 'isShortcutKey', function ( assert ) {
	assert.strictEqual( ve.ce.isShortcutKey( { ctrlKey: true } ), true, 'ctrlKey' );
	assert.strictEqual( ve.ce.isShortcutKey( { metaKey: true } ), true, 'metaKey' );
	assert.strictEqual( ve.ce.isShortcutKey( {} ), false, 'Not set' );
} );

QUnit.test( 'nextCursorOffset', function ( assert ) {
	function dumpnode( node ) {
		if ( node.nodeType === Node.TEXT_NODE ) {
			return '#' + node.data;
		} else {
			return node.nodeName.toLowerCase();
		}
	}

	var cases = [
		{ html: '<p>foo<img>bar</p>', expected: [ '#bar', 0 ] },
		{ html: '<p>foo<b><i><img></i></b></p>', expected: [ 'i', 1 ] },
		{ html: '<p><b>foo</b><img>bar</p>', expected: [ '#bar', 0 ] },
		{ html: '<p>foo<b><i><img></i></b></p>', expected: [ 'i', 1 ] },
		{ html: '<p><b>foo</b><img></p>', expected: [ 'p', 2 ] },
		{ html: '<p><img><b>foo</b></p>', expected: [ 'p', 1 ] },
		{ html: '<p><b>foo</b><img><b>bar</b></p>', expected: [ 'p', 2 ] }
	];

	var elt = ve.createDocumentFromHtml( '' ).createElement( 'div' );
	cases.forEach( function ( caseItem ) {
		elt.innerHTML = caseItem.html;
		var img = elt.getElementsByTagName( 'img' )[ 0 ];
		var nextOffset = ve.ce.nextCursorOffset( img );
		assert.deepEqual(
			[ dumpnode( nextOffset.node ), nextOffset.offset ],
			caseItem.expected,
			caseItem.html
		);
	} );
} );

QUnit.test( 'resolveTestOffset', function ( assert ) {
	var cases = [
		[ 'o', 'k' ],
		// TODO: doesn't handle tags correctly yet!
		// ['w', '<b>', 'x', 'y', '</b>', 'z'],
		// ['q', '<b>', 'r', '<b>', 's', 't', '</b>', 'u', '</b>', 'v']
		[ 'h', 'e', 'l', 'l', 'o' ]
	];

	var dom = ve.createDocumentFromHtml( '' );
	var elt = dom.createElement( 'div' );
	cases.forEach( function ( caseItem ) {
		elt.innerHTML = caseItem.join( '' );
		for ( var j = 0, jlen = caseItem.length; j < jlen + 1; j++ ) {
			var testOffset = new ve.ce.TestOffset( 'forward', j );
			var pre = caseItem.slice( 0, j ).join( '' );
			var post = caseItem.slice( j ).join( '' );
			assert.strictEqual(
				testOffset.resolve( elt ).slice,
				pre + '|' + post
			);
			testOffset = new ve.ce.TestOffset( 'backward', j );
			pre = caseItem.slice( 0, jlen - j ).join( '' );
			post = caseItem.slice( jlen - j ).join( '' );
			assert.strictEqual(
				testOffset.resolve( elt ).slice,
				pre + '|' + post
			);
		}
	} );
} );

QUnit.test( 'fakeImes', function ( assert ) {
	var testsFailAt;
	if ( Function.prototype.bind === undefined ) {
		// Assume we are in PhantomJS (which breaks different tests than a real browser)
		testsFailAt = ve.ce.imetestsPhantomFailAt;
	} else {
		testsFailAt = ve.ce.imetestsFailAt;
	}

	// TODO: make this function actually affect the events triggered
	var fakePreventDefault = function () {};
	var fakeIsPreventDefault = function () {
		return false;
	};

	ve.ce.imetests.forEach( function ( caseItem ) {
		var testName = caseItem[ 0 ];
		var failAt = testsFailAt[ testName ] || null;
		var testActions = caseItem[ 1 ];
		var foundEndLoop = false;
		// First element is the testInfo
		var testInfo = testActions[ 0 ];
		var view = ve.test.utils.createSurfaceViewFromHtml( testInfo.startDom || '' );
		view.getModel().setLinearSelection( new ve.Range( 1 ) );
		var testRunner = new ve.ce.TestRunner( view );
		// Start at 1 to omit the testInfo
		var died = false;
		for ( var j = 1, jlen = testActions.length; j < jlen; j++ ) {
			var action = testActions[ j ].action;
			var args = testActions[ j ].args;
			var seq = testActions[ j ].seq;
			if ( !died ) {
				if ( action === 'sendEvent' ) {
					// TODO: make preventDefault work
					args[ 1 ].preventDefault = fakePreventDefault;
					args[ 1 ].isDefaultPrevented = fakeIsPreventDefault;
				}
				try {
					testRunner[ action ].apply( testRunner, args );
				} catch ( ex ) {
					died = ex;
				}
			}
			// Check synchronization at the end of each event loop
			if ( action === 'endLoop' ) {
				foundEndLoop = true;
				if ( failAt === null || seq < failAt ) {
					// If no expected failure yet, test the code ran and the
					// model and CE surface are in sync
					if ( died ) {
						testRunner.failDied( assert, testName, seq, died );
					} else {
						testRunner.testEqual( assert, testName, seq );
					}
				} else if ( seq === failAt ) {
					// If *at* expected failure, check something failed
					if ( died ) {
						testRunner.ok( assert, testName + ' (fail expected)', seq );
					} else {
						testRunner.testNotEqual( assert, testName + ' (fail expected)', seq );
					}
				} else {
					// If *after* expected failure, allow anything
					testRunner.ok( assert, testName, seq );
				}
			}
		}
		// Test that there is at least one endLoop
		assert.strictEqual( foundEndLoop, true, testName + ' found at least one endLoop' );
		view.destroy();
	} );
} );

QUnit.test( 'isAfterAnnotationBoundary', function ( assert ) {
	var div = ve.createDocumentFromHtml( '' ).createElement( 'div' );

	div.innerHTML = 'Q<b>R<i>S</i>T</b><s>U</s>V<u>W</u>';

	// In the following tests, the 'path' properties are a list of descent offsets to find a
	// particular descendant node from the top-level div. E.g. a path of [ 5, 7 ] refers to
	// the node div.childNodes[ 5 ].childNodes[ 7 ] .
	var cases = [
		{ path: [], offset: 0, expected: false },
		{ path: [ 0 ], offset: 0, expected: false },
		{ path: [ 0 ], offset: 1, expected: false },
		{ path: [], offset: 1, expected: false },
		{ path: [ 1 ], offset: 0, expected: true },
		{ path: [ 1, 0 ], offset: 0, expected: true },
		{ path: [ 1, 0 ], offset: 1, expected: false },
		{ path: [ 1 ], offset: 1, expected: false },
		{ path: [ 1, 1 ], offset: 0, expected: true },
		{ path: [ 1, 1, 0 ], offset: 0, expected: true },
		{ path: [ 1, 1, 0 ], offset: 1, expected: false },
		{ path: [ 1, 1 ], offset: 1, expected: false },
		{ path: [ 1 ], offset: 2, expected: true },
		{ path: [ 1, 2 ], offset: 0, expected: true },
		{ path: [ 1, 2 ], offset: 1, expected: false },
		{ path: [ 1 ], offset: 3, expected: false },
		// The next position *is* a after a boundary (though also before one)
		{ path: [], offset: 2, expected: true },
		{ path: [ 2 ], offset: 0, expected: true },
		{ path: [ 2, 0 ], offset: 0, expected: true },
		{ path: [ 2, 0 ], offset: 1, expected: false },
		{ path: [ 2 ], offset: 1, expected: false },
		{ path: [], offset: 3, expected: true },
		{ path: [ 3 ], offset: 0, expected: true },
		{ path: [ 3 ], offset: 1, expected: false },
		{ path: [], offset: 4, expected: false },
		{ path: [ 4 ], offset: 0, expected: true },
		{ path: [ 4, 0 ], offset: 0, expected: true },
		{ path: [ 4, 0 ], offset: 1, expected: false },
		{ path: [ 4 ], offset: 1, expected: false },
		{ path: [], offset: 5, expected: true }
	];

	cases.forEach( function ( caseItem ) {
		var node = div;
		for ( var j = 0, jLen = caseItem.path.length; j < jLen; j++ ) {
			node = node.childNodes[ caseItem.path[ j ] ];
		}
		assert.strictEqual(
			ve.ce.isAfterAnnotationBoundary( node, caseItem.offset ),
			caseItem.expected,
			`node=${ caseItem.path.join( ',' ) } offset=${ caseItem.offset }`
		);
	} );
} );
