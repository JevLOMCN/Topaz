/**
 * List of template parameters, each of which can be added or removed using a
 * checkbox.
 *
 * This is modelled after {@see OO.ui.OutlineSelectWidget}.  Currently we use
 * the SelectWidget in multi-select mode, and selection maps to checked
 * checkboxes.
 *
 * @class
 * @extends OO.ui.SelectWidget
 * @mixins OO.ui.mixin.TabIndexedElement
 * @mixins ve.ui.MWAriaDescribe
 *
 * @constructor
 * @param {Object} config
 * @cfg {ve.ui.MWTransclusionOutlineParameterWidget[]} items
 * @property {string|null} activeParameter Name of the currently selected parameter
 * @property {number} stickyHeaderHeight
 */
ve.ui.MWTransclusionOutlineParameterSelectWidget = function VeUiMWTransclusionOutlineParameterSelectWidget( config ) {
	// Parent constructor
	ve.ui.MWTransclusionOutlineParameterSelectWidget.super.call( this, ve.extendObject( config, {
		classes: [ 've-ui-mwTransclusionOutlineParameterSelectWidget' ],
		multiselect: true
	} ) );

	// Mixin constructors
	OO.ui.mixin.TabIndexedElement.call( this, {
		tabIndex: this.isEmpty() ? -1 : 0
	} );
	ve.ui.MWAriaDescribe.call( this, config );

	this.$element
		.on( {
			focus: this.bindDocumentKeyDownListener.bind( this ),
			blur: this.onBlur.bind( this )
		} );

	this.activeParameter = null;
	this.stickyHeaderHeight = 0;
};

/* Inheritance */

OO.inheritClass( ve.ui.MWTransclusionOutlineParameterSelectWidget, OO.ui.SelectWidget );
OO.mixinClass( ve.ui.MWTransclusionOutlineParameterSelectWidget, OO.ui.mixin.TabIndexedElement );
OO.mixinClass( ve.ui.MWTransclusionOutlineParameterSelectWidget, ve.ui.MWAriaDescribe );

/* Events */

/**
 * This is fired instead of the "choose" event from the {@see OO.ui.SelectWidget} base class when
 * pressing space on a parameter to toggle it or scroll it into view, without losing the focus.
 *
 * @event templateParameterSpaceDown
 * @param {ve.ui.MWTransclusionOutlineParameterWidget} item
 * @param {boolean} selected
 */

/* Static Methods */

/**
 * @param {Object} config
 * @param {string} config.data Parameter name
 * @param {string} config.label
 * @param {boolean} [config.required=false] Required parameters can't be unchecked
 * @param {boolean} [config.selected=false] If the parameter is currently used (checked)
 * @return {ve.ui.MWTransclusionOutlineParameterWidget}
 */
ve.ui.MWTransclusionOutlineParameterSelectWidget.static.createItem = function ( config ) {
	return new ve.ui.MWTransclusionOutlineParameterWidget( config );
};

/* Methods */

/**
 * @inheritDoc OO.ui.mixin.GroupElement
 * @param {ve.ui.MWTransclusionOutlineParameterWidget[]} items
 * @param {number} [index]
 * @return {ve.ui.MWTransclusionOutlineParameterSelectWidget}
 */
ve.ui.MWTransclusionOutlineParameterSelectWidget.prototype.addItems = function ( items, index ) {
	var self = this;
	items.forEach( function ( item ) {
		item.connect( self, {
			change: [ 'onCheckboxChange', item ]
		} );
	} );

	ve.ui.MWTransclusionOutlineParameterSelectWidget.super.prototype.addItems.call( this, items, index );
	this.setTabIndex( this.isEmpty() ? -1 : 0 );
	return this;
};

ve.ui.MWTransclusionOutlineParameterSelectWidget.prototype.ensureVisibilityOfFirstCheckedParameter = function () {
	// TODO: Replace with {@see OO.ui.SelectWidget.findFirstSelectedItem} when available
	var firstChecked = this.findSelectedItems()[ 0 ];
	if ( firstChecked ) {
		firstChecked.ensureVisibility( this.stickyHeaderHeight );
	}
};

/**
 * @param {string|null} [paramName] Parameter name to set, e.g. "param1". Omit to remove setting.
 */
ve.ui.MWTransclusionOutlineParameterSelectWidget.prototype.setActiveParameter = function ( paramName ) {
	// Note: We know unnamed parameter placeholders never have an item here
	var newItem = paramName ? this.findItemFromData( paramName ) : null;
	// Unhighlight when called with no parameter name
	this.highlightItem( newItem );

	paramName = paramName || null;
	if ( this.activeParameter === paramName ) {
		return;
	}

	var currentItem = this.activeParameter ? this.findItemFromData( this.activeParameter ) : null;
	this.activeParameter = paramName;

	if ( currentItem ) {
		currentItem.toggleActivePageIndicator( false );
	}
	if ( newItem ) {
		newItem.toggleActivePageIndicator( true );
	}
};

/**
 * @inheritDoc OO.ui.SelectWidget
 */
ve.ui.MWTransclusionOutlineParameterSelectWidget.prototype.highlightItem = function ( item ) {
	if ( item ) {
		item.ensureVisibility( this.stickyHeaderHeight );
	}
	ve.ui.MWTransclusionOutlineParameterSelectWidget.super.prototype.highlightItem.call( this, item );
};

/**
 * @param {string} paramName
 */
ve.ui.MWTransclusionOutlineParameterSelectWidget.prototype.markParameterAsUnused = function ( paramName ) {
	// There is no OO.ui.SelectWidget.unselectItemByData(), we need to do this manually
	/** @type {ve.ui.MWTransclusionOutlineParameterWidget} */
	var item = paramName ? this.findItemFromData( paramName ) : null;
	if ( item ) {
		item.setSelected( false );
		// An unused parameter can't be the active (set) one; it doesn't exist in the content pane
		if ( this.activeParameter === paramName ) {
			this.activeParameter = null;
			item.toggleActivePageIndicator( false );
		}
	}
};

/**
 * @private
 * @param {ve.ui.MWTransclusionOutlineParameterWidget} item
 * @param {boolean} value
 */
ve.ui.MWTransclusionOutlineParameterSelectWidget.prototype.onCheckboxChange = function ( item, value ) {
	// This extra check shouldn't be necessary, but better be safe than sorry
	if ( item.isSelected() !== value ) {
		// Note: This should have been named `toggle…` as it toggles the item's selection
		this.chooseItem( item );
	}
};

/**
 * @inheritDoc OO.ui.SelectWidget
 */
ve.ui.MWTransclusionOutlineParameterSelectWidget.prototype.onFocus = function ( event ) {
	if ( event.target !== this.$element[ 0 ] || this.findHighlightedItem() ) {
		return;
	}

	var index = 0;
	if ( event.relatedTarget ) {
		var toolbarClass = 've-ui-mwTransclusionOutlineControlsWidget',
			// The only elements below a parameter list can be another part or the toolbar
			selector = '.ve-ui-mwTransclusionOutlinePartWidget, .' + toolbarClass,
			$fromPart = $( event.relatedTarget ).closest( selector ),
			$toPart = $( event.target ).closest( selector );
		// When shift+tabbing into the list, highlight the last parameter
		// eslint-disable-next-line no-jquery/no-class-state
		if ( $fromPart.hasClass( toolbarClass ) || $fromPart.index() > $toPart.index() ) {
			index = this.getItemCount() - 1;
		}
	}
	this.highlightItem( this.items[ index ] );

	// Don't call the parent. It makes assumptions what should be done here.
};

/**
 * @inheritDoc OO.ui.SelectWidget
 * @param {jQuery.Event} e
 * @fires choose
 */
ve.ui.MWTransclusionOutlineParameterSelectWidget.prototype.onMouseDown = function ( e ) {
	if ( e.which === OO.ui.MouseButtons.LEFT ) {
		var item = this.findTargetItem( e );
		// Same as pressing enter, see below.
		if ( item && item.isSelected() ) {
			this.emit( 'choose', item, item.isSelected() );

			// Don't call the parent, i.e. can't click to unselect the item
			return false;
		}
	}

	ve.ui.MWTransclusionOutlineParameterSelectWidget.super.prototype.onMouseDown.call( this, e );
};

/**
 * @inheritDoc OO.ui.SelectWidget
 * @param {KeyboardEvent} e
 * @fires choose
 * @fires templateParameterSpaceDown
 */
ve.ui.MWTransclusionOutlineParameterSelectWidget.prototype.onDocumentKeyDown = function ( e ) {
	var item;

	switch ( e.keyCode ) {
		case OO.ui.Keys.HOME:
			item = this.items[ 0 ];
			if ( item ) {
				this.highlightItem( item );
			}
			break;
		case OO.ui.Keys.END:
			item = this.items[ this.items.length - 1 ];
			if ( item ) {
				this.highlightItem( item );
			}
			break;
		case OO.ui.Keys.SPACE:
			item = this.findHighlightedItem();
			if ( item ) {
				// Warning, this intentionally doesn't call .chooseItem() because we don't want this
				// to fire a "choose" event!
				if ( item.isSelected() ) {
					this.unselectItem( item );
				} else {
					this.selectItem( item );
				}
				this.emit( 'templateParameterSpaceDown', item, item.isSelected() );
			}
			e.preventDefault();
			break;
		case OO.ui.Keys.ENTER:
			item = this.findHighlightedItem();
			// Same as clicking with the mouse, see above.
			if ( item && item.isSelected() ) {
				this.emit( 'choose', item, item.isSelected() );
				e.preventDefault();

				// Don't call the parent, i.e. can't use enter to unselect the item
				return false;
			}
			break;
	}

	ve.ui.MWTransclusionOutlineParameterSelectWidget.super.prototype.onDocumentKeyDown.call( this, e );
};

ve.ui.MWTransclusionOutlineParameterSelectWidget.prototype.onBlur = function () {
	this.highlightItem();
	this.unbindDocumentKeyDownListener();
};
