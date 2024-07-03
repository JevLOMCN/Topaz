/*!
 * VisualEditor UserInterface LanguageSearchDialog class.
 *
 * @copyright See AUTHORS.txt
 */

/**
 * Dialog for searching for and selecting a language.
 *
 * @class
 * @extends OO.ui.ProcessDialog
 *
 * @constructor
 * @param {Object} [config] Configuration options
 */
ve.ui.LanguageSearchDialog = function VeUiLanguageSearchDialog( config ) {
	// Parent constructor
	ve.ui.LanguageSearchDialog.super.call( this, config );
};

/* Inheritance */

OO.inheritClass( ve.ui.LanguageSearchDialog, OO.ui.ProcessDialog );

/* Static Properties */

ve.ui.LanguageSearchDialog.static.name = 'languageSearch';

ve.ui.LanguageSearchDialog.static.size = 'medium';

ve.ui.LanguageSearchDialog.static.title =
	OO.ui.deferMsg( 'visualeditor-dialog-language-search-title' );

ve.ui.LanguageSearchDialog.static.actions = [
	{
		label: OO.ui.deferMsg( 'visualeditor-dialog-action-cancel' ),
		flags: [ 'safe', 'close' ]
	}
];

/**
 * Language search widget class to use.
 *
 * @static
 * @property {Function}
 * @inheritable
 */
ve.ui.LanguageSearchDialog.static.languageSearchWidget = ve.ui.LanguageSearchWidget;

/* Methods */

/**
 * @inheritdoc
 */
ve.ui.LanguageSearchDialog.prototype.initialize = function () {
	ve.ui.LanguageSearchDialog.super.prototype.initialize.apply( this, arguments );

	// eslint-disable-next-line new-cap
	this.searchWidget = new this.constructor.static.languageSearchWidget();
	this.searchWidget.getResults().connect( this, { choose: 'onSearchResultsChoose' } );
	this.$body.append( this.searchWidget.$element );
};

/**
 * Handle the search widget being selected
 *
 * @param {ve.ui.LanguageResultWidget} item Chosen item
 */
ve.ui.LanguageSearchDialog.prototype.onSearchResultsChoose = function ( item ) {
	var data = item.getData();
	this.close( {
		action: 'done',
		lang: data.code,
		dir: ve.init.platform.getLanguageDirection( data.code )
	} );
};

/**
 * @inheritdoc
 */
ve.ui.LanguageSearchDialog.prototype.getSetupProcess = function ( data ) {
	return ve.ui.LanguageSearchDialog.super.prototype.getSetupProcess.call( this, data )
		.next( function () {
			this.searchWidget.setAvailableLanguages( data.availableLanguages );
			this.searchWidget.addResults();
		}, this );
};

/**
 * @inheritdoc
 */
ve.ui.LanguageSearchDialog.prototype.getReadyProcess = function ( data ) {
	return ve.ui.LanguageSearchDialog.super.prototype.getReadyProcess.call( this, data )
		.next( function () {
			this.searchWidget.getQuery().focus();
		}, this );
};

/**
 * @inheritdoc
 */
ve.ui.LanguageSearchDialog.prototype.getTeardownProcess = function ( data ) {
	return ve.ui.LanguageSearchDialog.super.prototype.getTeardownProcess.call( this, data )
		.first( function () {
			this.searchWidget.getQuery().setValue( '' );
		}, this );
};

/**
 * @inheritdoc
 */
ve.ui.LanguageSearchDialog.prototype.getBodyHeight = function () {
	return 300;
};

/* Registration */

ve.ui.windowFactory.register( ve.ui.LanguageSearchDialog );
