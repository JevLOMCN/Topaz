'use strict';

/*!
 * VisualEditor MWCitationNeededContextItem class.
 *
 * @copyright 2011-2018 VisualEditor Team's Cite sub-team and others; see AUTHORS.txt
 * @license MIT
 */

/**
 * Context item for a citation needed template.
 *
 * @constructor
 * @extends ve.ui.MWDefinedTransclusionContextItem
 * @param {ve.ui.LinearContext} context Context the item is in
 * @param {ve.dm.Model} model Model the item is related to
 * @param {Object} [config]
 */
ve.ui.MWCitationNeededContextItem = function VeUiMWCitationNeededContextItem() {
	// Parent constructor
	ve.ui.MWCitationNeededContextItem.super.apply( this, arguments );

	this.addButton = new OO.ui.ButtonWidget( {
		label: ve.msg( 'cite-ve-citationneeded-button' ),
		flags: [ 'progressive' ]
	} ).on( 'click', this.onAddClick.bind( this ) );

	// Remove progressive flag from edit, as addButton is now the
	// main progressive action in the context.
	this.editButton.setFlags( { progressive: false } );

	// Initialization
	this.$element.addClass( 've-ui-mwCitationNeededContextItem' );
};

/* Inheritance */

OO.inheritClass( ve.ui.MWCitationNeededContextItem, ve.ui.MWDefinedTransclusionContextItem );

/* Static Properties */

ve.ui.MWCitationNeededContextItem.static.name = 'citationNeeded';

ve.ui.MWCitationNeededContextItem.static.icon = 'quotes';

ve.ui.MWCitationNeededContextItem.static.label = OO.ui.deferMsg( 'cite-ve-citationneeded-title' );

/* Methods */

ve.ui.MWCitationNeededContextItem.prototype.onAddClick = function () {
	const contextItem = this;
	const surface = this.context.getSurface();
	const encapsulatedWikitext = this.getCanonicalParam( 'encapsulate' );

	let promise;
	if ( encapsulatedWikitext ) {
		this.addButton.setDisabled( true );
		promise = ve.init.target
			.parseWikitextFragment( encapsulatedWikitext, false, this.model.getDocument() )
			.then( ( response ) => {

				if ( ve.getProp( response, 'visualeditor', 'result' ) !== 'success' ) {
					return ve.createDeferred().reject().promise();
				}

				const dmDoc = ve.ui.MWWikitextStringTransferHandler.static
					.createDocumentFromParsoidHtml(
						response.visualeditor.content,
						surface.getModel().getDocument()
					);
				const nodes = dmDoc.getDocumentNode().children.filter( function ( node ) {
					return !node.isInternal();
				} );
				let range;

				// Unwrap single content branch nodes to match internal copy/paste behaviour
				// (which wouldn't put the open and close tags in the clipboard to begin with).
				if (
					nodes.length === 1 &&
					nodes[ 0 ].canContainContent()
				) {
					range = nodes[ 0 ].getRange();
				}

				surface.getModel().pushStaging();
				surface.getModel().getFragment()
					.insertDocument( dmDoc, range ).collapseToEnd().select();
				return true;
			} );
		promise.always( function () {
			contextItem.addButton.setDisabled( false );
		} );
	} else {
		promise = ve.createDeferred().resolve( false ).promise();
	}

	// TODO: This assumes Citoid is installed...
	const action = ve.ui.actionFactory.create( 'citoid', surface );
	promise.then( function ( inStaging ) {
		action.open( true, undefined, inStaging );
	} );
	ve.track( 'activity.' + this.constructor.static.name, { action: 'context-add-citation' } );
};

/**
 * @override
 */
ve.ui.MWCitationNeededContextItem.prototype.renderBody = function () {
	const date = this.getCanonicalParam( 'date' );
	let description = ve.msg( 'cite-ve-citationneeded-description' );

	if ( date ) {
		description += ve.msg( 'word-separator' ) + ve.msg( 'parentheses', date );
	}

	this.$body.empty();
	this.$body.append( $( '<p>' ).addClass( 've-ui-mwCitationNeededContextItem-description' ).text( description ) );

	const reason = this.getCanonicalParam( 'reason' );
	if ( reason ) {
		this.$body.append(
			$( '<p>' ).addClass( 've-ui-mwCitationNeededContextItem-reason' ).append(
				document.createTextNode( ve.msg( 'cite-ve-citationneeded-reason' ) + ve.msg( 'word-separator' ) ),
				// TODO: reason could have HTML entities, but this is rare
				$( '<em>' ).text( reason )
			)
		);
	}
	this.$body.append( this.addButton.$element );
};

/* Registration */

ve.ui.contextItemFactory.register( ve.ui.MWCitationNeededContextItem );
