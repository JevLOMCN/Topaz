/*!
 * VisualEditor UserInterface CompletionWidget class.
 *
 * @copyright See AUTHORS.txt
 */

/**
 * Widget that displays autocompletion suggestions
 *
 * @class
 * @extends OO.ui.Widget
 *
 * @constructor
 * @param {ve.ui.Surface} surface Surface to complete into
 * @param {Object} [config] Configuration options
 * @cfg {Object} [validate] Validation pattern passed to TextInputWidgets
 * @cfg {boolean} [readOnly=false] Prevent changes to the value of the widget.
 */
ve.ui.CompletionWidget = function VeUiCompletionWidget( surface, config ) {
	this.surface = surface;
	this.surfaceModel = surface.getModel();

	// Configuration
	config = config || {
		anchor: false
	};

	// Parent constructor
	ve.ui.CompletionWidget.super.call( this, config );

	this.$tabIndexed = this.$element;

	var $doc = surface.getView().getDocument().getDocumentNode().$element;
	this.popup = new OO.ui.PopupWidget( {
		anchor: false,
		align: 'forwards',
		hideWhenOutOfView: false,
		autoFlip: false,
		width: null,
		$container: config.$popupContainer || this.surface.$element,
		containerPadding: config.popupPadding
	} );
	this.input = new OO.ui.TextInputWidget();
	this.menu = new OO.ui.MenuSelectWidget( {
		widget: this,
		$input: $doc.add( this.input.$input )
	} );
	// This may be better semantically as a MenuSectionOptionWidget,
	// but that causes all subsequent options to be indented.
	this.header = new OO.ui.MenuOptionWidget( {
		classes: [ 've-ui-completionWidget-header' ],
		disabled: true
	} );
	this.noResults = new OO.ui.MenuOptionWidget( {
		label: ve.msg( 'visualeditor-completionwidget-noresults' ),
		classes: [ 've-ui-completionWidget-noresults' ],
		disabled: true
	} );

	// Events
	this.menu.connect( this, {
		choose: 'onMenuChoose',
		toggle: 'onMenuToggle'
	} );
	this.input.connect( this, { change: 'update' } );

	this.popup.$element.prepend( this.input.$element );
	this.popup.$body.append(
		this.menu.$element
	);

	// Setup
	this.$element.addClass( 've-ui-completionWidget' )
		.append(
			this.popup.$element
		);
};

/* Inheritance */

OO.inheritClass( ve.ui.CompletionWidget, OO.ui.Widget );

/**
 * Setup the completion widget
 *
 * @param {ve.ui.Action} action Action which opened the widget
 * @param {boolean} [isolateInput] Isolate input from the surface
 */
ve.ui.CompletionWidget.prototype.setup = function ( action, isolateInput ) {
	var range = this.surfaceModel.getSelection().getCoveringRange();
	this.action = action;
	this.isolateInput = !!isolateInput;
	this.sequenceLength = this.action.getSequenceLength();
	this.initialOffset = range.end - this.sequenceLength;

	this.input.toggle( this.isolateInput );
	if ( this.isolateInput ) {
		this.wasActive = !this.surface.getView().isDeactivated();
		this.surface.getView().deactivate();
		this.input.setValue( '' );
		setTimeout( function () {
			this.input.focus();
		}.bind( this ), 1 );
	} else {
		this.wasActive = false;
	}

	this.update();

	this.surfaceModel.connect( this, { select: 'onModelSelect' } );
};

/**
 * Teardown the completion widget
 */
ve.ui.CompletionWidget.prototype.teardown = function () {
	this.tearingDown = true;
	this.popup.toggle( false );
	this.surfaceModel.disconnect( this );
	if ( this.wasActive ) {
		this.surface.getView().activate();
	}
	this.action = undefined;
	this.tearingDown = false;
};

/**
 * Update the completion widget after the input has changed
 */
ve.ui.CompletionWidget.prototype.update = function () {
	var direction = this.surface.getDir(),
		range = this.getCompletionRange(),
		boundingRect = this.surface.getView().getSelection( new ve.dm.LinearSelection( range ) ).getSelectionBoundingRect(),
		style = {
			top: boundingRect.bottom
		};

	var input;
	if ( this.isolateInput ) {
		input = this.input.getValue();
	} else {
		var data = this.surfaceModel.getDocument().data;
		input = data.getText( false, range );
	}

	if ( direction === 'rtl' ) {
		// This works because this.$element is a 0x0px box, with the menu positioned relative to it.
		// If this style was applied to the menu, we'd need to do some math here to align the right
		// edge of the menu with the right edge of the selection.
		style.left = boundingRect.right;
	} else {
		style.left = boundingRect.left;
	}
	this.$element.css( style );

	this.updateMenu( input );
	this.action.getSuggestions( input ).then( function ( suggestions ) {
		if ( !this.action ) {
			// Check widget hasn't been torn down
			return;
		}
		this.menu.clearItems();
		var menuItems = suggestions.map( this.action.getMenuItemForSuggestion.bind( this.action ) );
		menuItems = this.action.updateMenuItems( menuItems );
		this.menu.addItems( menuItems );
		this.menu.highlightItem( this.menu.findFirstSelectableItem() );
		this.updateMenu( input, suggestions );
	}.bind( this ) );
};

/**
 * Update the widget's menu with the latest suggestions
 *
 * @param {string} input Input text
 * @param {Array} suggestions Suggestions
 */
ve.ui.CompletionWidget.prototype.updateMenu = function ( input, suggestions ) {
	// Update the header based on the input
	var label = this.action.getHeaderLabel( input, suggestions );
	if ( label !== undefined ) {
		this.header.setLabel( label );
	}
	if ( this.header.getLabel() !== null ) {
		this.menu.addItems( [ this.header ], 0 );
	} else {
		this.menu.removeItems( [ this.header ] );
	}
	if ( !this.isolateInput ) {
		// If there is a header or menu items, show the menu
		if ( this.menu.items.length ) {
			this.menu.toggle( true );
			this.popup.toggle( true );
			// Menu may have changed size, so recalculate position
			this.popup.updateDimensions();
		} else {
			this.popup.toggle( false );
		}
	} else {
		if ( !this.menu.items.length ) {
			this.menu.addItems( [ this.noResults ], 0 );
		}
		this.menu.toggle( true );
		this.popup.toggle( true );
		this.popup.updateDimensions();
	}
};

/**
 * Handle choose events from the menu
 *
 * @param {OO.ui.MenuOptionWidget} item Chosen option
 */
ve.ui.CompletionWidget.prototype.onMenuChoose = function ( item ) {
	this.action.chooseItem( item, this.getCompletionRange( true ) );

	this.teardown();
};

/**
 * Handle toggle events from the menu
 *
 * @param {boolean} visible Menu is visible
 */
ve.ui.CompletionWidget.prototype.onMenuToggle = function ( visible ) {
	if ( !visible && !this.tearingDown ) {
		// Menu was hidden by the user (e.g. pressed ESC) - trigger a teardown
		this.teardown();
	}
};

/**
 * Handle select events from the document model
 *
 * @param {ve.dm.Selection} selection Selection
 */
ve.ui.CompletionWidget.prototype.onModelSelect = function () {
	var range = this.getCompletionRange();
	var widget = this;

	function countMatches() {
		var matches = widget.menu.getItems().length;
		if ( widget.header.getLabel() !== null ) {
			matches--;
		}
		if ( widget.action.constructor.static.alwaysIncludeInput ) {
			matches--;
		}
		return matches;
	}

	if ( !range || range.isBackwards() || this.action.shouldAbandon( this.surfaceModel.getDocument().data.getText( false, range ), countMatches() ) ) {
		this.teardown();
	} else {
		this.update();
	}
};

/**
 * Get the range where the user has entered text in the document since opening the widget
 *
 * @param {boolean} [withSequence] Include the triggering sequence text in the range
 * @return {ve.Range|null} Range, null if not valid
 */
ve.ui.CompletionWidget.prototype.getCompletionRange = function ( withSequence ) {
	var range = this.surfaceModel.getSelection().getCoveringRange();
	if ( !range || !this.action ) {
		return null;
	}
	return new ve.Range( this.initialOffset + ( withSequence ? 0 : this.sequenceLength ), range.end );
};
