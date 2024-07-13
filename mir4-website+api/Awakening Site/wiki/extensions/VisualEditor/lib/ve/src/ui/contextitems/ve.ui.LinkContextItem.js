/*!
 * VisualEditor LinkContextItem class.
 *
 * @copyright See AUTHORS.txt
 */

/**
 * Context item for a link.
 *
 * @class
 * @extends ve.ui.AnnotationContextItem
 *
 * @param {ve.ui.LinearContext} context Context the item is in
 * @param {ve.dm.Model} model Model the item is related to
 * @param {Object} [config] Configuration options
 */
ve.ui.LinkContextItem = function VeUiLinkContextItem( context, model, config ) {
	// Parent constructor
	ve.ui.LinkContextItem.super.call( this, context, model, config );

	// Initialization
	this.$element.addClass( 've-ui-linkContextItem' );

	this.labelPreview = new OO.ui.LabelWidget();
	if ( this.context.isMobile() ) {
		this.$labelLayout = $( '<div>' ).addClass( 've-ui-linkContextItem-label' ).append(
			$( '<div>' ).addClass( 've-ui-linkContextItem-label-body' ).append(
				new OO.ui.LabelWidget( {
					classes: [ 've-ui-linkContextItem-label-label' ],
					label: OO.ui.deferMsg( 'visualeditor-linkcontext-label-label' )
				} ).$element,
				$( '<div>' ).addClass( 've-ui-linkContextItem-label-preview' ).append( this.labelPreview.$element )
			)
		);
		this.$innerBody = $( '<div>' ).addClass( 've-ui-linkContextItem-inner-body' );
		this.$body.append(
			this.$labelLayout,
			new OO.ui.LabelWidget( {
				classes: [ 've-ui-linkContextItem-link-label' ],
				label: OO.ui.deferMsg( 'visualeditor-linkinspector-title' )
			} ).$element,
			this.$innerBody
		);
		// Sub-classes should now append content to $innerBody
		this.$body = this.$innerBody;
	} else {
		this.labelButton = new OO.ui.ButtonWidget( {
			label: OO.ui.deferMsg( 'visualeditor-linkcontext-label-change' ),
			framed: false,
			flags: [ 'progressive' ]
		} );
		this.$labelLayout = $( '<div>' ).addClass( 've-ui-linkContextItem-label' ).append(
			$( '<div>' ).addClass( 've-ui-linkContextItem-label-label' ).append(
				new OO.ui.IconWidget( { icon: 'quotes' } ).$element,
				new OO.ui.LabelWidget( { label: OO.ui.deferMsg( 'visualeditor-linkcontext-label-label' ) } ).$element
			),
			$( '<div>' ).addClass( 've-ui-linkContextItem-label-preview' ).append( this.labelPreview.$element )
		);
		this.labelButton.connect( this, { click: 'onLabelButtonClick' } );

		if ( !this.isReadOnly() ) {
			this.$labelLayout.append( $( '<div>' ).addClass( 've-ui-linkContextItem-label-action' ).append( this.labelButton.$element ) );
		}
	}
};

/* Inheritance */

OO.inheritClass( ve.ui.LinkContextItem, ve.ui.AnnotationContextItem );

/* Static Properties */

ve.ui.LinkContextItem.static.name = 'link';

ve.ui.LinkContextItem.static.icon = 'link';

ve.ui.LinkContextItem.static.label = OO.ui.deferMsg( 'visualeditor-linkinspector-title' );

ve.ui.LinkContextItem.static.modelClasses = [ ve.dm.LinkAnnotation ];

ve.ui.LinkContextItem.static.embeddable = false;

ve.ui.LinkContextItem.static.commandName = 'link';

ve.ui.LinkContextItem.static.clearable = true;

ve.ui.LinkContextItem.static.clearMsg = OO.ui.deferMsg( 'visualeditor-linkcontext-remove' );

ve.ui.LinkContextItem.static.clearIcon = 'unLink';

/* Methods */

/**
 * @inheritdoc
 */
ve.ui.LinkContextItem.prototype.getDescription = function () {
	return this.model.getHref();
};

/**
 * @inheritdoc
 */
ve.ui.LinkContextItem.prototype.renderBody = function () {
	var htmlDoc = this.context.getSurface().getModel().getDocument().getHtmlDocument();
	var $link = $( '<a>' )
		.addClass( 've-ui-linkContextItem-link' )
		.text( this.getDescription() )
		.attr( {
			target: '_blank',
			rel: 'noopener'
		} );
	// T322704
	ve.setAttributeSafe( $link[ 0 ], 'href', ve.resolveUrl( this.model.getHref(), htmlDoc ), '#' );
	this.$body.empty().append( $link );
	if ( !this.context.isMobile() ) {
		this.$body.append( this.$labelLayout );
	}
	this.updateLabelPreview();
};

/**
 * Set the preview of the label
 *
 * @protected
 */
ve.ui.LinkContextItem.prototype.updateLabelPreview = function () {
	var surfaceModel = this.context.getSurface().getModel(),
		annotationView = this.getAnnotationView();

	// annotationView is a potentially old view node from when the context was
	// first focused in the document. If the annotation model has been changed
	// as well, this may be a problem.
	var label;
	if ( annotationView ) {
		label = surfaceModel.getFragment().expandLinearSelection( 'annotation', annotationView.getModel() ).getText();
	}

	this.labelPreview.setLabel( label || ve.msg( 'visualeditor-linkcontext-label-fallback' ) );
};

/**
 * Handle label-edit button click events.
 *
 * @localdoc Selects the contents of the link annotation
 *
 * @protected
 */
ve.ui.LinkContextItem.prototype.onLabelButtonClick = function () {
	var surface = this.context.getSurface().getView(),
		annotationView = this.getAnnotationView();

	surface.selectNodeContents(
		annotationView.$element[ 0 ],
		this.context.isMobile() ? 'end' : undefined
	);

	ve.track( 'activity.' + this.constructor.static.name, { action: 'context-label' } );
};

/* Registration */

ve.ui.contextItemFactory.register( ve.ui.LinkContextItem );
