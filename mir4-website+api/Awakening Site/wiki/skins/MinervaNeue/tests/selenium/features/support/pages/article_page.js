/**
 * Represents a generic article page
 *
 * @extends MinervaPage
 * @example
 * https://en.m.wikipedia.org/wiki/Barack_Obama
 */

'use strict';

const MinervaPage = require( './minerva_page' );

class ArticlePage extends MinervaPage {
	get contributions_link_element() {
		return $( '.page-actions-menu .menu__item--page-actions-contributions' );
	}

	get watch_element() {
		return $( '#ca-watch' );
	}

	get reference_element() {
		return $( 'sup a' );
	}

	get drawer_reference_element() {
		return $( '.drawer sup a' );
	}

	get talk_tab_element() {
		return $( '.minerva__tab-container a[rel="discussion"]' );
	}

	get mask_element() {
		return $( '.mw-mf-page-center__mask' );
	}

	get drawer_mask_element() {
		return $( '.drawer-container__mask--visible' );
	}

	get watched_element() {
		return $( '.minerva-icon--unStar-progressive' );
	}

	get menu_button_element() {
		return $( '#mw-mf-main-menu-button' );
	}

	get search_icon_element() {
		return $( '#searchIcon' );
	}

	get menu_element() {
		return $( 'nav' );
	}

	get user_links_element() {
		return $( '.user-links' );
	}

	get notifications_button_element() {
		return $( '#pt-notifications-alert' );
	}

	get drawer_element() {
		return $( '.drawer' );
	}

	get edit_link_element() {
		return $( '#ca-editsource, #ca-createsource, #ca-edit, #ca-create' );
	}

	get first_heading_element() {
		return $( 'h1.mw-first-heading' );
	}

	get notification_element() {
		return $( '.mw-notification-area .mw-notification' );
	}

	get overlay_heading_element() {
		return $( '.overlay-title h2' );
	}

	get overlay_category_topic_item_element() {
		return $( '.topic-title-list li' );
	}

	get red_link_element() {
		return $( '#mw-content-text a.new' );
	}

	get is_authenticated_element() {
		return $( 'body.is-authenticated' );
	}

	get last_modified_bar_history_link_element() {
		return $( 'a.last-modified-bar__text[href*=\'Special:History\']' );
	}
}

module.exports = new ArticlePage();
