( function () {
	/**
	 * Source pages model for notification filtering
	 *
	 * @class
	 * @mixins OO.EventEmitter
	 *
	 * @constructor
	 * @param {Object} config Configuration object
	 * @cfg {string} [currentSource] The selected source for the model.
	 *  Defaults to the current wiki.
	 */
	mw.echo.dm.SourcePagesModel = function MwEchoDmSourcePagesModel( config ) {
		config = config || {};

		// Mixin constructor
		OO.EventEmitter.call( this );

		this.sources = {};

		this.currentSource = config.currentSource || 'local';
		this.currentPage = null;
	};

	/* Initialization */
	OO.initClass( mw.echo.dm.SourcePagesModel );
	OO.mixinClass( mw.echo.dm.SourcePagesModel, OO.EventEmitter );

	/* Events */

	/**
	 * @event update
	 *
	 * The state of the source page model has changed
	 */

	/* Methods */

	/**
	 * Set the current source and page.
	 *
	 * @param {string} source New source
	 * @param {string} page New page
	 * @fires update
	 */
	mw.echo.dm.SourcePagesModel.prototype.setCurrentSourcePage = function ( source, page ) {
		if (
			this.currentSource !== source ||
			this.currentPage !== page
		) {
			this.currentSource = source;
			this.currentPage = page;
			this.emit( 'update' );
		}
	};

	/**
	 * Get the current source
	 *
	 * @return {string} Current source
	 */
	mw.echo.dm.SourcePagesModel.prototype.getCurrentSource = function () {
		return this.currentSource;
	};

	/**
	 * Get the title of the currently selected page
	 *
	 * @return {string} Page title
	 */
	mw.echo.dm.SourcePagesModel.prototype.getCurrentPage = function () {
		return this.currentPage;
	};

	/**
	 * Set all sources and pages. This will also reset and override any
	 * previously set information.
	 *
	 * @param {Object} sourceData A detailed object about sources and pages
	 */
	mw.echo.dm.SourcePagesModel.prototype.setAllSources = function ( sourceData ) {
		this.reset();
		for ( var source in sourceData ) {
			if ( Object.prototype.hasOwnProperty.call( sourceData, source ) ) {
				this.setSourcePagesDetails( source, sourceData[ source ] );
			}
		}
		this.emit( 'update' );
	};

	/**
	 * Get an array of all source names
	 *
	 * @return {string[]} Array of source names
	 */
	mw.echo.dm.SourcePagesModel.prototype.getSourcesArray = function () {
		return Object.keys( this.sources );
	};

	/**
	 * Get the title of a source
	 *
	 * @param {string} source Symbolic name of the source
	 * @return {string} Source title
	 */
	mw.echo.dm.SourcePagesModel.prototype.getSourceTitle = function ( source ) {
		return this.sources[ source ] && this.sources[ source ].title;
	};

	/**
	 * Get the total count of a source
	 *
	 * @param {string} source Symbolic name of the source
	 * @return {number} Total count
	 */
	mw.echo.dm.SourcePagesModel.prototype.getSourceTotalCount = function ( source ) {
		return ( this.sources[ source ] && this.sources[ source ].totalCount ) || 0;
	};

	/**
	 * Get all pages in a source
	 *
	 * @param {string} source Symbolic name of the source
	 * @return {Object} Page definitions in this source
	 */
	mw.echo.dm.SourcePagesModel.prototype.getSourcePages = function ( source ) {
		return this.sources[ source ] && this.sources[ source ].pages;
	};

	/**
	 * Get the list of page titles associated with one group title.
	 *
	 * @param {string} source Symbolic name of the source
	 * @param {string} title Group title
	 * @return {string[]} Page titles
	 */
	mw.echo.dm.SourcePagesModel.prototype.getGroupedPagesForTitle = function ( source, title ) {
		return OO.getProp( this.sources, source, 'pages', title, 'pages' ) || [];
	};

	/**
	 * Get the list of page titles associated with the current group title.
	 *
	 * @return {string[]} Page titles
	 */
	mw.echo.dm.SourcePagesModel.prototype.getGroupedPagesForCurrentTitle = function () {
		return this.getGroupedPagesForTitle( this.getCurrentSource(), this.getCurrentPage() );
	};

	/**
	 * Reset the data
	 */
	mw.echo.dm.SourcePagesModel.prototype.reset = function () {
		this.sources = {};
	};

	/**
	 * Set the details of a source and its page definitions
	 *
	 * @private
	 * @param {string} source Source symbolic name
	 * @param {Object} details Details object
	 */
	mw.echo.dm.SourcePagesModel.prototype.setSourcePagesDetails = function ( source, details ) {
		this.sources[ source ] = {
			title: details.source.title || source,
			base: details.source.base,
			totalCount: details.totalCount || 0,
			pages: {}
		};

		for ( var i = 0; i < details.pages.length; i++ ) {
			var page = details.pages[ i ];
			this.sources[ source ].pages[ page.title ] = page;
		}
	};
}() );
