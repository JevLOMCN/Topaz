/*!
 * VisualEditor MWMagicLinkNodeContextItem class.
 *
 * @copyright See AUTHORS.txt
 */

/**
 * Context item for a MWMagicLinkNode.
 *
 * @class
 * @extends ve.ui.LinkContextItem
 *
 * @constructor
 * @param {ve.ui.LinearContext} context Context the item is in
 * @param {ve.dm.MWMagicLinkNode} model Model the item is related to
 * @param {Object} [config]
 */
ve.ui.MWMagicLinkNodeContextItem = function VeUiMWMagicLinkNodeContextItem() {
	// Parent constructor
	ve.ui.MWMagicLinkNodeContextItem.super.apply( this, arguments );

	// Initialization
	this.$element.addClass( 've-ui-mwMagicLinkNodeContextItem' );
};

/* Inheritance */

OO.inheritClass( ve.ui.MWMagicLinkNodeContextItem, ve.ui.LinkContextItem );

/* Static Properties */

ve.ui.MWMagicLinkNodeContextItem.static.name = 'link/mwMagic';

ve.ui.MWMagicLinkNodeContextItem.static.label = null; // see #setup()

ve.ui.MWMagicLinkNodeContextItem.static.modelClasses = [ ve.dm.MWMagicLinkNode ];

ve.ui.MWMagicLinkNodeContextItem.static.clearable = false;

/* Methods */

ve.ui.MWMagicLinkNodeContextItem.prototype.setup = function () {
	// Set up label
	var msg = 'visualeditor-magiclinknodeinspector-title-' +
		this.model.getMagicType().toLowerCase();

	// The following messages are used here:
	// * visualeditor-magiclinknodeinspector-title-isbn
	// * visualeditor-magiclinknodeinspector-title-pmid
	// * visualeditor-magiclinknodeinspector-title-rfc
	this.setLabel( OO.ui.deferMsg( msg ) );

	// Invoke superclass method.
	return ve.ui.MWMagicLinkNodeContextItem.super.prototype.setup.call( this );
};

ve.ui.MWMagicLinkNodeContextItem.prototype.getDescription = function () {
	return this.model.getAttribute( 'content' );
};

/**
 * @inheritdoc
 */
ve.ui.MWMagicLinkNodeContextItem.prototype.renderBody = function () {
	// Parent method
	ve.ui.MWMagicLinkNodeContextItem.super.prototype.renderBody.apply( this, arguments );

	this.$labelLayout.remove();
};
/* Registration */

ve.ui.contextItemFactory.register( ve.ui.MWMagicLinkNodeContextItem );
