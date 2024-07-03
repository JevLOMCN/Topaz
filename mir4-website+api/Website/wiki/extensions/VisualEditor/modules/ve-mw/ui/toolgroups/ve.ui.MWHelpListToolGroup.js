/*!
 * VisualEditor MediaWiki UserInterface help list toolgroup classes.
 *
 * @copyright See AUTHORS.txt
 * @license The MIT License (MIT); see LICENSE.txt
 */

/**
 * MediaWiki UserInterface edit mode tool.
 *
 * This extends ListToolGroup to add a footer below the tool list.
 * The footer contains the version number.
 *
 * @class
 * @extends OO.ui.ListToolGroup
 *
 * @constructor
 * @param {OO.ui.Toolbar} toolbar
 * @param {Object} [config] Configuration options
 */
ve.ui.MWHelpListToolGroup = function VeUiMwHelpListToolGroup() {
	this.$footer = $( '<div>' ).addClass( 've-ui-mwHelpListToolGroup-tools-footer' );

	// Parent constructor
	ve.ui.MWHelpListToolGroup.super.apply( this, arguments );

	// Initialization
	this.$element.addClass( 've-ui-mwHelpListToolGroup' );
	this.$group.addClass( 've-ui-mwHelpListToolGroup-tools' );
};

/* Setup */

OO.inheritClass( ve.ui.MWHelpListToolGroup, OO.ui.ListToolGroup );

/* Static Properties */

/**
 * @static
 * @inheritdoc
 */
ve.ui.MWHelpListToolGroup.static.name = 'mwHelpList';

/* Methods */

ve.ui.MWHelpListToolGroup.prototype.insertItemElements = function () {
	// Mixin method
	OO.ui.mixin.GroupElement.prototype.insertItemElements.apply( this, arguments );

	this.$group.append( this.$footer );
};

ve.ui.MWHelpListToolGroup.prototype.setActive = function () {
	// Parent method
	ve.ui.MWHelpListToolGroup.super.prototype.setActive.apply( this, arguments );

	if ( this.active && !this.versionPromise ) {
		var $version = $( '<div>' ).addClass( 'oo-ui-pendingElement-pending' ).text( '\u00a0' );
		this.$footer.append( $version );
		this.versionPromise = ve.init.target.getLocalApi().get( {
			action: 'query',
			meta: 'siteinfo',
			siprop: 'extensions'
		} ).then( function ( response ) {
			var extension = response.query.extensions.filter( function ( ext ) {
				return ext.name === 'VisualEditor';
			} )[ 0 ];

			if ( extension && extension[ 'vcs-version' ] ) {
				$version
					.removeClass( 'oo-ui-pendingElement-pending' )
					.empty()
					.append( $( '<a>' )
						.addClass( 've-ui-mwHelpListToolGroup-version-link' )
						.attr( 'target', '_blank' )
						.attr( 'rel', 'noopener' )
						.attr( 'href', extension[ 'vcs-url' ] )
						.append( $( '<span>' )
							.addClass( 've-ui-mwHelpListToolGroup-version-label' )
							.text( ve.msg( 'visualeditor-version-label' ) + ' ' + extension[ 'vcs-version' ].slice( 0, 7 ) )
						)
					)
					.append( ' ' )
					.append( $( '<span>' )
						.addClass( 've-ui-mwHelpListToolGroup-version-date' )
						.text( extension[ 'vcs-date' ] )
					);
			} else {
				$version.remove();
			}
		}, function () {
			$version.remove();
		} );
	}
};

/* Registration */

ve.ui.toolGroupFactory.register( ve.ui.MWHelpListToolGroup );

/**
 * User guide tool.
 *
 * @class
 * @extends ve.ui.Tool
 * @constructor
 * @param {OO.ui.ToolGroup} toolGroup
 * @param {Object} [config] Configuration options
 */
ve.ui.MWUserGuideTool = function VeUiMWUserGuideTool() {
	ve.ui.MWUserGuideTool.super.apply( this, arguments );

	this.setDisabled( false );
};
OO.inheritClass( ve.ui.MWUserGuideTool, ve.ui.Tool );
ve.ui.MWUserGuideTool.static.name = 'mwUserGuide';
ve.ui.MWUserGuideTool.static.group = 'help';
ve.ui.MWUserGuideTool.static.icon = 'help';
ve.ui.MWUserGuideTool.static.title =
	OO.ui.deferMsg( 'visualeditor-help-label' );
ve.ui.MWUserGuideTool.static.autoAddToCatchall = false;

// Never disabled
ve.ui.MWUserGuideTool.prototype.onUpdateState = function () {};

ve.ui.MWUserGuideTool.prototype.onSelect = function () {
	this.setActive( false );
	window.open( new mw.Title( ve.msg( 'visualeditor-help-link' ) ).getUrl() );
};

ve.ui.toolFactory.register( ve.ui.MWUserGuideTool );

/**
 * Feedback dialog tool.
 *
 * @class
 * @extends ve.ui.Tool
 * @constructor
 * @param {OO.ui.ToolGroup} toolGroup
 * @param {Object} [config] Configuration options
 */
ve.ui.MWFeedbackDialogTool = function VeUiMWFeedbackDialogTool() {
	ve.ui.MWFeedbackDialogTool.super.apply( this, arguments );

	this.setDisabled( false );
};
OO.inheritClass( ve.ui.MWFeedbackDialogTool, ve.ui.Tool );
ve.ui.MWFeedbackDialogTool.static.name = 'mwFeedbackDialog';
ve.ui.MWFeedbackDialogTool.static.group = 'help';
ve.ui.MWFeedbackDialogTool.static.icon = 'speechBubble';
ve.ui.MWFeedbackDialogTool.static.title =
	OO.ui.deferMsg( 'visualeditor-feedback-tool' );
ve.ui.MWFeedbackDialogTool.static.autoAddToCatchall = false;

// Never disabled
ve.ui.MWFeedbackDialogTool.prototype.onUpdateState = function () {};

ve.ui.MWFeedbackDialogTool.prototype.onSelect = function () {
	var tool = this;

	this.setActive( false );

	if ( !this.feedbackPromise ) {
		this.feedbackPromise = mw.loader.using( 'mediawiki.feedback' ).then( function () {
			var mode = tool.toolbar.getSurface().getMode();

			// This can't be constructed until the editor has loaded as it uses special messages
			var feedbackConfig = {
				bugsLink: 'https://phabricator.wikimedia.org/maniphest/task/edit/form/1/?projects=VisualEditor',
				showUseragentCheckbox: true,
				useragentCheckboxMandatory: true
			};

			// If so configured, tell mw.feedback that we're posting to a remote wiki and set the title
			var veConfig = mw.config.get( 'wgVisualEditorConfig' );
			if ( veConfig.feedbackApiUrl ) {
				feedbackConfig.apiUrl = veConfig.feedbackApiUrl;
				feedbackConfig.title = new mw.Title(
					mode === 'source' ?
						veConfig.sourceFeedbackTitle : veConfig.feedbackTitle
				);
			} else {
				feedbackConfig.title = new mw.Title(
					mode === 'source' ?
						ve.msg( 'visualeditor-feedback-source-link' ) : ve.msg( 'visualeditor-feedback-link' )
				);
			}

			return new mw.Feedback( feedbackConfig );
		} );
	}
	this.feedbackPromise.done( function ( feedback ) {
		feedback.launch( {
			message: ve.msg( 'visualeditor-feedback-defaultmessage', location.toString() )
		} );
	} );
};

ve.ui.toolFactory.register( ve.ui.MWFeedbackDialogTool );
