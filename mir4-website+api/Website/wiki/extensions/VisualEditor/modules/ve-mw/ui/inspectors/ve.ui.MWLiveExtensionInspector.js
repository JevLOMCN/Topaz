/*!
 * VisualEditor UserInterface MWLiveExtensionInspector class.
 *
 * @copyright See AUTHORS.txt
 * @license The MIT License (MIT); see LICENSE.txt
 */

/**
 * Inspector for editing generic MediaWiki extensions with dynamic rendering.
 *
 * @class
 * @abstract
 * @extends ve.ui.MWExtensionInspector
 *
 * @constructor
 * @param {Object} [config] Configuration options
 */
ve.ui.MWLiveExtensionInspector = function VeUiMWLiveExtensionInspector() {
	// Parent constructor
	ve.ui.MWLiveExtensionInspector.super.apply( this, arguments );

	this.updatePreviewDebounced = ve.debounce( this.updatePreview.bind( this ), 1000 );
};

/* Inheritance */

OO.inheritClass( ve.ui.MWLiveExtensionInspector, ve.ui.MWExtensionInspector );

/* Methods */

/**
 * @inheritdoc
 */
ve.ui.MWLiveExtensionInspector.prototype.initialize = function () {
	// Parent method
	ve.ui.MWLiveExtensionInspector.super.prototype.initialize.call( this );

	this.generatedContentsError = new ve.ui.MWExpandableErrorElement();
	this.form.$element.append( this.generatedContentsError.$element );
};

/**
 * @inheritdoc
 */
ve.ui.MWLiveExtensionInspector.prototype.getSetupProcess = function ( data ) {
	return ve.ui.MWLiveExtensionInspector.super.prototype.getSetupProcess.call( this, data )
		.next( function () {
			var element = this.getNewElement();
			// Initialization
			this.getFragment().getSurface().pushStaging();

			if ( !this.selectedNode ) {
				// Create a new node
				// collapseToEnd returns a new fragment
				this.fragment = this.getFragment().collapseToEnd().insertContent( [
					element,
					{ type: '/' + element.type }
				] );
				// Check if the node was inserted at a structural offset and
				// wrapped in a paragraph
				if ( this.getFragment().getSelection().getRange().getLength() === 4 ) {
					this.fragment = this.getFragment().adjustLinearSelection( 1, -1 );
				}
				this.getFragment().select();
				this.selectedNode = this.getFragment().getSelectedNode();
			}
			this.input.on( 'change', this.onChangeHandler );
			this.generatedContentsError.connect( this, {
				update: 'updateSize'
			} );
			this.selectedNode.connect( this, {
				generatedContentsError: 'showGeneratedContentsError'
			} );
		}, this );
};

/**
 * @inheritdoc
 */
ve.ui.MWLiveExtensionInspector.prototype.getTeardownProcess = function ( data ) {
	return ve.ui.MWLiveExtensionInspector.super.prototype.getTeardownProcess.call( this, data )
		.first( function () {
			this.input.off( 'change', this.onChangeHandler );
			this.generatedContentsError.clear();
			this.generatedContentsError.disconnect( this );
			this.selectedNode.disconnect( this );
			if ( data === undefined ) { // cancel
				this.getFragment().getSurface().popStaging();
			}
		}, this );
};

/**
 * @inheritdoc
 */
ve.ui.MWLiveExtensionInspector.prototype.insertOrUpdateNode = function () {
	// No need to call parent method as changes have already been made
	// to the model in staging, just need to apply them.
	this.updatePreview();
	this.getFragment().getSurface().applyStaging();
	// Force the selected node to re-render after staging has finished
	this.selectedNode.emit( 'update', false );
};

/**
 * @inheritdoc
 */
ve.ui.MWLiveExtensionInspector.prototype.removeNode = function () {
	this.getFragment().getSurface().popStaging();

	// Parent method
	ve.ui.MWLiveExtensionInspector.super.prototype.removeNode.call( this );
};

/**
 * @inheritdoc
 */
ve.ui.MWLiveExtensionInspector.prototype.onChange = function () {
	// Parent method
	ve.ui.MWLiveExtensionInspector.super.prototype.onChange.call( this );

	this.updatePreviewDebounced();
};

/**
 * Update the node rendering to reflect the current content in the inspector.
 */
ve.ui.MWLiveExtensionInspector.prototype.updatePreview = function () {
	if ( !this.selectedNode ) {
		// Method is called debounced, so selectedNode may not still exist
		return;
	}
	var mwData = ve.copy( this.selectedNode.getAttribute( 'mw' ) );

	this.updateMwData( mwData );

	this.hideGeneratedContentsError();

	if ( this.visible ) {
		this.getFragment().changeAttributes( { mw: mwData } );
	}
};

/**
 * Show the error container and set the error label to contain the error.
 *
 * @param {jQuery} $element Element containing the error
 */
ve.ui.MWLiveExtensionInspector.prototype.showGeneratedContentsError = function ( $element ) {
	this.generatedContentsError.show( this.formatGeneratedContentsError( $element ) );
};

/**
 * Hide the error and collapse the error container.
 */
ve.ui.MWLiveExtensionInspector.prototype.hideGeneratedContentsError = function () {
	this.generatedContentsError.clear();
};

/**
 * Format the error.
 *
 * Default behaviour returns the error with no modification.
 *
 * @param {jQuery} $element Element containing the error
 * @return {jQuery} $element Element containing the error
 */
ve.ui.MWLiveExtensionInspector.prototype.formatGeneratedContentsError = function ( $element ) {
	return $element;
};
