/*!
 * VisualEditor UserInterface DataTransferHandlerFactory tests.
 *
 * @copyright See AUTHORS.txt
 */

QUnit.module( 've.ui.DataTransferHandlerFactory' );

/* Stubs */

ve.test.utils.makeStubTransferHandler = function ( name, handlesPaste, types, kinds, extensions ) {
	function StubHandler() {
		StubHandler.super.apply( this, arguments );
	}
	OO.inheritClass( StubHandler, extensions ? ve.ui.FileTransferHandler : ve.ui.DataTransferHandler );
	StubHandler.static.name = name;
	StubHandler.static.handlesPaste = !!handlesPaste;
	StubHandler.static.kinds = kinds;
	StubHandler.static.types = types || [];
	StubHandler.static.extensions = extensions;
	return StubHandler;
};

ve.test.utils.makeStubTransferItem = function ( type, kind, extension ) {
	return {
		type: type,
		kind: kind,
		getExtension: function () {
			return extension;
		}
	};
};

/* Tests */
QUnit.test( 'getHandlerNameForItem', function ( assert ) {
	var makeStubTransferHandler = ve.test.utils.makeStubTransferHandler,
		makeStubTransferItem = ve.test.utils.makeStubTransferItem,
		factory = new ve.ui.DataTransferHandlerFactory(),
		stubItemTypeHtml = makeStubTransferItem( 'text/html' ),
		stubItemFileHtml = makeStubTransferItem( 'text/html', 'file', 'html' ),
		stubItemStringHtml = makeStubTransferItem( 'text/html', 'string', 'html' ),
		stubItemExtHtml = makeStubTransferItem( null, null, 'html' ),
		stubItemProto = makeStubTransferItem( '__proto__', '__proto__', '__proto__' );

	var StubHandlerFileHtml1 = makeStubTransferHandler( 'filehtml1', true, [ 'text/html' ], [ 'file' ], [ 'html' ] );
	var StubHandlerFileHtml2 = makeStubTransferHandler( 'filehtml2', false, [ 'text/html' ], [ 'file' ], [ 'html' ] );
	var StubHandlerStringHtml = makeStubTransferHandler( 'stringhtml', false, [ 'text/html' ], [ 'string' ] );
	var StubHandlerHtml1 = makeStubTransferHandler( 'html1', true, [ 'text/html' ] );
	var StubHandlerHtml2 = makeStubTransferHandler( 'html2', false, [ 'text/html' ] );
	// The `html3` handler should never show up
	var StubHandlerHtml3 = makeStubTransferHandler( 'html3', true, [ 'text/html' ] );
	StubHandlerHtml3.static.matchFunction = function () {
		return false;
	};

	// The factory should start out empty and __proto__ shouldn't cause a crash
	assert.strictEqual( factory.getHandlerNameForItem( stubItemTypeHtml, false ), undefined, 'Empty factory shouldn\'t match by type' );
	assert.strictEqual( factory.getHandlerNameForItem( stubItemFileHtml, false ), undefined, 'Empty factory shouldn\'t match by kind' );
	assert.strictEqual( factory.getHandlerNameForItem( stubItemStringHtml, false ), undefined, 'Empty factory shouldn\'t match by kind (2)' );
	assert.strictEqual( factory.getHandlerNameForItem( stubItemExtHtml, false ), undefined, 'Empty factory shouldn\'t match by extension' );
	assert.strictEqual( factory.getHandlerNameForItem( stubItemProto, false ), undefined, 'Empty factory shouldn\'t crash on __proto__' );

	factory.register( StubHandlerFileHtml1 );
	factory.register( StubHandlerFileHtml2 );
	factory.register( StubHandlerStringHtml );
	factory.register( StubHandlerHtml1 );
	factory.register( StubHandlerHtml2 );
	factory.register( StubHandlerHtml3 );

	// Ensure that __proto__ doesn't cause a crash
	assert.strictEqual( factory.getHandlerNameForItem( stubItemProto, false ), undefined, 'Ensure that __proto__ doesn\'t cause a crash' );

	// 1. Match by kind + type
	assert.strictEqual( factory.getHandlerNameForItem( stubItemFileHtml, false ), 'filehtml2', 'Match by kind and type (unfiltered)' );
	assert.strictEqual( factory.getHandlerNameForItem( stubItemFileHtml, true ), 'filehtml1', 'Match by kind a type (filtered for paste)' );
	// 2. Match by just type (note that html3 doesn't show up)
	assert.strictEqual( factory.getHandlerNameForItem( stubItemTypeHtml, false ), 'html2', 'Match by type (unfiltered)' );
	assert.strictEqual( factory.getHandlerNameForItem( stubItemTypeHtml, true ), 'html1', 'Match by type (filtered for paste)' );
	// 3. Match by file extension
	assert.strictEqual( factory.getHandlerNameForItem( stubItemExtHtml, false ), 'filehtml2', 'Match by extension (unfiltered)' );
	assert.strictEqual( factory.getHandlerNameForItem( stubItemExtHtml, true ), 'filehtml1', 'Match by extension (filtered for paste)' );

	// Match by (1) kind and type, then fall through & match by (2) just type.
	assert.strictEqual( factory.getHandlerNameForItem( stubItemStringHtml, false ), 'stringhtml', 'Match by kind and type (unfiltered, take 2)' );
	assert.strictEqual( factory.getHandlerNameForItem( stubItemStringHtml, true ), 'html1', 'Fall through kind and type match after filter, match by just type' );
} );
