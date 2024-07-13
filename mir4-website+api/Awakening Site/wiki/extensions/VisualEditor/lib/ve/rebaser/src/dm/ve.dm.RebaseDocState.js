/*!
 * VisualEditor DataModel rebase document state class.
 *
 * @copyright See AUTHORS.txt
 */

'use strict';

/**
 * DataModel rebase document state
 *
 * @class
 *
 * @constructor
 */
ve.dm.RebaseDocState = function VeDmRebaseDocState() {
	/**
	 * @property {ve.dm.Change} history History as one big change
	 */
	this.history = new ve.dm.Change();

	/**
	 * @property {Map.<number, Object>} authors Information about each author
	 */
	this.authors = new Map();
};

/* Inheritance */

OO.initClass( ve.dm.RebaseDocState );

/* Static Methods */

/**
 * Get new empty author data object
 *
 * @return {Object} New empty author data object
 * @return {string} return.name
 * @return {string} return.color
 * @return {number} return.rejections Number of unacknowledged rejections
 * @return {ve.dm.Change|null} return.continueBase Continue base
 * @return {string} return.token Secret token for usurping sessions
 * @return {boolean} return.active Whether the author is active
 */
ve.dm.RebaseDocState.static.newAuthorData = function () {
	return {
		name: '',
		color: '',
		rejections: 0,
		continueBase: null,
		// TODO use cryptographic randomness here and convert to hex
		token: Math.random().toString(),
		active: true
	};
};

/* Methods */

ve.dm.RebaseDocState.prototype.getActiveAuthors = function () {
	const result = {};
	this.authors.forEach( function ( authorData, authorId ) {
		if ( authorData.active ) {
			result[ authorId ] = {
				name: authorData.name,
				color: authorData.color
			};
		}
	} );
	return result;
};

ve.dm.RebaseDocState.prototype.clearHistory = function () {
	this.history = new ve.dm.Change();
	this.authors.forEach( function ( authorData ) {
		authorData.continueBase = null;
	} );
};
