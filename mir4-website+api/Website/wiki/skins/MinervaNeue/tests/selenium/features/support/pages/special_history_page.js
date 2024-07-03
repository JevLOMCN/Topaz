'use strict';

const { Page } = require( './mw_core_pages' );
/**
 * Represents the mobile-first Special:History page
 *
 * @extends Page
 * @example
 * https://en.m.wikipedia.org/wiki/Special:History/Barack_Obama
 */
class SpecialHistoryPage extends Page {

	get content_header_bar_element() {
		return $( '.content-header' );
	}

	get content_header_bar_link_element() {
		return $( '.content-header a' );
	}

	get side_list_element() {
		return $( '.side-list' );
	}

	get last_contribution_element() {
		return $( '.side-list li' );
	}

	get last_contribution_link_element() {
		return $( '.side-list li a' );
	}

	get last_contribution_title_element() {
		return $( '.side-list li h3' );
	}

	get last_contribution_timestamp_element() {
		return $( '.side-list li p.timestamp' );
	}

	get last_contribution_edit_summary_element() {
		return $( '.side-list li p.edit-summary' );
	}

	get last_contribution_username_element() {
		return $( '.side-list li p.mw-mf-user' );
	}

	get more_link_element() {
		return $( '.more' );
	}

	open() {
		super.open( 'Special:History' );
	}
}

module.exports = new SpecialHistoryPage();
