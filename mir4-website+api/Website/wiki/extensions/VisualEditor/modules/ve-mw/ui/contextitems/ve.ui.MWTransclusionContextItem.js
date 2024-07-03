/*!
 * VisualEditor MWTransclusionContextItem class.
 *
 * @copyright See AUTHORS.txt
 */

/**
 * Context item for a MWTransclusion.
 *
 * @class
 * @extends ve.ui.LinearContextItem
 *
 * @constructor
 * @param {ve.ui.LinearContext} context Context the item is in
 * @param {ve.dm.Model} model Model the item is related to
 * @param {Object} [config]
 */
ve.ui.MWTransclusionContextItem = function VeUiMWTransclusionContextItem() {
	// Parent constructor
	ve.ui.MWTransclusionContextItem.super.apply( this, arguments );

	// Initialization
	this.$element.addClass( 've-ui-mwTransclusionContextItem' );
	if ( !this.model.isSingleTemplate() ) {
		this.setLabel( ve.msg( 'visualeditor-dialog-transclusion-title-edit-transclusion' ) );
	}
};

/* Inheritance */

OO.inheritClass( ve.ui.MWTransclusionContextItem, ve.ui.LinearContextItem );

/* Static Properties */

ve.ui.MWTransclusionContextItem.static.name = 'transclusion';

ve.ui.MWTransclusionContextItem.static.icon = 'puzzle';

ve.ui.MWTransclusionContextItem.static.label =
	OO.ui.deferMsg( 'visualeditor-dialogbutton-template-tooltip' );

ve.ui.MWTransclusionContextItem.static.modelClasses = [ ve.dm.MWTransclusionNode ];

ve.ui.MWTransclusionContextItem.static.commandName = 'transclusion';

/**
 * Only display item for single-template transclusions of these templates.
 *
 * @property {string|string[]|null}
 * @static
 * @inheritable
 */
ve.ui.MWTransclusionContextItem.static.template = null;

/* Static Methods */

/**
 * @static
 * @localdoc Sharing implementation with ve.ui.MWTransclusionDialogTool
 */
ve.ui.MWTransclusionContextItem.static.isCompatibleWith =
	ve.ui.MWTransclusionDialogTool.static.isCompatibleWith;

/* Methods */

/**
 * @inheritdoc
 */
ve.ui.MWTransclusionContextItem.prototype.getDescription = function () {
	/** @type {ve.ce.MWTransclusionNode} */
	var nodeClass = ve.ce.nodeFactory.lookup( this.model.constructor.static.name );
	return ve.msg(
		'visualeditor-dialog-transclusion-contextitem-description',
		nodeClass.static.getDescription( this.model ),
		this.model.getPartsList().length
	);
};

/**
 * @inheritdoc
 */
ve.ui.MWTransclusionContextItem.prototype.renderBody = function () {
	var nodeClass = ve.ce.nodeFactory.lookup( this.model.constructor.static.name );
	// eslint-disable-next-line no-jquery/no-append-html
	this.$body.append( ve.htmlMsg(
		'visualeditor-dialog-transclusion-contextitem-description',
		nodeClass.static.getDescriptionDom( this.model ),
		this.model.getPartsList().length
	) );
};

/**
 * @inheritdoc
 */
ve.ui.MWTransclusionContextItem.prototype.onEditButtonClick = function () {
	var surfaceModel = this.context.getSurface().getModel(),
		selection = surfaceModel.getSelection(),
		contextItem = this;

	if ( selection instanceof ve.dm.TableSelection ) {
		surfaceModel.setLinearSelection( selection.getOuterRanges(
			surfaceModel.getDocument()
		)[ 0 ] );
	}

	ve.ui.MWTransclusionContextItem.super.prototype.onEditButtonClick.apply( this, arguments );

	this.context.getSurface().getDialogs().once( 'opening', function ( win, opening ) {
		opening.then( function () {
			contextItem.toggleLoadingVisualization( false );
		} );
	} );
	this.toggleLoadingVisualization( true );
};

/**
 * @private
 * @param {boolean} [isLoading=false]
 */
ve.ui.MWTransclusionContextItem.prototype.toggleLoadingVisualization = function ( isLoading ) {
	this.editButton.setDisabled( isLoading );
	if ( isLoading ) {
		this.originalEditButtonLabel = this.editButton.getLabel();
		this.editButton.setLabel( ve.msg( 'visualeditor-dialog-transclusion-contextitem-loading' ) );
	} else if ( this.originalEditButtonLabel ) {
		this.editButton.setLabel( this.originalEditButtonLabel );
	}
};

/* Registration */

ve.ui.contextItemFactory.register( ve.ui.MWTransclusionContextItem );
