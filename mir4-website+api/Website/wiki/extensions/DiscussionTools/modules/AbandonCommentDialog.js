function AbandonCommentDialog() {
	// Parent constructor
	AbandonCommentDialog.super.apply( this, arguments );
}

/* Inheritance */

OO.inheritClass( AbandonCommentDialog, mw.widgets.AbandonEditDialog );

AbandonCommentDialog.static.name = 'abandoncomment';
AbandonCommentDialog.static.message = OO.ui.deferMsg( 'discussiontools-replywidget-abandon' );
AbandonCommentDialog.static.actions = OO.copy( AbandonCommentDialog.static.actions );
AbandonCommentDialog.static.actions[ 0 ].label =
	OO.ui.deferMsg( 'discussiontools-replywidget-abandon-discard' );

AbandonCommentDialog.static.actions[ 1 ].label =
	OO.ui.deferMsg( 'discussiontools-replywidget-abandon-keep' );

OO.ui.getWindowManager().addWindows( [ new AbandonCommentDialog() ] );
