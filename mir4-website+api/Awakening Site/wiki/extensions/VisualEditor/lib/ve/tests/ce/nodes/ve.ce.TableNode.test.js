/*!
 * VisualEditor ContentEditable TableNode tests.
 *
 * @copyright See AUTHORS.txt
 */

QUnit.module( 've.ce.TableNode' );

/* Tests */

QUnit.test( 'getNearestCellNode', function ( assert ) {
	var view = ve.test.utils.createSurfaceViewFromHtml( ve.dm.example.singleLine`
			<table>
				<tr>
					<td>
						Foo
						<table><tr><td>Bar</td></tr></table>
					</td>
					<td>Baz</td>
				</tr>
			</table>'
		` ),
		documentNode = view.getDocument().getDocumentNode(),
		tableNode = documentNode.children[ 0 ],
		$tableNode = tableNode.$element,
		cases = [
			{
				msg: 'Table cell',
				element: $tableNode.find( 'td' )[ 0 ],
				node: documentNode.children[ 0 ].children[ 0 ].children[ 0 ].children[ 0 ]
			},
			{
				msg: 'Paragraph inside cell',
				element: $tableNode.find( 'td' ).last().find( 'p' )[ 0 ],
				node: documentNode.children[ 0 ].children[ 0 ].children[ 0 ].children[ 1 ]
			},
			{
				msg: 'Cell inside nested table',
				element: $tableNode.find( 'table td' ).first()[ 0 ],
				node: null
			}
		];

	cases.forEach( function ( caseItem ) {
		assert.strictEqual( tableNode.getNearestCellNode( caseItem.element ), caseItem.node, caseItem.msg );
	} );
	view.destroy();
} );

QUnit.test( 'getFirstSectionNode', function ( assert ) {
	var view = ve.test.utils.createSurfaceViewFromHtml( ve.dm.example.singleLine`
			<table>
				<caption>Caption</caption>
				<tr><td>Foo</td></tr>
			</table>
		` ),
		documentNode = view.getDocument().getDocumentNode(),
		tableNode = documentNode.children[ 0 ],
		result = tableNode.getFirstSectionNode();

	assert.true( result instanceof ve.ce.TableSectionNode, 'result is a TableSectionNode' );
	assert.strictEqual( result, tableNode.children[ 1 ], 'result is 2nd child of table' );
} );

QUnit.test( 'onTableMouseDown/onTableMouseMove/onTableMouseUp/onTableDblClick', function ( assert ) {
	var realVeCeSurfaceGetOffsetFromCoords = ve.ce.Surface.prototype.getOffsetFromCoords,
		view = ve.test.utils.createSurfaceViewFromDocument( ve.dm.example.createExampleDocument( 'mergedCells' ) ),
		model = view.getModel(),
		documentNode = view.getDocument().getDocumentNode(),
		tableNode = documentNode.children[ 0 ],
		cell = tableNode.children[ 0 ].children[ 3 ].children[ 1 ],
		e = {
			target: cell.$element[ 0 ],
			originalEvent: { pageX: 0, pageY: 0 },
			preventDefault: function () {}
		};

	// Fake ve.ce.Surface#getOffsetFromCoords (the method doesn't work properly in this unit
	// test, because our mouse event dummy coordinates do not actually relate in any way to
	// the test surface coordinates).
	ve.ce.Surface.prototype.getOffsetFromCoords = function () {
		return -1;
	};
	try {
		tableNode.onTableMouseDown( e );
		tableNode.onTableMouseMove( e );
		tableNode.onTableMouseUp( e );

		var expectedSelection = ve.test.utils.selectionFromRangeOrSelection(
			model.getDocument(),
			{
				type: 'table',
				tableRange: new ve.Range( 0, 171 ),
				fromCol: 1,
				fromRow: 3,
				toCol: 3,
				toRow: 5
			}
		);
		assert.equalHash( model.getSelection(), expectedSelection, 'Selection after mouse up' );

		tableNode.onTableDblClick( e );

		expectedSelection = ve.test.utils.selectionFromRangeOrSelection(
			model.getDocument(),
			new ve.Range( 94 )
		);
		assert.equalHash( model.getSelection(), expectedSelection, 'Selection after double click' );

		ve.extendObject( e, { target: e.target.previousSibling, shiftKey: true } );
		tableNode.onTableMouseDown( e );
		tableNode.onTableMouseUp( e );

		expectedSelection = ve.test.utils.selectionFromRangeOrSelection(
			model.getDocument(),
			{
				type: 'table',
				tableRange: new ve.Range( 0, 171 ),
				fromCol: 3,
				fromRow: 3,
				toCol: 0,
				toRow: 5
			}
		);

		assert.equalHash( model.getSelection(), expectedSelection, 'Selection after Shift-click on another cell' );
	} finally {
		ve.ce.Surface.prototype.getOffsetFromCoords = realVeCeSurfaceGetOffsetFromCoords;
	}
} );

QUnit.test( 'onTableMouseDown', function ( assert ) {
	var view = ve.test.utils.createSurfaceViewFromHtml(
			'<table><tr><td>Foo</td><td>Bar</td></tr></table>'
		),
		documentNode = view.getDocument().getDocumentNode(),
		tableNode = documentNode.children[ 0 ],
		$tableNode = tableNode.$element,
		mockEvent = {
			preventDefault: function () {}
		},
		cases = [
			{
				msg: 'Table cell',
				event: {
					target: $tableNode.find( 'td' )[ 0 ]
				},
				expectedSelection: {
					type: 'table',
					tableRange: new ve.Range( 0, 20 ),
					fromCol: 0,
					fromRow: 0,
					toCol: 0,
					toRow: 0
				}
			},
			{
				msg: 'Shift click second cell paragraph',
				event: {
					target: $tableNode.find( 'td' ).last().find( 'p' )[ 0 ],
					shiftKey: true
				},
				expectedSelection: {
					type: 'table',
					tableRange: new ve.Range( 0, 20 ),
					fromCol: 0,
					fromRow: 0,
					toCol: 1,
					toRow: 0
				}
			}
		];

	cases.forEach( function ( caseItem ) {
		tableNode.onTableMouseDown( ve.extendObject( mockEvent, caseItem.event ) );
		assert.deepEqual(
			tableNode.surface.getModel().getSelection().toJSON(),
			caseItem.expectedSelection,
			caseItem.msg
		);
		// Clear document mouse up handlers
		tableNode.onTableMouseUp();
	} );
	view.destroy();
} );
