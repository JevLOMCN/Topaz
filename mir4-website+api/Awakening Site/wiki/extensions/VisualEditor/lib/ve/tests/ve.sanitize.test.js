/*!
 * VisualEditor HTML sanitization utilities tests.
 *
 * @copyright See AUTHORS.txt
 */

QUnit.module( 've.sanitize' );

QUnit.test( 've.sanitizeHtml', function ( assert ) {

	var cases = [
		{
			msg: 'Unsafe link href removed',
			html: '<a href="javascript:alert(1);">Foo</a>',
			expected: '<a>Foo</a>'
		},
		{
			msg: 'Unsafe onclick removed',
			html: '<span onclick="alert(1);">Foo</span>',
			expected: '<span>Foo</span>'
		},
		{
			msg: 'script and style elements removed',
			html: '<script></script><style></style>',
			expected: ''
		},
		{
			msg: 'style attribute allowed',
			html: '<span style="font-weight: 700;">Foo</span>',
			expected: '<span style="font-weight: 700;">Foo</span>'
		},
		{
			msg: 'RDFa attributes allowed',
			html: '<span about="#1" rel="#2" resource="#3" property="#4" content="#5" dataType="#6" typeof="#7">Foo</span>',
			expected: '<span about="#1" rel="#2" resource="#3" property="#4" content="#5" dataType="#6" typeof="#7">Foo</span>'
		},
		{
			msg: 'figure-inline element allowed',
			html: '<figure-inline></figure-inline>',
			expected: '<figure-inline></figure-inline>'
		},
		{
			msg: 'HTML tag in attribute allowed',
			html: '<span content="&lt;b&gt;Foo&lt;/b&gt;">Foo</span>',
			expected: '<span content="&lt;b&gt;Foo&lt;/b&gt;">Foo</span>'
		},
		{
			msg: 'Self-closing HTML tag in attribute allowed',
			html: '<span content="&lt;img/&gt;">Foo</span>',
			expected: '<span content="&lt;img/&gt;">Foo</span>'
		}
	];

	cases.forEach( function ( caseItem ) {
		var actual = document.createElement( 'div' );
		var nodes = ve.sanitizeHtml( caseItem.html );
		Array.prototype.forEach.call( nodes, function ( node ) {
			actual.appendChild( node );
		} );

		var expected = document.createElement( 'div' );
		expected.innerHTML = caseItem.expected;
		assert.equalDomElement( actual, expected, caseItem.msg );
	} );

} );

QUnit.test( 've.sanitizeHtmlToDocument', function ( assert ) {

	var nodes = ve.sanitizeHtml( '<a href="allowed">Foo</a>' );
	var body = document.createElement( 'body' );
	Array.prototype.forEach.call( nodes, function ( node ) {
		body.appendChild( node );
	} );
	var sanitizedDocument = ve.sanitizeHtmlToDocument( '<a href="allowed">Foo</a>' );

	assert.equalDomElement( sanitizedDocument.body, body, 'Body node is the same as if we used ve.sanitizeHtml' );

} );

QUnit.test( 've.setAttributeSafe', function ( assert ) {

	var cases = [
		{
			msg: 'Unsafe link href sets fallback instead',
			element: '<a rel="bar">Foo</a>',
			attr: 'href',
			// eslint-disable-next-line no-script-url
			val: 'javascript:alert(1)',
			fallbackVal: '#',
			expected: '<a href="#" rel="bar">Foo</a>'
		},
		{
			msg: 'Unsafe link href without fallback is no-op',
			element: '<a rel="bar">Foo</a>',
			attr: 'href',
			// eslint-disable-next-line no-script-url
			val: 'javascript:alert(1)',
			expected: '<a rel="bar">Foo</a>'
		},
		{
			msg: 'Safe link is set',
			element: '<a rel="bar">Foo</a>',
			attr: 'href',
			val: '#javascript',
			expected: '<a href="#javascript" rel="bar">Foo</a>'
		},
		{
			msg: 'onclick is not set',
			element: '<div>Foo</div>',
			attr: 'onclick',
			val: 'alert()',
			expected: '<div>Foo</div>'
		},
		{
			msg: 'RDFa attribute is set',
			element: '<div>Foo</div>',
			attr: 'about',
			val: '#1',
			expected: '<div about="#1">Foo</div>'
		}
	];

	cases.forEach( function ( caseItem ) {
		var actual = document.createElement( 'div' );
		actual.innerHTML = caseItem.element;
		ve.setAttributeSafe(
			actual.childNodes[ 0 ],
			caseItem.attr,
			caseItem.val,
			caseItem.fallbackVal
		);

		var expected = document.createElement( 'div' );
		expected.innerHTML = caseItem.expected;

		assert.equalDomElement( actual, expected, caseItem.msg );
	} );

} );
