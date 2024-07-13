/**
 * Represents a generic article page with the editor overlay open
 *
 * @class ArticlePageWithOverlay
 * @extends MinervaPage
 * @example
 * https://en.m.wikipedia.org/wiki/Barack_Obama#/editor/0
 */

'use strict';

const MinervaPage = require( './minerva_page' );

class ArticlePageWithOverlay extends MinervaPage {
	get overlay_element() {
		return $( '.overlay' );
	}

	// overlay components
	get overlay_content_element() {
		return $( '.overlay-content' );
	}

	get overlay_close_element() {
		return $( '.overlay .cancel' );
	}
}

module.exports = new ArticlePageWithOverlay();
