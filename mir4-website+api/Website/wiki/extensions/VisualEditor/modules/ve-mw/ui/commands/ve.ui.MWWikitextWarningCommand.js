/*!
 * VisualEditor UserInterface MediaWiki WikitextWarningCommand class.
 *
 * @copyright See AUTHORS.txt
 */

/**
 * Wikitext warning command.
 *
 * @class
 * @extends ve.ui.Command
 *
 * @constructor
 */
ve.ui.MWWikitextWarningCommand = function VeUiMWWikitextWarningCommand() {
	// Parent constructor
	ve.ui.MWWikitextWarningCommand.super.call(
		this, 'mwWikitextWarning'
	);
	this.warning = null;
};

/* Inheritance */

OO.inheritClass( ve.ui.MWWikitextWarningCommand, ve.ui.Command );

/* Methods */

/**
 * @inheritdoc
 */
ve.ui.MWWikitextWarningCommand.prototype.execute = function () {
	var command = this;
	if ( this.warning && this.warning.isOpen ) {
		return false;
	}
	// eslint-disable-next-line no-jquery/no-html
	var $message = $( '<div>' ).html( ve.init.platform.getParsedMessage( 'visualeditor-wikitext-warning' ) );
	ve.targetLinksToNewWindow( $message[ 0 ] );
	ve.init.platform.notify(
		$message.contents(),
		ve.msg( 'visualeditor-wikitext-warning-title' ),
		{ tag: 'visualeditor-wikitext-warning' }
	).then( function ( message ) {
		command.warning = message;
	} );
	return true;
};

/* Registration */

ve.ui.commandRegistry.register( new ve.ui.MWWikitextWarningCommand() );
