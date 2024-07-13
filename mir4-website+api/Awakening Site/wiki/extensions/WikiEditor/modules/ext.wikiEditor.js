/*
 * JavaScript for WikiEditor
 */

var editingSessionId;

// This sets $.wikiEditor and $.fn.wikiEditor
require( './jquery.wikiEditor.js' );

function logEditEvent( data ) {
	if ( mw.config.get( 'wgMFMode' ) !== null ) {
		// Visiting a ?action=edit URL can, depending on user settings, result
		// in the MobileFrontend overlay appearing on top of WikiEditor. In
		// these cases, don't log anything.
		return;
	}
	mw.track( 'editAttemptStep', $.extend( {
		// eslint-disable-next-line camelcase
		editor_interface: 'wikitext',
		platform: 'desktop', // FIXME T249944
		integration: 'page'
	}, data ) );
}

function logEditFeature( feature, action ) {
	if ( mw.config.get( 'wgMFMode' ) !== null ) {
		// Visiting a ?action=edit URL can, depending on user settings, result
		// in the MobileFrontend overlay appearing on top of WikiEditor. In
		// these cases, don't log anything.
		return;
	}
	mw.track( 'visualEditorFeatureUse', {
		feature: feature,
		action: action,
		// eslint-disable-next-line camelcase
		editor_interface: 'wikitext',
		platform: 'desktop', // FIXME T249944
		integration: 'page'
	} );
}

function logAbort( switchingToVE, unmodified ) {
	if ( switchingToVE ) {
		logEditFeature( 'editor-switch', 'visual-desktop' );
	}

	var abortType;
	if ( switchingToVE && unmodified ) {
		abortType = 'switchnochange';
	} else if ( switchingToVE ) {
		abortType = 'switchwithout';
	} else if ( unmodified ) {
		abortType = 'nochange';
	} else {
		abortType = 'abandon';
	}

	logEditEvent( {
		action: 'abort',
		type: abortType
	} );
}

$( function () {
	var $textarea = $( '#wpTextbox1' ),
		$editingSessionIdInput = $( '#editingStatsId' ),
		origText = $textarea.val();

	// T263505, T249038
	$( '#wikieditorUsed' ).val( 'yes' );

	if ( $editingSessionIdInput.length ) {
		editingSessionId = $editingSessionIdInput.val();
		if ( window.performance && window.performance.timing ) {
			// We want to track from the time the user started to try to
			// launch the editor which navigationStart approximates. All
			// of our supported browsers *should* allow this. Rather than
			// fall back to the timestamp when the page loaded for those
			// that don't, we just ignore them, so as to not skew the
			// results towards better-performance in those cases.
			var readyTime = Date.now();
			logEditEvent( {
				action: 'ready',
				timing: readyTime - window.performance.timing.navigationStart
			} );
			$textarea.on( 'wikiEditor-toolbar-doneInitialSections', function () {
				logEditEvent( {
					action: 'loaded',
					timing: Date.now() - window.performance.timing.navigationStart
				} );
			} ).one( 'input', function () {
				logEditEvent( {
					action: 'firstChange',
					timing: Date.now() - readyTime
				} );
			} );
		}
		var $form = $textarea.closest( 'form' );
		if ( mw.user.options.get( 'uselivepreview' ) ) {
			$form.find( '#wpPreview' ).on( 'click', function () {
				logEditFeature( 'preview', 'preview-live' );
			} );
		}

		var submitting;
		$form.on( 'submit', function () {
			submitting = true;
		} );
		var onUnloadFallback = window.onunload;

		window.onunload = function () {
			var unmodified = mw.config.get( 'wgAction' ) !== 'submit' && origText === $textarea.val(),
				caVeEdit = $( '#ca-ve-edit' )[ 0 ],
				switchingToVE = caVeEdit && (
					document.activeElement === caVeEdit ||
					$.contains( caVeEdit, document.activeElement )
				);

			var fallbackResult;
			if ( onUnloadFallback ) {
				fallbackResult = onUnloadFallback();
			}

			if ( !submitting ) {
				logAbort( switchingToVE, unmodified );
			}

			// If/when the user uses the back button to go back to the edit form
			// and the browser serves this from bfcache, regenerate the session ID
			// so we don't use the same ID twice. Ideally we'd do this by listening to the pageshow
			// event and checking e.originalEvent.persisted, but that doesn't work in Chrome:
			// https://code.google.com/p/chromium/issues/detail?id=344507
			// So instead we modify the DOM here, after sending the abort event.
			editingSessionId = mw.user.generateRandomSessionId();
			$editingSessionIdInput.val( editingSessionId );

			return fallbackResult;
		};
		$textarea.on( 'wikiEditor-switching-visualeditor', function () {
			var unmodified = mw.config.get( 'wgAction' ) !== 'submit' && origText === $textarea.val();
			// A non-navigation switch to VE has occurred. As such, avoid eventually
			// double-logging an abort when VE is done.
			window.onunload = onUnloadFallback;

			logAbort( true, unmodified );
		} );

		// Add logging for Realtime Preview.
		mw.hook( 'ext.WikiEditor.realtimepreview.enable' ).add( function () {
			logEditFeature( 'preview', 'preview-realtime-on' );
		} );
		mw.hook( 'ext.WikiEditor.realtimepreview.inuse' ).add( function () {
			logEditFeature( 'preview', 'preview-realtime-inuse' );
		} );
		mw.hook( 'ext.WikiEditor.realtimepreview.disable' ).add( function () {
			logEditFeature( 'preview', 'preview-realtime-off' );
		} );
		mw.hook( 'ext.WikiEditor.realtimepreview.loaded' ).add( function () {
			logEditFeature( 'preview', 'preview-realtime-loaded' );
		} );
		mw.hook( 'ext.WikiEditor.realtimepreview.stop' ).add( function () {
			logEditFeature( 'preview', 'preview-realtime-error-stopped' );
		} );
		mw.hook( 'ext.WikiEditor.realtimepreview.reloadError' ).add( function () {
			logEditFeature( 'preview', 'preview-realtime-reload-error' );
		} );
		mw.hook( 'ext.WikiEditor.realtimepreview.reloadHover' ).add( function () {
			logEditFeature( 'preview', 'preview-realtime-reload-hover' );
		} );
		mw.hook( 'ext.WikiEditor.realtimepreview.reloadManual' ).add( function () {
			logEditFeature( 'preview', 'preview-realtime-reload-manual' );
		} );
	}

	// The old toolbar is still in place and needs to be removed so there aren't two toolbars
	$( '#toolbar' ).remove();
	// Add toolbar module
	// TODO: Implement .wikiEditor( 'remove' )
	mw.addWikiEditor( $textarea );
} );

mw.addWikiEditor = function ( $textarea ) {
	if ( $textarea.css( 'display' ) === 'none' ) {
		return;
	}

	$textarea.wikiEditor(
		'addModule', require( './jquery.wikiEditor.toolbar.config.js' )
	);

	var dialogsConfig = require( './jquery.wikiEditor.dialogs.config.js' );
	// Replace icons
	dialogsConfig.replaceIcons( $textarea );
	// Add dialogs module
	$textarea.wikiEditor( 'addModule', dialogsConfig.getDefaultConfig() );

};
