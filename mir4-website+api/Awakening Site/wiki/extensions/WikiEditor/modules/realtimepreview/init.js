mw.hook( 'wikiEditor.toolbarReady' ).add( function ( $textarea ) {

	// Guard against this module being loaded for non-wikitext pages.
	// This is already done in Hooks.php but Realtime Preview can also be loaded as a gadget so this is necessary.
	if ( mw.config.get( 'wgPageContentModel' ) !== 'wikitext' ) {
		return;
	}

	var RealtimePreview = require( './RealtimePreview.js' );
	var realtimePreview = new RealtimePreview();
	$textarea.wikiEditor( 'addToToolbar', {
		section: 'secondary',
		group: 'default',
		tools: {
			realtimepreview: {
				type: 'element',
				element: function ( context ) {
					return realtimePreview.getToolbarButton( context );
				}
			}
		}
	} );
	if ( realtimePreview.getUserPref() && realtimePreview.isScreenWideEnough() ) {
		realtimePreview.setEnabled();
		mw.hook( 'ext.WikiEditor.realtimepreview.inuse' ).fire( this );
	}
} );
