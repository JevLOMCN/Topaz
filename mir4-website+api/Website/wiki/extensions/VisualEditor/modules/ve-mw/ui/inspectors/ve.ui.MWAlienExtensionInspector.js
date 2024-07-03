/*!
 * VisualEditor UserInterface MWAlienExtensionInspector class.
 *
 * @copyright See AUTHORS.txt
 * @license The MIT License (MIT); see LICENSE.txt
 */

/**
 * Inspector for editing alienated MediaWiki extensions.
 *
 * @class
 * @extends ve.ui.MWExtensionInspector
 *
 * @constructor
 * @param {Object} [config] Configuration options
 */
ve.ui.MWAlienExtensionInspector = function VeUiMWAlienExtensionInspector() {
	// Parent constructor
	ve.ui.MWAlienExtensionInspector.super.apply( this, arguments );

	// Properties
	this.attributeInputs = {};
	this.$attributes = null;
};

/* Inheritance */

OO.inheritClass( ve.ui.MWAlienExtensionInspector, ve.ui.MWExtensionInspector );

/* Static properties */

ve.ui.MWAlienExtensionInspector.static.name = 'alienExtension';

ve.ui.MWAlienExtensionInspector.static.allowedEmpty = true;

ve.ui.MWAlienExtensionInspector.static.modelClasses = [
	ve.dm.MWAlienInlineExtensionNode,
	ve.dm.MWAlienBlockExtensionNode
];

/* Methods */

/**
 * @inheritdoc
 */
ve.ui.MWAlienExtensionInspector.prototype.initialize = function () {
	// Parent method
	ve.ui.MWAlienExtensionInspector.super.prototype.initialize.apply( this, arguments );

	this.$attributes = $( '<div>' ).addClass( 've-ui-mwAlienExtensionInspector-attributes' );
	this.form.$element.append( this.$attributes );
};

/**
 * @inheritdoc
 */
ve.ui.MWAlienExtensionInspector.prototype.getSetupProcess = function ( data ) {
	return ve.ui.MWAlienExtensionInspector.super.prototype.getSetupProcess.call( this, data )
		.next( function () {
			var attributes = this.selectedNode.getAttribute( 'mw' ).attrs;

			if ( attributes && !ve.isEmptyObject( attributes ) ) {
				for ( var key in attributes ) {
					var attributeInput = new OO.ui.TextInputWidget( {
						value: attributes[ key ]
					} );
					attributeInput.connect( this, { change: 'onChangeHandler' } );
					this.attributeInputs[ key ] = attributeInput;
					var field = new OO.ui.FieldLayout(
						attributeInput,
						{
							align: 'left',
							label: key
						}
					);
					this.$attributes.append( field.$element );
				}
			}

			this.title.setLabel( this.selectedNode.getExtensionName() );
		}, this );
};

/**
 * @inheritdoc
 */
ve.ui.MWAlienExtensionInspector.prototype.getTeardownProcess = function ( data ) {
	return ve.ui.MWAlienExtensionInspector.super.prototype.getTeardownProcess.call( this, data )
		.next( function () {
			this.$attributes.empty();
			this.attributeInputs = {};
		}, this );
};

/**
 * @inheritdoc ve.ui.MWExtensionWindow
 */
ve.ui.MWAlienExtensionInspector.prototype.updateMwData = function ( mwData ) {
	// Parent method
	ve.ui.MWAlienExtensionInspector.super.prototype.updateMwData.call( this, mwData );

	if ( !ve.isEmptyObject( this.attributeInputs ) ) {
		// Make sure we have an attrs object to populate
		mwData.attrs = mwData.attrs || {};
		for ( var key in this.attributeInputs ) {
			mwData.attrs[ key ] = this.attributeInputs[ key ].getValue();
		}
	}
};

/* Registration */

ve.ui.windowFactory.register( ve.ui.MWAlienExtensionInspector );
