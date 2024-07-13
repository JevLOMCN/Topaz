/**
 * Container for the entire transclusion dialog sidebar, may contain a single or
 * multiple templates or raw wikitext snippets.
 *
 * @class
 * @extends OO.ui.Widget
 *
 * @constructor
 * @property {Object.<string,ve.ui.MWTransclusionOutlinePartWidget>} partWidgets Map of top-level
 *  items currently visible in this container, indexed by part id
 */
ve.ui.MWTransclusionOutlineWidget = function VeUiMWTransclusionOutlineWidget() {
	// Parent constructor
	ve.ui.MWTransclusionOutlineWidget.super.call( this, {
		classes: [ 've-ui-mwTransclusionOutlineWidget' ]
	} );

	// Initialization
	this.partWidgets = {};
};

/* Inheritance */

OO.inheritClass( ve.ui.MWTransclusionOutlineWidget, OO.ui.Widget );

/* Events */

/**
 * @event filterPagesByName
 * @param {Object.<string,boolean>} visibility Keyed by unique id of the {@see OO.ui.BookletLayout}
 *  page, e.g. something like "part_1/param1".
 */

/**
 * Respond to the intent to select a sidebar item
 *
 * @event sidebarItemSelected
 * @param {string} pageName Unique id of the {@see OO.ui.BookletLayout} page, e.g. something like
 *  "part_1" or "part_1/param1".
 * @param {boolean} [soft] If true, don't focus the content pane.  Defaults to false.
 */

/* Methods */

/**
 * @param {ve.dm.MWTransclusionPartModel|null} removed Removed part
 * @param {ve.dm.MWTransclusionPartModel|null} added Added part
 * @param {number} [newPosition]
 */
ve.ui.MWTransclusionOutlineWidget.prototype.onReplacePart = function ( removed, added, newPosition ) {
	if ( removed ) {
		this.removePartWidget( removed );
	}
	if ( added ) {
		this.addPartWidget( added, newPosition, removed );
	}
};

/**
 * Handle spacebar in a part header
 *
 * @param {string} pageName
 * @fires sidebarItemSelected
 */
ve.ui.MWTransclusionOutlineWidget.prototype.onTransclusionPartSoftSelected = function ( pageName ) {
	this.emit( 'sidebarItemSelected', pageName, true );
};

/**
 * @private
 * @param {ve.dm.MWTransclusionPartModel} part
 */
ve.ui.MWTransclusionOutlineWidget.prototype.removePartWidget = function ( part ) {
	var id = part.getId();
	if ( id in this.partWidgets ) {
		this.partWidgets[ id ]
			.disconnect( this )
			.$element.remove();
		delete this.partWidgets[ id ];
	}
};

/**
 * @private
 * @param {ve.dm.MWTransclusionPartModel} part
 * @param {number} [newPosition]
 * @param {ve.dm.MWTransclusionPartModel|null} [removed]
 * @fires filterPagesByName
 */
ve.ui.MWTransclusionOutlineWidget.prototype.addPartWidget = function ( part, newPosition, removed ) {
	var keys = Object.keys( this.partWidgets ),
		onlyPart = keys.length === 1 && this.partWidgets[ keys[ 0 ] ];
	if ( onlyPart instanceof ve.ui.MWTransclusionOutlineTemplateWidget ) {
		// To recalculate the height of the sticky header when we enter multi-part mode
		onlyPart.recalculateStickyHeaderHeight();
	}

	var widget;
	if ( part instanceof ve.dm.MWTemplateModel ) {
		widget = new ve.ui.MWTransclusionOutlineTemplateWidget( part, removed instanceof ve.dm.MWTemplatePlaceholderModel );
		// This forwards events from the nested ve.ui.MWTransclusionOutlineTemplateWidget upwards.
		widget.connect( this, {
			filterParametersById: 'onFilterParametersByName'
		} );
	} else if ( part instanceof ve.dm.MWTemplatePlaceholderModel ) {
		widget = new ve.ui.MWTransclusionOutlinePlaceholderWidget( part );
	} else if ( part instanceof ve.dm.MWTransclusionContentModel ) {
		widget = new ve.ui.MWTransclusionOutlineWikitextWidget( part );
	}

	widget.connect( this, {
		transclusionPartSoftSelected: 'onTransclusionPartSoftSelected',
		transclusionOutlineItemSelected: [ 'emit', 'sidebarItemSelected' ]
	} );

	this.partWidgets[ part.getId() ] = widget;
	if ( typeof newPosition === 'number' && newPosition < this.$element.children().length ) {
		this.$element.children().eq( newPosition ).before( widget.$element );
	} else {
		this.$element.append( widget.$element );
	}

	if ( widget instanceof ve.ui.MWTransclusionOutlineTemplateWidget ) {
		// We can do this only after the widget is visible on screen
		widget.recalculateStickyHeaderHeight();
	}
};

ve.ui.MWTransclusionOutlineWidget.prototype.hideAllUnusedParameters = function () {
	for ( var id in this.partWidgets ) {
		var partWidget = this.partWidgets[ id ];
		if ( partWidget instanceof ve.ui.MWTransclusionOutlineTemplateWidget &&
			partWidget.toggleUnusedWidget
		) {
			partWidget.toggleUnusedWidget.toggleUnusedParameters( false );
		}
	}
};

ve.ui.MWTransclusionOutlineWidget.prototype.initializeAllStickyHeaderHeights = function () {
	for ( var id in this.partWidgets ) {
		var partWidget = this.partWidgets[ id ];
		if ( partWidget instanceof ve.ui.MWTransclusionOutlineTemplateWidget ) {
			partWidget.recalculateStickyHeaderHeight();
		}
	}
};

/**
 * This is inspired by {@see OO.ui.SelectWidget.selectItem}, but isn't one.
 *
 * @param {string} [pageName] Symbolic name of page. Omit to remove current selection.
 */
ve.ui.MWTransclusionOutlineWidget.prototype.setSelectionByPageName = function ( pageName ) {
	var selectedPartId = pageName ? pageName.split( '/', 1 )[ 0 ] : null,
		isParameter = pageName ? pageName.length > selectedPartId.length : false;

	for ( var partId in this.partWidgets ) {
		var partWidget = this.partWidgets[ partId ],
			selected = partId === pageName;

		partWidget.setSelected( selected );
		if ( selected && !isParameter ) {
			partWidget.scrollElementIntoView();
		}

		if ( partWidget instanceof ve.ui.MWTransclusionOutlineTemplateWidget ) {
			var selectedParamName = ( partId === selectedPartId && isParameter ) ?
				pageName.slice( selectedPartId.length + 1 ) : null;
			partWidget.setParameter( selectedParamName );
		}
	}
};

/**
 * @param {string} pageName
 * @param {boolean} hasValue
 */
ve.ui.MWTransclusionOutlineWidget.prototype.toggleHasValueByPageName = function ( pageName, hasValue ) {
	var idParts = pageName.split( '/', 2 ),
		templatePartWidget = this.partWidgets[ idParts[ 0 ] ];

	templatePartWidget.toggleHasValue( idParts[ 1 ], hasValue );
};

/**
 * Checks if the provided DOM element belongs to the DOM structure of one of the top-level
 * {@see ve.ui.MWTransclusionOutlinePartWidget}s, and returns its id. Useful for e.g. mouse click or
 * keyboard handlers.
 *
 * @param {HTMLElement} element
 * @return {string|undefined} Always a top-level part id, e.g. "part_0"
 */
ve.ui.MWTransclusionOutlineWidget.prototype.findPartIdContainingElement = function ( element ) {
	if ( element ) {
		for ( var id in this.partWidgets ) {
			var part = this.partWidgets[ id ];
			if ( $.contains( part.$element[ 0 ], element ) ) {
				return id;
			}
		}
	}
};

/**
 * Removes all {@see ve.ui.MWTransclusionOutlinePartWidget}, i.e. empties the list.
 */
ve.ui.MWTransclusionOutlineWidget.prototype.clear = function () {
	for ( var id in this.partWidgets ) {
		this.partWidgets[ id ]
			.disconnect( this )
			.$element.remove();
	}
	this.partWidgets = {};
};

/**
 * @private
 * @param {Object.<string,boolean>} visibility
 * @fires filterPagesByName
 */
ve.ui.MWTransclusionOutlineWidget.prototype.onFilterParametersByName = function ( visibility ) {
	this.emit( 'filterPagesByName', visibility );
	this.setSelectionByPageName();
};
