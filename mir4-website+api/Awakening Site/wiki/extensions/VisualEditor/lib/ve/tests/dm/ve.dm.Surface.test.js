/*!
 * VisualEditor DataModel Surface tests.
 *
 * @copyright See AUTHORS.txt
 */

QUnit.module( 've.dm.Surface' );

ve.dm.SurfaceStub = function VeDmSurfaceStub( data, range ) {
	var doc = new ve.dm.Document( data || [ { type: 'paragraph' }, 'h', 'i', { type: '/paragraph' }, { type: 'internalList' }, { type: '/internalList' } ] );

	// Inheritance
	ve.dm.SurfaceStub.super.call( this, doc );

	// Initialize selection to simulate the surface being focused
	this.setLinearSelection( range || new ve.Range( 1 ) );
};

OO.inheritClass( ve.dm.SurfaceStub, ve.dm.Surface );

// Tests

QUnit.test( 'getDocument', function ( assert ) {
	var surface = new ve.dm.SurfaceStub();
	assert.strictEqual( surface.getDocument(), surface.documentModel );
} );

QUnit.test( 'getSelection', function ( assert ) {
	var surface = new ve.dm.SurfaceStub();
	assert.strictEqual( surface.getSelection(), surface.selection );
} );

QUnit.test( 'setSelection out of range', function ( assert ) {
	var surface = new ve.dm.SurfaceStub( [
		{ type: 'paragraph' },
		'F', 'o', 'o',
		{ type: '/paragraph' },
		{ type: 'internalList' }, { type: '/internalList' }
	] );

	surface.setSelection( new ve.dm.LinearSelection( new ve.Range( 2, 100 ) ) );
	var range = surface.getSelection().getRange();
	assert.deepEqual( [ range.start, range.end ], [ 2, 5 ] );
	surface.setSelection( new ve.dm.LinearSelection( new ve.Range( 99, 100 ) ) );
	range = surface.getSelection().getRange();
	assert.deepEqual( [ range.start, range.end ], [ 5, 5 ] );
} );

QUnit.test( 'contextChange events', function ( assert ) {
	var surface = new ve.dm.SurfaceStub( ve.dm.example.preprocessAnnotations( [
		{ type: 'paragraph' },
		'F', 'o', 'o',
		// Bold "bar"
		[ 'b', [ ve.dm.example.bold ] ],
		[ 'a', [ ve.dm.example.bold ] ],
		[ 'r', [ ve.dm.example.bold ] ],
		// Italic "baz"
		[ 'b', [ ve.dm.example.italic ] ],
		[ 'a', [ ve.dm.example.italic ] ],
		[ 'z', [ ve.dm.example.italic ] ],
		'F', 'o', 'o',
		{ type: '/paragraph' },
		{ type: 'list', attributes: { style: 'bullet' } },
		{ type: 'listItem' },
		{ type: 'paragraph' },
		'O', 'n', 'e',
		{ type: '/paragraph' },
		{ type: '/listItem' },
		{ type: 'listItem' },
		{ type: 'paragraph' },
		'T', 'w', 'o',
		{ type: '/paragraph' },
		{ type: '/listItem' },
		{ type: '/list' },
		{ type: 'internalList' }, { type: '/internalList' }
	] ) );

	var contextChanges = 0;
	surface.on( 'contextChange', function () {
		contextChanges++;
	} );

	var cases = [
		{
			title: 'setSelection, to equivalent selection',
			initialSelection: new ve.Range( 1 ),
			selection: new ve.Range( 1 ),
			expected: 0
		},
		{
			title: 'setSelection, to equivalent expanded selection',
			initialSelection: new ve.Range( 1, 2 ),
			selection: new ve.Range( 1, 2 ),
			expected: 0
		},
		{
			title: 'setSelection, move within plain text',
			initialSelection: new ve.Range( 1 ),
			selection: new ve.Range( 2 ),
			expected: 0
		},
		{
			// Going from collapsed to not is a context change
			title: 'setSelection, expands over plain text',
			initialSelection: new ve.Range( 1 ),
			selection: new ve.Range( 1, 2 ),
			expected: 1
		},
		{
			title: 'setSelection, collapses',
			initialSelection: new ve.Range( 1, 2 ),
			selection: new ve.Range( 1 ),
			expected: 1
		},
		{
			title: 'setSelection, non-collapsed selection expands over plain text',
			initialSelection: new ve.Range( 1, 2 ),
			selection: new ve.Range( 1, 3 ),
			expected: 0
		},
		{
			title: 'setSelection, non-collapsed selection expands into annotated text',
			initialSelection: new ve.Range( 1, 2 ),
			selection: new ve.Range( 1, 5 ),
			expected: 1
		},
		{
			title: 'setSelection, non-collapsed selection expands into annotated text, other direction',
			initialSelection: new ve.Range( 12, 11 ),
			selection: new ve.Range( 12, 8 ),
			expected: 2 // Move + insertion annotations change
		},
		{
			title: 'setSelection, move collapsed selection to edge of annotated text',
			initialSelection: new ve.Range( 2 ),
			selection: new ve.Range( 4 ),
			expected: 1 // Move + no insertion annotations change
		},
		{
			title: 'setSelection, move collapsed selection to within annotated text',
			initialSelection: new ve.Range( 2 ),
			selection: new ve.Range( 5 ),
			expected: 2 // Move + insertion annotations change
		},
		{
			title: 'setSelection, selection collapsed in end node (but overally non-collapsed) becomes non-collapsed in end node',
			initialSelection: new ve.Range( 18, 24 ),
			selection: new ve.Range( 18, 26 ),
			expected: 1 // Move + insertion annotations change
		}
	];

	cases.forEach( function ( caseItem ) {
		surface.setLinearSelection( caseItem.initialSelection || new ve.Range( 1 ) );
		contextChanges = 0;
		surface.setLinearSelection( caseItem.selection );
		assert.strictEqual( contextChanges, caseItem.expected, caseItem.title );
	} );
} );

QUnit.test( 'documentUpdate/select events', function ( assert ) {
	var surface = new ve.dm.SurfaceStub(),
		doc = surface.getDocument(),
		// docmentUpdate doesn't fire for no-op transactions, so make sure there's something there
		tx = ve.dm.TransactionBuilder.static.newFromInsertion( doc, 3, [ 'i' ] ),
		events = {
			documentUpdate: 0,
			select: 0
		};

	surface.on( 'documentUpdate', function () {
		events.documentUpdate++;
	} );
	surface.on( 'select', function () {
		events.select++;
	} );
	surface.change( tx.clone() );
	assert.deepEqual( events, { documentUpdate: 1, select: 0 }, 'change with transaction only' );
	surface.setLinearSelection( new ve.Range( 2 ) );
	assert.deepEqual( events, { documentUpdate: 1, select: 1 }, 'setSelection' );
	surface.change( tx.clone(), new ve.dm.LinearSelection( new ve.Range( 3 ) ) );
	assert.deepEqual( events, { documentUpdate: 2, select: 2 }, 'change with transaction and selection' );
} );

QUnit.test( 'breakpoint/undo/redo', function ( assert ) {
	var range = new ve.Range( 1, 3 ),
		surface = new ve.dm.SurfaceStub( null, range ),
		fragment = surface.getFragment(),
		doc = surface.getDocument(),
		selection = new ve.dm.LinearSelection( range ),
		tx = ve.dm.TransactionBuilder.static.newFromInsertion( doc, 1, [ 'x' ] );

	assert.strictEqual( surface.breakpoint(), false, 'Returns false if no transactions applied' );

	surface.change( tx );
	assert.deepEqual( surface.undoStack, [], 'Undo stack data matches before breakpoint' );
	assert.deepEqual( surface.newTransactions, [ tx ], 'New transactions match before breakpoint' );

	assert.strictEqual( surface.breakpoint(), true, 'Returns true after transaction applied' );
	assert.strictEqual( surface.breakpoint(), false, 'Returns false if no transactions applied since last breakpoint' );

	assert.deepEqual(
		surface.undoStack, [ {
			start: 0,
			transactions: [ tx ],
			selection: new ve.dm.LinearSelection( tx.translateRange( selection.getRange() ) ),
			selectionBefore: selection
		} ],
		'Undo stack data matches after breakpoint'
	);
	assert.deepEqual( surface.newTransactions, [], 'New transactions empty after breakpoint' );

	assert.equalHash( surface.getSelection(), fragment.getSelection(), 'Range changed' );
	// Dirty selection to make sure beforeSelection works
	surface.setLinearSelection( new ve.Range( 3 ) );
	surface.undo();
	assert.equalHash( surface.getSelection().getRange(), range, 'Range restored after undo' );
	assert.strictEqual( fragment.getText(), 'hi', 'Text restored after undo' );

	surface.setLinearSelection( new ve.Range( 3 ) );
	surface.redo();
	assert.equalHash( surface.getSelection(), fragment.getSelection(), 'Range changed after redo' );
	assert.strictEqual( fragment.getText(), 'xhi', 'Text changed after redo' );

} );

QUnit.test( 'multi-user undo', function ( assert ) {
	var surfaces = [];

	// Create two surfaces owned by authors 1 & 2, consisting of interleaved
	// transactions by both users, each adding to their own paragraph
	for ( var i = 1; i <= 2; i++ ) {
		var range = new ve.Range( 1 );
		var surface = new ve.dm.SurfaceStub( [
			{ type: 'paragraph' }, { type: '/paragraph' },
			{ type: 'paragraph' }, { type: '/paragraph' },
			{ type: 'internalList' }, { type: '/internalList' }
		], range );
		surface.setAuthorId( i );
		surface.setMultiUser( true );

		var doc = surface.getDocument();

		var tx = ve.dm.TransactionBuilder.static.newFromInsertion( doc, 1, 'foo'.split( '' ) );
		tx.authorId = 1;
		surface.change( tx );
		surface.breakpoint();

		tx = ve.dm.TransactionBuilder.static.newFromInsertion( doc, 6, '123'.split( '' ) );
		tx.authorId = 2;
		surface.change( tx );
		surface.breakpoint();

		tx = ve.dm.TransactionBuilder.static.newFromInsertion( doc, 4, 'bar'.split( '' ) );
		tx.authorId = 1;
		surface.change( tx );
		surface.breakpoint();

		tx = ve.dm.TransactionBuilder.static.newFromInsertion( doc, 12, '456'.split( '' ) );
		tx.authorId = 2;
		surface.change( tx );
		surface.breakpoint();

		surfaces.push( surface );
	}

	// User on surface 1 presses undo twice, reverting only their changes
	surfaces[ 0 ].undo();
	surfaces[ 0 ].undo();
	assert.equalLinearData(
		surfaces[ 0 ].getDocument().getData(),
		[
			{ type: 'paragraph' }, { type: '/paragraph' },
			{ type: 'paragraph' }, '1', '2', '3', '4', '5', '6', { type: '/paragraph' },
			{ type: 'internalList' }, { type: '/internalList' }
		]
	);
	assert.strictEqual( surfaces[ 0 ].canUndo(), false, 'No more steps for user on surface 1 to undo' );

	// User on surface 2 presses undo twice, reverting only their changes
	surfaces[ 1 ].undo();
	surfaces[ 1 ].undo();
	assert.equalLinearData(
		surfaces[ 1 ].getDocument().getData(),
		[
			{ type: 'paragraph' }, 'f', 'o', 'o', 'b', 'a', 'r', { type: '/paragraph' },
			{ type: 'paragraph' }, { type: '/paragraph' },
			{ type: 'internalList' }, { type: '/internalList' }
		]
	);
	// TODO: We should disable undo as soon as the user runs out of transactions of their own
	assert.strictEqual( surfaces[ 1 ].canUndo(), true, 'User on surface 2 thinks they can still undo' );
	surfaces[ 1 ].undo();
	assert.strictEqual( surfaces[ 1 ].canUndo(), false, 'User on surface 2 realises they can\'t undo' );
	// Count complete history: 4 transactions + 2 undos
	assert.strictEqual( surfaces[ 1 ].getDocument().getCompleteHistoryLength(), 6, 'Final undo was a no-op' );

} );

QUnit.test( 'change rollback', function ( assert ) {
	var range = new ve.Range( 1, 3 ),
		surface = new ve.dm.SurfaceStub( null, range ),
		doc = surface.getDocument();

	var tx = ve.dm.TransactionBuilder.static.newFromInsertion( doc, 1, [ { type: '/heading' } ] );
	assert.throws(
		function () {
			surface.change( tx );
		},
		new Error( 'Expected closing for paragraph but got closing for heading' ),
		'Transaction throws an exception'
	);

	assert.strictEqual( surface.canUndo(), false, 'No history to undo after failed change' );
} );

QUnit.test( 'range translation', function ( assert ) {
	var surface = new ve.dm.SurfaceStub( null, new ve.Range( 3 ) ),
		doc = surface.getDocument(),
		tx = ve.dm.TransactionBuilder.static.newFromInsertion( doc, 3, [ 'x' ] );
	surface.change( tx );
	var selection = surface.getSelection();
	assert.true( selection instanceof ve.dm.LinearSelection, 'Selection is linear' );
	assert.equalRange( selection.getRange(), new ve.Range( 3 ), 'Cursor is unmoved' );
} );

QUnit.test( 'staging', function ( assert ) {
	var surface = new ve.dm.SurfaceStub( null, new ve.Range( 1, 3 ) ),
		fragment = surface.getFragment(),
		doc = surface.getDocument();

	assert.strictEqual( surface.isStaging(), false, 'isStaging false when not staging' );
	assert.strictEqual( surface.getStagingTransactions(), undefined, 'getStagingTransactions undefined when not staging' );
	assert.strictEqual( surface.doesStagingAllowUndo(), undefined, 'doesStagingAllowUndo undefined when not staging' );
	assert.equalHash( surface.getSelection(), fragment.getSelection(), 'Surface range matches fragment range' );

	surface.change( ve.dm.TransactionBuilder.static.newFromInsertion( doc, 1, [ 'a' ] ) );

	surface.pushStaging();
	assert.strictEqual( surface.isStaging(), true, 'isStaging true after pushStaging' );
	assert.deepEqual( surface.getStagingTransactions(), [], 'getStagingTransactions empty array after pushStaging' );
	assert.strictEqual( surface.doesStagingAllowUndo(), false, 'doesStagingAllowUndo false when staging without undo' );

	var tx1 = ve.dm.TransactionBuilder.static.newFromInsertion( doc, 2, [ 'b' ] );
	surface.change( tx1 );

	assert.strictEqual( fragment.getText(), 'abhi', 'document contents match after first transaction' );
	assert.deepEqual( surface.getStagingTransactions(), [ tx1 ], 'getStagingTransactions contains first transaction after change' );
	assert.equalHash( surface.getSelection(), fragment.getSelection(), 'Surface selection matches fragment range' );

	surface.pushStaging( true );
	assert.strictEqual( surface.isStaging(), true, 'isStaging true after nested pushStaging' );
	assert.deepEqual( surface.getStagingTransactions(), [], 'getStagingTransactions empty array after nested pushStaging' );
	assert.strictEqual( surface.doesStagingAllowUndo(), true, 'doesStagingAllowUndo true when staging with undo' );
	assert.equalHash( surface.getSelection(), fragment.getSelection(), 'Surface selection matches fragment range' );

	var tx2 = ve.dm.TransactionBuilder.static.newFromInsertion( doc, 3, [ 'c' ] );
	surface.change( tx2 );

	assert.strictEqual( fragment.getText(), 'abchi', 'document contents match after second transaction' );
	assert.deepEqual( surface.getStagingTransactions(), [ tx2 ], 'getStagingTransactions contains second transaction after change in nested staging' );

	assert.deepEqual( surface.popStaging(), [ tx2 ], 'popStaging returns second transaction list' );
	assert.strictEqual( surface.isStaging(), true, 'isStaging true after nested popStaging' );
	assert.strictEqual( fragment.getText(), 'abhi', 'document contents match after nested popStaging' );
	assert.equalHash( surface.getSelection(), fragment.getSelection(), 'Surface selection matches fragment range' );

	assert.deepEqual( surface.popStaging(), [ tx1 ], 'popStaging returns first transaction list' );
	assert.strictEqual( surface.isStaging(), false, 'isStaging false after outer popStaging' );
	assert.strictEqual( fragment.getText(), 'ahi', 'document contents match after outer popStaging' );
	assert.equalHash( surface.getSelection(), fragment.getSelection(), 'Surface selection matches fragment range' );

	surface.pushStaging();
	tx1 = ve.dm.TransactionBuilder.static.newFromInsertion( doc, 2, [ 'b' ] );
	surface.change( tx1 );

	surface.pushStaging();
	tx2 = ve.dm.TransactionBuilder.static.newFromInsertion( doc, 3, [ 'c' ] );
	surface.change( tx2 );

	assert.deepEqual( surface.popAllStaging(), [ tx1, tx2 ], 'popAllStaging returns full transaction list' );
	assert.strictEqual( fragment.getText(), 'ahi', 'document contents match after outer clearStaging' );
	assert.equalHash( surface.getSelection(), fragment.getSelection(), 'Surface selection matches fragment range' );

	surface.pushStaging();
	tx1 = ve.dm.TransactionBuilder.static.newFromInsertion( doc, 2, [ 'b' ] );
	surface.change( tx1 );

	surface.pushStaging();
	tx2 = ve.dm.TransactionBuilder.static.newFromInsertion( doc, 3, [ 'c' ] );
	surface.change( tx2 );

	surface.applyStaging();
	assert.deepEqual( surface.getStagingTransactions(), [ tx1, tx2 ], 'applyStaging merges transactions' );

	surface.applyStaging();
	assert.strictEqual( surface.isStaging(), false, 'isStaging false after outer applyStaging' );
	assert.strictEqual( fragment.getText(), 'abchi', 'document contents changed after applyStaging' );
	assert.equalHash( surface.getSelection(), fragment.getSelection(), 'Surface selection matches fragment range' );

	surface.pushStaging();
	tx1 = ve.dm.TransactionBuilder.static.newFromInsertion( doc, 4, [ 'd' ] );
	surface.change( tx1 );

	surface.pushStaging();
	tx2 = ve.dm.TransactionBuilder.static.newFromInsertion( doc, 5, [ 'e' ] );
	surface.change( tx2 );

	surface.applyAllStaging();
	assert.strictEqual( surface.isStaging(), false, 'isStaging false after outer applyAllStaging' );
	assert.strictEqual( fragment.getText(), 'abcdehi', 'document contents changed after applyAllStaging' );
	assert.equalHash( surface.getSelection(), fragment.getSelection(), 'Surface selection matches fragment range' );

	surface.undo();
	assert.strictEqual( fragment.getText(), 'abchi', 'document contents changed after undo' );
	assert.equalHash( surface.getSelection(), fragment.getSelection(), 'Surface selection matches fragment range' );

	surface.pushStaging();
	surface.pushStaging();
	// Apply transaction at second level, first level is empty and has no selctionBefore
	tx1 = ve.dm.TransactionBuilder.static.newFromInsertion( doc, 4, [ 'd' ] );
	surface.change( tx1 );
	surface.applyAllStaging();
	// Dirty selection
	surface.setLinearSelection( new ve.Range( 1 ) );
	// Undo should restore the selection from the second level's selectionBefore
	surface.undo();

	assert.equalHash( surface.getSelection(), fragment.getSelection(), 'Surface selection matches fragment range' );

} );

QUnit.test( 'getOffsetFromSourceOffset / getSourceOffsetFromOffset / getRangeFromSourceOffsets', function ( assert ) {
	var i,
		surface = new ve.dm.SurfaceStub( [
			{ type: 'paragraph' }, 'f', 'o', 'o', { type: '/paragraph' },
			{ type: 'paragraph' }, 'b', 'a', { type: '/paragraph' },
			{ type: 'paragraph' }, 'q', 'u', 'u', 'x', { type: '/paragraph' },
			{ type: 'internalList' }, { type: '/internalList' }
		] ),
		expectedOffsets = [
			1, 2, 3, 4,
			6, 7, 8,
			10, 11, 12, 13, 14
		],
		expectedSourceOffsets = [
			0, 0, 1, 2, 3,
			4, 4, 5, 6,
			7, 7, 8, 9, 10, 11,
			12
		];

	for ( i = 0; i < expectedOffsets.length; i++ ) {
		assert.strictEqual( surface.getOffsetFromSourceOffset( i ), expectedOffsets[ i ], 'Correct offset at ' + i );
	}
	assert.throws(
		function () {
			surface.getOffsetFromSourceOffset( -1 );
		},
		Error, 'Offset -1 is out of bounds' );
	assert.throws(
		function () {
			surface.getOffsetFromSourceOffset( expectedOffsets.length );
		},
		Error, 'Offset ' + expectedOffsets.length + ' is out of bounds'
	);
	for ( i = 0; i < expectedSourceOffsets.length; i++ ) {
		assert.strictEqual( surface.getSourceOffsetFromOffset( i ), expectedSourceOffsets[ i ], 'Correct source offset at ' + i );
	}
	assert.throws(
		function () {
			surface.getSourceOffsetFromOffset( -1 );
		},
		Error, 'Offset -1 is out of bounds' );
	assert.throws(
		function () {
			surface.getSourceOffsetFromOffset( expectedSourceOffsets.length );
		},
		Error, 'Offset ' + expectedSourceOffsets.length + ' is out of bounds'
	);

	assert.equalRange( surface.getRangeFromSourceOffsets( 1, 5 ), new ve.Range( 2, 7 ), 'Simple forwards range' );
	assert.equalRange( surface.getRangeFromSourceOffsets( 6, 2 ), new ve.Range( 8, 3 ), 'Simple backwards range' );
	assert.equalRange( surface.getRangeFromSourceOffsets( 7, 7 ), new ve.Range( 10 ), 'Collapsed range (2 args)' );
	assert.equalRange( surface.getRangeFromSourceOffsets( 8 ), new ve.Range( 11 ), 'Collapsed range (1 arg)' );
} );

QUnit.test( 'autosave', function ( assert ) {
	var storage = ve.init.platform.sessionStorage,
		autosaveFailed = 0,
		done = assert.async(),
		surface = new ve.dm.SurfaceStub(),
		fragment = surface.getLinearFragment( new ve.Range( 3 ) ),
		state = {
			name: 'name',
			id: 1
		};

	assert.strictEqual( surface.restoreChanges(), false, 'restoreChanges returns false when nothing to restore' );
	assert.strictEqual( surface.storeDocState( state, '<p>foo</p>' ), true, 'storeDocState returns true' );
	assert.deepEqual( storage.getObject( 've-docstate' ), state, 'storeDocState writes doc state to session storage' );
	assert.strictEqual( storage.get( 've-dochtml' ), '<p>foo</p>', 'storeDocState writes custom HTML to session storage' );
	surface.storeDocState( state, '' );
	assert.strictEqual( storage.get( 've-dochtml' ), '', 'storeDocState can set HTML to empty string' );
	surface.storeDocState();
	assert.strictEqual( storage.get( 've-dochtml' ), '<p>hi</p>', 'storeDocState writes current HTML to session storage' );
	assert.strictEqual( storage.getObject( 've-docstate' ), null, 'docstate is empty if not provided' );

	fragment.insertContent( ' bar' );
	surface.breakpoint();
	assert.deepEqual( storage.getObject( 've-changes' ), null, 'Changes aren\'t stored before startStoringChanges' );

	surface = new ve.dm.SurfaceStub();
	fragment = surface.getLinearFragment( new ve.Range( 3 ) );
	surface.startStoringChanges();
	surface.storeDocState( state );
	fragment.insertContent( ' bar' );
	surface.breakpoint();
	assert.deepEqual(
		storage.getObject( 've-changes' ),
		[ {
			start: 0,
			transactions: [ [ 3, [ '', ' bar' ], 3 ] ]
		} ],
		'First change stored'
	);

	surface.storeChanges();
	assert.strictEqual( storage.getObject( 've-changes' ).length, 1, 'No extra change stored if no changes since last store' );

	fragment.convertNodes( 'heading', { level: 1 } );
	surface.breakpoint();
	assert.deepEqual(
		storage.getObject( 've-changes' )[ 1 ],
		{
			start: 1,
			transactions: [ [
				[ [ { type: 'paragraph' } ], [ { type: 'heading', attributes: { level: 1 } } ] ],
				6,
				[ [ { type: '/paragraph' } ], [ { type: '/heading' } ] ],
				2
			] ]
		},
		'Second change stored'
	);
	fragment.collapseToEnd().insertContent( ' baz' );
	surface.setLinearSelection( new ve.Range( 5 ) );
	surface.breakpoint();
	assert.strictEqual(
		storage.getObject( 've-changes' ).length, 3, 'Fourth change stored'
	);
	assert.deepEqual(
		storage.getObject( 've-selection' ),
		{ type: 'linear', range: { type: 'range', from: 5, to: 5 } },
		'Selection state stored'
	);

	surface.stopStoringChanges();
	fragment.collapseToEnd().insertContent( ' quux' );
	surface.breakpoint();
	assert.strictEqual(
		storage.getObject( 've-changes' ).length, 3, 'Change not stored after stopStoringChanges'
	);

	assert.throws(
		function () {
			surface.documentModel.completeHistory = new ve.dm.Change();
			surface.restoreChanges();
		},
		/Failed to restore/,
		'Calling restoreChanges on the wrong document state throws "Failed to restore…" error'
	);

	surface = new ve.dm.SurfaceStub();
	fragment = null;
	assert.strictEqual( surface.getHtml(), '<p>hi</p>', 'Document HTML before restoreChanges' );
	assert.strictEqual( surface.restoreChanges(), true, 'restoreChanges returns true on success' );
	assert.strictEqual( surface.getHtml(), '<h1>hi bar baz</h1>', 'Document HTML restored' );
	assert.strictEqual( surface.getDocument().getCompleteHistoryLength(), 3, 'Document history restored' );
	setTimeout( function ( s ) {
		assert.equalHash( s.getSelection(), { type: 'linear', range: { type: 'range', from: 5, to: 5 } }, 'Document selection restored (async)' );
		s.undo();
		assert.equalHash( s.getSelection(), { type: 'linear', range: { type: 'range', from: 7, to: 7 } }, 'Document selection guessed after undo' );
		done();
	}.bind( this, surface ) );

	ve.init.platform.storageDisabled = true;
	assert.strictEqual( surface.restoreChanges(), false, 'restoreChanges returns false if session storage disabled' );
	ve.init.platform.storageDisabled = false;

	surface.removeDocStateAndChanges();
	assert.strictEqual( storage.get( 've-html' ), null, 'HTML empty after removeDocStateAndChanges' );
	assert.strictEqual( storage.getObject( 've-docstate' ), null, 'Doc state empty after removeDocStateAndChanges' );
	assert.deepEqual( storage.getObject( 've-changes' ), null, 'Changes empty after removeDocStateAndChanges' );

	surface = new ve.dm.SurfaceStub();
	fragment = surface.getLinearFragment( new ve.Range( 3 ) );
	surface.startStoringChanges();
	// Pass magic string to only fail when writing HTML
	assert.strictEqual( surface.storeDocState( state, '__FAIL__' ), false, 'storeDocState returns false when HTML can\'t be stored' );
	assert.strictEqual( storage.getObject( 've-docstate' ), null, 'docstate is wiped if HTML storage failed' );

	ve.init.platform.storageDisabled = true;
	surface = new ve.dm.SurfaceStub();
	fragment = surface.getLinearFragment( new ve.Range( 3 ) );
	surface.startStoringChanges();
	assert.strictEqual( surface.storeDocState( state ), false, 'storeDocState returns false when sessionStorage disabled' );
	fragment.insertContent( ' bar' );
	surface.breakpoint();
	assert.strictEqual( storage.getObject( 've-changes' ), false, 'No changes recorded' );
	ve.init.platform.storageDisabled = false;

	surface.on( 'autosaveFailed', function () {
		autosaveFailed++;
	} );
	surface.startStoringChanges();
	surface.storeDocState( state );
	ve.init.platform.storageDisabled = true;
	assert.strictEqual( autosaveFailed, 0, 'Autosave hasn\'t failed before first change' );
	fragment.insertContent( ' bar' );
	surface.breakpoint();
	assert.strictEqual( autosaveFailed, 1, 'Autosave fails after first change' );
	fragment.insertContent( ' baz' );
	surface.breakpoint();
	assert.strictEqual( autosaveFailed, 1, 'Subsequent failures don\'t fire autosaveFailed again' );
	assert.strictEqual( storage.getObject( 've-changes' ), false, 'No changes recorded' );
	ve.init.platform.storageDisabled = false;

	surface.storeDocState( state, '<p>foo</p>' );
	// This allows callers to call storeDocState after startStoringChanges, e.g. after the first transaction
	assert.strictEqual( surface.lastStoredChange, 1, 'storeDocState with custom HTML doesn\'t advance the lastStoredChange pointer' );
	surface.storeDocState( state );
	assert.strictEqual( surface.lastStoredChange, 5, 'storeDocState without custom HTML advances the lastStoredChange pointer' );

} );

// TODO: ve.dm.Surface#getHistory
// TODO: ve.dm.Surface#canRedo
// TODO: ve.dm.Surface#canUndo
// TODO: ve.dm.Surface#hasBeenModified
// TODO: ve.dm.Surface#truncateUndoStack
