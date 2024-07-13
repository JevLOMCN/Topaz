function ModeTabSelectWidget() {
	// Parent constructor
	ModeTabSelectWidget.super.apply( this, arguments );
}

OO.inheritClass( ModeTabSelectWidget, OO.ui.TabSelectWidget );

ModeTabSelectWidget.prototype.onDocumentKeyDown = function ( e ) {
	// Handle Space like Enter
	if ( e.keyCode === OO.ui.Keys.SPACE ) {
		e = $.Event( e, { keyCode: OO.ui.Keys.ENTER } );
	}
	ModeTabSelectWidget.super.prototype.onDocumentKeyDown.call( this, e );
};

module.exports = ModeTabSelectWidget;
