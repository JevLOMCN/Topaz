/*
 * This file is part of the MediaWiki extension MultimediaViewer.
 *
 * MultimediaViewer is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 2 of the License, or
 * (at your option) any later version.
 *
 * MultimediaViewer is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with MultimediaViewer.  If not, see <http://www.gnu.org/licenses/>.
 */

const base = require( './base.js' );
mw.mmv = base;
module.exports = base;

( function () {
	const $document = $( document );

	// If MediaViewer is disabled by the user, do not set up click handling.
	// This is loaded before user JS so we cannot check wgMediaViewer.
	if ( !base.isMediaViewerEnabledOnClick() ) {
		return;
	}

	$document.on( 'click.mmv-head', 'a.image, a.mw-file-description', ( e ) => {
		// Do not interfere with non-left clicks or if modifier keys are pressed.
		// Also, make sure we do not get in a loop.
		if ( ( e.button !== 0 && e.which !== 1 ) || e.altKey || e.ctrlKey || e.shiftKey || e.metaKey || e.replayed ) {
			return;
		}

		// We wait for document readiness because mw.loader.using writes to the DOM
		// which can cause a blank page if it happens before DOM readiness
		$( () => {
			mw.loader.using( [ 'mmv.bootstrap.autostart' ], ( req ) => {
				const bootstrap = req( 'mmv.bootstrap.autostart' );
				bootstrap.whenThumbsReady().then( () => {
					// We have to copy the properties, passing e doesn't work. Probably because of preventDefault()
					$( e.target ).trigger( { type: 'click', which: 1, replayed: true } );
				} );
			} );
		} );

		e.preventDefault();
	} );
}() );
