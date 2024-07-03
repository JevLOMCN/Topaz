/*!
 * VisualEditor MobileActionsContextItem class.
 *
 * @copyright See AUTHORS.txt
 */

/**
 * Context item to show extra actions required on mobile.
 *
 * These actions allow users to do things that are not possible with
 * a virtual keyboard and a fake selection, specifically copy & delete.
 *
 * @class
 * @extends ve.ui.LinearContextItem
 *
 * @param {ve.ui.LinearContext} context Context the item is in
 * @param {ve.dm.Model} model Model the item is related to
 * @param {Object} [config] Configuration options
 */
ve.ui.MobileActionsContextItem = function VeUiMobileActionsContextItem( context, model, config ) {
	// Parent constructor
	ve.ui.MobileActionsContextItem.super.call( this, context, model, config );

	this.copyButton = new OO.ui.ButtonWidget( {
		framed: false,
		label: ve.msg( 'visualeditor-clipboard-copy' ),
		icon: 'copy'
	} );
	this.deleteButton = new OO.ui.ButtonWidget( {
		framed: false,
		label: ve.msg( 'visualeditor-contextitemwidget-label-remove' ),
		icon: 'trash',
		flags: [ 'destructive' ]
	} );

	this.$head.append( this.copyButton.$element );
	if ( !this.isReadOnly() ) {
		this.$head.append( this.deleteButton.$element );
	}

	// Events
	this.copyButton.connect( this, { click: 'onCopyButtonClick' } );
	this.deleteButton.connect( this, { click: 'onDeleteButtonClick' } );

	// Initialization
	this.$element.addClass( 've-ui-mobileActionsContextItem' );
};

/* Inheritance */

OO.inheritClass( ve.ui.MobileActionsContextItem, ve.ui.LinearContextItem );

/* Static Properties */

ve.ui.MobileActionsContextItem.static.name = 'mobileActions';

ve.ui.MobileActionsContextItem.static.editable = false;

// Show this context last
ve.ui.MobileActionsContextItem.static.sortOrder = 1;

/**
 * @inheritdoc
 */
ve.ui.MobileActionsContextItem.static.isCompatibleWith = function ( model ) {
	return OO.ui.isMobile() && model instanceof ve.dm.Node && (
		model.isFocusable() || model.isCellable()
	);
};

/* Methods */

/**
 * Handle copy button click events.
 */
ve.ui.MobileActionsContextItem.prototype.onCopyButtonClick = function () {
	var surfaceView = this.context.getSurface().getView();

	surfaceView.activate();
	// Force a native selection on mobile
	surfaceView.preparePasteTargetForCopy( true );

	var copied;
	try {
		copied = document.execCommand( 'copy' );
	} catch ( e ) {
		copied = false;
	}

	ve.init.platform.notify( ve.msg( copied ? 'visualeditor-clipboard-copy-success' : 'visualeditor-clipboard-copy-fail' ) );

	// Restore normal selection for device type
	surfaceView.preparePasteTargetForCopy();
	if ( OO.ui.isMobile() ) {
		// Support: Mobile Safari
		// Force remove the selection to hide the keyboard
		document.activeElement.blur();
	}

	ve.track( 'activity.' + this.constructor.static.name, { action: 'context-copy' } );
};

/**
 * Handle delete button click events.
 */
ve.ui.MobileActionsContextItem.prototype.onDeleteButtonClick = function () {
	var surface = this.context.getSurface();
	var command = surface.commandRegistry.lookup( 'backspace' );

	// Use the 'backspace' command as this triggers the KeyDownHandler for the
	// current selection, e.g. ve.ce.TableDeleteKeyDownHandler will be used to
	// clear table cells for TableSelection's.
	if ( command ) {
		command.execute( surface, undefined, 'context' );
		this.emit( 'command' );
		ve.track( 'activity.' + this.constructor.static.name, { action: 'context-delete' } );
	}
};

/* Registration */

ve.ui.contextItemFactory.register( ve.ui.MobileActionsContextItem );
