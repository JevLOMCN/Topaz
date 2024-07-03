/**
 * Specialized layout forked from and similar to {@see OO.ui.BookletLayout}, but to synchronize the
 * sidebar and content pane of the transclusion dialog.
 *
 * Also owns the outline controls.
 *
 * This class has domain knowledge about its contents, for example different
 * behaviors for template vs template parameter elements.
 *
 * @class
 * @extends OO.ui.MenuLayout
 *
 * @constructor
 * @param {Object} [config] Configuration options
 * @property {Object.<string,OO.ui.PageLayout>} pages
 * @property {string} currentPageName Name of the currently selected transclusion item (top-level
 *  part or template parameter). Typically represented as a blue bar in the sidebar. Special cases
 *  you should be aware of:
 *  - An unchecked parameter exists as an item in the sidebar, but not as a page in the content
 *    pane.
 *  - A parameter placeholder (to add an undocumented parameter) exists as a page in the content
 *    pane, but has no corresponding item in the sidebar.
 */
ve.ui.MWTwoPaneTransclusionDialogLayout = function VeUiMWTwoPaneTransclusionDialogLayout( config ) {
	// Parent constructor
	ve.ui.MWTwoPaneTransclusionDialogLayout.super.call( this, config );

	// Properties
	this.pages = {};
	this.currentPageName = null;
	this.stackLayout = new ve.ui.MWVerticalLayout();
	this.setContentPanel( this.stackLayout );
	this.sidebar = new ve.ui.MWTransclusionOutlineWidget();
	this.outlinePanel = new OO.ui.PanelLayout( { expanded: this.expanded, scrollable: true } );
	this.setMenuPanel( this.outlinePanel );
	this.outlineControlsWidget = new ve.ui.MWTransclusionOutlineControlsWidget();

	// Events
	this.sidebar.connect( this, {
		filterPagesByName: 'onFilterPagesByName',
		sidebarItemSelected: 'onSidebarItemSelected'
	} );
	// Event 'focus' does not bubble, but 'focusin' does
	this.stackLayout.$element.on( 'focusin', this.onStackLayoutFocus.bind( this ) );

	// Initialization
	this.$element.addClass( 've-ui-mwTwoPaneTransclusionDialogLayout' );
	this.stackLayout.$element.addClass( 've-ui-mwTwoPaneTransclusionDialogLayout-stackLayout' );
	this.outlinePanel.$element
		.addClass( 've-ui-mwTwoPaneTransclusionDialogLayout-outlinePanel' )
		.append(
			$( '<div>' ).addClass( 've-ui-mwTwoPaneTransclusionDialogLayout-sidebar-container' )
				.append( this.sidebar.$element ),
			this.outlineControlsWidget.$element
		);
};

/* Setup */

OO.inheritClass( ve.ui.MWTwoPaneTransclusionDialogLayout, OO.ui.MenuLayout );

/* Methods */

/**
 * @private
 * @param {Object.<string,boolean>} visibility
 */
ve.ui.MWTwoPaneTransclusionDialogLayout.prototype.onFilterPagesByName = function ( visibility ) {
	this.currentPageName = null;
	for ( var pageName in visibility ) {
		var page = this.getPage( pageName );
		if ( page ) {
			page.toggle( visibility[ pageName ] );
		}
	}
};

/**
 * @param {ve.dm.MWTransclusionPartModel|null} removed Removed part
 * @param {ve.dm.MWTransclusionPartModel|null} added Added part
 * @param {number} [newPosition]
 */
ve.ui.MWTwoPaneTransclusionDialogLayout.prototype.onReplacePart = function ( removed, added, newPosition ) {
	this.sidebar.onReplacePart( removed, added, newPosition );

	var keys = Object.keys( this.pages ),
		isMultiPart = keys.length > 1,
		isLastPlaceholder = keys.length === 1 &&
			this.pages[ keys[ 0 ] ] instanceof ve.ui.MWTemplatePlaceholderPage;

	// TODO: In other cases this is disabled rather than hidden. See T311303
	this.outlineControlsWidget.removeButton.toggle( !isLastPlaceholder );

	if ( isMultiPart ) {
		// Warning, this is intentionally never turned off again
		this.outlineControlsWidget.toggle( true );
	}
};

/**
 * @private
 * @param {jQuery.Event} e Focusin event
 */
ve.ui.MWTwoPaneTransclusionDialogLayout.prototype.onStackLayoutFocus = function ( e ) {
	// Find the page that an element was focused within
	var $target = $( e.target ).closest( '.oo-ui-pageLayout' );
	for ( var name in this.pages ) {
		if ( this.pages[ name ].$element[ 0 ] === $target[ 0 ] ) {
			this.setPage( name );
			break;
		}
	}
};

/**
 * Focus the input field for the current page.
 *
 * If the focus is already in an element on the current page, nothing will happen.
 */
ve.ui.MWTwoPaneTransclusionDialogLayout.prototype.focus = function () {
	var page = this.pages[ this.currentPageName ];
	if ( !page ) {
		return;
	}

	// Only change the focus if it's visible and is not already the current page
	if ( page.$element[ 0 ].offsetParent !== null &&
		!OO.ui.contains( page.$element[ 0 ], this.getElementDocument().activeElement, true )
	) {
		page.focus();
	}
};

/**
 * @param {string} pageName
 */
ve.ui.MWTwoPaneTransclusionDialogLayout.prototype.focusPart = function ( pageName ) {
	this.setPage( pageName );
	this.focus();
};

/**
 * Parts and parameters can be soft-selected, or selected and focused.
 *
 * @param {string|null} pageName Full, unique name of part or parameter, or null to deselect
 * @param {boolean} [soft] If true, suppress content pane focus.
 */
ve.ui.MWTwoPaneTransclusionDialogLayout.prototype.onSidebarItemSelected = function ( pageName, soft ) {
	this.setPage( pageName );

	var page = this.pages[ pageName ];
	if ( page ) {
		// Warning, scrolling must be done before focussing. The browser will trigger a conflicting
		// scroll when the focussed element is out of view.
		page.scrollElementIntoView( { alignToTop: true, padding: { top: 20 } } );
	}

	// We assume "mobile" means "touch device with on-screen keyboard". That should only open when
	// tapping the input field, not when navigating in the sidebar.
	if ( !soft && !OO.ui.isMobile() ) {
		this.focus();
	}
};

/**
 * @param {boolean} show If the sidebar should be shown or not.
 */
ve.ui.MWTwoPaneTransclusionDialogLayout.prototype.toggleOutline = function ( show ) {
	this.toggleMenu( show );
	if ( show ) {
		var self = this;
		// HACK: Kill dumb scrollbars when the sidebar stops animating, see T161798.
		// Only necessary when outline controls are present, delay matches transition on
		// `.oo-ui-menuLayout-menu`.
		setTimeout( function () {
			OO.ui.Element.static.reconsiderScrollbars( self.outlinePanel.$element[ 0 ] );
		}, OO.ui.theme.getDialogTransitionDuration() );
	}
};

/**
 * @return {ve.ui.MWTransclusionOutlineControlsWidget}
 */
ve.ui.MWTwoPaneTransclusionDialogLayout.prototype.getOutlineControls = function () {
	return this.outlineControlsWidget;
};

/**
 * Get the list of pages on the stack ordered by appearance.
 *
 * @return {OO.ui.PageLayout[]}
 */
ve.ui.MWTwoPaneTransclusionDialogLayout.prototype.getPagesOrdered = function () {
	return this.stackLayout.getItems();
};

/**
 * @param {string} name Symbolic name of page
 * @return {OO.ui.PageLayout|undefined} Page, if found
 */
ve.ui.MWTwoPaneTransclusionDialogLayout.prototype.getPage = function ( name ) {
	return this.pages[ name ];
};

/**
 * @return {OO.ui.PageLayout|undefined} Current page, if found
 */
ve.ui.MWTwoPaneTransclusionDialogLayout.prototype.getCurrentPage = function () {
	return this.pages[ this.currentPageName ];
};

/**
 * @return {string|null} A top-level part id like "part_0" if that part is selected. When a
 *  parameter is selected null is returned.
 */
ve.ui.MWTwoPaneTransclusionDialogLayout.prototype.getSelectedTopLevelPartId = function () {
	var page = this.getCurrentPage(),
		isParameter = page instanceof ve.ui.MWParameterPage || page instanceof ve.ui.MWAddParameterPage;
	return page && !isParameter ? page.getName() : null;
};

/**
 * @return {string|null} A top-level part id like "part_0" that corresponds to the current
 *  selection, whatever is selected. When a parameter is selected the id of the template the
 *  parameter belongs to is returned.
 */
ve.ui.MWTwoPaneTransclusionDialogLayout.prototype.getTopLevelPartIdForSelection = function () {
	return this.currentPageName ? this.currentPageName.split( '/', 1 )[ 0 ] : null;
};

/**
 * When pages are added with the same names as existing pages, the existing pages will be
 * automatically removed before the new pages are added.
 *
 * @param {OO.ui.PageLayout[]} pages Pages to add
 * @param {number} index Index of the insertion point
 */
ve.ui.MWTwoPaneTransclusionDialogLayout.prototype.addPages = function ( pages, index ) {
	var i, name, page,
		stackLayoutPages = this.stackLayout.getItems();

	// Remove pages with same names
	var remove = [];
	for ( i = 0; i < pages.length; i++ ) {
		page = pages[ i ];
		name = page.getName();

		if ( Object.prototype.hasOwnProperty.call( this.pages, name ) ) {
			// Correct the insertion index
			var currentIndex = stackLayoutPages.indexOf( this.pages[ name ] );
			if ( currentIndex !== -1 && currentIndex + 1 < index ) {
				index--;
			}
			remove.push( name );
		}
	}
	if ( remove.length ) {
		this.removePages( remove );
	}

	// Add new pages
	for ( i = 0; i < pages.length; i++ ) {
		page = pages[ i ];
		name = page.getName();
		this.pages[ page.getName() ] = page;
	}

	this.stackLayout.addItems( pages, index );
};

/**
 * @param {string[]} pagesNamesToRemove
 */
ve.ui.MWTwoPaneTransclusionDialogLayout.prototype.removePages = function ( pagesNamesToRemove ) {
	var layout = this,
		pagesToRemove = [],
		isCurrentParameter = this.pages[ this.currentPageName ] instanceof ve.ui.MWParameterPage,
		isCurrentPageRemoved = false,
		prevSelectionCandidate, nextSelectionCandidate;

	this.stackLayout.getItems().forEach( function ( page ) {
		var pageName = page.getName();

		if ( pagesNamesToRemove.indexOf( pageName ) !== -1 ) {
			pagesToRemove.push( page );
			delete layout.pages[ pageName ];
			if ( layout.currentPageName === pageName ) {
				layout.currentPageName = null;
				isCurrentPageRemoved = true;
			}
			return;
		}

		// Move the selection from a removed top-level part to another, but not to a parameter
		if ( pageName.indexOf( '/' ) === -1 ) {
			if ( !isCurrentPageRemoved ) {
				// The last part before the removed one
				prevSelectionCandidate = pageName;
			} else if ( !nextSelectionCandidate ) {
				// The first part after the removed one
				nextSelectionCandidate = pageName;
			}
		}
	} );

	this.stackLayout.removeItems( pagesToRemove );
	if ( isCurrentPageRemoved && !isCurrentParameter ) {
		this.setPage( nextSelectionCandidate || prevSelectionCandidate );
	}
};

ve.ui.MWTwoPaneTransclusionDialogLayout.prototype.clearPages = function () {
	this.pages = {};
	this.currentPageName = null;
	this.sidebar.clear();
	this.stackLayout.clearItems();
};

/**
 * Set the current page and sidebar selection, by symbolic name. Doesn't focus the input.
 *
 * @param {string} [name] Symbolic name of page. Omit to remove current selection.
 */
ve.ui.MWTwoPaneTransclusionDialogLayout.prototype.setPage = function ( name ) {
	var page = this.pages[ name ];

	if ( page && name === this.currentPageName ) {
		return;
	}

	var previousPage = this.currentPageName ? this.pages[ this.currentPageName ] : null;
	this.currentPageName = name;

	if ( previousPage ) {
		// Blur anything focused if the next page doesn't have anything focusable.
		// This is not needed if the next page has something focusable (because once it is
		// focused this blur happens automatically).
		if ( !OO.ui.isMobile() &&
			( !page || OO.ui.findFocusable( page.$element ).length !== 0 )
		) {
			var $focused = previousPage.$element.find( ':focus' );
			if ( $focused.length ) {
				$focused[ 0 ].blur();
			}
		}
	}

	this.sidebar.setSelectionByPageName( name );
	this.refreshControls();
};

/**
 * @private
 */
ve.ui.MWTwoPaneTransclusionDialogLayout.prototype.refreshControls = function () {
	var partId = this.getSelectedTopLevelPartId(),
		canMoveUp, canMoveDown = false,
		canBeDeleted = !!partId;

	if ( partId ) {
		var pages = this.stackLayout.getItems(),
			page = this.getPage( partId ),
			index = pages.indexOf( page );
		canMoveUp = index > 0;
		// Check if there is at least one more top-level part below the current one
		for ( var i = index + 1; i < pages.length; i++ ) {
			if ( !( pages[ i ] instanceof ve.ui.MWParameterPage || pages[ i ] instanceof ve.ui.MWAddParameterPage ) ) {
				canMoveDown = true;
				break;
			}
		}
	}

	this.outlineControlsWidget.setButtonsEnabled( {
		canMoveUp: canMoveUp,
		canMoveDown: canMoveDown,
		canBeDeleted: canBeDeleted
	} );
};
