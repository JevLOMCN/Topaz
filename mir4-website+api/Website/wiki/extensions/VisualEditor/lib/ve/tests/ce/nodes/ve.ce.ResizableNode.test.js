/*!
 * VisualEditor ContentEditable ResizableNode tests.
 *
 * @copyright See AUTHORS.txt
 */

QUnit.module( 've.ce.ResizableNode' );

/* Tests */

QUnit.test( 'updateSizeLabel', function ( assert ) {
	var view = ve.test.utils.createSurfaceViewFromHtml( ve.dm.example.blockImage.html ),
		documentNode = view.getDocument().getDocumentNode(),
		resizableNode = documentNode.children[ 0 ];

	resizableNode.setOriginalDimensions( {
		width: 100,
		height: 30
	} );
	resizableNode.updateSizeLabel();

	// Position is too fragile to assert
	resizableNode.$sizeLabel.css( { top: '', left: '' } );
	assert.equalDomElement(
		resizableNode.$sizeLabel[ 0 ],
		$( ve.dm.example.singleLine`
			<div class="ve-ce-resizableNode-sizeLabel ve-ce-resizableNode-sizeLabel-resizing" style="width: 100px; height: 30px; line-height: 30px;">
				<span class="ve-ce-resizableNode-sizeText">
					<span class="ve-ce-resizableNode-sizeText-size">100 × 50</span>
					<span class="ve-ce-resizableNode-sizeText-scale">100%</span>
				</span>
			</div>
		` )[ 0 ],
		'Size label'
	);

	view.destroy();
} );

QUnit.test( 'resize events', function ( assert ) {
	var view = ve.test.utils.createSurfaceViewFromHtml( ve.dm.example.image.html ),
		documentNode = view.getDocument().getDocumentNode(),
		resizableNode = documentNode.children[ 0 ].children[ 0 ],
		mockEvent = {
			preventDefault: function () {}
		};

	// Focusing the resizable node triggers the display of the handles. Thus,
	// everything after this point takes as granted that there's a selection
	// covering the node. Make that happen:
	resizableNode.resizableSurface.getModel().setSelection(
		new ve.dm.LinearSelection( resizableNode.getOuterRange() )
	);

	// Sets up the handles
	resizableNode.onResizableFocus();

	resizableNode.onResizeHandlesCornerMouseDown( ve.extendObject( mockEvent, {
		screenX: 80,
		screenY: 100,
		target: resizableNode.$resizeHandles.find( '.ve-ce-resizableNode-seHandle' )[ 0 ]
	} ) );

	assert.deepEqual(
		resizableNode.resizeInfo,
		{
			mouseX: 80,
			mouseY: 100,
			top: 0,
			left: 0,
			width: 100,
			height: 50,
			handle: 'se'
		},
		'resizeInfo calculated'
	);
	assert.strictEqual( resizableNode.resizing, true, 'node is resizing' );
	assert.strictEqual( resizableNode.root.getSurface().resizing, true, 'surface is resizing' );

	resizableNode.onDocumentMouseMove( ve.extendObject( mockEvent, {
		screenX: 100, // +20
		screenY: 150, // +50
		shiftKey: false
	} ) );

	// Handles sized while keeping ratio, rather than directly how the cursor has moved:
	assert.strictEqual( resizableNode.$resizeHandles.css( 'width' ), '120px', 'handles sized: width' );
	assert.strictEqual( resizableNode.$resizeHandles.css( 'height' ), '60px', 'handles sizer: height' );

	resizableNode.onDocumentMouseUp( ve.extendObject( mockEvent, {
		screenX: 100, // +20
		screenY: 150, // +50
		shiftKey: false
	} ) );

	assert.strictEqual( resizableNode.resizing, false, 'node is no longer resizing' );
	assert.strictEqual( resizableNode.root.getSurface().resizing, false, 'surface is no longer resizing' );

	assert.deepEqual(
		resizableNode.getModel().getCurrentDimensions(),
		{
			width: 120,
			height: 60
		},
		'dimensions updated'
	);
} );

QUnit.test( 'notResizable', function ( assert ) {
	var view = ve.test.utils.createSurfaceViewFromHtml( ve.dm.example.image.html ),
		documentNode = view.getDocument().getDocumentNode(),
		resizableNode = documentNode.children[ 0 ].children[ 0 ],
		isMobile = OO.ui.isMobile;

	// Sizing is disabled on mobile:
	OO.ui.isMobile = function () {
		return true;
	};

	assert.strictEqual( resizableNode.isResizable(), false, 'Not resizable' );

	resizableNode.updateSizeLabel();

	assert.equalDomElement(
		resizableNode.$sizeLabel[ 0 ],
		$( '<div class="ve-ce-resizableNode-sizeLabel"><span class="ve-ce-resizableNode-sizeText"></span></div>' )[ 0 ],
		'Size label remains stubbed after update'
	);

	resizableNode.hideSizeLabel();
	assert.strictEqual( resizableNode.$sizeLabel.hasClass( 'oo-ui-element-hidden' ), false, 'Hiding size label doesn\'t apply hidden class' );

	resizableNode.showHandles( [ 'nw' ] );
	assert.strictEqual( resizableNode.$resizeHandles[ 0 ].className, 've-ce-resizableNode-handles', 'No resize handles hidden' );

	OO.ui.isMobile = isMobile;
} );
