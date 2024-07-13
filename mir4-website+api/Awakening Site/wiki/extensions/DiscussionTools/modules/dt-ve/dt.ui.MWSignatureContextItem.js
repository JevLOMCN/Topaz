function DtUiMWSignatureContextItem() {
	// Parent constructor
	DtUiMWSignatureContextItem.super.apply( this, arguments );
}

OO.inheritClass( DtUiMWSignatureContextItem, ve.ui.MWSignatureContextItem );

DtUiMWSignatureContextItem.static.name = 'dtMwSignature';

DtUiMWSignatureContextItem.static.modelClasses = [ require( './dt.dm.MWSignatureNode.js' ) ];

DtUiMWSignatureContextItem.static.label =
	OO.ui.deferMsg( 'discussiontools-replywidget-signature-title' );

// Get the formatted, localized, platform-specific shortcut key for the given command
DtUiMWSignatureContextItem.prototype.getShortcutKey = function ( commandName ) {
	// Adapted from ve.ui.CommandHelpDialog.prototype.initialize
	var commandInfo = ve.ui.commandHelpRegistry.lookup( commandName );
	var triggerList = ve.ui.triggerRegistry.lookup( commandInfo.trigger );
	var $shortcut = $( '<kbd>' ).addClass( 've-ui-commandHelpDialog-shortcut' ).append(
		triggerList[ 0 ].getMessage( true ).map( ve.ui.CommandHelpDialog.static.buildKeyNode )
	).find( 'kbd + kbd' ).before( '+' ).end();
	return $shortcut;
};

// Add a description saying that typing a signature is not needed here
DtUiMWSignatureContextItem.prototype.renderBody = function () {
	this.$body.empty().append( mw.message(
		'discussiontools-replywidget-signature-body',
		$( '<code>' ).text( '~~~~' ),
		this.getShortcutKey( 'undo' )
	).parseDom() );
};

ve.ui.contextItemFactory.register( DtUiMWSignatureContextItem );

module.exports = DtUiMWSignatureContextItem;
