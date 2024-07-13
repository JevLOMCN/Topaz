/*!
 * VisualEditor MWAlienAnnotationContextItem class.
 *
 * @copyright See AUTHORS.txt
 */

/**
 * Context item for a MWAlienAnnotation
 *
 * @class
 * @extends ve.ui.MWAnnotationContextItem
 *
 * @constructor
 * @param {ve.ui.LinearContext} context Context the item is in
 * @param {ve.dm.Model} model Model the item is related to
 * @param {Object} [config]
 */
ve.ui.MWAlienAnnotationContextItem = function VeUiMWAlienAnnotationContextItem() {
	// Parent constructor
	ve.ui.MWAlienAnnotationContextItem.super.apply( this, arguments );
};

/* Inheritance */

OO.inheritClass( ve.ui.MWAlienAnnotationContextItem, ve.ui.MWAnnotationContextItem );

/* Static Properties */

ve.ui.MWAlienAnnotationContextItem.static.name = 'mwAlienAnnotation';

ve.ui.MWAlienAnnotationContextItem.static.modelClasses = [
	ve.dm.MWAlienAnnotationNode
];

/* Methods */

ve.ui.MWAlienAnnotationContextItem.prototype.getLabelMessage = function () {
	var type = this.model.getAttribute( 'type' );
	if ( type.indexOf( '/End', type.length - 4 ) !== -1 ) {
		return mw.message( 'visualeditor-annotations-default-end' ).text();
	} else {
		return mw.message( 'visualeditor-annotations-default-start' ).text();
	}
};

ve.ui.MWAlienAnnotationContextItem.prototype.getDescriptionMessage = function () {
	var type = this.model.getAttribute( 'type' );
	if ( type.indexOf( '/End', type.length - 4 ) !== -1 ) {
		return '';
	}
	return mw.message( 'visualeditor-annotations-default-description' ).parseDom();
};

ve.ui.contextItemFactory.register( ve.ui.MWAlienAnnotationContextItem );
