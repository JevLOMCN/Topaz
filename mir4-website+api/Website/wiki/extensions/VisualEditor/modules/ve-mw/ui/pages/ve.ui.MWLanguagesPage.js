/*!
 * VisualEditor user interface MWLanguagesPage class.
 *
 * @copyright See AUTHORS.txt
 * @license The MIT License (MIT); see LICENSE.txt
 */

/**
 * MediaWiki meta dialog Languages page.
 *
 * @class
 * @extends OO.ui.PageLayout
 *
 * @constructor
 * @param {string} name Unique symbolic name of page
 * @param {Object} [config] Configuration options
 * @cfg {jQuery} [$overlay] Overlay to render dropdowns in
 */
ve.ui.MWLanguagesPage = function VeUiMWLanguagesPage() {
	// Parent constructor
	ve.ui.MWLanguagesPage.super.apply( this, arguments );

	// Properties
	this.languagesFieldset = new OO.ui.FieldsetLayout( {
		label: ve.msg( 'visualeditor-dialog-meta-languages-label' ),
		icon: 'textLanguage'
	} );

	// Initialization
	this.languagesFieldset.$element.append(
		$( '<span>' )
			.text( ve.msg( 'visualeditor-dialog-meta-languages-readonlynote' ) )
	);
	this.$element.append( this.languagesFieldset.$element );

	this.getAllLanguageItems().done( this.onLoadLanguageData.bind( this ) );
};

/* Inheritance */

OO.inheritClass( ve.ui.MWLanguagesPage, OO.ui.PageLayout );

/* Methods */

/**
 * @inheritdoc
 */
ve.ui.MWLanguagesPage.prototype.setupOutlineItem = function () {
	this.outlineItem
		.setIcon( 'textLanguage' )
		.setLabel( ve.msg( 'visualeditor-dialog-meta-languages-section' ) );
};

ve.ui.MWLanguagesPage.prototype.onLoadLanguageData = function ( languages ) {
	var $languagesTable = $( '<table>' ),
		languageslength = languages.length;

	$languagesTable
		.addClass( 've-ui-mwLanguagesPage-languages-table' )
		.append( $( '<tr>' )
			.append(
				$( '<th>' )
					.text( ve.msg( 'visualeditor-dialog-meta-languages-code-label' ) )
			)
			.append(
				$( '<th>' )
					.text( ve.msg( 'visualeditor-dialog-meta-languages-name-label' ) )
			)
			.append(
				$( '<th>' )
					.text( ve.msg( 'visualeditor-dialog-meta-languages-link-label' ) )
			)
		);

	for ( var i = 0; i < languageslength; i++ ) {
		languages[ i ].safelang = languages[ i ].lang;
		languages[ i ].dir = 'auto';
		if ( $.uls ) {
			// site codes don't always represent official language codes
			// using real language code instead of a dummy ('redirect' in ULS' terminology)
			languages[ i ].safelang = $.uls.data.isRedirect( languages[ i ].lang ) || languages[ i ].lang;
			languages[ i ].dir = ve.init.platform.getLanguageDirection( languages[ i ].safelang );
		}
		$languagesTable.append(
			$( '<tr>' ).append(
				$( '<td>' ).text( languages[ i ].lang ),
				$( '<td>' ).text( languages[ i ].langname ).add( $( '<td>' ).text( languages[ i ].title ) )
					.attr( {
						lang: languages[ i ].safelang,
						dir: languages[ i ].dir
					} )
			)
		);
	}

	this.languagesFieldset.$element.append( $languagesTable );
};

/**
 * Handle language items being loaded.
 *
 * @param {jQuery.Deferred} deferred Deferred to resolve with language data
 * @param {Object} response API response
 */
ve.ui.MWLanguagesPage.prototype.onAllLanguageItemsSuccess = function ( deferred, response ) {
	var languages = [],
		langlinks = OO.getProp( response, 'query', 'pages', 0, 'langlinks' );
	if ( langlinks ) {
		for ( var i = 0, iLen = langlinks.length; i < iLen; i++ ) {
			languages.push( {
				lang: langlinks[ i ].lang,
				langname: langlinks[ i ].autonym,
				title: langlinks[ i ].title,
				metaItem: null
			} );
		}
	}
	deferred.resolve( languages );
};

/**
 * Gets language item from meta list item
 *
 * @param {ve.dm.MWLanguageMetaItem} metaItem
 * @return {Object} item
 */
ve.ui.MWLanguagesPage.prototype.getLanguageItemFromMetaListItem = function ( metaItem ) {
	// TODO: get real values from metaItem once Parsoid actually provides them - T50970
	return {
		lang: 'lang',
		langname: 'langname',
		title: 'title',
		metaItem: metaItem
	};
};

/**
 * Get array of language items from meta list
 *
 * @return {Object[]} items
 */
ve.ui.MWLanguagesPage.prototype.getLocalLanguageItems = function () {
	var items = [],
		languages = this.metaList.getItemsInGroup( 'mwLanguage' ),
		languageslength = languages.length;

	// Loop through MWLanguages and build out items

	for ( var i = 0; i < languageslength; i++ ) {
		items.push( this.getLanguageItemFromMetaListItem( languages[ i ] ) );
	}
	return items;
};

/**
 * Get array of language items from meta list
 *
 * @return {jQuery.Promise}
 */
ve.ui.MWLanguagesPage.prototype.getAllLanguageItems = function () {
	var deferred = ve.createDeferred();
	// TODO: Detect paging token if results exceed limit
	ve.init.target.getContentApi().get( {
		action: 'query',
		prop: 'langlinks',
		llprop: 'autonym',
		lllimit: 500,
		titles: ve.init.target.getPageName()
	} )
		.done( this.onAllLanguageItemsSuccess.bind( this, deferred ) )
		.fail( this.onAllLanguageItemsError.bind( this, deferred ) );
	return deferred.promise();
};

/**
 * Handle language items failing to be loaded.
 *
 * TODO: This error function should probably not be empty.
 */
ve.ui.MWLanguagesPage.prototype.onAllLanguageItemsError = function () {};

ve.ui.MWLanguagesPage.prototype.getFieldsets = function () {
	return [
		this.languagesFieldset
	];
};
