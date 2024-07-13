/*!
 * VisualEditor DataModel AlienNode class.
 *
 * @copyright See AUTHORS.txt
 */

/**
 * DataModel alien node.
 *
 * @class
 * @abstract
 * @extends ve.dm.LeafNode
 * @mixins ve.dm.FocusableNode
 *
 * @constructor
 * @param {Object} [element] Reference to element in linear model
 */
ve.dm.AlienNode = function VeDmAlienNode() {
	// Parent constructor
	ve.dm.AlienNode.super.apply( this, arguments );

	// Mixin constructor
	ve.dm.FocusableNode.call( this );
};

/* Inheritance */

OO.inheritClass( ve.dm.AlienNode, ve.dm.LeafNode );

OO.mixinClass( ve.dm.AlienNode, ve.dm.FocusableNode );

/* Static members */

ve.dm.AlienNode.static.name = 'alien';

ve.dm.AlienNode.static.preserveHtmlAttributes = false;

ve.dm.AlienNode.static.enableAboutGrouping = true;

ve.dm.AlienNode.static.matchRdfaTypes = [ 've:Alien' ];

ve.dm.AlienNode.static.matchFunction = function () {
	return true;
};

ve.dm.AlienNode.static.toDataElement = function ( domElements, converter ) {
	var element;

	if ( this.name !== 'alien' ) {
		element = { type: this.name };
	} else if ( domElements.length === 1 && [ 'td', 'th' ].indexOf( domElements[ 0 ].nodeName.toLowerCase() ) !== -1 ) {
		var attributes = {};
		ve.dm.TableCellableNode.static.setAttributes( attributes, domElements );
		element = {
			type: 'alienTableCell',
			attributes: attributes
		};
	} else {
		var isInline = this.isHybridInline( domElements, converter );
		var type = isInline ? 'alienInline' : 'alienBlock';
		element = { type: type };
	}

	return element;
};

ve.dm.AlienNode.static.toDomElements = function ( dataElement, doc, converter ) {
	return ve.copyDomElements( converter.getStore().value( dataElement.originalDomElementsHash ) || [], doc );
};

/**
 * @inheritdoc
 */
ve.dm.AlienNode.static.isDiffComparable = function ( element, other, elementStore, otherStore ) {
	if ( element.type === other.type ) {
		if ( element.originalDomElementsHash === other.originalDomElementsHash ) {
			return true;
		}
	} else {
		return false;
	}

	// HACK: We can't strip 'about' attributes before converting, as we need them
	// for about grouping, but we should ignore them for diffing as they can be
	// non-persistent in historical diffs.

	function removeAboutAttributes( el ) {
		Array.prototype.forEach.call( el.querySelectorAll( '[about]' ), function ( e ) {
			e.removeAttribute( 'about' );
		} );
	}

	// Deep copy DOM nodes from store
	var elementOriginalDomElements = ve.copy( elementStore.value( element.originalDomElementsHash ) );
	var otherOriginalDomElements = ve.copy( otherStore.value( other.originalDomElementsHash ) );
	// Remove about attributes
	elementOriginalDomElements.forEach( removeAboutAttributes );
	otherOriginalDomElements.forEach( removeAboutAttributes );
	// Compare DOM trees
	return ve.compare(
		ve.copy( elementOriginalDomElements, ve.convertDomElements ),
		ve.copy( otherOriginalDomElements, ve.convertDomElements )
	);
};

/**
 * @inheritdoc
 */
ve.dm.AlienNode.static.getHashObject = function ( dataElement ) {
	return {
		type: dataElement.type,
		// Some comparison methods ignore the originalDomElementsHash
		// property. Rename it so it doesn't get ignored for alien nodes.
		alienDomElementsHash: dataElement.originalDomElementsHash
	};
};

/* Registration */

ve.dm.modelRegistry.register( ve.dm.AlienNode );
