/*!
 * VisualEditor UserInterface LinkAnnotationWidget class.
 *
 * @copyright See AUTHORS.txt
 */

/**
 * Creates an ve.ui.LinkAnnotationWidget object.
 *
 * @class
 * @extends OO.ui.Widget
 *
 * @constructor
 * @param {Object} [config] Configuration options
 */
ve.ui.LinkAnnotationWidget = function VeUiLinkAnnotationWidget( config ) {
	// Properties
	this.annotation = null;
	this.input = this.createInputWidget( config );

	// Parent constructor
	// Must be called after this.input is set as parent constructor calls this.setDisabled
	ve.ui.LinkAnnotationWidget.super.apply( this, arguments );

	// Initialization
	this.$element
		.append( this.input.$element )
		.addClass( 've-ui-linkAnnotationWidget' );

	// Events
	this.getTextInputWidget().connect( this, { change: 'onTextChange' } );
};

/* Inheritance */

OO.inheritClass( ve.ui.LinkAnnotationWidget, OO.ui.Widget );

/* Events */

/**
 * @event change
 *
 * A change event is emitted when the annotation value of the input changes.
 *
 * @param {ve.dm.LinkAnnotation|null} annotation
 */

/* Static Methods */

/**
 * Get an annotation from the current text value
 *
 * @static
 * @param {string} value Text value
 * @return {ve.dm.LinkAnnotation|null} Link annotation
 */
ve.ui.LinkAnnotationWidget.static.getAnnotationFromText = function ( value ) {
	var href = value.trim();

	// Keep annotation in sync with value
	if ( href === '' ) {
		return null;
	} else {
		return new ve.dm.LinkAnnotation( {
			type: 'link',
			attributes: {
				href: href
			}
		} );
	}
};

/**
 * Get a text value for the current annotation
 *
 * @static
 * @param {ve.dm.LinkAnnotation|null} annotation Link annotation
 * @return {string} Text value for the annotation
 */
ve.ui.LinkAnnotationWidget.static.getTextFromAnnotation = function ( annotation ) {
	return annotation ? annotation.getHref() : '';
};

/* Methods */

/**
 * Create a widget to be used by the annotation widget
 *
 * @param {Object} [config] Configuration options
 * @return {OO.ui.Widget} Text input widget
 */
ve.ui.LinkAnnotationWidget.prototype.createInputWidget = function ( config ) {
	return new OO.ui.TextInputWidget( ve.extendObject( { validate: 'non-empty' }, config ) );
};

/**
 * Get the text input widget used by the annotation widget
 *
 * @return {OO.ui.TextInputWidget} Text input widget
 */
ve.ui.LinkAnnotationWidget.prototype.getTextInputWidget = function () {
	return this.input;
};

/**
 * @inheritdoc
 */
ve.ui.LinkAnnotationWidget.prototype.setDisabled = function () {
	// Parent method
	ve.ui.LinkAnnotationWidget.super.prototype.setDisabled.apply( this, arguments );

	this.getTextInputWidget().setDisabled( this.isDisabled() );
	return this;
};

/**
 * Handle value-changing events from the text input
 *
 * @param {string} value New input value
 */
ve.ui.LinkAnnotationWidget.prototype.onTextChange = function ( value ) {
	var widget = this;

	// RTL/LTR check
	// TODO: Make this work properly
	if ( document.body.classList.contains( 'rtl' ) ) {
		var isExt = ve.init.platform.getExternalLinkUrlProtocolsRegExp().test( value.trim() );
		// If URL is external, flip to LTR. Otherwise, set back to RTL
		this.getTextInputWidget().setDir( isExt ? 'ltr' : 'rtl' );
	}

	this.getTextInputWidget().getValidity()
		.done( function () {
			widget.setAnnotation( widget.constructor.static.getAnnotationFromText( value ), true );
		} )
		.fail( function () {
			widget.setAnnotation( null, true );
		} );
};

/**
 * Sets the annotation value.
 *
 * The input value will automatically be updated.
 *
 * @param {ve.dm.LinkAnnotation|null} annotation Link annotation
 * @param {boolean} [fromText] Annotation was generated from text input
 * @return {ve.ui.LinkAnnotationWidget}
 * @chainable
 */
ve.ui.LinkAnnotationWidget.prototype.setAnnotation = function ( annotation, fromText ) {
	if ( ve.compare(
		annotation ? annotation.getComparableObject() : {},
		this.annotation ? this.annotation.getComparableObject() : {}
	) ) {
		// No change
		return this;
	}

	this.annotation = annotation;

	// If this method was triggered by a change to the text input, leave it alone.
	if ( !fromText ) {
		this.getTextInputWidget().setValue( this.constructor.static.getTextFromAnnotation( annotation ) );
	}

	this.emit( 'change', this.annotation );

	return this;
};

/**
 * Gets the annotation value.
 *
 * @return {ve.dm.LinkAnnotation} Link annotation
 */
ve.ui.LinkAnnotationWidget.prototype.getAnnotation = function () {
	return this.annotation;
};

/**
 * Get the hyperlink location.
 *
 * @return {string} Hyperlink location
 */
ve.ui.LinkAnnotationWidget.prototype.getHref = function () {
	return this.constructor.static.getTextFromAnnotation( this.annotation );
};

/**
 * Set the read-only state of the widget
 *
 * @param {boolean} readOnly Make widget read-only
 * @return {ve.ui.LinkAnnotationWidget}
 * @chainable
 */
ve.ui.LinkAnnotationWidget.prototype.setReadOnly = function ( readOnly ) {
	this.input.setReadOnly( readOnly );
	return this;
};
