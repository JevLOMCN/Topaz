'use strict';

/*!
 * VisualEditor DataModel MWReferencesListNode class.
 *
 * @copyright 2011-2018 VisualEditor Team's Cite sub-team and others; see AUTHORS.txt
 * @license MIT
 */

/**
 * DataModel MediaWiki references list node.
 *
 * @constructor
 * @extends ve.dm.BranchNode
 * @mixes ve.dm.FocusableNode
 * @param {Object} [element] Reference to element in linear model
 * @param {ve.dm.Node[]} [children]
 */
ve.dm.MWReferencesListNode = function VeDmMWReferencesListNode() {
	// Parent constructor
	ve.dm.MWReferencesListNode.super.apply( this, arguments );

	// Mixin constructors
	ve.dm.FocusableNode.call( this );
};

/* Inheritance */

OO.inheritClass( ve.dm.MWReferencesListNode, ve.dm.BranchNode );

OO.mixinClass( ve.dm.MWReferencesListNode, ve.dm.FocusableNode );

/* Methods */

ve.dm.MWReferencesListNode.prototype.isEditable = function () {
	return !this.getAttribute( 'templateGenerated' );
};

/* Static members */

ve.dm.MWReferencesListNode.static.name = 'mwReferencesList';

ve.dm.MWReferencesListNode.static.handlesOwnChildren = true;

ve.dm.MWReferencesListNode.static.ignoreChildren = true;

ve.dm.MWReferencesListNode.static.matchTagNames = null;

ve.dm.MWReferencesListNode.static.matchRdfaTypes = [ 'mw:Extension/references', 'mw:Transclusion' ];

// Allow TemplateStyles in the matching element
ve.dm.MWReferencesListNode.static.allowedRdfaTypes = [ 'mw:Extension/templatestyles' ];

// e.g. with a {{reflist}} with TemplateStyles
ve.dm.MWReferencesListNode.static.enableAboutGrouping = true;

// This node has the same specificity as ve.dm.MWTranslcusionNode and only matches
// ahead of it because it is registered later (via a dependency in ResourceLoader)
// TODO: Make this less fragile.
ve.dm.MWReferencesListNode.static.matchFunction = function ( domElement ) {
	function hasTypeof( el, type ) {
		return ( el.getAttribute( 'typeof' ) || '' ).indexOf( type ) !== -1;
	}
	function isRefList( el ) {
		return el && el.nodeType === Node.ELEMENT_NODE && hasTypeof( el, 'mw:Extension/references' );
	}
	// If the template generated only a reference list, treat it as a ref list (T52769)
	return isRefList( domElement ) ||
		// A div-wrapped reference list
		( domElement.children.length === 1 && isRefList( domElement.children[ 0 ] ) ) ||
		// TemplateStyles, about-grouped to a div-wrapped reference list
		(
			hasTypeof( domElement, 'mw:Extension/templatestyles' ) &&
			domElement.hasAttribute( 'about' ) &&
			domElement.nextElementSibling &&
			domElement.nextElementSibling.getAttribute( 'about' ) === domElement.getAttribute( 'about' ) &&
			// A div-wrapped reference list
			domElement.nextElementSibling.children.length === 1 &&
			isRefList( domElement.nextElementSibling.children[ 0 ] )
			// TODO: We should probably check there aren't subsequent elements. This
			// and the above checks would be easier if the matchFunction was passed
			// all the elements in the about group.
		);
};

ve.dm.MWReferencesListNode.static.preserveHtmlAttributes = false;

ve.dm.MWReferencesListNode.static.toDataElement = function ( domElements, converter ) {
	const type = domElements[ 0 ].getAttribute( 'typeof' ) || '';

	let refListNode;
	// We may have matched a mw:Transclusion wrapping a reference list, so pull out the refListNode
	if ( type.indexOf( 'mw:Extension/references' ) !== -1 ) {
		refListNode = domElements[ 0 ];
	} else {
		refListNode = domElements[ 0 ].querySelector( '[typeof*="mw:Extension/references"]' ) ||
			// In the TemplateStyles case, the ref list is in the second element
			domElements[ 1 ].querySelector( '[typeof*="mw:Extension/references"]' );
	}

	const mwDataJSON = refListNode.getAttribute( 'data-mw' );
	const mwData = mwDataJSON ? JSON.parse( mwDataJSON ) : {};
	const refGroup = ve.getProp( mwData, 'attrs', 'group' ) || '';
	const responsiveAttr = ve.getProp( mwData, 'attrs', 'responsive' );
	const listGroup = 'mwReference/' + refGroup;
	const templateGenerated = type.indexOf( 'mw:Transclusion' ) !== -1;
	const isResponsiveDefault = mw.config.get( 'wgCiteResponsiveReferences' );

	let referencesListData = {
		type: this.name,
		attributes: {
			mw: mwData,
			originalMw: mwDataJSON,
			refGroup: refGroup,
			listGroup: listGroup,
			isResponsive: responsiveAttr !== undefined ? responsiveAttr !== '0' : isResponsiveDefault,
			templateGenerated: templateGenerated
		}
	};
	if ( mwData.body && mwData.body.html && !templateGenerated ) {
		// Process the nodes in .body.html as if they were this node's children
		// Don't process template-generated reflists, that mangles the content (T209493)
		const contentsDiv = domElements[ 0 ].ownerDocument.createElement( 'div' );
		contentsDiv.innerHTML = mwData.body.html;
		const contentsData = converter.getDataFromDomClean( contentsDiv );
		referencesListData = [ referencesListData ]
			.concat( contentsData )
			.concat( [ { type: '/' + this.name } ] );
	}
	return referencesListData;
};

ve.dm.MWReferencesListNode.static.toDomElements = function ( data, doc, converter ) {
	const isForParser = converter.isForParser();
	const dataElement = data[ 0 ];
	const attrs = dataElement.attributes;

	// If we are sending a template generated ref back to Parsoid, output it as a
	// template.  This works because the dataElement already has mw, originalMw
	// and originalDomIndex properties.
	if ( attrs.templateGenerated && isForParser ) {
		return ve.dm.MWTransclusionNode.static
			.toDomElements.call( this, dataElement, doc, converter );
	}

	let els;
	if ( !isForParser ) {
		// Output needs to be read so re-render
		const modelNode = ve.dm.nodeFactory.createFromElement( dataElement );
		// Build from original doc's internal list to get all refs (T186407)
		modelNode.setDocument( converter.originalDocInternalList.getDocument() );
		const viewNode = ve.ce.nodeFactory.createFromModel( modelNode );
		viewNode.modified = true;
		viewNode.update();
		els = [ doc.createElement( 'div' ) ];
		els[ 0 ].appendChild( viewNode.$reflist[ 0 ] );
		// Destroy the view node so it doesn't try to update the DOM node later
		// (e.g. updateDebounced)
		viewNode.destroy();
	} else if ( dataElement.originalDomElementsHash !== undefined ) {
		// If there's more than 1 element, preserve entire array, not just first element
		els = ve.copyDomElements(
			converter.getStore().value( dataElement.originalDomElementsHash ), doc );
	} else {
		els = [ doc.createElement( 'div' ) ];
	}

	const mwData = attrs.mw ? ve.copy( attrs.mw ) : {};

	mwData.name = 'references';

	if ( attrs.refGroup ) {
		ve.setProp( mwData, 'attrs', 'group', attrs.refGroup );
	} else if ( mwData.attrs ) {
		delete mwData.attrs.group;
	}

	const originalMw = attrs.originalMw;
	const originalMwData = originalMw && JSON.parse( originalMw );
	const originalResponsiveAttr = ve.getProp( originalMwData, 'attrs', 'responsive' );
	const isResponsiveDefault = mw.config.get( 'wgCiteResponsiveReferences' );

	if ( !(
		// The original "responsive" attribute hasn't had its meaning changed
		originalResponsiveAttr !== undefined && ( originalResponsiveAttr !== '0' ) === attrs.isResponsive
	) ) {
		if ( attrs.isResponsive !== isResponsiveDefault ) {
			ve.setProp( mwData, 'attrs', 'responsive', attrs.isResponsive ? '' : '0' );
		} else if ( mwData.attrs ) {
			delete mwData.attrs.responsive;
		}
	}

	if ( mwData.autoGenerated ) {
		// This was an autogenerated reflist. We need to check whether changes
		// have been made which make that no longer true. The reflist dialog
		// handles unsetting this if changes to the properties have been made.
		// Here we want to work out if it has been moved away from the end of
		// the document.
		// TODO: it would be better to do this without needing to fish through
		// the converter's linear data. Use the DM tree instead?
		let nextIndex = converter.documentData.indexOf( data[ data.length - 1 ] ) + 1;
		let nextElement;
		while ( ( nextElement = converter.documentData[ nextIndex ] ) ) {
			if ( nextElement.type[ 0 ] !== '/' ) {
				break;
			}
			nextIndex++;
		}
		if ( nextElement && nextElement.type !== 'internalList' ) {
			delete mwData.autoGenerated;
		}
	}

	const el = els[ 0 ];
	el.setAttribute( 'typeof', 'mw:Extension/references' );

	const contentsData = data.slice( 1, -1 );
	if ( contentsData.length > 2 ) {
		const wrapper = doc.createElement( 'div' );
		converter.getDomSubtreeFromData( data.slice( 1, -1 ), wrapper );
		const contentsHtml = wrapper.innerHTML; // Returns '' if wrapper is empty
		const originalHtml = ve.getProp( mwData, 'body', 'html' ) || '';
		const originalHtmlWrapper = doc.createElement( 'div' );
		originalHtmlWrapper.innerHTML = originalHtml;
		// Only set body.html if contentsHtml and originalHtml are actually different
		if ( !originalHtmlWrapper.isEqualNode( wrapper ) ) {
			ve.setProp( mwData, 'body', 'html', contentsHtml );
		}
	}

	// If mwData and originalMw are the same, use originalMw to prevent reserialization.
	// Reserialization has the potential to reorder keys and so change the DOM unnecessarily
	if ( originalMw && ve.compare( mwData, originalMwData ) ) {
		el.setAttribute( 'data-mw', originalMw );
	} else {
		el.setAttribute( 'data-mw', JSON.stringify( mwData ) );
	}

	return els;
};

ve.dm.MWReferencesListNode.static.describeChange = function ( key, change ) {
	if ( key === 'refGroup' ) {
		if ( !change.from ) {
			return ve.htmlMsg( 'cite-ve-changedesc-reflist-group-to', this.wrapText( 'ins', change.to ) );
		} else if ( !change.to ) {
			return ve.htmlMsg( 'cite-ve-changedesc-reflist-group-from', this.wrapText( 'del', change.from ) );
		} else {
			return ve.htmlMsg( 'cite-ve-changedesc-reflist-group-both', this.wrapText( 'del', change.from ), this.wrapText( 'ins', change.to ) );
		}
	}

	if ( key === 'isResponsive' ) {
		if ( change.from ) {
			return ve.msg( 'cite-ve-changedesc-reflist-responsive-unset' );
		}
		return ve.msg( 'cite-ve-changedesc-reflist-responsive-set' );
	}

	if ( key === 'originalMw' ) {
		return null;
	}

	return null;
};

ve.dm.MWReferencesListNode.static.getHashObject = function ( dataElement ) {
	return {
		type: dataElement.type,
		attributes: {
			refGroup: dataElement.attributes.refGroup,
			listGroup: dataElement.attributes.listGroup,
			isResponsive: dataElement.attributes.isResponsive,
			templateGenerated: dataElement.attributes.templateGenerated
		}
	};
};

/* Registration */

ve.dm.modelRegistry.register( ve.dm.MWReferencesListNode );
