/*!
 * VisualEditor UserInterface MWInternalLinkAnnotationWidget class.
 *
 * @copyright See AUTHORS.txt
 * @license The MIT License (MIT); see LICENSE.txt
 */

/**
 * Creates an ve.ui.MWInternalLinkAnnotationWidget object.
 *
 * @class
 * @extends ve.ui.LinkAnnotationWidget
 *
 * @constructor
 * @param {Object} [config] Configuration options
 */
ve.ui.MWInternalLinkAnnotationWidget = function VeUiMWInternalLinkAnnotationWidget() {
	// Parent constructor
	ve.ui.MWInternalLinkAnnotationWidget.super.apply( this, arguments );
};

/* Inheritance */

OO.inheritClass( ve.ui.MWInternalLinkAnnotationWidget, ve.ui.LinkAnnotationWidget );

/* Static Methods */

/**
 * @inheritdoc
 */
ve.ui.MWInternalLinkAnnotationWidget.static.getAnnotationFromText = function ( value ) {
	var trimmed = value.trim(),
		title = mw.Title.newFromText( trimmed );

	if ( !title ) {
		return null;
	}
	return ve.dm.MWInternalLinkAnnotation.static.newFromTitle( title );
};

/**
 * @inheritdoc
 */
ve.ui.MWInternalLinkAnnotationWidget.static.getTextFromAnnotation = function ( annotation ) {
	return annotation ? annotation.getAttribute( 'normalizedTitle' ) : '';
};

/* Methods */

/**
 * Create a text input widget to be used by the annotation widget
 *
 * @param {Object} [config] Configuration options
 * @return {OO.ui.TextInputWidget} Text input widget
 */
ve.ui.MWInternalLinkAnnotationWidget.prototype.createInputWidget = function ( config ) {
	var input = new mw.widgets.TitleSearchWidget( ve.extendObject( {
		icon: 'search',
		excludeCurrentPage: true,
		showImages: mw.config.get( 'wgVisualEditorConfig' ).usePageImages,
		showDescriptions: mw.config.get( 'wgVisualEditorConfig' ).usePageDescriptions,
		showDisambigsLast: true,
		showInterwikis: true,
		searchFragments: true,
		addQueryInput: false,
		api: ve.init.target.getContentApi(),
		cache: ve.init.platform.linkCache
	}, config ) );

	// Put query first in DOM
	// TODO: Consider upstreaming this to SearchWidget
	input.$element.prepend( input.$query );

	// Remove 'maxlength', because it should not apply to full URLs, which we allow users to paste
	// here. Maximum length of page titles will still be enforced by JS validation later (we can't
	// override maxLength config option, because that would break the validation).
	input.getQuery().$input.removeAttr( 'maxlength' );

	input.query.$input.attr( 'aria-label', mw.msg( 'visualeditor-linkinspector-button-link-internal' ) );
	return input;
};

/**
 * @inheritdoc
 */
ve.ui.MWInternalLinkAnnotationWidget.prototype.getTextInputWidget = function () {
	return this.input.query;
};

// #getHref returns the link title, not a fully resolved URL, however the only
// use case of widget.getHref is for link insertion text, which expects a title.
//
// Callers needing the full resolved URL should use ve.resolveUrl

/**
 * @inheritdoc
 */
ve.ui.MWInternalLinkAnnotationWidget.prototype.onTextChange = function ( value ) {
	var htmlDoc = this.getElementDocument(),
		namespacesWithSubpages = mw.config.get( 'wgVisualEditorConfig' ).namespacesWithSubpages,
		basePageObj = mw.Title.newFromText( mw.config.get( 'wgRelevantPageName' ) );
	// Specific thing we want to check: has a valid URL for an internal page
	// been pasted into here, in which case we want to convert it to just the
	// page title. This has to happen /here/ because a URL can reference a
	// valid page while not being a valid Title (e.g. if it contains a "%").
	if ( ve.init.platform.getExternalLinkUrlProtocolsRegExp().test( value ) ) {
		var targetData = mw.libs.ve.getTargetDataFromHref(
			value,
			htmlDoc
		);
		if ( targetData.isInternal ) {
			value = targetData.title;
			this.input.query.setValue( targetData.title );
		}
	} else if ( namespacesWithSubpages.indexOf( basePageObj.namespace ) !== -1 && value[ 0 ] === '/' ) {
		// This does make it more-difficult to deliberately link to a page in the
		// default namespace that starts with a / when you're on a subpage-allowing
		// namespace. However, the exact same trick you need to know to make it work
		// in plain wikitext applies: search for `:/foo`.
		value = basePageObj.getPrefixedText() + value;
		this.input.query.setValue( value );
	}
	return ve.ui.MWInternalLinkAnnotationWidget.super.prototype.onTextChange.call( this, value );
};
