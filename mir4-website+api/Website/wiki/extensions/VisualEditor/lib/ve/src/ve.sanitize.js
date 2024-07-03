/*!
 * VisualEditor HTML sanitization utilities.
 *
 * @copyright See AUTHORS.txt
 */

/* global DOMPurify */

/**
 * Parse some user HTML into a sanitized node list, making it safe to load on the page
 *
 * @param {string} html
 * @param {boolean} [returnDocument] For internal use only (if true, return whole document)
 * @return {NodeList|HTMLDocument} Sanitized node list (or HTML document, for internal use only)
 */
ve.sanitizeHtml = function ( html, returnDocument ) {
	// TODO: Move MW-specific rules to ve-mw
	var addTags = [ 'figure-inline' ],
		addAttrs = [
			'srcset',
			// RDFa
			'about', 'rel', 'resource', 'property', 'content', 'datatype', 'typeof'
		];
	var options = {
		ADD_TAGS: addTags,
		ADD_ATTR: addAttrs,
		ADD_URI_SAFE_ATTR: addAttrs,
		FORBID_TAGS: [ 'style' ]
	};
	if ( !returnDocument ) {
		options.FORCE_BODY = true;
		options.RETURN_DOM_FRAGMENT = true;
		return DOMPurify.sanitize( html, options ).childNodes;
	}
	options.RETURN_DOM = true;
	return DOMPurify.sanitize( html, options ).ownerDocument;
};

/**
 * Parse some user HTML into a sanitized HTML document, making it safe to load on the page
 *
 * @param {string} html
 * @return {HTMLDocument}
 */
ve.sanitizeHtmlToDocument = function ( html ) {
	return ve.sanitizeHtml( html, true );
};

/**
 * Set an element attribute to a specific value if it is safe
 *
 * @param {HTMLElement} element Element
 * @param {string} attr Attribute
 * @param {string} val Value
 * @param {string} [fallbackVal] Optional fallback value if val is unsafe (will also be safety-checked)
 */
ve.setAttributeSafe = function ( element, attr, val, fallbackVal ) {
	if ( DOMPurify.isValidAttribute( element.tagName, attr, val ) ) {
		element.setAttribute( attr, val );
	} else if ( fallbackVal !== undefined && DOMPurify.isValidAttribute( element.tagName, attr, fallbackVal ) ) {
		element.setAttribute( attr, fallbackVal );
	}
};
