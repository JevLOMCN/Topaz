/*!
 * VisualEditor DataModel MWIncludesNode class.
 *
 * @copyright See AUTHORS.txt
 * @license The MIT License (MIT); see LICENSE.txt
 */

/**
 * DataModel MW node for noinclude, includeonly and onlyinclude tags.
 *
 * @class
 * @extends ve.dm.AlienInlineNode
 * @constructor
 * @param {Object} element Reference to element in linear model
 */
ve.dm.MWIncludesNode = function VeDmMWIncludesNode() {
	// Parent constructor
	ve.dm.MWIncludesNode.super.apply( this, arguments );
};

/* Inheritance */

OO.inheritClass( ve.dm.MWIncludesNode, ve.dm.AlienInlineNode );

/* Static Properties */

ve.dm.MWIncludesNode.static.name = 'mwIncludes';

ve.dm.MWIncludesNode.static.matchRdfaTypes = [
	/^mw:Includes\//
];

/* Static Methods */

/**
 * @inheritdoc
 */
ve.dm.MWIncludesNode.static.toDataElement = function ( domElements ) {
	var mwDataJSON = domElements[ 0 ].getAttribute( 'data-mw' ),
		type = domElements[ 0 ].getAttribute( 'typeof' );

	var dataElement = {
		type: 'mwIncludes',
		attributes: {
			type: type
		}
	};

	if ( mwDataJSON !== null ) {
		dataElement.attributes.mw = JSON.parse( mwDataJSON );
	}

	if ( type === 'mw:Includes/IncludeOnly/End' ) {
		// We don't want to allow typing between this and the opening tag, as the content is stored in
		// data-mw as wikitext. Therefore pretend that includeonly nodes have an implicit closing tag.
		// This should be fine since the closing tag directly follows the opening tag.
		return [];
	}

	return dataElement;
};

/**
 * @inheritdoc
 */
ve.dm.MWIncludesNode.static.toDomElements = function ( dataElement, doc, converter ) {
	var el = doc.createElement( 'meta' );
	el.setAttribute( 'typeof', dataElement.attributes.type );
	if ( dataElement.attributes.mw ) {
		el.setAttribute( 'data-mw', JSON.stringify( dataElement.attributes.mw ) );
	}

	var els = [ el ];
	if ( dataElement.attributes.type === 'mw:Includes/IncludeOnly' ) {
		// includeonly nodes have an implicit closing tag
		els = els.concat( ve.dm.MWIncludesNode.static.toDomElements( {
			type: 'mwIncludes',
			attributes: {
				type: 'mw:Includes/IncludeOnly/End'
			}
		}, doc, converter ) );
	}

	return els;
};

/* Methods */

ve.dm.MWIncludesNode.prototype.getWikitextTag = function () {
	var map = {
		'mw:Includes/NoInclude': '<noinclude>',
		'mw:Includes/NoInclude/End': '</noinclude>',
		'mw:Includes/OnlyInclude': '<onlyinclude>',
		'mw:Includes/OnlyInclude/End': '</onlyinclude>',
		'mw:Includes/IncludeOnly': '<includeonly>…</includeonly>'
	};
	return map[ this.getAttribute( 'type' ) ];
};

/* Registration */

ve.dm.modelRegistry.register( ve.dm.MWIncludesNode );
