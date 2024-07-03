/*!
 * TemplateData Generator edit template page init
 *
 * @author Moriel Schottlender
 * @author Ed Sanders
 */

/* global ve */
/* eslint-disable no-jquery/no-global-selector */

'use strict';

new mw.Api().loadMessages( 'templatedata-doc-subpage', { amlang: mw.config.get( 'wgContentLanguage' ) } ).then( function () {
	var Target = require( './Target.js' ),
		pageName = mw.config.get( 'wgPageName' ),
		docSubpage = mw.msg( 'templatedata-doc-subpage' ),
		config = {
			pageName: pageName,
			isPageSubLevel: false
		},
		$textbox = $( '#wpTextbox1' );

	var pieces = pageName.split( '/' );
	var isDocPage = pieces.length > 1 && pieces[ pieces.length - 1 ] === docSubpage;
	var openTDG = new URL( location.href ).searchParams.get( 'templatedata' ) === 'edit';

	config = {
		pageName: pageName,
		isPageSubLevel: pieces.length > 1,
		parentPage: pageName,
		isDocPage: isDocPage,
		docSubpage: docSubpage
	};

	// Only if we are in a doc page do we set the parent page to
	// the one above. Otherwise, all parent pages are current pages
	if ( isDocPage ) {
		pieces.pop();
		config.parentPage = pieces.join( '/' );
	}

	// Textbox wikitext editor
	if ( $textbox.length ) {
		// Prepare the editor
		var wtTarget = new Target( $textbox, config );
		$( '.tdg-editscreen-placeholder' ).replaceWith( wtTarget.$element );
		if ( openTDG ) {
			wtTarget.onEditOpenDialogButton();
		}
	}
	var veTarget;
	// Visual editor source mode
	mw.hook( 've.activationComplete' ).add( function () {
		var surface = ve.init.target.getSurface();
		if ( surface.getMode() === 'source' ) {
			// Source mode will have created a dummy textbox
			$textbox = $( '#wpTextbox1' );
			veTarget = new Target( $textbox, config );
			// Use the same font size as main content text
			veTarget.$element.addClass( 'mw-body-content' );
			$( '.ve-init-mw-desktopArticleTarget-originalContent' ).prepend( veTarget.$element );

			if ( openTDG ) {
				veTarget.onEditOpenDialogButton();
			}
		}
	} );
	mw.hook( 've.deactivationComplete' ).add( function () {
		if ( veTarget ) {
			veTarget.destroy();
			veTarget = null;
		}
	} );
} );
