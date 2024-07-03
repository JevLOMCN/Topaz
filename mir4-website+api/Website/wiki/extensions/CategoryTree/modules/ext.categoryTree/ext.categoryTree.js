/**
 * JavaScript for the CategoryTree extension.
 *
 * © 2006 Daniel Kinzler
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 * http://www.gnu.org/copyleft/gpl.html
 *
 * @file
 * @author Daniel Kinzler, brightbyte.de
 */

( function () {
	var loadChildren,
		config = require( './data.json' );

	/**
	 * Expands a given node (loading it's children if not loaded)
	 *
	 * @param {jQuery} $link
	 */
	function expandNode( $link ) {
		// Show the children node
		var $children = $link.parents( '.CategoryTreeItem' )
			.siblings( '.CategoryTreeChildren' )
			.css( 'display', '' );

		$link.attr( {
			title: mw.msg( 'categorytree-collapse' ),
			'aria-expanded': 'true'
		} );

		if ( !$link.data( 'ct-loaded' ) ) {
			loadChildren( $link, $children );
		}
	}

	/**
	 * Collapses a node
	 *
	 * @param {jQuery} $link
	 */
	function collapseNode( $link ) {
		// Hide the children node
		$link.parents( '.CategoryTreeItem' )
			.siblings( '.CategoryTreeChildren' )
			.css( 'display', 'none' );

		$link.attr( {
			title: mw.msg( 'categorytree-expand' ),
			'aria-expanded': 'false'
		} );
	}

	/**
	 * Handles clicks on the expand buttons, and calls the appropriate function
	 *
	 * @this {Element} CategoryTreeToggle
	 * @param {jQuery.Event} e
	 */
	function handleNode( e ) {
		e.preventDefault();

		var $link = $( this );
		if ( $link.attr( 'aria-expanded' ) === 'false' ) {
			expandNode( $link );
		} else {
			collapseNode( $link );
		}
	}

	/**
	 * Attach click handler to buttons
	 *
	 * @param {jQuery} $content
	 */
	function attachHandler( $content ) {
		$content.find( '.CategoryTreeToggle' )
			.on( 'click', handleNode )
			.attr( {
				href: '#',
				role: 'button',
				title: function () {
					return mw.msg(
						$( this ).attr( 'aria-expanded' ) === 'false' ?
							'categorytree-expand' :
							'categorytree-collapse'
					);
				}
			} )
			.addClass( 'CategoryTreeToggleHandlerAttached' );
	}

	/**
	 * Loads children for a node via an HTTP call
	 *
	 * @param {jQuery} $link
	 * @param {jQuery} $children
	 */
	loadChildren = function ( $link, $children ) {
		var $linkParentCTTag, ctTitle, ctMode, ctOptions;

		/**
		 * Error callback
		 */
		function error() {
			var $retryLink;

			$retryLink = $( '<a>' )
				.text( mw.msg( 'categorytree-retry' ) )
				.attr( {
					role: 'button',
					tabindex: 0
				} )
				.on( 'click keypress', function ( e ) {
					if (
						e.type === 'click' ||
						e.type === 'keypress' && e.which === 13
					) {
						loadChildren( $link, $children );
					}
				} );

			$children
				.text( mw.msg( 'categorytree-error' ) + ' ' )
				.append( $retryLink );
		}

		$link.data( 'ct-loaded', true );

		$children.empty().append(
			$( '<i>' )
				.addClass( 'CategoryTreeNotice' )
				.text( mw.msg( 'categorytree-loading' ) )
		);

		$linkParentCTTag = $link.parents( '.CategoryTreeTag' );

		// Element may not have a .CategoryTreeTag parent, fallback to defauls
		// Probably a CategoryPage (@todo: based on what?)
		ctTitle = $link.attr( 'data-ct-title' );
		ctMode = $linkParentCTTag.data( 'ct-mode' );
		ctMode = typeof ctMode === 'number' ? ctMode : undefined;
		ctOptions = $linkParentCTTag.attr( 'data-ct-options' ) || config.defaultCtOptions;

		// Mode and options have defaults or fallbacks, title does not.
		// Don't make a request if there is no title.
		if ( !ctTitle ) {
			error();
			return;
		}

		new mw.Api().get( {
			action: 'categorytree',
			category: ctTitle,
			options: ctOptions,
			uselang: mw.config.get( 'wgUserLanguage' ),
			formatversion: 2
		} ).done( function ( data ) {
			var $data;

			data = data.categorytree.html;

			if ( data === '' ) {
				$data = $( '<i>' ).addClass( 'CategoryTreeNotice' )
					// eslint-disable-next-line mediawiki/msg-doc
					.text( mw.msg( {
						0: 'categorytree-no-subcategories',
						10: 'categorytree-no-pages',
						100: 'categorytree-no-parent-categories'
					}[ ctMode ] || 'categorytree-nothing-found' ) );
			} else {
				$data = $( $.parseHTML( data ) );
				attachHandler( $data );
			}

			$children.empty().append( $data );
		} ).fail( error );
	};

	// Register click events
	mw.hook( 'wikipage.content' ).add( attachHandler );

	// Attach click handler for categories.
	// This is needed when wgCategoryTreeHijackPageCategories is enabled.
	mw.hook( 'wikipage.categories' ).add( attachHandler );

	$( function () {
		// Attach click handler for sidebar
		// eslint-disable-next-line no-jquery/no-global-selector
		attachHandler( $( '#p-categorytree-portlet' ) );
	} );

}() );
