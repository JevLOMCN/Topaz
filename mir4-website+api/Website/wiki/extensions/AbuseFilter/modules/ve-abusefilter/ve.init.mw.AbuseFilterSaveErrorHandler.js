mw.loader.using( 'ext.visualEditor.targetLoader' ).then( function () {
	mw.libs.ve.targetLoader.addPlugin( function () {

		ve.init.mw.AbuseFilterSaveErrorHandler = function () {};

		OO.inheritClass( ve.init.mw.AbuseFilterSaveErrorHandler, ve.init.mw.SaveErrorHandler );

		ve.init.mw.AbuseFilterSaveErrorHandler.static.name = 'abuseFilter';

		ve.init.mw.AbuseFilterSaveErrorHandler.static.matchFunction = function ( data ) {
			return data.errors && data.errors.some( function ( err ) {
				return err.code === 'abusefilter-disallowed' || err.code === 'abusefilter-warning';
			} );
		};

		ve.init.mw.AbuseFilterSaveErrorHandler.static.process = function ( data, target ) {
			var isWarning = data.errors.every( function ( err ) {
				return err.code === 'abusefilter-warning';
			} );
			// Handle warnings/errors from Extension:AbuseFilter
			target.showSaveError( target.extractErrorMessages( data ), isWarning );
			// Emit event for tracking. TODO: This is a bad design
			target.emit( 'saveErrorAbuseFilter' );
		};

		ve.init.mw.saveErrorHandlerFactory.register( ve.init.mw.AbuseFilterSaveErrorHandler );

	} );
} );
