/*!
 * VisualEditor user interface MWChemDialog class.
 *
 * @copyright See AUTHORS.txt
 * @license MIT
 */

/**
 * Dialog for inserting and editing chem formulas.
 *
 * @class
 * @extends ve.ui.MWExtensionPreviewDialog
 *
 * @constructor
 * @param {Object} [config] Configuration options
 */

ve.ui.MWChemDialog = function VeUiMWChemDialog( config ) {
	// Parent constructor
	ve.ui.MWChemDialog.super.call( this, config );

};

/* Inheritance */

OO.inheritClass( ve.ui.MWChemDialog, ve.ui.MWLatexDialog );

/* Static properties */

ve.ui.MWChemDialog.static.name = 'chemDialog';

ve.ui.MWChemDialog.static.title = OO.ui.deferMsg( 'math-visualeditor-mwchemdialog-title' );

ve.ui.MWChemDialog.static.modelClasses = [ ve.dm.MWChemNode ];

ve.ui.MWChemDialog.static.symbolsModule = 'ext.math.visualEditor.chemSymbols';

ve.ui.MWChemDialog.static.autocompleteWordList = [];

/* Registration */

ve.ui.windowFactory.register( ve.ui.MWChemDialog );
