( function () {
	'use strict';

	/**
	 * Creates an mw.widgets.MathWbEntitySelector object. This class was inspired by
	 * mw.widgets.TitleInputWidget.
	 *
	 * @class
	 * @extends OO.ui.TextInputWidget
	 * @mixin mw.widgets.TitleWidget
	 * @mixin OO.ui.mixin.LookupElement
	 *
	 * @constructor
	 * @param {Object} [config] Configuration options
	 * @cfg {mw.Api} [api] API object to use, creates a mw.ForeignApi instance if not specified
	 */
	mw.widgets.MathWbEntitySelector = function ( config ) {
		config = config || {};
		this.api = config.api;
		if ( !this.api ) {
			this.api = new mw.ForeignApi(
				mw.config.get( 'wgMathEntitySelectorUrl' ),
				{ anonymous: true } );
		}
		OO.ui.TextInputWidget.call( this, config );
		OO.ui.mixin.LookupElement.call( this, config );
		this.$element.addClass( 'mw-widget-MathWbEntitySelector' );
	};
	OO.inheritClass( mw.widgets.MathWbEntitySelector, OO.ui.TextInputWidget );
	OO.mixinClass( mw.widgets.MathWbEntitySelector, OO.ui.mixin.LookupElement );

	/**
	 * Get the API object for wikibase requests
	 *
	 * @return {mw.Api} MediaWiki API
	 */
	mw.widgets.MathWbEntitySelector.prototype.getApi = function () {
		return this.api;
	};

	/**
	 * Get the current value of the search query
	 *
	 * @abstract
	 * @return {string} Search query
	 */
	mw.widgets.MathWbEntitySelector.prototype.getQueryValue = function () {
		return this.getValue();
	};

	/**
	 * @inheritdoc OO.ui.mixin.LookupElement
	 */
	mw.widgets.MathWbEntitySelector.prototype.getLookupCacheDataFromResponse = function ( response ) {
		return response.search || {};
	};

	/**
	 * @inheritdoc OO.ui.mixin.LookupElement
	 */
	mw.widgets.MathWbEntitySelector.prototype.getLookupMenuOptionsFromData = function ( response ) {
		return response.map( function ( res ) {
			return new OO.ui.MenuOptionWidget( { data: res.id, label: res.label, title: res.description } );
		} );
	};

	/**
	 * Get API params for a given query
	 *
	 * @param {string} query User query
	 * @return {Object} API params
	 */
	mw.widgets.MathWbEntitySelector.prototype.getApiParams = function ( query ) {

		return {
			action: 'wbsearchentities',
			search: query,
			format: 'json',
			errorformat: 'plaintext',
			language: mw.config.get( 'wgContentLanguage' ),
			uselang: mw.config.get( 'wgContentLanguage' )
		};
	};

	/**
	 * @inheritdoc
	 */
	mw.widgets.MathWbEntitySelector.prototype.getLookupRequest = function () {
		const api = this.getApi(),
			query = this.getQueryValue(),
			widget = this,
			promiseAbortObject = {
				abort: function () {
					// Do nothing. This is just so OOUI doesn't break due to abort being undefined.
					// see also mw.widgets.TitleWidget.prototype.getSuggestionsPromise
				}
			},
			req = api.get( widget.getApiParams( query ) );
		promiseAbortObject.abort = req.abort.bind( req );
		return req.promise( promiseAbortObject );
	};

	// eslint-disable-next-line no-jquery/no-global-selector
	const $wbEntitySelector = $( '#wbEntitySelector' );
	if ( $wbEntitySelector.length ) {
		OO.ui.infuse( $wbEntitySelector );
	}
}() );
