/*!
 * VisualEditor DataModel Annotation tests.
 *
 * @copyright See AUTHORS.txt
 */

QUnit.module( 've.dm.Annotation' );

QUnit.test( 'getHashObject', function ( assert ) {
	var cases = [
		{
			msg: 'Bold',
			annotation: new ve.dm.BoldAnnotation( {
				type: 'textStyle/bold',
				attributes: { nodeName: 'b' }
			} ),
			expected: {
				type: 'textStyle/bold',
				attributes: { nodeName: 'b' }
			}
		},
		{
			msg: 'Italic with original DOM elements',
			annotation: new ve.dm.ItalicAnnotation( {
				type: 'textStyle/italic',
				attributes: { nodeName: 'i' },
				originalDomElementsHash: 1
			} ),
			expected: {
				type: 'textStyle/italic',
				attributes: { nodeName: 'i' },
				originalDomElementsHash: 1
			}
		}
	];

	cases.forEach( function ( caseItem ) {
		assert.deepEqual( caseItem.annotation.getHashObject(), caseItem.expected, caseItem.msg );
	} );
} );
