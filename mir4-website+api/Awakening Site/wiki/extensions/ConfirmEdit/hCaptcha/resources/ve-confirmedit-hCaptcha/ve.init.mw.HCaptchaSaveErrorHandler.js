mw.loader.using( 'ext.visualEditor.targetLoader' ).then( function () {
	mw.libs.ve.targetLoader.addPlugin( function () {
		ve.init.mw.HCaptchaSaveErrorHandler = function () {};

		OO.inheritClass( ve.init.mw.HCaptchaSaveErrorHandler, ve.init.mw.SaveErrorHandler );

		ve.init.mw.HCaptchaSaveErrorHandler.static.name = 'confirmEditHCaptcha';

		ve.init.mw.HCaptchaSaveErrorHandler.static.getReadyPromise = function () {
			const onLoadFn = 'onHcaptchaLoadCallback' + Date.now();
			let deferred, scriptURL, params;

			if ( !this.readyPromise ) {
				deferred = $.Deferred();
				scriptURL = new mw.Uri( require( './config.json' ).hCaptchaScriptURL );
				params = { onload: onLoadFn, render: 'explicit' };
				scriptURL.query = $.extend( scriptURL.query, params );

				this.readyPromise = deferred.promise();
				window[ onLoadFn ] = deferred.resolve;
				mw.loader.load( scriptURL.toString() );
			}

			return this.readyPromise;
		};

		ve.init.mw.HCaptchaSaveErrorHandler.static.matchFunction = function ( data ) {
			const captchaData = ve.getProp( data, 'visualeditoredit', 'edit', 'captcha' );

			return !!( captchaData && captchaData.type === 'hcaptcha' );
		};

		ve.init.mw.HCaptchaSaveErrorHandler.static.process = function ( data, target ) {
			const self = this,
				siteKey = require( './config.json' ).hCaptchaSiteKey,
				$container = $( '<div>' );

			// Register extra fields
			target.saveFields.wpCaptchaWord = function () {
				// eslint-disable-next-line no-jquery/no-global-selector
				return $( '[name=h-captcha-response]' ).val();
			};

			this.getReadyPromise()
				.then( function () {
					// ProcessDialog's error system isn't great for this yet.
					target.saveDialog.clearMessage( 'api-save-error' );
					target.saveDialog.showMessage( 'api-save-error', $container, { wrap: false } );
					self.widgetId = window.hcaptcha.render( $container[ 0 ], {
						sitekey: siteKey,
						callback: function () {
							target.saveDialog.executeAction( 'save' );
						},
						'expired-callback': function () {},
						'error-callback': function () {}
					} );
					target.saveDialog.popPending();
					target.saveDialog.updateSize();

					target.emit( 'saveErrorCaptcha' );
				} );
		};

		ve.init.mw.saveErrorHandlerFactory.register( ve.init.mw.HCaptchaSaveErrorHandler );
	} );
} );
