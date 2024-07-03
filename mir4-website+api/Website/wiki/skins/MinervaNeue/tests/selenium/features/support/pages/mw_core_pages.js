/**
 * A list of all MediaWiki core pageObjects.
 * To simplify imports in world.js.
 */

'use strict';

module.exports = {
	// Page is a constructor, all other pageObjects are instances.
	Page: require( 'wdio-mediawiki/Page' ),
	UserLoginPage: require( 'wdio-mediawiki/LoginPage' )
};
