/*!
 * VisualEditor annotated text content state chunk class
 *
 * @copyright See AUTHORS.txt
 */

/**
 * Uniformly annotated chunk of text content
 *
 * @class
 *
 * @constructor
 * @param {string} text Plain text
 * @param {HTMLElement[]} elements Annotation elements in force
 * @param {string} type If this is a unicorn then 'unicorn', else 'text'
 */
ve.ce.TextStateChunk = function VeCeTextStateChunk( text, elements, type ) {
	/**
	 * @property {string} text The plain text of this chunk
	 */
	this.text = text;

	/**
	 * @property {HTMLElement[]} elements The annotation elements open at this chunk
	 */
	this.elements = elements;

	/**
	 * @property {string} type The chunk type: 'text' or 'unicorn'
	 */
	this.type = type;
};

/* Inheritance */

OO.initClass( ve.ce.TextStateChunk );

/* Static methods */

/**
 * Test whether two elements are equal as annotations.
 * TODO: Improve this test. Currently annotations are compared by tag name and
 * certain attributes, which results in a stricter test than that of
 * ve.dm.ModelRegistry#matchElement . That is, a element pair that tests unequal
 * here might still both match the same ve.dm.Annotation object.
 *
 * @static
 * @param {HTMLElement} element1 An annotation element
 * @param {HTMLElement} element2 Another annotation element
 * @return {boolean} True if the elements are equal as annotations
 */
ve.ce.TextStateChunk.static.compareElements = function ( element1, element2 ) {
	return element1 === element2 || (
		element1.nodeName === element2.nodeName &&
		element1.getAttribute( 'class' ) === element2.getAttribute( 'class' ) &&
		element1.getAttribute( 'typeof' ) === element2.getAttribute( 'typeof' ) &&
		element1.getAttribute( 'property' ) === element2.getAttribute( 'property' ) &&
		element1.getAttribute( 'href' ) === element2.getAttribute( 'href' )
	);
};

/* Methods */

/**
 * Test whether this chunk has the same annotations (in order) as another
 *
 * @param {ve.ce.TextStateChunk} other The other chunk
 * @return {boolean} True if the chunks have the same annotations
 */
ve.ce.TextStateChunk.prototype.hasEqualElements = function ( other ) {
	if ( this.elements === other.elements ) {
		return true;
	}
	if ( this.elements.length !== other.elements.length ) {
		return false;
	}
	for ( var i = 0, len = this.elements.length; i < len; i++ ) {
		if ( !this.constructor.static.compareElements( this.elements[ i ], other.elements[ i ] ) ) {
			return false;
		}
	}
	return true;
};

/**
 * Test whether this chunk is equal to another chunk in both elements and text.
 *
 * @param {ve.ce.TextStateChunk} other The other chunk
 * @return {boolean} True if the chunks are equal
 */
ve.ce.TextStateChunk.prototype.isEqual = function ( other ) {
	return this.text === other.text && this.type === other.type && this.hasEqualElements( other );
};
