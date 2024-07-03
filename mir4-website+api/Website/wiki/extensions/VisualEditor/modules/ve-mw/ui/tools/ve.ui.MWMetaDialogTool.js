/*!
 * VisualEditor MediaWiki UserInterface meta tool classes.
 *
 * @copyright See AUTHORS.txt
 * @license The MIT License (MIT); see LICENSE.txt
 */

/**
 * MediaWiki UserInterface VE only dialog tool.
 *
 * @class
 * @abstract
 * @extends ve.ui.WindowTool
 * @constructor
 */
ve.ui.MWVeOnlyDialogTool = function VeUiMWVeOnlyDialogTool() {
	ve.ui.MWVeOnlyDialogTool.super.apply( this, arguments );
};
OO.inheritClass( ve.ui.MWVeOnlyDialogTool, ve.ui.WindowTool );
ve.ui.MWVeOnlyDialogTool.prototype.onUpdateState = function () {
	// Parent method
	ve.ui.MWVeOnlyDialogTool.super.prototype.onUpdateState.apply( this, arguments );

	// Meta tools are not supported in source mode (yet?)
	this.setDisabled( !this.toolbar.getSurface() || this.toolbar.getSurface().getMode() === 'source' );
};

/**
 * MediaWiki UserInterface meta dialog tool.
 *
 * @class
 * @extends ve.ui.WindowTool
 * @constructor
 * @param {OO.ui.Toolbar} toolbar
 * @param {Object} [config] Configuration options
 */
ve.ui.MWMetaDialogTool = function VeUiMWMetaDialogTool() {
	ve.ui.MWMetaDialogTool.super.apply( this, arguments );
};
OO.inheritClass( ve.ui.MWMetaDialogTool, ve.ui.WindowTool );
ve.ui.MWMetaDialogTool.static.name = 'meta';
ve.ui.MWMetaDialogTool.static.group = 'utility';
ve.ui.MWMetaDialogTool.static.icon = 'window';
ve.ui.MWMetaDialogTool.static.title =
	OO.ui.deferMsg( 'visualeditor-meta-tool' );
ve.ui.MWMetaDialogTool.static.commandName = 'meta';
ve.ui.MWMetaDialogTool.static.autoAddToCatchall = false;
ve.ui.toolFactory.register( ve.ui.MWMetaDialogTool );

ve.ui.commandRegistry.register(
	new ve.ui.Command(
		'meta', 'window', 'open',
		{ args: [ 'meta' ] }
	)
);

/**
 * MediaWiki UserInterface categories tool.
 *
 * @class
 * @extends ve.ui.MWVeOnlyDialogTool
 * @constructor
 * @param {OO.ui.Toolbar} toolbar
 * @param {Object} [config] Configuration options
 */
ve.ui.MWCategoriesDialogTool = function VeUiMWCategoriesDialogTool() {
	ve.ui.MWCategoriesDialogTool.super.apply( this, arguments );
};
OO.inheritClass( ve.ui.MWCategoriesDialogTool, ve.ui.MWVeOnlyDialogTool );
ve.ui.MWCategoriesDialogTool.static.name = 'categories';
ve.ui.MWCategoriesDialogTool.static.group = 'utility';
ve.ui.MWCategoriesDialogTool.static.icon = 'tag';
ve.ui.MWCategoriesDialogTool.static.title =
	OO.ui.deferMsg( 'visualeditor-categories-tool' );
ve.ui.MWCategoriesDialogTool.static.commandName = 'meta/categories';
ve.ui.MWCategoriesDialogTool.static.autoAddToCatchall = false;
ve.ui.toolFactory.register( ve.ui.MWCategoriesDialogTool );

ve.ui.commandRegistry.register(
	new ve.ui.Command(
		'meta/categories', 'window', 'open',
		{ args: [ 'meta', { page: 'categories' } ] }
	)
);

/**
 * MediaWiki UserInterface page settings tool.
 *
 * @class
 * @extends ve.ui.MWVeOnlyDialogTool
 * @constructor
 * @param {OO.ui.Toolbar} toolbar
 * @param {Object} [config] Configuration options
 */
ve.ui.MWPageSettingsDialogTool = function VeUiMWPageSettingsDialogTool() {
	ve.ui.MWPageSettingsDialogTool.super.apply( this, arguments );
};
OO.inheritClass( ve.ui.MWPageSettingsDialogTool, ve.ui.MWVeOnlyDialogTool );
ve.ui.MWPageSettingsDialogTool.static.name = 'settings';
ve.ui.MWPageSettingsDialogTool.static.group = 'utility';
ve.ui.MWPageSettingsDialogTool.static.icon = 'pageSettings';
ve.ui.MWPageSettingsDialogTool.static.title =
	OO.ui.deferMsg( 'visualeditor-settings-tool' );
ve.ui.MWPageSettingsDialogTool.static.commandName = 'meta/settings';
ve.ui.MWPageSettingsDialogTool.static.autoAddToCatchall = false;
ve.ui.toolFactory.register( ve.ui.MWPageSettingsDialogTool );

ve.ui.commandRegistry.register(
	new ve.ui.Command(
		'meta/settings', 'window', 'open',
		{ args: [ 'meta', { page: 'settings' } ] }
	)
);

/**
 * MediaWiki UserInterface advanced page settings tool.
 *
 * @class
 * @extends ve.ui.MWVeOnlyDialogTool
 * @constructor
 * @param {OO.ui.Toolbar} toolbar
 * @param {Object} [config] Configuration options
 */
ve.ui.MWAdvancedPageSettingsDialogTool = function VeUiMWAdvancedPageSettingsDialogTool() {
	ve.ui.MWAdvancedPageSettingsDialogTool.super.apply( this, arguments );
};
OO.inheritClass( ve.ui.MWAdvancedPageSettingsDialogTool, ve.ui.MWVeOnlyDialogTool );
ve.ui.MWAdvancedPageSettingsDialogTool.static.name = 'advancedSettings';
ve.ui.MWAdvancedPageSettingsDialogTool.static.group = 'utility';
ve.ui.MWAdvancedPageSettingsDialogTool.static.icon = 'settings';
ve.ui.MWAdvancedPageSettingsDialogTool.static.title =
	OO.ui.deferMsg( 'visualeditor-advancedsettings-tool' );
ve.ui.MWAdvancedPageSettingsDialogTool.static.commandName = 'meta/advanced';
ve.ui.MWAdvancedPageSettingsDialogTool.static.autoAddToCatchall = false;
ve.ui.toolFactory.register( ve.ui.MWAdvancedPageSettingsDialogTool );

ve.ui.commandRegistry.register(
	new ve.ui.Command(
		'meta/advanced', 'window', 'open',
		{ args: [ 'meta', { page: 'advancedSettings' } ] }
	)
);

/**
 * MediaWiki UserInterface languages tool.
 *
 * @class
 * @extends ve.ui.MWVeOnlyDialogTool
 * @constructor
 * @param {OO.ui.Toolbar} toolbar
 * @param {Object} [config] Configuration options
 */
ve.ui.MWLanguagesDialogTool = function VeUiMWLanguagesDialogTool() {
	ve.ui.MWLanguagesDialogTool.super.apply( this, arguments );
};
OO.inheritClass( ve.ui.MWLanguagesDialogTool, ve.ui.MWVeOnlyDialogTool );
ve.ui.MWLanguagesDialogTool.static.name = 'languages';
ve.ui.MWLanguagesDialogTool.static.group = 'utility';
ve.ui.MWLanguagesDialogTool.static.icon = 'textLanguage';
ve.ui.MWLanguagesDialogTool.static.title =
	OO.ui.deferMsg( 'visualeditor-languages-tool' );
ve.ui.MWLanguagesDialogTool.static.commandName = 'meta/languages';
ve.ui.MWLanguagesDialogTool.static.autoAddToCatchall = false;
ve.ui.toolFactory.register( ve.ui.MWLanguagesDialogTool );

ve.ui.commandRegistry.register(
	new ve.ui.Command(
		'meta/languages', 'window', 'open',
		{ args: [ 'meta', { page: 'languages' } ] }
	)
);

/**
 * MediaWiki UserInterface TemplatesUsed tool.
 *
 * @class
 * @extends ve.ui.WindowTool
 * @constructor
 * @param {OO.ui.Toolbar} toolbar
 * @param {Object} [config] Configuration options
 */
ve.ui.MWTemplatesUsedDialogTool = function VeUiMWTemplatesUsedDialogTool() {
	ve.ui.MWTemplatesUsedDialogTool.super.apply( this, arguments );
};
OO.inheritClass( ve.ui.MWTemplatesUsedDialogTool, ve.ui.WindowTool );
ve.ui.MWTemplatesUsedDialogTool.static.name = 'templatesUsed';
ve.ui.MWTemplatesUsedDialogTool.static.group = 'utility';
ve.ui.MWTemplatesUsedDialogTool.static.icon = 'puzzle';
ve.ui.MWTemplatesUsedDialogTool.static.title =
	OO.ui.deferMsg( 'visualeditor-templatesused-tool' );
ve.ui.MWTemplatesUsedDialogTool.static.commandName = 'meta/templatesUsed';
ve.ui.MWTemplatesUsedDialogTool.static.autoAddToCatchall = false;
ve.ui.toolFactory.register( ve.ui.MWTemplatesUsedDialogTool );

ve.ui.commandRegistry.register(
	new ve.ui.Command(
		'meta/templatesUsed', 'window', 'open',
		{ args: [ 'meta', { page: 'templatesUsed' } ] }
	)
);
