/*!
 * VisualEditor UserInterface ListTool classes.
 *
 * @copyright See AUTHORS.txt
 */

/**
 * UserInterface list tool.
 *
 * @abstract
 * @class
 * @extends ve.ui.Tool
 * @constructor
 * @param {OO.ui.ToolGroup} toolGroup
 * @param {Object} [config] Configuration options
 */
ve.ui.ListTool = function VeUiListTool() {
	// Parent constructor
	ve.ui.ListTool.super.apply( this, arguments );

	// Properties
	this.method = null;
};

/* Inheritance */

OO.inheritClass( ve.ui.ListTool, ve.ui.Tool );

/* Static Properties */

/**
 * List style the tool applies.
 *
 * @abstract
 * @static
 * @property {string}
 * @inheritable
 */
ve.ui.ListTool.static.style = '';

ve.ui.ListTool.static.deactivateOnSelect = false;

/* Methods */

/**
 * @inheritdoc
 */
ve.ui.ListTool.prototype.onUpdateState = function ( fragment ) {
	// Parent method
	ve.ui.ListTool.super.prototype.onUpdateState.apply( this, arguments );

	var style = this.constructor.static.style;
	var isMatching = fragment.hasMatchingAncestor( 'list', { style: style }, true );
	this.setActive( isMatching );
};

/**
 * UserInterface bullet tool.
 *
 * @class
 * @extends ve.ui.ListTool
 * @constructor
 * @param {OO.ui.ToolGroup} toolGroup
 * @param {Object} [config] Configuration options
 */
ve.ui.BulletListTool = function VeUiBulletListTool() {
	ve.ui.BulletListTool.super.apply( this, arguments );
};
OO.inheritClass( ve.ui.BulletListTool, ve.ui.ListTool );
ve.ui.BulletListTool.static.name = 'bullet';
ve.ui.BulletListTool.static.group = 'structure';
ve.ui.BulletListTool.static.icon = 'listBullet';
ve.ui.BulletListTool.static.title =
	OO.ui.deferMsg( 'visualeditor-listbutton-bullet-tooltip' );
ve.ui.BulletListTool.static.style = 'bullet';
ve.ui.BulletListTool.static.commandName = 'bullet';
ve.ui.toolFactory.register( ve.ui.BulletListTool );

/**
 * UserInterface number tool.
 *
 * @class
 * @extends ve.ui.ListTool
 * @constructor
 * @param {OO.ui.ToolGroup} toolGroup
 * @param {Object} [config] Configuration options
 */
ve.ui.NumberListTool = function VeUiNumberListTool() {
	ve.ui.NumberListTool.super.apply( this, arguments );
};
OO.inheritClass( ve.ui.NumberListTool, ve.ui.ListTool );
ve.ui.NumberListTool.static.name = 'number';
ve.ui.NumberListTool.static.group = 'structure';
ve.ui.NumberListTool.static.icon = 'listNumbered';
ve.ui.NumberListTool.static.title =
	OO.ui.deferMsg( 'visualeditor-listbutton-number-tooltip' );
ve.ui.NumberListTool.static.style = 'number';
ve.ui.NumberListTool.static.commandName = 'number';
ve.ui.toolFactory.register( ve.ui.NumberListTool );
