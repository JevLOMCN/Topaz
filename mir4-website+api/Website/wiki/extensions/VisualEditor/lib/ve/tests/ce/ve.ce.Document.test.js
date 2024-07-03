/*!
 * VisualEditor ContentEditable Document tests.
 *
 * @copyright See AUTHORS.txt
 */

QUnit.module( 've.ce.Document' );

/* Tests */

QUnit.test( 'Converter tests', function ( assert ) {
	var cases = ve.dm.example.domToDataCases;

	for ( var msg in cases ) {
		if ( cases[ msg ].ceHtml ) {
			var caseItem = ve.copy( cases[ msg ] );
			caseItem.base = caseItem.base || ve.dm.example.baseUri;
			var model = ve.test.utils.getModelFromTestCase( caseItem );
			var view = new ve.ce.Document( model );
			var $documentElement = view.getDocumentNode().$element;
			// Simplify slugs
			$documentElement.find( '.ve-ce-branchNode-slug' ).contents().remove();
			assert.equalDomElement(
				// Wrap both in plain DIVs as we are only comparing the child nodes
				$( '<div>' ).append( $documentElement.contents() )[ 0 ],
				$( '<div>' ).html( caseItem.ceHtml )[ 0 ],
				msg
			);
		}
	}
} );

// TODO: getNodeFromOffset
// TODO: getSlugAtOffset
// TODO: getDirectionalityFromRange

QUnit.test( 'getNodeAndOffset', function ( assert ) {
	/* eslint-disable quotes */
	var docNodeStart = "<div class='ve-ce-branchNode ve-ce-documentNode ve-ce-attachedRootNode ve-ce-rootNode'>",
		pNodeStart = "<p class='ve-ce-branchNode ve-ce-contentBranchNode ve-ce-paragraphNode'>";

	// Each test below has the following:
	// html: an input document
	// data: the expected DM content
	// positions: the node+offset corresponding to each DM offset, shown by marking pipe
	// characters on a modified HTML representation in which text nodes are wrapped in
	// <#text>…</#text> tags (and most attributes are omitted)
	// dies (optional): a list of DM offsets where getNodeAndOffset is expected to die
	var cases = [
		{
			title: 'Simple para',
			html: '<p>x</p>',
			data: [ '<paragraph>', 'x', '</paragraph>' ],
			positions: docNodeStart + "|" + pNodeStart + "<#text>|x|</#text></p></div>"
		},
		{
			title: 'Bold',
			html: '<p>x<b>y</b>z</p>',
			data: [ '<paragraph>', 'x', 'y', 'z', '</paragraph>' ],
			positions: docNodeStart + "|" + pNodeStart + "<#text>|x|</#text><b class='ve-ce-annotation ve-ce-textStyleAnnotation ve-ce-boldAnnotation'><#text>y|</#text></b><#text>z|</#text></p></div>"
		},
		{
			title: 'Nested block nodes',
			html: '<div><p>x</p></div>',
			data: [ '<div>', '<paragraph>', 'x', '</paragraph>', '</div>' ],
			positions: docNodeStart + "<div class='ve-ce-branchNode-slug ve-ce-branchNode-blockSlug'></div>|<div class='ve-ce-branchNode'>|" + pNodeStart + "<#text>|x|</#text></p>|</div><div class='ve-ce-branchNode-slug ve-ce-branchNode-blockSlug'></div></div>"
		},
		{
			title: 'Empty document',
			html: '<p></p>',
			unwrap: [ { type: 'paragraph' } ],
			data: [],
			positions: "",
			dies: [ 1 ]
		},
		{
			title: 'Slugless emptied paragraph',
			html: '<p></p>',
			replacement: { path: [ 0 ], innerHtml: '' },
			data: [ '<paragraph>', '</paragraph>' ],
			positions: docNodeStart + "|" + pNodeStart + "|</p></div>"
		},
		{
			title: 'Slugless emptied paragraph with raw <br> in DOM only',
			html: '<p></p>',
			replacement: { path: [ 0 ], innerHtml: '<br>' },
			data: [ '<paragraph>', '</paragraph>' ],
			positions: docNodeStart + "|" + pNodeStart + "|<br></br></p></div>"
		},
		{
			title: 'Paragraph with links',
			html: '<p><a href="A">A</a><a href="B">B</a></p>',
			data: [ '<paragraph>', 'A', 'B', '</paragraph>' ],
			positions: docNodeStart + "|" + pNodeStart + "|<img class='ve-ce-nail ve-ce-nail-pre-open'></img><a class='ve-ce-annotation ve-ce-nailedAnnotation ve-ce-linkAnnotation'><img class='ve-ce-nail ve-ce-nail-post-open'></img><#text>A</#text><img class='ve-ce-nail ve-ce-nail-pre-close'></img></a><img class='ve-ce-nail ve-ce-nail-post-close'></img>|<img class='ve-ce-nail ve-ce-nail-pre-open'></img><a class='ve-ce-annotation ve-ce-nailedAnnotation ve-ce-linkAnnotation'><img class='ve-ce-nail ve-ce-nail-post-open'></img><#text>B</#text><img class='ve-ce-nail ve-ce-nail-pre-close'></img></a><img class='ve-ce-nail ve-ce-nail-post-close'></img>|</p></div>"
		},
		{
			title: 'Paragraph with links, non-text nodes',
			html: '<p><a href="A"><b>A<img src="' + ve.dm.example.imgSrc + '"></b></a></p>',
			data: [ '<paragraph>', 'A', '<inlineImage>', '</inlineImage>', '</paragraph>' ],
			positions: docNodeStart + "|" + pNodeStart + "|<img class='ve-ce-nail ve-ce-nail-pre-open'></img><a class='ve-ce-annotation ve-ce-nailedAnnotation ve-ce-linkAnnotation'><img class='ve-ce-nail ve-ce-nail-post-open'></img><b class='ve-ce-annotation ve-ce-textStyleAnnotation ve-ce-boldAnnotation'><#text>A|</#text><img class='ve-ce-leafNode ve-ce-focusableNode ve-ce-imageNode ve-ce-inlineImageNode'></img></b><img class='ve-ce-nail ve-ce-nail-pre-close'></img></a><img class='ve-ce-nail ve-ce-nail-post-close'></img>||<span class='ve-ce-branchNode-slug ve-ce-branchNode-inlineSlug'></span></p></div>"
		},
		{
			title: 'About grouped aliens',
			html: "<p><span rel='ve:Alien' about='x'>Foo</span><span rel='ve:Alien' about='x'>Bar</span></p>",
			data: [ '<paragraph>', '<alienInline>', '</alienInline>', '</paragraph>' ],
			positions: docNodeStart + "|" + pNodeStart + "|<span class='ve-ce-branchNode-slug ve-ce-branchNode-inlineSlug'></span><span class='ve-ce-focusableNode ve-ce-leafNode'><#text>|Foo</#text></span><span class='ve-ce-focusableNode ve-ce-leafNode'><#text>Bar</#text></span>|<span class='ve-ce-branchNode-slug ve-ce-branchNode-inlineSlug'></span></p></div>"
		},
		{
			title: 'Non-about grouped aliens',
			html: "<p><span rel='ve:Alien' about='x'>Foo</span><span rel='ve:Alien' about='y'>Bar</span></p>",
			data: [ '<paragraph>', '<alienInline>', '</alienInline>', '<alienInline>', '</alienInline>', '</paragraph>' ],
			positions: docNodeStart + "|" + pNodeStart + "|<span class='ve-ce-branchNode-slug ve-ce-branchNode-inlineSlug'></span><span class='ve-ce-focusableNode ve-ce-leafNode'><#text>|Foo</#text></span>|<span class='ve-ce-branchNode-slug ve-ce-branchNode-inlineSlug'></span><span class='ve-ce-focusableNode ve-ce-leafNode'><#text>|Bar</#text></span>|<span class='ve-ce-branchNode-slug ve-ce-branchNode-inlineSlug'></span></p></div>"
		},
		{
			title: 'Meta outside of CBN',
			html: "<p>X</p><!----><p>Y</p>",
			data: [ '<paragraph>', 'X', '</paragraph>', '<commentMeta>', '</commentMeta>', '<paragraph>', 'Y', '</paragraph>' ],
			positions: docNodeStart + "|" + pNodeStart + "<#text>|X|</#text></p>|||" + pNodeStart + "<#text>|Y|</#text></p></div>"
		},
		{
			title: 'Meta does not cause double block slug',
			html: "<p rel='ve:Alien'>X</p><!---->",
			data: [ '<alienBlock>', '</alienBlock>', '<commentMeta>', '</commentMeta>' ],
			positions: docNodeStart + "<div class='ve-ce-branchNode-slug ve-ce-branchNode-blockSlug'></div>|<p class='ve-ce-focusableNode ve-ce-leafNode'>|<#text>X</#text></p><div class='ve-ce-branchNode-slug ve-ce-branchNode-blockSlug'></div>||</div>"
		}
	];
	/* eslint-enable quotes */

	function showModelItem( item ) {
		if ( item.type ) {
			return '<' + item.type + '>';
		} else if ( Array.isArray( item ) ) {
			return item[ 0 ];
		} else if ( typeof item === 'string' ) {
			return item;
		} else {
			return '(unexpected: ' + item + ')';
		}
	}

	cases.forEach( function ( caseItem ) {
		var parts = caseItem.positions.split( /[|]/ );
		var view = ve.test.utils.createSurfaceViewFromHtml( caseItem.html );
		var dmDoc = view.getModel().getDocument();
		if ( caseItem.unwrap ) {
			new ve.dm.Surface( dmDoc ).change(
				ve.dm.TransactionBuilder.static.newFromWrap(
					dmDoc,
					new ve.Range( 0, dmDoc.data.countNonInternalElements() ),
					[],
					[],
					caseItem.unwrap,
					[]
				)
			);
		}
		var data = dmDoc.data.data
			.slice( 0, -2 )
			.map( showModelItem );
		var ceDoc = view.documentView;
		var rootNode = ceDoc.getDocumentNode().$element.get( 0 );
		assert.deepEqual( data, caseItem.data, caseItem.title + ' (data)' );

		var offsetCount = data.length;
		assert.strictEqual(
			offsetCount,
			caseItem.positions.replace( /[^|]/g, '' ).length,
			caseItem.title + ' (offset count)'
		);

		if ( caseItem.replacement ) {
			var node = rootNode;
			for ( var j = 0, jLen = caseItem.replacement.path.length; j < jLen; j++ ) {
				node = node.childNodes[ caseItem.replacement.path[ j ] ];
			}
			$( node ).closest(
				'.ve-ce-branchNode,.ve-ce-leafNode'
			).data( 'view' ).removeSlugs();
			node.innerHTML = caseItem.replacement.innerHtml;
		}

		for ( var offset = 0; offset < offsetCount; offset++ ) {
			assert.strictEqual(
				ve.test.utils.serializePosition(
					rootNode,
					ceDoc.getNodeAndOffset( offset ),
					{ ignore: '.ve-ce-branchNode-slug>*' }
				),
				[].concat(
					parts.slice( 0, offset + 1 ),
					[ '|' ],
					parts.slice( offset + 1 )
				).join( '' ),
				caseItem.title + ' (' + offset + ')'
			);
		}
		for ( var k = 0; caseItem.dies && k < caseItem.dies.length; k++ ) {
			var dieOffset = caseItem.dies[ k ];
			var ex = null;
			try {
				ceDoc.getNodeAndOffset( dieOffset );
			} catch ( e ) {
				ex = e;
			}
			assert.notStrictEqual( ex, null, caseItem.title + ' (' + dieOffset + ') dies' );
		}
		view.destroy();
	} );
} );

QUnit.test( 'attachedRoot', function ( assert ) {
	var dmDoc = ve.dm.converter.getModelFromDom(
			ve.createDocumentFromHtml(
				'<section>Foo</section><section>Bar</section><section>Baz</section>'
			)
		),
		attachedRoot = dmDoc.getDocumentNode().children[ 1 ],
		surfaceModel = new ve.dm.Surface( dmDoc, attachedRoot ),
		surfaceView = ve.test.utils.createSurfaceViewFromDocument( surfaceModel );

	assert.deepEqual(
		surfaceView.getDocument().getDocumentNode().children.map( function ( node ) {
			return node.type;
		} ),
		[ 'unrendered', 'section', 'unrendered', 'unrendered' ],
		'Only attached root is rendered'
	);
} );
