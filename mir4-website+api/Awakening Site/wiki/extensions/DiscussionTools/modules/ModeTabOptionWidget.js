function ModeTabOptionWidget() {
	// Parent constructor
	ModeTabOptionWidget.super.apply( this, arguments );

	this.$element.addClass( 'ext-discussiontools-ui-modeTab' );
}

OO.inheritClass( ModeTabOptionWidget, OO.ui.TabOptionWidget );

ModeTabOptionWidget.static.highlightable = true;

module.exports = ModeTabOptionWidget;
