/*!
 * VisualEditor DataModel LanguageAnnotation class.
 *
 * @copyright See AUTHORS.txt
 */

/**
 * DataModel language annotation.
 *
 * Represents `<span>` tags with 'lang' and 'dir' properties.
 *
 * @class
 * @extends ve.dm.TextStyleAnnotation
 * @constructor
 * @param {Object} element
 */
ve.dm.LanguageAnnotation = function VeDmLanguageAnnotation() {
	// Parent constructor
	ve.dm.LanguageAnnotation.super.apply( this, arguments );
};

/* Inheritance */

OO.inheritClass( ve.dm.LanguageAnnotation, ve.dm.TextStyleAnnotation );

/* Static Properties */

ve.dm.LanguageAnnotation.static.name = 'meta/language';

ve.dm.LanguageAnnotation.static.matchTagNames = [ 'span', 'bdo' ];

ve.dm.LanguageAnnotation.static.matchFunction = function ( domElement ) {
	var lang = domElement.getAttribute( 'lang' ),
		dir = ( domElement.getAttribute( 'dir' ) || '' ).toLowerCase();
	return lang || dir === 'ltr' || dir === 'rtl';
};

ve.dm.LanguageAnnotation.static.applyToAppendedContent = true;

ve.dm.LanguageAnnotation.static.toDataElement = function ( domElements ) {
	// Parent method
	var dataElement = ve.dm.LanguageAnnotation.super.static.toDataElement.apply( this, arguments );
	dataElement.attributes.lang = domElements[ 0 ].getAttribute( 'lang' );
	dataElement.attributes.dir = domElements[ 0 ].getAttribute( 'dir' );

	return dataElement;
};

ve.dm.LanguageAnnotation.static.toDomElements = function ( dataElement ) {
	// Parent method
	var domElements = ve.dm.LanguageAnnotation.super.static.toDomElements.apply( this, arguments ),
		domElement = domElements[ 0 ];

	if ( dataElement.attributes.lang ) {
		domElement.setAttribute( 'lang', dataElement.attributes.lang );
	}
	if ( dataElement.attributes.dir ) {
		domElement.setAttribute( 'dir', dataElement.attributes.dir );
	}

	return domElements;
};

ve.dm.LanguageAnnotation.static.describeChange = function ( key, change ) {
	if ( key === 'lang' ) {
		return ve.htmlMsg( 'visualeditor-changedesc-language',
			this.wrapText( 'del', ve.init.platform.getLanguageName( change.from.toLowerCase() ) ),
			this.wrapText( 'ins', ve.init.platform.getLanguageName( change.to.toLowerCase() ) )
		);
	}

	// TODO: Show something nicer than 'null', 'ltr', and 'rtl'.
	if ( key === 'dir' ) {
		return ve.htmlMsg( 'visualeditor-changedesc-direction',
			this.wrapText( 'del', change.from.toLowerCase() ),
			this.wrapText( 'ins', change.to.toLowerCase() )
		);
	}

	// Parent method
	return ve.dm.LanguageAnnotation.super.static.describeChange.apply( this, arguments );
};

/* Methods */

/**
 * @return {Object}
 */
ve.dm.LanguageAnnotation.prototype.getComparableObject = function () {
	return {
		type: 'meta/language',
		lang: this.getAttribute( 'lang' ),
		dir: this.getAttribute( 'dir' )
	};
};

/* Registration */

ve.dm.modelRegistry.register( ve.dm.LanguageAnnotation );
