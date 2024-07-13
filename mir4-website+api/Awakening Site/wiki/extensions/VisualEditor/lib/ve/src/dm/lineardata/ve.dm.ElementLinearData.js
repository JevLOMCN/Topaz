/*!
 * VisualEditor ElementLinearData classes.
 *
 * Class containing element linear data and an hash-value store.
 *
 * @copyright See AUTHORS.txt
 */

/**
 * Element linear data storage
 *
 * @class
 * @extends ve.dm.FlatLinearData
 * @constructor
 * @param {ve.dm.HashValueStore} store Hash-value store
 * @param {Array} [data] Linear data
 */
ve.dm.ElementLinearData = function VeDmElementLinearData() {
	// Parent constructor
	ve.dm.ElementLinearData.super.apply( this, arguments );
};

/* Inheritance */

OO.inheritClass( ve.dm.ElementLinearData, ve.dm.FlatLinearData );

/* Static Members */

// eslint-disable-next-line security/detect-non-literal-regexp
ve.dm.ElementLinearData.static.startWordRegExp = new RegExp(
	'^(' + unicodeJS.characterclass.patterns.word + ')'
);

// eslint-disable-next-line security/detect-non-literal-regexp
ve.dm.ElementLinearData.static.endWordRegExp = new RegExp(
	'(' + unicodeJS.characterclass.patterns.word + ')$'
);

/* Static Methods */

/**
 * Compare two elements' basic properties
 *
 * Elements are comparable if they have the same type and attributes, or
 * have the same text data. Anything semantically irrelevant is filtered
 * out first.
 *
 * When changing, ensure that ve.dm.Transaction.static.compareElementsForTranslate
 * is also updated.
 *
 * @param {Object|Array|string} a First element
 * @param {Object|Array|string} b Second element
 * @return {boolean} Elements are comparable
 */
ve.dm.ElementLinearData.static.compareElementsUnannotated = function ( a, b ) {
	var aPlain = a,
		bPlain = b;

	if ( a === b ) {
		return true;
	}

	if ( Array.isArray( a ) ) {
		aPlain = a[ 0 ];
	}
	if ( Array.isArray( b ) ) {
		bPlain = b[ 0 ];
	}
	if ( typeof aPlain === 'string' && typeof bPlain === 'string' ) {
		return aPlain === bPlain;
	}

	if ( typeof a !== typeof b ) {
		// Different types
		return false;
	}

	// By this point, both must be objects, so must have equal types
	if ( a.type !== b.type ) {
		return false;
	}

	// Both objects are open elements, so compare hashes.
	// (NB we only need to check one as they have equal .type)
	if ( ve.dm.LinearData.static.isOpenElementData( a ) ) {
		// As we are using hashes, we don't need to worry about annotations
		aPlain = ve.dm.modelRegistry.lookup( a.type ).static.getHashObject( a );
		delete aPlain.originalDomElementsHash;

		bPlain = ve.dm.modelRegistry.lookup( b.type ).static.getHashObject( b );
		delete bPlain.originalDomElementsHash;

		return ve.compare( aPlain, bPlain );
	} else {
		// Both objects are close elements, no need to compare attributes
		return true;
	}
};

/**
 * Compare two elements' basic properties and annotations
 *
 * Elements are comparable if they have the same type, attributes,
 * text data and annotations, as determined by
 * ve.dm.AnnotationSet#compareTo .
 *
 * @param {Object|Array|string} a First element
 * @param {Object|Array|string} b Second element
 * @param {ve.dm.HashValueStore} aStore First element's store
 * @param {ve.dm.HashValueStore} [bStore] Second element's store, if different
 * @return {boolean} Elements are comparable
 */
ve.dm.ElementLinearData.static.compareElements = function ( a, b, aStore, bStore ) {
	if ( a === b ) {
		return true;
	}

	var typeofA = typeof a;

	if ( typeofA !== typeof b ) {
		// Different types
		return false;
	}
	if ( typeofA === 'string' ) {
		// Both strings, and not equal
		return false;
	}
	if ( !this.compareElementsUnannotated( a, b ) ) {
		return false;
	}
	var aAnnotations, bAnnotations;
	// Elements are equal without annotations, now compare annotations:
	if ( Array.isArray( a ) ) {
		aAnnotations = a[ 1 ];
	}
	if ( Array.isArray( b ) ) {
		bAnnotations = b[ 1 ];
	}
	if ( a && a.type ) {
		aAnnotations = a.annotations;
	}
	if ( b && b.type ) {
		bAnnotations = b.annotations;
	}

	var aSet = new ve.dm.AnnotationSet( aStore, aAnnotations || [] );
	var bSet = new ve.dm.AnnotationSet( bStore || aStore, bAnnotations || [] );

	return aSet.compareTo( bSet );
};

/**
 * Read the array of annotation store hashes from an item of linear data
 *
 * @param {string|Array|Object} item Item of linear data
 * @return {string[]} An array of annotation store hashes
 */
ve.dm.ElementLinearData.static.getAnnotationHashesFromItem = function ( item ) {
	if ( typeof item === 'string' ) {
		return [];
	} else if ( item.annotations ) {
		return item.annotations.slice();
	} else if ( item[ 1 ] ) {
		return item[ 1 ].slice();
	} else {
		return [];
	}
};

/**
 * Set annotations' store hashes at a specified offset.
 *
 * Cleans up data structure if hashes array is empty.
 *
 * @param {string|Array|Object} item Item of linear data
 * @param {string[]} hashes Annotations' store hashes
 * @return {string|Array|Object} Deep-copied, modified item
 */
ve.dm.ElementLinearData.static.replaceAnnotationHashesForItem = function ( item, hashes ) {
	var isElement = ve.dm.LinearData.static.isElementData( item );
	item = ve.copy( item );
	hashes = hashes.slice();
	if ( hashes.length > 0 ) {
		if ( isElement ) {
			// New element annotation
			item.annotations = hashes;
		} else {
			// New character annotation
			var character = ve.dm.ElementLinearData.static.getCharacterDataFromItem( item );
			item = [ character, hashes ];
		}
	} else {
		if ( isElement ) {
			// Cleanup empty element annotation
			delete item.annotations;
		} else {
			// Cleanup empty character annotation
			item = ve.dm.ElementLinearData.static.getCharacterDataFromItem( item );
		}
	}
	return item;
};

/**
 * Get character data from an item
 *
 * @param {string|Array|Object} item Item to get character data from
 * @return {string} Character data, or '' if no character data
 */
ve.dm.ElementLinearData.static.getCharacterDataFromItem = function ( item ) {
	var data = Array.isArray( item ) ? item[ 0 ] : item;
	return typeof data === 'string' ? data : '';
};

/* Methods */

/**
 * Check if content can be inserted at an offset in document data.
 *
 * This method assumes that any value that has a type property that's a string is an element object.
 *
 * Content offsets:
 *
 *      <heading> a </heading> <paragraph> b c <img> </img> </paragraph>
 *     .         ^ ^          .           ^ ^ ^     .      ^            .
 *
 * Content offsets:
 *
 *      <list> <listItem> </listItem> <list>
 *     .      .          .           .      .
 *
 * @param {number} offset Document offset
 * @return {boolean} Content can be inserted at offset
 */
ve.dm.ElementLinearData.prototype.isContentOffset = function ( offset ) {
	// Edges are never content
	if ( offset === 0 || offset === this.getLength() ) {
		return false;
	}
	var left = this.getData( offset - 1 );
	var right = this.getData( offset );
	var factory = ve.dm.nodeFactory;
	return (
		// Data exists at offsets
		( left !== undefined && right !== undefined ) &&
		(
			// If there's content on the left or the right of the offset than we are good
			// <paragraph>|a|</paragraph>
			( typeof left === 'string' || typeof right === 'string' ) ||
			// Same checks but for annotated characters - isArray is slower, try it next
			( Array.isArray( left ) || Array.isArray( right ) ) ||
			// The most expensive test are last, these deal with elements
			(
				// Right of a leaf
				// <paragraph><image></image>|</paragraph>
				(
					// Is an element
					typeof left.type === 'string' &&
					// Is a closing
					left.type.charAt( 0 ) === '/' &&
					// Is a leaf
					factory.isNodeContent( left.type.slice( 1 ) )
				) ||
				// Left of a leaf
				// <paragraph>|<image></image></paragraph>
				(
					// Is an element
					typeof right.type === 'string' &&
					// Is not a closing
					right.type.charAt( 0 ) !== '/' &&
					// Is a leaf
					factory.isNodeContent( right.type )
				) ||
				// Inside empty content branch
				// <paragraph>|</paragraph>
				(
					// Inside empty element
					'/' + left.type === right.type &&
					// Both are content branches (right is the same type)
					factory.canNodeContainContent( left.type )
				)
			)
		)
	);
};

/**
 * Check if structure can be inserted at an offset in document data.
 *
 * If the {unrestricted} param is true than only offsets where any kind of element can be inserted
 * will return true. This can be used to detect the difference between a location that a paragraph
 * can be inserted, such as between two tables but not directly inside a table.
 *
 * This method assumes that any value that has a type property that's a string is an element object.
 *
 * Structural offsets (unrestricted = false):
 *
 *      <heading> a </heading> <paragraph> b c <img> </img> </paragraph>
 *     ^         . .          ^           . . .     .      .            ^
 *
 * Structural offsets (unrestricted = true):
 *
 *      <heading> a </heading> <paragraph> b c <img> </img> </paragraph>
 *     ^         . .          ^           . . .     .      .            ^
 *
 * Structural offsets (unrestricted = false):
 *
 *      <list> <listItem> </listItem> <list>
 *     ^      ^          ^           ^      ^
 *
 * Content branch offsets (unrestricted = true):
 *
 *      <list> <listItem> </listItem> <list>
 *     ^      .          ^           .      ^
 *
 * @param {number} offset Document offset
 * @param {boolean} [unrestricted] Only return true if any kind of element can be inserted at offset
 * @return {boolean} Structure can be inserted at offset
 */
ve.dm.ElementLinearData.prototype.isStructuralOffset = function ( offset, unrestricted ) {
	// Edges are always structural
	if ( offset === 0 || offset === this.getLength() ) {
		return true;
	}
	// Offsets must be within range and both sides must be elements
	var left = this.getData( offset - 1 );
	var right = this.getData( offset );
	var factory = ve.dm.nodeFactory;
	return (
		(
			left !== undefined &&
			right !== undefined &&
			typeof left.type === 'string' &&
			typeof right.type === 'string'
		) &&
		(
			// Right of a branch
			// <list><listItem><paragraph>a</paragraph>|</listItem>|</list>|
			(
				// Is a closing
				left.type.charAt( 0 ) === '/' &&
				// Is a branch or non-content leaf
				(
					factory.canNodeHaveChildren( left.type.slice( 1 ) ) ||
					!factory.isNodeContent( left.type.slice( 1 ) )
				) &&
				(
					// Only apply this rule in unrestricted mode
					!unrestricted ||
					// Right of an unrestricted branch
					// <list><listItem><paragraph>a</paragraph>|</listItem></list>|
					// Both are non-content branches that can have any kind of child
					factory.getParentNodeTypes( left.type.slice( 1 ) ) === null
				)
			) ||
			// Left of a branch
			// |<list>|<listItem>|<paragraph>a</paragraph></listItem></list>
			(
				// Is not a closing
				right.type.charAt( 0 ) !== '/' &&
				// Is a branch or non-content leaf
				(
					factory.canNodeHaveChildren( right.type ) ||
					!factory.isNodeContent( right.type )
				) &&
				(
					// Only apply this rule in unrestricted mode
					!unrestricted ||
					// Left of an unrestricted branch
					// |<list><listItem>|<paragraph>a</paragraph></listItem></list>
					// Both are non-content branches that can have any kind of child
					factory.getParentNodeTypes( right.type ) === null
				)
			) ||
			// Inside empty non-content branch
			// <list>|</list> or <list><listItem>|</listItem></list>
			(
				// Inside empty element
				'/' + left.type === right.type &&
				// Both are non-content branches (right is the same type)
				factory.canNodeHaveChildrenNotContent( left.type ) &&
				(
					// Only apply this rule in unrestricted mode
					!unrestricted ||
					// Both are non-content branches that can have any kind of child
					factory.getChildNodeTypes( left.type ) === null
				)
			)
		)
	);
};

/**
 * Check for non-content elements in data.
 *
 * This method assumes that any value that has a type property that's a string is an element object.
 * Elements are discovered by iterating through the entire data array.
 *
 * @return {boolean} True if all elements in data are content elements
 */
ve.dm.ElementLinearData.prototype.isContentData = function () {
	var i = this.getLength();
	while ( i-- ) {
		var item = this.getData( i );
		if ( item.type !== undefined &&
			item.type.charAt( 0 ) !== '/' &&
			!ve.dm.nodeFactory.isNodeContent( item.type )
		) {
			return false;
		}
	}
	return true;
};

/**
 * Check if an annotation can be applied at a specific offset
 *
 * @param {number} offset
 * @param {ve.dm.Annotation} annotation
 * @param {boolean} [ignoreClose] Ignore close elements, otherwise check if their open element is annotatable
 * @return {boolean} Annotation can be applied at this offset
 */
ve.dm.ElementLinearData.prototype.canTakeAnnotationAtOffset = function ( offset, annotation, ignoreClose ) {
	if ( this.isElementData( offset ) ) {
		if ( ignoreClose && this.isCloseElementData( offset ) ) {
			return false;
		}
		var type = this.getType( offset );
		// Structural nodes are never annotatable
		// Disallowed annotations can't be set
		return ve.dm.nodeFactory.isNodeContent( type ) && ve.dm.nodeFactory.canNodeTakeAnnotation( type, annotation );
	} else {
		// Text is always annotatable
		return true;
	}
};

/**
 * Get annotations' store hashes covered by an offset.
 *
 * @param {number} offset Offset to get annotations for
 * @param {boolean} [ignoreClose] Ignore annotations on close elements
 * @return {string[]} An array of annotation store hashes the offset is covered by
 * @throws {Error} offset out of bounds
 */
ve.dm.ElementLinearData.prototype.getAnnotationHashesFromOffset = function ( offset, ignoreClose ) {
	if ( offset < 0 || offset > this.getLength() ) {
		throw new Error( 'offset ' + offset + ' out of bounds' );
	}

	// Since annotations are not stored on a closing leaf node,
	// rewind offset by 1 to return annotations for that structure
	if (
		!ignoreClose &&
		this.isCloseElementData( offset ) &&
		!ve.dm.nodeFactory.canNodeHaveChildren( this.getType( offset ) ) // Leaf node
	) {
		offset = this.getRelativeContentOffset( offset, -1 );
		if ( offset === -1 ) {
			return [];
		}
	}

	var item = this.getData( offset );
	return this.constructor.static.getAnnotationHashesFromItem( item ) || [];
};

/**
 * Get annotations covered by an offset.
 *
 * The returned AnnotationSet is a clone of the one in the data.
 *
 * @param {number} offset Offset to get annotations for
 * @param {boolean} [ignoreClose] Ignore annotations on close elements
 * @return {ve.dm.AnnotationSet} A set of all annotation objects offset is covered by
 * @throws {Error} offset out of bounds
 */
ve.dm.ElementLinearData.prototype.getAnnotationsFromOffset = function ( offset, ignoreClose ) {
	return new ve.dm.AnnotationSet( this.getStore(), this.getAnnotationHashesFromOffset( offset, ignoreClose ) );
};

/**
 * Set annotations of data at a specified offset.
 *
 * Cleans up data structure if annotation set is empty.
 *
 * @param {number} offset Offset to set annotations at
 * @param {ve.dm.AnnotationSet} annotations Annotations to set
 */
ve.dm.ElementLinearData.prototype.setAnnotationsAtOffset = function ( offset, annotations ) {
	this.setAnnotationHashesAtOffset( offset, this.getStore().hashAll( annotations.get() ) );
};

/**
 * Set annotations' store hashes at a specified offset.
 *
 * Cleans up data structure if hashes array is empty.
 *
 * @param {number} offset Offset to set annotation hashes at
 * @param {string[]} hashes Annotations' store hashes
 */
ve.dm.ElementLinearData.prototype.setAnnotationHashesAtOffset = function ( offset, hashes ) {
	var item = this.getData( offset );
	item = this.constructor.static.replaceAnnotationHashesForItem( item, hashes );
	this.setData( offset, item );
};

/**
 * Set or unset an attribute at a specified offset.
 *
 * @param {number} offset Offset to set/unset attribute at
 * @param {string} key Attribute name
 * @param {Mixed} value Value to set, or undefined to unset
 */
ve.dm.ElementLinearData.prototype.setAttributeAtOffset = function ( offset, key, value ) {
	if ( !this.isElementData( offset ) ) {
		return;
	}
	this.modifyData( offset, function ( item ) {
		if ( value === undefined ) {
			// Clear
			if ( item.attributes ) {
				delete item.attributes[ key ];
			}
		} else {
			// Automatically initialize attributes object
			if ( !item.attributes ) {
				item.attributes = {};
			}
			// Set
			item.attributes[ key ] = value;
		}
	} );
};

/**
 * Get character data at a specified offset
 *
 * @param {number} offset Offset to get character data from
 * @return {string} Character data
 */
ve.dm.ElementLinearData.prototype.getCharacterData = function ( offset ) {
	var item = this.getData( offset );
	return ve.dm.ElementLinearData.static.getCharacterDataFromItem( item );
};

/**
 * Gets the range of content surrounding a given offset that's covered by a given annotation.
 *
 * @param {number} offset Offset to begin looking forward and backward from
 * @param {Object} annotation Annotation to test for coverage with
 * @return {ve.Range|null} Range of content covered by annotation, or null if offset is not covered
 */
ve.dm.ElementLinearData.prototype.getAnnotatedRangeFromOffset = function ( offset, annotation ) {
	var start = offset,
		end = offset;
	if ( this.getAnnotationsFromOffset( offset ).contains( annotation ) === false ) {
		return null;
	}
	while ( start > 0 ) {
		start--;
		if ( this.getAnnotationsFromOffset( start ).contains( annotation ) === false ) {
			start++;
			break;
		}
	}
	while ( end < this.getLength() ) {
		if ( this.getAnnotationsFromOffset( end ).contains( annotation ) === false ) {
			break;
		}
		end++;
	}
	return new ve.Range( start, end );
};

/**
 * Get the range of an annotation found within a range.
 *
 * @param {ve.Range} range Range to begin looking forward and backward from
 * @param {ve.dm.Annotation} annotation Annotation to test for coverage with
 * @return {ve.Range|null} Range of content covered by annotation, or a copy of the range
 */
ve.dm.ElementLinearData.prototype.getAnnotatedRangeFromSelection = function ( range, annotation ) {
	var start = range.start,
		end = range.end;
	while ( start > 0 ) {
		start--;
		if ( this.getAnnotationsFromOffset( start ).contains( annotation ) === false ) {
			start++;
			break;
		}
	}
	while ( end < this.getLength() ) {
		if ( this.getAnnotationsFromOffset( end ).contains( annotation ) === false ) {
			break;
		}
		end++;
	}
	return new ve.Range( start, end );
};

/**
 * Get annotations common to all content in a range.
 *
 * @param {ve.Range} range Range to get annotations for
 * @param {boolean} [all=false] Get all annotations found within the range, not just those that cover it
 * @param {boolean} [nullIfContentEmpty=false] Returns null (instead of an empty ve.dm.AnnotationSet) if
 *  there is no content in the range.
 * @return {ve.dm.AnnotationSet|null} All annotation objects range is covered by.
 */
ve.dm.ElementLinearData.prototype.getAnnotationsFromRange = function ( range, all, nullIfContentEmpty ) {
	var ignoreChildrenDepth = 0;
	var left, right;
	// Iterator over the range, looking for annotations, starting at the 2nd character
	for ( var i = range.start; i < range.end; i++ ) {
		if ( this.isElementData( i ) ) {
			if ( ve.dm.nodeFactory.shouldIgnoreChildren( this.getType( i ) ) ) {
				ignoreChildrenDepth += this.isOpenElementData( i ) ? 1 : -1;
			}
			// Skip non-content data
			if ( !ve.dm.nodeFactory.isNodeContent( this.getType( i ) ) ) {
				continue;
			}
		}
		// Ignore things inside ignoreChildren nodes
		if ( ignoreChildrenDepth > 0 ) {
			continue;
		}
		if ( !left ) {
			// Look at left side of range for annotations
			left = this.getAnnotationsFromOffset( i );
			// Shortcut for single character and zero-length ranges
			if ( range.getLength() === 0 || range.getLength() === 1 ) {
				return left;
			}
			continue;
		}
		// Current character annotations
		right = this.getAnnotationsFromOffset( i );
		if ( all && !right.isEmpty() ) {
			left.addSet( right );
		} else if ( !all ) {
			// A non annotated character indicates there's no full coverage
			if ( right.isEmpty() ) {
				return new ve.dm.AnnotationSet( this.getStore() );
			}
			// Exclude comparable annotations that are in left but not right
			left = left.getComparableAnnotationsFromSet( right );
			// If we've reduced left down to nothing, just stop looking
			if ( left.isEmpty() ) {
				break;
			}
		}
	}
	return left || ( nullIfContentEmpty ? null : new ve.dm.AnnotationSet( this.getStore() ) );
};

/**
 * Get the insertion annotations that should apply to a range.
 *
 * The semantics are intended to match Chromium's behaviour.
 * TODO: This cannot match Firefox behaviour, which depends on the cursor's annotation
 * boundary side, and performs a union of the annotations at each end of the selection;
 * see https://phabricator.wikimedia.org/T113869 .
 *
 * @param {ve.Range} range The range into which text would be inserted
 * @param {boolean} [startAfterAnnotations] Use annotations after cursor if collapsed
 * @return {ve.dm.AnnotationSet} The insertion annotations that should apply
 */
ve.dm.ElementLinearData.prototype.getInsertionAnnotationsFromRange = function ( range, startAfterAnnotations ) {
	var start;
	// Get position for start annotations
	if ( range.isCollapsed() && !startAfterAnnotations ) {
		// Use the position just before the cursor
		start = Math.max( 0, range.start - 1 );
	} else {
		// If uncollapsed, use the first character of the selection
		// If collapsed, use the first position after the cursor
		start = range.start;
	}

	var startAnnotations;
	// Get startAnnotations: the annotations that apply at the selection start
	if ( this.isContentOffset( start ) ) {
		startAnnotations = this.getAnnotationsFromOffset( start );
	} else {
		startAnnotations = new ve.dm.AnnotationSet( this.getStore() );
	}

	var afterAnnotations;
	// Get afterAnnotations: the annotations that apply straight after the selection
	if ( this.isContentOffset( range.end ) ) {
		afterAnnotations = this.getAnnotationsFromOffset( range.end );
	} else {
		// Use the empty set
		afterAnnotations = new ve.dm.AnnotationSet( this.getStore() );
	}

	// Return those startAnnotations that either continue in afterAnnotations or
	// should get added to appended content
	return startAnnotations.filter( function ( annotation ) {
		return annotation.constructor.static.applyToAppendedContent ||
			afterAnnotations.containsComparable( annotation );
	} );
};

/**
 * Check if the range has any annotations
 *
 * @param {ve.Range} range Range to check for annotations
 * @return {boolean} The range contains at least one annotation
 */
ve.dm.ElementLinearData.prototype.hasAnnotationsInRange = function ( range ) {
	for ( var i = range.start; i < range.end; i++ ) {
		if ( this.getAnnotationHashesFromOffset( i, true ).length ) {
			return true;
		}
	}
	return false;
};

/**
 * Get a range without any whitespace content at the beginning and end.
 *
 * @param {ve.Range} range Range to trim
 * @return {Object} Trimmed range
 */
ve.dm.ElementLinearData.prototype.trimOuterSpaceFromRange = function ( range ) {
	var start = range.start,
		end = range.end;
	while ( /^\s+$/.test( this.getCharacterData( end - 1 ) ) ) {
		end--;
	}
	while ( start < end && /^\s+$/.test( this.getCharacterData( start ) ) ) {
		start++;
	}
	return range.to < range.end ? new ve.Range( end, start ) : new ve.Range( start, end );
};

/**
 * Check if the data is just text
 *
 * @param {ve.Range} [range] Range to get the data for. The whole data set if not specified.
 * @param {boolean} [ignoreNonContentNodes] Ignore all non-content nodes, e.g. paragraphs, headings, lists
 * @param {string[]} [ignoredTypes] Only ignore specific non-content types
 * @param {boolean} [ignoreCoveringAnnotations] Ignore covering annotations
 * @param {boolean} [ignoreAllAnnotations] Ignore all annotations
 * @return {boolean} The data is plain text
 */
ve.dm.ElementLinearData.prototype.isPlainText = function ( range, ignoreNonContentNodes, ignoredTypes, ignoreCoveringAnnotations, ignoreAllAnnotations ) {
	range = range || new ve.Range( 0, this.getLength() );

	var annotations;
	if ( ignoreCoveringAnnotations ) {
		annotations = this.getAnnotationsFromRange( range );
	}

	for ( var i = range.start; i < range.end; i++ ) {
		if ( typeof this.data[ i ] === 'string' ) {
			// Un-annotated text
			continue;
		} else if ( Array.isArray( this.data[ i ] ) ) {
			// Annotated text
			if ( ignoreAllAnnotations ) {
				continue;
			}
			if (
				ignoreCoveringAnnotations &&
				annotations.containsAllOf( this.getAnnotationsFromOffset( i ) )
			) {
				continue;
			}
		} else if ( ignoreNonContentNodes || ignoredTypes ) {
			// Element data
			var type = this.getType( i );
			if ( ignoredTypes && ignoredTypes.indexOf( type ) !== -1 ) {
				continue;
			}
			if ( ignoreNonContentNodes && !ve.dm.nodeFactory.isNodeContent( type ) ) {
				continue;
			}
		}
		return false;
	}
	return true;
};

/**
 * Execute a callback function for each group of consecutive content data (text or content element).
 *
 * @param {ve.Range} range Range in which to search
 * @param {Function} callback Function called with the following parameters:
 * @param {number} callback.offset Offset of the first datum of the run.
 * @param {string} callback.text Text of the run (with content element opening/closing data
 *   replaced with U+FFFC).
 */
ve.dm.ElementLinearData.prototype.forEachRunOfContent = function ( range, callback ) {
	var text = '';
	for ( var i = range.start; i < range.end; i++ ) {
		if ( !this.isElementData( i ) ) {
			text += this.getCharacterData( i );
		} else if ( ve.dm.nodeFactory.isNodeContent( this.getType( i ) ) ) {
			text += '\uFFFC'; // U+FFFC OBJECT REPLACEMENT CHARACTER
		} else {
			if ( text ) {
				callback( i - text.length, text );
			}
			text = '';
		}
	}
	if ( text ) {
		callback( range.end - text.length, text );
	}
};

/**
 * Get the data as plain text
 *
 * @param {boolean} [maintainIndices] Maintain data offset to string index alignment by replacing elements with line breaks
 * @param {ve.Range} [range] Range to get the data for. The whole data set if not specified.
 * @return {string} Data as plain text
 */
ve.dm.ElementLinearData.prototype.getText = function ( maintainIndices, range ) {
	range = range || new ve.Range( 0, this.getLength() );

	var text = '';
	for ( var i = range.start; i < range.end; i++ ) {
		if ( !this.isElementData( i ) ) {
			text += this.getCharacterData( i );
		} else if ( maintainIndices ) {
			text += '\n';
		}
	}
	return text;
};

/**
 * Get the data as original source text (source mode only)
 *
 * Split paragraphs are converted to single line breaks. It is assumed the
 * document contains nothing but plain text and paragraph elements.
 *
 * @param {ve.Range} [range] Range to get the data for. The whole data set if not specified.
 * @return {string} Data as original source text
 */
ve.dm.ElementLinearData.prototype.getSourceText = function ( range ) {
	return ve.dm.sourceConverter.getSourceTextFromDataRange( this.data, range );
};

/**
 * Get an offset at a distance to an offset that passes a validity test.
 *
 * - If {offset} is not already valid, one step will be used to move it to a valid one.
 * - If {offset} is already valid and cannot be moved in the direction of {distance} and still be
 *   valid, it will be left where it is
 * - If {distance} is zero the result will either be {offset} if it's already valid or the
 *   nearest valid offset to the right if possible and to the left otherwise.
 * - If {offset} is after the last valid offset and {distance} is >= 1, or if {offset} if
 *   before the first valid offset and {distance} <= 1 than the result will be the nearest
 *   valid offset in the opposite direction.
 * - If the data does not contain a single valid offset the result will be -1
 *
 * Nodes that want their children to be ignored (see ve.dm.Node#static-ignoreChildren) are not
 * descended into. Giving a starting offset inside an ignoreChildren node will give unpredictable
 * results.
 *
 * @param {number} offset Offset to start from
 * @param {number} distance Number of valid offsets to move
 * @param {Function} callback Function to call to check if an offset is valid which will be
 * given initial argument of offset
 * @param {...Mixed} [args] Additional arguments to pass to the callback
 * @return {number} Relative valid offset or -1 if there are no valid offsets in data
 */
ve.dm.ElementLinearData.prototype.getRelativeOffset = function ( offset, distance, callback ) {
	var args = Array.prototype.slice.call( arguments, 3 );

	// If offset is already a structural offset and distance is zero than no further work is needed,
	// otherwise distance should be 1 so that we can get out of the invalid starting offset
	if ( distance === 0 ) {
		if ( callback.apply( this, [ offset ].concat( args ) ) ) {
			return offset;
		} else {
			distance = 1;
		}
	}
	// Initial values
	var direction = (
		offset <= 0 ? 1 : (
			offset >= this.getLength() ? -1 : (
				distance > 0 ? 1 : -1
			)
		)
	);
	distance = Math.abs( distance );
	var start = offset;
	var i = start + direction;
	offset = -1;
	var steps = 0;
	var ignoreChildrenDepth = 0;
	var turnedAround = false;
	// Iteration
	while ( i >= 0 && i <= this.getLength() ) {
		// Detect when the search for a valid offset enters a node whose children should be
		// ignored, and don't return an offset inside such a node. This clearly won't work
		// if you start inside such a node, but you shouldn't be doing that to being with
		var dataOffset = i + ( direction > 0 ? -1 : 0 );
		if (
			this.isElementData( dataOffset ) &&
			ve.dm.nodeFactory.shouldIgnoreChildren( this.getType( dataOffset ) )
		) {
			var isOpen = this.isOpenElementData( dataOffset );
			// We have entered a node if we step right over an open, or left over a close.
			// Otherwise we have left a node
			if ( ( direction > 0 && isOpen ) || ( direction < 0 && !isOpen ) ) {
				ignoreChildrenDepth++;
			} else {
				ignoreChildrenDepth--;
				if ( ignoreChildrenDepth < 0 ) {
					return -1;
				}
			}
		}
		if ( callback.apply( this, [ i ].concat( args ) ) ) {
			if ( !ignoreChildrenDepth ) {
				steps++;
				offset = i;
				if ( distance === steps ) {
					return offset;
				}
			}
		} else if (
			// Don't keep turning around over and over
			!turnedAround &&
			// Only turn around if not a single step could be taken
			steps === 0 &&
			// Only turn around if we're about to reach the edge
			( ( direction < 0 && i === 0 ) || ( direction > 0 && ( i === this.getLength() || this.getType( i - 1 ) === 'internalList' ) ) )
		) {
			// Before we turn around, let's see if we are at a valid position
			if ( callback.apply( this, [ start ].concat( args ) ) ) {
				// Stay where we are
				return start;
			}
			// Start over going in the opposite direction
			direction *= -1;
			i = start;
			distance = 1;
			turnedAround = true;
			ignoreChildrenDepth = 0;
		}
		i += direction;
	}
	return offset;
};

/**
 * Get a content offset at a distance from an offset.
 *
 * This method is a wrapper around {getRelativeOffset}, using {isContentOffset} as
 * the offset validation callback.
 *
 * @param {number} offset Offset to start from
 * @param {number} distance Number of content offsets to move
 * @return {number} Relative content offset or -1 if there are no valid offsets in data
 */
ve.dm.ElementLinearData.prototype.getRelativeContentOffset = function ( offset, distance ) {
	return this.getRelativeOffset( offset, distance, this.constructor.prototype.isContentOffset );
};

/**
 * Get the nearest content offset to an offset.
 *
 * If the offset is already a valid offset, it will be returned unchanged. This method differs from
 * calling {getRelativeContentOffset} with a zero length difference because the direction can be
 * controlled without necessarily moving the offset if it's already valid. Also, if the direction
 * is 0 or undefined than nearest offsets will be found to the left and right and the one with the
 * shortest distance will be used.
 *
 * @param {number} offset Offset to start from
 * @param {number} [direction] Direction to prefer matching offset in, -1 for left and 1 for right
 * @return {number} Nearest content offset or -1 if there are no valid offsets in data
 */
ve.dm.ElementLinearData.prototype.getNearestContentOffset = function ( offset, direction ) {
	if ( this.isContentOffset( offset ) ) {
		return offset;
	}
	if ( direction === undefined ) {
		var left = this.getRelativeContentOffset( offset, -1 );
		var right = this.getRelativeContentOffset( offset, 1 );
		return offset - left < right - offset ? left : right;
	} else {
		return this.getRelativeContentOffset( offset, direction > 0 ? 1 : -1 );
	}
};

/**
 * Get a structural offset at a distance from an offset.
 *
 * This method is a wrapper around {getRelativeOffset}, using {this.isStructuralOffset} as
 * the offset validation callback.
 *
 * @param {number} offset Offset to start from
 * @param {number} distance Number of structural offsets to move
 * @param {boolean} [unrestricted] Only consider offsets where any kind of element can be inserted
 * @return {number} Relative structural offset
 */
ve.dm.ElementLinearData.prototype.getRelativeStructuralOffset = function ( offset, distance, unrestricted ) {
	// Optimization: start and end are always unrestricted structural offsets
	if ( distance === 0 && ( offset === 0 || offset === this.getLength() ) ) {
		return offset;
	}
	return this.getRelativeOffset(
		offset, distance, this.constructor.prototype.isStructuralOffset, unrestricted
	);
};

/**
 * Get the nearest structural offset to an offset.
 *
 * If the offset is already a valid offset, it will be returned unchanged. This method differs from
 * calling {getRelativeStructuralOffset} with a zero length difference because the direction can be
 * controlled without necessarily moving the offset if it's already valid. Also, if the direction
 * is 0 or undefined than nearest offsets will be found to the left and right and the one with the
 * shortest distance will be used.
 *
 * @param {number} offset Offset to start from
 * @param {number} [direction] Direction to prefer matching offset in, -1 for left and 1 for right
 * @param {boolean} [unrestricted] Only consider offsets where any kind of element can be inserted
 * @return {number} Nearest structural offset
 */
ve.dm.ElementLinearData.prototype.getNearestStructuralOffset = function ( offset, direction, unrestricted ) {
	if ( this.isStructuralOffset( offset, unrestricted ) ) {
		return offset;
	}
	if ( !direction ) {
		var left = this.getRelativeStructuralOffset( offset, -1, unrestricted );
		var right = this.getRelativeStructuralOffset( offset, 1, unrestricted );
		return offset - left < right - offset ? left : right;
	} else {
		return this.getRelativeStructuralOffset( offset, direction > 0 ? 1 : -1, unrestricted );
	}
};

/**
 * Get the range of the word at offset (else a collapsed range)
 *
 * First, if the offset is not a content offset then it will be moved to the nearest one.
 * Then, if the offset is inside a word, it will be expanded to that word;
 * else if the offset is at the end of a word, it will be expanded to that word;
 * else if the offset is at the start of a word, it will be expanded to that word;
 * else the offset is not adjacent to any word and is returned as a collapsed range.
 *
 * @param {number} offset Offset to start from; must not be inside a surrogate pair
 * @return {ve.Range} Boundaries of the adjacent word (else offset as collapsed range)
 */
ve.dm.ElementLinearData.prototype.getWordRange = function ( offset ) {
	var dataString = new ve.dm.DataString( this.getData() );

	offset = this.getNearestContentOffset( offset );

	var range;
	if ( unicodeJS.wordbreak.isBreak( dataString, offset ) ) {
		// The cursor offset is not inside a word. See if there is an adjacent word
		// codepoint (checking two chars to allow surrogate pairs). If so, expand in that
		// direction only (preferring backwards if there are word codepoints on both
		// sides).

		if ( this.constructor.static.endWordRegExp.exec(
			( dataString.read( offset - 2 ) || ' ' ) +
			( dataString.read( offset - 1 ) || ' ' )
		) ) {
			// Cursor is immediately after a word codepoint: expand backwards
			range = new ve.Range(
				unicodeJS.wordbreak.prevBreakOffset( dataString, offset ),
				offset
			);
		} else if ( this.constructor.static.startWordRegExp.exec(
			( dataString.read( offset ) || ' ' ) +
			( dataString.read( offset + 1 ) || ' ' )
		) ) {
			// Cursor is immediately before a word codepoint: expand forwards
			range = new ve.Range(
				offset,
				unicodeJS.wordbreak.nextBreakOffset( dataString, offset )
			);
		} else {
			// Cursor is not adjacent to a word codepoint: do not expand
			return new ve.Range( offset );
		}
	} else {
		// Cursor is inside a word: expand both backwards and forwards
		range = new ve.Range(
			unicodeJS.wordbreak.prevBreakOffset( dataString, offset ),
			unicodeJS.wordbreak.nextBreakOffset( dataString, offset )
		);
	}
	// Range expanded to all whitespace: collapse
	if ( this.getText( false, range ).trim().length === 0 ) {
		return new ve.Range( offset );
	}
	return range;
};

/**
 * Finds all instances of items being stored in the hash-value store for this data store
 *
 * Currently this is just all annotations still in use.
 *
 * @param {ve.Range} [range] Optional range to get store values for
 * @return {Object} Object containing all store values, keyed by store hash
 */
ve.dm.ElementLinearData.prototype.getUsedStoreValues = function ( range ) {
	var store = this.getStore(),
		valueStore = {};

	range = range || new ve.Range( 0, this.data.length );

	for ( var i = range.start; i < range.end; i++ ) {
		// Annotations
		// Use ignoreClose to save time; no need to count every element annotation twice
		var hashes = this.getAnnotationHashesFromOffset( i, true );
		var j = hashes.length;
		while ( j-- ) {
			var hash = hashes[ j ];
			if ( !Object.prototype.hasOwnProperty.call( valueStore, hash ) ) {
				valueStore[ hash ] = store.value( hash );
			}
		}
		if ( this.data[ i ].originalDomElementsHash !== undefined ) {
			valueStore[ this.data[ i ].originalDomElementsHash ] = store.value( this.data[ i ].originalDomElementsHash );
		}
	}
	return valueStore;
};

/**
 * Remap the internal list indexes used in this linear data.
 *
 * Calls remapInternalListIndexes() for each node.
 *
 * @param {Object} mapping Mapping from internal list indexes to internal list indexes
 * @param {ve.dm.InternalList} internalList Internal list the indexes are being mapped into.
 *  Used for refreshing attribute values that were computed with getNextUniqueNumber().
 */
ve.dm.ElementLinearData.prototype.remapInternalListIndexes = function ( mapping, internalList ) {
	for ( var i = 0, ilen = this.data.length; i < ilen; i++ ) {
		if ( this.isOpenElementData( i ) ) {
			var nodeClass = ve.dm.nodeFactory.lookup( this.getType( i ) );
			// eslint-disable-next-line no-loop-func
			this.modifyData( i, function ( item ) {
				nodeClass.static.remapInternalListIndexes( item, mapping, internalList );
			} );
		}
	}
};

/**
 * Remap the internal list keys used in this linear data.
 *
 * Calls remapInternalListKeys() for each node.
 *
 * @param {ve.dm.InternalList} internalList Internal list the keys are being mapped into.
 */
ve.dm.ElementLinearData.prototype.remapInternalListKeys = function ( internalList ) {
	for ( var i = 0, ilen = this.data.length; i < ilen; i++ ) {
		if ( this.isOpenElementData( i ) ) {
			var nodeClass = ve.dm.nodeFactory.lookup( this.getType( i ) );
			// eslint-disable-next-line no-loop-func
			this.modifyData( i, function ( item ) {
				nodeClass.static.remapInternalListKeys( item, internalList );
			} );
		}
	}
};

/**
 * Remap an annotation hash when it changes
 *
 * @param  {string} oldHash Old hash to replace
 * @param  {string} newHash New hash to replace it with
 */
ve.dm.ElementLinearData.prototype.remapAnnotationHash = function ( oldHash, newHash ) {
	function remap( annotations ) {
		var spliceAt;
		while ( ( spliceAt = annotations.indexOf( oldHash ) ) !== -1 ) {
			if ( annotations.indexOf( newHash ) === -1 ) {
				annotations.splice( spliceAt, 1, newHash );
			} else {
				annotations.splice( spliceAt, 1 );
			}
		}
	}
	for ( var i = 0, ilen = this.data.length; i < ilen; i++ ) {
		if ( this.data[ i ] === undefined || typeof this.data[ i ] === 'string' ) {
			// Common case, cheap, avoid the isArray check
			continue;
		} else {
			this.modifyData( i, function ( item ) {
				if ( Array.isArray( item ) ) {
					remap( item[ 1 ] );
				} else if ( item.annotations !== undefined ) {
					remap( item.annotations );
				}
				if ( ve.getProp( item, 'internal', 'metaItems' ) ) {
					var data = ve.getProp( item, 'internal', 'metaItems' );
					for ( var j = 0, jlen = data.length; j < jlen; j++ ) {
						if ( data[ j ].annotations !== undefined ) {
							remap( data[ j ].annotations );
						}
					}
				}
			} );
		}
	}
};

/**
 * Sanitize data according to a set of rules.
 *
 * @param {Object} rules Sanitization rules
 * @param {string[]} [rules.blacklist] Blacklist of model types which aren't allowed
 * @param {Object} [rules.conversions] Model type conversions to apply, e.g. { heading: 'paragraph' }
 * @param {boolean} [rules.removeOriginalDomElements] Remove references to DOM elements data was converted from
 * @param {boolean} [rules.plainText] Remove all formatting for plain text import
 * @param {boolean} [rules.allowBreaks] Allow <br> line breaks, otherwise the node will be split
 * @param {boolean} [rules.preserveHtmlWhitespace] Preserve non-semantic HTML whitespace
 * @param {boolean} [rules.nodeSanitization] Apply per-type node sanitizations via ve.dm.Node#sanitize
 * @param {boolean} [rules.keepEmptyContentBranches] Preserve empty content branch nodes
 * @param {boolean} [rules.singleLine] Don't allow more that one ContentBranchNode
 * @param {boolean} [rules.allowMetaData] Don't strip metadata
 */
ve.dm.ElementLinearData.prototype.sanitize = function ( rules ) {
	var elementStack = [],
		store = this.getStore(),
		allAnnotations = this.getAnnotationsFromRange( new ve.Range( 0, this.getLength() ), true );

	var i, len;
	var emptySet, setToRemove;
	if ( rules.plainText ) {
		emptySet = new ve.dm.AnnotationSet( store );
	} else {
		if ( rules.removeOriginalDomElements ) {
			// Remove originalDomElements from annotations
			for ( i = 0, len = allAnnotations.getLength(); i < len; i++ ) {
				var ann = allAnnotations.get( i );
				if ( ann.element.originalDomElementsHash !== undefined ) {
					// This changes the hash of the value, so we have to
					// update that. If we don't do this, other assumptions
					// that values fetched from the store are actually in the
					// store will fail.
					var oldHash = store.hashOfValue( ann );
					delete allAnnotations.get( i ).element.originalDomElementsHash;
					var newHash = store.replaceHash( oldHash, ann );
					this.remapAnnotationHash( oldHash, newHash );
					if ( allAnnotations.storeHashes.indexOf( newHash ) !== -1 ) {
						// New annotation-value was already in the set, which
						// just reduces the effective-length of the set.
						allAnnotations.storeHashes.splice( i, 1 );
						i--;
						len--;
					} else {
						allAnnotations.storeHashes.splice( i, 1, newHash );
					}
				}
			}
		}

		// Create annotation set to remove from blacklist
		setToRemove = allAnnotations.filter( function ( annotation ) {
			return (
				rules.blacklist && rules.blacklist[ annotation.name ]
			) || (
				// If original DOM element references are being removed, remove spans
				annotation.name === 'textStyle/span' && rules.removeOriginalDomElements
			);
		} );
	}

	var contentElement;
	for ( i = 0, len = this.getLength(); i < len; i++ ) {
		if ( this.isElementData( i ) ) {
			var type = this.getType( i );
			var canContainContent = ve.dm.nodeFactory.canNodeContainContent( type );
			var isOpen = this.isOpenElementData( i );

			if ( isOpen ) {
				elementStack.push( this.getData( i ) );
			} else {
				elementStack.pop();
			}
			// Apply type conversions
			if ( rules.conversions && rules.conversions[ type ] ) {
				type = rules.conversions[ type ];
				// eslint-disable-next-line no-loop-func
				this.modifyData( i, function ( item ) {
					item.type = ( !isOpen ? '/' : '' ) + type;
				} );
			}

			// Convert content-containing non-paragraph nodes to paragraphs in plainText mode
			if ( rules.plainText && type !== 'paragraph' && canContainContent ) {
				type = 'paragraph';
				this.setData( i, {
					type: ( this.isCloseElementData( i ) ? '/' : '' ) + type
				} );
			}

			// Remove blacklisted nodes, and metadata if disallowed
			if (
				( rules.blacklist && rules.blacklist[ type ] ) ||
				( rules.plainText && type !== 'paragraph' && type !== 'internalList' ) ||
				( !rules.allowMetadata && ve.dm.nodeFactory.isMetaData( type ) )
			) {
				this.splice( i, 1 );
				len--;
				// Make sure you haven't just unwrapped a wrapper paragraph
				if ( isOpen ) {
					this.modifyData( i, function ( item ) {
						ve.deleteProp( item, 'internal', 'generated' );
					} );
				}
				// Move pointer back and continue
				i--;
				continue;
			}

			// Split on breaks
			if ( !rules.allowBreaks && type === 'break' && contentElement ) {
				if ( this.isOpenElementData( i - 1 ) && this.isCloseElementData( i + 2 ) ) {
					// If the break is the only element in another element it was likely added
					// to force it open, so remove it.
					this.splice( i, 2 );
					len -= 2;
				} else {
					this.splice( i, 2, { type: '/' + contentElement.type }, ve.copy( contentElement ) );
				}
				// Move pointer back and continue
				i--;
				continue;
			}

			// If a node is empty but can contain content, then just remove it
			if (
				!rules.keepEmptyContentBranches &&
				isOpen && this.isCloseElementData( i + 1 ) &&
				!ve.getProp( this.getData( i ), 'internal', 'generated' ) &&
				canContainContent
			) {
				this.splice( i, 2 );
				len -= 2;
				// Move pointer back and continue
				i--;
				continue;
			}

			if ( !rules.preserveHtmlWhitespace ) {
				this.modifyData( i, function ( item ) {
					ve.deleteProp( item, 'internal', 'whitespace' );
				} );
			}

			if ( canContainContent && !isOpen && rules.singleLine ) {
				i++;
				var start = i;
				while ( i < len && !( this.isOpenElementData( i ) && this.getType( i ) === 'internalList' ) ) {
					i++;
				}
				this.splice( start, i - start );
				break;
			}

			// Store the current contentElement for splitting
			if ( canContainContent ) {
				contentElement = isOpen ? this.getData( i ) : null;
			}
		} else {
			// Support: Firefox
			// Remove plain newline characters, as they are semantically meaningless
			// and will confuse the user. Firefox adds these automatically when copying
			// line-wrapped HTML. T104790
			// However, don't remove them if we're in a situation where they might
			// actually be meaningful -- i.e. if we're inside a <pre>. T132006
			if (
				this.getCharacterData( i ) === '\n' &&
				// Get last open type from the stack
				!ve.dm.nodeFactory.doesNodeHaveSignificantWhitespace( elementStack[ elementStack.length - 1 ].type )
			) {
				if ( /^\s+$/.test( this.getCharacterData( i + 1 ) ) ||
					/^\s+$/.test( this.getCharacterData( i - 1 ) )
				) {
					// If whitespace-adjacent, remove the newline to avoid double spaces
					this.splice( i, 1 );
					len--;
					// Move pointer back and continue
					i--;
					continue;
				} else {
					// …otherwise replace it with a space
					if ( typeof this.getData( i ) === 'string' ) {
						this.setData( i, ' ' );
					} else {
						this.modifyData( i, function ( item ) {
							item[ 0 ] = ' ';
						} );
					}
				}
			}
			// Support: Chrome, Safari
			// Sometimes all spaces are replaced with NBSP by the browser, so replace those
			// which aren't adjacent to plain spaces. T183647
			if (
				this.getCharacterData( i ) === '\u00a0' &&
				// Get last open type from the stack
				!ve.dm.nodeFactory.doesNodeHaveSignificantWhitespace( elementStack[ elementStack.length - 1 ].type )
			) {
				if ( !( this.getCharacterData( i + 1 ) === ' ' || this.getCharacterData( i - 1 ) === ' ' ) ) {
					// Replace with a space
					if ( typeof this.getData( i ) === 'string' ) {
						this.setData( i, ' ' );
					} else {
						this.modifyData( i, function ( item ) {
							item[ 0 ] = ' ';
						} );
					}
				}
			}
		}
		var annotations = this.getAnnotationsFromOffset( i, true );
		if ( !annotations.isEmpty() ) {
			if ( rules.plainText ) {
				this.setAnnotationsAtOffset( i, emptySet );
			} else if ( setToRemove.getLength() ) {
				// Remove blacklisted annotations
				annotations.removeSet( setToRemove );
				this.setAnnotationsAtOffset( i, annotations );
			}
		}
		if ( this.isOpenElementData( i ) ) {
			if ( rules.nodeSanitization ) {
				var nodeClass = ve.dm.modelRegistry.lookup( this.getType( i ) );
				// Perform per-class sanitizations:
				// eslint-disable-next-line no-loop-func
				this.modifyData( i, function ( item ) {
					nodeClass.static.sanitize( item, rules );
				} );
			}
			if ( rules.removeOriginalDomElements ) {
				this.modifyData( i, function ( item ) {
					// Remove originalDomElements from nodes
					delete item.originalDomElementsHash;
				} );
			}
			// Remove metadata if disallowed (moved metadata)
			if ( !rules.allowMetadata ) {
				this.modifyData( i, function ( item ) {
					ve.deleteProp( item, 'internal', 'metaItems' );
				} );
			}
		}
	}
};

/**
 * Run all elements through getClonedElement(). This should be done if
 * you intend to insert the sliced data back into the document as a copy
 * of the original data (e.g. for copy and paste).
 *
 * @param {boolean} preserveGenerated Preserve internal.generated properties of elements
 */
ve.dm.ElementLinearData.prototype.cloneElements = function ( preserveGenerated ) {
	var store = this.getStore();
	for ( var i = 0, len = this.getLength(); i < len; i++ ) {
		if ( this.isOpenElementData( i ) ) {
			var nodeClass = ve.dm.nodeFactory.lookup( this.getType( i ) );
			if ( nodeClass ) {
				this.setData( i, nodeClass.static.cloneElement( this.getData( i ), store, preserveGenerated ) );
			}
		}
	}
};

/**
 * Counts all elements that aren't between internalList and /internalList
 *
 * @param {number} [limit] Number of elements after which to stop counting
 * @return {number} Number of elements that aren't in an internalList
 */
ve.dm.ElementLinearData.prototype.countNonInternalElements = function ( limit ) {
	var internalDepth = 0,
		count = 0;
	for ( var i = 0, l = this.getLength(); i < l; i++ ) {
		var type = this.getType( i );
		if ( type && ve.dm.nodeFactory.isNodeInternal( type ) ) {
			if ( this.isOpenElementData( i ) ) {
				internalDepth++;
			} else {
				internalDepth--;
			}
		} else if ( !internalDepth ) {
			count++;
			if ( limit && count >= limit ) {
				return count;
			}
		}
	}
	return count;
};

/**
 * Checks if the document has content that's not part of an internalList.
 *
 * @return {boolean} The document has content
 */
ve.dm.ElementLinearData.prototype.hasContent = function () {
	// Two or less elements (<p>, </p>) is considered an empty document
	// For performance, abort the count when we reach 3.
	return this.countNonInternalElements( 3 ) > 2 ||
		// Also check that the element is not a content branch node, e.g. a blockImage
		// and also that is not the internal list
		(
			this.isElementData( 0 ) &&
			!ve.dm.nodeFactory.canNodeContainContent( this.getType( 0 ) ) &&
			!ve.dm.nodeFactory.isNodeInternal( this.getType( 0 ) )
		);
};

/**
 * Get the length of the common start sequence of annotations that applies to a whole range
 *
 * @param {ve.Range} range The document range
 * @return {number} Common start sequence length (0 if the range is empty)
 */
ve.dm.ElementLinearData.prototype.getCommonAnnotationArrayLength = function ( range ) {
	var annotationHashesForOffset = [];
	for ( var i = range.start; i < range.end; i++ ) {
		annotationHashesForOffset.push( this.getAnnotationHashesFromOffset( i ) );
	}
	return ve.getCommonStartSequenceLength( annotationHashesForOffset );
};
