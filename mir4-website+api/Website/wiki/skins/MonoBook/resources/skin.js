/* eslint-disable no-jquery/no-global-selector */
$( function () {
	var mobileMediaQuery = window.matchMedia( 'screen and (max-width: 550px)' ),
		isResponsive = document.body.classList.contains( 'skin--responsive' ),
		echo = require( './mobile-echo.js' ),
		uls = require( './mobile-uls.js' ),
		// Track if DOM has been set up for mobile fanciness yet
		monobookMobileElements = false,
		// Toggles and targets for popouts
		toggles = {
			'#sidebar-toggle': '#sidebar-mobilejs',
			'#p-personal-toggle': '#p-personal',
			'#ca-more a': '#p-cactions',
			'#ca-languages a': '#p-lang',
			'#ca-tools a': '#p-tb'
		};

	// Close menus
	function closeMenus() {
		$( '.mobile-menu-active' ).removeClass( 'mobile-menu-active' );
		$( '.menus-cover' ).removeClass( 'visible' );
	}

	// Set up DOM for mobile fanciness
	// We don't automatically do this because MonoBook; most users will be on desktop
	function setupMonoBookMobile() {
		if ( !monobookMobileElements && mobileMediaQuery.matches ) {
			// Duplicate nav
			$( '#column-one' ).append(
				$( '#sidebar' ).clone().find( '*' ).addBack().each( function () {
					if ( this.id ) {
						this.id = this.id + '-mobilejs';
					}
				} ).end().end()
			);
			// Thing to fade out the content while menus are active
			$( '#column-one' ).append( $( '<div>' ).attr( 'id', 'menus-cover-background' ).addClass( 'menus-cover' ) );
			$( '#column-one' ).append( $( '<div>' ).attr( 'id', 'menus-cover' ).addClass( 'menus-cover' ) );

			// Add extra cactions tabs - edit, editsource, contributions
			// Wrap in function to keep jenkins from whining
			$( function () {
				var newTabs = [
					'ca-edit',
					// 'ca-ve-edit', // TODO when VE is more usable to begin with here
					// 'ca-watch', 'ca-unwatch', // Maybe?
					't-contributions'
				];
				newTabs.forEach( function ( item ) {
					var $a = $( '#' + item + ' a' );
					// TODO check if we're on the page and add class=selected

					if ( $a.length ) {
						mw.util.addPortletLink(
							'p-cactions-mobile',
							$a.attr( 'href' ),
							$a.text(),
							$a.parent().attr( 'id' ) + '-mobile',
							$a.attr( 'tooltip' ),
							$a.attr( 'accesskey' ),
							'#ca-more'
						);
					}
				} );
			} );

			// eslint-disable-next-line no-jquery/no-each-util
			$.each( toggles, function ( toggle, target ) {
				// Add close buttons
				$( target ).append( $( '<div>' ).addClass( 'mobile-close-button' ) );

				// Open menus
				$( toggle ).on( 'click', function () {
					if ( mobileMediaQuery.matches ) {
						$( target ).addClass( 'mobile-menu-active' );
						$( '.menus-cover' ).addClass( 'visible' );
					}
					// Don't still link to # targets
					return false;
				} );
			} );

			$( '.mobile-close-button, .menus-cover' ).on( 'click', closeMenus );
			// TODO: tap events on same (if not already included in 'click') - also close
			// TODO: appropriate swipe event(s) - also close

			monobookMobileElements = true;
		}
	}

	if ( isResponsive ) {
		$( window ).on( 'resize', setupMonoBookMobile );
		setupMonoBookMobile();
		if ( mw.loader.getState( 'ext.uls.interface' ) !== null ) {
			uls();
		}
		if ( mw.loader.getState( 'ext.echo.init' ) !== null && !mw.user.isAnon() ) {
			echo();
		}
	}
} );
