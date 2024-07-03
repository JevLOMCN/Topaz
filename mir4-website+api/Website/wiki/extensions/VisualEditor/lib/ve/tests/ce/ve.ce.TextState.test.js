/*!
 * VisualEditor ContentEditable TextState tests.
 *
 * @copyright See AUTHORS.txt
 */

QUnit.module( 've.ce.TextState' );

/* Tests */

QUnit.test( 'getChangeTransaction', function ( assert ) {
	var underlineHash = ve.dm.example.underlineHash,
		boldHash = ve.dm.example.boldHash,
		annHash = ve.dm.example.annHash;

	var cases = [
		{
			msg: 'Clear bold',
			oldRawHtml: '<p>foo <b>bar</b> baz</p>',
			oldInnerHtml: 'foo <b class="' + ve.dm.example.textStyleClasses + ' ve-ce-boldAnnotation">bar</b> baz',
			newInnerHtml: 'foo bar baz',
			operations: [
				{ type: 'retain', length: 5 },
				{
					type: 'replace',
					remove: [ [ 'b', [ annHash( 'b' ) ] ], [ 'a', [ annHash( 'b' ) ] ], [ 'r', [ annHash( 'b' ) ] ] ],
					insert: [ 'b', 'a', 'r' ]
				},
				{ type: 'retain', length: 7 }
			]
		},
		{
			msg: 'Extend bold',
			oldRawHtml: '<p>foo <b>ba</b> baz</p>',
			oldInnerHtml: 'foo <b class="' + ve.dm.example.textStyleClasses + ' ve-ce-boldAnnotation">ba</b> baz',
			newInnerHtml: 'foo <b class="' + ve.dm.example.textStyleClasses + ' ve-ce-boldAnnotation">bar</b> baz',
			operations: [
				{ type: 'retain', length: 7 },
				{
					type: 'replace',
					remove: [],
					insert: [ [ 'r', [ annHash( 'b' ) ] ] ]
				},
				{ type: 'retain', length: 7 }
			]
		},
		{
			msg: 'Set bold',
			oldRawHtml: '<p>foo bar baz</p>',
			oldInnerHtml: 'foo bar baz',
			newInnerHtml: 'foo <b class="' + ve.dm.example.textStyleClasses + ' ve-ce-boldAnnotation">bar</b> baz',
			operations: [
				{ type: 'retain', length: 5 },
				{
					type: 'replace',
					remove: [ 'b', 'a', 'r' ],
					insert: [ [ 'b', [ boldHash ] ], [ 'a', [ boldHash ] ], [ 'r', [ boldHash ] ] ]
				},
				{ type: 'retain', length: 7 }
			]
		},
		{
			msg: 'Insert at start of bold',
			oldRawHtml: 'wx<b>y</b>',
			oldInnerHtml: 'wx<b class="' + ve.dm.example.textStyleClasses + ' ve-ce-boldAnnotation">y</b>',
			newInnerHtml: 'wx<b class="' + ve.dm.example.textStyleClasses + ' ve-ce-boldAnnotation">zy</b>',
			operations: [
				{ type: 'retain', length: 3 },
				{
					type: 'replace',
					remove: [],
					insert: [ [ 'z', [ annHash( 'b' ) ] ] ]
				},
				{ type: 'retain', length: 4 }
			]
		},
		{
			msg: 'Insert before start of bold',
			oldRawHtml: 'wx<b>y</b>',
			oldInnerHtml: 'wx<b class="' + ve.dm.example.textStyleClasses + ' ve-ce-boldAnnotation">y</b>',
			newInnerHtml: 'wxz<b class="' + ve.dm.example.textStyleClasses + ' ve-ce-boldAnnotation">y</b>',
			operations: [
				{ type: 'retain', length: 3 },
				{
					type: 'replace',
					remove: [],
					insert: [ 'z' ]
				},
				{ type: 'retain', length: 4 }
			]
		},
		{
			msg: 'Insert into insertion annotation',
			willFail: true,
			oldRawHtml: 'wx<b></b>z',
			oldInnerHtml: 'wx<b class="' + ve.dm.example.textStyleClasses + ' ve-ce-boldAnnotation"></b>z',
			newInnerHtml: 'wx<b class="' + ve.dm.example.textStyleClasses + ' ve-ce-boldAnnotation">y</b>z',
			operations: [
				{ type: 'retain', length: 3 },
				{
					type: 'replace',
					remove: [],
					insert: [ [ 'y', [ 'anything' ] ] ]
				},
				{ type: 'retain', length: 4 }
			]
		},
		{
			msg: 'Set bold underline before underline',
			oldRawHtml: 'foo bar<u>baz</u>',
			oldInnerHtml: 'foo bar<u class="' + ve.dm.example.textStyleClasses + ' ve-ce-underlineAnnotation">baz</u>',
			newInnerHtml: 'foo <u class="' + ve.dm.example.textStyleClasses + ' ve-ce-underlineAnnotation"><b class="' + ve.dm.example.textStyleClasses + ' ve-ce-boldAnnotation">bar</b>baz</u>',
			operations: [
				{ type: 'retain', length: 5 },
				{
					type: 'replace',
					remove: [ 'b', 'a', 'r' ],
					insert: [
						[ 'b', [ annHash( 'u' ), boldHash ] ],
						[ 'a', [ annHash( 'u' ), boldHash ] ],
						[ 'r', [ annHash( 'u' ), boldHash ] ]
					]
				},
				{ type: 'retain', length: 6 }
			]
		},
		{
			msg: 'Clear bold in italic',
			oldRawHtml: '<p><i>foo <b>bar</b> baz</i></p>',
			oldInnerHtml: '<i class="' + ve.dm.example.textStyleClasses + ' ve-ce-italicAnnotation">foo <b class="' + ve.dm.example.textStyleClasses + ' ve-ce-boldAnnotation">bar</b> baz</i>',
			newInnerHtml: '<i class="' + ve.dm.example.textStyleClasses + ' ve-ce-italicAnnotation">foo bar baz</i>',
			operations: [
				{ type: 'retain', length: 5 },
				{
					type: 'replace',
					remove: [
						[ 'b', [ annHash( 'i' ), annHash( 'b' ) ] ],
						[ 'a', [ annHash( 'i' ), annHash( 'b' ) ] ],
						[ 'r', [ annHash( 'i' ), annHash( 'b' ) ] ]
					],
					insert: [
						[ 'b', [ annHash( 'i' ) ] ],
						[ 'a', [ annHash( 'i' ) ] ],
						[ 'r', [ annHash( 'i' ) ] ]
					]
				},
				{ type: 'retain', length: 7 }
			]
		},
		{
			msg: 'Extend bold in italic',
			oldRawHtml: '<p><i>foo <b>ba</b> baz</i></p>',
			oldInnerHtml: '<i class="' + ve.dm.example.textStyleClasses + ' ve-ce-italicAnnotation">foo <b class="' + ve.dm.example.textStyleClasses + ' ve-ce-boldAnnotation">ba</b> baz</i>',
			newInnerHtml: '<i class="' + ve.dm.example.textStyleClasses + ' ve-ce-italicAnnotation">foo <b class="' + ve.dm.example.textStyleClasses + ' ve-ce-boldAnnotation">bar</b> baz</i>',
			operations: [
				{ type: 'retain', length: 7 },
				{
					type: 'replace',
					remove: [],
					insert: [ [ 'r', [ annHash( 'i' ), annHash( 'b' ) ] ] ]
				},
				{ type: 'retain', length: 7 }
			]
		},
		{
			msg: 'Set bold in italic',
			oldRawHtml: '<p><i>foo bar baz</i></p>',
			oldInnerHtml: '<i class="' + ve.dm.example.textStyleClasses + ' ve-ce-italicAnnotation">foo bar baz</i>',
			newInnerHtml: '<i class="' + ve.dm.example.textStyleClasses + ' ve-ce-italicAnnotation">foo <b class="' + ve.dm.example.textStyleClasses + ' ve-ce-boldAnnotation">bar</b> baz</i>',
			operations: [
				{ type: 'retain', length: 5 },
				{
					type: 'replace',
					remove: [
						[ 'b', [ annHash( 'i' ) ] ],
						[ 'a', [ annHash( 'i' ) ] ],
						[ 'r', [ annHash( 'i' ) ] ]
					],
					insert: [
						[ 'b', [ annHash( 'i' ), boldHash ] ],
						[ 'a', [ annHash( 'i' ), boldHash ] ],
						[ 'r', [ annHash( 'i' ), boldHash ] ]
					]
				},
				{ type: 'retain', length: 7 }
			]
		},
		{
			msg: 'Insert at start of bold in italic',
			oldRawHtml: '<i>wx<b>y</b></i>',
			oldInnerHtml: '<i class="' + ve.dm.example.textStyleClasses + ' ve-ce-italicAnnotation">wx<b class="' + ve.dm.example.textStyleClasses + ' ve-ce-boldAnnotation">y</b></i>',
			newInnerHtml: '<i class="' + ve.dm.example.textStyleClasses + ' ve-ce-italicAnnotation">wx<b class="' + ve.dm.example.textStyleClasses + ' ve-ce-boldAnnotation">zy</b></i>',
			operations: [
				{ type: 'retain', length: 3 },
				{
					type: 'replace',
					remove: [],
					insert: [ [ 'z', [ annHash( 'i' ), annHash( 'b' ) ] ] ]
				},
				{ type: 'retain', length: 4 }
			]
		},
		{
			msg: 'Insert before start of bold in italic',
			oldRawHtml: '<i>wx<b>y</b></i>',
			oldInnerHtml: '<i class="' + ve.dm.example.textStyleClasses + ' ve-ce-italicAnnotation">wx<b class="' + ve.dm.example.textStyleClasses + ' ve-ce-boldAnnotation">y</b></i>',
			newInnerHtml: '<i class="' + ve.dm.example.textStyleClasses + ' ve-ce-italicAnnotation">wxz<b class="' + ve.dm.example.textStyleClasses + ' ve-ce-boldAnnotation">y</b></i>',
			operations: [
				{ type: 'retain', length: 3 },
				{
					type: 'replace',
					remove: [],
					insert: [ [ 'z', [ annHash( 'i' ) ] ] ]
				},
				{ type: 'retain', length: 4 }
			]
		},
		{
			msg: 'Insert into insertion annotation in italic',
			willFail: true,
			oldRawHtml: '<i>wx<b></b>z</i>',
			oldInnerHtml: '<i class="' + ve.dm.example.textStyleClasses + ' ve-ce-italicAnnotation">wx<b class="' + ve.dm.example.textStyleClasses + ' ve-ce-boldAnnotation"><img class="ve-ce-unicorn ve-ce-pre-unicorn"><img class="ve-ce-unicorn ve-ce-post-unicorn"></b>z</i>',
			newInnerHtml: '<i class="' + ve.dm.example.textStyleClasses + ' ve-ce-italicAnnotation">wx<b class="' + ve.dm.example.textStyleClasses + ' ve-ce-boldAnnotation"><img class="ve-ce-unicorn ve-ce-pre-unicorn">y<img class="ve-ce-unicorn ve-ce-post-unicorn"></b>z</i>',
			operations: [
				{ type: 'retain', length: 3 },
				{
					type: 'replace',
					remove: [],
					insert: [ [ 'y', [ 'anything1', 'anything2' ] ] ]
				},
				{ type: 'retain', length: 4 }
			]
		},
		{
			msg: 'Set bold underline before underline in italic',
			oldRawHtml: '<i>foo bar<u>baz</u></i>',
			oldInnerHtml: '<i class="' + ve.dm.example.textStyleClasses + ' ve-ce-italicAnnotation">foo bar<u class="' + ve.dm.example.textStyleClasses + ' ve-ce-underlineAnnotation">baz</u></i>',
			newInnerHtml: '<i class="' + ve.dm.example.textStyleClasses + ' ve-ce-italicAnnotation">foo <u class="' + ve.dm.example.textStyleClasses + ' ve-ce-underlineAnnotation"><b class="' + ve.dm.example.textStyleClasses + ' ve-ce-boldAnnotation">bar</b>baz</u></i>',
			operations: [
				{ type: 'retain', length: 5 },
				{
					type: 'replace',
					remove: [
						[ 'b', [ annHash( 'i' ) ] ],
						[ 'a', [ annHash( 'i' ) ] ],
						[ 'r', [ annHash( 'i' ) ] ]
					],
					insert: [
						[ 'b', [ annHash( 'i' ), annHash( 'u' ), boldHash ] ],
						[ 'a', [ annHash( 'i' ), annHash( 'u' ), boldHash ] ],
						[ 'r', [ annHash( 'i' ), annHash( 'u' ), boldHash ] ]
					]
				},
				{ type: 'retain', length: 6 }
			]
		},
		{
			msg: 'Set bold with distant existing bold tag',
			oldRawHtml: '<b>foo</b> <i>bar</i> baz',
			oldInnerHtml: '<b class="' + ve.dm.example.textStyleClasses + ' ve-ce-boldAnnotation">foo</b> <i class="' + ve.dm.example.textStyleClasses + ' ve-ce-italicAnnotation">bar</i> baz',
			newInnerHtml: '<b class="' + ve.dm.example.textStyleClasses + ' ve-ce-boldAnnotation">foo</b> <i class="' + ve.dm.example.textStyleClasses + ' ve-ce-italicAnnotation">bar</i> <b class="' + ve.dm.example.textStyleClasses + ' ve-ce-boldAnnotation">baz</b>',
			operations: [
				{ type: 'retain', length: 9 },
				{
					type: 'replace',
					remove: [ 'b', 'a', 'z' ],
					// TODO: Reuse bold instead of creating a new bold?
					// (Some annotation types may need specific rules as to
					// when this can be done)
					insert: [ [ 'b', [ boldHash ] ], [ 'a', [ boldHash ] ], [ 'z', [ boldHash ] ] ]
				},
				{ type: 'retain', length: 3 }
			]
		},
		{
			msg: 'Set underline across bold close',
			oldRawHtml: '<i>a<b>bc</b>de</i>',
			oldInnerHtml: '<i class="' + ve.dm.example.textStyleClasses + ' ve-ce-italicAnnotation">a<b class="' + ve.dm.example.textStyleClasses + ' ve-ce-boldAnnotation">bc</b>de</i>',
			newInnerHtml: '<i class="' + ve.dm.example.textStyleClasses + ' ve-ce-italicAnnotation">a<b class="' + ve.dm.example.textStyleClasses + ' ve-ce-boldAnnotation">b<u class="' + ve.dm.example.textStyleClasses + ' ve-ce-underlineAnnotation">c</u></b><u class="' + ve.dm.example.textStyleClasses + ' ve-ce-underlineAnnotation">d</u>e</i>',
			operations: [
				{ type: 'retain', length: 3 },
				{
					type: 'replace',
					remove: [
						[ 'c', [ annHash( 'i' ), annHash( 'b' ) ] ],
						[ 'd', [ annHash( 'i' ) ] ]
					],
					insert: [
						[ 'c', [ annHash( 'i' ), annHash( 'b' ), underlineHash ] ],
						[ 'd', [ annHash( 'i' ), underlineHash ] ]
					]
				},
				{ type: 'retain', length: 4 }
			]
		},
		{
			msg: 'Set bold in one place and normal in another',
			oldRawHtml: '<b>foo</b> <i>bar</i> baz',
			oldInnerHtml: '<b class="' + ve.dm.example.textStyleClasses + ' ve-ce-boldAnnotation">foo</b> <i class="' + ve.dm.example.textStyleClasses + ' ve-ce-italicAnnotation">bar</i> baz',
			newInnerHtml: '<b class="' + ve.dm.example.textStyleClasses + ' ve-ce-boldAnnotation">foo</b> bar <b class="' + ve.dm.example.textStyleClasses + ' ve-ce-boldAnnotation">baz</b>',
			operations: [
				{ type: 'retain', length: 5 },
				{
					type: 'replace',
					// This weird-looking removal is the correct output for
					// a diff algorithm that matches common start/end items
					// then replaces the entire interior. In real life usage
					// there won't usually be two separate changed regions.
					remove: [
						[ 'b', [ annHash( 'i' ) ] ],
						[ 'a', [ annHash( 'i' ) ] ],
						[ 'r', [ annHash( 'i' ) ] ],
						' ', 'b', 'a', 'z'
					],
					// The first insertion get
					insert: [
						'b', 'a', 'r', ' ',
						[ 'b', [ annHash( 'b' ) ] ],
						[ 'a', [ annHash( 'b' ) ] ],
						[ 'z', [ annHash( 'b' ) ] ]
					]
				},
				{ type: 'retain', length: 3 }
			]
		},
		{
			msg: 'Insert new chunk whose annotations match end chunk\'s',
			oldRawHtml: '<u>x</u>yz',
			oldInnerHtml: '<u class="' + ve.dm.example.textStyleClasses + ' ve-ce-underlineAnnotation">x</u>yz',
			newInnerHtml: '<u class="' + ve.dm.example.textStyleClasses + ' ve-ce-underlineAnnotation">x</u>y<u class="' + ve.dm.example.textStyleClasses + ' ve-ce-underlineAnnotation">w</u>yz',
			operations: [
				{ type: 'retain', length: 2 },
				{
					type: 'replace',
					remove: [],
					insert: [ 'y', [ 'w', [ annHash( 'u' ) ] ] ]
				},
				{ type: 'retain', length: 5 }
			]
		},
		{
			msg: 'Ambiguous insert with start and end both identical to original',
			oldRawHtml: 'ab',
			oldInnerHtml: 'ab',
			newInnerHtml: 'ab<u class="' + ve.dm.example.textStyleClasses + ' ve-ce-underlineAnnotation">x</u>ab',
			operations: [
				{ type: 'retain', length: 3 },
				{
					type: 'replace',
					remove: [],
					insert: [ [ 'x', [ underlineHash ] ], 'a', 'b' ]
				},
				{ type: 'retain', length: 3 }
			]
		}
	];

	cases.forEach( function ( caseItem ) {
		var view = ve.test.utils.createSurfaceViewFromHtml( caseItem.oldRawHtml );
		var documentView = view.getDocument();
		var documentNode = documentView.getDocumentNode();
		var contentNode = documentNode.children[ 0 ];
		( caseItem.willFail ? assert.notEqualDomElement : assert.equalDomElement ).call(
			assert,
			$( '<div>' ).append( contentNode.$element.clone().contents() )[ 0 ],
			$( '<div>' ).html( caseItem.oldInnerHtml )[ 0 ],
			caseItem.msg + ' (oldInnerHtml)'
		);
		view.model.setSelection( new ve.dm.LinearSelection( new ve.Range( 1 ) ) );
		var oldState = new ve.ce.RangeState( null, documentNode, false );
		contentNode.$element.html( caseItem.newInnerHtml );
		view.model.setSelection( new ve.dm.LinearSelection( new ve.Range( 1 ) ) );
		var newState = new ve.ce.RangeState( oldState, documentNode, false );
		var change = newState.textState.getChangeTransaction(
			oldState.textState,
			view.model.getDocument(),
			newState.node.getOffset()
		);
		( caseItem.willFail ? assert.notDeepEqual : assert.deepEqual ).call(
			assert,
			change.operations,
			caseItem.operations,
			caseItem.msg + ' (operations)'
		);
		view.destroy();
	} );
} );
