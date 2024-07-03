function MWUsernameCompletionTool() {
	// Parent constructor
	MWUsernameCompletionTool.super.apply( this, arguments );
}

OO.inheritClass( MWUsernameCompletionTool, ve.ui.Tool );

// Static
MWUsernameCompletionTool.static.commandName = 'insertAndOpenMWUsernameCompletions';
MWUsernameCompletionTool.static.name = 'usernameCompletion';
MWUsernameCompletionTool.static.icon = 'userAdd';
MWUsernameCompletionTool.static.title = OO.ui.deferMsg( 'discussiontools-replywidget-mention-tool-title' );
MWUsernameCompletionTool.static.autoAddToCatchall = false;

ve.ui.toolFactory.register( MWUsernameCompletionTool );
