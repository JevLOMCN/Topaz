( function () {
	/**
	 * Sorted list abstract data structure.
	 *
	 * @class
	 * @abstract
	 * @mixins OO.EventEmitter
	 * @mixins OO.SortedEmitterList
	 *
	 * @constructor
	 */
	mw.echo.dm.SortedList = function MwEchoDmSortedList() {
		// Mixin constructors
		OO.EventEmitter.call( this );
		OO.SortedEmitterList.call( this );
	};

	/* Initialization */

	OO.mixinClass( mw.echo.dm.SortedList, OO.EventEmitter );
	OO.mixinClass( mw.echo.dm.SortedList, OO.SortedEmitterList );

	/**
	 * Defines whether or not this list contains items
	 * or lists of items.
	 *
	 * @return {boolean} This list is a group
	 */
	mw.echo.dm.SortedList.prototype.isGroup = null;
}() );
