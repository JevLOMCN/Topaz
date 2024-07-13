mw.loader.using( 'ext.visualEditor.targetLoader' ).then( function () {
	mw.libs.ve.targetLoader.addPlugin( function () {

		ve.init.mw.TurnstileSaveErrorHandler = function () {};

		OO.inheritClass( ve.init.mw.TurnstileSaveErrorHandler, ve.init.mw.SaveErrorHandler );

		ve.init.mw.TurnstileSaveErrorHandler.static.name = 'confirmEditTurnstile';

		ve.init.mw.TurnstileSaveErrorHandler.static.getReadyPromise = function () {
			const onLoadFn = 'onTurnstileLoadCallback' + Date.now();
			let deferred, config, scriptURL, params;

			if ( !this.readyPromise ) {
				deferred = $.Deferred();
				config = mw.config.get( 'wgConfirmEditConfig' );
				scriptURL = new mw.Uri( config.turnstileScriptURL );
				params = { onload: onLoadFn, render: 'explicit' };
				scriptURL.query = $.extend( scriptURL.query, params );

				this.readyPromise = deferred.promise();
				window[ onLoadFn ] = deferred.resolve;
				mw.loader.load( scriptURL.toString() );
			}

			return this.readyPromise;
		};

		ve.init.mw.TurnstileSaveErrorHandler.static.matchFunction = function ( data ) {
			const captchaData = ve.getProp( data, 'visualeditoredit', 'edit', 'captcha' );

			return !!( captchaData && captchaData.type === 'turnstile' );
		};

		ve.init.mw.TurnstileSaveErrorHandler.static.process = function ( data, target ) {
			const self = this,
				config = mw.config.get( 'wgConfirmEditConfig' ),
				siteKey = config.turnstileSiteKey,
				$container = $( '<div>' );

			// Register extra fields
			target.saveFields.wpCaptchaWord = function () {
				// eslint-disable-next-line no-jquery/no-global-selector
				return $( '#cf-turnstile-response' ).val();
			};

			this.getReadyPromise()
				.then( function () {
					if ( self.widgetId ) {
						window.turnstile.reset( self.widgetId );
					} else {
						target.saveDialog.showMessage( 'api-save-error', $container, { wrap: false } );
						self.widgetId = window.turnstile.render( $container[ 0 ], {
							sitekey: siteKey,
							callback: function () {
								target.saveDialog.executeAction( 'save' );
							},
							'expired-callback': function () {},
							'error-callback': function () {}
						} );

						target.saveDialog.updateSize();
					}

					target.emit( 'saveErrorCaptcha' );
				} );
		};

		ve.init.mw.saveErrorHandlerFactory.register( ve.init.mw.TurnstileSaveErrorHandler );

	} );
} );
