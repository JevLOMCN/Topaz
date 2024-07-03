/*!
 * VisualEditor user interface MWVESwitchPopupWidget class.
 *
 * @copyright See AUTHORS.txt
 * @license The MIT License (MIT); see LICENSE.txt
 */

mw.libs.ve = mw.libs.ve || {};
/**
 * @class
 * @extends OO.ui.PopupWidget
 *
 * @constructor
 * @param {string} mode Current edit mode
 * @param {Object} [config] Configuration options
 */
mw.libs.ve.SwitchPopupWidget = function MWLibsVESwitchPopupWidget( mode, config ) {
	var prefix = mode === 'visual' ? 'visualeditor-mweditmodewt' : 'visualeditor-mweditmodeve',
		option = mode === 'visual' ? 'visualeditor-hidevisualswitchpopup' : 'visualeditor-hidesourceswitchpopup';

	// Parent constructor
	mw.libs.ve.SwitchPopupWidget.super.call( this, $.extend( {
		autoClose: true,
		head: true,
		// The following messages are used here:
		// * visualeditor-mweditmodewt-popup-title
		// * visualeditor-mweditmodeve-popup-title
		label: mw.msg( prefix + '-popup-title' ),
		padded: true
	}, config ) );

	// The following messages are used here:
	// * visualeditor-mweditmodewt-popup-body
	// * visualeditor-mweditmodeve-popup-body
	var $content = $( '<p>' ).text( mw.msg( prefix + '-popup-body' ) );

	if ( mw.user.isNamed() ) {
		var showAgainCheckbox = new OO.ui.CheckboxInputWidget()
			.on( 'change', function ( value ) {
				var configValue = value ? '1' : '';
				new mw.Api().saveOption( option, configValue );
				mw.user.options.set( option, configValue );
			} );

		var showAgainLayout = new OO.ui.FieldLayout( showAgainCheckbox, {
			align: 'inline',
			label: mw.msg( 'visualeditor-mweditmodeve-showagain' )
		} );
		$content = $content.add( showAgainLayout.$element );
	}

	this.$body.append( $content );

	this.$element
		// HACK: Pretend to be a PopupTool
		// TODO: Create upstream PopupListToolGroup
		.addClass( 've-init-mw-switchPopupWidget' );
};

/* Inheritance */

OO.inheritClass( mw.libs.ve.SwitchPopupWidget, OO.ui.PopupWidget );
