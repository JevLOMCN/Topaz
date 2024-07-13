'use strict';

/*!
 * VisualEditor ContentEditable MWReferencesListNode class.
 *
 * @copyright 2011-2018 VisualEditor Team's Cite sub-team and others; see AUTHORS.txt
 * @license MIT
 */

/**
 * ContentEditable MediaWiki references list node.
 *
 * @constructor
 * @extends ve.ce.LeafNode
 * @mixes ve.ce.FocusableNode
 * @param {ve.dm.MWReferencesListNode} model Model to observe
 * @param {Object} [config] Configuration options
 */
ve.ce.MWReferencesListNode = function VeCeMWReferencesListNode() {
	// Parent constructor
	ve.ce.MWReferencesListNode.super.apply( this, arguments );

	// Mixin constructors
	ve.ce.FocusableNode.call( this );

	// Properties
	this.internalList = null;
	this.listNode = null;
	this.modified = false;

	// DOM changes
	this.$element.addClass( 've-ce-mwReferencesListNode' );
	this.$reflist = $( '<ol>' ).addClass( 'mw-references references' );
	this.$originalRefList = null;
	this.$refmsg = $( '<p>' )
		.addClass( 've-ce-mwReferencesListNode-muted' );

	// Events
	this.getModel().connect( this, { attributeChange: 'onAttributeChange' } );

	this.updateDebounced = ve.debounce( this.update.bind( this ) );

	// Initialization
	// Rendering the reference list can be slow, so do it in an idle callback
	// (i.e. after the editor has finished loading). Previously we used the
	// Parsoid DOM rendering for the first paint, and updated only when references
	// were modified, but this means the reference list is out of sync with the
	// model for features such as T54750.
	mw.requestIdleCallback( this.update.bind( this ) );
};

/* Inheritance */

OO.inheritClass( ve.ce.MWReferencesListNode, ve.ce.LeafNode );

OO.mixinClass( ve.ce.MWReferencesListNode, ve.ce.FocusableNode );

/* Static Properties */

ve.ce.MWReferencesListNode.static.name = 'mwReferencesList';

ve.ce.MWReferencesListNode.static.tagName = 'div';

ve.ce.MWReferencesListNode.static.primaryCommandName = 'referencesList';

/* Static Methods */

/**
 * @override
 * @see ve.ce.LeafNode
 */
ve.ce.MWReferencesListNode.static.getDescription = function ( model ) {
	return model.getAttribute( 'refGroup' );
};

/**
 * @override
 * @see ve.ce.FocusableNode
 */
ve.ce.MWReferencesListNode.prototype.getExtraHighlightClasses = function () {
	return ve.ce.FocusableNode.prototype
		.getExtraHighlightClasses.apply( this, arguments )
		.concat( [ 've-ce-mwReferencesListNode-highlight' ] );
};

/* Methods */

/**
 * Handle setup events.
 */
ve.ce.MWReferencesListNode.prototype.onSetup = function () {
	this.internalList = this.getModel().getDocument().getInternalList();
	this.listNode = this.internalList.getListNode();

	this.internalList.connect( this, { update: 'onInternalListUpdate' } );
	this.listNode.connect( this, { update: 'onListNodeUpdate' } );

	// Parent method
	ve.ce.MWReferencesListNode.super.prototype.onSetup.call( this );
};

/**
 * Handle teardown events.
 */
ve.ce.MWReferencesListNode.prototype.onTeardown = function () {
	// Parent method
	ve.ce.MWReferencesListNode.super.prototype.onTeardown.call( this );

	if ( !this.listNode ) {
		return;
	}

	this.internalList.disconnect( this, { update: 'onInternalListUpdate' } );
	this.listNode.disconnect( this, { update: 'onListNodeUpdate' } );

	this.internalList = null;
	this.listNode = null;
};

/**
 * Handle the updating of the InternalList object.
 *
 * This will occur after a document transaction.
 *
 * @param {string[]} groupsChanged A list of groups which have changed in this transaction
 */
ve.ce.MWReferencesListNode.prototype.onInternalListUpdate = function ( groupsChanged ) {
	// Only update if this group has been changed
	if ( groupsChanged.indexOf( this.getModel().getAttribute( 'listGroup' ) ) !== -1 ) {
		this.modified = true;
		this.updateDebounced();
	}
};

/**
 * Rerender when the 'listGroup' attribute changes in the model.
 *
 * @param {string} key Attribute key
 * @param {string} from Old value
 * @param {string} to New value
 */
ve.ce.MWReferencesListNode.prototype.onAttributeChange = function ( key ) {
	switch ( key ) {
		case 'listGroup':
			this.updateDebounced();
			this.modified = true;
			break;
		case 'isResponsive':
			this.updateClasses();
			break;
	}
};

/**
 * Handle the updating of the InternalListNode.
 *
 * This will occur after changes to any InternalItemNode.
 */
ve.ce.MWReferencesListNode.prototype.onListNodeUpdate = function () {
	// When the list node updates we're not sure which list group the item
	// belonged to so we always update
	// TODO: Only re-render the reference which has been edited
	this.updateDebounced();
};

/**
 * Update the references list.
 */
ve.ce.MWReferencesListNode.prototype.update = function () {
	const model = this.getModel();

	// Check the node hasn't been destroyed, as this method is debounced.
	if ( !model ) {
		return;
	}

	const internalList = model.getDocument().internalList;
	const refGroup = model.getAttribute( 'refGroup' );
	const listGroup = model.getAttribute( 'listGroup' );
	const nodes = internalList.getNodeGroup( listGroup );
	const hasModelReferences = !!( nodes && nodes.indexOrder.length );

	let emptyText;
	if ( refGroup !== '' ) {
		emptyText = ve.msg( 'cite-ve-referenceslist-isempty', refGroup );
	} else {
		emptyText = ve.msg( 'cite-ve-referenceslist-isempty-default' );
	}

	// Use the Parsoid-provided DOM if:
	//
	// * There are no references in the model
	// * There have been no changes to the references in the model (!this.modified)
	//
	// In practice this is for he.wiki where references are template-generated (T187495)
	if (
		!hasModelReferences &&
		!this.modified &&
		model.getElement().originalDomElementsHash
	) {
		// Create a copy when importing to the main document, as extensions may
		// modify DOM nodes in the main doc.
		this.$originalRefList = $( ve.copyDomElements( model.getStore().value(
			model.getElement().originalDomElementsHash
		), document ) );
		if ( this.$originalRefList.find( 'li' ).length ) {
			this.$element.append( this.$originalRefList );
		} else {
			this.$refmsg.text( emptyText );
			this.$element.append( this.$refmsg );
		}
		return;
	}

	if ( this.$originalRefList ) {
		this.$originalRefList.remove();
		this.$originalRefList = null;
	}
	this.$reflist.detach().empty().attr( 'data-mw-group', refGroup || null );
	this.$refmsg.detach();

	if ( !hasModelReferences ) {
		this.$refmsg.text( emptyText );
		this.$element.append( this.$refmsg );
	} else {
		nodes.indexOrder.forEach( function ( index ) {
			const firstNode = nodes.firstNodes[ index ];

			const key = internalList.keys[ index ];
			let keyedNodes = nodes.keyedNodes[ key ];
			keyedNodes = keyedNodes.filter( function ( node ) {
				// Exclude placeholders and references defined inside the references list node
				return !node.getAttribute( 'placeholder' ) &&
					!node.findParent( ve.dm.MWReferencesListNode );
			} );

			if ( !keyedNodes.length ) {
				return;
			}

			const $li = $( '<li>' )
				.append( this.renderBacklinks( keyedNodes, refGroup ), ' ' );

			// Generate reference HTML from first item in key
			const modelNode = internalList.getItemNode( firstNode.getAttribute( 'listIndex' ) );
			if ( modelNode && modelNode.length ) {
				const refPreview = new ve.ui.MWPreviewElement( modelNode, { useView: true } );
				$li.append(
					$( '<span>' )
						.addClass( 'reference-text' )
						.append( refPreview.$element )
				);
			} else {
				$li.append(
					$( '<span>' )
						.addClass( 've-ce-mwReferencesListNode-muted' )
						.text( ve.msg( 'cite-ve-referenceslist-missingref-in-list' ) )
				).addClass( 've-ce-mwReferencesListNode-missingRef' );
			}

			if ( this.getRoot() ) {
				const surface = this.getRoot().getSurface().getSurface();
				$li.on( 'mousedown', function ( e ) {
					if ( ve.isUnmodifiedLeftClick( e ) && modelNode && modelNode.length ) {
						const items = ve.ui.contextItemFactory.getRelatedItems( [ firstNode ] )
							.filter( ( item ) => item.name !== 'mobileActions' );
						if ( items.length ) {
							const contextItem = ve.ui.contextItemFactory.lookup( items[ 0 ].name );
							if ( contextItem ) {
								const command = surface.commandRegistry
									.lookup( contextItem.static.commandName );
								if ( command ) {
									const fragmentArgs = {
										fragment: surface.getModel()
											.getLinearFragment( firstNode.getOuterRange(), true ),
										selectFragmentOnClose: false
									};
									const newArgs = ve.copy( command.args );
									if ( command.name === 'reference' ) {
										newArgs[ 1 ] = fragmentArgs;
									} else {
										ve.extendObject( newArgs[ 0 ], fragmentArgs );
									}
									command.execute( surface, newArgs );
								}
							}
						}
					}
					e.preventDefault();
				} );
			}

			this.$reflist.append( $li );
		}.bind( this ) );

		this.updateClasses();
		this.$element.append( this.$reflist );
	}
};

/**
 * Update ref list classes.
 *
 * Currently used to set responsive layout
 */
ve.ce.MWReferencesListNode.prototype.updateClasses = function () {
	const isResponsive = this.getModel().getAttribute( 'isResponsive' );

	this.$element
		.toggleClass( 'mw-references-wrap', isResponsive )
		.toggleClass( 'mw-references-columns', isResponsive && this.$reflist.children().length > 10 );
};

/**
 * Build markers for backlinks
 *
 * @param {ve.dm.Node[]} keyedNodes A list of ref nodes linked to a reference list item
 * @param {string} refGroup Reference group name
 * @return {jQuery} Element containing backlinks
 */
ve.ce.MWReferencesListNode.prototype.renderBacklinks = function ( keyedNodes, refGroup ) {
	if ( keyedNodes.length === 1 ) {
		return $( '<a>' )
			.attr( 'rel', 'mw:referencedBy' )
			.attr( 'data-mw-group', refGroup || null )
			.append( $( '<span>' ).addClass( 'mw-linkback-text' ).text( '↑ ' ) );
	}

	// named reference with multiple usages
	const $refSpan = $( '<span>' ).attr( 'rel', 'mw:referencedBy' );
	for ( let i = 0; i < keyedNodes.length; i++ ) {
		$( '<a>' )
			.attr( 'data-mw-group', refGroup || null )
			.append( $( '<span>' ).addClass( 'mw-linkback-text' ).text( ( i + 1 ) + ' ' ) )
			.appendTo( $refSpan );
	}
	return $refSpan;
};

/* Registration */

ve.ce.nodeFactory.register( ve.ce.MWReferencesListNode );
