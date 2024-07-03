/**
 * Build an overflow menu button and items for display adjacent to heading and comment thread items.
 *
 * @param {jQuery} $container
 * @param {ThreadItemSet} pageThreads
 */
function init( $container, pageThreads ) {
	mw.loader.using( [ 'oojs-ui-widgets', 'oojs-ui.styles.icons-editing-core' ] ).then( function () {
		$container.find( '.ext-discussiontools-init-section-overflowMenuButton' ).each( function () {
			// Comment ellipsis
			var $threadMarker = $( this ).closest( '[data-mw-thread-id]' );
			if ( !$threadMarker.length ) {
				// Heading ellipsis
				$threadMarker = $( this ).closest( '.ext-discussiontools-init-section' ).find( '[data-mw-thread-id]' );
			}
			var threadItem = pageThreads.findCommentById( $threadMarker.data( 'mw-thread-id' ) );

			var buttonMenu = OO.ui.infuse( this, {
				$overlay: true,
				menu: {
					classes: [ 'ext-discussiontools-init-section-overflowMenu' ],
					horizontalPosition: threadItem.type === 'heading' ? 'end' : 'start'
				}
			} );

			mw.loader.using( buttonMenu.getData().resourceLoaderModules || [] ).then( function () {
				var itemConfigs = buttonMenu.getData().itemConfigs;
				if ( !itemConfigs ) {
					// We should never have missing itemConfigs, but if this happens, hide the empty menu
					buttonMenu.toggle( false );
					return;
				}
				var overflowMenuItemWidgets = itemConfigs.map( function ( itemConfig ) {
					return new OO.ui.MenuOptionWidget( itemConfig );
				} );
				buttonMenu.getMenu().addItems( overflowMenuItemWidgets );
				buttonMenu.getMenu().items.forEach( function ( menuItem ) {
					mw.hook( 'discussionToolsOverflowMenuOnAddItem' ).fire( menuItem.getData().id, menuItem, threadItem );
				} );
			} );

			buttonMenu.getMenu().on( 'choose', function ( menuItem ) {
				mw.hook( 'discussionToolsOverflowMenuOnChoose' ).fire( menuItem.getData().id, menuItem, threadItem );
			} );
		} );

		$container.find( '.ext-discussiontools-init-section-bar' ).on( 'click', function ( e ) {
			// Don't toggle section when clicking on bar
			e.stopPropagation();
		} );
	} );
}

module.exports = {
	init: init
};
