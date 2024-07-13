/*!
 * VisualEditor LanguageContextItem class.
 *
 * @copyright See AUTHORS.txt
 */

/**
 * Context item for a language.
 *
 * @class
 * @extends ve.ui.AnnotationContextItem
 *
 * @param {ve.ui.LinearContext} context Context the item is in
 * @param {ve.dm.Model} model Model the item is related to
 * @param {Object} [config] Configuration options
 */
ve.ui.LanguageContextItem = function VeUiLanguageContextItem( context, model, config ) {
	// Parent constructor
	ve.ui.LanguageContextItem.super.call( this, context, model, config );

	// Initialization
	this.$element.addClass( 've-ui-languageContextItem' );
};

/* Inheritance */

OO.inheritClass( ve.ui.LanguageContextItem, ve.ui.AnnotationContextItem );

/* Static Properties */

ve.ui.LanguageContextItem.static.name = 'language';

ve.ui.LanguageContextItem.static.icon = 'textLanguage';

ve.ui.LanguageContextItem.static.label = OO.ui.deferMsg( 'visualeditor-languageinspector-title' );

ve.ui.LanguageContextItem.static.modelClasses = [ ve.dm.LanguageAnnotation ];

ve.ui.LanguageContextItem.static.embeddable = false;

ve.ui.LanguageContextItem.static.commandName = 'language';

ve.ui.LanguageContextItem.static.clearMsg = OO.ui.deferMsg( 'visualeditor-languagecontext-remove' );

/* Methods */

/**
 * @inheritdoc
 */
ve.ui.LanguageContextItem.prototype.getDescription = function () {
	return ve.ce.LanguageAnnotation.static.getDescription( this.model );
};

/* Registration */

ve.ui.contextItemFactory.register( ve.ui.LanguageContextItem );
