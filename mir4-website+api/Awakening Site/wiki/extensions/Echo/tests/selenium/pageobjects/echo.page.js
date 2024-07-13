'use strict';
const Page = require( 'wdio-mediawiki/Page' );

class EchoPage extends Page {

	get alerts() {
		return $( '#pt-notifications-alert' );
	}

	get notices() {
		return $( '#pt-notifications-notice' );
	}

	get alertsFlyout() {
		return $( '.oo-ui-labelElement-label*=Alerts' );
	}

	get noticesFlyout() {
		return $( '.oo-ui-labelElement-label*=Notices' );
	}

	get alertMessage() {
		return $( '.mw-echo-ui-notificationItemWidget-content-message-header' );
	}

}
module.exports = new EchoPage();
