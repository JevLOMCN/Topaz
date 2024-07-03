/**
 * MediaWiki:Gadget-codeeditor.js
 * (c) 2011 Brion Vibber <brion @ pobox.com>
 * GPLv2 or later
 *
 * Syntax highlighting, auto-indenting code editor widget for on-wiki JS and CSS pages.
 * Uses embedded Ajax.org Cloud9 Editor: https://ace.c9.io/
 *
 * Known issues:
 * - extension version doesn't have optional bits correct
 * - ties into WikiEditor, so doesn't work on classic toolbar
 * - background worker for JS syntax check doesn't load in non-debug mode (probably also fails if extension assets are offsite)
 * - copy/paste not available from context menu (Firefox, Chrome on Linux -- kbd & main menu commands ok)
 * - accessibility: tab/shift-tab are overridden. is there a consistent alternative for keyboard-reliant users?
 * - accessibility: accesskey on the original textarea needs to be moved over or otherwise handled
 * - scrollbar initializes too wide; need to trigger resize check after that's filled
 */
/*
 * JavaScript for WikiEditor Table of Contents
 */

$( function () {
	// eslint-disable-next-line no-jquery/no-global-selector
	var $wpTextbox1 = $( '#wpTextbox1' );

	// Code is supposed to be always LTR. See bug 39364.
	$wpTextbox1.parent().prop( 'dir', 'ltr' );

	// Add code editor module
	$wpTextbox1.wikiEditor( 'addModule', 'codeEditor' );

	$wpTextbox1.on( 'wikiEditor-toolbar-doneInitialSections', function () {
		$wpTextbox1.data( 'wikiEditor-context' ).fn.codeEditorMonitorFragment();
	} );
} );
