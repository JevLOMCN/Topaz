/*!
 * VisualEditor UserInterface MWCommentInspector class.
 *
 * @copyright See AUTHORS.txt
 * @license The MIT License (MIT); see LICENSE.txt
 */

/**
 * Inspector for editing MediaWiki comments.
 *
 * @class
 * @extends ve.ui.CommentInspector
 *
 * @constructor
 * @param {Object} [config] Configuration options
 */
ve.ui.MWCommentInspector = function VeUiMWCommentInspector() {
	// Parent constructor
	ve.ui.MWCommentInspector.super.apply( this, arguments );
};

/* Inheritance */

OO.inheritClass( ve.ui.MWCommentInspector, ve.ui.CommentInspector );

/* Static properties */

ve.ui.MWCommentInspector.static.name = 'comment';

ve.ui.MWCommentInspector.static.modelClasses = [ ve.dm.CommentNode ];

/* Methods */

/**
 * @inheritdoc
 */
ve.ui.MWCommentInspector.prototype.initialize = function () {
	// Parent method
	ve.ui.MWCommentInspector.super.prototype.initialize.apply( this, arguments );

	this.textWidget.$input.on( 'copy', this.onCopy.bind( this ) );
};

ve.ui.MWCommentInspector.prototype.onCopy = function ( e ) {
	var clipboardData = e.originalEvent.clipboardData,
		selection = ( e.target.value ).slice( e.target.selectionStart, e.target.selectionEnd );
	if ( ve.isClipboardDataFormatsSupported( e, true ) ) {
		// We are in an environment where setting text/x-wiki will work
		e.preventDefault();

		clipboardData.setData( 'text/x-wiki', selection );
		clipboardData.setData( 'text/plain', selection ); // If you're pasting to not-VE
	}
};

/* Registration */

ve.ui.windowFactory.register( ve.ui.MWCommentInspector );
