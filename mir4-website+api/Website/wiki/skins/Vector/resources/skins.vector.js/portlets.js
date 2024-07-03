const dropdownMenus = require( './dropdownMenus.js' );

/**
 * An object containing the data to help create a portlet.
 *
 * @typedef {Object} Hint
 * @property {string} type
 */

/**
 * Creates default portlet.
 *
 * @param {Element} portlet
 * @param {boolean} isDropdown
 * @return {Element}
 */
function addDefaultPortlet( portlet, isDropdown ) {
	const ul = portlet.querySelector( 'ul' );
	if ( !ul ) {
		return portlet;
	}
	ul.classList.add( 'vector-menu-content-list' );
	const label = portlet.querySelector( 'label' );
	if ( label ) {
		const labelDiv = document.createElement( 'div' );
		labelDiv.classList.add( 'vector-menu-heading' );
		if ( !isDropdown ) {
			labelDiv.innerHTML = label.textContent || '';
			portlet.insertBefore( labelDiv, label );
			label.remove();
		}
	}
	let wrapper = portlet.querySelector( 'div:last-child' );
	if ( wrapper ) {
		ul.remove();
		wrapper.appendChild( ul );
		wrapper.classList.add( 'vector-menu-content' );
	} else {
		wrapper = document.createElement( 'div' );
		wrapper.classList.add( 'vector-menu-content' );
		ul.remove();
		wrapper.appendChild( ul );
		portlet.appendChild( wrapper );
	}
	portlet.classList.add( 'vector-menu' );
	return portlet;
}

/**
 * A hook handler for util.addPortlet hook.
 * It creates a portlet based on the hint, and adabt it to vector skin.
 *
 * @param {Element} content
 * @return {Element}
 */
function makeDropdown( content ) {
	const id = content.id;
	const label = content.querySelector( 'label' );
	if ( !content.parentNode || !label ) {
		return content;
	}
	label.id = `${ id }-dropdown-label`;
	label.setAttribute( 'for', `${ id }-dropdown-checkbox` );
	label.classList.add( 'vector-dropdown-label' );
	label.setAttribute( 'aria-hidden', 'true' );
	const labelSpan = document.createElement( 'span' );
	labelSpan.textContent = label.textContent;
	label.textContent = '';
	labelSpan.classList.add( 'vector-dropdown-label-text' );
	label.appendChild( labelSpan );
	const dropdown = document.createElement( 'div' );
	const checkbox = document.createElement( 'input' );
	const dropdownContent = document.createElement( 'div' );
	dropdownContent.classList.add( 'vector-dropdown-content' );
	checkbox.type = 'checkbox';
	checkbox.id = `${ id }-dropdown-checkbox`;
	checkbox.setAttribute( 'role', 'button' );
	checkbox.setAttribute( 'aria-haspopup', 'true' );
	checkbox.setAttribute( 'data-event-name', `ui.dropdown-${ id }-dropdown` );
	checkbox.classList.add( 'vector-dropdown-checkbox' );
	checkbox.setAttribute( 'aria-label', label.textContent || '' );
	dropdown.id = `${ id }-dropdown`;
	dropdown.classList.add( 'vector-dropdown', `${ id }-dropdown` );
	dropdown.appendChild( checkbox );
	dropdown.appendChild( label );
	dropdown.appendChild( dropdownContent );
	content.parentNode.insertBefore( dropdown, content );
	dropdownContent.appendChild( content );
	dropdownMenus.dropdownMenus( [ dropdown ] );
	return dropdown;
}

/**
 * A hook handler for util.addPortlet hook.
 * It creates a portlet based on the hint, and adapt it to vector skin.
 * If #p-cactions is used, the new portlet will be converted into a dropdown.
 *
 * @param {Element} portlet
 * @param {string|null} before
 * @return {Element}
 */
function addPortletHandler( portlet, before ) {

	const isDropdown = !!( before && before === '#p-cactions' );
	portlet.classList.remove( 'mw-portlet-js' );

	const transformedPortlet = addDefaultPortlet( portlet, isDropdown );
	if ( isDropdown ) {
		const pageToolsDropdown = document.querySelector( '#vector-page-tools-dropdown' );
		const pageToolsMarker = pageToolsDropdown ? pageToolsDropdown.parentNode : null;
		// Guard against unexpected changes to HTML.
		if ( pageToolsMarker === null || !pageToolsMarker.parentNode ) {
			throw new Error( 'Vector 2022 addPortletLink: No #vector-page-tools-dropdown element in the DOM.' );
		}
		const dropdown = makeDropdown( transformedPortlet );
		pageToolsMarker.parentNode.insertBefore( dropdown, pageToolsMarker );
		return transformedPortlet;
	}
	return transformedPortlet;
}

/**
 * @return {{addPortletHandler: (function(Element, string): Element)}}
 */
function main() {
	mw.hook( 'util.addPortlet' ).add( addPortletHandler );
	// Update any portlets that were created prior to the hook being registered.
	document.querySelectorAll( '.mw-portlet-js' ).forEach( ( node ) => {
		const nextID = node && node.nextElementSibling && node.nextElementSibling.id;
		addPortletHandler( node, nextID ? `#${ nextID }` : null );
	} );
	return {
		addPortletHandler
	};
}

module.exports = {
	main, addPortletHandler
};
