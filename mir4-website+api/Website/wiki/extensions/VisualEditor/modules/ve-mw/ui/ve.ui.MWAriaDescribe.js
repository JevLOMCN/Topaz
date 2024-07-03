/**
 * Mixin for adding descriptive ARIA support to elements.
 *
 * @class
 * @abstract
 *
 * @constructor
 * @param {Object} [config] Configuration options
 * @cfg {jQuery} [$ariaDescribedBy]
 * @cfg {string} [ariaLabel]
 * @cfg {jQuery} [$describedElement]
 */
ve.ui.MWAriaDescribe = function VeUiMWAriaDescribe( config ) {
	this.$describedElement = config.$describedElement || this.$element;

	if ( config.$ariaDescribedBy ) {
		this.setAriaDescribedBy( config.$ariaDescribedBy );
	}

	if ( config.ariaLabel ) {
		this.setAriaLabel( config.ariaLabel );
	}
};

/* Setup */

OO.initClass( ve.ui.MWAriaDescribe );

/**
 * @param {jQuery} [$description]
 * @chainable
 * @return {OO.ui.Element} The element, for chaining
 */
ve.ui.MWAriaDescribe.prototype.setAriaDescribedBy = function ( $description ) {
	if ( !$description ) {
		this.$describedElement.removeAttr( 'aria-describedby' );
		return this;
	}

	if ( !$description.attr( 'id' ) ) {
		$description.attr( 'id', OO.ui.generateElementId() );
	}
	this.$describedElement.attr( 'aria-describedby', $description.attr( 'id' ) );
	return this;
};

/**
 * @param {string} label
 * @chainable
 * @return {OO.ui.Element} The element, for chaining
 */
ve.ui.MWAriaDescribe.prototype.setAriaLabel = function ( label ) {
	this.$describedElement.attr( 'aria-label', label );
	return this;
};
