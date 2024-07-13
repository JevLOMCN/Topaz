/*!
 * VisualEditor TabIndexScope class.
 *
 * @copyright See AUTHORS.txt
 */

/**
 * TabIndex Scope container constructor
 *
 * @class
 * @constructor
 *
 * @param {Object} [config] Configuration options
 * @cfg {jQuery} [root] Initial root element to scope tabIndex within
 * @cfg {boolean} [skipAriaDisabled] Whether to skip elements that are just aria-disabled from the order
 */
ve.ui.TabIndexScope = function VeUiTabIndexScope( config ) {
	config = ve.extendObject( {
		root: false,
		skipAriaDisabled: true,
		skipAriaHidden: true
	}, config );

	this.skipAriaDisabled = config.skipAriaDisabled;
	this.skipAriaHidden = config.skipAriaHidden;

	this.onRootKeyDownBound = this.onRootKeyDown.bind( this );

	if ( config.root ) {
		this.setTabRoot( config.root );
	}
};

/* Setup */

OO.initClass( ve.ui.TabIndexScope );

/* Methods */

/**
 * Set the current root element for tabbing
 *
 * @param {jQuery} $root root element to scope tabIndex within
 */
ve.ui.TabIndexScope.prototype.setTabRoot = function ( $root ) {
	if ( this.$root ) {
		this.$root.off( 'keydown', this.onRootKeyDownBound );
	}

	this.$root = $( $root ).on( 'keydown', this.onRootKeyDownBound );
};

/**
 * Build a list of elements in the current root, in tab order
 *
 * This mimics browser behavior: fetch focusable elements, sort by [tabIndex, DOM order]
 *
 * @return {HTMLElement[]} list of elements in the order they should be tabbed through
 */
ve.ui.TabIndexScope.prototype.getElementsInRoot = function () {
	var self = this,
		elements = this.$root.find( '*' ).filter( function () {
			if ( this.tabIndex === -1 ) {
				// tabIndex -1 is focusable, but shouldn't appear to keyboard-navigation
				return false;
			}
			if ( self.skipAriaDisabled && this.getAttribute( 'aria-disabled' ) === 'true' ) {
				return false;
			}
			if ( self.skipAriaHidden && $( this ).closest( '[aria-hidden="true"]', self.$root[ 0 ] ).length ) {
				return false;
			}
			if ( this.isContentEditable && this.contentEditable !== 'true' ) {
				// Skip nodes within contentEditable nodes (but not the root contentEditable nodes),
				// which would be focusable if they weren't editable, e.g. links.
				// This matches browser behavior.
				return false;
			}
			return OO.ui.isFocusableElement( $( this ) );
		} ).map( function ( index ) {
			return { element: this, index: index };
		} ).get();
	elements.sort( function ( a, b ) {
		if ( a.element.tabIndex < b.element.tabIndex ) {
			return -1;
		}
		if ( a.element.tabIndex > b.element.tabIndex ) {
			return 1;
		}
		return a.index - b.index;
	} );
	return elements.map( function ( data ) {
		return data.element;
	} );
};

/**
 * Handle keydown events on elements
 *
 * @private
 * @param {jQuery.Event} e
 */
ve.ui.TabIndexScope.prototype.onRootKeyDown = function ( e ) {
	if ( e.which !== OO.ui.Keys.TAB ) {
		return;
	}

	var elements = this.getElementsInRoot();
	var index = elements.indexOf( e.target );

	if ( index === -1 ) {
		return;
	}

	index += e.shiftKey ? -1 : 1;

	if ( ( index < 0 || index >= elements.length ) ) {
		return;
	}

	e.preventDefault();
	elements[ index ].focus();
};

/**
 * Teardown tabbable elements manager
 */
ve.ui.TabIndexScope.prototype.teardown = function () {
	this.setRoot( [] );
};
