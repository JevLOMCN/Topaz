/** @typedef {Object.<string, IssueSummary[]>} IssueSummaryMap */

const PageHTMLParser = require( 'mobile.startup' ).PageHTMLParser;
const KEYWORD_ALL_SECTIONS = 'all';
const config = mw.config;
const NS_MAIN = 0;
const NS_CATEGORY = 14;
const CURRENT_NS = config.get( 'wgNamespaceNumber' );
const features = mw.config.get( 'wgMinervaFeatures', {} );
const pageIssuesParser = require( './parser.js' );
const pageIssuesOverlay = require( './overlay/pageIssuesOverlay.js' );
const pageIssueFormatter = require( './page/pageIssueFormatter.js' );
// When the query string flag is set force on new treatment.
// When wgMinervaPageIssuesNewTreatment is the default this line can be removed.
const QUERY_STRING_FLAG = mw.util.getParamValue( 'minerva-issues' );
const newTreatmentEnabled = features.pageIssues || QUERY_STRING_FLAG;

/**
 * Render a banner in a containing element.
 * if in group B, a learn more link will be append to any amboxes inside $container
 * if in group A or control, any amboxes in container will be removed and a link "page issues"
 * will be rendered above the heading.
 * This function comes with side effects. It will populate a global "allIssues" object which
 * will link section numbers to issues.
 *
 * @param {PageHTMLParser} pageHTMLParser parser to search for page issues
 * @param {string} labelText what the label of the page issues banner should say
 * @param {string} section that the banner and its issues belong to.
 *  If string KEYWORD_ALL_SECTIONS banner should apply to entire page.
 * @param {boolean} inline - if true the first ambox in the section will become the entry point
 *                           for the issues overlay
 *  and if false, a link will be rendered under the heading.
 * @param {OverlayManager} overlayManager
 * @ignore
 *
 * @return {{ambox: jQuery, issueSummaries: IssueSummary[]}}
 */
function insertBannersOrNotice( pageHTMLParser, labelText, section, inline, overlayManager ) {
	const issueUrl = section === KEYWORD_ALL_SECTIONS ? '#/issues/' + KEYWORD_ALL_SECTIONS : '#/issues/' + section;
	const selector = [ '.ambox', '.tmbox', '.cmbox', '.fmbox' ].join( ',' );
	const issueSummaries = [];

	const $metadata = section === KEYWORD_ALL_SECTIONS ?
		pageHTMLParser.$el.find( selector ) :
		// find heading associated with the section
		pageHTMLParser.findChildInSectionLead( parseInt( section, 10 ), selector );
	// clean it up a little
	$metadata.find( '.NavFrame' ).remove();
	$metadata.each( function () {
		const $this = $( this );

		if ( $this.find( selector ).length === 0 ) {
			const issueSummary = pageIssuesParser.extract( $this );
			// Some issues after "extract" has been run will have no text.
			// For example in Template:Talk header the table will be removed and no issue found.
			// These should not be rendered.
			if ( issueSummary.text ) {
				issueSummaries.push( issueSummary );
			}
		}
	} );

	if ( inline ) {
		issueSummaries.forEach( function ( issueSummary, i ) {
			const isGrouped = issueSummary.issue.grouped;
			const lastIssueIsGrouped = issueSummaries[ i - 1 ] &&
				issueSummaries[ i - 1 ].issue.grouped;
			const multiple = isGrouped && !lastIssueIsGrouped;
			// only render the first grouped issue of each group
			pageIssueFormatter.insertPageIssueBanner(
				issueSummary,
				mw.msg( 'skin-minerva-issue-learn-more' ),
				issueUrl,
				overlayManager,
				multiple
			);
		} );
	} else if ( issueSummaries.length ) {
		pageIssueFormatter.insertPageIssueNotice( labelText, section );
	}

	return {
		ambox: $metadata,
		issueSummaries: issueSummaries
	};
}

/**
 * Obtains the list of issues for the current page and provided section
 *
 * @param {IssueSummaryMap} allIssues mapping section {number} to {IssueSummary}
 * @param {number|string} section either KEYWORD_ALL_SECTIONS or a number relating to the
 *                                section the issues belong to
 * @return {jQuery[]} array of all issues.
 */
function getIssues( allIssues, section ) {
	if ( section !== KEYWORD_ALL_SECTIONS ) {
		return allIssues[ section ] || [];
	}
	// Note section.all may not exist, depending on the structure of the HTML page.
	// It will only exist when Minerva has been run in desktop mode.
	// If it's absent, we'll reduce all the other lists into one.
	return allIssues[ KEYWORD_ALL_SECTIONS ] || Object.keys( allIssues ).reduce(
		function ( all, key ) {
			return all.concat( allIssues[ key ] );
		},
		[]
	);
}

/**
 * Scan an element for any known cleanup templates and replace them with a button
 * that opens them in a mobile friendly overlay.
 *
 * @ignore
 * @param {OverlayManager} overlayManager
 * @param {PageHTMLParser} pageHTMLParser
 */
function initPageIssues( overlayManager, pageHTMLParser ) {
	let section;
	let issueSummaries = [];
	const allIssues = {};
	const $lead = pageHTMLParser.getLeadSectionElement();
	const issueOverlayShowAll = CURRENT_NS === NS_CATEGORY || !$lead;
	const inline = newTreatmentEnabled && CURRENT_NS === 0;

	// set A-B test class.
	// When wgMinervaPageIssuesNewTreatment is the default this can be removed.
	if ( newTreatmentEnabled ) {
		$( document.documentElement ).addClass( 'issues-group-B' );
	}

	if ( CURRENT_NS === NS_CATEGORY ) {
		section = KEYWORD_ALL_SECTIONS;
		// e.g. Template:English variant category; Template:WikiProject
		issueSummaries = insertBannersOrNotice( pageHTMLParser, mw.msg( 'mobile-frontend-meta-data-issues-header' ),
			section, inline, overlayManager ).issueSummaries;
		allIssues[ section ] = issueSummaries;
	} else if ( CURRENT_NS === NS_MAIN ) {
		const label = mw.msg( 'mobile-frontend-meta-data-issues-header' );
		if ( issueOverlayShowAll ) {
			section = KEYWORD_ALL_SECTIONS;
			issueSummaries = insertBannersOrNotice(
				pageHTMLParser, label, section, inline, overlayManager
			).issueSummaries;
			allIssues[ section ] = issueSummaries;
		} else {
			// parse lead
			section = '0';
			issueSummaries = insertBannersOrNotice(
				pageHTMLParser, label, section, inline, overlayManager
			).issueSummaries;
			allIssues[ section ] = issueSummaries;
			if ( newTreatmentEnabled ) {
				// parse other sections but only in group B. In treatment A no issues are shown
				// for sections.
				pageHTMLParser.$el.find( PageHTMLParser.HEADING_SELECTOR ).each(
					function ( i, headingEl ) {
						const $headingEl = $( headingEl );
						// section number is absent on protected pages, when this is the case
						// use i, otherwise icon will not show (T340910)
						const sectionNum = $headingEl.find( '.edit-page' ).data( 'section' ) || i;

						// Note certain headings matched using
						// PageHTMLParser.HEADING_SELECTOR may not be headings and will
						// not have a edit link. E.g. table of contents.
						if ( sectionNum ) {
							// Render banner for sectionNum associated with headingEl inside
							// Page
							section = sectionNum.toString();
							issueSummaries = insertBannersOrNotice(
								pageHTMLParser, label, section, inline, overlayManager
							).issueSummaries;
							allIssues[ section ] = issueSummaries;
						}
					}
				);
			}
		}
	}

	// Setup the overlay route.
	overlayManager.add( new RegExp( '^/issues/(\\d+|' + KEYWORD_ALL_SECTIONS + ')$' ), function ( s ) {
		return pageIssuesOverlay(
			getIssues( allIssues, s ), s, CURRENT_NS
		);
	} );
}

module.exports = {
	init: initPageIssues,
	test: {
		insertBannersOrNotice: insertBannersOrNotice
	}
};
