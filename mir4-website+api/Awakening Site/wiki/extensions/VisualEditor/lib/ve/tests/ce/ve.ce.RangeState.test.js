/*!
 * VisualEditor ContentEditable Document tests.
 *
 * @copyright See AUTHORS.txt
 */

QUnit.module( 've.ce.RangeState' );

/* Tests */

QUnit.test( 'Basic tests', function ( assert ) {
	var view = ve.test.utils.createSurfaceViewFromHtml( ve.dm.example.html ),
		nativeSelection = view.nativeSelection,
		doc = view.getDocument().getDocumentNode(),
		cases = [
			{
				msg: 'From null to null state',
				resetOld: true,
				range: null,
				expected: {
					branchNodeChanged: false,
					contentChanged: false,
					hash: null,
					node: null,
					selectionChanged: false,
					text: null,
					veRange: null
				}
			},
			{
				msg: 'From null to inside heading',
				resetOld: true,
				range: {
					startNode: doc.children[ 0 ].$element[ 0 ],
					startOffset: 1
				},
				expected: {
					branchNodeChanged: true,
					contentChanged: false,
					hash: '<H1>#<B>#</B><I>#</I></H1>',
					node: doc.children[ 0 ],
					selectionChanged: true,
					text: 'abc',
					veRange: new ve.Range( 2 )
				}
			},
			{
				msg: 'From heading to paragraph',
				range: {
					startNode: doc.children[ 4 ].$element[ 0 ],
					startOffset: 0,
					endNode: doc.children[ 4 ].$element[ 0 ],
					endOffset: 1
				},
				expected: {
					branchNodeChanged: true,
					contentChanged: false,
					hash: '<P>#</P>',
					node: doc.children[ 4 ],
					selectionChanged: true,
					text: 'l',
					veRange: new ve.Range( 56, 57 )
				}
			},
			{
				msg: 'Selection changing anchor node only',
				range: {
					startNode: doc.children[ 4 ].$element[ 0 ],
					startOffset: 1,
					endNode: doc.children[ 4 ].$element[ 0 ],
					endOffset: 1
				},
				expected: {
					branchNodeChanged: false,
					contentChanged: false,
					hash: '<P>#</P>',
					node: doc.children[ 4 ],
					selectionChanged: true,
					text: 'l',
					veRange: new ve.Range( 57, 57 )
				}
			},
			{
				msg: 'From paragraph back to null state',
				expected: {
					branchNodeChanged: true,
					contentChanged: false,
					hash: null,
					node: null,
					selectionChanged: true,
					text: null,
					veRange: null
				}
			},
			{
				msg: 'From null state to null state',
				expected: {
					branchNodeChanged: false,
					contentChanged: false,
					hash: null,
					node: null,
					selectionChanged: false,
					text: null,
					veRange: null
				}
			}
		];

	function getSummary( state ) {
		return {
			branchNodeChanged: state.branchNodeChanged,
			selectionChanged: state.selectionChanged,
			contentChanged: state.contentChanged,
			veRange: state.veRange && state.veRange.toJSON(),
			node: state.node,
			text: state.text,
			hash: state.hash
		};
	}

	var oldState = null;
	cases.forEach( function ( caseItem ) {
		nativeSelection.removeAllRanges();
		if ( caseItem.range ) {
			var nativeRange = document.createRange();
			nativeRange.setStart( caseItem.range.startNode, caseItem.range.startOffset );
			if ( caseItem.range.endNode ) {
				nativeRange.setEnd( caseItem.range.endNode, caseItem.range.endOffset );
			}
			nativeSelection.addRange( nativeRange );
		}
		if ( caseItem.resetOld ) {
			oldState = null;
		}
		var rangeState = new ve.ce.RangeState( oldState, doc );
		assert.deepEqualWithNodeTree(
			getSummary( rangeState ),
			getSummary( caseItem.expected ),
			caseItem.msg
		);
		oldState = rangeState;
	} );
	view.destroy();
} );
