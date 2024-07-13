/*!
 * VisualEditor MediaWiki-specific ContentEditable ContentBranchNode tests.
 *
 * @copyright See AUTHORS.txt
 * @license The MIT License (MIT); see LICENSE.txt
 */

QUnit.module( 've.ce.ContentBranchNode (MW)' );

/* Tests */

// FIXME runner copypasted from core, use data provider
QUnit.test( 'getRenderedContents', ( assert ) => {
	const cases = [ {
			msg: 'Annotation spanning text and inline nodes',
			data: [
				{ type: 'paragraph' },
				'a',
				[ 'b', [ { type: 'textStyle/bold' } ] ],
				{
					type: 'mwEntity',
					attributes: { character: 'c' },
					annotations: [ { type: 'textStyle/bold' } ]
				},
				{ type: '/mwEntity' },
				[ 'd', [ { type: 'textStyle/bold' } ] ],
				{
					type: 'alienInline',
					originalDomElements: $.parseHTML( '<span rel="ve:Alien">e</span>' ),
					annotations: [ { type: 'textStyle/bold' } ]
				},
				{ type: '/alienInline' },
				{ type: '/paragraph' }
			],
			html: ve.dm.example.singleLine`
				a
				<b>
					b
					<span class="ve-ce-leafNode ve-ce-mwEntityNode" contenteditable="false">c</span>
					d
					<span rel="ve:Alien" class="ve-ce-focusableNode ve-ce-leafNode" contenteditable="false">e</span>
				</b>
			`
		} ];

	for ( let i = 0; i < cases.length; i++ ) {
		const doc = new ve.dm.Document( ve.dm.example.preprocessAnnotations( cases[ i ].data ) ),
			$wrapper = $( new ve.ce.ParagraphNode( doc.getDocumentNode().getChildren()[ 0 ] ).getRenderedContents() );
		// HACK strip out all the class="ve-ce-textStyleAnnotation ve-ce-textStyleBoldAnnotation" crap
		$wrapper.find( '.ve-ce-textStyleAnnotation' ).removeAttr( 'class' );
		assert.equalDomElement( $wrapper[ 0 ], $( '<div>' ).html( cases[ i ].html )[ 0 ], cases[ i ].msg );
	}
} );
