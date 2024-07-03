/*!
 * VisualEditor user interface MWParameterPage class.
 *
 * @copyright See AUTHORS.txt
 * @license The MIT License (MIT); see LICENSE.txt
 */

/**
 * Container for editing the value of a parameter in the template dialog
 * content pane.  Includes a dynamic value input depending on the parameter's
 * type documented in TemplateData.
 *
 * @class
 * @extends OO.ui.PageLayout
 *
 * @constructor
 * @param {ve.dm.MWParameterModel} parameter Template parameter
 * @param {Object} [config] Configuration options
 * @cfg {jQuery} [$overlay] Overlay to render dropdowns in
 * @cfg {boolean} [readOnly] Parameter is read-only
 */
ve.ui.MWParameterPage = function VeUiMWParameterPage( parameter, config ) {
	var paramName = parameter.getName();

	// Configuration initialization
	config = ve.extendObject( {
		scrollable: false
	}, config );

	// Parent constructor
	ve.ui.MWParameterPage.super.call( this, parameter.getId(), config );

	// Properties
	this.edited = false;
	this.parameter = parameter;
	this.spec = parameter.getTemplate().getSpec();
	this.defaultValue = parameter.getDefaultValue();
	this.exampleValue = parameter.getExampleValue();
	this.hasValue = null;

	this.$info = $( '<div>' );
	this.$field = $( '<div>' );

	// Construct the field docs for the template description
	var $doc = $( '<div>' )
		.attr( 'id', OO.ui.generateElementId() )
		.addClass( 've-ui-mwParameterPage-doc' );
	var description = this.spec.getParameterDescription( paramName );
	if ( description ) {
		$( '<p>' ).text( description ).appendTo( $doc );
	}

	// Note: Calling createValueInput() sets some properties we rely on later in this function
	this.valueInput = this.createValueInput()
		.setValue( this.parameter.getValue() )
		.connect( this, { change: 'onValueInputChange' } );

	this.valueInput.$input.attr( 'aria-describedby', $doc.attr( 'id' ) );

	if ( config.readOnly && this.valueInput.setReadOnly ) {
		this.valueInput.setReadOnly( true );
	}

	var labelElement = new OO.ui.LabelWidget( {
		input: this.valueInput,
		label: this.spec.getParameterLabel( paramName ),
		classes: [ 've-ui-mwParameterPage-label' ]
	} );

	var statusIndicator;
	if ( this.parameter.isRequired() ) {
		$( '<p>' )
			.addClass( 've-ui-mwParameterPage-doc-required' )
			.text( ve.msg( 'visualeditor-dialog-transclusion-required-parameter-description' ) )
			.appendTo( $doc );
	} else if ( this.parameter.isDeprecated() ) {
		statusIndicator = new OO.ui.IndicatorWidget( {
			classes: [ 've-ui-mwParameterPage-statusIndicator' ],
			indicator: 'alert',
			title: ve.msg( 'visualeditor-dialog-transclusion-deprecated-parameter' )
		} );
		$( '<p>' )
			.addClass( 've-ui-mwParameterPage-doc-deprecated' )
			.text( ve.msg(
				'visualeditor-dialog-transclusion-deprecated-parameter-description',
				this.spec.getParameterDeprecationDescription( paramName )
			) )
			.appendTo( $doc );
	}

	if ( this.defaultValue ) {
		$( '<p>' )
			.addClass( 've-ui-mwParameterPage-doc-default' )
			.text( ve.msg( 'visualeditor-dialog-transclusion-param-default', this.defaultValue ) )
			.appendTo( $doc );
	}

	if ( this.exampleValue ) {
		$( '<p>' )
			.addClass( 've-ui-mwParameterPage-doc-example' )
			.text( ve.msg(
				'visualeditor-dialog-transclusion-param-example-long',
				this.exampleValue
			) )
			.appendTo( $doc );
	}

	// Initialization
	this.$info
		.addClass( 've-ui-mwParameterPage-info' )
		.append( labelElement.$element );
	if ( statusIndicator ) {
		this.$info.append( ' ', statusIndicator.$element );
	}
	this.$field
		.addClass( 've-ui-mwParameterPage-field' )
		.append(
			this.valueInput.$element
		);

	if ( !this.parameter.isDocumented() ) {
		$( '<span>' )
			.addClass( 've-ui-mwParameterPage-undocumentedLabel' )
			.text( ve.msg( 'visualeditor-dialog-transclusion-param-undocumented' ) )
			.appendTo( labelElement.$element );
	}

	this.$element
		.addClass( 've-ui-mwParameterPage' )
		.append( this.$info, this.$field );

	if ( $doc.children().length ) {
		this.$field.addClass( 've-ui-mwParameterPage-inlineDescription' );
		this.collapsibleDoc = new ve.ui.MWExpandableContentElement( {
			classes: [ 've-ui-mwParameterPage-inlineDescription' ],
			$content: $doc
		} );
		this.$info.after( this.collapsibleDoc.$element );
	}
};

/* Inheritance */

OO.inheritClass( ve.ui.MWParameterPage, OO.ui.PageLayout );

/* Events */

/**
 * Triggered when the parameter value changes between empty and not empty.
 *
 * @event hasValueChange
 * @param string parameterId Keyed by unique id of the parameter, e.g. something
 *  like "part_1/param1".
 * @param boolean hasValue
 */

/* Methods */

/**
 * Get default configuration for an input widget.
 *
 * @private
 * @return {Object}
 */
ve.ui.MWParameterPage.prototype.getDefaultInputConfig = function () {
	var valueInputConfig = {
		autosize: true,
		required: this.parameter.isRequired()
	};

	if ( this.defaultValue ) {
		valueInputConfig.placeholder = ve.msg(
			'visualeditor-dialog-transclusion-param-default',
			this.defaultValue
		);
	}

	return valueInputConfig;
};

/**
 * Create a value input widget based on the parameter type and whether it is
 * required or not.
 *
 * @private
 * @return {OO.ui.InputWidget}
 */
ve.ui.MWParameterPage.prototype.createValueInput = function () {
	var type = this.parameter.getType(),
		value = this.parameter.getValue(),
		valueInputConfig = this.getDefaultInputConfig();

	// TODO:
	// * date - T100206
	// * number - T124850
	// * unbalanced-wikitext/content - T106242
	// * string? - T124917
	if (
		type === 'wiki-page-name' &&
		( value === '' || mw.Title.newFromText( value ) )
	) {
		return new mw.widgets.TitleInputWidget( ve.extendObject( {
			api: ve.init.target.getContentApi()
		}, valueInputConfig ) );
	} else if (
		type === 'wiki-file-name' &&
		( value === '' || mw.Title.newFromText( value ) )
	) {
		return new mw.widgets.TitleInputWidget( ve.extendObject( {}, valueInputConfig, {
			api: ve.init.target.getContentApi(),
			namespace: 6,
			showImages: true
		} ) );
	} else if (
		type === 'wiki-user-name' &&
		( value === '' || mw.Title.newFromText( value ) )
	) {
		valueInputConfig.validate = function ( val ) {
			// TODO: Check against wgMaxNameChars
			// TODO: Check against unicode validation regex from MW core's User::isValidUserName
			return !!mw.Title.newFromText( val );
		};
		return new mw.widgets.UserInputWidget( ve.extendObject( {
			api: ve.init.target.getContentApi()
		}, valueInputConfig ) );
	} else if (
		type === 'wiki-template-name' &&
		( value === '' || mw.Title.newFromText( value ) )
	) {
		return new mw.widgets.TitleInputWidget( ve.extendObject( {
			api: ve.init.target.getContentApi()
		}, valueInputConfig, {
			namespace: mw.config.get( 'wgNamespaceIds' ).template
		} ) );
	} else if ( type === 'boolean' && ( value === '1' || value === '0' ) ) {
		return new ve.ui.MWParameterCheckboxInputWidget( valueInputConfig );
	} else if (
		type === 'url' &&
		(
			value === '' ||
			ve.init.platform.getExternalLinkUrlProtocolsRegExp().exec( value.trim() )
		)
	) {
		return ve.ui.MWExternalLinkAnnotationWidget.static.createExternalLinkInputWidget( valueInputConfig );
	} else if (
		this.parameter.getSuggestedValues().length &&
		this.isSuggestedValueType( type )
	) {
		valueInputConfig.menu = { filterFromInput: true, highlightOnFilter: true };
		valueInputConfig.options =
			this.parameter.getSuggestedValues().filter( function ( suggestedValue ) {
				// This wasn't validated for a while, existing templates can do anything here
				return typeof suggestedValue === 'string';
			} ).map( function ( suggestedValue ) {
				return { data: suggestedValue, label: suggestedValue || '\xA0' };
			} );
		return new OO.ui.ComboBoxInputWidget( valueInputConfig );
	} else if ( type !== 'line' || value.indexOf( '\n' ) !== -1 ) {
		// If the type is line, but there are already newlines in the provided
		// value, don't break the existing content by only providing a single-
		// line field. (This implies that the TemplateData for the field isn't
		// complying with its use in practice...)
		return new ve.ui.MWLazyMultilineTextInputWidget( valueInputConfig );
	}

	// Wrapping single line input (T348482)
	return new ve.ui.MWLazyMultilineTextInputWidget( ve.extendObject( {
		rows: 1,
		autosize: true,
		allowLinebreaks: false
	}, valueInputConfig ) );
};

/**
 * Whether or not to show suggested values for a given parameter type
 *
 * @private
 * @param {string} type Parameter type
 * @return {boolean} True if suggested values should be shown
 */
ve.ui.MWParameterPage.prototype.isSuggestedValueType = function ( type ) {
	return [ 'unknown', 'content', 'line', 'string', 'number', 'unbalanced-wikitext' ].indexOf( type ) > -1;
};

/**
 * @private
 * @return {boolean} True if there is either user-provided input or a default value
 */
ve.ui.MWParameterPage.prototype.containsSomeValue = function () {
	// Note: For templates that allow overriding a default value with nothing, the empty string is
	// meaningful user input. For templates that don't, the parameter can never be truly empty.
	return !!( this.valueInput.getValue() || this.defaultValue );
};

/**
 * Handle change events from the value input
 *
 * @private
 * @param {string} value
 */
ve.ui.MWParameterPage.prototype.onValueInputChange = function () {
	var value = this.valueInput.getValue();

	if ( !this.edited ) {
		ve.track( 'activity.transclusion', { action: 'edit-parameter-value' } );
	}
	this.edited = true;
	this.parameter.setValue( value );

	if ( !!value !== this.hasValue ) {
		this.hasValue = !!value;
		this.emit( 'hasValueChange', this.parameter.getId(), this.hasValue );
	}
};

/**
 * @inheritdoc
 */
ve.ui.MWParameterPage.prototype.focus = function () {
	this.valueInput.focus();
};

/**
 * Refresh collapsible children.
 */
ve.ui.MWParameterPage.prototype.updateSize = function () {
	if ( this.collapsibleDoc ) {
		this.collapsibleDoc.updateSize();
	}
};
