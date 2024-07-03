/*!
 * VisualEditor UserInterface MWSyntaxHighlightInspector class.
 *
 * @copyright VisualEditor Team and others
 * @license The MIT License (MIT); see LICENSE.txt
 */

/**
 * MediaWiki syntax highlight inspector.
 *
 * @class
 * @extends ve.ui.MWLiveExtensionInspector
 * @mixins ve.ui.MWSyntaxHighlightWindow
 *
 * @constructor
 * @param {Object} [config] Configuration options
 */
ve.ui.MWSyntaxHighlightInspector = function VeUiMWSyntaxHighlightInspector() {
	// Parent constructor
	ve.ui.MWSyntaxHighlightInspector.super.apply( this, arguments );

	// Mixin constructor
	ve.ui.MWSyntaxHighlightWindow.call( this );
};

/* Inheritance */

OO.inheritClass( ve.ui.MWSyntaxHighlightInspector, ve.ui.MWLiveExtensionInspector );

OO.mixinClass( ve.ui.MWSyntaxHighlightInspector, ve.ui.MWSyntaxHighlightWindow );

/* Static properties */

ve.ui.MWSyntaxHighlightInspector.static.name = 'syntaxhighlightInspector';

ve.ui.MWSyntaxHighlightInspector.static.modelClasses = [ ve.dm.MWInlineSyntaxHighlightNode ];

/* Methods */

/**
 * @inheritdoc
 */
ve.ui.MWSyntaxHighlightInspector.prototype.initialize = function () {
	// Parent method
	ve.ui.MWSyntaxHighlightInspector.super.prototype.initialize.call( this );

	// Mixin method
	ve.ui.MWSyntaxHighlightWindow.prototype.initialize.call( this );

	// Initialization
	this.$content.addClass( 've-ui-mwSyntaxHighlightInspector-content' );
	this.form.$element.prepend(
		this.languageField.$element,
		this.codeField.$element
	);
};

/**
 * @inheritdoc
 */
ve.ui.MWSyntaxHighlightInspector.prototype.getReadyProcess = function ( data ) {
	// Parent process
	var process = ve.ui.MWSyntaxHighlightInspector.super.prototype.getReadyProcess.call( this, data );
	// Mixin process
	return ve.ui.MWSyntaxHighlightWindow.prototype.getReadyProcess.call( this, data, process );
};

/**
 * @inheritdoc
 */
ve.ui.MWSyntaxHighlightInspector.prototype.getSetupProcess = function ( data ) {
	// Parent process
	var process = ve.ui.MWSyntaxHighlightInspector.super.prototype.getSetupProcess.call( this, data );
	// Mixin process
	return ve.ui.MWSyntaxHighlightWindow.prototype.getSetupProcess.call( this, data, process ).next( function () {
		this.language.on( 'change', this.onChangeHandler );
	}, this );
};

/**
 * @inheritdoc
 */
ve.ui.MWSyntaxHighlightInspector.prototype.getTeardownProcess = function ( data ) {
	// Parent process
	var process = ve.ui.MWSyntaxHighlightInspector.super.prototype.getTeardownProcess.call( this, data );
	// Mixin process
	return ve.ui.MWSyntaxHighlightWindow.prototype.getTeardownProcess.call( this, data, process ).first( function () {
		this.language.off( 'change', this.onChangeHandler );
	}, this );
};

/**
 * @inheritdoc
 */
ve.ui.MWSyntaxHighlightInspector.prototype.updateMwData = function () {
	// Parent method
	ve.ui.MWSyntaxHighlightInspector.super.prototype.updateMwData.apply( this, arguments );
	// Mixin method
	ve.ui.MWSyntaxHighlightWindow.prototype.updateMwData.apply( this, arguments );
};

/* Registration */

ve.ui.windowFactory.register( ve.ui.MWSyntaxHighlightInspector );
