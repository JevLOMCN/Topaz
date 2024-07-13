( function () {
	/**
	 * Pagination model for echo notifications pages.
	 *
	 * @class
	 * @mixins OO.EventEmitter
	 *
	 * @constructor
	 * @param {Object} config Configuration object
	 * @cfg {string} [pageNext] The continue value of the next page
	 * @cfg {number} [itemsPerPage] The number of items per page
	 * @cfg {number} [currentPageItemCount] The number of items that are in the
	 *  current page. If not given, the initial count defaults to the total number
	 *  of items per page.
	 */
	mw.echo.dm.PaginationModel = function MwEchoDmPaginationModel( config ) {
		config = config || {};

		// Mixin constructor
		OO.EventEmitter.call( this );

		this.pagesContinue = [];
		this.itemsPerPage = config.itemsPerPage || 25;
		this.currentPageItemCount = config.currentPageItemCount || this.itemsPerPage;

		// Set initial page
		this.currPageIndex = 0;
		this.pagesContinue[ 0 ] = '';

		// If a next page is given, fill it
		if ( config.pageNext ) {
			this.setPageContinue( 1, config.pageNext );
		}
	};

	/* Initialization */

	OO.initClass( mw.echo.dm.PaginationModel );
	OO.mixinClass( mw.echo.dm.PaginationModel, OO.EventEmitter );

	/* Events */

	/**
	 * @event update
	 *
	 * Pagination information was updated
	 */

	/* Methods */

	/**
	 * Reset pagination data
	 *
	 * @fires update
	 */
	mw.echo.dm.PaginationModel.prototype.reset = function () {
		this.pagesContinue = [];
		this.currPageIndex = 0;
		this.currentPageItemCount = this.itemsPerPage;

		this.emit( 'update' );
	};
	/**
	 * Set a page index with its 'continue' value, used for API fetching
	 *
	 * @param {number} page Page index
	 * @param {string} continueVal Continue string value
	 */
	mw.echo.dm.PaginationModel.prototype.setPageContinue = function ( page, continueVal ) {
		if ( this.pagesContinue[ page ] !== continueVal ) {
			this.pagesContinue[ page ] = continueVal;
			this.emit( 'update' );
		}
	};

	/**
	 * Get the 'continue' value of a certain page
	 *
	 * @param {number} page Page index
	 * @return {string} Continue string value
	 */
	mw.echo.dm.PaginationModel.prototype.getPageContinue = function ( page ) {
		return this.pagesContinue[ page ];
	};

	/**
	 * Get the current page index
	 *
	 * @return {number} Current page index
	 */
	mw.echo.dm.PaginationModel.prototype.getCurrPageIndex = function () {
		return this.currPageIndex;
	};

	/**
	 * Set the current page index
	 *
	 * @private
	 * @param {number} index Current page index
	 */
	mw.echo.dm.PaginationModel.prototype.setCurrPageIndex = function ( index ) {
		this.currPageIndex = index;
	};

	/**
	 * Move forward to the next page
	 *
	 * @fires update
	 */
	mw.echo.dm.PaginationModel.prototype.forwards = function () {
		if ( this.hasNextPage() ) {
			this.setCurrPageIndex( this.currPageIndex + 1 );
			this.emit( 'update' );
		}
	};

	/**
	 * Move backwards to the previous page
	 *
	 * @fires update
	 */
	mw.echo.dm.PaginationModel.prototype.backwards = function () {
		if ( this.hasPrevPage() ) {
			this.setCurrPageIndex( this.currPageIndex - 1 );
			this.emit( 'update' );
		}
	};

	/**
	 * Get the previous page continue value
	 *
	 * @return {string} Previous page continue value
	 */
	mw.echo.dm.PaginationModel.prototype.getPrevPageContinue = function () {
		return this.pagesContinue[ this.currPageIndex - 1 ] || '';
	};

	/**
	 * Get the current page continue value
	 *
	 * @return {string} Current page continue value
	 */
	mw.echo.dm.PaginationModel.prototype.getCurrPageContinue = function () {
		return this.pagesContinue[ this.currPageIndex ] || '';
	};

	/**
	 * Get the next page continue value
	 *
	 * @return {string} Next page continue value
	 */
	mw.echo.dm.PaginationModel.prototype.getNextPageContinue = function () {
		return this.pagesContinue[ this.currPageIndex + 1 ] || '';
	};

	/**
	 * Set the next page continue value
	 *
	 * @param {string} cont Next page continue value
	 */
	mw.echo.dm.PaginationModel.prototype.setNextPageContinue = function ( cont ) {
		this.setPageContinue( this.currPageIndex + 1, cont );
	};

	/**
	 * Check whether a previous page exists
	 *
	 * @return {boolean} Previous page exists
	 */
	mw.echo.dm.PaginationModel.prototype.hasPrevPage = function () {
		return this.currPageIndex > 0;
	};

	/**
	 * Check whether a next page exists
	 *
	 * @return {boolean} Next page exists
	 */
	mw.echo.dm.PaginationModel.prototype.hasNextPage = function () {
		return !!this.pagesContinue[ this.currPageIndex + 1 ];
	};

	/**
	 * Set the number of items in the current page
	 *
	 * @param {number} count Number of items
	 * @fires update
	 */
	mw.echo.dm.PaginationModel.prototype.setCurrentPageItemCount = function ( count ) {
		if ( this.currentPageItemCount !== count ) {
			this.currentPageItemCount = count;
			this.emit( 'update' );
		}
	};

	/**
	 * Get the number of items in the current page
	 *
	 * @return {number} Number of items
	 */
	mw.echo.dm.PaginationModel.prototype.getCurrentPageItemCount = function () {
		return this.currentPageItemCount;
	};

	/**
	 * Set the number of items per page
	 *
	 * @param {number} count Number of items per page
	 */
	mw.echo.dm.PaginationModel.prototype.setItemsPerPage = function ( count ) {
		this.itemsPerPage = count;
	};

	/**
	 * Get the number of items per page
	 *
	 * @return {number} Number of items per page
	 */
	mw.echo.dm.PaginationModel.prototype.getItemsPerPage = function () {
		return this.itemsPerPage;
	};
}() );
