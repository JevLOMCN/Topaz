/*!
 * VisualEditor MWPreContextItem class.
 *
 * @copyright See AUTHORS.txt
 */

/**
 * Context item for a MWPre.
 *
 * @class
 * @extends ve.ui.LinearContextItem
 *
 * @constructor
 * @param {ve.ui.LinearContext} context Context the item is in
 * @param {ve.dm.Model} model Model the item is related to
 * @param {Object} [config]
 */
ve.ui.MWPreContextItem = function VeUiMWPreContextItem() {
	// Parent constructor
	ve.ui.MWPreContextItem.super.apply( this, arguments );

	// Initialization
	this.$element.addClass( 've-ui-mwPreContextItem' );
};

/* Inheritance */

OO.inheritClass( ve.ui.MWPreContextItem, ve.ui.LinearContextItem );

/* Static Properties */

ve.ui.MWPreContextItem.static.name = 'mwPre';

ve.ui.MWPreContextItem.static.icon = 'markup';

ve.ui.MWPreContextItem.static.label = OO.ui.deferMsg( 'visualeditor-mwpredialog-title' );

ve.ui.MWPreContextItem.static.modelClasses = [ ve.dm.MWPreNode ];

ve.ui.MWPreContextItem.static.commandName = 'mwPre';

/* Registration */

ve.ui.contextItemFactory.register( ve.ui.MWPreContextItem );

ve.ui.commandRegistry.register(
	new ve.ui.Command(
		'mwPre', 'window', 'open',
		{ args: [ 'mwPre' ], supportedSelections: [ 'linear' ] }
	)
);

ve.ui.sequenceRegistry.register(
	new ve.ui.Sequence( 'wikitextPre', 'mwPre', '<pre', 4 )
);
