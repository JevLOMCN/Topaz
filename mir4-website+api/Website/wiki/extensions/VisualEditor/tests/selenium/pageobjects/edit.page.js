'use strict';
const Page = require( 'wdio-mediawiki/Page' );

class EditPage extends Page {

	get bulletListOption() {
		return $( '.oo-ui-tool-name-bullet' );
	}

	get boldTextStyleOption() {
		return $( '.oo-ui-tool-name-bold' );
	}

	get content() {
		return $( '#content' );
	}

	get edited() {
		return $( '*=Your edit was saved' );
	}

	get formatParagraphElement() {
		return $( '.ve-ui-toolbar-group-format' );
	}

	get helpPopup() {
		return $( '.ve-ui-mwHelpListToolGroup-tools' );
	}

	get helpElement() {
		return $( '.ve-ui-toolbar-group-help' );
	}

	get insert() {
		return $( '.ve-ui-toolbar-group-insert' );
	}

	get insertMenu() {
		return $( '.oo-ui-tool-name-media' );
	}

	get insertTableElement() {
		return $( '.oo-ui-tool-name-insertTable' );
	}

	get insertedTable() {
		return $( 'table.ve-ce-branchNode' );
	}

	get notices() {
		return $( '.ve-ui-mwNoticesPopupTool-items' );
	}

	get notification() {
		return $( 'div.mw-notification-content span.oo-ui-labelElement-label' );
	}

	get options() {
		return $( '.oo-ui-tool-name-meta' );
	}

	get pageOptionsElement() {
		return $( '.ve-ui-toolbar-group-pageMenu' );
	}

	get paragraphFormatMenu() {
		return $( '.oo-ui-tool-name-paragraph' );
	}

	get popupToolGroup() {
		return $( '.oo-ui-popupToolGroup-active-tools' );
	}

	get savePage() {
		return $( '.ve-ui-overlay-global .oo-ui-processDialog-actions-primary' );
	}

	get savePageDots() {
		return $( '.ve-ui-toolbar-saveButton' );
	}

	get specialCharacterElement() {
		return $( '.oo-ui-tool-name-specialCharacter' );
	}

	get specialCharacterMenu() {
		return $( '.oo-ui-menuLayout' );
	}

	get structureOptionsElement() {
		return $( '.ve-ui-toolbar-group-structure' );
	}

	get styleTextElement() {
		return $( '.ve-ui-toolbar-group-style' );
	}

	get switchEditorElement() {
		return $( '.ve-ui-toolbar-group-editMode' );
	}

	get toolbar() {
		return $( '.ve-init-mw-desktopArticleTarget-toolbar-open' );
	}

	get veBodyContent() {
		return $( '.mw-body-content.ve-ui-surface' );
	}

	get veRootNode() {
		return $( '.ve-ce-rootNode[role="textbox"]' );
	}

	get visualEditing() {
		return $( '.oo-ui-tool-name-editModeVisual' );
	}

	openForEditing( title ) {
		super.openTitle( title, { veaction: 'edit', cxhidebetapopup: 1, hidewelcomedialog: 1, vehidebetadialog: 1 } );
	}

	activationComplete() {
		return browser.executeAsync( function ( done ) {
			mw.hook( 've.activationComplete' ).add( function () {
				done();
			} );
		} );
	}

	async insertTable() {
		await this.insert.click();
		await this.insertTableElement.click();
	}

	saveComplete() {
		return browser.executeAsync( function ( done ) {
			ve.init.target.on( 'save', function () {
				done();
			} );
		} );
	}

}
module.exports = new EditPage();
