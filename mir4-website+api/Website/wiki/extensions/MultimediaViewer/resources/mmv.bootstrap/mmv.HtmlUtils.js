/*
 * This file is part of the MediaWiki extension MediaViewer.
 *
 * MediaViewer is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 2 of the License, or
 * (at your option) any later version.
 *
 * MediaViewer is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with MediaViewer.  If not, see <http://www.gnu.org/licenses/>.
 */

( function () {

	/**
	 * Shared cache between HtmlUtils instances to store the results of expensive text operations.
	 *
	 * @member HtmlUtils
	 * @private
	 * @static
	 * @type {{text: Object.<string, string>, textWithLinks: Object.<string, string>, textWithTags: Object.<string, string>}}
	 */
	const cache = {
		text: {},
		textWithLinks: {},
		textWithTags: {}
	};

	/**
	 * Helper class that does various HTML-to-text transformations
	 */
	class HtmlUtils {
		constructor() { }

		/**
		 * Returns a jQuery node which contains the given HTML (wrapped into a `<div>` - this is
		 * necessary since an arbitrary HTML string might not have a jQuery representation).
		 *
		 * @param {string|HTMLElement|jQuery} html
		 * @return {jQuery}
		 */
		wrapAndJquerify( html ) {
			if ( this.isJQueryOrHTMLElement( html ) ) {
				return $( '<div>' ).append( $( html ).clone() );
			} else if ( typeof html === 'string' ) {
				return $( '<div>' ).html( html );
			} else {
				mw.log.warn( 'wrapAndJquerify: unknown type', html );
				throw new Error( 'wrapAndJquerify: unknown type' );
			}
		}

		/**
		 * Returns true of the object is a jQuery object or an HTMLElement, false otherwise
		 *
		 * @param {string|HTMLElement|jQuery} html
		 * @return {boolean}
		 */
		isJQueryOrHTMLElement( html ) {
			if ( html instanceof $ ) {
				return true;
			}

			if ( window.HTMLElement ) {
				if ( html instanceof HTMLElement ) {
					return true;
				}
			}

			return false;
		}

		/**
		 * Filters display:none and <style></style> children of a node.
		 * The root element is never filtered, and generally ignored (i.e. whether the root element is
		 * visible won't affect the filtering).
		 * Works in place.
		 *
		 * @param {jQuery} $jq
		 */
		filterInvisible( $jq ) {
			// We are not using :visible because
			// 1) it would require appending $jq to the document which makes things complicated;
			// 2) the main difference is that it looks for CSS rules hiding the element;
			//    since this function is intended to be used on html originating from a different
			//    document, possibly a different site, that would probably have unexpected results.
			$jq
				.find( '[style]' )
				.filter( function () {
					return this.style.display === 'none';
				} )
				.remove();

			// TemplateStyles can generate inline style tags
			$jq
				.find( 'style' )
				.remove();
		}

		/**
		 * Discards all nodes which do not match the whitelist,
		 * but keeps the text and whitelisted nodes inside them.
		 * Works in-place.
		 *
		 * @param {jQuery} $el
		 * @param {string} whitelist a jQuery selector string such as 'a, span, br'
		 */
		whitelistHtml( $el, whitelist ) {
			let $prev;
			let $child = $el.children().first();

			while ( $child && $child.length ) {
				const child = $child.get( 0 );

				if ( child.nodeType !== child.ELEMENT_NODE ) {
					return;
				}

				this.whitelistHtml( $child, whitelist );

				if ( !$child.is( whitelist ) ) {
					$prev = $child.prev();
					$child.replaceWith( $child.contents() );
				} else {
					$prev = $child;
				}

				if ( $prev && $prev.length === 1 ) {
					$child = $prev.next();
				} else {
					$child = $el.children().first();
				}
			}
		}

		/**
		 * Adds a whitespace to block elements. This is useful if you want to convert the contents
		 * to text and don't want words that are visually separate (e.g. table cells) to be fused.
		 * Works in-place.
		 *
		 * @param {jQuery} $el
		 */
		appendWhitespaceToBlockElements( $el ) {
			// the list of what elements to add whitespace to is somewhat ad-hoc (not all of these
			// are technically block-level elements, and a lot of block-level elements are missing)
			// but will hopefully cover the common cases where text is fused together.
			$el
				.find( 'blockquote, dd, dl, dt, li, td' )
				.before( ' ' )
				.after( ' ' );
			$el
				.find( 'br, tr, p' )
				.before( '\n' )
				.after( '\n' );
		}

		/**
		 * Returns the HTML code for a jQuery element (only the first one if passed a set of elements).
		 * Unlike .html(), this includes HTML code for the outermost element; compare
		 * - `$('<div>').html() // ''`
		 * - `HtmlUtils.jqueryToHtml( $('<div>') ) // '<div></div>'`
		 *
		 * @param {jQuery} $el
		 * @return {string}
		 */
		jqueryToHtml( $el ) {
			// There are two possible implementations for this:
			// 1) load into a wrapper element and get its innerHTML;
			// 2) use outerHTML.
			// We go with 1) because it handles the case when a jQuery object contains something
			// that is not an element (this can happen with e.g. $x.children() which returns text
			// nodes as well).
			return $( '<div>' ).append( $el ).html();
		}

		/**
		 * Cleans up superfluous whitespace.
		 * Given that the results will be displayed in a HTML environment, this doesn't have any real
		 * effect. It is mostly there to make testing easier.
		 *
		 * @protected
		 * @param {string} html a HTML (or plaintext) string
		 * @return {string}
		 */
		mergeWhitespace( html ) {
			html = html.replace( /^\s+|\s+$/g, '' );
			html = html.replace( /\s*\n\s*/g, '\n' );
			html = html.replace( / {2,}/g, ' ' );
			return html;
		}

		/**
		 * Returns the text content of a html string.
		 * Tries to give an approximation of what would be visible if the HTML would be displayed.
		 *
		 * @param {string} html
		 * @return {string}
		 */
		htmlToText( html ) {
			if ( !cache.text[ html ] ) {
				const $html = this.wrapAndJquerify( html );
				this.filterInvisible( $html );
				this.appendWhitespaceToBlockElements( $html );
				cache.text[ html ] = this.mergeWhitespace( $html.text() );
			}
			return cache.text[ html ];
		}

		/**
		 * Returns the text content of a html string, with the `<a>`, `<i>`, `<b>` tags left intact.
		 * Tries to give an approximation of what would be visible if the HTML would be displayed.
		 *
		 * @param {string} html
		 * @return {string}
		 */
		htmlToTextWithTags( html ) {
			if ( !cache.textWithTags[ html ] ) {
				const $html = this.wrapAndJquerify( html );
				this.filterInvisible( $html );
				this.appendWhitespaceToBlockElements( $html );
				this.whitelistHtml( $html, 'a, span, i, b, sup, sub' );
				cache.textWithTags[ html ] = this.mergeWhitespace( $html.html() );
			}
			return cache.textWithTags[ html ];
		}

		/**
		 * Returns the text content of a html string, with the `<a>` tags left intact.
		 * Tries to give an approximation of what would be visible if the HTML would be displayed.
		 *
		 * @param {string} html
		 * @return {string}
		 */
		htmlToTextWithLinks( html ) {
			if ( !cache.textWithLinks[ html ] ) {
				const $html = this.wrapAndJquerify( html );
				this.filterInvisible( $html );
				this.appendWhitespaceToBlockElements( $html );
				this.whitelistHtml( $html, 'a, span' );
				cache.textWithLinks[ html ] = this.mergeWhitespace( $html.html() );
			}
			return cache.textWithLinks[ html ];
		}

		/**
		 * Generates HTML code for a link.
		 *
		 * @param {string} text Link text (plain text; will be sanitized)
		 * @param {Object} props Link attributes (should at a minimum include href; will be sanitized)
		 * @return {string}
		 */
		makeLinkText( text, props ) {
			for ( const key in props ) {
				if ( !Object.prototype.hasOwnProperty.call( props, key ) ) {
					continue;
				}
				props[ key ] = this.htmlToText( props[ key ] );
			}
			return this.jqueryToHtml( $( '<a>' ).prop( props ).text( text ) );
		}
	}

	module.exports = HtmlUtils;
}() );
