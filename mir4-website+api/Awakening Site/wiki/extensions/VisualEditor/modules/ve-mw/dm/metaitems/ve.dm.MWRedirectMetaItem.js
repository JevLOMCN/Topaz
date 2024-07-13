/*!
 * VisualEditor DataModel MWRedirectMetaItem class.
 *
 * @copyright See AUTHORS.txt
 * @license The MIT License (MIT); see LICENSE.txt
 */

/**
 * DataModel redirect meta item.
 *
 * @class
 * @extends ve.dm.MetaItem
 * @constructor
 * @param {Object} element Reference to element in meta-linmod
 */
ve.dm.MWRedirectMetaItem = function VeDmMWRedirectMetaItem() {
	// Parent constructor
	ve.dm.MWRedirectMetaItem.super.apply( this, arguments );
};

/* Inheritance */

OO.inheritClass( ve.dm.MWRedirectMetaItem, ve.dm.MetaItem );

/* Static Properties */

ve.dm.MWRedirectMetaItem.static.name = 'mwRedirect';

ve.dm.MWRedirectMetaItem.static.group = 'mwRedirect';

ve.dm.MWRedirectMetaItem.static.matchTagNames = [ 'link' ];

ve.dm.MWRedirectMetaItem.static.matchRdfaTypes = [ 'mw:PageProp/redirect' ];

ve.dm.MWRedirectMetaItem.static.toDataElement = function ( domElements, converter ) {
	// HACK piggy-back on MWInternalLinkAnnotation's ./ stripping logic
	var linkData = ve.dm.MWInternalLinkAnnotation.static.toDataElement( domElements, converter );
	linkData.type = this.name;
	return linkData;
};

ve.dm.MWRedirectMetaItem.static.toDomElements = function ( dataElement, doc, converter ) {
	var domElement;
	var href = ve.dm.MWInternalLinkAnnotation.static.getHref( dataElement );
	if ( converter.isForPreview() ) {
		// TODO: Move this a DM utility that doesn't use jQuery internally
		domElement = ve.init.mw.ArticleTarget.static.buildRedirectMsg( dataElement.attributes.title )[ 0 ];
	} else {
		domElement = doc.createElement( 'link' );
		domElement.setAttribute( 'rel', 'mw:PageProp/redirect' );
		// HACK piggy-back on MWInternalLinkAnnotation's logic
		domElement.setAttribute( 'href', href );
	}
	return [ domElement ];
};

ve.dm.MWRedirectMetaItem.static.describeChange = function ( key, change ) {
	if ( key === 'title' ) {
		return ve.htmlMsg( 'visualeditor-changedesc-mwredirect', this.wrapText( 'del', change.from ), this.wrapText( 'ins', change.to ) );
	}
	return null;
};

/* Registration */

ve.dm.modelRegistry.register( ve.dm.MWRedirectMetaItem );

ve.ui.metaListDiffRegistry.register( 'mwRedirect', function ( diffElement, diffQueue, documentNode, documentSpacerNode ) {
	diffQueue = diffElement.processQueue( diffQueue );

	if ( !diffQueue.length ) {
		return;
	}

	var redirects = document.createElement( 'div' );
	diffElement.renderQueue(
		diffQueue,
		redirects,
		documentSpacerNode
	);
	documentNode.insertBefore( redirects, documentNode.firstChild );
} );
