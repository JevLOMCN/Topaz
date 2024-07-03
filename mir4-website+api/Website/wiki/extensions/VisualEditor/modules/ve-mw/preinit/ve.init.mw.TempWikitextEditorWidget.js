/*!
 * VisualEditor MediaWiki temporary wikitext editor widget
 *
 * @copyright See AUTHORS.txt
 * @license The MIT License (MIT); see LICENSE.txt
 */

mw.libs.ve = mw.libs.ve || {};

/**
 * MediaWiki temporary wikitext editor widget
 *
 * This widget can be used to show the user a basic editing interface
 * while VE libraries are still loading.
 *
 * It has a similar API to OO.ui.InputWidget, but is designed to
 * be loaded before any core VE code or dependencies, e.g. OOUI.
 *
 * @class
 *
 * @constructor
 * @param {Object} config Configuration options
 * @cfg {string} value Raw wikitext to edit
 */
mw.libs.ve.MWTempWikitextEditorWidget = function VeUiMwTempWikitextEditorWidget( config ) {
	var conf = mw.config.get( 'wgVisualEditor' ),
		dir = conf.pageLanguageDir,
		lang = conf.pageLanguageCode;

	this.$element = $( '<textarea>' )
		.addClass( 've-init-mw-tempWikitextEditorWidget ' )
		// The following classes are used here:
		// * mw-editfont-monospace
		// * mw-editfont-sans-serif
		// * mw-editfont-serif
		.addClass( 'mw-editfont-' + mw.user.options.get( 'editfont' ) )
		// The following classes are used here:
		// * mw-content-ltr
		// * mw-content-rtl
		.addClass( 'mw-content-' + dir )
		.attr( {
			lang: lang,
			dir: dir
		} )
		.val( config.value );
};

/**
 * Focus the input and move the cursor to the start.
 *
 * @return {mw.libs.ve.MWTempWikitextEditorWidget}
 * @chainable
 */
mw.libs.ve.MWTempWikitextEditorWidget.prototype.moveCursorToStart = function () {
	// Move cursor to start
	this.$element[ 0 ].setSelectionRange( 0, 0 );
	this.focus();
	return this;
};

/**
 * Expand the height of the text to fit the contents
 *
 * @return {mw.libs.ve.MWTempWikitextEditorWidget}
 * @chainable
 */
mw.libs.ve.MWTempWikitextEditorWidget.prototype.adjustSize = function () {
	// Don't bother with reducing height for simplicity
	this.$element.height( this.$element[ 0 ].scrollHeight );
	return this;
};

/**
 * Focus the input
 *
 * @return {mw.libs.ve.MWTempWikitextEditorWidget}
 * @chainable
 */
mw.libs.ve.MWTempWikitextEditorWidget.prototype.focus = function () {
	this.$element[ 0 ].focus();
	return this;
};

/**
 * @return {string} Raw, possibly edited wikitext
 */
mw.libs.ve.MWTempWikitextEditorWidget.prototype.getValue = function () {
	return this.$element.val();
};

/**
 * Get the selection range
 *
 * @return {Object} Object containing numbers 'from' and 'to'
 */
mw.libs.ve.MWTempWikitextEditorWidget.prototype.getRange = function () {
	var input = this.$element[ 0 ],
		start = input.selectionStart,
		end = input.selectionEnd,
		isBackwards = input.selectionDirection === 'backward';

	return {
		from: isBackwards ? end : start,
		to: isBackwards ? start : end
	};
};
