/*!
 * VisualEditor UserInterface MWExpandableErrorElement class.
 *
 * @copyright See AUTHORS.txt
 * @license The MIT License (MIT); see LICENSE.txt
 */

/**
 * MediaWiki expandable error element.
 *
 * @class
 * @extends OO.ui.Element
 * @mixins OO.EventEmitter
 *
 * @constructor
 * @param {Object} [config] Configuration options
 */
ve.ui.MWExpandableErrorElement = function VeUiMWExpandableErrorElement( config ) {
	// Parent constructor
	ve.ui.MWExpandableErrorElement.super.call( this, config );

	// Mixin constructors
	OO.EventEmitter.call( this );

	// Interaction
	this.expanded = false;
	this.expandable = false;

	this.toggle( false );
	this.label = new OO.ui.LabelWidget( {
		classes: [ 've-ui-mwExpandableErrorElement-label' ]
	} );
	this.button = new OO.ui.ButtonWidget( {
		framed: false,
		classes: [ 've-ui-mwExpandableErrorElement-button' ],
		icon: 'expand'
	} ).toggle( false );

	this.$element.append(
		this.button.$element,
		this.label.$element
	).addClass( 've-ui-mwExpandableErrorElement' );
};

/* Inheritance */

OO.inheritClass( ve.ui.MWExpandableErrorElement, OO.ui.Element );

OO.mixinClass( ve.ui.MWExpandableErrorElement, OO.EventEmitter );

/* Events */

/**
 * @event update
 */

/* Methods */

/**
 * Set the expandability of the error.
 *
 * @param {boolean} [expandable] Value to set the expandability to,
 * determine based on label size if omitted
 */
ve.ui.MWExpandableErrorElement.prototype.setExpandable = function ( expandable ) {
	if ( expandable !== undefined ) {
		this.expandable = expandable;
	} else {
		// Check if error fits when in not-expandable mode
		this.label.$element
			.addClass( 've-ui-mwExpandableErrorElement-label-not-expandable' );
		this.expandable = this.label.$element.prop( 'scrollWidth' ) >
			this.label.$element.innerWidth();
	}
	this.label.$element
		.toggleClass( 've-ui-mwExpandableErrorElement-label-not-expandable', !this.expandable );
};

/**
 * Show the error and set the label to contain the error text.
 *
 * @param {jQuery} [$element] Element containing the error
 */
ve.ui.MWExpandableErrorElement.prototype.show = function ( $element ) {
	this.label.setLabel( $element || null );
	this.toggle( true );

	this.setExpandable();

	if ( this.expandable ) {
		this.label.$element.addClass( 've-ui-mwExpandableErrorElement-label-collapsed' );
		this.button.toggle( true );
		this.button.connect( this, { click: 'toggleLabel' } );
	}

	this.emit( 'update' );
};

/**
 * Hide and collapse the error element, remove the label, and set expandable
 * to false.
 */
ve.ui.MWExpandableErrorElement.prototype.clear = function () {
	this.label.setLabel( null );
	this.toggle( false );

	this.button.toggle( false );
	this.button.disconnect( this );
	this.toggleLabel( false );

	this.emit( 'update' );
};

/**
 * Toggles the label between collapsed and expanded.
 *
 * @param {boolean} [expand] Expand if true, collapse if false, toggle if
 * omitted
 */
ve.ui.MWExpandableErrorElement.prototype.toggleLabel = function ( expand ) {
	// Set this.expanded to the new state
	this.expanded = expand === undefined ? !this.expanded : expand;

	// Update DOM based on the new state of this.expanded
	this.button.setIcon( this.expanded ? 'collapse' : 'expand' );
	this.label.$element
		.toggleClass( 've-ui-mwExpandableErrorElement-label-expanded', this.expanded )
		.toggleClass( 've-ui-mwExpandableErrorElement-label-collapsed', !this.expanded );

	this.emit( 'update' );
};
