'use strict';

/*!
 * VisualEditor UserInterface MWReferenceSearchWidget class.
 *
 * @copyright 2011-2018 VisualEditor Team's Cite sub-team and others; see AUTHORS.txt
 * @license MIT
 */

/**
 * Creates an ve.ui.MWReferenceSearchWidget object.
 *
 * @constructor
 * @extends OO.ui.SearchWidget
 * @param {Object} [config] Configuration options
 */
ve.ui.MWReferenceSearchWidget = function VeUiMWReferenceSearchWidget( config ) {
	// Configuration initialization
	config = ve.extendObject( {
		placeholder: ve.msg( 'cite-ve-reference-input-placeholder' )
	}, config );

	// Parent constructor
	ve.ui.MWReferenceSearchWidget.super.call( this, config );

	// Properties
	this.index = [];
	this.indexEmpty = true;
	this.built = false;

	// Initialization
	this.$element.addClass( 've-ui-mwReferenceSearchWidget' );
};

/* Inheritance */

OO.inheritClass( ve.ui.MWReferenceSearchWidget, OO.ui.SearchWidget );

/* Methods */

/**
 * Handle query change events.
 *
 * @param {string} value New value
 */
ve.ui.MWReferenceSearchWidget.prototype.onQueryChange = function () {
	// Parent method
	ve.ui.MWReferenceSearchWidget.super.prototype.onQueryChange.call( this );

	// Populate
	this.addResults();
};

/**
 * Set the internal list and check if it contains any references
 *
 * @param {ve.dm.InternalList} internalList Internal list
 */
ve.ui.MWReferenceSearchWidget.prototype.setInternalList = function ( internalList ) {
	if ( this.results.findSelectedItem() ) {
		this.results.findSelectedItem().setSelected( false );
	}

	this.internalList = internalList;
	this.internalList.connect( this, { update: 'onInternalListUpdate' } );
	this.internalList.getListNode().connect( this, { update: 'onListNodeUpdate' } );

	const groups = internalList.getNodeGroups();
	for ( const groupName in groups ) {
		if ( groupName.indexOf( 'mwReference/' ) === 0 && groups[ groupName ].indexOrder.length ) {
			this.indexEmpty = false;
			return;
		}
	}
	this.indexEmpty = true;
};

/**
 * Handle the updating of the InternalList object.
 *
 * This will occur after a document transaction.
 *
 * @param {string[]} groupsChanged A list of groups which have changed in this transaction
 */
ve.ui.MWReferenceSearchWidget.prototype.onInternalListUpdate = function ( groupsChanged ) {
	if ( groupsChanged.some( function ( groupName ) {
		return groupName.indexOf( 'mwReference/' ) === 0;
	} ) ) {
		this.built = false;
	}
};

/**
 * Handle the updating of the InternalListNode.
 *
 * This will occur after changes to any InternalItemNode.
 */
ve.ui.MWReferenceSearchWidget.prototype.onListNodeUpdate = function () {
	this.built = false;
};

/**
 * Build a searchable index of references.
 */
ve.ui.MWReferenceSearchWidget.prototype.buildIndex = function () {
	const groups = this.internalList.getNodeGroups();

	if ( this.built ) {
		return;
	}

	this.index = [];
	const groupNames = Object.keys( groups ).sort();

	for ( let i = 0; i < groupNames.length; i++ ) {
		const groupName = groupNames[ i ];
		if ( groupName.indexOf( 'mwReference/' ) !== 0 ) {
			continue;
		}
		const group = groups[ groupName ];
		const firstNodes = group.firstNodes;
		const indexOrder = group.indexOrder;

		let n = 0;
		for ( let j = 0; j < indexOrder.length; j++ ) {
			const refNode = firstNodes[ indexOrder[ j ] ];
			// Exclude placeholder references
			if ( refNode.getAttribute( 'placeholder' ) ) {
				continue;
			}
			// Only increment counter for real references
			n++;
			const refModel = ve.dm.MWReferenceModel.static.newFromReferenceNode( refNode );
			const itemNode = this.internalList.getItemNode( refModel.getListIndex() );

			const refGroup = refModel.getGroup();
			const citation = ( refGroup ? refGroup + ' ' : '' ) + n;
			// Use [\s\S]* instead of .* to catch esoteric whitespace (T263698)
			const matches = refModel.getListKey().match( /^literal\/([\s\S]*)$/ );
			const name = matches && matches[ 1 ] || '';

			// TODO: At some point we need to make sure this text is updated in
			// case the view node is still rendering. This shouldn't happen because
			// all references are supposed to be in the store and therefore are
			// immediately rendered, but we shouldn't trust that on principle to
			// account for edge cases.

			let $element;
			// Make visible text, citation and reference name searchable
			let text = citation + ' ' + name;
			if ( itemNode.length ) {
				$element = new ve.ui.MWPreviewElement( itemNode, { useView: true } ).$element;
				text = $element.text().toLowerCase() + ' ' + text;
				// Make URLs searchable
				$element.find( 'a[href]' ).each( function () {
					text += ' ' + this.getAttribute( 'href' );
				} );
			} else {
				$element = $( '<span>' )
					.addClass( 've-ce-mwReferencesListNode-muted' )
					.text( ve.msg( 'cite-ve-referenceslist-missingref-in-list' ) );
			}

			this.index.push( {
				$element: $element,
				text: text,
				reference: refModel,
				citation: citation,
				name: name
			} );
		}
	}

	// Re-populate
	this.onQueryChange();

	this.built = true;
};

/**
 * Check whether buildIndex will create an empty index based on the current internalList.
 *
 * @return {boolean} Index is empty
 */
ve.ui.MWReferenceSearchWidget.prototype.isIndexEmpty = function () {
	return this.indexEmpty;
};

ve.ui.MWReferenceSearchWidget.prototype.addResults = function () {
	const query = this.query.getValue().trim().toLowerCase();
	const items = [];

	for ( let i = 0; i < this.index.length; i++ ) {
		const item = this.index[ i ];
		if ( item.text.indexOf( query ) >= 0 ) {
			const $citation = $( '<div>' )
				.addClass( 've-ui-mwReferenceSearchWidget-citation' )
				.text( '[' + item.citation + ']' );
			const $name = $( '<div>' )
				.addClass( 've-ui-mwReferenceSearchWidget-name' )
				.toggleClass( 've-ui-mwReferenceSearchWidget-name-autogenerated', /^:\d+$/.test( item.name ) )
				.text( item.name );
			items.push(
				new ve.ui.MWReferenceResultWidget( {
					data: item.reference,
					label: $citation.add( $name ).add( item.$element )
				} )
			);
		}
	}

	this.results.addItems( items );
};
