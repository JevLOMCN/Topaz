/*!
 * VisualEditor MediaWiki Initialization DesktopArticleTarget tests.
 *
 * @copyright See AUTHORS.txt
 * @license The MIT License (MIT); see LICENSE.txt
 */

QUnit.module( 've.init.mw.DesktopArticleTarget', ve.test.utils.newMwEnvironment( {
	config: {
		wgVisualEditor: ve.extendObject( {}, mw.config.get( 'wgVisualEditor' ), {
			pageLanguageCode: 'he',
			pageLanguageDir: 'rtl'
		} ),
		wgVisualEditorConfig: ve.extendObject( {}, mw.config.get( 'wgVisualEditorConfig' ), {
			// Disable welcome dialog
			showBetaWelcome: false
		} ),
		wgAction: 'view',
		wgNamespaceNumber: 0,
		wgCanonicalNamespace: ''
	}
} ) );

QUnit.test( 'init', ( assert ) => {
	const response = {
			visualeditor: {
				result: 'success',
				notices: [
					'<b>HTML string notice</b> message',
					{
						type: 'object notice',
						message: '<b>object notice</b> message'
					}
				],
				copyrightWarning: '<div id="editpage-copywarn">Blah blah</div>',
				checkboxesDef: {
					wpMinoredit: {
						id: 'wpMinoredit',
						'label-message': 'minoredit',
						tooltip: 'minoredit',
						'label-id': 'mw-editpage-minoredit',
						'legacy-name': 'minor',
						default: false
					},
					wpWatchthis: {
						id: 'wpWatchthis',
						'label-message': 'watchthis',
						tooltip: 'watch',
						'label-id': 'mw-editpage-watch',
						'legacy-name': 'watch',
						default: true
					}
				},
				checkboxesMessages: {
					'accesskey-minoredit': 'i',
					'tooltip-minoredit': 'Mark this as a minor edit',
					minoredit: 'This is a minor edit',
					'accesskey-watch': 'w',
					'tooltip-watch': 'Add this page to your watchlist',
					watchthis: 'Watch this page'
				},
				protectedClasses: '',
				basetimestamp: '20161119005107',
				starttimestamp: '20180831122319',
				oldid: 1804,
				blockinfo: null,
				wouldautocreate: false,
				canEdit: true,
				content: '<!DOCTYPE html>\n' + ve.dm.example.singleLine`
					<html prefix="dc: http://purl.org/dc/terms/ mw: http://mediawiki.org/rdf/" about="http://localhost/MediaWiki/core/index.php/Special:Redirect/revision/1804">
						<head prefix="mwr: http://localhost/MediaWiki/core/index.php/Special:Redirect/"><meta property="mw:TimeUuid" content="a4fc0409-ad18-11e8-9b45-dd8cefbedb6d"/>
							<meta charset="utf-8"/>
							<meta property="mw:pageNamespace" content="0"/>
							<meta property="mw:pageId" content="643"/>
							<link rel="dc:replaces" resource="mwr:revision/0"/>
							<meta property="dc:modified" content="2016-11-19T00:51:07.000Z"/>
							<meta property="mw:revisionSHA1" content="da39a3ee5e6b4b0d3255bfef95601890afd80709"/>
							<meta property="mw:html:version" content="1.7.0"/>
							<link rel="dc:isVersionOf" href="http://localhost/MediaWiki/core/index.php/Empty"/>
							<title>Empty</title>
							<base href="http://localhost/MediaWiki/core/index.php/"/>
							<link rel="stylesheet" href="//localhost/MediaWiki/core/load.php?modules=mediawiki.legacy.commonPrint%2Cshared%7Cmediawiki.skinning.content.parsoid%7Cmediawiki.skinning.interface%7Cskins.vector.styles%7Csite.styles%7Cext.cite.style%7Cext.cite.styles%7Cmediawiki.page.gallery.styles&amp;only=styles&amp;skin=vector"/>
						</head>
						<body id="mwAA" lang="he" class="mw-content-rtl sitedir-rtl rtl mw-body-content parsoid-body mediawiki mw-parser-output" dir="rtl">
							<section data-mw-section-id="0" id="mwAQ"></section>
						</body>
					</html>
				`,
				preloaded: false,
				etag: '"1804/a4fc0409-ad18-11e8-9b45-dd8cefbedb6d"'
			}
		},
		target = new ve.init.mw.DesktopArticleTarget(),
		dataPromise = ve.createDeferred().resolve( response ).promise(),
		done = assert.async();

	target.on( 'surfaceReady', () => {
		assert.strictEqual( target.getSurface().getModel().getDocument().getLang(), 'he', 'Page language is passed through from config' );
		assert.strictEqual( target.getSurface().getModel().getDocument().getDir(), 'rtl', 'Page direction is passed through from config' );
		target.activatingDeferred.then( async () => {
			assert.equalDomElement(
				target.toolbar.tools.notices.noticeItems[ 0 ].$element[ 0 ],
				$( '<div class="ve-ui-mwNoticesPopupTool-item"><b>HTML string notice</b> message</div>' )[ 0 ],
				'HTML string notice message is passed through from API'
			);
			assert.strictEqual( target.toolbar.tools.notices.noticeItems[ 0 ].type, undefined, 'Plain text notice type is undefined' );
			assert.equalDomElement(
				target.toolbar.tools.notices.noticeItems[ 1 ].$element[ 0 ],
				$( '<div class="ve-ui-mwNoticesPopupTool-item"><b>object notice</b> message</div>' )[ 0 ],
				'Object notice message is passed through from API'
			);
			assert.strictEqual( target.toolbar.tools.notices.noticeItems[ 1 ].type, 'object notice', 'Object notice type is passed through from API' );

			// Open the save dialog and examine it (this bypasses a bunch of stuff, and may fail in funny
			// ways, but #showSaveDialog has many dependencies that I don't want to simulate here).
			const dialogs = target.getSurface().getDialogs();
			const instance = dialogs.openWindow( 'mwSave', target.getSaveDialogOpeningData() );
			await instance.opened;
			const dialog = dialogs.getCurrentWindow();
			assert.equalDomElement(
				dialog.$element.find( '#editpage-copywarn' )[ 0 ],
				$( '<div id="editpage-copywarn">Blah blah</div>' )[ 0 ],
				'Copyright warning message is passed through from API'
			);
			dialogs.closeWindow( 'mwSave' );
			await instance.closed;

			// Store doc state and examine it
			target.storeDocState();
			const storedData = JSON.parse( sessionStorage.getItem( 've-docstate' ) );
			const ignoredKeys = {
				// Not stored because it's always 'success'
				result: true,
				// Not stored because it's stored elsewhere
				content: true,
				// Not stored because if you're blocked, the editor opens in read-only mode (or doesn't open
				// at all, on mobile), so we'll never have to restore from auto-save
				blockinfo: true
			};
			Object.keys( response.visualeditor ).forEach( ( key ) => {
				if ( !ignoredKeys[ key ] ) {
					assert.deepEqual(
						storedData.response[ key ],
						response.visualeditor[ key ],
						key + ' can be restored from auto-save data'
					);
				}
			} );

			await target.destroy();
			done();
		} );
	} );
	target.activate( dataPromise );
} );

QUnit.test( 'compatibility', ( assert ) => {
	const cases = [
			{
				msg: 'Unidentified browser',
				userAgent: 'FooBar Browser Company Version 3.141',
				matches: []
			},
			{
				msg: 'IE11',
				userAgent: 'Mozilla/5.0 (Windows NT 6.3; WOW64; Trident/7.0; .NET4.0E; .NET4.0C; rv:11.0) like Gecko',
				matches: [ 'supportedList' ]
			},
			{
				msg: 'Edge 12',
				userAgent: 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/42.0.2311.135 Safari/537.36 Edge/12.246',
				matches: [ 'supportedList' ]
			},
			{
				msg: 'Firefox 10',
				userAgent: 'Mozilla/5.0 (X11; Mageia; Linux x86_64; rv:10.0.9) Gecko/20100101 Firefox/10.0.9',
				matches: [ 'unsupportedList' ]
			},
			{
				msg: 'Firefox 11',
				userAgent: 'Mozilla/5.0 (Windows NT 6.1; U;WOW64; de;rv:11.0) Gecko Firefox/11.0',
				matches: [ 'unsupportedList' ]
			},
			{
				msg: 'Firefox 12',
				userAgent: 'Mozilla/5.0 (compatible; Windows; U; Windows NT 6.2; WOW64; en-US; rv:12.0) Gecko/20120403211507 Firefox/12.0',
				matches: [ 'supportedList' ]
			},
			{
				msg: 'Firefox 13',
				userAgent: 'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:13.0) Gecko/20100101 Firefox/13.0',
				matches: [ 'supportedList' ]
			},
			{
				msg: 'Firefox 14',
				userAgent: 'Mozilla/5.0 (Windows NT 6.1; rv:12.0) Gecko/20120403211507 Firefox/14.0.1',
				matches: [ 'supportedList' ]
			},
			{
				msg: 'Firefox 15',
				userAgent: 'Mozilla/5.0 (X11; Ubuntu; Linux i686; rv:15.0) Gecko/20100101 Firefox/15.0.1',
				matches: [ 'supportedList' ]
			},
			{
				msg: 'Firefox 24',
				userAgent: 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10.8; rv:24.0) Gecko/20100101 Firefox/24.0',
				matches: [ 'supportedList' ]
			},
			{
				msg: 'Safari 4',
				userAgent: 'Mozilla/5.0 (Macintosh; U; Intel Mac OS X 10_6_3; en-us) AppleWebKit/531.21.11 (KHTML, like Gecko) Version/4.0.4 Safari/531.21.10',
				matches: [ 'unsupportedList' ]
			},
			{
				msg: 'Safari 5',
				userAgent: 'Mozilla/5.0 (Macintosh; U; Intel Mac OS X 10_6_3; en-us) AppleWebKit/534.1+ (KHTML, like Gecko) Version/5.0 Safari/533.16',
				matches: [ 'unsupportedList' ]
			},
			{
				msg: 'Safari 6',
				userAgent: 'Mozilla/5.0 (Macintosh; Intel Mac OS X 1084) AppleWebKit/536.30.1 (KHTML like Gecko) Version/6.0.5 Safari/536.30.1',
				matches: [ 'unsupportedList' ]
			},
			{
				msg: 'Safari 7',
				userAgent: 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_9_3) AppleWebKit/537.75.14 (KHTML, like Gecko) Version/7.0.3 Safari/7046A194A',
				matches: [ 'supportedList' ]
			},
			{
				msg: 'Safari 8',
				userAgent: 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_10_2) AppleWebKit/600.3.18 (KHTML, like Gecko) Version/8.0.3 Safari/600.3.18',
				matches: [ 'supportedList' ]
			},
			{
				msg: 'Chrome 18',
				userAgent: 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_5_8) AppleWebKit/535.19 (KHTML, like Gecko) Chrome/18.0.1025.151 Safari/535.19',
				matches: []
			},
			{
				msg: 'Chrome 19',
				userAgent: 'Mozilla/5.0 (Windows NT 6.2) AppleWebKit/536.3 (KHTML, like Gecko) Chrome/19.0.1061.0 Safari/536.3',
				matches: [ 'supportedList' ]
			},
			{
				msg: 'Chrome 27',
				userAgent: 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/27.0.1453.93 Safari/537.36',
				matches: [ 'supportedList' ]
			},
			{
				msg: 'Android 2.3',
				userAgent: 'Mozilla/5.0 (Linux; U; Android 2.3.5; en-us; HTC Vision Build/GRI40) AppleWebKit/533.1 (KHTML, like Gecko) Version/4.0 Mobile Safari/533.1',
				matches: []
			},
			{
				msg: 'Android 3.0',
				userAgent: 'Mozilla/5.0 (Linux; U; Android 3.0; en-us; Xoom Build/HRI39) AppleWebKit/534.13 (KHTML, like Gecko) Version/4.0 Safari/534.13',
				matches: []
			},
			{
				msg: 'Android 4.0',
				userAgent: 'Mozilla/5.0 (Linux; U; Android 4.0.3; HTC Sensation Build/IML74K) AppleWebKit/534.30 (KHTML, like Gecko) Version/4.0 Mobile Safari/534.30',
				matches: []
			},
			{
				msg: 'Opera 11',
				userAgent: 'Opera/9.80 (Windows NT 5.1) Presto/2.10.229 Version/11.64',
				matches: [ 'unsupportedList' ]
			},
			{
				msg: 'Opera 12.16',
				userAgent: 'Opera/9.80 (Windows NT 5.1) Presto/2.12.388 Version/12.16',
				matches: []
			},
			{
				msg: 'Opera 15.0',
				userAgent: 'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/28.0.1500.52 Safari/537.36 OPR/15.0.1147.100',
				matches: [ 'supportedList' ]
			},
			{
				msg: 'Amazon Silk desktop',
				userAgent: 'Mozilla/5.0 (Macintosh; U; Intel Mac OS X 10_6_3; en-us; Silk/1.0.13.81_10003810) AppleWebKit/533.16 (KHTML, like Gecko) Version/5.0 Safari/533.16 Silk-Accelerated=true',
				matches: []
			},
			{
				msg: 'Amazon Silk mobile',
				userAgent: 'Mozilla/5.0 (Linux; U; Android 4.0.3; en-us; KFTT Build/IML74K) AppleWebKit/535.19 (KHTML, like Gecko) Silk/2.1 Mobile Safari/535.19 Silk-Accelerated=true',
				matches: []
			}
		];

	const compatibility = {
		supportedList: ve.init.mw.DesktopArticleTarget.static.compatibility.supportedList,
		// TODO: Fix this mess when we split ve.init from ve.platform
		unsupportedList: mw.libs.ve.unsupportedList
	};

	cases.forEach( ( caseItem ) => {
		const profile = $.client.profile( { userAgent: caseItem.userAgent, platform: '' } ),
			matches = [];
		[ 'unsupportedList', 'supportedList' ].every( ( list ) => {
			if ( $.client.test( compatibility[ list ], profile, true ) ) {
				matches.push( list );
				// Don't check supportedList if on unsupportedList
				return false;
			}
			return true;
		} );
		assert.deepEqual( matches, caseItem.matches,
			caseItem.msg + ': ' + ( caseItem.matches.length ? caseItem.matches.join() : 'greylist (no matches)' ) );
	} );
} );
