/*!
 * VisualEditor UserInterface MediaSizeWidget class.
 *
 * @copyright See AUTHORS.txt
 */

/**
 * Widget that lets the user edit dimensions (width and height),
 * based on a scalable object.
 *
 * @class
 * @extends OO.ui.Widget
 *
 * @constructor
 * @param {ve.dm.Scalable} [scalable]
 * @param {Object} [config] Configuration options
 * @cfg {boolean} [noDefaultDimensions] The item being sized doesn't have default dimensions
 * @cfg {string} [dimensionsAlign] Alignment for the dimensions widget
 */
ve.ui.MediaSizeWidget = function VeUiMediaSizeWidget( scalable, config ) {
	// Configuration
	config = config || {};

	this.scalable = scalable;

	// Parent constructor
	ve.ui.MediaSizeWidget.super.call( this, config );

	// Properties
	this.ratio = {};
	this.currentDimensions = {};
	this.maxDimensions = {};
	this.valid = null;
	this.noDefaultDimensions = !!config.noDefaultDimensions;
	this.dimensionsAlign = config.dimensionsAlign || 'right';

	// Define button select widget
	this.sizeTypeSelect = new OO.ui.ButtonSelectWidget( {
		classes: [ 've-ui-mediaSizeWidget-section-sizetype' ]
	} );
	this.sizeTypeSelect.addItems( [
		new OO.ui.ButtonOptionWidget( {
			data: 'default',
			label: ve.msg( 'visualeditor-mediasizewidget-sizeoptions-default' )
		} ),
		// TODO: when upright is supported by Parsoid
		// new OO.ui.ButtonOptionWidget( {
		// data: 'scale',
		// label: ve.msg( 'visualeditor-mediasizewidget-sizeoptions-scale' )
		// } ),
		new OO.ui.ButtonOptionWidget( {
			data: 'custom',
			label: ve.msg( 'visualeditor-mediasizewidget-sizeoptions-custom' )
		} )
	] );
	var sizeTypeField = new OO.ui.FieldLayout( this.sizeTypeSelect );

	// Define scale
	/*
	this.scaleInput = new OO.ui.TextInputWidget();
	scalePercentLabel = new OO.ui.LabelWidget( {
		input: this.scaleInput,
		label: ve.msg( 'visualeditor-mediasizewidget-label-scale-percent' )
	} );
	*/

	this.dimensions = new ve.ui.DimensionsWidget( { validate: this.isValid.bind( this ) } );

	// Error label is available globally so it can be displayed and
	// hidden as needed
	this.errorLabel = new OO.ui.LabelWidget( {
		label: ve.msg( 'visualeditor-mediasizewidget-label-defaulterror' )
	} );

	// Field layouts
	/*
	scaleField = new OO.ui.FieldLayout(
		this.scaleInput, {
			align: 'right',
			// TODO: when upright is supported by Parsoid
			// classes: ['ve-ui-mediaSizeWidget-section-scale'],
			label: ve.msg( 'visualeditor-mediasizewidget-label-scale' )
		}
	);
	TODO: when upright is supported by Parsoid
	this.scaleInput.$element.append( scalePercentLabel.$element );
	*/
	var dimensionsField = new OO.ui.FieldLayout(
		this.dimensions, {
			align: this.dimensionsAlign,
			classes: [ 've-ui-mediaSizeWidget-section-custom' ]
		}
	);

	// Build GUI
	this.$element.addClass( 've-ui-mediaSizeWidget' );
	if ( !this.noDefaultDimensions ) {
		this.$element.append( sizeTypeField.$element );
	}
	this.$element.append( dimensionsField.$element );
	// TODO: when upright is supported by Parsoid
	// this.$element.append( scaleField.$element );
	this.$element.append(
		$( '<div>' )
			.addClass( 've-ui-mediaSizeWidget-label-error' )
			.append( this.errorLabel.$element )
	);

	// Events
	this.dimensions.connect( this, {
		widthChange: [ 'onDimensionsChange', 'width' ],
		heightChange: [ 'onDimensionsChange', 'height' ]
	} );
	// TODO: when upright is supported by Parsoid
	// this.scaleInput.connect( this, { change: 'onScaleChange' } );
	this.sizeTypeSelect.connect( this, { choose: 'onSizeTypeChoose' } );

};

/* Inheritance */

OO.inheritClass( ve.ui.MediaSizeWidget, OO.ui.Widget );

/* Events */

/**
 * @event change
 * @param {Object} dimensions Width and height dimensions
 */

/**
 * @event valid
 * @param {boolean} isValid Current dimensions are valid
 */

/**
 * @event changeSizeType
 * @param {string} sizeType 'default', 'custom' or 'scale'
 */

/* Methods */

/**
 * Respond to change in original dimensions in the scalable object.
 * Specifically, enable or disable the 'default' option.
 *
 * @param {Object} dimensions Original dimensions
 */
ve.ui.MediaSizeWidget.prototype.onScalableOriginalSizeChange = function () {
	// Revalidate current dimensions
	this.updateDisabled();
	this.validateDimensions();
};

/**
 * Respond to change in current dimensions in the scalable object.
 *
 * @param {Object} dimensions Original dimensions
 */
ve.ui.MediaSizeWidget.prototype.onScalableCurrentSizeChange = function ( dimensions ) {
	if ( !ve.isEmptyObject( dimensions ) ) {
		this.setCurrentDimensions( dimensions );
		this.validateDimensions();
	}
};

/**
 * Respond to default size or status change in the scalable object.
 *
 * @param {boolean} isDefault Current default state
 */
ve.ui.MediaSizeWidget.prototype.onScalableDefaultSizeChange = function ( isDefault ) {
	// Update the default size into the dimensions widget
	this.updateDefaultDimensions();
	// TODO: When 'scale' ('upright' support) is ready, this will need to be adjusted
	// to support that as well
	this.setSizeType(
		isDefault ?
			'default' :
			'custom'
	);
	this.validateDimensions();
};

/**
 * Respond to width/height input value change. Only update dimensions if
 * the value is numeric. Invoke validation for every change.
 *
 * This is triggered every time the dimension widget has its values changed
 * either by the user or externally. The external call to 'setCurrentDimensions'
 * will result in this event being evoked if the dimension inputs have changed,
 * and same with changing dimensions type.
 *
 * The 'change' event for the entire widget is emitted through this method, as
 * it means that the actual values have changed, regardless of whether they
 * are valid or not.
 *
 * @param {string} type The input that was updated, 'width' or 'height'
 * @param {string} value The new value of the input
 * @fires change
 */
ve.ui.MediaSizeWidget.prototype.onDimensionsChange = function ( type, value ) {
	if ( +value === 0 && !this.noDefaultDimensions ) {
		this.setSizeType( 'default' );
	} else {
		this.setSizeType( 'custom' );
		if ( !isNaN( +value ) ) {
			var dimensions = {};
			dimensions[ type ] = +value;
			this.setCurrentDimensions( dimensions );
		} else {
			this.validateDimensions();
		}
	}
};

// /**
//  * Respond to change of the scale input
//  */
/*
ve.ui.MediaSizeWidget.prototype.onScaleChange = function () {
	// If the input changed (and not empty), set to 'custom'
	// Otherwise, set to 'default'
	if ( !this.dimensions.isEmpty() ) {
		this.sizeTypeSelect.selectItemByData( 'scale' );
	} else {
		this.sizeTypeSelect.selectItemByData( 'default' );
	}
};
*/

/**
 * Respond to size type change
 *
 * @param {OO.ui.OptionWidget} item Selected size type item
 * @fires changeSizeType
 */
ve.ui.MediaSizeWidget.prototype.onSizeTypeChoose = function ( item ) {
	var selectedType = item.getData(),
		wasDefault = this.scalable.isDefault();

	this.scalable.toggleDefault( selectedType === 'default' );

	if ( selectedType === 'default' ) {
		// If there are defaults, put them into the values
		if ( !ve.isEmptyObject( this.dimensions.getDefaults() ) ) {
			this.dimensions.clear();
		}
	} else if ( selectedType === 'custom' ) {
		// If we were default size before, set the current dimensions to the default size
		if ( wasDefault && !ve.isEmptyObject( this.dimensions.getDefaults() ) ) {
			this.setCurrentDimensions( this.dimensions.getDefaults() );
		}
		this.validateDimensions();
	}

	this.emit( 'changeSizeType', selectedType );
	this.updateDisabled();
	this.validateDimensions();
};

// /**
//  * Set the placeholder value of the scale input
//  *
//  * @param {number} value Placeholder value
//  * @chainable
//  * @return {ve.ui.MediaSizeWidget}
//  */
/*
ve.ui.MediaSizeWidget.prototype.setScalePlaceholder = function ( value ) {
	this.scaleInput.$element.prop( 'placeholder', value );
	return this;
};
*/

// /**
//  * Get the placeholder value of the scale input
//  *
//  * @return {string} Placeholder value
//  */
/*
ve.ui.MediaSizeWidget.prototype.getScalePlaceholder = function () {
	return this.scaleInput.$element.prop( 'placeholder' );
};
*/

/**
 * Select a size type in the select widget
 *
 * @param {string} sizeType The size type to select
 * @chainable
 * @return {ve.ui.MediaSizeWidget}
 */
ve.ui.MediaSizeWidget.prototype.setSizeType = function ( sizeType ) {
	if (
		this.getSizeType() !== sizeType ||
		// If the dimensions widget has zeros make sure to
		// allow for the change in size type
		+this.dimensions.getWidth() === 0 ||
		+this.dimensions.getHeight() === 0
	) {
		this.sizeTypeSelect.chooseItem(
			this.sizeTypeSelect.findItemFromData( sizeType )
		);
	}
	return this;
};
/**
 * Get the size type from the select widget
 *
 * @return {string} The size type
 */
ve.ui.MediaSizeWidget.prototype.getSizeType = function () {
	return this.sizeTypeSelect.findSelectedItem() ? this.sizeTypeSelect.findSelectedItem().getData() : '';
};

/**
 * Set the scalable object the widget deals with
 *
 * @param {ve.dm.Scalable} scalable A scalable object representing the media source being resized.
 * @chainable
 * @return {ve.ui.MediaSizeWidget}
 */
ve.ui.MediaSizeWidget.prototype.setScalable = function ( scalable ) {
	if ( this.scalable instanceof ve.dm.Scalable ) {
		this.scalable.disconnect( this );
	}
	this.scalable = scalable;
	// Events
	this.scalable.connect( this, {
		defaultSizeChange: 'onScalableDefaultSizeChange',
		originalSizeChange: 'onScalableOriginalSizeChange',
		currentSizeChange: 'onScalableCurrentSizeChange'
	} );

	this.updateDefaultDimensions();

	if ( !this.scalable.isDefault() ) {
		// Reset current dimensions to new scalable object
		this.setCurrentDimensions( this.scalable.getCurrentDimensions() );
	}

	// Call for the set size type according to default or custom settings of the scalable
	if ( this.scalable.getOriginalDimensions() ) {
		this.setSizeType( this.scalable.isDefault() ? 'default' : 'custom' );
	}
	this.updateDisabled();
	this.validateDimensions();
	return this;
};

/**
 * Get the attached scalable object
 *
 * @return {ve.dm.Scalable} The scalable object representing the media
 * source being resized.
 */
ve.ui.MediaSizeWidget.prototype.getScalable = function () {
	return this.scalable;
};

/**
 * Set the image aspect ratio explicitly
 *
 * @param {number} ratio Numerical value of an aspect ratio
 * @chainable
 * @return {ve.ui.MediaSizeWidget}
 */
ve.ui.MediaSizeWidget.prototype.setRatio = function ( ratio ) {
	this.scalable.setRatio( ratio );
	return this;
};

/**
 * Get the current aspect ratio
 *
 * @return {number} Aspect ratio
 */
ve.ui.MediaSizeWidget.prototype.getRatio = function () {
	return this.scalable.getRatio();
};

/**
 * Set the maximum dimensions for the image. These will be limited only if
 * enforcedMax is true.
 *
 * @param {Object} dimensions Height and width
 * @chainable
 * @return {ve.ui.MediaSizeWidget}
 */
ve.ui.MediaSizeWidget.prototype.setMaxDimensions = function ( dimensions ) {
	// Normalize dimensions before setting
	var maxDimensions = ve.dm.Scalable.static.getDimensionsFromValue( dimensions, this.scalable.getRatio() );
	this.scalable.setMaxDimensions( maxDimensions );
	return this;
};

/**
 * Retrieve the currently defined maximum dimensions
 *
 * @return {Object} dimensions Height and width
 */
ve.ui.MediaSizeWidget.prototype.getMaxDimensions = function () {
	return this.scalable.getMaxDimensions();
};

/**
 * Retrieve the current dimensions
 *
 * @return {Object} Width and height
 */
ve.ui.MediaSizeWidget.prototype.getCurrentDimensions = function () {
	return this.currentDimensions;
};

/**
 * @inheritdoc
 */
ve.ui.MediaSizeWidget.prototype.setDisabled = function ( disabled ) {
	// Parent method
	ve.ui.MediaSizeWidget.super.prototype.setDisabled.call( this, disabled );

	this.updateDisabled();
	return this;
};

/**
 * Update the disabled state of sub widgets
 *
 * @chainable
 * @return {ve.ui.MediaSizeWidget}
 */
ve.ui.MediaSizeWidget.prototype.updateDisabled = function () {
	var disabled = this.isDisabled();

	// The 'updateDisabled' method may called before the widgets
	// are fully defined. So, before disabling/enabling anything,
	// make sure the objects exist
	if ( this.sizeTypeSelect &&
		this.dimensions &&
		this.scalable
	) {
		var sizeType = this.getSizeType();

		// Disable the type select
		this.sizeTypeSelect.setDisabled( disabled );

		// Disable the default type options
		this.sizeTypeSelect.findItemFromData( 'default' ).setDisabled(
			ve.isEmptyObject( this.scalable.getDefaultDimensions() )
		);

		// Disable the dimensions widget
		this.dimensions.setDisabled( disabled || sizeType !== 'custom' );

		// Disable the scale widget
		// this.scaleInput.setDisabled( disabled || sizeType !== 'scale' );
	}
	return this;
};

/**
 * Updates the current dimensions in the inputs, either one at a time or both
 *
 * @param {Object} dimensions Dimensions with width and height
 * @fires change
 */
ve.ui.MediaSizeWidget.prototype.setCurrentDimensions = function ( dimensions ) {
	// Recursion protection
	if ( this.preventChangeRecursion ) {
		return;
	}
	this.preventChangeRecursion = true;

	if ( !this.scalable.isFixedRatio() ) {
		dimensions = ve.extendObject( {}, this.getCurrentDimensions(), dimensions );
	}

	// Normalize the new dimensions
	var normalizedDimensions = ve.dm.Scalable.static.getDimensionsFromValue( dimensions, this.scalable.getRatio() );

	if (
		// Update only if the dimensions object is valid
		ve.dm.Scalable.static.isDimensionsObjectValid( normalizedDimensions ) &&
		// And only if the dimensions object is not default
		!this.scalable.isDefault()
	) {
		this.currentDimensions = normalizedDimensions;
		// This will only update if the value has changed
		// Set width & height individually as they may be 0
		this.dimensions.setWidth( this.currentDimensions.width );
		this.dimensions.setHeight( this.currentDimensions.height );

		// Update scalable object
		this.scalable.setCurrentDimensions( this.currentDimensions );

		this.validateDimensions();
		// Emit change event
		this.emit( 'change', this.currentDimensions );
	}
	this.preventChangeRecursion = false;
};

/**
 * Validate current dimensions.
 * Explicitly call for validating the current dimensions. This is especially
 * useful if we've changed conditions for the widget, like limiting image
 * dimensions for thumbnails when the image type changes. Triggers the error
 * class if needed.
 *
 * @return {boolean} Current dimensions are valid
 */
ve.ui.MediaSizeWidget.prototype.validateDimensions = function () {
	var isValid = this.isValid();

	if ( this.valid !== isValid ) {
		this.valid = isValid;
		this.errorLabel.toggle( !isValid );
		this.dimensions.setValidityFlag();
		// Emit change event
		this.emit( 'valid', this.valid );
	}
	return isValid;
};

/**
 * Set default dimensions for the widget. Values are given by scalable's
 * defaultDimensions. If no default dimensions are available,
 * the defaults are removed.
 */
ve.ui.MediaSizeWidget.prototype.updateDefaultDimensions = function () {
	var defaultDimensions = this.scalable.getDefaultDimensions();

	if ( !ve.isEmptyObject( defaultDimensions ) ) {
		this.dimensions.setDefaults( defaultDimensions );
	} else {
		this.dimensions.removeDefaults();
	}
	this.updateDisabled();
	this.validateDimensions();
};

/**
 * Check if the custom dimensions are empty.
 *
 * @return {boolean} Both width/height values are empty
 */
ve.ui.MediaSizeWidget.prototype.isCustomEmpty = function () {
	return this.dimensions.isEmpty();
};

// /**
//  * Check if the scale input is empty.
//  *
//  * @return {boolean} Scale input value is empty
//  */
/*
ve.ui.MediaSizeWidget.prototype.isScaleEmpty = function () {
	return ( this.scaleInput.getValue() === '' );
};
*/

/**
 * Check if all inputs are empty.
 *
 * @return {boolean} All input values are empty
 */
ve.ui.MediaSizeWidget.prototype.isEmpty = function () {
	return this.isCustomEmpty();
	// return this.isCustomEmpty() && this.isScaleEmpty();
};

/**
 * Check whether the current value inputs are valid
 * 1. If placeholders are visible, the input is valid
 * 2. If inputs have non numeric values, input is invalid
 * 3. If inputs have numeric values, validate through scalable
 *    calculations to see if the dimensions follow the rules.
 *
 * @return {boolean} Valid or invalid dimension values
 */
ve.ui.MediaSizeWidget.prototype.isValid = function () {
	var itemType = this.sizeTypeSelect.findSelectedItem() ?
		this.sizeTypeSelect.findSelectedItem().getData() : 'custom';

	// TODO: when upright is supported by Parsoid add validation for scale

	if ( itemType === 'custom' ) {
		if (
			this.dimensions.getDefaults() &&
			this.dimensions.isEmpty()
		) {
			return true;
		} else if (
			!isNaN( +this.dimensions.getWidth() ) &&
			!isNaN( +this.dimensions.getHeight() )
		) {
			return this.scalable.isCurrentDimensionsValid();
		} else {
			return false;
		}
	} else {
		// Default images are always valid size
		return true;
	}
};
