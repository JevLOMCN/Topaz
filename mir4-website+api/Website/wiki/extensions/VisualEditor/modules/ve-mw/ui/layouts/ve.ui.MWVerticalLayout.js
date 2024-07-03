/**
 * Container for a vertical series of PageLayouts, similar to OO.ui.HorizontalLayout
 */
ve.ui.MWVerticalLayout = function VeUiMwVerticalLayout() {
	// Parent constructor
	ve.ui.MWVerticalLayout.super.call( this, { scrollable: true } );

	// Mixin constructors
	OO.ui.mixin.GroupElement.call( this, { $group: this.$element } );

	// Initialization
	this.$element.addClass( 've-ui-mwVerticalLayout' );
};

OO.inheritClass( ve.ui.MWVerticalLayout, OO.ui.PanelLayout );
OO.mixinClass( ve.ui.MWVerticalLayout, OO.ui.mixin.GroupElement );
