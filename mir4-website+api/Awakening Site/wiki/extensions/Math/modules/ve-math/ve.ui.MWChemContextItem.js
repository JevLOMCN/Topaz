/*!
 * VisualEditor MWChemContextItem class.
 *
 * @copyright See AUTHORS.txt
 */

/**
 * Context item for a chem node.
 *
 * @class
 * @extends ve.ui.MWLatexContextItem
 *
 * @param {ve.ui.LinearContext} context Context the item is in
 * @param {ve.dm.Model} model Model the item is related to
 * @param {Object} config Configuration options
 */
ve.ui.MWChemContextItem = function VeUiMWChemContextItem() {
	// Parent constructor
	ve.ui.MWChemContextItem.super.apply( this, arguments );
};

/* Inheritance */

OO.inheritClass( ve.ui.MWChemContextItem, ve.ui.MWLatexContextItem );

/* Static Properties */

ve.ui.MWChemContextItem.static.name = 'chem';

ve.ui.MWChemContextItem.static.icon = 'labFlask';

ve.ui.MWChemContextItem.static.label = OO.ui.deferMsg( 'math-visualeditor-mwchemdialog-title' );

ve.ui.MWChemContextItem.static.modelClasses = [ ve.dm.MWChemNode ];

ve.ui.MWChemContextItem.static.embeddable = false;

ve.ui.MWChemContextItem.static.commandName = 'chemDialog';

ve.ui.MWChemContextItem.static.inlineEditCommand = 'chemInspector';

/* Registration */

ve.ui.contextItemFactory.register( ve.ui.MWChemContextItem );
