var ResizingDragBar = require( './ResizingDragBar.js' );

/**
 * This is a layout with two resizable panes.
 *
 * @class
 * @constructor
 * @extends OO.ui.Layout
 * @param {Object} [config] Configuration options
 */
function TwoPaneLayout( config ) {
	// Configuration initialization
	config = config || {};
	TwoPaneLayout.super.call( this, config );

	this.$pane1 = $( '<div>' ).addClass( 'ext-WikiEditor-twopanes-pane1' );
	var middleDragBar = new ResizingDragBar( { isEW: true } );
	this.$pane2 = $( '<div>' ).addClass( 'ext-WikiEditor-twopanes-pane2' );

	this.$element.addClass( 'ext-WikiEditor-twopanes-TwoPaneLayout' );
	this.$element.append( this.$pane1, middleDragBar.$element, this.$pane2 );
}

OO.inheritClass( TwoPaneLayout, OO.ui.Layout );

/**
 * Set pane 1 content.
 *
 * @public
 * @param {jQuery|string|Function|OO.ui.HtmlSnippet} content
 */
TwoPaneLayout.prototype.setPane1 = function ( content ) {
	this.setContent( this.$pane1, content );
};

/**
 * @public
 * @return {jQuery}
 */
TwoPaneLayout.prototype.getPane1 = function () {
	return this.$pane1;
};

/**
 * Set pane 2 content.
 *
 * @public
 * @param {jQuery|string|Function|OO.ui.HtmlSnippet} content
 */
TwoPaneLayout.prototype.setPane2 = function ( content ) {
	this.setContent( this.$pane2, content );
};

/**
 * @public
 * @return {jQuery}
 */
TwoPaneLayout.prototype.getPane2 = function () {
	return this.$pane2;
};

/**
 * @private
 * @param {jQuery} $container The container to set the content in.
 * @param {jQuery|string|Function|OO.ui.HtmlSnippet} content The content to set.
 */
TwoPaneLayout.prototype.setContent = function ( $container, content ) {
	if ( typeof content === 'string' ) {
		$container.text( content );
	} else if ( content instanceof OO.ui.HtmlSnippet ) {
		$container.html( content.toString() );
	} else if ( content instanceof $ ) {
		$container.empty().append( content );
	} else {
		$container.empty();
	}
};

module.exports = TwoPaneLayout;
