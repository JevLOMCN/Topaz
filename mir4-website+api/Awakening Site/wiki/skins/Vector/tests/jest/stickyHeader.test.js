const mustache = require( 'mustache' );
const fs = require( 'fs' );
const stickyHeaderTemplate = fs.readFileSync( 'includes/templates/StickyHeader.mustache', 'utf8' );
const buttonTemplate = fs.readFileSync( 'includes/templates/Button.mustache', 'utf8' );
const iconTemplate = fs.readFileSync( 'includes/templates/Icon.mustache', 'utf8' );
const sticky = require( '../../resources/skins.vector.js/stickyHeader.js' );
const { userLinksHTML, dropdownPartials } = require( './userLinksData.js' );

const defaultButtonsTemplateData = [ {
	href: '#',
	id: 'ca-talk-sticky-header',
	icon: 'speechBubbles',
	class: 'cdx-button cdx-button--weight-quiet cdx-button--icon-only sticky-header-icon',
	'array-attributes': [ {
		key: 'data-event-name',
		value: 'talk-sticky-header'
	}, {
		key: 'tabindex',
		value: '-1'
	} ]
}, {
	href: '#',
	id: 'ca-history-sticky-header',
	icon: 'history',
	class: 'cdx-button cdx-button--weight-quiet cdx-button--icon-only sticky-header-icon',
	'array-attributes': [ {
		key: 'data-event-name',
		value: 'history-sticky-header'
	}, {
		key: 'tabindex',
		value: '-1'
	} ]
}, {
	href: '#',
	id: 'ca-watchstar-sticky-header',
	icon: 'star',
	class: 'cdx-button cdx-button--weight-quiet cdx-button--icon-only sticky-header-icon mw-watchlink',
	'array-attributes': [ {
		key: 'data-event-name',
		value: 'watch-sticky-header'
	}, {
		key: 'tabindex',
		value: '-1'
	} ]
} ];

const editButtonsTemplateData = [ {
	href: '#',
	id: 'ca-ve-edit-sticky-header',
	icon: 'edit',
	class: 'cdx-button cdx-button--weight-quiet cdx-button--icon-only sticky-header-icon',
	'array-attributes': [ {
		key: 'data-event-name',
		value: 've-edit-sticky-header'
	}, {
		key: 'tabindex',
		value: '-1'
	} ]
}, {
	href: '#',
	id: 'ca-edit-sticky-header',
	icon: 'wikiText',
	class: 'cdx-button cdx-button--weight-quiet cdx-button--icon-only sticky-header-icon',
	'array-attributes': [ {
		key: 'data-event-name',
		value: 'wikitext-edit-sticky-header'
	}, {
		key: 'tabindex',
		value: '-1'
	} ]
}, {
	href: '#',
	id: 'ca-viewsource-sticky-header',
	icon: 'star',
	class: 'cdx-button cdx-button--weight-quiet cdx-button--icon-only sticky-header-icon',
	'array-attributes': [ {
		key: 'data-event-name',
		value: 'editLock'
	}, {
		key: 'tabindex',
		value: '-1'
	} ]
} ];

const templateData = {
	'data-toc': {
		'array-sections': []
	},
	'data-sticky-header-toc-dropdown': {
		id: 'vector-sticky-header-toc',
		class: 'mw-portlet mw-portlet-sticky-header-toc vector-sticky-header-toc',
		'html-items': '',
		'html-vector-menu-checkbox-attributes': 'tabindex="-1"',
		'html-vector-menu-label-attributes': 'tabindex="-1"',
		'label-class': 'cdx-button cdx-button--weight-quiet cdx-button--icon-only'
	},
	'array-buttons': [ {
		label: '0 languages',
		id: 'p-lang-btn-sticky-header',
		class: 'cdx-button cdx-button--weight-quiet mw-interlanguage-selector',
		'array-attributes': [ {
			key: 'data-event-name',
			value: 'ui.dropdown-p-lang-btn-sticky-header'
		}, {
			key: 'tabindex',
			value: '-1'
		} ]
	} ],
	'data-button-start': {
		label: 'search',
		icon: 'search',
		class: 'cdx-button cdx-button--weight-quiet cdx-button--icon-only vector-sticky-header-search-toggle',
		'array-attributes': [ {
			key: 'data-event-name',
			value: 'ui.vector-sticky-search-form.icon'
		}, {
			key: 'tabindex',
			value: '-1'
		} ]
	},
	'data-search': {},
	'array-icon-buttons': defaultButtonsTemplateData.concat( editButtonsTemplateData )
};

const renderedHTML = mustache.render(
	stickyHeaderTemplate, templateData, Object.assign( {}, dropdownPartials, {
		Button: buttonTemplate,
		Icon: iconTemplate,
		SearchBox: '<div> </div>' // ignore SearchBox for this test
	} ) );

beforeEach( () => {
	document.body.innerHTML = renderedHTML;
} );

test( 'Sticky header renders', () => {
	expect( document.body.innerHTML ).toMatchSnapshot();
} );

describe( 'sticky header', () => {
	test( 'prepareUserLinksDropdown removes gadgets from dropdown', async () => {
		const menu = document.createElement( 'div' );
		menu.innerHTML = userLinksHTML;
		const userLinksDropdown = /** @type {Element} */ ( menu.querySelector( '#' + sticky.USER_LINKS_DROPDOWN_ID ) );
		const newMenu = sticky.prepareUserLinksDropdown( userLinksDropdown );
		// check classes have been updated and removed.
		expect( userLinksDropdown.querySelectorAll( '.user-links-collapsible-item' ).length > 0 ).toBeTruthy();
		expect( userLinksDropdown.querySelectorAll( '.mw-list-item-js' ).length > 0 ).toBeTruthy();
		expect( newMenu.querySelectorAll( '.user-links-collapsible-item' ).length ).toBe( 0 );
		expect( newMenu.querySelectorAll( '.mw-list-item-js' ).length ).toBe( 0 );
	} );
} );
