( function () {
	window.enom = {};
	var enom = window.enom;
	var mw = window.mw;
	// console.log('enom: EditNoticesOnMobile loaded');
	if ( !window.location.host.match( /(^|\.)m\./ ) && mw.config.get( 'skin' ) === 'minerva' ) { // desktop Minerva! The edit notice is here but it's playing hide-and-seek. child's play
		// console.log('enom: unhide notices on desktop Minerva');
		mw.util.addCSS( '.content.fmbox,.content.tmbox {display:unset !important} .mw-editnotice * {display:unset !important}' );
		// eslint-disable-next-line no-jquery/no-global-selector
		$( '.editnotice-page .fmbox,.editnotice-page .tmbox,.editnotice-page .tmbox-content' ).removeClass( [ 'fmbox', 'tmbox', 'tmbox-content' ] );
	} else if ( mw.config.get( 'skin' ) === 'minerva' ) { // mobile Minerva, a bit more complicated. need to get the notice and create a popup
		// console.log('enom: you are on the mobile domain I think');
		enom.time = Date.now();
		enom.testValidJSON = function ( string ) {
			if ( string === null ) {
				return false;
			}
			try {
				JSON.parse( string );
			} catch ( e ) {
				return false;
			}
			return true;
		};
		enom.storeNotice = function ( notice ) {
			if ( window.localStorage && !enom.testValidJSON( window.localStorage.ENOM ) ) {
				window.localStorage.setItem( 'ENOM', '{}' );
			}
			if ( window.localStorage && enom.testValidJSON( window.localStorage.ENOM ) ) { // not sure how all browsers behave if localStorage is unavailable, so to be safe, test validity
				enom.cachedNotices = JSON.parse( window.localStorage.ENOM );
				enom.cachedNotices[ mw.config.get( 'wgPageName' ) ] = { text: notice, date: enom.time };
				enom.cachedNoticesNew = JSON.stringify( enom.cachedNotices );
				if ( enom.cachedNoticesNew.length > 1000000 ) { // it's quite unlikely a user would accumulate >1MB of edit notices, but if it somehow happens we purge all and start with a clean slate
					enom.cachedNoticesNew = '{}';
				}
				window.localStorage.setItem( 'ENOM', enom.cachedNoticesNew );
			}
		};
		enom.getNotice = function () {
			if ( enom.trigger && enom.trigger.target && enom.trigger.target.href ) {
				enom.pageTitle = decodeURIComponent( enom.trigger.target.href.match( /.*title=([^&]*).*/ )[ 1 ] );
			} else {
				enom.pageTitle = mw.config.get( 'wgPageName' );
			}
			enom.newNotice = false;
			if ( window.localStorage && enom.testValidJSON( window.localStorage.ENOM ) ) {
				enom.cachedNotices = JSON.parse( window.localStorage.ENOM );
				enom.update = false;
				for ( enom.cachedInt = 0; enom.cachedInt < Object.keys( enom.cachedNotices ).length; enom.cachedInt++ ) {
					enom.checkEntry = enom.cachedNotices[ Object.keys( enom.cachedNotices )[ enom.cachedInt ] ];
					if ( enom.checkEntry.date < enom.time - 43200000 ) { // entry >12 hours (43200000ms) old
						// console.log('enom: removed '+Object.keys(enom.cachedNotices)[enom.cachedInt]+' from locally cached notices');
						delete enom.cachedNotices[ Object.keys( enom.cachedNotices )[ enom.cachedInt ] ];
						enom.update = true;
					}
					if ( enom.update ) {
						window.localStorage.setItem( 'ENOM', JSON.stringify( enom.cachedNotices ) );
					}
				}
				if ( enom.cachedNotices[ mw.config.get( 'wgPageName' ) ] ) {
					enom.noticeText = enom.cachedNotices[ mw.config.get( 'wgPageName' ) ].text;
					// console.log('enom: found cached notice');
					enom.popupNotice( enom.noticeText, false );
					return;
				}
			}
			enom.editNoticeParams = {
				format: 'json',
				action: 'visualeditor',
				paction: 'metadata',
				page: enom.pageTitle,
				formatversion: '2'
			};
			mw.loader.using( [ 'mediawiki.api' ], function () {
				// console.log('enom: download notice');
				enom.newNotice = true;
				var api = new mw.Api();
				api.post( enom.editNoticeParams ).done( function ( data ) {
					enom.parsednotices = '<div id="EditNoticeOnMobile">';
					for ( enom.noticeint = 0; enom.noticeint < Object.keys( data.visualeditor.notices ).length; enom.noticeint++ ) {
						if ( Object.keys( data.visualeditor.notices )[ enom.noticeint ].match( /editnotice/ ) ) { // there's also semiprotectedwarning, presumably some other protection warnings. is there an overview of all possible messages?
							enom.parsednotices = enom.parsednotices + data.visualeditor.notices[ Object.keys( data.visualeditor.notices )[ enom.noticeint ] ];
						}
					}
					enom.parsednotices = enom.parsednotices + '</div>';
					enom.popupNotice( enom.parsednotices, true );
					enom.storeNotice( enom.parsednotices );
				} );
			} );
		};
		enom.showPopup = function ( noticetext ) {
			mw.loader.using( [ 'oojs-ui-core', 'oojs-ui-windows' ] ).then( function () {
				OO.ui.alert( new OO.ui.HtmlSnippet( noticetext.replace( /([" ])nomobile([" ])/, '$1$2' ) ), { size: 'larger' } );
				var DelayClassFix = setInterval( function () { // popup doesn't immediately exist..
					clearInterval( DelayClassFix );
					// eslint-disable-next-line no-jquery/no-global-selector
					$( '#EditNoticeOnMobile .mf-section-0' ).addClass( 'stopHidingMe' );
					// eslint-disable-next-line no-jquery/no-global-selector
					$( '#EditNoticeOnMobile *' ).removeClass( [ 'tmbox', 'tmbox-content' ] );
				}, 500 );
			} );
		};
		enom.popupNotice = function ( noticetext, popup ) {
			if ( noticetext.match( /<div[^>]*EditNoticeOnMobile[^>]*><\/div>/ ) ) { // empty notice, don't show anything
				// console.log('enom: notice is empty (no notice for this page)');
				return;
			}
			mw.util.addCSS( '.stopHidingMe{display:unset !important}#EditNoticeOnMobile .mbox-image{display:none}' ); // todo: test fmbox notice
			if ( popup ) { // shove popup into user's face only if freshly downloaded
				enom.showPopup( noticetext );
			}
			enom.int = 0;
			enom.waitingForVE = function ( toolbarclass, type ) {
				enom.int = enom.int + 1;
				var DelayedButton = setInterval( function () {
					clearInterval( DelayedButton );
					if ( $( toolbarclass )[ 0 ] ) {
						// eslint-disable-next-line no-jquery/no-global-selector, no-jquery/no-sizzle
						enom[ 'showNoticeButton' + type ] = $( '.overlay-header:not(.hidden) .header-action button:eq(0)' ).clone();
						enom[ 'showNoticeButton' + type ][ 0 ].classList.remove( 'mw-ui-icon-mf-next-invert', 'continue' );
						enom[ 'showNoticeButton' + type ][ 0 ].classList.add( 'mw-ui-icon-mf-alert' );
						enom[ 'showNoticeButton' + type ][ 0 ].disabled = false;
						enom[ 'showNoticeButton' + type ][ 0 ].style = '';
						enom[ 'showNoticeButton' + type ][ 0 ].title = 'Editnotice';
						enom[ 'showNoticeButton' + type ].on( 'click', function () {
							enom.showPopup( noticetext );
						} );
						// eslint-disable-next-line no-jquery/no-append-html
						$( toolbarclass ).append( enom[ 'showNoticeButton' + type ] );
					} else if ( enom.int < 300 ) { // 300*50ms = 15 seconds. VE can be very very slow sometimes. Depends on client/page complexity/etc
						// console.log('no VE yet (' + enom.int + ')');
						enom.waitingForVE( toolbarclass, type );
						return;
					}
				}, 50 );
			};
			enom.waitingForVE( '.overlay-header:not(.hidden) .header-action:eq(0)', 'source' ); // source toolbar
			enom.waitingForVE( '.overlay-header .toolbar .oo-ui-toolbar-tools', 'visual' ); // visual toolbar
		};
		// eslint-disable-next-line no-jquery/no-global-selector
		if ( $( '#ca-edit' )[ 0 ] ) {
			// eslint-disable-next-line no-jquery/no-global-selector
			$( '#ca-edit,.mw-editsection .edit-page' ).on( 'click', function ( event ) {
				enom.getNotice( event );
			} );
		}
		if ( window.location.href.match( /#\/editor\// ) ) {
			enom.getNotice();
		}
	}
}() );
