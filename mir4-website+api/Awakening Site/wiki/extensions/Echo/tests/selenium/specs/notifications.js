'use strict';

const assert = require( 'assert' ),
	NotificationsPage = require( '../pageobjects/notifications.page' ),
	UserLoginPage = require( 'wdio-mediawiki/LoginPage' );

describe( 'Notifications', function () {

	it( 'checks for Notifications Page @daily', async function () {

		await UserLoginPage.login( browser.config.mwUser, browser.config.mwPwd );
		await NotificationsPage.open();

		assert.strictEqual( await NotificationsPage.notificationHeading.getText(), 'Notifications' );

	} );

} );
