/*!
 * VisualEditor user interface MWTransclusionContentPage class.
 *
 * @copyright See AUTHORS.txt
 * @license The MIT License (MIT); see LICENSE.txt
 */

/**
 * Template dialog content pane input for a raw wikitext snippet.
 *
 * @class
 * @extends OO.ui.PageLayout
 *
 * @constructor
 * @param {ve.dm.MWTransclusionContentModel} content
 * @param {string} name Unique symbolic name of page
 * @param {Object} [config] Configuration options
 * @cfg {jQuery} [$overlay] Overlay to render dropdowns in
 * @cfg {boolean} [isReadOnly] Page is read-only
 */
ve.ui.MWTransclusionContentPage = function VeUiMWTransclusionContentPage( content, name, config ) {
	// Configuration initialization
	config = ve.extendObject( {
		scrollable: false
	}, config );

	// Parent constructor
	ve.ui.MWTransclusionContentPage.super.call( this, name, config );

	// Properties
	this.content = content;
	this.textInput = new ve.ui.MWLazyMultilineTextInputWidget( {
		autosize: true,
		classes: [ 've-ui-mwTransclusionDialog-input' ]
	} )
		.setValue( this.content.serialize() )
		.setReadOnly( config.isReadOnly )
		.connect( this, { change: 'onTextInputChange' } );
	this.valueFieldset = new OO.ui.FieldsetLayout( {
		label: ve.msg( 'visualeditor-dialog-transclusion-wikitext' ),
		icon: 'wikiText',
		$content: this.textInput.$element
	} );

	// Initialization
	this.$element
		.addClass( 've-ui-mwTransclusionContentPage' )
		.append( this.valueFieldset.$element );
};

/* Inheritance */

OO.inheritClass( ve.ui.MWTransclusionContentPage, OO.ui.PageLayout );

/* Methods */

/**
 * @private
 */
ve.ui.MWTransclusionContentPage.prototype.onTextInputChange = function () {
	this.content.setWikitext( this.textInput.getValue() );
};
