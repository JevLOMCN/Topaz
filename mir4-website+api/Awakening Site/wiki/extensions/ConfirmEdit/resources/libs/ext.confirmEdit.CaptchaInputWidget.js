mw.libs.confirmEdit = {};

/**
 * @class
 * @extends OO.ui.TextInputWidget
 *
 * @constructor
 * @param {Object} [captchaData] Value of 'captcha' property returned from action=edit API
 * @param {Object} [config] Configuration options
 */
mw.libs.confirmEdit.CaptchaInputWidget = function MwWidgetsCaptchaInputWidget( captchaData, config ) {
	config = config || {};

	// Parent constructor
	mw.libs.confirmEdit.CaptchaInputWidget.parent.call( this, config );

	// Properties
	this.$captchaImg = null;
	this.captchaId = null;

	// Initialization
	this.$element.addClass( 'mw-confirmEdit-captchaInputWidget' );
	this.$element.prepend( this.makeCaptchaInterface( captchaData ) );
};

/* Setup */

OO.inheritClass( mw.libs.confirmEdit.CaptchaInputWidget, OO.ui.TextInputWidget );

/* Events */

/**
 * @event load
 *
 * A load event is emitted when the CAPTCHA image loads.
 */

/* Methods */

mw.libs.confirmEdit.CaptchaInputWidget.prototype.makeCaptchaInterface = function ( captchaData ) {
	let $captchaImg, msg, question;

	const $captchaParagraph = $( '<div>' ).append(
		$( '<strong>' ).text( mw.msg( 'captcha-label' ) ),
		document.createTextNode( mw.msg( 'colon-separator' ) )
	);
	const $captchaDiv = $( '<div>' ).append( $captchaParagraph );

	if ( captchaData.url ) {
		// FancyCaptcha
		// Based on FancyCaptcha::getFormInformation() and ext.confirmEdit.fancyCaptcha.js
		this.$input.attr( 'placeholder', mw.msg( 'fancycaptcha-imgcaptcha-ph' ) );
		mw.loader.load( 'ext.confirmEdit.fancyCaptcha' );
		$captchaDiv.addClass( 'fancycaptcha-captcha-container' );
		$captchaParagraph.append( mw.message( 'fancycaptcha-edit' ).parseDom() );
		$captchaImg = $( '<img>' )
			.attr( 'src', captchaData.url )
			.data( 'captchaId', captchaData.id )
			.addClass( 'fancycaptcha-image' )
			.on( 'load', this.emit.bind( this, 'load' ) );
		$captchaDiv.append(
			$captchaImg,
			' ',
			$( '<a>' ).addClass( 'fancycaptcha-reload' ).text( mw.msg( 'fancycaptcha-reload-text' ) )
		);
	} else {
		if ( captchaData.type === 'simple' || captchaData.type === 'math' ) {
			// SimpleCaptcha and MathCaptcha
			msg = 'captcha-edit';
		} else if ( captchaData.type === 'question' ) {
			// QuestyCaptcha
			msg = 'questycaptcha-edit';
		}

		if ( msg ) {
			switch ( captchaData.mime ) {
				case 'text/html':
					question = $.parseHTML( captchaData.question );
					break;
				case 'text/plain':
					question = document.createTextNode( captchaData.question );
					break;
			}
			// Messages documented above
			// eslint-disable-next-line mediawiki/msg-doc
			$captchaParagraph.append( mw.message( msg ).parseDom(), '<br>', question );
		}
	}

	if ( $captchaImg ) {
		this.$captchaImg = $captchaImg;
	} else {
		this.captchaId = captchaData.id;
	}

	return $captchaDiv;
};

mw.libs.confirmEdit.CaptchaInputWidget.prototype.getCaptchaId = function () {
	// 'ext.confirmEdit.fancyCaptcha' can update this value if the "Refresh" button is used
	return this.$captchaImg ? this.$captchaImg.data( 'captchaId' ) : this.captchaId;
};

mw.libs.confirmEdit.CaptchaInputWidget.prototype.getCaptchaWord = function () {
	return this.getValue();
};
