'use strict';

const accessKey = process.env.SAUCE_ONDEMAND_ACCESS_KEY,
	Builder = require( 'selenium-webdriver' ).Builder,
	fs = require( 'fs' ),
	Jimp = require( 'jimp' ),
	username = process.env.SAUCE_ONDEMAND_USERNAME,
	webdriver = require( 'selenium-webdriver' ),
	TIMEOUT = 10 * 1000;

function createScreenshotEnvironment( test ) {
	let clientSize, driver;

	test.beforeEach( function () {
		const lang = this.currentTest.parent.lang || 'en';

		// Use Sauce Labs when running on Jenins
		if ( process.env.JENKINS_URL ) {
			driver = new webdriver.Builder().withCapabilities( {
				browserName: process.env.BROWSER,
				platform: process.env.PLATFORM,
				screenResolution: '1280x1024',
				username: username,
				accessKey: accessKey
			} ).usingServer( 'http://' + username + ':' + accessKey +
				'@ondemand.saucelabs.com:80/wd/hub' ).build();
		} else {
			// If not running on Jenkins, use local browser
			driver = new Builder().forBrowser( 'chrome' ).build();
		}

		driver.manage().timeouts().setScriptTimeout( TIMEOUT );
		driver.manage().window().setSize( 1200, 1000 );

		driver.get( 'https://en.wikipedia.org/wiki/Help:Sample_page?veaction=edit&vehidebetadialog=1&uselang=' + lang )
			.then( null, function ( e ) {
				console.error( e.message );
			} );
		driver.wait(
			driver.executeAsyncScript(
				require( './screenshots-client/utils.js' )
			).then( function ( cs ) {
				clientSize = cs;
			}, function ( e ) {
				// Log error (timeout)
				console.error( e.message );
				// Setup failed, set clientSize to null so no screenshots are generated
				clientSize = null;
			} )
		);
	} );

	test.afterEach( function () {
		driver.quit()
			.then( null, function ( e ) {
				console.error( e.message );
			} );
	} );

	function cropScreenshot( filename, imageBuffer, rect, padding ) {
		if ( padding === undefined ) {
			padding = 5;
		}

		const left = Math.max( 0, rect.left - padding );
		const top = Math.max( 0, rect.top - padding );
		const right = Math.min( clientSize.width, rect.left + rect.width + padding );
		const bottom = Math.min( clientSize.height, rect.top + rect.height + padding );

		return Jimp.read( imageBuffer ).then( function ( jimpImage ) {
			try {
				jimpImage
					.crop( left, top, right - left, bottom - top )
					.write( filename );
			} catch ( e ) {
				// Log error (memory?)
				console.error( e );
			}
		} );
	}

	function runScreenshotTest( lang, name, clientScript, padding, teardownScript ) {
		if ( !clientSize ) {
			// Setup failed, don't generated a broken screenshot
			return;
		}

		const filename = './screenshots/' + name + '-' + lang + '.png';

		driver.manage().timeouts().setScriptTimeout( TIMEOUT );
		driver.wait(
			driver.executeAsyncScript( clientScript ).then( function ( rect ) {
				return driver.takeScreenshot().then( function ( base64Image ) {
					if ( rect ) {
						const imageBuffer = Buffer.from( base64Image, 'base64' );
						return cropScreenshot( filename, imageBuffer, rect, padding );
					} else {
						fs.writeFile( filename, base64Image, 'base64' );
					}
				} ).then( function () {
					if ( teardownScript ) {
						return driver.executeAsyncScript( teardownScript );
					}
				} );
			}, function ( e ) {
				// Log error (timeout)
				console.error( e );
			} )
		);
	}

	return runScreenshotTest;
}

module.exports.createScreenshotEnvironment = createScreenshotEnvironment;
