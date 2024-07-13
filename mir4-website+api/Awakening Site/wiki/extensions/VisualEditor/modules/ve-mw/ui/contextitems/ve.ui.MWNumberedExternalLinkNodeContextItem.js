/*!
 * VisualEditor MWNumberedExternalLinkNodeContextItem class.
 *
 * @copyright See AUTHORS.txt
 */

/**
 * Context item for a MWNumberedExternalLinkNode.
 *
 * @class
 * @extends ve.ui.LinkContextItem
 *
 * @constructor
 * @param {ve.ui.LinearContext} context Context the item is in
 * @param {ve.dm.Model} model Model the item is related to
 * @param {Object} [config]
 */
ve.ui.MWNumberedExternalLinkNodeContextItem = function VeUiMWNumberedExternalLinkNodeContextItem() {
	// Parent constructor
	ve.ui.MWNumberedExternalLinkNodeContextItem.super.apply( this, arguments );

	// Initialization
	this.$element.addClass( 've-ui-mwNumberedExternalLinkNodeContextItem' );

	if ( this.context.isMobile() ) {
		// Label editing button doesn't exist on mobile by default
		this.labelButton = new OO.ui.ButtonWidget( {
			label: OO.ui.deferMsg( 'visualeditor-linknodeinspector-add-label' ),
			framed: false,
			flags: [ 'progressive' ]
		} );
		this.labelButton.connect( this, { click: 'onLabelButtonClick' } );
		this.$labelLayout.empty().append( this.labelButton.$element );
	} else {
		this.labelButton.setLabel( OO.ui.deferMsg( 'visualeditor-linknodeinspector-add-label' ) );
	}
};

/* Inheritance */

OO.inheritClass( ve.ui.MWNumberedExternalLinkNodeContextItem, ve.ui.LinkContextItem );

/* Static Properties */

ve.ui.MWNumberedExternalLinkNodeContextItem.static.name = 'link/mwNumberedExternal';

ve.ui.MWNumberedExternalLinkNodeContextItem.static.modelClasses = [ ve.dm.MWNumberedExternalLinkNode ];

ve.ui.MWNumberedExternalLinkNodeContextItem.static.clearable = false;

/* Methods */

/**
 * @inheritdoc
 */
ve.ui.MWNumberedExternalLinkNodeContextItem.prototype.onLabelButtonClick = function () {
	var surfaceModel = this.context.getSurface().getModel(),
		surfaceView = this.context.getSurface().getView(),
		doc = surfaceModel.getDocument(),
		nodeRange = this.model.getOuterRange();

	// TODO: this is very similar to part of
	// ve.ui.MWLinkNodeInspector.prototype.getTeardownProcess, and should
	// perhaps be consolidated into a reusable "replace node with annotated
	// text and select that text" method somewhere appropriate.

	var annotation = new ve.dm.MWExternalLinkAnnotation( {
		type: 'link/mwExternal',
		attributes: {
			href: this.model.getHref()
		}
	} );
	var annotations = doc.data.getAnnotationsFromOffset( nodeRange.start ).clone();
	annotations.push( annotation );
	var content = this.model.getHref().split( '' );
	ve.dm.Document.static.addAnnotationsToData( content, annotations );
	surfaceModel.change(
		ve.dm.TransactionBuilder.static.newFromReplacement( doc, nodeRange, content )
	);
	setTimeout( function () {
		surfaceView.selectAnnotation( function ( view ) {
			return view.model instanceof ve.dm.MWExternalLinkAnnotation;
		} );
	} );
};

/* Registration */

ve.ui.contextItemFactory.register( ve.ui.MWNumberedExternalLinkNodeContextItem );
