/*!
 * VisualEditor MediaWiki Initialization CollabTarget class.
 *
 * @copyright See AUTHORS.txt
 * @license The MIT License (MIT); see LICENSE.txt
 */

/**
 * MediaWiki mobile article target.
 *
 * @class
 * @extends ve.init.mw.Target
 *
 * @constructor
 * @param {mw.Title} title Page sub-title
 * @param {string} rebaserUrl Rebaser server URL
 * @param {Object} [config] Configuration options
 * @cfg {mw.Title} [importTitle] Title to import
 */
ve.init.mw.CollabTarget = function VeInitMwCollabTarget( title, rebaserUrl, config ) {
	config = config || {};
	config.toolbarConfig = ve.extendObject( {
		shadow: true,
		actions: true,
		floatable: true
	}, config.toolbarConfig );

	this.title = title;
	this.rebaserUrl = rebaserUrl;
	this.importTitle = config.importTitle || null;

	// Parent constructor
	ve.init.mw.CollabTarget.super.call( this, config );

	// HACK: Disable redo command until supported (T185706)
	ve.ui.commandRegistry.unregister( 'redo' );

	// HACK: Disable references until supported (T194838)
	ve.ui.commandRegistry.unregister( 'reference' );
	ve.ui.commandRegistry.unregister( 'referencesList' );
	ve.ui.commandRegistry.unregister( 'citoid' );

	// eslint-disable-next-line no-jquery/no-global-selector
	this.$editableContent = $( '#mw-content-text' );

	// Initialization
	this.$element.addClass( 've-init-mw-articleTarget ve-init-mw-collabTarget' );
};

/* Inheritance */

OO.inheritClass( ve.init.mw.CollabTarget, ve.init.mw.Target );

/* Static Properties */

ve.init.mw.CollabTarget.static.name = 'collab';

ve.init.mw.CollabTarget.static.trackingName = 'collab';

ve.init.mw.CollabTarget.static.toolbarGroups = ve.copy( ve.init.mw.CollabTarget.static.toolbarGroups );
ve.init.mw.CollabTarget.static.toolbarGroups.splice( 4, 0, {
	name: 'commentAnnotation',
	include: [ 'commentAnnotation' ]
} );
// HACK: Disable references until supported (T194838)
ve.init.mw.CollabTarget.static.toolbarGroups = ve.init.mw.CollabTarget.static.toolbarGroups.filter( function ( group ) {
	return group.name !== 'reference';
} );
ve.init.mw.CollabTarget.static.toolbarGroups.push(
	{
		name: 'help',
		align: 'after',
		include: [ 'help' ]
	},
	{
		name: 'pageMenu',
		align: 'after',
		type: 'list',
		icon: 'menu',
		indicator: null,
		title: ve.msg( 'visualeditor-pagemenu-tooltip' ),
		label: ve.msg( 'visualeditor-pagemenu-tooltip' ),
		invisibleLabel: true,
		include: [ 'changeDirectionality', 'findAndReplace' ]
	},
	{
		name: 'authorList',
		align: 'after',
		include: [ 'authorList' ]
	},
	{
		name: 'export',
		align: 'after',
		include: [ 'export' ]
	}
);

ve.init.mw.CollabTarget.static.importRules = ve.copy( ve.init.mw.CollabTarget.static.importRules );
ve.init.mw.CollabTarget.static.importRules.external.blacklist[ 'link/mwExternal' ] = false;

/* Methods */

/**
 * @inheritdoc
 */
ve.init.mw.CollabTarget.prototype.getSurfaceConfig = function ( config ) {
	return ve.init.mw.CollabTarget.super.prototype.getSurfaceConfig.call( this, ve.extendObject( {
		nullSelectionOnBlur: false
	}, config ) );
};

/**
 * @inheritdoc
 */
ve.init.mw.CollabTarget.prototype.getSurfaceClasses = function () {
	var classes = ve.init.mw.CollabTarget.super.prototype.getSurfaceClasses.call( this );
	return classes.concat( [ 'mw-body-content' ] );
};

/**
 * Page modifications after editor load.
 */
ve.init.mw.CollabTarget.prototype.transformPage = function () {
};

/**
 * Page modifications after editor teardown.
 */
ve.init.mw.CollabTarget.prototype.restorePage = function () {
};

/**
 * MWBackTool calls tryTeardown
 */
ve.init.mw.CollabTarget.prototype.tryTeardown = function () {
	this.emit( 'close' );
};

/**
 * Get the title of the imported document, if there was one
 *
 * @return {mw.Title|null} Title of imported document
 */
ve.init.mw.CollabTarget.prototype.getImportTitle = function () {
	return this.importTitle;
};

/**
 * @inheritdoc
 */
ve.init.mw.CollabTarget.prototype.getPageName = function () {
	return this.getImportTitle() || this.pageName;
};

/* Registration */

ve.init.mw.targetFactory.register( ve.init.mw.CollabTarget );

/**
 * Export tool
 */
ve.ui.MWExportTool = function VeUiMWExportTool() {
	// Parent constructor
	ve.ui.MWExportTool.super.apply( this, arguments );

	if ( OO.ui.isMobile() ) {
		this.setIcon( 'upload' );
		this.setTitle( null );
	}
};
OO.inheritClass( ve.ui.MWExportTool, ve.ui.Tool );
ve.ui.MWExportTool.static.name = 'export';
ve.ui.MWExportTool.static.displayBothIconAndLabel = !OO.ui.isMobile();
ve.ui.MWExportTool.static.group = 'export';
ve.ui.MWExportTool.static.autoAddToCatchall = false;
// eslint-disable-next-line es-x/no-regexp-prototype-flags
ve.ui.MWExportTool.static.flags = [ 'progressive', 'primary' ];
ve.ui.MWExportTool.static.title =
	OO.ui.deferMsg( 'visualeditor-rebase-client-export-start' );
ve.ui.MWExportTool.static.commandName = 'mwExportWikitext';
ve.ui.toolFactory.register( ve.ui.MWExportTool );

ve.ui.commandRegistry.register(
	new ve.ui.Command(
		'mwExportWikitext', 'window', 'open',
		{ args: [ 'mwExportWikitext' ] }
	)
);
