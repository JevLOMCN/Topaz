/*!
 * VisualEditor MediaWiki UserInterface back tool classes.
 *
 * @copyright See AUTHORS.txt
 * @license The MIT License (MIT); see LICENSE.txt
 */

/**
 * Back tool
 */
ve.ui.MWBackTool = function VeUiMwBackTool() {
	// Parent constructor
	ve.ui.MWBackTool.super.apply( this, arguments );
};
OO.inheritClass( ve.ui.MWBackTool, ve.ui.Tool );
ve.ui.MWBackTool.static.name = 'back';
ve.ui.MWBackTool.static.commandName = 'cancel';
ve.ui.MWBackTool.static.group = 'navigation';
ve.ui.MWBackTool.static.icon = 'close';
ve.ui.MWBackTool.static.title =
	OO.ui.deferMsg( 'visualeditor-backbutton-tooltip' );

ve.ui.toolFactory.register( ve.ui.MWBackTool );
