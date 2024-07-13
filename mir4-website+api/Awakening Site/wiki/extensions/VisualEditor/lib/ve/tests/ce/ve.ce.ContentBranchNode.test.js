/*!
 * VisualEditor ContentEditable ContentBranchNode tests.
 *
 * @copyright See AUTHORS.txt
 */

QUnit.module( 've.ce.ContentBranchNode' );

/* Tests */

QUnit.test( 'getRenderedContents', function ( assert ) {
	var cases = [
		{
			msg: 'Plain text without annotations',
			data: [
				{ type: 'paragraph' },
				'a',
				'b',
				'c',
				{ type: '/paragraph' }
			],
			html: 'abc'
		},
		{
			msg: 'Bold text',
			data: [
				{ type: 'paragraph' },
				[ 'a', [ { type: 'textStyle/bold' } ] ],
				[ 'b', [ { type: 'textStyle/bold' } ] ],
				[ 'c', [ { type: 'textStyle/bold' } ] ],
				{ type: '/paragraph' }
			],
			html: '<b>abc</b>'
		},
		{
			msg: 'Italic text',
			data: [
				{ type: 'paragraph' },
				[ 'a', [ { type: 'textStyle/italic' } ] ],
				[ 'b', [ { type: 'textStyle/italic' } ] ],
				[ 'c', [ { type: 'textStyle/italic' } ] ],
				{ type: '/paragraph' }
			],
			html: '<i>abc</i>'
		},
		{
			msg: 'Underline text',
			data: [
				{ type: 'paragraph' },
				[ 'a', [ { type: 'textStyle/underline' } ] ],
				[ 'b', [ { type: 'textStyle/underline' } ] ],
				[ 'c', [ { type: 'textStyle/underline' } ] ],
				{ type: '/paragraph' }
			],
			html: '<u>abc</u>'
		},
		{
			msg: 'Strikethrough text',
			data: [
				{ type: 'paragraph' },
				[ 'a', [ { type: 'textStyle/strikethrough' } ] ],
				[ 'b', [ { type: 'textStyle/strikethrough' } ] ],
				[ 'c', [ { type: 'textStyle/strikethrough' } ] ],
				{ type: '/paragraph' }
			],
			html: '<s>abc</s>'
		},
		{
			msg: 'Deleted text',
			data: [
				{ type: 'paragraph' },
				[ 'a', [ { type: 'textStyle/strikethrough', attributes: { nodeName: 'del' } } ] ],
				[ 'b', [ { type: 'textStyle/strikethrough', attributes: { nodeName: 'del' } } ] ],
				[ 'c', [ { type: 'textStyle/strikethrough', attributes: { nodeName: 'del' } } ] ],
				{ type: '/paragraph' }
			],
			html: '<del>abc</del>'
		},
		{
			msg: 'Small text',
			data: [
				{ type: 'paragraph' },
				[ 'a', [ { type: 'textStyle/small' } ] ],
				[ 'b', [ { type: 'textStyle/small' } ] ],
				[ 'c', [ { type: 'textStyle/small' } ] ],
				{ type: '/paragraph' }
			],
			html: '<small>abc</small>'
		},
		{
			msg: 'Big text',
			data: [
				{ type: 'paragraph' },
				[ 'a', [ { type: 'textStyle/big' } ] ],
				[ 'b', [ { type: 'textStyle/big' } ] ],
				[ 'c', [ { type: 'textStyle/big' } ] ],
				{ type: '/paragraph' }
			],
			html: '<big>abc</big>'
		},
		{
			msg: 'Strong text',
			data: [
				{ type: 'paragraph' },
				[ 'a', [ { type: 'textStyle/bold', attributes: { nodeName: 'strong' } } ] ],
				[ 'b', [ { type: 'textStyle/bold', attributes: { nodeName: 'strong' } } ] ],
				[ 'c', [ { type: 'textStyle/bold', attributes: { nodeName: 'strong' } } ] ],
				{ type: '/paragraph' }
			],
			html: '<strong>abc</strong>'
		},
		{
			msg: 'Emphasized text',
			data: [
				{ type: 'paragraph' },
				[ 'a', [ { type: 'textStyle/italic', attributes: { nodeName: 'em' } } ] ],
				[ 'b', [ { type: 'textStyle/italic', attributes: { nodeName: 'em' } } ] ],
				[ 'c', [ { type: 'textStyle/italic', attributes: { nodeName: 'em' } } ] ],
				{ type: '/paragraph' }
			],
			html: '<em>abc</em>'
		},
		{
			msg: 'Superscript text',
			data: [
				{ type: 'paragraph' },
				[ 'a', [ { type: 'textStyle/superscript' } ] ],
				[ 'b', [ { type: 'textStyle/superscript' } ] ],
				[ 'c', [ { type: 'textStyle/superscript' } ] ],
				{ type: '/paragraph' }
			],
			html: '<sup>abc</sup>'
		},
		{
			msg: 'Subscript text',
			data: [
				{ type: 'paragraph' },
				[ 'a', [ { type: 'textStyle/subscript' } ] ],
				[ 'b', [ { type: 'textStyle/subscript' } ] ],
				[ 'c', [ { type: 'textStyle/subscript' } ] ],
				{ type: '/paragraph' }
			],
			html: '<sub>abc</sub>'
		},
		{
			msg: 'Code text',
			data: [
				{ type: 'paragraph' },
				[ 'a', [ { type: 'textStyle/code' } ] ],
				[ 'b', [ { type: 'textStyle/code' } ] ],
				[ 'c', [ { type: 'textStyle/code' } ] ],
				{ type: '/paragraph' }
			],
			html: '<code>abc</code>'
		},
		{
			msg: 'Teletype text',
			data: [
				{ type: 'paragraph' },
				[ 'a', [ { type: 'textStyle/code', attributes: { nodeName: 'tt' } } ] ],
				[ 'b', [ { type: 'textStyle/code', attributes: { nodeName: 'tt' } } ] ],
				[ 'c', [ { type: 'textStyle/code', attributes: { nodeName: 'tt' } } ] ],
				{ type: '/paragraph' }
			],
			html: '<tt>abc</tt>'
		},
		{
			msg: 'Bold character, plain character, italic character',
			data: [
				{ type: 'paragraph' },
				[ 'a', [ { type: 'textStyle/bold' } ] ],
				'b',
				[ 'c', [ { type: 'textStyle/italic' } ] ],
				{ type: '/paragraph' }
			],
			html: '<b>a</b>b<i>c</i>'
		},
		{
			msg: 'Comparable annotations: strong, bold',
			data: [
				{ type: 'paragraph' },
				[ 'a', [ { type: 'textStyle/bold', attributes: { nodeName: 'strong' } } ] ],
				[ 'b', [ { type: 'textStyle/bold' } ] ],
				{ type: '/paragraph' }
			],
			html: '<strong>ab</strong>'
		},
		{
			msg: 'Bold, italic and underlined text (same order)',
			data: [
				{ type: 'paragraph' },
				[ 'a', [
					{ type: 'textStyle/bold' },
					{ type: 'textStyle/italic' },
					{ type: 'textStyle/underline' }
				] ],
				[ 'b', [
					{ type: 'textStyle/bold' },
					{ type: 'textStyle/italic' },
					{ type: 'textStyle/underline' }
				] ],
				[ 'c', [
					{ type: 'textStyle/bold' },
					{ type: 'textStyle/italic' },
					{ type: 'textStyle/underline' }
				] ],
				{ type: '/paragraph' }
			],
			html: '<b><i><u>abc</u></i></b>'
		},
		{
			msg: 'Varying order in consecutive range doesn\'t affect rendering',
			data: [
				{ type: 'paragraph' },
				[ 'a', [
					{ type: 'textStyle/bold' },
					{ type: 'textStyle/italic' },
					{ type: 'textStyle/underline' }
				] ],
				[ 'b', [
					{ type: 'textStyle/italic' },
					{ type: 'textStyle/underline' },
					{ type: 'textStyle/bold' }
				] ],
				[ 'c', [
					{ type: 'textStyle/underline' },
					{ type: 'textStyle/bold' },
					{ type: 'textStyle/italic' }
				] ],
				{ type: '/paragraph' }
			],
			html: '<b><i><u>abc</u></i></b>'
		},
		{
			msg: 'Varying order in non-consecutive range does affect rendering',
			data: [
				{ type: 'paragraph' },
				[ 'a', [
					{ type: 'textStyle/bold' },
					{ type: 'textStyle/italic' },
					{ type: 'textStyle/underline' }
				] ],
				'b',
				[ 'c', [
					{ type: 'textStyle/underline' },
					{ type: 'textStyle/bold' },
					{ type: 'textStyle/italic' }
				] ],
				{ type: '/paragraph' }
			],
			html: '<b><i><u>a</u></i></b>b<u><b><i>c</i></b></u>'
		},
		{
			msg: 'Text annotated in varying order, surrounded by plain text',
			data: [
				{ type: 'paragraph' },
				'a',
				'b',
				'c',
				[ 'd', [
					{ type: 'textStyle/bold' },
					{ type: 'textStyle/italic' },
					{ type: 'textStyle/underline' }
				] ],
				[ 'e', [
					{ type: 'textStyle/italic' },
					{ type: 'textStyle/underline' },
					{ type: 'textStyle/bold' }
				] ],
				[ 'f', [
					{ type: 'textStyle/underline' },
					{ type: 'textStyle/bold' },
					{ type: 'textStyle/italic' }
				] ],
				'g',
				'h',
				'i',
				{ type: '/paragraph' }
			],
			html: 'abc<b><i><u>def</u></i></b>ghi'
		},
		{
			msg: 'Out-of-order closings do not produce misnested tags',
			data: [
				{ type: 'paragraph' },
				'a',
				'b',
				'c',
				[ 'd', [
					{ type: 'textStyle/bold' },
					{ type: 'textStyle/italic' },
					{ type: 'textStyle/underline' }
				] ],
				[ 'e', [
					{ type: 'textStyle/italic' },
					{ type: 'textStyle/underline' }
				] ],
				[ 'f', [
					{ type: 'textStyle/underline' },
					{ type: 'textStyle/bold' },
					{ type: 'textStyle/italic' }
				] ],
				'g',
				'h',
				'i',
				{ type: '/paragraph' }
			],
			html: 'abc<b><i><u>d</u></i></b><i><u>e<b>f</b></u></i>ghi'
		},
		{
			msg: 'Additional openings are added inline, even when out of order',
			data: [
				{ type: 'paragraph' },
				'a',
				'b',
				'c',
				[ 'd', [
					{ type: 'textStyle/italic' },
					{ type: 'textStyle/underline' },
					{ type: 'textStyle/bold' }
				] ],
				[ 'e', [
					{ type: 'textStyle/italic' },
					{ type: 'textStyle/underline' }
				] ],
				[ 'f', [
					{ type: 'textStyle/underline' },
					{ type: 'textStyle/bold' },
					{ type: 'textStyle/italic' }
				] ],
				'g',
				'h',
				'i',
				{ type: '/paragraph' }
			],
			html: 'abc<i><u><b>d</b>e<b>f</b></u></i>ghi'
		},
		{
			msg: 'Out-of-order closings surrounded by plain text',
			data: [
				{ type: 'paragraph' },
				'a',
				'b',
				'c',
				[ 'd', [
					{ type: 'textStyle/italic' },
					{ type: 'textStyle/underline' },
					{ type: 'textStyle/bold' }
				] ],
				[ 'e', [
					{ type: 'textStyle/bold' },
					{ type: 'textStyle/underline' }
				] ],
				[ 'f', [
					{ type: 'textStyle/underline' },
					{ type: 'textStyle/bold' }
				] ],
				'g',
				'h',
				'i',
				{ type: '/paragraph' }
			],
			html: 'abc<i><u><b>d</b></u></i><b><u>ef</u></b>ghi'
		}
	];

	cases.forEach( function ( caseItem ) {
		var doc = new ve.dm.Document( ve.dm.example.preprocessAnnotations( caseItem.data ) );
		var $wrapper = $( new ve.ce.ParagraphNode( doc.getDocumentNode().getChildren()[ 0 ] ).getRenderedContents() );
		// HACK strip out all the class="ve-ce-textStyleAnnotation ve-ce-textStyleBoldAnnotation" crap
		$wrapper.find( '.ve-ce-textStyleAnnotation' ).removeAttr( 'class' );
		assert.equalDomElement( $wrapper[ 0 ], $( '<div>' ).html( caseItem.html )[ 0 ], caseItem.msg );
	} );
} );
