var registries = require( './dt.ui.registries.js' );

/**
 * DiscussionTools-specific target, inheriting from the stand-alone target
 *
 * @class
 * @extends ve.init.mw.Target
 *
 * @param {mw.dt.ReplyWidgetVisual} replyWidget
 * @param {Object} config Configuration options
 */
function CommentTarget( replyWidget, config ) {
	config = config || {};

	this.replyWidget = replyWidget;

	// Parent constructor
	CommentTarget.super.call( this, ve.extendObject( {
		toolbarConfig: { position: 'top' }
	}, config ) );
}

/* Inheritance */

OO.inheritClass( CommentTarget, ve.init.mw.Target );

/* Static methods */

CommentTarget.static.name = 'discussionTools';

CommentTarget.static.modes = [ 'visual', 'source' ];

if ( OO.ui.isMobile() ) {
	// Mobile currently expects one tool per group
	CommentTarget.static.toolbarGroups = [
		{
			name: 'history',
			include: [ 'undo' ]
		},
		{
			name: 'style',
			classes: [ 've-test-toolbar-style' ],
			type: 'list',
			icon: 'textStyle',
			title: OO.ui.deferMsg( 'visualeditor-toolbar-style-tooltip' ),
			label: OO.ui.deferMsg( 'visualeditor-toolbar-style-tooltip' ),
			invisibleLabel: true,
			include: [ { group: 'textStyle' }, 'language', 'clear' ],
			forceExpand: [ 'bold', 'italic', 'clear' ],
			promote: [ 'bold', 'italic' ],
			demote: [ 'strikethrough', 'code', 'underline', 'language', 'clear' ]
		},
		{
			name: 'link',
			include: [ 'link' ]
		},
		{
			name: 'other',
			include: [ 'usernameCompletion' ]
		},
		{
			name: 'editMode',
			type: 'list',
			icon: 'edit',
			title: OO.ui.deferMsg( 'visualeditor-mweditmode-tooltip' ),
			label: OO.ui.deferMsg( 'visualeditor-mweditmode-tooltip' ),
			invisibleLabel: true,
			include: [ 'editModeVisual', 'editModeSource' ]
		}
	];
} else {
	CommentTarget.static.toolbarGroups = [
		{
			name: 'style',
			title: OO.ui.deferMsg( 'visualeditor-toolbar-style-tooltip' ),
			include: [ 'bold', 'italic', 'moreTextStyle' ]
		},
		{
			name: 'link',
			include: [ 'link' ]
		},
		{
			name: 'specialCharacter',
			include: [ 'specialCharacter' ]
		},
		{
			name: 'other',
			include: [ 'usernameCompletion' ]
		}
	];
}

CommentTarget.static.importRules = ve.copy( CommentTarget.static.importRules );

CommentTarget.static.importRules.external.blacklist = ve.extendObject(
	{},
	CommentTarget.static.importRules.external.blacklist,
	{
		// Annotations
		// Allow pasting external links
		'link/mwExternal': false
	}
);

CommentTarget.static.importRulesForReplyTool = ve.copy( CommentTarget.static.importRules );

CommentTarget.static.importRulesForReplyTool.external.conversions = ve.extendObject(
	{},
	CommentTarget.static.importRulesForReplyTool.external.conversions,
	{
		mwHeading: 'paragraph'
	}
);

CommentTarget.static.importRulesForReplyTool.external.blacklist = ve.extendObject(
	{},
	CommentTarget.static.importRulesForReplyTool.external.blacklist,
	{
		// Strip all table structure
		mwTable: true,
		tableSection: true,
		tableRow: true,
		tableCell: true
	}
);

CommentTarget.prototype.attachToolbar = function () {
	this.replyWidget.$headerWrapper.append(
		this.getToolbar().$element.append( this.replyWidget.modeTabSelect ? this.replyWidget.modeTabSelect.$element : null )
	);
	this.getToolbar().$element.prepend( this.getSurface().getToolbarDialogs().$element );
};

CommentTarget.prototype.getSurfaceConfig = function ( config ) {
	config = ve.extendObject( { mode: this.defaultMode }, config );
	return CommentTarget.super.prototype.getSurfaceConfig.call( this, ve.extendObject( {
		commandRegistry: config.mode === 'source' ? registries.wikitextCommandRegistry : registries.commandRegistry,
		sequenceRegistry: config.mode === 'source' ? registries.wikitextSequenceRegistry :
			this.replyWidget.isNewTopic ? registries.sequenceRegistry : registries.sequenceRegistryForReplyTool,
		dataTransferHandlerFactory: config.mode === 'source' ? ve.ui.wikitextDataTransferHandlerFactory : ve.ui.dataTransferHandlerFactory,
		importRules: this.replyWidget.isNewTopic ? this.constructor.static.importRules : this.constructor.static.importRulesForReplyTool,
		// eslint-disable-next-line no-jquery/no-global-selector
		$overlayContainer: $( '#content' )
	}, config ) );
};

CommentTarget.prototype.editSource = function () {
	this.replyWidget.switch( 'source' );
};

CommentTarget.prototype.switchToVisualEditor = function () {
	this.replyWidget.switch( 'visual' );
};

/* Registration */

ve.init.mw.targetFactory.register( CommentTarget );

module.exports = CommentTarget;
