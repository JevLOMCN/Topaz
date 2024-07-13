/*!
 * VisualEditor client (browser) specific utilities that interact with a rendered DOM.
 *
 * @copyright See AUTHORS.txt
 */

/**
 * @method
 * @inheritdoc OO.ui.Element#scrollIntoView
 */
ve.scrollIntoView = OO.ui.Element.static.scrollIntoView.bind( OO.ui.Element.static );

/**
 * Select the contents of an element
 *
 * @param {HTMLElement} element
 */
ve.selectElement = function ( element ) {
	var win = OO.ui.Element.static.getWindow( element ),
		nativeRange = win.document.createRange(),
		nativeSelection = win.getSelection();
	nativeRange.setStart( element, 0 );
	nativeRange.setEnd( element, element.childNodes.length );
	nativeSelection.removeAllRanges();
	nativeSelection.addRange( nativeRange );
};

/**
 * Feature detect if the browser supports extending selections
 *
 * Should work everywhere except IE
 *
 * @private
 * @property {boolean}
 */
ve.supportsSelectionExtend = !!window.getSelection().extend;

/**
 * Translate rect by some fixed vector and return a new offset object
 *
 * @param {Object} rect Offset object containing all or any of top, left, bottom, right, width & height
 * @param {number} x Horizontal translation
 * @param {number} y Vertical translation
 * @return {Object} Translated rect
 */
ve.translateRect = function ( rect, x, y ) {
	var translatedRect = {};
	if ( rect.top !== undefined ) {
		translatedRect.top = rect.top + y;
	}
	if ( rect.bottom !== undefined ) {
		translatedRect.bottom = rect.bottom + y;
	}
	if ( rect.left !== undefined ) {
		translatedRect.left = rect.left + x;
	}
	if ( rect.right !== undefined ) {
		translatedRect.right = rect.right + x;
	}
	if ( rect.width !== undefined ) {
		translatedRect.width = rect.width;
	}
	if ( rect.height !== undefined ) {
		translatedRect.height = rect.height;
	}
	return translatedRect;
};

/**
 * Get the start and end rectangles (in a text flow sense) from a list of rectangles
 *
 * The start rectangle is the top-most, and the end rectangle is the bottom-most.
 *
 * @param {Object[]|null} rects Full list of rectangles
 * @return {Object.<string,Object>|null} Object containing two rectangles: start and end, or null if there are no rectangles
 */
ve.getStartAndEndRects = function ( rects ) {
	if ( !rects || !rects.length ) {
		return null;
	}
	var startRect, endRect;
	for ( var i = 0, l = rects.length; i < l; i++ ) {
		if ( !startRect || rects[ i ].top < startRect.top ) {
			// Use ve.extendObject as ve.copy copies non-plain objects by reference
			startRect = ve.extendObject( {}, rects[ i ] );
		} else if ( rects[ i ].top === startRect.top ) {
			// Merge rects with the same top coordinate
			startRect.left = Math.min( startRect.left, rects[ i ].left );
			startRect.right = Math.max( startRect.right, rects[ i ].right );
			startRect.width = startRect.right - startRect.left;
		}
		if ( !endRect || rects[ i ].bottom > endRect.bottom ) {
			// Use ve.extendObject as ve.copy copies non-plain objects by reference
			endRect = ve.extendObject( {}, rects[ i ] );
		} else if ( rects[ i ].bottom === endRect.bottom ) {
			// Merge rects with the same bottom coordinate
			endRect.left = Math.min( endRect.left, rects[ i ].left );
			endRect.right = Math.max( endRect.right, rects[ i ].right );
			endRect.width = startRect.right - startRect.left;
		}
	}
	return {
		start: startRect,
		end: endRect
	};
};

/**
 * Minimize a set of rectangles by discarding ones which are contained by others
 *
 * @param {Object[]} rects Full list of rectangles
 * @param {number} [allowedErrorOffset=3] Allowed error offset, the pixel error amount
 *  used in coordinate comparisons.
 * @return {Object[]} Minimized list of rectangles
 */
ve.minimizeRects = function ( rects, allowedErrorOffset ) {
	if ( allowedErrorOffset === undefined ) {
		allowedErrorOffset = 3;
	}

	// Check if rect1 contains rect2
	function contains( rect1, rect2 ) {
		return rect2.left >= rect1.left - allowedErrorOffset &&
			rect2.top >= rect1.top - allowedErrorOffset &&
			rect2.right <= rect1.right + allowedErrorOffset &&
			rect2.bottom <= rect1.bottom + allowedErrorOffset;
	}

	function merge( rect1, rect2 ) {
		var rect = {
			top: Math.min( rect1.top, rect2.top ),
			left: Math.min( rect1.left, rect2.left ),
			bottom: Math.max( rect1.bottom, rect2.bottom ),
			right: Math.max( rect1.right, rect2.right )
		};
		rect.width = rect.right - rect.left;
		rect.height = rect.bottom - rect.top;
		return rect;
	}

	function isApprox( a, b ) {
		return Math.abs( a - b ) < allowedErrorOffset;
	}

	var minimalRects = [];
	rects.forEach( function ( rect ) {
		var keep = true;
		for ( var i = 0, il = minimalRects.length; i < il; i++ ) {
			// This rect is contained by an existing rect, discard
			if ( contains( minimalRects[ i ], rect ) ) {
				keep = false;
				break;
			}
			// An existing rect is contained by this rect, discard the existing rect
			if ( contains( rect, minimalRects[ i ] ) ) {
				minimalRects.splice( i, 1 );
				i--;
				il--;
				break;
			}
			// Rect is horizontally adjacent to an existing rect, merge
			if (
				isApprox( rect.top, minimalRects[ i ].top ) && isApprox( rect.bottom, minimalRects[ i ].bottom ) && (
					isApprox( rect.left, minimalRects[ i ].right ) || isApprox( rect.right, minimalRects[ i ].left )
				)
			) {
				keep = false;
				minimalRects[ i ] = merge( minimalRects[ i ], rect );
				break;
			}
			// Rect is vertically adjacent to an existing rect, merge
			if (
				isApprox( rect.left, minimalRects[ i ].left ) && isApprox( rect.right, minimalRects[ i ].right ) && (
					isApprox( rect.top, minimalRects[ i ].bottom ) || isApprox( rect.bottom, minimalRects[ i ].top )
				)
			) {
				keep = false;
				minimalRects[ i ] = merge( minimalRects[ i ], rect );
				break;
			}
			// TODO: Consider case where a rect bridges two existing minimalRects, and so requires two
			// merges in one step. As rects are usually returned in order, this is unlikely to happen.
		}
		if ( keep ) {
			minimalRects.push( rect );
		}
	} );

	return minimalRects;
};

/**
 * Get the client platform string from the browser.
 *
 * FIXME T126036: This is a wrapper for calling getSystemPlatform() on the current
 * platform except that if the platform hasn't been constructed yet, it falls back
 * to using the base class implementation in {ve.init.Platform}. A proper solution
 * would be not to need this information before the platform is constructed.
 *
 * @see ve.init.Platform#getSystemPlatform
 * @return {string} Client platform string
 */
ve.getSystemPlatform = function () {
	return ( ve.init.platform && ve.init.platform.constructor || ve.init.Platform ).static.getSystemPlatform();
};

/**
 * Check whether a jQuery event represents a plain left click, without any modifiers
 *
 * @param {jQuery.Event} e
 * @return {boolean} Whether it was an unmodified left click
 */
ve.isUnmodifiedLeftClick = function ( e ) {
	return e && e.which && e.which === OO.ui.MouseButtons.LEFT && !( e.shiftKey || e.altKey || e.ctrlKey || e.metaKey );
};

/**
 * Are multiple formats for clipboardData items supported?
 *
 * If you want to use unknown formats, an additional check for whether we're
 * on MS Edge needs to be made, as that only supports standard plain text / HTML.
 *
 * @param {jQuery.Event} e A jQuery event object for a copy/paste event
 * @param {boolean} [customTypes] Check whether non-standard formats are supported
 * @return {boolean} Whether multiple clipboardData item formats are supported
 */
ve.isClipboardDataFormatsSupported = function ( e, customTypes ) {
	var cacheKey = customTypes ? 'cachedCustom' : 'cached';

	if ( ve.isClipboardDataFormatsSupported[ cacheKey ] === undefined ) {
		var profile = $.client.profile();
		var clipboardData = e.originalEvent.clipboardData || e.originalEvent.dataTransfer;
		ve.isClipboardDataFormatsSupported[ cacheKey ] = !!(
			clipboardData &&
			( !customTypes || profile.name !== 'edge' ) && (
				// Support: Chrome
				clipboardData.items ||
				// Support: Firefox >= 48
				// (but not Firefox Android, which has name='android' and doesn't support this feature)
				( profile.name === 'firefox' && profile.versionNumber >= 48 )
			)
		);
	}

	return ve.isClipboardDataFormatsSupported[ cacheKey ];
};

/**
 * Workaround for catastrophic Firefox bug (T209646)
 *
 * anchorNode and focusNode return unusable 'Restricted' object
 * when focus is in a number input:
 * https://bugzilla.mozilla.org/show_bug.cgi?id=1495482
 *
 * @param {Selection} selection Native selection
 */
ve.fixSelectionNodes = function ( selection ) {
	var profile = $.client.profile();

	if ( profile.layout !== 'gecko' ) {
		return;
	}

	function fixNodeProperty( prop ) {
		Object.defineProperty( selection, prop, {
			get: function () {
				var node = Object.getOwnPropertyDescriptor( Selection.prototype, prop ).get.call( this );
				try {
					// Try to read a property out of node if it not null
					// Throws an exception in FF
					// eslint-disable-next-line no-unused-expressions
					node && node.prop;
				} catch ( e ) {
					// When an input is focused, the selection becomes the input itself,
					// so the anchor/focusNode is the input's parent.
					// Fall back to null if that doesn't exist.
					ve.log( 'Worked around Firefox bug with getSelection().anchorNode/focusNode. See https://phabricator.wikimedia.org/T209646.' );
					return ( document.activeElement && document.activeElement.parentNode ) || null;
				}
				return node;
			}
		} );
	}

	if ( !Object.getOwnPropertyDescriptor( selection, 'anchorNode' ) ) {
		fixNodeProperty( 'anchorNode' );
		fixNodeProperty( 'focusNode' );
	}
};

/**
 * Safely decode HTML entities
 *
 * @param {string} html Text with HTML entities
 * @return {string} Text with HTML entities decoded
 */
ve.safeDecodeEntities = function ( html ) {
	// Decode HTML entities, safely (no elements permitted inside textarea)
	var textarea = document.createElement( 'textarea' );
	textarea.innerHTML = html;
	return textarea.textContent;
};
