/**
 * @module referencePreview
 */
const { isTrackingEnabled, LOGGING_SCHEMA } = require( './referencePreviewsInstrumentation.js' );

const TEMPLATE = document.createElement( 'template' );
TEMPLATE.innerHTML = `
<div class="mwe-popups mwe-popups mwe-popups-type-reference" aria-hidden>
	<div class="mwe-popups-container">
		<div class="mwe-popups-extract">
			<div class="mwe-popups-scroll">
				<strong class="mwe-popups-title">
					<span class="popups-icon"></span>
					<span class="mwe-popups-title-placeholder"></span>
				</strong>
				<bdi><div class="mw-parser-output"></div></bdi>
			</div>
			<div class="mwe-popups-fade"></div>
		</div>
		<footer>
			<div class="mwe-popups-settings"></div>
		</footer>
	</div>
</div>`;

/**
 * @param {HTMLElement} node
 * @param {HTMLElement|string} htmlOrOtherNode
 */
const replaceWith = ( node, htmlOrOtherNode ) => {
	if ( typeof htmlOrOtherNode === 'string' ) {
		node.insertAdjacentHTML( 'afterend', htmlOrOtherNode );
	} else {
		node.parentNode.appendChild( htmlOrOtherNode );
	}
	node.remove();
};

/**
 * @param {ext.popups.PreviewModel} model
 * @return {jQuery}
 */
function renderReferencePreview(
	model
) {
	const type = model.referenceType || 'generic';
	// The following messages are used here:
	// * popups-refpreview-book
	// * popups-refpreview-journal
	// * popups-refpreview-news
	// * popups-refpreview-note
	// * popups-refpreview-web
	let titleMsg = mw.message( `popups-refpreview-${ type }` );
	if ( !titleMsg.exists() ) {
		titleMsg = mw.message( 'popups-refpreview-reference' );
	}

	const el = TEMPLATE.content.cloneNode( true ).children[ 0 ];

	replaceWith(
		el.querySelector( '.mwe-popups-title-placeholder' ),
		mw.html.escape( titleMsg.text() )
	);
	// The following classes are used here:
	// * popups-icon--reference-generic
	// * popups-icon--reference-book
	// * popups-icon--reference-journal
	// * popups-icon--reference-news
	// * popups-icon--reference-note
	// * popups-icon--reference-web
	el.querySelector( '.mwe-popups-title .popups-icon' )
		.classList.add( `popups-icon--reference-${ type }` );
	el.querySelector( '.mw-parser-output' )
		.innerHTML = model.extract;

	// Make sure to not destroy existing targets, if any
	Array.prototype.forEach.call(
		el.querySelectorAll( '.mwe-popups-extract a[href][class~="external"]:not([target])' ),
		( a ) => {
			a.target = '_blank';
			// Don't let the external site access and possibly manipulate window.opener.location
			a.rel = `${ a.rel ? `${ a.rel } ` : '' }noopener`;
		}
	);

	// We assume elements that benefit from being collapsible are to large for the popup
	Array.prototype.forEach.call( el.querySelectorAll( '.mw-collapsible' ), ( node ) => {
		const otherNode = document.createElement( 'div' );
		otherNode.classList.add( 'mwe-collapsible-placeholder' );
		const icon = document.createElement( 'span' );
		icon.classList.add( 'popups-icon', 'popups-icon--infoFilled' );
		const label = document.createElement( 'span' );
		label.classList.add( 'mwe-collapsible-placeholder-label' );
		label.textContent = mw.msg( 'popups-refpreview-collapsible-placeholder' );
		otherNode.appendChild( icon );
		otherNode.appendChild( label );
		replaceWith( node, otherNode );
	} );

	// Undo remaining effects from the jquery.tablesorter.js plugin
	const undoHeaderSort = ( headerSort ) => {
		headerSort.classList.remove( 'headerSort' );
		headerSort.removeAttribute( 'tabindex' );
		headerSort.removeAttribute( 'title' );
	};
	Array.prototype.forEach.call( el.querySelectorAll( 'table.sortable' ), ( node ) => {
		node.classList.remove( 'sortable', 'jquery-tablesorter' );
		Array.prototype.forEach.call( node.querySelectorAll( '.headerSort' ), undoHeaderSort );
	} );

	// TODO: Do not remove this but move it up into the templateHTML constant!
	const settingsButton = document.createElement( 'a' );
	settingsButton.classList.add( 'cdx-button', 'cdx-button--fake-button', 'cdx-button--fake-button--enabled', 'cdx-button--weight-quiet', 'cdx-button--icon-only', 'mwe-popups-settings-button' );
	const settingsIcon = document.createElement( 'span' );
	settingsIcon.classList.add( 'popups-icon', 'popups-icon--size-small', 'popups-icon--settings' );
	const settingsButtonLabel = document.createElement( 'span' );
	settingsButtonLabel.textContent = mw.msg( 'popups-settings-icon-gear-title' );
	settingsButton.append( settingsIcon );
	settingsButton.append( settingsButtonLabel );
	el.querySelector( '.mwe-popups-settings' ).appendChild( settingsButton );

	if ( isTrackingEnabled() ) {
		el.querySelector( '.mw-parser-output' ).addEventListener( 'click', ( ev ) => {
			if ( !ev.target.matches( 'a' ) ) {
				return;
			}
			mw.track( LOGGING_SCHEMA, {
				action: 'clickedReferencePreviewsContentLink'
			} );
		} );
	}

	el.querySelector( '.mwe-popups-scroll' ).addEventListener( 'scroll', function ( e ) {
		const element = e.target,
			// We are dealing with floating point numbers here when the page is zoomed!
			scrolledToBottom = element.scrollTop >= element.scrollHeight - element.clientHeight - 1;

		if ( isTrackingEnabled() ) {
			if ( !element.isOpenRecorded ) {
				mw.track( LOGGING_SCHEMA, {
					action: 'poppedOpen',
					scrollbarsPresent: element.scrollHeight > element.clientHeight
				} );
				element.isOpenRecorded = true;
			}

			if (
				element.scrollTop > 0 &&
				!element.isScrollRecorded
			) {
				mw.track( LOGGING_SCHEMA, {
					action: 'scrolled'
				} );
				element.isScrollRecorded = true;
			}
		}

		if ( !scrolledToBottom && element.isScrolling ) {
			return;
		}

		const extract = element.parentNode,
			hasHorizontalScroll = element.scrollWidth > element.clientWidth,
			scrollbarHeight = element.offsetHeight - element.clientHeight,
			hasVerticalScroll = element.scrollHeight > element.clientHeight,
			scrollbarWidth = element.offsetWidth - element.clientWidth;
		const fade = extract.querySelector( '.mwe-popups-fade' );
		fade.style.bottom = hasHorizontalScroll ? `${ scrollbarHeight }px` : 0;
		fade.style.right = hasVerticalScroll ? `${ scrollbarWidth }px` : 0;

		element.isScrolling = !scrolledToBottom;
		extract.classList.toggle( 'mwe-popups-fade-out', element.isScrolling );
		extract.setAttribute( 'lang', mw.config.get( 'wgPageContentLanguage' ) );
	} );

	return el;
}

/**
 * @param {ext.popups.PreviewModel} model
 * @return {ext.popups.Preview}
 */
function createReferencePreview( model ) {
	return {
		el: renderReferencePreview( model ),
		hasThumbnail: false,
		isTall: false
	};
}

module.exports = createReferencePreview;
