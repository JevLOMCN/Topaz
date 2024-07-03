/**
 * Represents a generic article page with the editor overlay open
 *
 * @class ArticlePageWithEditorOverlay
 * @extends MinervaPage
 * @example
 * https://en.m.wikipedia.org/wiki/Barack_Obama#/editor/0
 */

'use strict';

const MinervaPage = require( './minerva_page' );

class ArticlePageWithEditorOverlay extends MinervaPage {
	get editor_overlay_element() {
		return $( '.overlay' );
	}

	// overlay components
	get editor_load_basic_element() {
		return $( '.ve-loadbasiceditor' );
	}

	get editor_textarea_element() {
		return $( '.overlay .wikitext-editor, .overlay .surface' );
	}

	get continue_element() {
		return $( '.overlay .continue' );
	}

	get submit_element() {
		return $( '.overlay .submit' );
	}
}

module.exports = new ArticlePageWithEditorOverlay();
