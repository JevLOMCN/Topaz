/**
 * This is a layout for displaying an error message.
 *
 * @class
 * @constructor
 * @extends OO.ui.Layout
 * @param {Object} [config] Configuration options
 */
function ErrorLayout( config ) {
	config = config || {};
	ErrorLayout.super.call( this, config );

	var $image = $( '<div>' ).addClass( 'ext-WikiEditor-image-realtimepreview-error' );
	var $title = $( '<h3>' ).text( mw.msg( 'wikieditor-realtimepreview-error' ) );
	this.$message = $( '<div>' ).addClass( 'ext-WikiEditor-realtimepreview-error-msg' );
	this.reloadButton = new OO.ui.ButtonWidget( {
		icon: 'reload',
		label: mw.msg( 'wikieditor-realtimepreview-reload' ),
		framed: false
	} );

	this.$element.addClass( 'ext-WikiEditor-realtimepreview-ErrorLayout' );
	this.$element.append( $image, $title, this.$message, this.reloadButton.$element );
}

OO.inheritClass( ErrorLayout, OO.ui.Layout );

/**
 * @public
 * @return {OO.ui.ButtonWidget}
 */
ErrorLayout.prototype.getReloadButton = function () {
	return this.reloadButton;
};

/**
 * Set the displayed error message.
 *
 * @public
 * @param {jQuery} $errorMsg The message to display.
 */
ErrorLayout.prototype.setMessage = function ( $errorMsg ) {
	this.$message
		.empty()
		.append( $errorMsg );
};

module.exports = ErrorLayout;
