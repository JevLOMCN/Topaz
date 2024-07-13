/*!
 * VisualEditor DataModel AnnotationSet class.
 *
 * @copyright See AUTHORS.txt
 */

/**
 * Annotation set.
 *
 * @constructor
 * @param {ve.dm.HashValueStore} store
 * @param {string[]} [storeHashes]
 */
ve.dm.AnnotationSet = function VeDmAnnotationSet( store, storeHashes ) {
	// Parent constructor
	this.store = store;
	this.storeHashes = storeHashes || [];

	if ( this.get().indexOf( undefined ) !== -1 ) {
		throw new Error( 'Annotation with hash ' +
			this.storeHashes[ this.get().indexOf( undefined ) ] +
			' not found in store'
		);
	}
};

/* Methods */

/**
 * Get the hash-value store.
 *
 * @return {ve.dm.HashValueStore} Hash-value store
 */
ve.dm.AnnotationSet.prototype.getStore = function () {
	return this.store;
};

/**
 * Get a clone.
 *
 * @return {ve.dm.AnnotationSet} Copy of annotation set
 */
ve.dm.AnnotationSet.prototype.clone = function () {
	return new ve.dm.AnnotationSet( this.getStore(), this.storeHashes.slice() );
};

/**
 * Get an annotation set containing only annotations within the set with a specific name.
 *
 * @param {string} name Type name
 * @return {ve.dm.AnnotationSet} Copy of annotation set
 */
ve.dm.AnnotationSet.prototype.getAnnotationsByName = function ( name ) {
	return this.filter( function ( annotation ) {
		return annotation.name === name;
	} );
};

/**
 * Get an annotation set containing only annotations within the set which are comparable
 * to a specific annotation.
 *
 * @param {ve.dm.Annotation} annotation Annotation to compare to
 * @return {ve.dm.AnnotationSet} Copy of annotation set
 */
ve.dm.AnnotationSet.prototype.getComparableAnnotations = function ( annotation ) {
	return this.filter( function ( a ) {
		return ve.compare(
			annotation.getComparableObject(),
			a.getComparableObject()
		);
	} );
};

/**
 * Get an annotation set containing only annotations within the set which are comparable
 * to an annotation from another set.
 *
 * @param {ve.dm.AnnotationSet} annotations Annotation set to compare to
 * @return {ve.dm.AnnotationSet} Copy of annotation set
 */
ve.dm.AnnotationSet.prototype.getComparableAnnotationsFromSet = function ( annotations ) {
	return this.filter( function ( a ) {
		return annotations.containsComparable( a );
	} );
};

/**
 * Check if any annotations in the set have a specific name.
 *
 * @param {string} name Type name
 * @return {boolean} Annotation of given type exists in the set
 */
ve.dm.AnnotationSet.prototype.hasAnnotationWithName = function ( name ) {
	return this.containsMatching( function ( annotation ) {
		return annotation.name === name;
	} );
};

/**
 * Get an annotation or all annotations from the set.
 *
 * set.get( 5 ) returns the annotation at offset 5, set.get() returns an array with all annotations
 * in the entire set.
 *
 * @param {number} [offset] If set, only get the annotation at the offset
 * @return {ve.dm.Annotation[]|ve.dm.Annotation|undefined} The annotation at offset, or an array of all
 *  annotations in the set
 */
ve.dm.AnnotationSet.prototype.get = function ( offset ) {
	if ( offset !== undefined ) {
		return this.getStore().value( this.getHash( offset ) );
	} else {
		return this.getStore().values( this.getHashes() );
	}
};

/**
 * Get store hash from offset within annotation set.
 *
 * @param {number} offset Offset within annotation set
 * @return {string} Store hash at specified offset
 */
ve.dm.AnnotationSet.prototype.getHash = function ( offset ) {
	return this.storeHashes[ offset ];
};

/**
 * Get all store hashes.
 *
 * @return {string[]} Store hashes
 */
ve.dm.AnnotationSet.prototype.getHashes = function () {
	return this.storeHashes;
};

/**
 * Get the length of the set.
 *
 * @return {number} The number of annotations in the set
 */
ve.dm.AnnotationSet.prototype.getLength = function () {
	return this.storeHashes.length;
};

/**
 * Check if the set is empty.
 *
 * @return {boolean} The set is empty
 */
ve.dm.AnnotationSet.prototype.isEmpty = function () {
	return this.getLength() === 0;
};

/**
 * Check whether a given annotation occurs in the set.
 *
 * Annotations are compared by store hash.
 *
 * @param {ve.dm.Annotation} annotation
 * @return {boolean} There is an annotation in the set with the same hash as annotation
 */
ve.dm.AnnotationSet.prototype.contains = function ( annotation ) {
	return this.offsetOf( annotation ) !== -1;
};

/**
 * Check whether a given store hash occurs in the set.
 *
 * @param {string} storeHash Store hash of annotation
 * @return {boolean} There is an annotation in the set with this store hash
 */
ve.dm.AnnotationSet.prototype.containsHash = function ( storeHash ) {
	return this.getHashes().indexOf( storeHash ) !== -1;
};

/**
 * Check whether the set contains any of the annotations in another set.
 *
 * @param {ve.dm.AnnotationSet} set Set to compare the set with
 * @return {boolean} There is at least one annotation in set that is also in the set
 */
ve.dm.AnnotationSet.prototype.containsAnyOf = function ( set ) {
	var setHashes = set.getHashes(),
		thisHashes = this.getHashes();
	for ( var i = 0, length = setHashes.length; i < length; i++ ) {
		if ( thisHashes.indexOf( setHashes[ i ] ) !== -1 ) {
			return true;
		}
	}
	return false;
};

/**
 * Check whether the set contains all of the annotations in another set.
 *
 * @param {ve.dm.AnnotationSet} set Set to compare the set with
 * @return {boolean} All annotations in set are also in the set
 */
ve.dm.AnnotationSet.prototype.containsAllOf = function ( set ) {
	var setHashes = set.getHashes(),
		thisHashes = this.getHashes();
	for ( var i = 0, length = setHashes.length; i < length; i++ ) {
		if ( thisHashes.indexOf( setHashes[ i ] ) === -1 ) {
			return false;
		}
	}
	return true;
};

/**
 * Get the offset of a given annotation in the set.
 *
 * @param {ve.dm.Annotation} annotation Annotation to search for
 * @return {number} Offset of annotation in the set, or -1 if annotation is not in the set.
 */
ve.dm.AnnotationSet.prototype.offsetOf = function ( annotation ) {
	return this.offsetOfHash( this.getStore().hashOfValue( annotation ) );
};

/**
 * Get the offset of a given annotation in the set by store hash.
 *
 * @param {string} storeHash Store hash of annotation to search for
 * @return {number} Offset of annotation in the set, or -1 if annotation is not in the set.
 */
ve.dm.AnnotationSet.prototype.offsetOfHash = function ( storeHash ) {
	return this.getHashes().indexOf( storeHash );
};

/**
 * Filter the set by an item property.
 *
 * This returns a new set with all annotations in the set for which the callback returned true for.
 *
 * @param {Function} callback Function that takes an annotation and returns boolean true to include
 * @param {boolean} [returnBool] For internal use only
 * @return {ve.dm.AnnotationSet|boolean} New set containing only the matching annotations
 */
ve.dm.AnnotationSet.prototype.filter = function ( callback, returnBool ) {
	var result;

	if ( !returnBool ) {
		result = this.clone();
		// TODO: Should we be returning this on all methods that modify the original? Might help
		// with chainability, but perhaps it's also confusing because most chainable methods return
		// a new hash set.
		result.removeAll();
	}
	for ( var i = 0, length = this.getLength(); i < length; i++ ) {
		var storeHash = this.getHash( i );
		var annotation = this.getStore().value( storeHash );
		if ( callback( annotation ) ) {
			if ( returnBool ) {
				return true;
			} else {
				result.storeHashes.push( storeHash );
			}
		}
	}
	return returnBool ? false : result;
};

/**
 * Check if the set contains an annotation comparable to the specified one.
 *
 * getComparableObject is used to compare the annotations, and should return
 * true if an annotation is found which is mergeable with the specified one.
 *
 * @param {ve.dm.Annotation} annotation Annotation to compare to
 * @return {boolean} At least one comparable annotation found
 */
ve.dm.AnnotationSet.prototype.containsComparable = function ( annotation ) {
	return this.filter( function ( a ) {
		return annotation.compareTo( a );
	}, true );
};

/**
 * Get the first annotation mergeable with the specified one
 *
 * @param {ve.dm.Annotation} annotation Annotation to compare to
 * @return {ve.dm.Annotation|null} First matching annotation
 */
ve.dm.AnnotationSet.prototype.getComparable = function ( annotation ) {
	for ( var i = 0, len = this.getLength(); i < len; i++ ) {
		var ann = this.getStore().value( this.getHash( i ) );
		if ( ann.compareTo( annotation ) ) {
			return ann;
		}
	}
	return null;
};

/**
 * FIXME T126031: Check if the set contains an annotation comparable to the
 * specified one for the purposes of serialization.
 *
 * This method uses getComparableObjectForSerialization which also includes
 * HTML attributes.
 *
 * @param {ve.dm.Annotation} annotation Annotation to compare to
 * @return {boolean} At least one comparable annotation found
 */
ve.dm.AnnotationSet.prototype.containsComparableForSerialization = function ( annotation ) {
	return this.filter( function ( a ) {
		return annotation.compareToForSerialization( a );
	}, true );
};

/**
 * Check if the set contains at least one annotation where a given property matches a given filter.
 *
 * This is equivalent to (but more efficient than) `!this.filter( .. ).isEmpty()`.
 *
 * @see ve.dm.AnnotationSet#filter
 *
 * @param {Function} callback Function that takes an annotation and returns boolean true to include
 * @return {boolean} At least one matching annotation found
 */
ve.dm.AnnotationSet.prototype.containsMatching = function ( callback ) {
	return this.filter( callback, true );
};

/**
 * Check if the set contains the same annotations as another set.
 *
 * Compares annotations by their comparable object value.
 *
 * @param {ve.dm.AnnotationSet} annotationSet The annotationSet to compare this one to
 * @return {boolean} The annotations are the same
 */
ve.dm.AnnotationSet.prototype.compareTo = function ( annotationSet ) {
	var length = this.getHashes().length;

	if ( length === annotationSet.getLength() ) {
		for ( var i = 0; i < length; i++ ) {
			if ( !annotationSet.containsComparable( this.get( i ) ) ) {
				return false;
			}
		}
	} else {
		return false;
	}
	return true;
};

/**
 * Filter out all annotations in this set which are comparable to those in another set
 *
 * This returns a new set.
 *
 * @param  {ve.dm.AnnotationSet} set The AnnotationSet to filter out
 * @return {ve.dm.AnnotationSet} A new set without the comparable annotations
 */
ve.dm.AnnotationSet.prototype.withoutComparableSet = function ( set ) {
	return this.filter( function ( annotation ) {
		return !set.containsComparable( annotation );
	} );
};

/**
 * Strictly compare two annotation sets for equality.
 *
 * This method only considers two annotation sets to be equal if they contain exactly the same
 * annotations (not just comparable, but with the same hash in the HashValueStore)
 * in exactly the same order.
 *
 * @param {ve.dm.AnnotationSet} set The annotation set to compare this one to
 * @return {boolean} The annotation sets are equal
 */
ve.dm.AnnotationSet.prototype.equalsInOrder = function ( set ) {
	var ourHashes = this.getHashes(),
		theirHashes = set.getHashes();
	if ( ourHashes.length !== theirHashes.length ) {
		return false;
	}
	for ( var i = 0, len = ourHashes.length; i < len; i++ ) {
		if ( ourHashes[ i ] !== theirHashes[ i ] ) {
			return false;
		}
	}
	return true;
};

/**
 * Add an annotation to the set.
 *
 * If the annotation is already present in the set, nothing happens.
 *
 * The annotation will be inserted before the annotation that is currently at the given offset. If offset is
 * negative, it will be counted from the end (i.e. offset -1 is the last item, -2 the second-to-last,
 * etc.). If offset is out of bounds, the annotation will be added to the end of the set.
 *
 * @param {ve.dm.Annotation} annotation Annotation to add
 * @param {number} offset Offset to add the annotation at
 */
ve.dm.AnnotationSet.prototype.add = function ( annotation, offset ) {
	var storeHash = this.getStore().hash( annotation );
	// Negative offset
	if ( offset < 0 ) {
		offset = this.getLength() + offset;
	}
	// Greater than length, add to end
	if ( offset >= this.getLength() ) {
		this.push( annotation );
		return;
	}
	// If not in set already, splice in place
	if ( !this.containsHash( storeHash ) ) {
		this.storeHashes.splice( offset, 0, storeHash );
	}
};

/**
 * Add all annotations in the given set, removing any duplicates (including existing ones).
 *
 * The offset calculation happens before duplicates are removed.
 *
 * @param {ve.dm.AnnotationSet} set Set to add to the set
 * @param {number} [offset] Offset at which to insert; defaults to the end of the set
 */
ve.dm.AnnotationSet.prototype.addSet = function ( set, offset ) {
	var hashes = this.getHashes();
	if ( offset === undefined ) {
		offset = hashes.length;
	}
	this.storeHashes = OO.simpleArrayUnion(
		hashes.slice( 0, offset ),
		set.getHashes(),
		hashes.slice( offset )
	);
};

/**
 * Add an annotation at the end of the set.
 *
 * @param {ve.dm.Annotation} annotation Annotation to add
 */
ve.dm.AnnotationSet.prototype.push = function ( annotation ) {
	this.pushHash( this.getStore().hash( annotation ) );
};

/**
 * Add an annotation at the end of the set by store hash.
 *
 * @param {string} storeHash Store hash of annotation to add
 */
ve.dm.AnnotationSet.prototype.pushHash = function ( storeHash ) {
	this.storeHashes.push( storeHash );
};

/**
 * Remove the annotation at a given offset.
 *
 * @param {number} offset Offset to remove item at. If negative, the counts from the end, see add()
 * @throws {Error} Offset out of bounds.
 */
ve.dm.AnnotationSet.prototype.removeAt = function ( offset ) {
	if ( offset < 0 ) {
		offset = this.getLength() + offset;
	}
	if ( offset >= this.getLength() ) {
		throw new Error( 'Offset out of bounds' );
	}
	this.storeHashes.splice( offset, 1 );
};

/**
 * Remove a given annotation from the set by store hash.
 *
 * If the annotation isn't in the set, nothing happens.
 *
 * @param {string} storeHash Store hash of annotation to remove
 */
ve.dm.AnnotationSet.prototype.removeHash = function ( storeHash ) {
	var offset = this.offsetOfHash( storeHash );
	if ( offset !== -1 ) {
		this.storeHashes.splice( offset, 1 );
	}
};

/**
 * Remove a given annotation from the set.
 *
 * If the annotation isn't in the set, nothing happens.
 *
 * @param {ve.dm.Annotation} annotation Annotation to remove
 */
ve.dm.AnnotationSet.prototype.remove = function ( annotation ) {
	var offset = this.offsetOf( annotation );
	if ( offset !== -1 ) {
		this.storeHashes.splice( offset, 1 );
	}
};

/**
 * Remove all annotations.
 */
ve.dm.AnnotationSet.prototype.removeAll = function () {
	this.storeHashes = [];
};

/**
 * Remove all annotations in a given set from the set.
 *
 * Annotations that aren't in the set are ignored.
 *
 * @param {ve.dm.AnnotationSet} set Set to remove from the set
 */
ve.dm.AnnotationSet.prototype.removeSet = function ( set ) {
	this.storeHashes = OO.simpleArrayDifference( this.getHashes(), set.getHashes() );
};

/**
 * Remove all annotations that are not also in a given other set from the set.
 *
 * @param {ve.dm.AnnotationSet} set Set to intersect with the set
 */
ve.dm.AnnotationSet.prototype.removeNotInSet = function ( set ) {
	this.storeHashes = OO.simpleArrayIntersection( this.getHashes(), set.getHashes() );
};

/**
 * Reverse the set.
 *
 * This returns a copy, the original set is not modified.
 *
 * @return {ve.dm.AnnotationSet} Copy of the set with the order reversed.
 */
ve.dm.AnnotationSet.prototype.reversed = function () {
	var newSet = this.clone();
	newSet.storeHashes.reverse();
	return newSet;
};

/**
 * Merge another set into the set.
 *
 * This returns a copy, the original set is not modified.
 *
 * @param {ve.dm.AnnotationSet} set Other set
 * @return {ve.dm.AnnotationSet} Set containing all annotations in the set as well as all annotations in set
 */
ve.dm.AnnotationSet.prototype.mergeWith = function ( set ) {
	var newSet = this.clone();
	newSet.addSet( set );
	return newSet;
};

/**
 * Get the difference between the set and another set.
 *
 * @param {ve.dm.AnnotationSet} set Other set
 * @return {ve.dm.AnnotationSet} New set containing all annotations that are in the set but not in set
 */
ve.dm.AnnotationSet.prototype.diffWith = function ( set ) {
	var newSet = this.clone();
	newSet.removeSet( set );
	return newSet;
};

/**
 * Get the intersection of the set with another set.
 *
 * This requires annotations to be strictly equal. To find annotations which are comparable
 * use the slower #getComparableAnnotationsFromSet with #addSet.
 *
 * @param {ve.dm.AnnotationSet} set Other set
 * @return {ve.dm.AnnotationSet} New set containing all annotations that are both in the set and in set
 */
ve.dm.AnnotationSet.prototype.intersectWith = function ( set ) {
	var newSet = this.clone();
	newSet.removeNotInSet( set );
	return newSet;
};
