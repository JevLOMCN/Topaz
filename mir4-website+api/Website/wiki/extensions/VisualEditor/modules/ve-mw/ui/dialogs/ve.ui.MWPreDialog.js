/*!
 * VisualEditor user interface MWPreDialog class.
 *
 * @copyright See AUTHORS.txt
 * @license The MIT License (MIT); see LICENSE.txt
 */

/**
 * Dialog for editing MediaWiki preformatted text using `<pre>` tags.
 *
 * @class
 * @extends ve.ui.MWExtensionDialog
 *
 * @constructor
 * @param {Object} [config] Configuration options
 */
ve.ui.MWPreDialog = function VeUiMWPreDialog() {
	// Parent constructor
	ve.ui.MWPreDialog.super.apply( this, arguments );

	this.$element.addClass( 've-ui-mwPreDialog' );
};

/* Inheritance */

OO.inheritClass( ve.ui.MWPreDialog, ve.ui.MWExtensionDialog );

/* Static properties */

ve.ui.MWPreDialog.static.name = 'mwPre';

ve.ui.MWPreDialog.static.size = 'large';

ve.ui.MWPreDialog.static.title = OO.ui.deferMsg( 'visualeditor-mwpredialog-title' );

ve.ui.MWPreDialog.static.modelClasses = [ ve.dm.MWPreNode ];

ve.ui.MWPreDialog.static.actions = ve.ui.MWPreDialog.super.static.actions.concat( [
	{
		action: 'convert',
		label: OO.ui.deferMsg( 'visualeditor-mwpredialog-convert' ),
		modes: [ 'edit' ]
	}
] );

/* Methods */

/**
 * @inheritdoc
 */
ve.ui.MWPreDialog.prototype.initialize = function () {
	// Parent method
	ve.ui.MWPreDialog.super.prototype.initialize.call( this );

	// Properties
	this.panel = new OO.ui.PanelLayout( {
		padded: true
	} );
	// Note that this overrides this.input from ve.ui.MWExtensionWindow
	this.input = new ve.ui.MWPreTextInputWidget( {
		// This number doesn't really matter, it just needs to be large.
		// The real height is enforced by #getBodyHeight and max-height in CSS.
		rows: 100,
		classes: [ 've-ui-mwExtensionWindow-input' ]
	} );
	this.input.connect( this, { resize: 'updateSize' } );

	// Initialization
	this.$element.addClass( 've-ui-mwPreDialog' );
	this.panel.$element.append( this.input.$element );
	this.$body.append( this.panel.$element );
};

/**
 * @inheritdoc
 */
ve.ui.MWPreDialog.prototype.getReadyProcess = function ( data ) {
	return ve.ui.MWPreDialog.super.prototype.getReadyProcess.call( this, data )
		.next( function () {
			this.input.focus();
		}, this );
};

/**
 * @inheritdoc
 */
ve.ui.MWPreDialog.prototype.getActionProcess = function ( action ) {
	if ( action === 'convert' ) {
		return new OO.ui.Process( function () {
			var
				value = this.input.getValue(),
				nodeRange = this.selectedNode.getOuterRange(),
				surfaceModel = this.getFragment().getSurface(),
				doc = surfaceModel.getDocument(),
				// Turn the text into content for insertion into document
				content = value.split( '' ),
				// Apply any annotations that were applied to the node also to the new content
				annotations = doc.data.getAnnotationsFromOffset( nodeRange.start );
			ve.dm.Document.static.addAnnotationsToData( content, annotations );
			// Make it use preformatted text
			content.unshift( { type: 'mwPreformatted' } );
			content.push( { type: '/mwPreformatted' } );
			// Replace
			surfaceModel.change(
				ve.dm.TransactionBuilder.static.newFromReplacement( doc, nodeRange, content )
			);
			this.close();
		}, this );
	}
	// Parent method
	return ve.ui.MWPreDialog.super.prototype.getActionProcess.call( this, action );
};

/**
 * @inheritdoc
 */
ve.ui.MWPreDialog.prototype.getBodyHeight = function () {
	return 500;
};

/* Registration */

ve.ui.windowFactory.register( ve.ui.MWPreDialog );
