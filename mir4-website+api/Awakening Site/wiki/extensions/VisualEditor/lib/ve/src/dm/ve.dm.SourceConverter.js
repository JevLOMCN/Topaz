/*!
 * VisualEditor DataModel Converter class.
 *
 * @copyright See AUTHORS.txt
 */

ve.dm.SourceConverter = function VeDmSourceConverter() {
};

/**
 * Convert from source text to a document model
 *
 * Equivalent to ve.dm.Converter#getModelFromDom.
 *
 * @param {string} sourceText Source text
 * @param {Object} [options] Conversion options
 * @param {string} [options.lang] Document language code
 * @param {string} [options.dir] Document directionality (ltr/rtl)
 * @return {ve.dm.Document} Document model
 */
ve.dm.SourceConverter.prototype.getModelFromSourceText = function ( sourceText, options ) {
	var data = this.getDataFromSourceText( sourceText );

	options = options || {};

	// TODO: Internal lists are completely unused in source mode
	data.push( { type: 'internalList' }, { type: '/internalList' } );

	return new ve.dm.Document( data, undefined, undefined, undefined, undefined, options.lang, options.dir, undefined, true );
};

/**
 * Convert from source text to linear data
 *
 * @param {string} sourceText Source text
 * @param {boolean} [inline] Convert text for inline insertion, so skip opening and closing paragraph elements
 * @return {Array} Linear data
 */
ve.dm.SourceConverter.prototype.getDataFromSourceText = function ( sourceText, inline ) {
	var lines = sourceText.split( /\r\n|\r|\n/ ),
		content = [];

	for ( var i = 0, l = lines.length; i < l; i++ ) {
		// Skip opening <p> in inline mode
		if ( !( inline && i === 0 ) ) {
			content.push( { type: 'paragraph' } );
		}
		ve.batchPush( content, lines[ i ].split( '' ) );
		// Skip closing </p> in inline mode
		if ( !( inline && i === l - 1 ) ) {
			content.push( { type: '/paragraph' } );
		}
	}
	return content;
};

/**
 * Convert from document model to source text
 *
 * @param {ve.dm.Document} model Document model
 * @return {string} Source text
 */
ve.dm.SourceConverter.prototype.getSourceTextFromModel = function ( model ) {
	return this.getSourceTextFromDataRange( model.data.data );
};

/**
 * Convert from linear data to source text, optionally with a specified range
 *
 * @param {Array} data Linear data
 * @param {ve.Range} [range] Range, defaults to full data set.
 * @return {string} Source text
 */
ve.dm.SourceConverter.prototype.getSourceTextFromDataRange = function ( data, range ) {
	var text = '';

	range = range || new ve.Range( 0, data.length );

	for ( var i = range.start; i < range.end; i++ ) {
		// Check for the most common case first for best performance
		if ( !data[ i ].type ) {
			text += data[ i ];
		} else if ( data[ i ].type === '/paragraph' &&
			( !data[ i + 1 ] || data[ i + 1 ].type === 'paragraph' )
		) {
			// (T243606) Append a newline after each full paragraph, including the last one in the range
			text += '\n';
		}
	}

	return text;
};

ve.dm.sourceConverter = new ve.dm.SourceConverter();
