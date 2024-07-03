// Captcha "errors" usually aren't errors. We simply don't know about them ahead of time,
// so we save once, then (if required) we get an error with a captcha back and try again after
// the user solved the captcha.
// TODO: ConfirmEdit API is horrible, there is no reliable way to know whether it is a "math",
// "question" or "fancy" type of captcha. They all expose differently named properties in the
// API for different things in the UI. At this point we only support the SimpleCaptcha and
// FancyCaptcha which we very intuitively detect by the presence of a "url" property.
mw.loader.using( 'ext.visualEditor.targetLoader' ).then( function () {
	mw.libs.ve.targetLoader.addPlugin( function () {

		ve.init.mw.CaptchaSaveErrorHandler = function () {};

		OO.inheritClass( ve.init.mw.CaptchaSaveErrorHandler, ve.init.mw.SaveErrorHandler );

		ve.init.mw.CaptchaSaveErrorHandler.static.name = 'confirmEditCaptchas';

		ve.init.mw.CaptchaSaveErrorHandler.static.matchFunction = function ( data ) {
			const captchaData = ve.getProp( data, 'visualeditoredit', 'edit', 'captcha' );

			return !!( captchaData && (
				captchaData.url ||
				captchaData.type === 'simple' ||
				captchaData.type === 'math' ||
				captchaData.type === 'question'
			) );
		};

		ve.init.mw.CaptchaSaveErrorHandler.static.process = function ( data, target ) {
			const captchaInput = new mw.libs.confirmEdit.CaptchaInputWidget(
				ve.getProp( data, 'visualeditoredit', 'edit', 'captcha' )
			);
			ve.targetLinksToNewWindow( captchaInput.$element[ 0 ] );

			function onCaptchaLoad() {
				target.saveDialog.updateSize();
				captchaInput.focus();
				captchaInput.scrollElementIntoView();
			}

			captchaInput.on( 'load', onCaptchaLoad );
			// Save when pressing 'Enter' in captcha field as it is single line.
			captchaInput.on( 'enter', function () {
				target.saveDialog.executeAction( 'save' );
			} );

			// Register extra fields
			target.saveFields.wpCaptchaId = function () {
				return captchaInput.getCaptchaId();
			};
			target.saveFields.wpCaptchaWord = function () {
				return captchaInput.getCaptchaWord();
			};
			// Unregister extra fields on save attempt
			target.saveDialog.once( 'save', function () {
				delete target.saveFields.wpCaptchaId;
				delete target.saveFields.wpCaptchaWord;
			} );

			// ProcessDialog's error system isn't great for this yet.
			target.saveDialog.clearMessage( 'api-save-error' );
			target.saveDialog.showMessage( 'api-save-error', captchaInput.$element, { wrap: false } );
			target.saveDialog.popPending();
			onCaptchaLoad();

			// Emit event for tracking. TODO: This is a bad design
			target.emit( 'saveErrorCaptcha' );
		};

		ve.init.mw.saveErrorHandlerFactory.register( ve.init.mw.CaptchaSaveErrorHandler );

	} );
} );
