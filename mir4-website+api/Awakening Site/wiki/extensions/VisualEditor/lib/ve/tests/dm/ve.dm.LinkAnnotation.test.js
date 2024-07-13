/*!
 * VisualEditor DataModel LinkAnnotation tests.
 *
 * @copyright See AUTHORS.txt
 */

QUnit.module( 've.dm.LinkAnnotation' );

QUnit.test( 'getFragment', function ( assert ) {
	var cases = [
		{
			msg: 'No fragment returns null',
			annotation: new ve.dm.LinkAnnotation( {
				type: 'link',
				attributes: { href: 'https://www.example.org/' }
			} ),
			expected: null
		},
		{
			msg: 'Blank fragment returns empty string',
			annotation: new ve.dm.LinkAnnotation( {
				type: 'link',
				attributes: { href: 'https://www.example.org/#' }
			} ),
			expected: ''
		},
		{
			msg: 'Extant fragment returns same string',
			annotation: new ve.dm.LinkAnnotation( {
				type: 'link',
				attributes: { href: 'https://www.example.org/#foo' }
			} ),
			expected: 'foo'
		},
		{
			msg: 'Hash-bang works returns full string',
			annotation: new ve.dm.LinkAnnotation( {
				type: 'link',
				attributes: { href: 'https://www.example.org/#!foo' }
			} ),
			expected: '!foo'
		},
		{
			msg: 'Double-hash returns everything after the first hash',
			annotation: new ve.dm.LinkAnnotation( {
				type: 'link',
				attributes: { href: 'https://www.example.org/##foo' }
			} ),
			expected: '#foo'
		},
		{
			msg: 'Multi-fragment returns everything after the first hash',
			annotation: new ve.dm.LinkAnnotation( {
				type: 'link',
				attributes: { href: 'https://www.example.org/#foo#bar#baz' }
			} ),
			expected: 'foo#bar#baz'
		}
	];

	cases.forEach( function ( caseItem ) {
		assert.strictEqual( caseItem.annotation.getFragment(), caseItem.expected, caseItem.msg );
	} );
} );
