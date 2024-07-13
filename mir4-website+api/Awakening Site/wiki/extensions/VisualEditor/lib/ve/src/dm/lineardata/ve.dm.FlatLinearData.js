/*!
 * VisualEditor FlatLinearData classes.
 *
 * Class containing Flat linear data and a hash-value store.
 *
 * @copyright See AUTHORS.txt
 */

/**
 * Flat linear data storage
 *
 * @class
 * @extends ve.dm.LinearData
 * @constructor
 * @param {ve.dm.HashValueStore} store Hash-value store
 * @param {Array} [data] Linear data
 */
ve.dm.FlatLinearData = function VeDmFlatLinearData() {
	// Parent constructor
	ve.dm.FlatLinearData.super.apply( this, arguments );
};

/* Inheritance */

OO.inheritClass( ve.dm.FlatLinearData, ve.dm.LinearData );

/* Methods */

/**
 * Get the type of the element at a specified offset.
 *
 * @param {number} offset Data offset
 * @return {string} Type of the element
 */
ve.dm.FlatLinearData.prototype.getType = function ( offset ) {
	return ve.dm.LinearData.static.getType( this.getData( offset ) );
};

/**
 * Check if data at a given offset is an element.
 *
 * @param {number} offset Data offset
 * @return {boolean} Data at offset is an element
 */
ve.dm.FlatLinearData.prototype.isElementData = function ( offset ) {
	return ve.dm.LinearData.static.isElementData( this.getData( offset ) );
};

/**
 * Check for elements in data.
 *
 * This method assumes that any value that has a type property that's a string is an element object.
 * Elements are discovered by iterating through the entire data array (backwards).
 *
 * @return {boolean} At least one elements exists in data
 */
ve.dm.FlatLinearData.prototype.containsElementData = function () {
	var i = this.getLength();
	while ( i-- ) {
		if ( this.isElementData( i ) ) {
			return true;
		}
	}
	return false;
};

/**
 * Checks if data at a given offset is an open element.
 *
 * @param {number} offset Data offset
 * @return {boolean} Data at offset is an open element
 */
ve.dm.FlatLinearData.prototype.isOpenElementData = function ( offset ) {
	return ve.dm.LinearData.static.isOpenElementData( this.getData( offset ) );
};

/**
 * Checks if data at a given offset is a close element.
 *
 * @param {number} offset Data offset
 * @return {boolean} Data at offset is a close element
 */
ve.dm.FlatLinearData.prototype.isCloseElementData = function ( offset ) {
	return ve.dm.LinearData.static.isCloseElementData( this.getData( offset ) );
};
