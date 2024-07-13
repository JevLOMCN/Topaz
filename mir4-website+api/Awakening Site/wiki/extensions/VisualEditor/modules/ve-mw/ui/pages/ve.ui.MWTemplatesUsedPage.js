/*!
 * VisualEditor user interface MWTemplatesUsedPage class.
 *
 * @copyright See AUTHORS.txt
 * @license The MIT License (MIT); see LICENSE.txt
 */

/**
 * MediaWiki meta dialog TemplatesUsed page.
 *
 * @class
 * @extends OO.ui.PageLayout
 *
 * @constructor
 * @param {string} name Unique symbolic name of page
 * @param {Object} [config] Configuration options
 * @cfg {jQuery} [$overlay] Overlay to render dropdowns in
 */
ve.ui.MWTemplatesUsedPage = function VeUiMWTemplatesUsedPage() {
	var page = this,
		target = ve.init.target;

	// Parent constructor
	ve.ui.MWTemplatesUsedPage.super.apply( this, arguments );

	// Properties
	this.templatesUsedFieldset = new OO.ui.FieldsetLayout( {
		label: ve.msg( 'visualeditor-templatesused-tool' ),
		icon: 'puzzle'
	} );

	this.templatesUsedFieldset.$group.addClass( [
		'mw-body-content'
	] );

	target.getContentApi().get( {
		action: 'visualeditor',
		paction: 'templatesused',
		page: target.getPageName(),
		uselang: mw.config.get( 'wgUserLanguage' )
	} ).then( function ( response ) {
		var templatesUsed = $.parseHTML( response.visualeditor );
		if ( templatesUsed.length && $( templatesUsed ).find( 'li' ).length ) {
			return templatesUsed;
		} else {
			return ve.createDeferred().reject().promise();
		}
	} ).then( function ( templatesUsed ) {
		// templatesUsed is an array of nodes
		// eslint-disable-next-line no-jquery/no-append-html
		page.templatesUsedFieldset.$group.append( templatesUsed );
		ve.targetLinksToNewWindow( page.templatesUsedFieldset.$group[ 0 ] );
	}, function () {
		page.templatesUsedFieldset.$group.append(
			$( '<em>' ).text( ve.msg( 'visualeditor-dialog-meta-templatesused-noresults' ) )
		);
	} );

	// Initialization
	this.$element.append( this.templatesUsedFieldset.$element );
};

/* Inheritance */

OO.inheritClass( ve.ui.MWTemplatesUsedPage, OO.ui.PageLayout );

/* Methods */

/**
 * @inheritdoc
 */
ve.ui.MWTemplatesUsedPage.prototype.setupOutlineItem = function () {
	this.outlineItem
		.setIcon( 'puzzle' )
		.setLabel( ve.msg( 'visualeditor-templatesused-tool' ) );
};

/**
 * @inheritdoc
 */
ve.ui.MWTemplatesUsedPage.prototype.focus = function () {
	// No controls, just focus the whole page instead of the first link
	this.$element[ 0 ].focus();
};

ve.ui.MWTemplatesUsedPage.prototype.getFieldsets = function () {
	return [
		this.templatesUsedFieldset
	];
};
