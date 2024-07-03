/*!
 * VisualEditor trigger demo
 *
 * @copyright See AUTHORS.txt
 */

( function () {
	/* eslint-disable no-jquery/no-global-selector */
	var $primary = $( '#primary' ),
		$modifiers = $( '#modifiers' ),
		$aliases = $( '#aliases' ),
		$trigger = $( '#trigger' ),
		/* eslint-enable no-jquery/no-global-selector */
		primaryKeys = ve.ui.Trigger.static.primaryKeys,
		modifierKeys = ve.ui.Trigger.static.modifierKeys,
		keyAliases = ve.ui.Trigger.static.keyAliases;

	function setTrigger( trigger ) {
		var parts;
		trigger = trigger.toString();
		parts = trigger.split( '+' );
		$trigger.text( trigger );
		parts.forEach( function ( part ) {
			var key = part.replace( '\\', '\\\\' ).replace( '"', '\\"' );
			$( '.key[rel~="' + key + '"]' ).addClass( 'active' );
		} );
	}

	// Initialization

	modifierKeys.forEach( function ( modifierKey ) {
		$modifiers.append(
			$( '<li>' ).append(
				$( '<span>' )
					.addClass( 'key' )
					.attr( 'rel', modifierKey )
					.text( modifierKey )
			)
		);
	} );
	primaryKeys.forEach( function ( primaryKey ) {
		$primary.append(
			$( '<li>' ).append(
				$( '<span>' )
					.addClass( 'key' )
					.attr( 'rel', primaryKey )
					.text( primaryKey )
			)
		);
	} );
	Object.keys( keyAliases ).forEach( function ( key ) {
		$aliases.append(
			$( '<li>' )
				.append( $( '<span>' ).addClass( 'key alias' ).text( key ) )
				.append( '⇢' )
				.append( $( '<span>' ).addClass( 'key' ).text( keyAliases[ key ] ) )
		);
	} );

	// Events
	$( document.body ).on( {
		keydown: function ( e ) {
			// eslint-disable-next-line no-jquery/no-global-selector
			$( '.active' ).removeClass( 'active' );
			setTrigger( new ve.ui.Trigger( e ) );
			e.preventDefault();
		}
	} );
	// eslint-disable-next-line no-jquery/no-global-selector
	$( '#primary .key, #modifiers .key' ).on( {
		mousedown: function ( e ) {
			var $target = $( e.target );
			if ( e.which === OO.ui.MouseButtons.LEFT ) {
				if ( $target.closest( '#primary' ).length ) {
					$primary.find( '.active' ).removeClass( 'active' );
				}
				// eslint-disable-next-line no-jquery/no-class-state
				if ( !$target.hasClass( 'active' ) ) {
					$target.addClass( 'active activating' );
				}
			}
		},
		mouseup: function ( e ) {
			var parts = [],
				$target = $( e.target );
			if ( e.which === OO.ui.MouseButtons.LEFT ) {
				// eslint-disable-next-line no-jquery/no-class-state
				if ( $target.hasClass( 'active' ) && !$target.hasClass( 'activating' ) ) {
					$target.removeClass( 'active' );
				}
				$target.removeClass( 'activating' );
				// eslint-disable-next-line no-jquery/no-global-selector
				$( '.active' ).each( function () {
					parts.push( $( this ).attr( 'rel' ) );
				} );
				setTrigger( new ve.ui.Trigger( parts.join( '+' ) ) );
			}
		}
	} );
}() );
