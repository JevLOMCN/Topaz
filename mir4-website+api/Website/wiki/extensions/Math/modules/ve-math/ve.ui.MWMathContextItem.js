/*!
 * VisualEditor MWMathContextItem class.
 *
 * @copyright See AUTHORS.txt
 */

/**
 * Context item for a math node.
 *
 * @class
 * @extends ve.ui.MWLatexContextItem
 *
 * @param {ve.ui.LinearContext} context Context the item is in
 * @param {ve.dm.Model} model Model the item is related to
 * @param {Object} config Configuration options
 */
ve.ui.MWMathContextItem = function VeUiMWMathContextItem() {
	// Parent constructor
	ve.ui.MWMathContextItem.super.apply( this, arguments );
};

/* Inheritance */

OO.inheritClass( ve.ui.MWMathContextItem, ve.ui.MWLatexContextItem );

/* Static Properties */

ve.ui.MWMathContextItem.static.name = 'math';

ve.ui.MWMathContextItem.static.icon = 'mathematics';

ve.ui.MWMathContextItem.static.label = OO.ui.deferMsg( 'math-visualeditor-mwmathdialog-title' );

ve.ui.MWMathContextItem.static.modelClasses = [ ve.dm.MWMathNode ];

ve.ui.MWMathContextItem.static.embeddable = false;

ve.ui.MWMathContextItem.static.commandName = 'mathDialog';

ve.ui.MWMathContextItem.static.inlineEditCommand = 'mathInspector';

/* Registration */

ve.ui.contextItemFactory.register( ve.ui.MWMathContextItem );
