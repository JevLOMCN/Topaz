/*!
 * VisualEditor Base method tests.
 *
 * @copyright See AUTHORS.txt
 */

QUnit.module( 've' );

/* Tests */

// ve.getProp: Tested upstream (OOjs)

// ve.setProp: Tested upstream (OOjs)

// ve.cloneObject: Tested upstream (OOjs)

// ve.getObjectValues: Tested upstream (OOjs)

// ve.compare: Tested upstream (OOjs)

// ve.copy: Tested upstream (OOjs)

// ve.isPlainObject: Tested upstream (jQuery)

// ve.isEmptyObject: Tested upstream (jQuery)

// ve.extendObject: Tested upstream (jQuery)

QUnit.test( 'compareClassLists', function ( assert ) {
	var cases = [
		{
			args: [ '', '' ],
			expected: true
		},
		{
			args: [ '', [] ],
			expected: true
		},
		{
			args: [ [], [] ],
			expected: true
		},
		{
			args: [ '', [ '' ] ],
			expected: true
		},
		{
			args: [ [], [ '' ] ],
			expected: true
		},
		{
			args: [ 'foo', '' ],
			expected: false
		},
		{
			args: [ 'foo', 'foo' ],
			expected: true
		},
		{
			args: [ 'foo', 'bar' ],
			expected: false
		},
		{
			args: [ 'foo', 'foo bar' ],
			expected: false
		},
		{
			args: [ 'foo', [ 'foo' ] ],
			expected: true
		},
		{
			args: [ [ 'foo' ], 'bar' ],
			expected: false
		},
		{
			args: [ 'foo', [ 'foo', 'bar' ] ],
			expected: false
		},
		{
			args: [ 'foo', [ 'foo', 'foo' ] ],
			expected: true
		},
		{
			args: [ [ 'foo' ], 'foo foo' ],
			expected: true
		},
		{
			args: [ 'foo bar foo', 'foo foo' ],
			expected: false
		}
	];

	cases.forEach( function ( caseItem ) {
		assert.strictEqual( ve.compareClassLists.apply( ve, caseItem.args ), caseItem.expected );
	} );
} );

QUnit.test( 'isInstanceOfAny', function ( assert ) {
	function Foo() {}
	OO.initClass( Foo );

	function Bar() {}
	OO.initClass( Bar );

	function SpecialFoo() {}
	OO.inheritClass( SpecialFoo, Foo );

	function VerySpecialFoo() {}
	OO.inheritClass( VerySpecialFoo, SpecialFoo );

	assert.strictEqual(
		ve.isInstanceOfAny( new Foo(), [ Foo ] ),
		true,
		'Foo is an instance of Foo'
	);

	assert.strictEqual(
		ve.isInstanceOfAny( new SpecialFoo(), [ Foo ] ),
		true,
		'SpecialFoo is an instance of Foo'
	);

	assert.strictEqual(
		ve.isInstanceOfAny( new SpecialFoo(), [ Bar ] ),
		false,
		'SpecialFoo is not an instance of Bar'
	);

	assert.strictEqual(
		ve.isInstanceOfAny( new SpecialFoo(), [ Bar, Foo ] ),
		true,
		'SpecialFoo is an instance of Bar or Foo'
	);

	assert.strictEqual(
		ve.isInstanceOfAny( new VerySpecialFoo(), [ Bar, Foo ] ),
		true,
		'VerySpecialFoo is an instance of Bar or Foo'
	);

	assert.strictEqual(
		ve.isInstanceOfAny( new VerySpecialFoo(), [ Foo, SpecialFoo ] ),
		true,
		'VerySpecialFoo is an instance of Foo or SpecialFoo'
	);

	assert.strictEqual(
		ve.isInstanceOfAny( new VerySpecialFoo(), [] ),
		false,
		'VerySpecialFoo is not an instance of nothing'
	);
} );

QUnit.test( 'getDomAttributes', function ( assert ) {
	assert.deepEqual(
		ve.getDomAttributes( $.parseHTML( '<div string="foo" empty number="0"></div>' )[ 0 ] ),
		{ string: 'foo', empty: '', number: '0' },
		'getDomAttributes() returns object with correct attributes'
	);
} );

QUnit.test( 'setDomAttributes', function ( assert ) {
	var sample = $.parseHTML( '<div foo="one" bar="two" baz="three"></div>' )[ 0 ];

	var target = {};
	ve.setDomAttributes( target, { add: 'foo' } );
	assert.deepEqual( target, {}, 'ignore incompatible target object' );

	target = document.createElement( 'div' );
	ve.setDomAttributes( target, { string: 'foo', empty: '', number: 0 } );
	assert.deepEqual(
		ve.getDomAttributes( target ),
		{ string: 'foo', empty: '', number: '0' },
		'add attributes'
	);

	target = sample.cloneNode();
	ve.setDomAttributes( target, { foo: null, bar: 'update', baz: undefined, add: 'yay' } );
	assert.deepEqual(
		ve.getDomAttributes( target ),
		{ bar: 'update', add: 'yay' },
		'add, update, and remove attributes'
	);

	target = sample.cloneNode();
	ve.setDomAttributes( target, { onclick: 'alert(1);', foo: 'update', add: 'whee' }, [ 'foo', 'add' ] );
	assert.strictEqual( target.hasAttribute( 'onclick' ), false, 'allow list affects creating attributes' );
	assert.deepEqual(
		ve.getDomAttributes( target ),
		{ foo: 'update', bar: 'two', baz: 'three', add: 'whee' },
		'allow list does not affect pre-existing attributes'
	);

	target = document.createElement( 'div' );
	ve.setDomAttributes( target, { Foo: 'add', Bar: 'add' }, [ 'bar' ] );
	assert.deepEqual(
		ve.getDomAttributes( target ),
		{ bar: 'add' },
		'allow list is case-insensitive'
	);

	target = sample.cloneNode();
	ve.setDomAttributes( target, { foo: 'update', bar: null }, [ 'bar', 'baz' ] );
	assert.propEqual(
		ve.getDomAttributes( target ),
		{ foo: 'one', baz: 'three' },
		'allow list affects removal/updating of attributes'
	);
} );

QUnit.test( 'sparseSplice', function ( assert ) {
	// Convert a sparse array of primitives to an array of strings, with '' for holes.
	// This is needed because QUnit.equiv treats holes as equivalent to undefined.
	function mapToString( flatArray ) {
		var strings = [];
		for ( var j = 0, jLen = flatArray.length; j < jLen; j++ ) {
			strings.push( Object.prototype.hasOwnProperty.call( flatArray, j ) ? String( flatArray[ j ] ) : '' );
		}
		return strings;
	}
	function runTest( arr, offset, remove, data, expectedReturn, expectedArray, msg ) {
		var observedReturn,
			testArr = arr.slice();

		observedReturn = ve.sparseSplice( testArr, offset, remove, data );
		assert.deepEqual(
			mapToString( observedReturn ),
			mapToString( expectedReturn ),
			msg + ': return'
		);
		assert.deepEqual(
			mapToString( testArr ),
			mapToString( expectedArray ),
			msg + ': modification'
		);
	}
	/* eslint-disable no-sparse-arrays */
	var scratch = [ 4, , 5, , 6 ];
	var cases = [
		// arr, offset, remove, data, expectedReturn, expectedArray, msg
		[ [], 0, 0, [ , 3 ], [], [ , 3 ], 'insert empty, leading hole' ],
		[ [], 0, 0, [ 1, , 3 ], [], [ 1, , 3 ], 'insert empty, middle hole' ],
		// Note: the first trailing comma does not create a hole
		[ [], 0, 0, [ 1, , ], [], [ 1, , ], 'insert empty, trailing hole' ],
		[ [ 4, , 5 ], 0, 0, [ 1, , 3 ], [], [ 1, , 3, 4, , 5 ], 'insert start' ],
		[ [ 0, , 4 ], 1, 0, [ 1, , 3 ], [], [ 0, 1, , 3, , 4 ], 'insert mid' ],
		[ [ 0, , 4 ], 3, 0, [ 1, , 3 ], [], [ 0, , 4, 1, , 3 ], 'insert end' ],

		[ [ 4, , 5, , 6 ], 0, 4, [ 1, , 3 ], [ 4, , 5, , ], [ 1, , 3, 6 ], 'diff<0 start' ],
		[ [ 4, , , 5, , 6 ], 1, 4, [ 1, , 3 ], [ , , 5, , ], [ 4, 1, , 3, 6 ], 'diff<0 mid' ],
		[ [ 4, , 5, , 6 ], 1, 4, [ 1, , 3 ], [ , 5, , 6 ], [ 4, 1, , 3 ], 'diff<0 end' ],

		[ [ 4, , 5, , 6 ], 0, 2, [ 1, , 3 ], [ 4, , ], [ 1, , 3, 5, , 6 ], 'diff>0 start' ],
		[ [ 4, , 5, , 6 ], 1, 2, [ 1, , 3 ], [ , 5 ], [ 4, 1, , 3, , 6 ], 'diff>0 mid' ],
		[ [ 4, , 5, , 6 ], 3, 2, [ 1, , 3 ], [ , 6 ], [ 4, , 5, 1, , 3 ], 'diff>0 end' ],

		[ [ 4, , 5, , 6 ], 0, 3, [ 1, , 3 ], [ 4, , 5 ], [ 1, , 3, , 6 ], 'diff=0 start' ],
		[ [ 4, , 5, , 6 ], 1, 3, [ 1, , 3 ], [ , 5, , ], [ 4, 1, , 3, 6 ], 'diff=0 mid' ],
		[ [ 4, , 5, , 6 ], 2, 3, [ 1, , 3 ], [ 5, , 6 ], [ 4, , 1, , 3 ], 'diff=0 end' ],
		[ scratch, 0, 0, scratch, [], [ 4, , 5, , 6, 4, , 5, , 6 ], 'reference-identical arr and data' ]
	];
	/* eslint-enable no-sparse-arrays */

	assert.notDeepEqual(
		// eslint-disable-next-line no-sparse-arrays
		mapToString( [ 1, , ] ),
		mapToString( [ 1, undefined ] ),
		'holes look different to undefined'
	);
	cases.forEach( function ( caseItem ) {
		runTest.apply( null, caseItem );
	} );
} );

QUnit.test( 'batchSplice', function ( assert ) {
	var actual = [ 'a', 'b', 'c', 'd', 'e' ],
		expected = actual.slice( 0 );

	var actualRet = ve.batchSplice( actual, 1, 1, [] );
	var expectedRet = expected.splice( 1, 1 );
	assert.deepEqual( expectedRet, actualRet, 'Removing 1 element (return value)' );
	assert.deepEqual( expected, actual, 'Removing 1 element (array)' );

	actualRet = ve.batchSplice( actual, 3, 2, [ 'w', 'x', 'y', 'z' ] );
	expectedRet = expected.splice( 3, 2, 'w', 'x', 'y', 'z' );
	assert.deepEqual( expectedRet, actualRet, 'Replacing 2 elements with 4 elements (return value)' );
	assert.deepEqual( expected, actual, 'Replacing 2 elements with 4 elements (array)' );

	actualRet = ve.batchSplice( actual, 0, 0, [ 'f', 'o', 'o' ] );
	expectedRet = expected.splice( 0, 0, 'f', 'o', 'o' );
	assert.deepEqual( expectedRet, actualRet, 'Inserting 3 elements (return value)' );
	assert.deepEqual( expected, actual, 'Inserting 3 elements (array)' );

	var bigArr = [];
	for ( var i = 0; i < 2100; i++ ) {
		bigArr[ i ] = i;
	}
	actualRet = ve.batchSplice( actual, 2, 3, bigArr );
	expectedRet = expected.splice.apply( expected, [ 2, 3 ].concat( bigArr.slice( 0, 1050 ) ) );
	expected.splice.apply( expected, [ 1052, 0 ].concat( bigArr.slice( 1050 ) ) );
	assert.deepEqual( expectedRet, actualRet, 'Replacing 3 elements with 2100 elements (return value)' );
	assert.deepEqual( expected, actual, 'Replacing 3 elements with 2100 elements (array)' );
} );

QUnit.test( 'batchPush', function ( assert ) {

	var actual = [];
	var actualRet = ve.batchPush( actual, [ 1, 2, 3 ] );
	assert.strictEqual( actualRet, 3, 'Adding to an empty array: return' );
	assert.deepEqual( actual, [ 1, 2, 3 ], 'Adding to an empty array: value' );

	actual = [ 1 ];
	actualRet = ve.batchPush( actual, [ 1, 2, 3 ] );
	assert.strictEqual( actualRet, 4, 'Adding to a non-empty array: return' );
	assert.deepEqual( actual, [ 1, 1, 2, 3 ], 'Adding to a non-empty array: value' );

	// batchPush takes a separate codepath for really long arrays, make sure it's behaving similarly:

	var bigArr = [];
	for ( var i = 0; i < 2100; i++ ) {
		bigArr[ i ] = i;
	}

	actual = [ 'a' ];
	actualRet = ve.batchPush( actual, bigArr );
	assert.strictEqual( actualRet, 2101, 'Adding a huge array: return' );
	assert.strictEqual( actual[ 0 ], 'a', 'Adding a huge array: first value' );
	assert.strictEqual( actual[ actual.length - 1 ], 2099, 'Adding a huge array: last value' );
} );

QUnit.test( 'insertIntoArray', function ( assert ) {
	var target = [ 'a', 'b', 'c' ];
	ve.insertIntoArray( target, 0, [ 'x', 'y' ] );
	assert.deepEqual( target, [ 'x', 'y', 'a', 'b', 'c' ], 'insert at start' );

	target = [ 'a', 'b', 'c' ];
	ve.insertIntoArray( target, 2, [ 'x', 'y' ] );
	assert.deepEqual( target, [ 'a', 'b', 'x', 'y', 'c' ], 'insert into the middle' );

	target = [ 'a', 'b', 'c' ];
	ve.insertIntoArray( target, 10, [ 'x', 'y' ] );
	assert.deepEqual( target, [ 'a', 'b', 'c', 'x', 'y' ], 'insert beyond end' );
} );

QUnit.test( 'escapeHtml', function ( assert ) {
	assert.strictEqual( ve.escapeHtml( ' "script\' <foo & bar> ' ), ' &quot;script&#039; &lt;foo &amp; bar&gt; ' );
} );

QUnit.test( 'addHeadTag', function ( assert ) {
	var cases = [
		{
			msg: 'no wrapper',
			html: '<p>foo</p>',
			expected: '<head><meta foo/></head><p>foo</p>'
		},
		{
			msg: 'body only',
			html: '<body><p>foo</p></body>',
			expected: '<head><meta foo/></head><body><p>foo</p></body>'
		},
		{
			msg: 'head & body',
			html: '<head></head><body><p>foo</p></body>',
			expected: '<head><meta foo/></head><body><p>foo</p></body>'
		},
		{
			msg: 'html & body',
			html: '<html><body><p>foo</p></body></html>',
			expected: '<html><head><meta foo/></head><body><p>foo</p></body></html>'
		},
		{
			msg: 'html, head & body',
			html: '<html><head></head><body><p>foo</p></body></html>',
			expected: '<html><head><meta foo/></head><body><p>foo</p></body></html>'
		}
	];

	cases.forEach( function ( caseItem ) {
		assert.strictEqual( ve.addHeadTag( caseItem.html, '<meta foo/>' ), caseItem.expected, caseItem.msg );
	} );
} );

QUnit.test( 'createDocumentFromHtml', function ( assert ) {
	var cases = [
		{
			msg: 'simple document with doctype, head and body',
			html: '<!doctype html><html lang="en"><head><title>Foo</title></head><body><p>Bar</p></body></html>',
			head: '<title>Foo</title>',
			body: '<p>Bar</p>',
			htmlAttributes: {
				lang: 'en'
			}
		},
		{
			msg: 'simple document without doctype',
			html: '<html lang="en"><head><title>Foo</title></head><body><p>Bar</p></body></html>',
			head: '<title>Foo</title>',
			body: '<p>Bar</p>',
			htmlAttributes: {
				lang: 'en'
			}
		},
		{
			msg: 'document with missing closing tags and missing <html> tag',
			html: '<!doctype html><head><title>Foo</title><base href="yay"><body><p>Bar<b>Baz',
			head: '<title>Foo</title><base href="yay" />',
			body: '<p>Bar<b>Baz</b></p>',
			htmlAttributes: {}
		},
		{
			msg: 'empty string results in empty document',
			html: '',
			head: '',
			body: '',
			htmlAttributes: {}
		},
		{
			msg: 'meta tag stays in body',
			html: '<meta/><p>foo</p>',
			head: '',
			body: '<meta/><p>foo</p>',
			htmlAttributes: {}
		},
		{
			msg: 'body wrapping not broken by custom tag',
			html: '<meta/><p>foo<bodything/></p>',
			head: '',
			body: '<meta/><p>foo<bodything/></p>',
			htmlAttributes: {}
		}
	];

	cases.forEach( function ( caseItem ) {
		var doc = ve.createDocumentFromHtml( caseItem.html, true );
		var attributes = $( 'html', doc ).get( 0 ).attributes;
		var attributesObject = {};
		for ( var i = 0; i < attributes.length; i++ ) {
			attributesObject[ attributes[ i ].name ] = attributes[ i ].value;
		}
		var expectedHead = $( '<head>' ).html( caseItem.head ).get( 0 );
		var expectedBody = $( '<body>' ).html( caseItem.body ).get( 0 );
		assert.equalDomElement( $( 'head', doc ).get( 0 ), expectedHead, caseItem.msg + ' (head)' );
		assert.equalDomElement( $( 'body', doc ).get( 0 ), expectedBody, caseItem.msg + ' (body)' );
		assert.deepEqual( attributesObject, caseItem.htmlAttributes, caseItem.msg + ' (html attributes)' );
	} );
} );

QUnit.test( 'resolveUrl', function ( assert ) {
	var cases = [
		{
			base: 'http://example.com',
			href: 'foo',
			resolved: 'http://example.com/foo',
			msg: 'Simple href with domain as base'
		},
		{
			base: 'http://example.com/bar',
			href: 'foo',
			resolved: 'http://example.com/foo',
			msg: 'Simple href with page as base'
		},
		{
			base: 'http://example.com/bar/',
			href: 'foo',
			resolved: 'http://example.com/bar/foo',
			msg: 'Simple href with directory as base'
		},
		{
			base: 'http://example.com/bar/',
			href: './foo',
			resolved: 'http://example.com/bar/foo',
			msg: './ in href'
		},
		{
			base: 'http://example.com/bar/',
			href: '../foo',
			resolved: 'http://example.com/foo',
			msg: '../ in href'
		},
		{
			base: 'http://example.com/bar/',
			href: '/foo',
			resolved: 'http://example.com/foo',
			msg: 'href starting with /'
		},
		{
			base: 'http://example.com/bar/',
			href: '//example.org/foo',
			resolved: 'http://example.org/foo',
			msg: 'protocol-relative href'
		},
		{
			base: 'http://example.com/bar/',
			href: 'https://example.org/foo',
			resolved: 'https://example.org/foo',
			msg: 'href with protocol'
		}
	];

	cases.forEach( function ( caseItem ) {
		var doc = ve.createDocumentFromHtml( '' );
		doc.head.appendChild( $( '<base>', doc ).attr( 'href', caseItem.base )[ 0 ] );
		assert.strictEqual( ve.resolveUrl( caseItem.href, doc ), caseItem.resolved, caseItem.msg );
	} );
} );

QUnit.test( 'resolveAttributes', function ( assert ) {
	var cases = [
		{
			base: 'http://example.com',
			html: '<div><a href="foo">foo</a></div><a href="bar">bar</a><img src="baz">',
			resolved: '<div><a href="http://example.com/foo">foo</a></div><a href="http://example.com/bar">bar</a><img src="http://example.com/baz">',
			msg: 'href and src resolved'
		},
		{
			base: 'http://example.com',
			html: '<a href="foo">foo</a>',
			resolved: '<a href="http://example.com/foo">foo</a>',
			msg: 'href resolved on self (unwrapped)'
		}
	];

	cases.forEach( function ( caseItem ) {
		var doc = ve.createDocumentFromHtml( '' );
		doc.head.appendChild( $( '<base>', doc ).attr( 'href', caseItem.base )[ 0 ] );
		var div = document.createElement( 'div' );
		div.innerHTML = caseItem.html;
		ve.resolveAttributes( div.childNodes, doc, ve.dm.Converter.static.computedAttributes );
		assert.strictEqual(
			div.innerHTML,
			caseItem.resolved,
			caseItem.msg
		);
	} );
} );

QUnit.test( 'fixBase', function ( assert ) {
	var cases = [
		{
			targetBase: '//example.org/foo',
			sourceBase: 'https://example.com',
			fixedBase: 'https://example.org/foo',
			msg: 'Protocol-relative base is made absolute'
		},
		{
			targetBase: 'http://example.org/foo',
			sourceBase: 'https://example.com',
			fixedBase: 'http://example.org/foo',
			msg: 'Fully specified base is left alone'
		},
		{
			// No targetBase
			sourceBase: 'https://example.com',
			fallbackBase: 'https://example.org/foo',
			fixedBase: 'https://example.org/foo',
			msg: 'When base is missing, fallback base is used'
		}
	];

	cases.forEach( function ( caseItem ) {
		var targetDoc = ve.createDocumentFromHtml( '' );
		var sourceDoc = ve.createDocumentFromHtml( '' );
		var expectedBase = caseItem.fixedBase;
		if ( caseItem.targetBase ) {
			targetDoc.head.appendChild( $( '<base>', targetDoc ).attr( 'href', caseItem.targetBase )[ 0 ] );
			if ( targetDoc.baseURI ) {
				// baseURI is valid, so we expect it to be untouched
				expectedBase = targetDoc.baseURI;
			}
		}
		if ( caseItem.sourceBase ) {
			sourceDoc.head.appendChild( $( '<base>', sourceDoc ).attr( 'href', caseItem.sourceBase )[ 0 ] );
		}
		ve.fixBase( targetDoc, sourceDoc, caseItem.fallbackBase );
		assert.strictEqual( targetDoc.baseURI, expectedBase, caseItem.msg );
	} );
} );

QUnit.test( 'isUriComponentValid', function ( assert ) {
	assert.strictEqual( ve.isUriComponentValid( 'Foo' ), true, '"Foo" is a valid URI component' );
	assert.strictEqual( ve.isUriComponentValid( 'Foo%20Bar' ), true, '"Foo%20Bar" is a valid URI component' );
	assert.strictEqual( ve.isUriComponentValid( '%E0%A4%A' ), false, '"%E0%A4%A" is an invalid URI component' );
} );

QUnit.test( 'safeDecodeURIComponent', function ( assert ) {
	assert.strictEqual( ve.safeDecodeURIComponent( 'Foo' ), 'Foo', '"Foo" is successfully URI decoded' );
	assert.strictEqual( ve.safeDecodeURIComponent( 'Foo%20Bar' ), 'Foo Bar', '"Foo%20Bar" is successfully URI decoded' );
	assert.strictEqual( ve.safeDecodeURIComponent( '%E0%A4%A' ), '%E0%A4%A', '"%E0%A4%A" is not URI decoded, just returned as-is' );
} );

QUnit.test( 'isBlockElement/isVoidElement', function ( assert ) {
	assert.strictEqual( ve.isBlockElement( 'div' ), true, '"div" is a block element' );
	assert.strictEqual( ve.isBlockElement( 'SPAN' ), false, '"SPAN" is not a block element' );
	assert.strictEqual( ve.isBlockElement( 'a' ), false, '"a" is not a block element' );
	assert.strictEqual( ve.isBlockElement( document.createElement( 'div' ) ), true, '<div> is a block element' );
	assert.strictEqual( ve.isBlockElement( document.createElement( 'span' ) ), false, '<span> is not a block element' );

	assert.strictEqual( ve.isVoidElement( 'img' ), true, '"img" is a void element' );
	assert.strictEqual( ve.isVoidElement( 'DIV' ), false, '"DIV" is not a void element' );
	assert.strictEqual( ve.isVoidElement( 'span' ), false, '"span" is not a void element' );
	assert.strictEqual( ve.isVoidElement( document.createElement( 'img' ) ), true, '<img> is a void element' );
	assert.strictEqual( ve.isVoidElement( document.createElement( 'div' ) ), false, '<div> is not a void element' );
} );

// TODO: ve.getByteOffset

// TODO: ve.getClusterOffset

QUnit.test( 'graphemeSafeSubstring', function ( assert ) {
	var text = '12\ud860\udee245\ud860\udee2789\ud860\udee2bc',
		cases = [
			{
				msg: 'start and end inside multibyte',
				start: 3,
				end: 12,
				expected: [ '\ud860\udee245\ud860\udee2789\ud860\udee2', '45\ud860\udee2789' ]
			},
			{
				msg: 'start and end next to multibyte',
				start: 4,
				end: 11,
				expected: [ '45\ud860\udee2789', '45\ud860\udee2789' ]
			},
			{
				msg: 'complete string',
				start: 0,
				end: text.length,
				expected: [ text, text ]
			},
			{
				msg: 'collapsed selection inside multibyte',
				start: 3,
				end: 3,
				expected: [ '\ud860\udee2', '' ]
			}
		];

	cases.forEach( function ( caseItem ) {
		assert.strictEqual(
			ve.graphemeSafeSubstring( text, caseItem.start, caseItem.end, true ),
			caseItem.expected[ 0 ],
			caseItem.msg + ' (outer)'
		);
		assert.strictEqual(
			ve.graphemeSafeSubstring( text, caseItem.start, caseItem.end, false ),
			caseItem.expected[ 1 ],
			caseItem.msg + ' (inner)'
		);
	} );
} );

QUnit.test( 'getCommonAncestor', function ( assert ) {
	var doc = ve.createDocumentFromHtml( '<html><div><p>AA<i><b>BB<img src="#"></b></i>CC</p>DD</div>EE</html>' );
	var cases = [
		{ nodes: 'b b', ancestor: 'b' },
		{ nodes: 'b i', ancestor: 'i' },
		{ nodes: 'textB img', ancestor: 'b' },
		{ nodes: 'p textD', ancestor: 'div' },
		{ nodes: 'textC img', ancestor: 'p' },
		{ nodes: 'textC b', ancestor: 'p' },
		{ nodes: 'textC textD', ancestor: 'div' },
		{ nodes: 'textA textB', ancestor: 'p' },
		{ nodes: 'textA img', ancestor: 'p' },
		{ nodes: 'img textE', ancestor: 'body' },
		{ nodes: 'textA textB textC textD', ancestor: 'div' },
		{ nodes: 'textA i b textC', ancestor: 'p' },
		{ nodes: 'body div head p', ancestor: 'html' },
		{ nodes: 'b null', ancestor: 'null' },
		{ nodes: 'null b', ancestor: 'null' },
		{ nodes: 'b i null', ancestor: 'null' },
		{ nodes: 'b null i', ancestor: 'null' },
		{ nodes: 'b unattached', ancestor: 'null' },
		{ nodes: 'unattached b', ancestor: 'null' }
	];
	var nodes = {};
	nodes.html = doc.documentElement;
	nodes.head = doc.head;
	nodes.body = doc.body;
	nodes.div = doc.getElementsByTagName( 'div' )[ 0 ];
	nodes.p = doc.getElementsByTagName( 'p' )[ 0 ];
	nodes.b = doc.getElementsByTagName( 'b' )[ 0 ];
	nodes.i = doc.getElementsByTagName( 'i' )[ 0 ];
	nodes.img = doc.getElementsByTagName( 'img' )[ 0 ];
	nodes.textA = nodes.p.childNodes[ 0 ];
	nodes.textB = nodes.b.childNodes[ 0 ];
	nodes.textC = nodes.p.childNodes[ 2 ];
	nodes.textD = nodes.div.childNodes[ 1 ];
	nodes.textE = nodes.body.childNodes[ 1 ];
	nodes.null = null;
	nodes.unattached = doc.createElement( 'div' ).appendChild( doc.createElement( 'span' ) );
	function getNode( name ) {
		return nodes[ name ];
	}

	cases.forEach( function ( caseItem ) {
		var testNodes = caseItem.nodes.split( /\s+/ ).map( getNode );
		var ancestorNode = nodes[ caseItem.ancestor ];
		assert.strictEqual(
			ve.getCommonAncestor.apply( null, testNodes ),
			ancestorNode,
			caseItem.nodes + ' -> ' + caseItem.ancestor
		);
	} );

	// Test no-argument case
	assert.strictEqual( ve.getCommonAncestor(), null, 'No nodes' );
} );

QUnit.test( 'getCommonStartSequenceLength', function ( assert ) {
	var cases = [
		{
			sequences: [ [ 0, 1, 2 ], [ 0, 1, 2 ], [ '0', 1, 2 ] ],
			commonLength: 0,
			title: 'No common start sequence'
		},
		{
			sequences: [ [ 1, 2, 3 ], [] ],
			commonLength: 0,
			title: 'Empty sequence'
		},
		{
			sequences: [ [ 'five', 6 ], [ 'five' ] ],
			commonLength: 1,
			title: 'Differing lengths'
		},
		{
			sequences: [ [ 1, 2 ] ],
			commonLength: 2,
			title: 'Single sequence'
		},
		{
			sequences: [ 'Cymru', 'Cymry', 'Cymraes', 'Cymro', 'Cymraeg' ],
			commonLength: 4,
			title: 'String sequences'
		},
		{
			sequences: [],
			commonLength: 0,
			title: 'No sequences'
		}
	];

	cases.forEach( function ( caseItem ) {
		assert.strictEqual(
			ve.getCommonStartSequenceLength( caseItem.sequences ),
			caseItem.commonLength,
			caseItem.title
		);
	} );
} );

QUnit.test( 'adjacentDomPosition', function ( assert ) {
	// In the following tests, the html is put inside the top-level div as innerHTML. Then
	// ve.adjacentDomPosition is called with the position just inside the div (i.e.
	// { node: div, offset: 0 } for forward direction tests, and
	// { node: div, offset: div.childNodes.length } for reverse direction tests). The result
	// of the first call is passed into the function again, and so on iteratively until the
	// function returns null. The 'path' properties are a list of descent offsets to find a
	// particular position node from the top-level div. E.g. a path of [ 5, 7 ] refers to the
	// node div.childNodes[ 5 ].childNodes[ 7 ] .
	var cases = [
		{
			title: 'Simple p node',
			html: '<p>x</p>',
			options: { stop: function () {
				return true;
			} },
			expectedOffsetPaths: [
				[ 0 ],
				[ 0, 0 ],
				[ 0, 0, 0 ],
				[ 0, 0, 1 ],
				[ 0, 1 ],
				[ 1 ]
			]
		},
		{
			title: 'Filtered descent',
			html: '<div class="x">foo</div><div class="y">bar</div>',
			options: { stop: function () {
				return true;
			}, noDescend: '.x' },
			expectedOffsetPaths: [
				[ 0 ],
				[ 1 ],
				[ 1, 0 ],
				[ 1, 0, 0 ],
				[ 1, 0, 1 ],
				[ 1, 0, 2 ],
				[ 1, 0, 3 ],
				[ 1, 1 ],
				[ 2 ]
			]
		},
		{
			title: 'Empty tags and heavy nesting',
			html: '<div><br/><p>foo <b>bar <i>baz</i></b></p></div>',
			options: { stop: function () {
				return true;
			} },
			expectedOffsetPaths: [
				[ 0 ],
				[ 0, 0 ],
				// The <br/> tag is void, so should get skipped
				[ 0, 1 ],
				[ 0, 1, 0 ],
				[ 0, 1, 0, 0 ],
				[ 0, 1, 0, 1 ],
				[ 0, 1, 0, 2 ],
				[ 0, 1, 0, 3 ],
				[ 0, 1, 0, 4 ],
				[ 0, 1, 1 ],
				[ 0, 1, 1, 0 ],
				[ 0, 1, 1, 0, 0 ],
				[ 0, 1, 1, 0, 1 ],
				[ 0, 1, 1, 0, 2 ],
				[ 0, 1, 1, 0, 3 ],
				[ 0, 1, 1, 0, 4 ],
				[ 0, 1, 1, 1 ],
				[ 0, 1, 1, 1, 0 ],
				[ 0, 1, 1, 1, 0, 0 ],
				[ 0, 1, 1, 1, 0, 1 ],
				[ 0, 1, 1, 1, 0, 2 ],
				[ 0, 1, 1, 1, 0, 3 ],
				[ 0, 1, 1, 1, 1 ],
				[ 0, 1, 1, 2 ],
				[ 0, 1, 2 ],
				[ 0, 2 ],
				[ 1 ]
			]
		}
	];

	var div = document.createElement( 'div' );
	div.contentEditable = 'true';

	for ( var direction in { forward: undefined, backward: undefined } ) {
		// eslint-disable-next-line no-loop-func
		cases.forEach( function ( caseItem ) {
			div.innerHTML = caseItem.html;
			var offsetPaths = [];
			var position = {
				node: div,
				offset: direction === 'backward' ? div.childNodes.length : 0
			};
			while ( position.node !== null ) {
				offsetPaths.push(
					ve.getOffsetPath( div, position.node, position.offset )
				);
				position = ve.adjacentDomPosition(
					position,
					direction === 'backward' ? -1 : 1,
					caseItem.options
				);
			}
			assert.deepEqual(
				offsetPaths,
				(
					direction === 'backward' ?
						caseItem.expectedOffsetPaths.slice().reverse() :
						caseItem.expectedOffsetPaths
				),
				caseItem.title + ' (' + direction + ')'
			);
		} );
	}
} );

QUnit.test( 'deepFreeze', function ( assert ) {
	var data = [
		{ type: 'heading', attributes: { level: 1 } },
		'F', 'o', 'o',
		{ type: '/heading' }
	];

	var originalData = ve.copy( data );
	var frozen = ve.deepFreeze( data, true );

	assert.deepEqual( frozen, originalData, 'Frozen data is equal to original data' );
	assert.strictEqual( frozen, data, 'Result is same object as input' );

	assert.throws( function () {
		data[ 0 ].attributes.level = 2;
	}, Error, 'Can\'t change data attribute' );

	assert.throws( function () {
		delete frozen[ 0 ].attributes;
	}, Error, 'Can\'t delete property' );

	frozen.splice( 3, 1, 'b' );

	assert.strictEqual( frozen[ 3 ], 'b', 'Data can be spliced' );
	assert.strictEqual( data[ 3 ], 'b', 'Original object affected by splice' );

	frozen = ve.deepFreeze( data );

	assert.throws( function () {
		frozen.splice( 3, 1, 'c' );
	}, Error, 'Can\'t splice if root is frozen' );

	frozen = ve.deepFreeze( data );
	assert.true( true, 'Freezing for a second time does not throw' );
} );

QUnit.test( 'deepFreeze (on cyclic structure)', function ( assert ) {
	var realFreeze = ve.deepFreeze;

	var cyclic = { foo: 'bar' };
	cyclic.self = cyclic;

	var count;

	ve.deepFreeze = function () {
		count++;
		return realFreeze.apply( ve, arguments );
	};
	try {
		count = 0;
		ve.deepFreeze( cyclic );
		assert.strictEqual( count, 1, 'Did not recurse into self' );
	} finally {
		ve.deepFreeze = realFreeze;
	}
} );

QUnit.test( 'deepFreeze (recursive, aliased)', function ( assert ) {
	var foo = { bar: {} },
		realFreeze = ve.deepFreeze;

	ve.deepFreeze = function ( x ) {
		return x;
	};
	try {
		foo = realFreeze( foo );
		assert.true( Object.isFrozen( foo.bar ), 'Recursed into aliased version' );
	} finally {
		ve.deepFreeze = realFreeze;
	}
} );

QUnit.test( 've.minimizeRects', function ( assert ) {
	var cases = [
		{
			rects: [
				// left, top, width, height
				[ 0, 0, 100, 100 ],
				// Identical square removed
				[ 0, 0, 100, 100 ],

				[ 200, 0, 100, 100 ],
				// Contained in previous rect
				[ 210, 10, 90, 90 ],

				// Contained in next rect
				[ 410, 0, 90, 90 ],
				[ 400, 0, 100, 100 ],

				// Horizontally adjacent
				[ 0, 200, 100, 100 ],
				[ 100, 200, 100, 100 ],
				[ 200, 200, 100, 100 ],

				// Vertically adjacent
				[ 0, 400, 100, 100 ],
				[ 0, 500, 100, 100 ],
				[ 0, 600, 100, 100 ]
			],
			expected: [
				// Identical square removed
				[ 0, 0, 100, 100 ],
				// Contained in previous rect
				[ 200, 0, 100, 100 ],
				// Contained in next rect
				[ 400, 0, 100, 100 ],
				// Horizontally adjacent
				[ 0, 200, 300, 100 ],
				// Vertically adjacent
				[ 0, 400, 100, 300 ]
			]
		}
	];

	function expand( rect ) {
		return {
			left: rect[ 0 ],
			top: rect[ 1 ],
			width: rect[ 2 ],
			height: rect[ 3 ],
			right: rect[ 0 ] + rect[ 2 ],
			bottom: rect[ 1 ] + rect[ 3 ]
		};
	}

	cases.forEach( function ( caseItem ) {
		caseItem.rects = caseItem.rects.map( expand );
		caseItem.expected = caseItem.expected.map( expand );

		var rectsBefore = ve.copy( caseItem.rects );
		var actual = ve.minimizeRects( caseItem.rects );
		assert.deepEqual( caseItem.rects, rectsBefore, 'Input not modified' );
		assert.deepEqual( actual, caseItem.expected, 'List minifed' );
	} );
} );
