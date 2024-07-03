/* global RealtimePreview */
/**
 * @class
 * @constructor
 * @param {RealtimePreview} realtimePreview
 * @param {OO.ui.ButtonWidget} reloadHoverButton
 */
function ManualWidget( realtimePreview, reloadHoverButton ) {
	var config = {
		classes: [ 'ext-WikiEditor-ManualWidget' ],
		$element: $( '<a>' )
	};
	ManualWidget.super.call( this, config );

	// Mixins.
	OO.ui.mixin.AccessKeyedElement.call( this, {} );
	OO.ui.mixin.ButtonElement.call( this, $.extend( {
		$button: this.$element
	}, config ) );
	OO.ui.mixin.IconElement.call( this, { icon: 'reload' } );
	OO.ui.mixin.TitledElement.call( this, {
		title: mw.msg( 'wikieditor-realtimepreview-reload-title' )
	} );

	this.reloadHoverButton = reloadHoverButton;

	// UI elements.
	var $reloadLabel = $( '<span>' )
		.text( mw.msg( 'wikieditor-realtimepreview-manual' ) );
	var $reloadButton = $( '<span>' )
		.addClass( 'ext-WikiEditor-realtimepreview-manual-reload' )
		.text( mw.msg( 'wikieditor-realtimepreview-reload' ) );
	this.connect( realtimePreview, {
		click: function () {
			if ( !this.isScreenWideEnough() ) {
				// Do nothing if realtime preview is not visible.
				return;
			}
			// Only refresh the preview if we're enabled.
			if ( this.enabled ) {
				this.doRealtimePreview();
			}
			mw.hook( 'ext.WikiEditor.realtimepreview.reloadManual' ).fire( this );
		}.bind( realtimePreview )
	} );
	this.$element.append( this.$icon, $reloadLabel, $reloadButton );
}

OO.inheritClass( ManualWidget, OO.ui.Widget );
OO.mixinClass( ManualWidget, OO.ui.mixin.AccessKeyedElement );
OO.mixinClass( ManualWidget, OO.ui.mixin.ButtonElement );
OO.mixinClass( ManualWidget, OO.ui.mixin.IconElement );
OO.mixinClass( ManualWidget, OO.ui.mixin.TitledElement );

ManualWidget.prototype.toggle = function ( show ) {
	ManualWidget.super.prototype.toggle.call( this, show );
	if ( show ) {
		this.reloadHoverButton.$element.remove();
		// Use the same access key as the hover reload button, because this won't ever be displayed at the same time as that.
		this.setAccessKey( mw.msg( 'accesskey-wikieditor-realtimepreview' ) );
	}
};

module.exports = ManualWidget;
