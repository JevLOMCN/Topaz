/*!
 * VisualEditor UserInterface HistoryTool classes.
 *
 * @copyright See AUTHORS.txt
 */

/**
 * UserInterface history tool.
 *
 * @class
 * @extends ve.ui.Tool
 * @constructor
 * @param {OO.ui.ToolGroup} toolGroup
 * @param {Object} [config] Configuration options
 */
ve.ui.HistoryTool = function VeUiHistoryTool() {
	// Parent constructor
	ve.ui.HistoryTool.super.apply( this, arguments );

	// Events
	this.toolbar.connect( this, { surfaceChange: 'onSurfaceChange' } );
};

/* Inheritance */

OO.inheritClass( ve.ui.HistoryTool, ve.ui.Tool );

/* Methods */

/**
 * Handle surface change events from the toolbar
 *
 * @param {ve.ui.Surface|null} oldSurface
 * @param {ve.ui.Surface|null} newSurface
 */
ve.ui.HistoryTool.prototype.onSurfaceChange = function ( oldSurface, newSurface ) {
	if ( oldSurface ) {
		oldSurface.getModel().disconnect( this );
	}
	if ( newSurface ) {
		newSurface.getModel().connect( this, { history: 'onHistory' } );
		this.onUpdateState( newSurface.getModel().getFragment() );
	}
};

/**
 * Handle history events on the surface model
 */
ve.ui.HistoryTool.prototype.onHistory = function () {
	this.onUpdateState( this.toolbar.getSurface().getModel().getFragment() );
};

/**
 * @inheritdoc
 */
ve.ui.HistoryTool.prototype.destroy = function () {
	this.toolbar.getSurface().getModel().disconnect( this );
	// Parent method
	ve.ui.HistoryTool.super.prototype.destroy.call( this );
};

/**
 * UserInterface undo tool.
 *
 * @class
 * @extends ve.ui.HistoryTool
 * @constructor
 * @param {OO.ui.ToolGroup} toolGroup
 * @param {Object} [config] Configuration options
 */
ve.ui.UndoHistoryTool = function VeUiUndoHistoryTool() {
	// Parent constructor
	ve.ui.UndoHistoryTool.super.apply( this, arguments );
};
OO.inheritClass( ve.ui.UndoHistoryTool, ve.ui.HistoryTool );
ve.ui.UndoHistoryTool.static.name = 'undo';
ve.ui.UndoHistoryTool.static.group = 'history';
ve.ui.UndoHistoryTool.static.icon = 'undo';
ve.ui.UndoHistoryTool.static.title =
	OO.ui.deferMsg( 'visualeditor-historybutton-undo-tooltip' );
ve.ui.UndoHistoryTool.static.commandName = 'undo';
ve.ui.toolFactory.register( ve.ui.UndoHistoryTool );

/**
 * UserInterface redo tool.
 *
 * @class
 * @extends ve.ui.HistoryTool
 * @constructor
 * @param {OO.ui.ToolGroup} toolGroup
 * @param {Object} [config] Configuration options
 */
ve.ui.RedoHistoryTool = function VeUiRedoHistoryTool() {
	// Parent constructor
	ve.ui.RedoHistoryTool.super.apply( this, arguments );
};
OO.inheritClass( ve.ui.RedoHistoryTool, ve.ui.HistoryTool );
ve.ui.RedoHistoryTool.static.name = 'redo';
ve.ui.RedoHistoryTool.static.group = 'history';
ve.ui.RedoHistoryTool.static.icon = 'redo';
ve.ui.RedoHistoryTool.static.title =
	OO.ui.deferMsg( 'visualeditor-historybutton-redo-tooltip' );
ve.ui.RedoHistoryTool.static.commandName = 'redo';
ve.ui.toolFactory.register( ve.ui.RedoHistoryTool );
