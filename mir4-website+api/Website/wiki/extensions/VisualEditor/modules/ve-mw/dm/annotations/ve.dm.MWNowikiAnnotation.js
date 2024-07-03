/*!
 * VisualEditor DataModel MWNowikiAnnotation class.
 *
 * @copyright See AUTHORS.txt
 * @license The MIT License (MIT); see LICENSE.txt
 */

/**
 * DataModel MediaWiki nowiki annotation
 *
 * Represents `<nowiki>` tags (in HTML as `<span typeof="mw:Nowiki">`) and unwraps them when they change
 * so as to retrigger Parsoid's escaping mechanism.
 *
 * @class
 * @extends ve.dm.Annotation
 * @constructor
 * @param {Object} element [description]
 */
ve.dm.MWNowikiAnnotation = function VeDmMWNowikiAnnotation() {
	// Parent constructor
	ve.dm.MWNowikiAnnotation.super.apply( this, arguments );
};

/* Inheritance */

OO.inheritClass( ve.dm.MWNowikiAnnotation, ve.dm.Annotation );

/* Static Properties */

ve.dm.MWNowikiAnnotation.static.name = 'mwNowiki';

ve.dm.MWNowikiAnnotation.static.matchRdfaTypes = [ 'mw:Nowiki' ];

ve.dm.MWNowikiAnnotation.static.toDomElements = function ( dataElement, doc, converter, childDomElements ) {
	var originalDomElements = converter.getStore().value( dataElement.originalDomElementsHash ),
		originalChildren = originalDomElements && originalDomElements[ 0 ] && originalDomElements[ 0 ].childNodes,
		contentsChanged = false,
		domElement = document.createElement( 'span' );

	// Determine whether the contents changed
	if ( !originalChildren || childDomElements.length !== originalChildren.length ) {
		contentsChanged = true;
	} else {
		for ( var i = 0, len = originalChildren.length; i < len; i++ ) {
			if ( !originalChildren[ i ].isEqualNode( childDomElements[ i ] ) ) {
				contentsChanged = true;
				break;
			}
		}
	}

	// If the contents changed, unwrap, otherwise, restore
	if ( contentsChanged ) {
		return [];
	}
	domElement.setAttribute( 'typeof', 'mw:Nowiki' );
	return [ domElement ];
};

/* Registration */

ve.dm.modelRegistry.register( ve.dm.MWNowikiAnnotation );
