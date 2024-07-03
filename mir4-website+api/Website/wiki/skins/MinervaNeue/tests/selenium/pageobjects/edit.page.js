'use strict';

const Page = require( 'wdio-mediawiki/Page' );

class EditPage extends Page {
	get content() {
		return $( '#wikitext-editor' );
	}

	get displayedContent() {
		return $( '#mw-content-text .mw-parser-output' );
	}

	get heading() {
		return $( 'h1.mw-first-heading' );
	}

	// Sync with src/mobile.editor.overlay/EditorOverlayBase.js in MobileFrontend
	get next() {
		return $( '.mf-icon-next-invert' );
	}

	get save() {
		return $( 'button.cdx-button' );
	}

	openForEditing( title ) {
		super.openTitle( title, { action: 'edit', mobileaction: 'toggle_view_mobile' } );
	}

	edit( name, content ) {
		this.openForEditing( name );
		this.content.setValue( content );
		this.next.click();
		this.save.click();
		browser.acceptAlert();
	}
}

module.exports = new EditPage();
