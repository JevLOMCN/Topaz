'use strict';

const Page = require( 'wdio-mediawiki/Page' );

class ViewListPage extends Page {
	get title() {
		return $( '#firstHeading' );
	}

	get newFilterButton() {
		return $( '.oo-ui-buttonElement a' );
	}

	get filterSavedNotice() {
		return $( '.mw-message-box-success' );
	}

	async savedFilterID() {
		const successElement = await this.filterSavedNotice;
		const succesMsg = await successElement.getHTML();
		const regexp = /\/history\/(\d+)\//;
		return regexp.exec( succesMsg )[ 1 ];
	}

	async savedFilterHistoryID() {
		const successElement = await this.filterSavedNotice;
		const succesMsg = await successElement.getHTML();
		const regexp = /\/diff\/prev\/(\d+)/;
		return regexp.exec( succesMsg )[ 1 ];
	}

	open() {
		super.openTitle( 'Special:AbuseFilter' );
	}
}
module.exports = new ViewListPage();
