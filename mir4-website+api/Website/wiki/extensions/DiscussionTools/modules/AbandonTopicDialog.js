function AbandonTopicDialog() {
	// Parent constructor
	AbandonTopicDialog.super.apply( this, arguments );
}

/* Inheritance */

OO.inheritClass( AbandonTopicDialog, mw.widgets.AbandonEditDialog );

AbandonTopicDialog.static.name = 'abandontopic';
AbandonTopicDialog.static.message = OO.ui.deferMsg( 'discussiontools-replywidget-abandontopic' );
AbandonTopicDialog.static.actions = OO.copy( AbandonTopicDialog.static.actions );
AbandonTopicDialog.static.actions[ 0 ].label =
	OO.ui.deferMsg( 'discussiontools-replywidget-abandontopic-discard' );

AbandonTopicDialog.static.actions[ 1 ].label =
	OO.ui.deferMsg( 'discussiontools-replywidget-abandontopic-keep' );

OO.ui.getWindowManager().addWindows( [ new AbandonTopicDialog() ] );
