mw.loader.using( 'ext.visualEditor.targetLoader' ).then( function () {
	mw.libs.ve.targetLoader.addPlugin( function () {

		ve.init.mw.SpamBlacklistSaveErrorHandler = function () {};

		OO.inheritClass( ve.init.mw.SpamBlacklistSaveErrorHandler, ve.init.mw.SaveErrorHandler );

		ve.init.mw.SpamBlacklistSaveErrorHandler.static.name = 'spamBlacklist';

		ve.init.mw.SpamBlacklistSaveErrorHandler.static.matchFunction = function ( data ) {
			return data.errors && data.errors.some( function ( err ) {
				return err.code === 'spamblacklist';
			} );
		};

		ve.init.mw.SpamBlacklistSaveErrorHandler.static.process = function ( data, target ) {
			// Handle errors from Extension:SpamBlacklist
			target.showSaveError( target.extractErrorMessages( data ) );
			// Emit event for tracking. TODO: This is a bad design
			target.emit( 'saveErrorSpamBlacklist' );
		};

		ve.init.mw.saveErrorHandlerFactory.register( ve.init.mw.SpamBlacklistSaveErrorHandler );

	} );
} );
