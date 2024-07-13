/*!
 * VisualEditor UserInterface MWMathInspector class.
 *
 * @copyright See AUTHORS.txt
 * @license MIT
 */

/**
 * MediaWiki math inspector.
 *
 * @class
 * @extends ve.ui.MWLiveExtensionInspector
 *
 * @constructor
 * @param {Object} [config] Configuration options
 */
ve.ui.MWMathInspector = function VeUiMWMathInspector( config ) {
	// Parent constructor
	ve.ui.MWMathInspector.super.call( this, config );
};

/* Inheritance */

OO.inheritClass( ve.ui.MWMathInspector, ve.ui.MWLatexInspector );

/* Static properties */

ve.ui.MWMathInspector.static.name = 'mathInspector';

ve.ui.MWMathInspector.static.title = OO.ui.deferMsg( 'math-visualeditor-mwmathdialog-title' );

ve.ui.MWMathInspector.static.modelClasses = [ ve.dm.MWMathNode ];

/* Registration */

ve.ui.windowFactory.register( ve.ui.MWMathInspector );
