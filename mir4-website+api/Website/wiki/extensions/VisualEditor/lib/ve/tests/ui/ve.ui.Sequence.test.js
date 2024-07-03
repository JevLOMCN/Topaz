/*!
 * VisualEditor UserInterface Sequence tests.
 *
 * @copyright See AUTHORS.txt
 */

QUnit.module( 've.ui.Sequence' );

/* Tests */

QUnit.test( 'findAndExecuteSequences', function ( assert ) {
	var emptyDocData = [ { type: 'paragraph' }, { type: '/paragraph' }, { type: 'internalList' }, { type: '/internalList' } ],
		cases = [
			{
				content: '* ',
				expectedData: [
					{ type: 'list', attributes: { style: 'bullet' } },
					{ type: 'listItem' },
					{ type: 'paragraph', internal: { generated: 'wrapper' } },
					{ type: '/paragraph' },
					{ type: '/listItem' },
					{ type: '/list' }
				],
				expectedRangeOrSelection: new ve.Range( 3 ),
				msg: 'Bullet list'
			},
			{
				content: '1. ',
				expectedData: [
					{ type: 'list', attributes: { style: 'number' } },
					{ type: 'listItem' },
					{ type: 'paragraph', internal: { generated: 'wrapper' } },
					{ type: '/paragraph' },
					{ type: '/listItem' },
					{ type: '/list' }
				],
				expectedRangeOrSelection: new ve.Range( 3 ),
				msg: 'Numbered list'
			},
			{
				content: '----',
				expectedData: [
					{ type: 'horizontalRule' },
					{ type: '/horizontalRule' },
					{ type: 'paragraph' },
					{ type: '/paragraph' }
				],
				expectedRangeOrSelection: new ve.Range( 3 ),
				msg: 'Horizontal rule'
			}
		];

	cases.forEach( function ( caseItem ) {
		var view = ve.test.utils.createSurfaceViewFromDocument( ve.dm.example.createExampleDocumentFromData( emptyDocData ) );
		var model = view.getModel();
		model.getLinearFragment( new ve.Range( 1 ) ).insertContent( caseItem.content ).collapseToEnd();
		view.findAndExecuteSequences();
		assert.deepEqual(
			model.getDocument().getData( model.getDocument().getDocumentRange() ),
			caseItem.expectedData,
			caseItem.msg + ': data'
		);
		var expectedSelection = ve.test.utils.selectionFromRangeOrSelection( model.getDocument(), caseItem.expectedRangeOrSelection );
		assert.equalHash(
			model.getSelection(),
			expectedSelection,
			caseItem.msg + ': selection'
		);
	} );
} );
