/*!
 * VisualEditor user interface DiffDialog class.
 *
 * @copyright See AUTHORS.txt
 */

/**
 * Dialog for displaying a diff.
 *
 * @class
 * @extends OO.ui.ProcessDialog
 *
 * @constructor
 * @param {Object} [config] Configuration options
 */
ve.ui.DiffDialog = function VeUiDiffDialog( config ) {
	// Parent constructor
	ve.ui.DiffDialog.super.call( this, config );
};

/* Inheritance */

OO.inheritClass( ve.ui.DiffDialog, OO.ui.ProcessDialog );

/* Static properties */

ve.ui.DiffDialog.static.name = 'diff';

ve.ui.DiffDialog.static.size = 'larger';

ve.ui.DiffDialog.static.title = 'Changes';

ve.ui.DiffDialog.static.actions = [
	{
		label: OO.ui.deferMsg( 'visualeditor-dialog-action-done' ),
		flags: [ 'safe', 'close' ]
	}
];

/* Methods */

/**
 * @inheritdoc
 */
ve.ui.DiffDialog.prototype.initialize = function () {
	// Parent method
	ve.ui.DiffDialog.super.prototype.initialize.apply( this, arguments );

	this.diffElement = null;

	this.content = new OO.ui.PanelLayout( {
		padded: true,
		expanded: false
	} );

	this.$body.append( this.content.$element );
};

/**
 * @inheritdoc
 */
ve.ui.DiffDialog.prototype.getSetupProcess = function ( data ) {
	return ve.ui.DiffDialog.super.prototype.getSetupProcess.call( this, data )
		.next( function () {
			this.diffElement = new ve.ui.DiffElement( new ve.dm.VisualDiff( data.oldDoc, data.newDoc ) );
			ve.targetLinksToNewWindow( this.diffElement.$document[ 0 ] );

			this.content.$element.append(
				this.diffElement.$element
			);
		}, this );
};

/**
 * @inheritdoc
 */
ve.ui.DiffDialog.prototype.getReadyProcess = function ( data ) {
	return ve.ui.DiffDialog.super.prototype.getReadyProcess.call( this, data )
		.next( function () {
			this.positionDiffElement();
		}, this );
};

/**
 * @inheritdoc
 */
ve.ui.DiffDialog.prototype.setDimensions = function () {
	// Parent method
	ve.ui.DiffDialog.super.prototype.setDimensions.apply( this, arguments );

	if ( !this.positioning ) {
		this.positionDiffElement();
	}
};

/**
 * Re-position elements within the diff element
 *
 * Should be called whenever the diff element's container has changed width.
 */
ve.ui.DiffDialog.prototype.positionDiffElement = function () {
	var dialog = this;
	setTimeout( function () {
		dialog.withoutSizeTransitions( function () {
			dialog.positioning = true;
			if ( dialog.diffElement && dialog.isVisible() ) {
				dialog.diffElement.positionDescriptions();
				dialog.updateSize();
			}
			dialog.positioning = false;
		} );
	}, OO.ui.theme.getDialogTransitionDuration() );
};

/**
 * @inheritdoc
 */
ve.ui.DiffDialog.prototype.getTeardownProcess = function ( data ) {
	return ve.ui.DiffDialog.super.prototype.getTeardownProcess.call( this, data )
		.next( function () {
			this.diffElement.$element.remove();
		}, this );
};

/* Registration */

ve.ui.windowFactory.register( ve.ui.DiffDialog );
