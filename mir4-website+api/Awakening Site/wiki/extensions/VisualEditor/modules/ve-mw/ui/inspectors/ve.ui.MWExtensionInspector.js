/*!
 * VisualEditor UserInterface MWExtensionInspector class.
 *
 * @copyright See AUTHORS.txt
 * @license The MIT License (MIT); see LICENSE.txt
 */

/**
 * Inspector for editing generic MediaWiki extensions.
 *
 * @class
 * @abstract
 * @extends ve.ui.NodeInspector
 * @mixins ve.ui.MWExtensionWindow
 *
 * @constructor
 * @param {Object} [config] Configuration options
 */
ve.ui.MWExtensionInspector = function VeUiMWExtensionInspector() {
	// Parent constructor
	ve.ui.MWExtensionInspector.super.apply( this, arguments );

	// Mixin constructors
	ve.ui.MWExtensionWindow.call( this );
};

/* Inheritance */

OO.inheritClass( ve.ui.MWExtensionInspector, ve.ui.NodeInspector );

OO.mixinClass( ve.ui.MWExtensionInspector, ve.ui.MWExtensionWindow );

/* Methods */

/**
 * @inheritdoc
 */
ve.ui.MWExtensionInspector.prototype.initialize = function () {
	// Parent method
	ve.ui.MWExtensionInspector.super.prototype.initialize.call( this );

	// Mixin method
	ve.ui.MWExtensionWindow.prototype.initialize.call( this );

	// Initialization
	this.$element.addClass( 've-ui-mwExtensionInspector' );
	this.form.$element.append( this.input.$element );
};

/**
 * @inheritdoc
 */
ve.ui.MWExtensionInspector.prototype.getSetupProcess = function ( data ) {
	data = data || {};
	// Parent process
	var process = ve.ui.MWExtensionInspector.super.prototype.getSetupProcess.call( this, data );
	// Mixin process
	return ve.ui.MWExtensionWindow.prototype.getSetupProcess.call( this, data, process );
};

/**
 * @inheritdoc
 */
ve.ui.MWExtensionInspector.prototype.getReadyProcess = function ( data ) {
	data = data || {};
	// Parent process
	var process = ve.ui.MWExtensionInspector.super.prototype.getReadyProcess.call( this, data );
	// Mixin process
	return ve.ui.MWExtensionWindow.prototype.getReadyProcess.call( this, data, process ).next( function () {
		// Focus the input
		this.input.focus();
	}, this );
};

/**
 * @inheritdoc
 */
ve.ui.MWExtensionInspector.prototype.getTeardownProcess = function ( data ) {
	data = data || {};
	// Parent process
	var process = ve.ui.MWExtensionInspector.super.prototype.getTeardownProcess.call( this, data );
	// Mixin process
	return ve.ui.MWExtensionWindow.prototype.getTeardownProcess.call( this, data, process );
};

/**
 * @inheritdoc
 */
ve.ui.MWExtensionInspector.prototype.getActionProcess = function ( action ) {
	// Parent process
	var process = ve.ui.MWExtensionInspector.super.prototype.getActionProcess.call( this, action );
	// Mixin process
	return ve.ui.MWExtensionWindow.prototype.getActionProcess.call( this, action, process );
};
