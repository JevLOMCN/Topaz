'use strict';

const Page = require( 'wdio-mediawiki/Page' );

class NotificationsPage extends Page {

	get notificationHeading() {
		return $( '#firstHeading' );
	}

	open() {
		super.openTitle( 'Special:Notifications', { uselang: 'en' } );
	}
}

module.exports = new NotificationsPage();
