/*!
 * VisualEditor DataModel MWDefaultSortMetaItem class.
 *
 * @copyright See AUTHORS.txt
 * @license The MIT License (MIT); see LICENSE.txt
 */

/**
 * DataModel category default sort meta item.
 *
 * @class
 * @extends ve.dm.MetaItem
 * @constructor
 * @param {Object} element Reference to element in meta-linmod
 */
ve.dm.MWDefaultSortMetaItem = function VeDmMWDefaultSortMetaItem() {
	// Parent constructor
	ve.dm.MWDefaultSortMetaItem.super.apply( this, arguments );
};

/* Inheritance */

OO.inheritClass( ve.dm.MWDefaultSortMetaItem, ve.dm.MetaItem );

/* Static Properties */

ve.dm.MWDefaultSortMetaItem.static.name = 'mwDefaultSort';

ve.dm.MWDefaultSortMetaItem.static.group = 'mwDefaultSort';

ve.dm.MWDefaultSortMetaItem.static.matchTagNames = [ 'span' ];

ve.dm.MWDefaultSortMetaItem.static.matchRdfaTypes = [ 'mw:Transclusion' ];

ve.dm.MWDefaultSortMetaItem.static.matchFunction = function ( domElement ) {
	var mwDataJSON = domElement.getAttribute( 'data-mw' ),
		mwData = mwDataJSON ? JSON.parse( mwDataJSON ) : {};
	return ve.getProp( mwData, 'parts', '0', 'template', 'target', 'function' ) === 'defaultsort';
};

ve.dm.MWDefaultSortMetaItem.static.toDataElement = function ( domElements ) {
	var mwDataJSON = domElements[ 0 ].getAttribute( 'data-mw' ),
		mwData = mwDataJSON ? JSON.parse( mwDataJSON ) : {},
		input = ve.getProp( mwData, 'parts', '0', 'template', 'target', 'wt' ),
		prefix, sortKey;
	if ( input ) {
		prefix = input.split( ':' )[ 0 ];
		sortKey = input.slice( prefix.length + 1 );
	}
	return {
		type: this.name,
		attributes: {
			prefix: prefix,
			sortkey: sortKey
		}
	};
};

ve.dm.MWDefaultSortMetaItem.static.toDomElements = function ( dataElement, doc ) {
	var prefix = dataElement.attributes.prefix ||
			mw.config.get( 'wgVisualEditorConfig' ).defaultSortPrefix,
		sortKey = dataElement.attributes.sortkey || '',
		mwData = {
			parts: [
				{
					template: {
						target: {
							wt: prefix + ':' + sortKey,
							function: 'defaultsort'
						}
					}
				}
			]
		};

	var span = doc.createElement( 'span' );
	span.setAttribute( 'typeof', 'mw:Transclusion' );
	span.setAttribute( 'data-mw', JSON.stringify( mwData ) );
	return [ span ];
};

/* Registration */

ve.dm.modelRegistry.register( ve.dm.MWDefaultSortMetaItem );
