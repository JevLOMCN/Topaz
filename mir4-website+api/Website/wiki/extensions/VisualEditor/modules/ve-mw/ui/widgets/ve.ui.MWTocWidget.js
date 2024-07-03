/*!
 * VisualEditor UserInterface MWTocWidget class.
 *
 * @copyright See AUTHORS.txt
 * @license The MIT License (MIT); see LICENSE.txt
 */

/**
 * Creates a ve.ui.MWTocWidget object.
 *
 * @class
 * @extends OO.ui.Widget
 *
 * @constructor
 * @param {ve.ui.Surface} surface
 * @param {Object} [config] Configuration options
 */
ve.ui.MWTocWidget = function VeUiMWTocWidget( surface, config ) {
	// Parent constructor
	ve.ui.MWTocWidget.super.call( this, config );

	// Properties
	this.surface = surface;
	this.doc = surface.getModel().getDocument();
	this.metaList = this.doc.getMetaList();
	// Topic level 0 lives inside of a toc item
	this.rootLength = 0;
	this.initialized = false;
	// Page settings cache
	this.mwTOCForce = false;
	this.mwTOCDisable = false;

	this.$tocList = $( '<ul>' );
	this.$element.addClass( 'toc ve-ui-mwTocWidget ve-ce-focusableNode' ).append(
		$( '<div>' ).addClass( 'toctitle' ).append(
			$( '<h2>' ).text( ve.msg( 'toc' ) )
		),
		this.$tocList
	).prop( 'contentEditable', 'false' );

	// Setup toggle link
	mw.hook( 'wikipage.content' ).fire( this.$element );

	// Events
	this.metaList.connect( this, {
		insert: 'onMetaListInsert',
		remove: 'onMetaListRemove'
	} );

	this.buildDebounced = ve.debounce( this.build.bind( this ) );

	this.initFromMetaList();
	this.build();
};

/* Inheritance */

OO.inheritClass( ve.ui.MWTocWidget, OO.ui.Widget );

/**
 * Bound to MetaList insert event to set TOC display options
 *
 * @param {ve.dm.MetaItem} metaItem
 */
ve.ui.MWTocWidget.prototype.onMetaListInsert = function ( metaItem ) {
	// Responsible for adding UI components
	if ( metaItem instanceof ve.dm.MWTOCMetaItem ) {
		var property = metaItem.getAttribute( 'property' );
		if ( property === 'mw:PageProp/forcetoc' ) {
			this.mwTOCForce = true;
		} else if ( property === 'mw:PageProp/notoc' ) {
			this.mwTOCDisable = true;
		}
	}
	this.updateVisibility();
};

/**
 * Bound to MetaList insert event to set TOC display options
 *
 * @param {ve.dm.MetaItem} metaItem
 */
ve.ui.MWTocWidget.prototype.onMetaListRemove = function ( metaItem ) {
	if ( metaItem instanceof ve.dm.MWTOCMetaItem ) {
		var property = metaItem.getAttribute( 'property' );
		if ( property === 'mw:PageProp/forcetoc' ) {
			this.mwTOCForce = false;
		} else if ( property === 'mw:PageProp/notoc' ) {
			this.mwTOCDisable = false;
		}
	}
	this.updateVisibility();
};

/**
 * Initialize TOC based on the presence of magic words
 */
ve.ui.MWTocWidget.prototype.initFromMetaList = function () {
	var i = 0,
		items = this.metaList.getItemsInGroup( 'mwTOC' ),
		len = items.length;
	if ( len > 0 ) {
		for ( ; i < len; i++ ) {
			if ( items[ i ] instanceof ve.dm.MWTOCMetaItem ) {
				var property = items[ i ].getAttribute( 'property' );
				if ( property === 'mw:PageProp/forcetoc' ) {
					this.mwTOCForce = true;
				}
				if ( property === 'mw:PageProp/notoc' ) {
					this.mwTOCDisable = true;
				}
			}
		}
		this.updateVisibility();
	}
};

/**
 * Hides or shows the TOC based on page and default settings
 */
ve.ui.MWTocWidget.prototype.updateVisibility = function () {
	// In MediaWiki if __FORCETOC__ is anywhere TOC is always displayed
	// ... Even if there is a __NOTOC__ in the article
	this.toggle( !this.mwTOCDisable && ( this.mwTOCForce || this.rootLength >= 3 ) );
};

/**
 * Rebuild TOC on ve.ce.MWHeadingNode teardown or setup
 *
 * Rebuilds on both teardown and setup of a node, so build is debounced
 */
ve.ui.MWTocWidget.prototype.rebuild = function () {
	if ( this.initialized ) {
		// Wait for transactions to process
		this.buildDebounced();
	}
};

/**
 * Update the text content of a specific heading node
 *
 * @param {ve.ce.MWHeadingNode} viewNode Heading node
 */
ve.ui.MWTocWidget.prototype.updateNode = function ( viewNode ) {
	if ( viewNode.$tocText ) {
		viewNode.$tocText.text( viewNode.$element.text() );
	}
};

/**
 * Build TOC from mwHeading dm nodes
 *
 * Based on generateTOC in Linker.php
 */
ve.ui.MWTocWidget.prototype.build = function () {
	var $newTocList = $( '<ul>' ),
		nodes = this.doc.getNodesByType( 'mwHeading', true ),
		surfaceView = this.surface.getView(),
		documentView = surfaceView.getDocument(),
		lastLevel = 0,
		stack = [],
		url = new URL( location.href );

	function getItemIndex( $el, n ) {
		return $el.children( 'li' ).length + ( n === stack.length - 1 ? 1 : 0 );
	}

	function linkClickHandler( /* heading */ ) {
		surfaceView.focus();
		// TODO: Impement heading scroll
		return false;
	}

	for ( var i = 0, l = nodes.length; i < l; i++ ) {
		var modelNode = nodes[ i ];
		var level = modelNode.getAttribute( 'level' );

		if ( level > lastLevel ) {
			var $list;
			if ( stack.length ) {
				$list = $( '<ul>' );
				stack[ stack.length - 1 ].children().last().append( $list );
			} else {
				$list = $newTocList;
			}
			stack.push( $list );
		} else if ( level < lastLevel ) {
			var levelDiff = lastLevel - level;
			while ( levelDiff > 0 && stack.length > 1 ) {
				stack.pop();
				levelDiff--;
			}
		}

		var tocNumber = stack.map( getItemIndex ).join( '.' );
		var viewNode = documentView.getBranchNodeFromOffset( modelNode.getRange().start );
		url.searchParams.set( 'section', ( i + 1 ).toString() );
		// The following classes are used here:
		// * toclevel-1, toclevel-2, ...
		// * tocsection-1, tocsection-2, ...
		var $item = $( '<li>' ).addClass( 'toclevel-' + stack.length ).addClass( 'tocsection-' + ( i + 1 ) );
		var $link = $( '<a>' ).attr( 'href', url.toString() ).append(
			$( '<span>' ).addClass( 'tocnumber' ).text( tocNumber )
		);
		var $text = $( '<span>' ).addClass( 'toctext' );

		viewNode.$tocText = $text;
		this.updateNode( viewNode );

		stack[ stack.length - 1 ].append( $item.append( $link.append( $text ) ) );
		$link.on( 'click', linkClickHandler.bind( this, viewNode ) );

		lastLevel = level;
	}

	this.$tocList.empty().append( $newTocList.children() );

	if ( nodes.length ) {
		this.rootLength = this.$tocList.children().length;
		var tocBeforeNode = documentView.getBranchNodeFromOffset( nodes[ 0 ].getRange().start );
		tocBeforeNode.$element.before( this.$element );
	} else {
		this.rootLength = 0;
	}

	this.initialized = true;
	this.updateVisibility();
};
