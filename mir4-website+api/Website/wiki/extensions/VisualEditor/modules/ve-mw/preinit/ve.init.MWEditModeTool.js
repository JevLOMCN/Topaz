/*!
 * VisualEditor MediaWiki edit mode tool classes.
 *
 * @copyright See AUTHORS.txt
 * @license The MIT License (MIT); see LICENSE.txt
 */

mw.libs.ve = mw.libs.ve || {};

/**
 * MediaWiki edit mode tool.
 *
 * @class
 * @abstract
 * @extends OO.ui.Tool
 * @constructor
 * @param {OO.ui.ToolGroup} toolGroup
 * @param {Object} [config] Config options
 */
mw.libs.ve.MWEditModeTool = function VeUiMWEditModeTool() {
	// Parent constructor
	mw.libs.ve.MWEditModeTool.super.apply( this, arguments );

	this.modeAvailable = null;
};

/* Inheritance */

OO.inheritClass( mw.libs.ve.MWEditModeTool, OO.ui.Tool );

/* Static Properties */

mw.libs.ve.MWEditModeTool.static.editMode = null;
mw.libs.ve.MWEditModeTool.static.group = 'editMode';
mw.libs.ve.MWEditModeTool.static.autoAddToCatchall = false;
mw.libs.ve.MWEditModeTool.static.unavailableTooltip = null;

/* Methods */

/**
 * Switch editors
 */
mw.libs.ve.MWEditModeTool.prototype.switch = function () {
	this.toolbar.emit( 'switchEditor', this.constructor.static.editMode );
};

/**
 * Get current edit mode
 *
 * @return {string} Current edit mode
 */
mw.libs.ve.MWEditModeTool.prototype.getMode = function () {
	return 'source';
};

/**
 * Check if edit mode is available
 *
 * @param {string} Edit mode
 * @return {boolean} Edit mode is available
 */
mw.libs.ve.MWEditModeTool.prototype.isModeAvailable = function () {
	return true;
};

/**
 * @inheritdoc
 */
mw.libs.ve.MWEditModeTool.prototype.onSelect = function () {
	if ( this.getMode() !== this.constructor.static.editMode ) {
		this.switch();
	}
	this.setActive( this.getMode() === this.constructor.static.editMode );
};

/**
 * @inheritdoc
 */
mw.libs.ve.MWEditModeTool.prototype.onUpdateState = function () {
	var modeAvailable = this.isModeAvailable( this.constructor.static.editMode );

	// Change title if state has changed
	if ( this.modeAvailable !== modeAvailable ) {
		this.$link.attr( 'title', modeAvailable ?
			OO.ui.resolveMsg( this.constructor.static.title ) :
			OO.ui.resolveMsg( this.constructor.static.unavailableTooltip )
		);
		this.setDisabled( !modeAvailable );
		this.modeAvailable = modeAvailable;
	}
	this.setActive( this.getMode() === this.constructor.static.editMode );
};

/**
 * MediaWiki edit mode visual tool.
 *
 * @class
 * @extends mw.libs.ve.MWEditModeTool
 * @constructor
 * @param {OO.ui.ToolGroup} toolGroup
 * @param {Object} [config] Config options
 */
mw.libs.ve.MWEditModeVisualTool = function VeUiMWEditModeVisualTool() {
	// Parent constructor
	mw.libs.ve.MWEditModeVisualTool.super.apply( this, arguments );
};

OO.inheritClass( mw.libs.ve.MWEditModeVisualTool, mw.libs.ve.MWEditModeTool );

mw.libs.ve.MWEditModeVisualTool.static.editMode = 'visual';
mw.libs.ve.MWEditModeVisualTool.static.name = 'editModeVisual';
mw.libs.ve.MWEditModeVisualTool.static.icon = 'eye';
mw.libs.ve.MWEditModeVisualTool.static.title =
	OO.ui.deferMsg( 'visualeditor-mweditmodeve-tool-current' );
mw.libs.ve.MWEditModeVisualTool.static.unavailableTooltip =
	OO.ui.deferMsg( 'visualeditor-mweditmodeve-tool-unavailable' );

/**
 * MediaWiki edit mode source tool.
 *
 * @class
 * @extends mw.libs.ve.MWEditModeTool
 * @constructor
 * @param {OO.ui.ToolGroup} toolGroup
 * @param {Object} [config] Config options
 */
mw.libs.ve.MWEditModeSourceTool = function VeUiMWEditModeSourceTool() {
	// Parent constructor
	mw.libs.ve.MWEditModeSourceTool.super.apply( this, arguments );
};

OO.inheritClass( mw.libs.ve.MWEditModeSourceTool, mw.libs.ve.MWEditModeTool );

mw.libs.ve.MWEditModeSourceTool.static.editMode = 'source';
mw.libs.ve.MWEditModeSourceTool.static.name = 'editModeSource';
mw.libs.ve.MWEditModeSourceTool.static.icon = 'wikiText';
mw.libs.ve.MWEditModeSourceTool.static.title =
	OO.ui.deferMsg( 'visualeditor-mweditmodesource-tool-current' );
mw.libs.ve.MWEditModeSourceTool.static.unavailableTooltip =
	OO.ui.deferMsg( 'visualeditor-mweditmodesource-tool-unavailable' );
