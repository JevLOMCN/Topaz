/*!
 * VisualEditor MWSignatureContextItem class.
 *
 * @copyright See AUTHORS.txt
 */

/**
 * Context item for a MWSignature.
 *
 * @class
 * @extends ve.ui.LinearContextItem
 *
 * @constructor
 * @param {ve.ui.LinearContext} context Context the item is in
 * @param {ve.dm.Model} model Model the item is related to
 * @param {Object} [config]
 */
ve.ui.MWSignatureContextItem = function VeUiMWSignatureContextItem() {
	// Parent constructor
	ve.ui.MWSignatureContextItem.super.apply( this, arguments );

	// Initialization
	this.$element.addClass( 've-ui-mwSignatureContextItem' );
	this.$actions.remove();
};

/* Inheritance */

OO.inheritClass( ve.ui.MWSignatureContextItem, ve.ui.LinearContextItem );

/* Static Properties */

ve.ui.MWSignatureContextItem.static.editable = false;

ve.ui.MWSignatureContextItem.static.name = 'mwSignature';

ve.ui.MWSignatureContextItem.static.icon = 'signature';

ve.ui.MWSignatureContextItem.static.label =
	OO.ui.deferMsg( 'visualeditor-mwsignature-tool' );

ve.ui.MWSignatureContextItem.static.modelClasses = [ ve.dm.MWSignatureNode ];

/* Methods */

/**
 * @inheritdoc
 */
ve.ui.MWSignatureContextItem.prototype.getDescription = function () {
	return '';
};

/* Registration */

ve.ui.contextItemFactory.register( ve.ui.MWSignatureContextItem );
