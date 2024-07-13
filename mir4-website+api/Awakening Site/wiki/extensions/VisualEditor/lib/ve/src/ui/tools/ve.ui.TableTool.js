/*!
 * VisualEditor UserInterface ListTool classes.
 *
 * @copyright See AUTHORS.txt
 * @license The MIT License (MIT); see LICENSE.txt
 */

/* Tools */

ve.ui.InsertTableTool = function VeUiInsertTableTool() {
	ve.ui.InsertTableTool.super.apply( this, arguments );
};
OO.inheritClass( ve.ui.InsertTableTool, ve.ui.Tool );
ve.ui.InsertTableTool.static.name = 'insertTable';
ve.ui.InsertTableTool.static.group = 'insert';
ve.ui.InsertTableTool.static.icon = 'table';
ve.ui.InsertTableTool.static.title = OO.ui.deferMsg( 'visualeditor-table-insert-table' );
ve.ui.InsertTableTool.static.commandName = 'insertTable';
ve.ui.toolFactory.register( ve.ui.InsertTableTool );
