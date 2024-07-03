/*!
 * VisualEditor DataModel MWCategoryMetaItem class.
 *
 * @copyright See AUTHORS.txt
 * @license The MIT License (MIT); see LICENSE.txt
 */

/**
 * DataModel category meta item.
 *
 * @class
 * @extends ve.dm.MetaItem
 * @constructor
 * @param {Object} element Reference to element in meta-linmod
 */
ve.dm.MWCategoryMetaItem = function VeDmMWCategoryMetaItem() {
	// Parent constructor
	ve.dm.MWCategoryMetaItem.super.apply( this, arguments );
};

/* Inheritance */

OO.inheritClass( ve.dm.MWCategoryMetaItem, ve.dm.MetaItem );

/* Static Properties */

ve.dm.MWCategoryMetaItem.static.name = 'mwCategory';

ve.dm.MWCategoryMetaItem.static.group = 'mwCategory';

ve.dm.MWCategoryMetaItem.static.matchTagNames = [ 'link' ];

ve.dm.MWCategoryMetaItem.static.matchRdfaTypes = [ 'mw:PageProp/Category' ];

ve.dm.MWCategoryMetaItem.static.toDataElement = function ( domElements ) {
	// Parsoid: LinkHandlerUtils::serializeAsWikiLink
	var href = domElements[ 0 ].getAttribute( 'href' ),
		titleAndFragment = href.match( /^(.*?)(?:#(.*))?\s*$/ );
	return {
		type: this.name,
		attributes: {
			category: mw.libs.ve.parseParsoidResourceName( titleAndFragment[ 1 ] ).title,
			sortkey: titleAndFragment[ 2 ] ? decodeURIComponent( titleAndFragment[ 2 ] ) : ''
		}
	};
};

ve.dm.MWCategoryMetaItem.static.toDomElements = function ( dataElement, doc, converter ) {
	var domElement;
	var category = dataElement.attributes.category || '';
	if ( converter.isForPreview() ) {
		domElement = doc.createElement( 'a' );
		var title = mw.Title.newFromText( category );
		domElement.setAttribute( 'href', title.getUrl() );
		domElement.appendChild( doc.createTextNode( title.getMainText() ) );
	} else {
		domElement = doc.createElement( 'link' );
		var sortkey = dataElement.attributes.sortkey || '';
		domElement.setAttribute( 'rel', 'mw:PageProp/Category' );

		// Parsoid: WikiLinkHandler::renderCategory
		var href = mw.libs.ve.encodeParsoidResourceName( category );
		if ( sortkey !== '' ) {
			href += '#' + sortkey.replace( /[%? [\]#|<>]/g, function ( match ) {
				return encodeURIComponent( match );
			} );
		}

		domElement.setAttribute( 'href', href );
	}
	return [ domElement ];
};

ve.dm.MWCategoryMetaItem.static.isDiffComparable = function ( element, other ) {
	// Don't try to compare different categories. Even fixing a typo in a category name
	// results in one category being removed and another added, which we shoud show.
	return element.type === other.type && element.attributes.category === other.attributes.category;
};

ve.dm.MWCategoryMetaItem.static.describeChange = function ( key, change ) {
	if ( key === 'sortkey' ) {
		if ( !change.from ) {
			return ve.htmlMsg( 'visualeditor-changedesc-mwcategory-sortkey-set', this.wrapText( 'ins', change.to ) );
		} else if ( !change.to ) {
			return ve.htmlMsg( 'visualeditor-changedesc-mwcategory-sortkey-unset', this.wrapText( 'del', change.from ) );
		} else {
			return ve.htmlMsg( 'visualeditor-changedesc-mwcategory-sortkey-changed',
				this.wrapText( 'del', change.from ),
				this.wrapText( 'ins', change.to )
			);
		}
	}

	// Parent method
	return ve.dm.MWCategoryMetaItem.super.static.describeChange.apply( this, arguments );
};

/* Registration */

ve.dm.modelRegistry.register( ve.dm.MWCategoryMetaItem );

ve.ui.metaListDiffRegistry.register( 'mwCategory', function ( diffElement, diffQueue, documentNode /* , documentSpacerNode */ ) {
	diffQueue = diffElement.processQueue( diffQueue );

	if ( !diffQueue.length ) {
		return;
	}

	var catLinks = document.createElement( 'div' );
	catLinks.setAttribute( 'class', 'catlinks' );

	var headerLink = document.createElement( 'a' );
	headerLink.appendChild( document.createTextNode( ve.msg( 'pagecategories', diffQueue.length ) ) );
	headerLink.setAttribute( 'href', ve.msg( 'pagecategorieslink' ) );

	catLinks.appendChild( headerLink );
	catLinks.appendChild( document.createTextNode( ve.msg( 'colon-separator' ) ) );

	var list = document.createElement( 'ul' );
	catLinks.appendChild( list );

	var catSpacerNode = document.createElement( 'span' );
	catSpacerNode.appendChild( document.createTextNode( ' … ' ) );

	// Wrap each item in the queue in an <li>
	diffQueue.forEach( function ( diffItem ) {
		var listItem = document.createElement( 'li' );
		diffElement.renderQueue(
			[ diffItem ],
			listItem, catSpacerNode
		);
		list.appendChild( listItem );
	} );

	documentNode.appendChild( catLinks );
} );
