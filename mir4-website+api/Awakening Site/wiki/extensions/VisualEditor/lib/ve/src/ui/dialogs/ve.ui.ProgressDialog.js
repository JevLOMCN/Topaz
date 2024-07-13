/*!
 * VisualEditor UserInterface ProgressDialog class.
 *
 * @copyright See AUTHORS.txt
 */

/**
 * Dialog for showing operations in progress.
 *
 * @class
 * @extends OO.ui.MessageDialog
 *
 * @constructor
 * @param {Object} [config] Configuration options
 */
ve.ui.ProgressDialog = function VeUiProgressDialog( config ) {
	// Parent constructor
	ve.ui.ProgressDialog.super.call( this, config );
};

/* Inheritance */

OO.inheritClass( ve.ui.ProgressDialog, OO.ui.MessageDialog );

/* Static Properties */

ve.ui.ProgressDialog.static.name = 'progress';

ve.ui.ProgressDialog.static.size = 'medium';

ve.ui.ProgressDialog.static.actions = [
	{
		action: 'cancel',
		label: OO.ui.deferMsg( 'visualeditor-dialog-action-cancel' ),
		flags: 'destructive',
		modes: 'cancellable'
	}
];

// Individual progress items can be cancellable, but the whole
// dialog should not be escapable.
ve.ui.ProgressDialog.static.escapable = false;

/* Methods */

/**
 * @inheritdoc
 */
ve.ui.ProgressDialog.prototype.initialize = function () {
	// Parent method
	ve.ui.ProgressDialog.super.prototype.initialize.call( this );

	// Properties
	this.inProgress = 0;
	this.cancelDeferreds = [];
};

/**
 * @inheritdoc
 */
ve.ui.ProgressDialog.prototype.getSetupProcess = function ( data ) {
	data = data || {};

	// Parent method
	return ve.ui.ProgressDialog.super.prototype.getSetupProcess.call( this, data )
		.next( function () {
			var cancellable = false,
				progresses = data.progresses;

			this.inProgress = progresses.length;
			this.text.$element.empty();
			this.cancelDeferreds = [];

			for ( var i = 0, l = progresses.length; i < l; i++ ) {
				var cancelDeferred = ve.createDeferred();
				var $row = $( '<div>' ).addClass( 've-ui-progressDialog-row' );
				var progressBar = new OO.ui.ProgressBarWidget();
				var fieldLayout = new OO.ui.FieldLayout(
					progressBar,
					{
						label: progresses[ i ].label,
						align: 'top'
					}
				);

				$row.append( fieldLayout.$element );

				if ( progresses[ i ].cancellable ) {
					var cancelButton = new OO.ui.ButtonWidget( {
						framed: false,
						icon: 'cancel',
						title: OO.ui.deferMsg( 'visualeditor-dialog-action-cancel' )
					} ).on( 'click', cancelDeferred.reject.bind( cancelDeferred ) );
					$row.append( cancelButton.$element );
					cancellable = true;
				}

				this.text.$element.append( $row );
				progresses[ i ].progressBarDeferred.resolve( progressBar, cancelDeferred.promise() );
				progresses[ i ].progressCompletePromise.then(
					this.progressComplete.bind( this, $row, false ),
					this.progressComplete.bind( this, $row, true )
				);
				this.cancelDeferreds.push( cancelDeferred );
			}
			this.actions.setMode( cancellable ? 'cancellable' : 'default' );
		}, this );
};

/**
 * @inheritdoc
 */
ve.ui.ProgressDialog.prototype.getActionProcess = function ( action ) {
	return new OO.ui.Process( function () {
		if ( action === 'cancel' ) {
			for ( var i = 0, l = this.cancelDeferreds.length; i < l; i++ ) {
				this.cancelDeferreds[ i ].reject();
			}
		}
		this.close( { action: action } );
	}, this );
};

/**
 * Progress has completed for an item
 *
 * @param {jQuery} $row Row containing progress bar which has completed
 * @param {boolean} failed The item failed
 */
ve.ui.ProgressDialog.prototype.progressComplete = function ( $row, failed ) {
	this.inProgress--;
	if ( !this.inProgress ) {
		this.close();
	}
	if ( failed ) {
		$row.remove();
		this.updateSize();
	}
};

/* Static methods */

/* Registration */

ve.ui.windowFactory.register( ve.ui.ProgressDialog );
