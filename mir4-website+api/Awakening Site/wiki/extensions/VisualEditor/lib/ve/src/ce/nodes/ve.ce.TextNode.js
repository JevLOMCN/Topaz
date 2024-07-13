/*!
 * VisualEditor ContentEditable TextNode class.
 *
 * @copyright See AUTHORS.txt
 */

/**
 * ContentEditable text node.
 *
 * @class
 * @extends ve.ce.LeafNode
 * @constructor
 * @param {ve.dm.TextNode} model Model to observe
 * @param {Object} [config] Configuration options
 */
ve.ce.TextNode = function VeCeTextNode() {
	// Parent constructor
	ve.ce.TextNode.super.apply( this, arguments );

	this.$element = $( [] );
};

/* Inheritance */

OO.inheritClass( ve.ce.TextNode, ve.ce.LeafNode );

/* Static Properties */

ve.ce.TextNode.static.name = 'text';

ve.ce.TextNode.static.splitOnEnter = true;

// Deprecated alias
ve.ce.TextNode.static.whitespaceHtmlCharacters = ve.visibleWhitespaceCharacters;

/* Methods */

/**
 * Get an HTML rendering of the text.
 *
 * @return {Array} Array of rendered HTML fragments with annotations
 */
ve.ce.TextNode.prototype.getAnnotatedHtml = function () {
	var data = this.model.getDocument().getDataFromNode( this.model ),
		whitespaceHtmlChars = ve.visibleWhitespaceCharacters,
		significantWhitespace = this.getModel().getParent().hasSignificantWhitespace();

	function setChar( chr, index ) {
		if ( Array.isArray( data[ index ] ) ) {
			// Don't modify the original array, clone it first
			data[ index ] = data[ index ].slice( 0 );
			data[ index ][ 0 ] = chr;
		} else {
			data[ index ] = chr;
		}
	}

	function getChar( index ) {
		if ( Array.isArray( data[ index ] ) ) {
			return data[ index ][ 0 ];
		} else {
			return data[ index ];
		}
	}

	if ( !significantWhitespace ) {
		for ( var i = 0; i < data.length; i++ ) {
			var char = getChar( i );
			// Show meaningful whitespace characters
			if ( Object.prototype.hasOwnProperty.call( whitespaceHtmlChars, char ) ) {
				setChar( whitespaceHtmlChars[ char ], i );
			}
		}
	}

	return data;
};

/* Registration */

ve.ce.nodeFactory.register( ve.ce.TextNode );
