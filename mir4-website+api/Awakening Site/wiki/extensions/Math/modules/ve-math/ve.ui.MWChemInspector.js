/*!
 * VisualEditor UserInterface MWChemInspector class.
 *
 * @copyright See AUTHORS.txt
 * @license MIT
 */

/**
 * MediaWiki chem inspector.
 *
 * @class
 * @extends ve.ui.MWLiveExtensionInspector
 *
 * @constructor
 * @param {Object} [config] Configuration options
 */
ve.ui.MWChemInspector = function VeUiMWChemInspector( config ) {
	// Parent constructor
	ve.ui.MWChemInspector.super.call( this, config );
};

/* Inheritance */

OO.inheritClass( ve.ui.MWChemInspector, ve.ui.MWLatexInspector );

/* Static properties */

ve.ui.MWChemInspector.static.name = 'chemInspector';

ve.ui.MWChemInspector.static.title = OO.ui.deferMsg( 'math-visualeditor-mwchemdialog-title' );

ve.ui.MWChemInspector.static.modelClasses = [ ve.dm.MWChemNode ];

/* Registration */

ve.ui.windowFactory.register( ve.ui.MWChemInspector );
