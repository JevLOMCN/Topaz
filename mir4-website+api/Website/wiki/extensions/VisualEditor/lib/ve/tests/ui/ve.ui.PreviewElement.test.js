/*!
 * VisualEditor PreviewElement tests.
 *
 * @copyright See AUTHORS.txt
 */

QUnit.module( 've.ui.PreviewElement' );

/* Tests */

QUnit.test( 'Basic tests', function ( assert ) {
	var done = assert.async(),
		doc = ve.dm.example.createExampleDocument(),
		modelNode = doc.getDocumentNode().getChildren()[ 0 ];

	var simplePreview = new ve.ui.PreviewElement();
	assert.deepEqual( simplePreview.isGenerating(), false, 'not isGenerating if constructed without model' );
	simplePreview.setModel( modelNode );
	assert.strictEqual( simplePreview.model, modelNode, 'setModel' );
	assert.strictEqual( simplePreview.isGenerating(), true, 'isGenerating after setModel' );

	simplePreview.once( 'render', function () {
		assert.deepEqual( simplePreview.isGenerating(), false, 'not isGenerating after render' );
		assert.equalDomElement(
			simplePreview.$element[ 0 ],
			$.parseHTML( '<div class="ve-ui-previewElement"><h1>a<b>b</b><i>c</i></h1></div>' )[ 0 ],
			'Simple node render'
		);
		done();
	} );

	var useViewPreview = new ve.ui.PreviewElement( modelNode, { useView: true } );
	assert.strictEqual( useViewPreview.isGenerating(), true, 'isGenerating after construction with model' );
	useViewPreview.once( 'render', function () {
		assert.deepEqual( simplePreview.isGenerating(), false, 'not isGenerating after useView render' );
		assert.equalDomElement(
			simplePreview.$element[ 0 ],
			$.parseHTML( '<div class="ve-ui-previewElement"><h1>a<b>b</b><i>c</i></h1></div>' )[ 0 ],
			'Simple useView render'
		);
	} );

	// TODO test:
	// * awaitGeneratedContent branch (useView & !useView)
	// * attribute resolution in replaceWithModelDom
	// * targetLinksToNewWindow
} );
