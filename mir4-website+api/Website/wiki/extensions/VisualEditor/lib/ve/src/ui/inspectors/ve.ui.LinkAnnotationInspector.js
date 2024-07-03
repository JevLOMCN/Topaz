/*!
 * VisualEditor UserInterface LinkAnnotationInspector class.
 *
 * @copyright See AUTHORS.txt
 */

/**
 * Inspector for linked content.
 *
 * @class
 * @extends ve.ui.AnnotationInspector
 *
 * @constructor
 * @param {Object} [config] Configuration options
 */
ve.ui.LinkAnnotationInspector = function VeUiLinkAnnotationInspector() {
	// Parent constructor
	ve.ui.LinkAnnotationInspector.super.apply( this, arguments );
};

/* Inheritance */

OO.inheritClass( ve.ui.LinkAnnotationInspector, ve.ui.AnnotationInspector );

/* Static properties */

ve.ui.LinkAnnotationInspector.static.name = 'link';

ve.ui.LinkAnnotationInspector.static.modelClasses = [ ve.dm.LinkAnnotation ];

/* Methods */

/**
 * Handle annotation input change events
 */
ve.ui.LinkAnnotationInspector.prototype.onAnnotationInputChange = function () {
	this.labelInput.$input.attr( 'placeholder', this.getInsertionText() );
	this.updateActions();
};

/**
 * Update the actions based on the annotation state
 */
ve.ui.LinkAnnotationInspector.prototype.updateActions = function () {
	var isValid = false,
		inspector = this,
		annotation = this.annotationInput.getAnnotation();

	this.annotationInput.getTextInputWidget().getValidity()
		.then( function () {
			isValid = true;
		} )
		.always( function () {
			isValid = isValid && !!annotation;
			inspector.actions.forEach( { actions: [ 'done', 'insert' ] }, function ( action ) {
				action.setDisabled( !isValid );
			} );
		} );
};

/**
 * @inheritdoc
 */
ve.ui.LinkAnnotationInspector.prototype.shouldRemoveAnnotation = function () {
	return !this.annotationInput.getAnnotation();
};

/**
 * @inheritdoc
 */
ve.ui.LinkAnnotationInspector.prototype.getInsertionText = function () {
	return this.labelInput.getValue().trim() || this.annotationInput.getHref();
};

/**
 * @inheritdoc
 */
ve.ui.LinkAnnotationInspector.prototype.getAnnotation = function () {
	return this.annotationInput.getAnnotation();
};

/**
 * @inheritdoc
 */
ve.ui.LinkAnnotationInspector.prototype.getAnnotationFromFragment = function ( fragment ) {
	var text = fragment.getText();

	return text ? new ve.dm.LinkAnnotation( {
		type: 'link',
		attributes: { href: text }
	} ) : null;
};

/**
 * @inheritdoc
 */
ve.ui.LinkAnnotationInspector.prototype.initialize = function () {
	// Parent method
	ve.ui.LinkAnnotationInspector.super.prototype.initialize.call( this );

	// Properties
	this.labelInput = this.createLabelInput();
	this.annotationInput = this.createAnnotationInput();

	this.labelField = new OO.ui.FieldLayout(
		this.labelInput,
		{
			align: 'top',
			label: ve.msg( 'visualeditor-linkcontext-label-label' )
		}
	);
	this.annotationField = new OO.ui.FieldLayout(
		this.annotationInput,
		{
			align: 'top',
			label: ve.msg( 'visualeditor-linkinspector-title' )
		}
	);

	// Events
	this.annotationInput.connect( this, { change: 'onAnnotationInputChange' } );
	this.annotationInput.getTextInputWidget().connect( this, { enter: 'onFormSubmit' } );
	this.labelInput.connect( this, { enter: 'onFormSubmit' } );
	this.labelInput.connect( this, { change: 'onAnnotationInputChange' } );

	// Initialization
	this.form.$element.append(
		this.labelField.$element,
		this.annotationField.$element
	);

	if ( !OO.ui.isMobile() ) {
		this.annotationField.setLabel( null );
		this.labelField.$element.detach();
	}
};

/**
 * Create a link label widget
 *
 * @return {OO.ui.TextInputWidget} Link label widget
 */
ve.ui.LinkAnnotationInspector.prototype.createLabelInput = function () {
	return new OO.ui.TextInputWidget();
};

/**
 * Create a link annotation widget
 *
 * @return {ve.ui.LinkAnnotationWidget} Link annotation widget
 */
ve.ui.LinkAnnotationInspector.prototype.createAnnotationInput = function () {
	return new ve.ui.LinkAnnotationWidget();
};

/**
 * @inheritdoc
 */
ve.ui.LinkAnnotationInspector.prototype.shouldInsertText = function () {
	if ( ve.ui.LinkAnnotationInspector.super.prototype.shouldInsertText.call( this ) ) {
		// Adding a new link
		return true;
	}
	if ( OO.ui.isMobile() ) {
		return !this.labelInput.isDisabled() &&
			// Don't touch it if the plaintext value hasn't changed, to preserve internal annotations if possible
			this.labelInput.getValue().trim() !== this.initialLabel.trim();
	}
	return false;
};

/**
 * @inheritdoc
 */
ve.ui.LinkAnnotationInspector.prototype.getSetupProcess = function ( data ) {
	return ve.ui.LinkAnnotationInspector.super.prototype.getSetupProcess.call( this, data )
		.next( function () {
			var title = ve.msg(
					this.isReadOnly() ?
						'visualeditor-linkinspector-title' : (
							this.isNew ?
								'visualeditor-linkinspector-title-add' :
								'visualeditor-linkinspector-title-edit'
						)
				),
				fragment = this.getFragment();
			this.title.setLabel( title ).setTitle( title );
			this.initialLabel = fragment.getText();
			this.labelInput.setDisabled( !fragment.containsOnlyText() );
			this.labelInput.setValue( this.initialLabel );
			this.annotationInput.setAnnotation( this.initialAnnotation );
			this.annotationInput.setReadOnly( this.isReadOnly() );

			this.updateActions();
		}, this );
};

/**
 * @inheritdoc
 */
ve.ui.LinkAnnotationInspector.prototype.getReadyProcess = function ( data ) {
	return ve.ui.LinkAnnotationInspector.super.prototype.getReadyProcess.call( this, data )
		.next( function () {
			if ( !OO.ui.isMobile() ) {
				this.annotationInput.getTextInputWidget().focus().select();
			}

			// Clear validation state, so that we don't get "invalid" state immediately on focus
			this.annotationInput.getTextInputWidget().setValidityFlag( true );
		}, this );
};

/**
 * @inheritdoc
 */
ve.ui.LinkAnnotationInspector.prototype.getHoldProcess = function ( data ) {
	return ve.ui.LinkAnnotationInspector.super.prototype.getHoldProcess.call( this, data )
		.next( function () {
			this.annotationInput.getTextInputWidget().blur();
		}, this );
};

/**
 * @inheritdoc
 */
ve.ui.LinkAnnotationInspector.prototype.getTeardownProcess = function ( data ) {
	return ve.ui.LinkAnnotationInspector.super.prototype.getTeardownProcess.call( this, data )
		.next( function () {
			this.annotationInput.setAnnotation( null );
			this.labelInput.$input.attr( 'placeholder', '' );
			this.labelInput.setValue( '' );
		}, this );
};

/* Registration */

ve.ui.windowFactory.register( ve.ui.LinkAnnotationInspector );
