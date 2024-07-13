/*!
 * VisualEditor UserInterface MWSaveDialogAction class.
 *
 * @copyright See AUTHORS.txt
 */

// TODO: Can perhaps extract a lot of the dialog lifecycle management code
// from ArticleTarget and put it here.

/**
 * Save action.
 *
 * @class
 * @extends ve.ui.Action
 *
 * @constructor
 * @param {ve.ui.Surface} surface Surface to act on
 */
ve.ui.MWSaveDialogAction = function VeUiMWSaveDialogAction() {
	// Parent constructor
	ve.ui.MWSaveDialogAction.super.apply( this, arguments );
};

/* Inheritance */

OO.inheritClass( ve.ui.MWSaveDialogAction, ve.ui.Action );

/* Static Properties */

ve.ui.MWSaveDialogAction.static.name = 'mwSaveDialog';

ve.ui.MWSaveDialogAction.static.methods = [ 'save', 'review', 'preview' ];

/* Methods */

/**
 * Open the save dialog
 *
 * @param {string} checkbox Checkbox to toggle after opening
 * @return {boolean} Action was executed
 */
ve.ui.MWSaveDialogAction.prototype.save = function ( checkbox ) {
	ve.init.target.showSaveDialog( null, checkbox );
	return true;
};

/**
 * Open the save dialog, and set it to the review panel
 *
 * @return {boolean} Action was executed
 */
ve.ui.MWSaveDialogAction.prototype.review = function () {
	ve.init.target.showSaveDialog( 'review' );
	return true;
};

/**
 * Open the save dialog, and set it to the preview panel
 *
 * @return {boolean} Action was executed
 */
ve.ui.MWSaveDialogAction.prototype.preview = function () {
	ve.init.target.showSaveDialog( 'preview' );
	return true;
};

/* Registration */

ve.ui.actionFactory.register( ve.ui.MWSaveDialogAction );

/* Commands */

/**
 * Command which can only execute when the document is saveable
 *
 * @class
 * @extends ve.ui.Command
 *
 * @constructor
 */
ve.ui.MWSaveCommand = function VeUiMwSaveCommand() {
	// Parent constructor
	ve.ui.MWSaveCommand.super.apply( this, arguments );
};

OO.inheritClass( ve.ui.MWSaveCommand, ve.ui.Command );

/**
 * @inheritdoc ve.ui.Command
 */
ve.ui.MWSaveCommand.prototype.isExecutable = function () {
	// Parent method
	return ve.ui.MWSaveCommand.super.prototype.isExecutable.apply( this, arguments ) &&
		ve.init.target.isSaveable();
};

ve.ui.commandRegistry.register(
	new ve.ui.MWSaveCommand(
		'showSave', 'mwSaveDialog', 'save'
	)
);
ve.ui.commandRegistry.register(
	new ve.ui.MWSaveCommand(
		'showChanges', 'mwSaveDialog', 'review'
	)
);
if ( mw.libs.ve.isWikitextAvailable ) {
	// Ensure wikitextCommandRegistry has finished loading
	mw.loader.using( 'ext.visualEditor.mwwikitext' ).then( function () {
		ve.ui.wikitextCommandRegistry.register(
			new ve.ui.MWSaveCommand(
				'showPreview', 'mwSaveDialog', 'preview'
			)
		);
	} );
}
ve.ui.commandRegistry.register(
	new ve.ui.MWSaveCommand(
		'saveMinoredit', 'mwSaveDialog', 'save',
		{ args: [ 'wpMinoredit' ] }
	)
);
ve.ui.commandRegistry.register(
	new ve.ui.MWSaveCommand(
		'saveWatchthis', 'mwSaveDialog', 'save',
		{ args: [ 'wpWatchthis' ] }
	)
);

/* Triggers & command help */

( function () {
	var accessKeyPrefix = $.fn.updateTooltipAccessKeys.getAccessKeyPrefix().replace( /-/g, '+' ),
		shortcuts = [
			{
				command: 'showSave',
				accessKey: 'accesskey-save',
				label: function () {
					return ve.init.target.getSaveButtonLabel();
				}
			},
			{
				command: 'showChanges',
				accessKey: 'accesskey-diff',
				label: OO.ui.deferMsg( 'visualeditor-savedialog-label-review' )
			},
			{
				command: 'showPreview',
				accessKey: 'accesskey-preview',
				label: OO.ui.deferMsg( 'showpreview' )
			},
			{
				command: 'saveMinoredit',
				accessKey: 'accesskey-minoredit',
				label: OO.ui.deferMsg( 'tooltip-minoredit' )
			},
			{
				command: 'saveWatchthis',
				accessKey: 'accesskey-watch',
				label: OO.ui.deferMsg( 'tooltip-watch' )
			}
		];

	shortcuts.forEach( function ( shortcut ) {
		// The following messages can be used here:
		// * accesskey-save
		// * accesskey-diff
		// * accesskey-preview
		// * accesskey-minoredit
		// * accesskey-watch
		var accessKey = ve.msg( shortcut.accessKey );
		if ( accessKey !== '-' && accessKey !== '' ) {
			try {
				ve.ui.triggerRegistry.register(
					shortcut.command, new ve.ui.Trigger( accessKeyPrefix + accessKey )
				);
			} catch ( e ) {
				mw.log.warn( 'Invalid accesskey data? Failed to register ' + accessKeyPrefix + accessKey );
				return;
			}
			ve.ui.commandHelpRegistry.register( 'other', shortcut.command, {
				trigger: shortcut.command,
				label: shortcut.label
			} );
			ve.ui.MWCommandHelpDialog.static.commandGroups.other.demote = ve.ui.MWCommandHelpDialog.static.commandGroups.other.demote || [];
			ve.ui.MWCommandHelpDialog.static.commandGroups.other.demote.push( shortcut.command );
		}
	} );
}() );
