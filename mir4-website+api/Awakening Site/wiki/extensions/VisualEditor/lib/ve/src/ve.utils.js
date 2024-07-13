/*!
 * VisualEditor utilities.
 *
 * @copyright See AUTHORS.txt
 */

/**
 * @class ve
 */

/**
 * Checks if an object is an instance of one or more classes.
 *
 * @param {Object} subject Object to check
 * @param {Function[]} classes Classes to compare with
 * @return {boolean} Object inherits from one or more of the classes
 */
ve.isInstanceOfAny = function ( subject, classes ) {
	var i = classes.length;

	while ( classes[ --i ] ) {
		if ( subject instanceof classes[ i ] ) {
			return true;
		}
	}
	return false;
};

/**
 * Get a deeply nested property of an object using variadic arguments, protecting against
 * undefined property errors.
 *
 * `quux = OO.getProp( obj, 'foo', 'bar', 'baz' );` is equivalent to `quux = obj.foo.bar.baz;`
 * except that the former protects against JS errors if one of the intermediate properties
 * is undefined. Instead of throwing an error, this function will return undefined in
 * that case.
 *
 * See https://doc.wikimedia.org/oojs/master/OO.html
 *
 * @method
 * @param {Object} obj
 * @param {...Mixed} [keys]
 * @return {Object|undefined} obj[arguments[1]][arguments[2]].... or undefined
 */
ve.getProp = OO.getProp;

/**
 * Set a deeply nested property of an object using variadic arguments, protecting against
 * undefined property errors.
 *
 * `OO.setProp( obj, 'foo', 'bar', 'baz' );` is equivalent to `obj.foo.bar = baz;` except that
 * the former protects against JS errors if one of the intermediate properties is
 * undefined. Instead of throwing an error, undefined intermediate properties will be
 * initialized to an empty object. If an intermediate property is not an object, or if obj itself
 * is not an object, this function will silently abort.
 *
 * See https://doc.wikimedia.org/oojs/master/OO.html
 *
 * @method
 * @param {Object} obj
 * @param {...Mixed} [keys]
 * @param {Mixed} [value]
 */
ve.setProp = OO.setProp;

/**
 * Delete a deeply nested property of an object using variadic arguments, protecting against
 * undefined property errors, and deleting resulting empty objects.
 *
 * See https://doc.wikimedia.org/oojs/master/OO.html
 *
 * @method
 * @param {Object} obj
 * @param {...Mixed} [keys]
 */
ve.deleteProp = OO.deleteProp;

/**
 * Create a new object that is an instance of the same
 * constructor as the input, inherits from the same object
 * and contains the same own properties.
 *
 * This makes a shallow non-recursive copy of own properties.
 * To create a recursive copy of plain objects, use #copy.
 *
 *     var foo = new Person( mom, dad );
 *     foo.setAge( 21 );
 *     var foo2 = OO.cloneObject( foo );
 *     foo.setAge( 22 );
 *
 *     // Then
 *     foo2 !== foo; // true
 *     foo2 instanceof Person; // true
 *     foo2.getAge(); // 21
 *     foo.getAge(); // 22
 *
 * See https://doc.wikimedia.org/oojs/master/OO.html
 *
 * @method
 * @param {Object} origin
 * @return {Object} Clone of origin
 */
ve.cloneObject = OO.cloneObject;

/**
 * Get an array of all property values in an object.
 *
 * See https://doc.wikimedia.org/oojs/master/OO.html
 *
 * @method
 * @param {Object} obj Object to get values from
 * @return {Array} List of object values
 */
ve.getObjectValues = OO.getObjectValues;

/**
 * Use binary search to locate an element in a sorted array.
 *
 * searchFunc is given an element from the array. `searchFunc(elem)` must return a number
 * above 0 if the element we're searching for is to the right of (has a higher index than) elem,
 * below 0 if it is to the left of elem, or zero if it's equal to elem.
 *
 * To search for a specific value with a comparator function (a `function cmp(a,b)` that returns
 * above 0 if `a > b`, below 0 if `a < b`, and 0 if `a == b`), you can use
 * `searchFunc = cmp.bind( null, value )`.
 *
 * See https://doc.wikimedia.org/oojs/master/OO.html
 *
 * @method
 * @param {Array} arr Array to search in
 * @param {Function} searchFunc Search function
 * @param {boolean} [forInsertion] If not found, return index where val could be inserted
 * @return {number|null} Index where val was found, or null if not found
 */
ve.binarySearch = OO.binarySearch;

/**
 * Recursively compare properties between two objects.
 *
 * A false result may be caused by property inequality or by properties in one object missing from
 * the other. An asymmetrical test may also be performed, which checks only that properties in the
 * first object are present in the second object, but not the inverse.
 *
 * If either a or b is null or undefined it will be treated as an empty object.
 *
 * See https://doc.wikimedia.org/oojs/master/OO.html
 *
 * @method
 * @param {Object|undefined|null} a First object to compare
 * @param {Object|undefined|null} b Second object to compare
 * @param {boolean} [asymmetrical] Whether to check only that a's values are equal to b's
 *  (i.e. a is a subset of b)
 * @return {boolean} If the objects contain the same values as each other
 */
ve.compare = OO.compare;

/**
 * Create a plain deep copy of any kind of object.
 *
 * Copies are deep, and will either be an object or an array depending on `source`.
 *
 * See https://doc.wikimedia.org/oojs/master/OO.html
 *
 * @method
 * @param {Object} source Object to copy
 * @param {Function} [leafCallback] Applied to leaf values after they are cloned but before they are
 *  added to the clone
 * @param {Function} [nodeCallback] Applied to all values before they are cloned. If the
 *  nodeCallback returns a value other than undefined, the returned value is used instead of
 *  attempting to clone.
 * @return {Object} Copy of source object
 */
ve.copy = OO.copy;

/**
 * @method
 * @inheritdoc OO.ui#debounce
 */
ve.debounce = OO.ui.debounce;

/**
 * @method
 * @inheritdoc OO.ui#throttle
 */
ve.throttle = OO.ui.throttle;

/**
 * Create a jQuery.Deferred-compatible object
 *
 * See http://api.jquery.com/jQuery.Deferred/
 *
 * @method
 * @return {jQuery.Deferred}
 */
ve.createDeferred = $.Deferred;

/**
 * Create a promise which resolves when the list of promises has resolved
 *
 * @param {jQuery.Promise[]} promises List of promises
 * @return {jQuery.Promise} Promise which resolves when the list of promises has resolved
 */
ve.promiseAll = function ( promises ) {
	return $.when.apply( $, promises );
};

/**
 * Copy an array of DOM elements, optionally into a different document.
 *
 * @param {HTMLElement[]} domElements DOM elements to copy
 * @param {HTMLDocument} [doc] Document to create the copies in; if unset, simply clone each element
 * @return {HTMLElement[]} Copy of domElements with copies of each element
 */
ve.copyDomElements = function ( domElements, doc ) {
	return domElements.map( function ( domElement ) {
		return doc ? doc.importNode( domElement, true ) : domElement.cloneNode( true );
	} );
};

/**
 * Check if two arrays of DOM elements are equal (according to .isEqualNode())
 *
 * @param {HTMLElement[]} domElements1 First array of DOM elements
 * @param {HTMLElement[]} domElements2 Second array of DOM elements
 * @return {boolean} All elements are pairwise equal
 */
ve.isEqualDomElements = function ( domElements1, domElements2 ) {
	var i = 0,
		len = domElements1.length;
	if ( len !== domElements2.length ) {
		return false;
	}
	for ( ; i < len; i++ ) {
		if ( !domElements1[ i ].isEqualNode( domElements2[ i ] ) ) {
			return false;
		}
	}
	return true;
};

/**
 * Compare two class lists, either whitespace separated strings or arrays
 *
 * Class lists are equivalent if they contain the same members,
 * excluding duplicates and ignoring order.
 *
 * @param {string[]|string} classList1 First class list
 * @param {string[]|string} classList2 Second class list
 * @return {boolean} Class lists are equivalent
 */
ve.compareClassLists = function ( classList1, classList2 ) {
	var removeEmpty = function ( c ) {
		return c !== '';
	};

	classList1 = Array.isArray( classList1 ) ? classList1 : classList1.trim().split( /\s+/ );
	classList2 = Array.isArray( classList2 ) ? classList2 : classList2.trim().split( /\s+/ );

	classList1 = classList1.filter( removeEmpty );
	classList2 = classList2.filter( removeEmpty );

	return ve.compare( OO.unique( classList1 ).sort(), OO.unique( classList2 ).sort() );
};

/**
 * Check to see if an object is a plain object (created using "{}" or "new Object").
 *
 * See http://api.jquery.com/jQuery.isPlainObject/
 *
 * @method
 * @param {Object} obj The object that will be checked to see if it's a plain object
 * @return {boolean}
 */
ve.isPlainObject = $.isPlainObject;

/**
 * Check to see if an object is empty (contains no properties).
 *
 * See http://api.jquery.com/jQuery.isEmptyObject/
 *
 * @method
 * @param {Object} obj The object that will be checked to see if it's empty
 * @return {boolean}
 */
ve.isEmptyObject = $.isEmptyObject;

/**
 * Merge properties of one or more objects into another.
 * Preserves original object's inheritance (e.g. Array, Object, whatever).
 * In case of array or array-like objects only the indexed properties
 * are copied over.
 * Beware: If called with only one argument, it will consider
 * 'target' as 'source' and 'this' as 'target'. Which means
 * ve.extendObject( { a: 1 } ); sets ve.a = 1;
 *
 * See http://api.jquery.com/jQuery.extend/
 *
 * @method
 * @param {boolean} [recursive=false]
 * @param {Mixed} [target] Object that will receive the new properties
 * @param {...Mixed} [sources] Variadic list of objects containing properties
 * to be merged into the target.
 * @return {Mixed} Modified version of first or second argument
 */
ve.extendObject = $.extend;

/**
 * Splice one array into another.
 *
 * This is the equivalent of arr.splice( offset, remove, d1, d2, d3, … ) except that arguments are
 * specified as an array rather than separate parameters.
 *
 * This method has been proven to be faster than using slice and concat to create a new array, but
 * performance tests should be conducted on each use of this method to verify this is true for the
 * particular use. Also, browsers change fast, never assume anything, always test everything.
 *
 * Includes a replacement for broken implementations of Array.prototype.splice().
 *
 * @param {Array|ve.dm.BranchNode} arr Target object (must have `splice` method, object will be modified)
 * @param {number} offset Offset in arr to splice at. This MUST NOT be negative, unlike the
 *  'index' parameter in Array#splice.
 * @param {number} remove Number of elements to remove at the offset. May be zero
 * @param {Array} data Array of items to insert at the offset. Must be non-empty if remove=0
 * @return {Array} Array of items removed
 */
ve.batchSplice = function ( arr, offset, remove, data ) {
	// We need to splice insertion in in batches, because of parameter list length limits which vary
	// cross-browser - 1024 seems to be a safe batch size on all browsers
	var index = 0,
		batchSize = 1024,
		toRemove = remove,
		removed = [];

	var splice;
	if ( !Array.isArray( arr ) ) {
		splice = arr.splice;
	} else {
		splice = Array.prototype.splice;
	}

	if ( data.length === 0 ) {
		// Special case: data is empty, so we're just doing a removal
		// The code below won't handle that properly, so we do it here
		return splice.call( arr, offset, remove );
	}

	while ( index < data.length ) {
		// Call arr.splice( offset, remove, i0, i1, i2, …, i1023 );
		// Only set remove on the first call, and set it to zero on subsequent calls
		var spliced = splice.apply(
			arr, [ index + offset, toRemove ].concat( data.slice( index, index + batchSize ) )
		);
		if ( toRemove > 0 ) {
			removed = spliced;
		}
		index += batchSize;
		toRemove = 0;
	}
	return removed;
};

/**
 * Splice one array into another, replicating any holes
 *
 * Similar to arr.splice.apply( arr, [ offset, remove ].concat( data ) ), except holes in
 * data remain holes in arr. Optimized for length changes that are negative, zero, or
 * fairly small positive.
 *
 * @param {Array} arr Array to modify
 * @param {number} offset Offset in arr to splice at. This MUST NOT be negative, unlike the
 *  'index' parameter in Array#splice.
 * @param {number} remove Number of elements to remove at the offset. May be zero
 * @param {Array} data Array of items to insert at the offset
 * @return {Array} Array of items removed, with holes preserved
 */
ve.sparseSplice = function ( arr, offset, remove, data ) {
	var removed = [],
		endOffset = offset + remove,
		diff = data.length - remove;
	if ( data === arr ) {
		// Pathological case: arr and data are reference-identical
		data = data.slice();
	}
	// Remove content without adjusting length
	arr.slice( offset, endOffset ).forEach( function ( item, j ) {
		removed[ j ] = item;
		delete arr[ offset + j ];
	} );
	// Adjust length
	if ( diff > 0 ) {
		// Grow with undefined values, then delete. (This is optimised for diff
		// comparatively small: otherwise, it would sometimes be quicker to relocate
		// each element of arr that lies above offset).
		ve.batchSplice( arr, endOffset, 0, new Array( diff ) );
		for ( var i = endOffset + diff - 1; i >= endOffset; i-- ) {
			delete arr[ i ];
		}
	} else if ( diff < 0 ) {
		// Shrink
		arr.splice( offset, -diff );
	}
	// Insert new content
	data.forEach( function ( item, j ) {
		arr[ offset + j ] = item;
	} );
	// Set removed.length in case there are holes at the end
	removed.length = remove;
	return removed;
};

/**
 * Insert one array into another.
 *
 * Shortcut for `ve.batchSplice( arr, offset, 0, src )`.
 *
 * @see #batchSplice
 * @param {Array|ve.dm.BranchNode} arr Target object (must have `splice` method)
 * @param {number} offset Offset in arr where items will be inserted
 * @param {Array} src Items to insert at offset
 */
ve.insertIntoArray = function ( arr, offset, src ) {
	ve.batchSplice( arr, offset, 0, src );
};

/**
 * Push one array into another.
 *
 * This is the equivalent of arr.push( d1, d2, d3, … ) except that arguments are
 * specified as an array rather than separate parameters.
 *
 * @param {Array|ve.dm.BranchNode} arr Object supporting .push() to insert at the end of the array. Will be modified
 * @param {Array} data Array of items to insert.
 * @return {number} length of the new array
 */
ve.batchPush = function ( arr, data ) {
	// We need to push insertion in batches, because of parameter list length limits which vary
	// cross-browser - 1024 seems to be a safe batch size on all browsers
	var index = 0,
		batchSize = 1024;
	if ( batchSize >= data.length ) {
		// Avoid slicing for small lists
		return arr.push.apply( arr, data );
	}
	var length;
	while ( index < data.length ) {
		// Call arr.push( i0, i1, i2, …, i1023 );
		length = arr.push.apply(
			arr, data.slice( index, index + batchSize )
		);
		index += batchSize;
	}
	return length;
};

/**
 * Log data to the console.
 *
 * This implementation does nothing, to add a real implementation ve.debug needs to be loaded.
 *
 * @param {...Mixed} [args] Data to log
 */
ve.log = ve.log || function () {
	// Don't do anything, this is just a stub
};

/**
 * Log error to the console.
 *
 * This implementation does nothing, to add a real implementation ve.debug needs to be loaded.
 *
 * @param {...Mixed} [args] Data to log
 */
ve.error = ve.error || function () {
	// Don't do anything, this is just a stub
};

/**
 * Log an object to the console.
 *
 * This implementation does nothing, to add a real implementation ve.debug needs to be loaded.
 *
 * @param {Object} obj
 */
ve.dir = ve.dir || function () {
	// Don't do anything, this is just a stub
};

/**
 * Deep freeze an object, making it immutable
 *
 * This implementation does nothing, to add a real implementation ve.freeze needs to be loaded.
 *
 * @param {Object} obj
 * @param {boolean} onlyProperties
 * @return {Object}
 */
ve.deepFreeze = ve.deepFreeze || function ( obj ) {
	// Don't do anything, this is just a stub
	return obj;
};

/**
 * Get a localized message.
 *
 * @param {string} key Message key
 * @param {...Mixed} [params] Message parameters
 * @return {string} Localized message
 */
ve.msg = function () {
	// Avoid using bind because ve.init.platform doesn't exist yet.
	// TODO: Fix dependency issues between ve.js and ve.init.platform
	return ve.init.platform.getMessage.apply( ve.init.platform, arguments );
};

/**
 * Get an HTML localized message with HTML or DOM arguments.
 *
 * @param {string} key Message key
 * @param {...Mixed} [params] Message parameters
 * @return {Node[]} Localized message
 */
ve.htmlMsg = function () {
	// Avoid using bind because ve.init.platform doesn't exist yet.
	// TODO: Fix dependency issues between ve.js and ve.init.platform
	return ve.init.platform.getHtmlMessage.apply( ve.init.platform, arguments );
};

/**
 * Get platform config value(s)
 *
 * @param {string|string[]} key Config key, or list of keys
 * @return {Mixed|Object} Config value, or keyed object of config values if list of keys provided
 */
ve.config = function () {
	return ve.init.platform.getConfig.apply( ve.init.platform, arguments );
};

/**
 * Get or set a user config value.
 *
 * @param {string|string[]|Object} key Config key, list of keys or object mapping keys to values
 * @param {Mixed} [value] Value to set, if setting and key is a string
 * @return {Mixed|Object|boolean} Config value, keyed object of config values if list of keys provided,
 *  or success boolean if setting.
 */
ve.userConfig = function ( key ) {
	if ( arguments.length <= 1 && ( typeof key === 'string' || Array.isArray( key ) ) ) {
		// get( string key )
		// get( Array keys )
		return ve.init.platform.getUserConfig.apply( ve.init.platform, arguments );
	} else {
		// set( Object values )
		// set( key, value )
		return ve.init.platform.setUserConfig.apply( ve.init.platform, arguments );
	}
};

/**
 * Convert a grapheme cluster offset to a byte offset.
 *
 * @param {string} text Text in which to calculate offset
 * @param {number} clusterOffset Grapheme cluster offset
 * @return {number} Byte offset
 */
ve.getByteOffset = function ( text, clusterOffset ) {
	return unicodeJS.graphemebreak.splitClusters( text )
		.slice( 0, clusterOffset )
		.join( '' )
		.length;
};

/**
 * Convert a byte offset to a grapheme cluster offset.
 *
 * @param {string} text Text in which to calculate offset
 * @param {number} byteOffset Byte offset
 * @return {number} Grapheme cluster offset
 */
ve.getClusterOffset = function ( text, byteOffset ) {
	return unicodeJS.graphemebreak.splitClusters( text.slice( 0, byteOffset ) ).length;
};

/**
 * Get a text substring, taking care not to split grapheme clusters.
 *
 * @param {string} text Text to take the substring from
 * @param {number} start Start offset
 * @param {number} end End offset
 * @param {boolean} [outer=false] Include graphemes if the offset splits them
 * @return {string} Substring of text
 */
ve.graphemeSafeSubstring = function ( text, start, end, outer ) {
	// TODO: improve performance by incrementally inspecting characters around the offsets
	var unicodeStart = ve.getByteOffset( text, ve.getClusterOffset( text, start ) ),
		unicodeEnd = ve.getByteOffset( text, ve.getClusterOffset( text, end ) );

	// If the selection collapses and we want an inner, then just return empty
	// otherwise we'll end up crossing over start and end
	if ( unicodeStart === unicodeEnd && !outer ) {
		return '';
	}

	// The above calculations always move to the right of a multibyte grapheme.
	// Depending on the outer flag, we may want to move to the left:
	if ( unicodeStart > start && outer ) {
		unicodeStart = ve.getByteOffset( text, ve.getClusterOffset( text, start ) - 1 );
	}
	if ( unicodeEnd > end && !outer ) {
		unicodeEnd = ve.getByteOffset( text, ve.getClusterOffset( text, end ) - 1 );
	}
	return text.slice( unicodeStart, unicodeEnd );
};

/**
 * Escape non-word characters so they can be safely used as HTML attribute values.
 *
 * @param {string} value Attribute value to escape
 * @return {string} Escaped attribute value
 */
ve.escapeHtml = ( function () {
	function escape( value ) {
		switch ( value ) {
			case '\'':
				return '&#039;';
			case '"':
				return '&quot;';
			case '<':
				return '&lt;';
			case '>':
				return '&gt;';
			case '&':
				return '&amp;';
		}
	}

	return function ( value ) {
		return value.replace( /['"<>&]/g, escape );
	};
}() );

/**
 * Get the attributes of a DOM element as an object with key/value pairs.
 *
 * @param {HTMLElement} element
 * @return {Object}
 */
ve.getDomAttributes = function ( element ) {
	var result = {};
	for ( var i = 0; i < element.attributes.length; i++ ) {
		result[ element.attributes[ i ].name ] = element.attributes[ i ].value;
	}
	return result;
};

/**
 * Set the attributes of a DOM element as an object with key/value pairs.
 *
 * Use the `null` or `undefined` value to ensure an attribute's absence.
 *
 * @param {HTMLElement} element DOM element to apply attributes to
 * @param {Object} attributes Attributes to apply
 * @param {string[]} [allowedAttributes] List of attributes to exclusively allow (all lowercase names)
 */
ve.setDomAttributes = function ( element, attributes, allowedAttributes ) {
	// Duck-typing for attribute setting
	if ( !element.setAttribute || !element.removeAttribute ) {
		return;
	}
	for ( var key in attributes ) {
		if ( allowedAttributes && allowedAttributes.indexOf( key.toLowerCase() ) === -1 ) {
			continue;
		}
		if ( attributes[ key ] === undefined || attributes[ key ] === null ) {
			element.removeAttribute( key );
		} else {
			element.setAttribute( key, attributes[ key ] );
		}
	}
};

/**
 * Get an HTML representation of a DOM element node, text node or comment node
 *
 * @param {Node} node The DOM node
 * @return {string} HTML representation of the node
 */
ve.getNodeHtml = function ( node ) {
	if ( node.nodeType === Node.ELEMENT_NODE ) {
		return node.outerHTML;
	} else {
		var div = document.createElement( 'div' );
		div.appendChild( node.cloneNode( true ) );
		return div.innerHTML;
	}
};

/**
 * Build a summary of an HTML element.
 *
 * Summaries include node name, text, attributes and recursive summaries of children.
 * Used for serializing or comparing HTML elements.
 *
 * @private
 * @param {HTMLElement} element Element to summarize
 * @param {boolean} [includeHtml=false] Include an HTML summary for element nodes
 * @param {Function} [getAttributeSummary] Callback to modify the summary of an attribute
 * @param {string} [getAttributeSummary.name] Name of the attribute to modify.
 * @param {string} [getAttributeSummary.value] Value to return for the given attribute.
 * @return {Object} Summary of element.
 */
ve.getDomElementSummary = function ( element, includeHtml, getAttributeSummary ) {
	var summary = {
		type: element.nodeName.toLowerCase(),
		text: element.textContent,
		attributes: {},
		children: []
	};

	if ( includeHtml && element.nodeType === Node.ELEMENT_NODE ) {
		summary.html = element.outerHTML;
	}

	var i;
	// Gather attributes
	if ( element.attributes ) {
		for ( i = 0; i < element.attributes.length; i++ ) {
			var name = element.attributes[ i ].name;
			if ( name === 'about' ) {
				// The about attribute is non-deterministic as we generate a new random
				// one whenever a node is cloned (see ve.dm.Node.static.cloneElement).
				// Exclude it from node comparisons.
				continue;
			}
			var value = element.attributes[ i ].value;
			summary.attributes[ name ] = getAttributeSummary ? getAttributeSummary( name, value ) : value;
		}
	}
	// Summarize children
	if ( element.childNodes ) {
		for ( i = 0; i < element.childNodes.length; i++ ) {
			summary.children.push( ve.getDomElementSummary( element.childNodes[ i ], includeHtml ) );
		}
	}
	return summary;
};

/**
 * Callback for #copy to convert nodes to a comparable summary.
 *
 * @private
 * @param {Object} value Value in the object/array
 * @return {Object} DOM element summary if value is a node, otherwise just the value
 */
ve.convertDomElements = function ( value ) {
	// Use duck typing rather than instanceof Node; the latter doesn't always work correctly
	if ( value && value.nodeType ) {
		return ve.getDomElementSummary( value );
	}
	return value;
};

ve.visibleWhitespaceCharacters = {
	'\n': '\u21b5', // ↵
	'\t': '\u279e' // ➞
};

/**
 * Check whether a given node is contentEditable
 *
 * Handles 'inherit', via checking isContentEditable. Knows to check for text
 * nodes, and will return whether the text node's parent is contentEditable.
 *
 * @param  {HTMLElement|Text} node Node to check contenteditable status of
 * @return {boolean} Node is contenteditable
 */
ve.isContentEditable = function ( node ) {
	return ( node.nodeType === Node.TEXT_NODE ? node.parentNode : node ).isContentEditable;
};

/**
 * Filter out metadata elements
 *
 * @param {Node[]} contents DOM nodes
 * @return {Node[]} Filtered DOM nodes
 */
ve.filterMetaElements = function ( contents ) {
	// Filter out link and style tags for T52043
	// Previously filtered out meta tags, but restore these as they
	// can be made visible with CSS.
	// As of jQuery 3 we can't use $.not( 'tagName' ) as that doesn't
	// match text nodes. Also we can't $.remove these elements as they
	// aren't attached to anything.
	contents = contents.filter( function ( node ) {
		return node.tagName !== 'LINK' && node.tagName !== 'STYLE';
	} );
	// Also remove link and style tags nested inside other tags
	$( contents ).find( 'link, style' ).remove();
	return contents;
};

/**
 * Modify a set of DOM elements to resolve attributes in the context of another document.
 *
 * This performs node.setAttribute( 'attr', nodeInDoc[attr] ); for every node.
 *
 * Doesn't use jQuery to avoid document switching performance bug
 *
 * @param {HTMLElement|HTMLElement[]|NodeList|jQuery} elementsOrJQuery Set of DOM elements to modify. Passing a jQuery selector is deprecated.
 * @param {HTMLDocument} doc Document to resolve against (different from $elements' .ownerDocument)
 * @param {string[]} attrs Attributes to resolve
 */
ve.resolveAttributes = function ( elementsOrJQuery, doc, attrs ) {
	// Convert jQuery selections to plain arrays
	var elements = elementsOrJQuery.toArray ? elementsOrJQuery.toArray() : elementsOrJQuery;

	// Duck typing for array or NodeList :(
	if ( elements.length === undefined ) {
		elements = [ elements ];
	}

	var attr;

	/**
	 * Resolves the value of attr to the computed property value.
	 *
	 * @private
	 * @param {HTMLElement} el Element
	 */
	function resolveAttribute( el ) {
		var nodeInDoc = doc.createElement( el.nodeName );
		nodeInDoc.setAttribute( attr, el.getAttribute( attr ) );
		if ( nodeInDoc[ attr ] ) {
			el.setAttribute( attr, nodeInDoc[ attr ] );
		}
	}

	for ( var i = 0, iLen = elements.length; i < iLen; i++ ) {
		var element = elements[ i ];
		for ( var j = 0, jLen = attrs.length; j < jLen; j++ ) {
			attr = attrs[ j ];
			if ( element.hasAttribute( attr ) ) {
				resolveAttribute( element );
			}
			Array.prototype.forEach.call( element.querySelectorAll( '[' + attr + ']' ), resolveAttribute );
		}
	}
};

/**
 * Make all links within a DOM element open in a new window
 *
 * @param {HTMLElement} container DOM element to search for links
 */
ve.targetLinksToNewWindow = function ( container ) {
	// Make all links open in a new window
	Array.prototype.forEach.call( container.querySelectorAll( 'a[href]' ), function ( el ) {
		ve.appendToRel( el, 'noopener' );
		el.setAttribute( 'target', '_blank' );
	} );
};

/**
 * Add a value to an element's rel attribute if it's not already present
 *
 * Rel is like class: it's actually a set, represented as a string. We don't
 * want to add the same value to the attribute if this function is called
 * repeatedly. This is mostly a placeholder for the relList property someday
 * becoming widely supported.
 *
 * @param  {HTMLElement} element DOM element whose attribute should be checked
 * @param  {string} value New rel value to be added
 */
ve.appendToRel = function ( element, value ) {
	var rel = element.getAttribute( 'rel' );
	if ( !rel ) {
		// Avoid all that string-creation if it's not needed
		element.setAttribute( 'rel', value );
	} else if ( ( ' ' + rel + ' ' ).indexOf( ' ' + value + ' ' ) === -1 ) {
		element.setAttribute( 'rel', rel + ' ' + value );
	}
};

/**
 * Check if a string is a valid URI component.
 *
 * A URI component is considered invalid if decodeURIComponent() throws an exception.
 *
 * @param {string} s String to test
 * @return {boolean} decodeURIComponent( s ) did not throw an exception
 * @see #safeDecodeURIComponent
 */
ve.isUriComponentValid = function ( s ) {
	try {
		decodeURIComponent( s );
	} catch ( e ) {
		return false;
	}
	return true;
};

/**
 * Safe version of decodeURIComponent() that doesn't throw exceptions.
 *
 * If the native decodeURIComponent() call threw an exception, the original string
 * will be returned.
 *
 * @param {string} s String to decode
 * @return {string} Decoded string, or same string if decoding failed
 * @see #isUriComponentValid
 */
ve.safeDecodeURIComponent = function ( s ) {
	try {
		s = decodeURIComponent( s );
	} catch ( e ) {}
	return s;
};

/**
 * Find the length of the common start sequence of one or more sequences
 *
 * Items are tested for sameness using === .
 *
 * @param {Array} sequences Array of sequences (arrays, strings etc)
 * @return {number} Common start sequence length (0 if sequences is empty)
 */
ve.getCommonStartSequenceLength = function ( sequences ) {
	if ( sequences.length === 0 ) {
		return 0;
	}
	var commonLength = 0;
	commonLengthLoop:
	while ( true ) {
		if ( commonLength >= sequences[ 0 ].length ) {
			break;
		}
		var val = sequences[ 0 ][ commonLength ];
		for ( var i = 1, len = sequences.length; i < len; i++ ) {
			if (
				sequences[ i ].length <= commonLength ||
				sequences[ i ][ commonLength ] !== val
			) {
				break commonLengthLoop;
			}
		}
		commonLength++;
	}
	return commonLength;
};

/**
 * Find the nearest common ancestor of DOM nodes
 *
 * @param {...Node|null} DOM nodes
 * @return {Node|null} Nearest common ancestor; or null if there is none / an argument is null
 */
ve.getCommonAncestor = function () {
	var args = Array.prototype.slice.call( arguments );
	var nodeCount = args.length;
	if ( nodeCount === 0 ) {
		return null;
	}
	var minHeight = null;
	var chains = [];
	var i, node;
	// Build every chain
	for ( i = 0; i < nodeCount; i++ ) {
		var chain = [];
		node = args[ i ];
		while ( node !== null ) {
			chain.unshift( node );
			node = node.parentNode;
		}
		if ( chain.length === 0 ) {
			// args[ i ] was null (so no common ancestor)
			return null;
		}
		if ( i > 0 && chain[ 0 ] !== chains[ chains.length - 1 ][ 0 ] ) {
			// No common ancestor (different documents or unattached branches)
			return null;
		}
		if ( minHeight === null || minHeight > chain.length ) {
			minHeight = chain.length;
		}
		chains.push( chain );
	}

	// Step through chains in parallel, until they differ.
	// All chains are guaranteed to start with the common document element (or the common root
	// of an unattached branch)
	for ( i = 1; i < minHeight; i++ ) {
		node = chains[ 0 ][ i ];
		for ( var j = 1; j < nodeCount; j++ ) {
			if ( node !== chains[ j ][ i ] ) {
				return chains[ 0 ][ i - 1 ];
			}
		}
	}
	return chains[ 0 ][ minHeight - 1 ];
};

/**
 * Get the index of a node in its parentNode's childNode list
 *
 * @param {Node} node The node
 * @return {number} Index in parentNode's childNode list
 */
ve.parentIndex = function ( node ) {
	return Array.prototype.indexOf.call( node.parentNode.childNodes, node );
};

/**
 * Get the offset path from ancestor to offset in descendant
 *
 * @param {Node} ancestor The ancestor node
 * @param {Node} node The descendant node
 * @param {number} nodeOffset The offset in the descendant node
 * @return {number[]} The offset path
 */
ve.getOffsetPath = function ( ancestor, node, nodeOffset ) {
	var path = [ nodeOffset ];
	while ( node !== ancestor ) {
		if ( node.parentNode === null ) {
			ve.log( node, 'is not a descendant of', ancestor );
			throw new Error( 'Not a descendant' );
		}
		path.unshift( ve.parentIndex( node ) );
		node = node.parentNode;
	}
	return path;
};

/**
 * Compare two tuples in lexicographical order.
 *
 * This function first compares `a[0]` with `b[0]`, then `a[1]` with `b[1]`, etc.
 * until it encounters a pair where `a[k] != b[k]`; then returns `a[k] - b[k]`.
 *
 * If `a[k] == b[k]` for every `k`, this function returns 0.
 *
 * If a and b are of unequal length, but `a[k] == b[k]` for all `k` that exist in both a and b, then
 * this function returns `Infinity` (if a is longer) or `-Infinity` (if b is longer).
 *
 * @param {number[]} a First tuple
 * @param {number[]} b Second tuple
 * @return {number} `a[k] - b[k]` where k is the lowest k such that `a[k] != b[k]`
 */
ve.compareTuples = function ( a, b ) {
	for ( var i = 0, len = Math.min( a.length, b.length ); i < len; i++ ) {
		if ( a[ i ] !== b[ i ] ) {
			return a[ i ] - b[ i ];
		}
	}
	if ( a.length > b.length ) {
		return Infinity;
	}
	if ( a.length < b.length ) {
		return -Infinity;
	}
	return 0;
};

/**
 * Compare two nodes for position in document
 *
 * Return null if either position is either null or incomparable (e.g. where one of the nodes
 * is detached or the nodes are from different documents).
 *
 * @param {Node|null} node1 First node
 * @param {number|null} offset1 First offset
 * @param {Node|null} node2 Second node
 * @param {number|null} offset2 Second offset
 * @return {number|null} negative, zero or positive number, or null if nodes null or incomparable
 */
ve.compareDocumentOrder = function ( node1, offset1, node2, offset2 ) {
	var commonAncestor = ve.getCommonAncestor( node1, node2 );
	if ( commonAncestor === null ) {
		// Signal no common ancestor. In theory we could disallow this case, and check
		// the nodes for detachedness and same-documentness before each call, but such
		// guard checks would duplicate (either explicitly or implicitly) much of the
		// branch traversal performed in this method.
		return null;
	}
	return ve.compareTuples(
		ve.getOffsetPath( commonAncestor, node1, offset1 ),
		ve.getOffsetPath( commonAncestor, node2, offset2 )
	);
};

/**
 * Get the closest matching DOM position in document order (forward or reverse)
 *
 * A DOM position is represented as an object with "node" and "offset" properties.
 *
 * The noDescend option can be used to exclude the positions inside certain element nodes; it is
 * a jQuery selector/function ( used as a test by $node.is() - see http://api.jquery.com/is/ );
 * it defaults to ve.rejectsCursor . Void elements (those matching ve.isVoidElement) are always
 * excluded.
 *
 * As well as the end position, an array of ve.PositionSteps (node traversals) is returned.
 * The "stop" option is a boolean-valued function used to test each ve.PositionStep in turn. If
 * If it returns true, the position arrived at is returned; else the stepping continues to the
 * next matching DOM position. It defaults to ve.isHardCursorStep .
 *
 * Limitation: some DOM positions cannot actually hold the cursor; e.g. the start of the interior
 * of a table node. Browser cursoring jumps over text node/annotation node boundaries as if they
 * were invisible, and skips over most grapheme clusters e.g. 'x\u0301' (though not all e.g.
 * '\u062D\u0627'). Also, Chromium normalizes cursor focus/offset, when they are set, to the
 * start-most such cursor position (Firefox does not).
 *
 * @param {Object} position Start position
 * @param {Node} position.node Start node
 * @param {number} position.offset Start offset
 * @param {number} direction +1 for forward, -1 for reverse
 * @param {Object} options
 * @param {Function|string} [options.noDescend] Selector or function: nodes to skip over
 * @param {Function} [options.stop] Boolean-valued ve.PositionStep test function
 * @return {Object} The adjacent DOM position encountered
 * @return {Node|null} return.node The node, or null if we stepped past the root node
 * @return {number|null} return.offset The offset, or null if we stepped past the root node
 * @return {Object[]} return.steps Steps taken {node: x, type: leave|cross|enter|internal, offset: n}
 * @see ve#isHardCursorStep
 */
ve.adjacentDomPosition = function ( position, direction, options ) {
	var node = position.node,
		offset = position.offset,
		steps = [];

	var noDescend = options.noDescend || ve.rejectsCursor;
	var stop = options.stop || ve.isHardCursorStep;

	direction = direction < 0 ? -1 : 1;
	var forward = ( direction === 1 );

	while ( true ) {
		var step;
		// If we're at the node's leading edge, move to the adjacent position in the parent node
		if ( offset === ( forward ? node.length || node.childNodes.length : 0 ) ) {
			step = new ve.PositionStep( node, 'leave' );
			steps.push( step );
			if ( node.parentNode === null ) {
				return {
					node: null,
					offset: null,
					steps: steps
				};
			}
			offset = ve.parentIndex( node ) + ( forward ? 1 : 0 );
			node = node.parentNode;
			if ( stop( step ) ) {
				return {
					node: node,
					offset: offset,
					steps: steps
				};
			}
			// Else take another step
			continue;
		}
		// Else we're in the interior of a node

		// If we're in a text node, move to the position in this node at the next offset
		if ( node.nodeType === Node.TEXT_NODE ) {
			step = new ve.PositionStep(
				node,
				'internal',
				offset - ( forward ? 0 : 1 )
			);
			steps.push( step );
			offset += direction;
			if ( stop( step ) ) {
				return {
					node: node,
					offset: offset,
					steps: steps
				};
			}
			continue;
		}
		// Else we're in the interior of an element node

		var childNode = node.childNodes[ forward ? offset : offset - 1 ];

		// Support: Firefox
		// If the child is uncursorable, or is an element matching noDescend, do not
		// descend into it: instead, return the position just beyond it in the current node
		if (
			childNode.nodeType === Node.ELEMENT_NODE &&
			( ve.isVoidElement( childNode ) || $( childNode ).is( noDescend ) )
		) {
			step = new ve.PositionStep( childNode, 'cross' );
			steps.push( step );
			offset += forward ? 1 : -1;
			if ( stop( step ) ) {
				return {
					node: node,
					offset: offset,
					steps: steps
				};
			}
			// Else take another step
			continue;
		}

		// Go to the closest offset inside the child node
		node = childNode;
		offset = forward ? 0 : node.length || node.childNodes.length;
		step = new ve.PositionStep( node, 'enter' );
		steps.push( step );
		if ( stop( step ) ) {
			return {
				node: node,
				offset: offset,
				steps: steps
			};
		}
	}
};

/**
 * Test whether a cursor movement step uses up a cursor press
 *
 * Essentially, this is true unless entering/exiting a contentEditable text/annotation node.
 * For instance in &lt;#text&gt;X&lt;/#text&gt;&lt;b&gt;&lt;#text&gt;y&lt;/#text&gt;&lt;/b&gt;
 * a single cursor press will jump from just after X to just after Y.
 *
 * @param {ve.PositionStep} step The cursor movement step to test
 * @return {boolean} Whether the cursor movement step uses up a cursor press
 * @see ve#adjacentDomPosition
 */
ve.isHardCursorStep = function ( step ) {
	if ( step.node.nodeType === Node.TEXT_NODE ) {
		return step.type === 'internal';
	}
	return ve.isBlockElement( step.node ) || ve.rejectsCursor( step.node );
};

/**
 * Tests whether an adjacent cursor would be prevented from entering the node
 *
 * @param {Node} [node] Element node or text node; defaults to "this" if a Node
 * @return {boolean} Whether an adjacent cursor would be prevented from entering
 */
ve.rejectsCursor = function ( node ) {
	if ( !node && this instanceof Node ) {
		node = this;
	}
	if ( node.nodeType === node.TEXT_NODE ) {
		return false;
	}
	if ( ve.isVoidElement( node ) ) {
		return true;
	}
	// We don't need to check whether the ancestor-nearest contenteditable tag is
	// false, because if so then there can be no adjacent cursor.
	return node.contentEditable === 'false';
};

/**
 * Count the common elements at the start and end of two sequences
 *
 * @param {Array|string} before The original sequence
 * @param {Array|string} after The modified sequence
 * @param {Function} [equals] Two-argument comparison returning boolean (defaults to ===)
 * @return {Object|null} Change offsets (valid in both sequences), or null if unchanged
 * @return {number} return.start Offset from start of first changed element
 * @return {number} return.end Offset from end of last changed element (nonoverlapping with start)
 */
ve.countEdgeMatches = function ( before, after, equals ) {
	if ( !equals ) {
		equals = function ( x, y ) {
			return x === y;
		};
	}

	var start, end;
	var len = Math.min( before.length, after.length );
	// Find maximal matching left slice
	for ( start = 0; start < len; start++ ) {
		if ( !equals( before[ start ], after[ start ] ) ) {
			break;
		}
	}
	if ( start === len && before.length === after.length ) {
		return null;
	}
	// Find maximal matching right slice that doesn't overlap the left slice
	for ( end = 0; end < len - start; end++ ) {
		if ( !equals(
			before[ before.length - 1 - end ],
			after[ after.length - 1 - end ]
		) ) {
			break;
		}
	}
	return { start: start, end: end };
};
