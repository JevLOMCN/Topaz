/*!
 * VisualEditor MWWikitextDataTransferHandlerFactory class.
 *
 * @copyright See AUTHORS.txt
 */

/**
 * Drop handler Factory.
 *
 * @class
 * @extends ve.ui.DataTransferHandlerFactory
 * @constructor
 */
ve.ui.MWWikitextDataTransferHandlerFactory = function VeUiMwWikitextDataTransferHandlerFactory() {
	var factory = this;

	// Parent constructor
	ve.ui.MWWikitextDataTransferHandlerFactory.super.apply( this, arguments );

	for ( var name in ve.ui.dataTransferHandlerFactory.registry ) {
		this.register( ve.ui.dataTransferHandlerFactory.registry[ name ] );
	}

	ve.ui.dataTransferHandlerFactory.on( 'register', function ( n, data ) {
		factory.register( data );
	} );
};

/* Inheritance */

OO.inheritClass( ve.ui.MWWikitextDataTransferHandlerFactory, ve.ui.DataTransferHandlerFactory );

/* Methods */

/**
 * Create an object based on a name.
 *
 * Name is used to look up the constructor to use, while all additional arguments are passed to the
 * constructor directly, so leaving one out will pass an undefined to the constructor.
 *
 * See https://doc.wikimedia.org/oojs/master/OO.Factory.html
 *
 * @param {string} name Object name
 * @param {...Mixed} [args] Arguments to pass to the constructor
 * @return {Object} The new object
 * @throws {Error} Unknown object name
 */
ve.ui.MWWikitextDataTransferHandlerFactory.prototype.create = function () {
	// Parent method
	var handler = ve.ui.MWWikitextDataTransferHandlerFactory.super.prototype.create.apply( this, arguments ),
		resolve = handler.resolve.bind( handler );

	function isPlain( data ) {
		return typeof data === 'string' || ve.dm.LinearData.static.getType( data ) === 'paragraph';
	}

	handler.resolve = function ( dataOrDoc ) {
		if ( typeof dataOrDoc === 'string' || ( Array.isArray( dataOrDoc ) && dataOrDoc.every( isPlain ) ) ) {
			resolve( dataOrDoc );
		} else {
			var doc = dataOrDoc instanceof ve.dm.Document ?
				dataOrDoc :
				// The handler may have also written items to the store
				new ve.dm.Document( new ve.dm.ElementLinearData( handler.surface.getModel().getDocument().getStore(), dataOrDoc ) );

			// Optimization: we can skip a server hit if this is a plain link,
			// with no title, whose href is equal to the contained text. This
			// avoids a stutter in the common case of pasting a link into the
			// document.
			var annotations = doc.data.getAnnotationsFromRange( new ve.Range( 0, doc.data.getLength() ) );
			if ( annotations.getLength() === 1 ) {
				var text = doc.data.getText();
				if ( annotations.get( 0 ).getAttribute( 'href' ) === text ) {
					return resolve( text );
				}
			}

			ve.init.target.getWikitextFragment( doc, false )
				.done( resolve )
				.fail( function () {
					handler.abort();
				} );
		}
	};

	return handler;
};

/* Initialization */

ve.ui.wikitextDataTransferHandlerFactory = new ve.ui.MWWikitextDataTransferHandlerFactory();

ve.ui.wikitextDataTransferHandlerFactory.unregister( ve.ui.MWWikitextStringTransferHandler );
