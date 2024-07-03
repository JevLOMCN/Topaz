'use strict';

/**
 * @file Add Cite-specific functionality to the WikiEditor toolbar.
 * Adds a button to insert <ref> tags, and adds a help section
 * about how to use references to WikiEditor's help panel.
 *
 * @author Jon Harald Søby
 */

mw.hook( 'wikiEditor.toolbarReady' ).add( function ( $textarea ) {
	/* Add the <ref></ref> button to the toolbar */
	$textarea.wikiEditor( 'addToToolbar', {
		section: 'main',
		group: 'insert',
		tools: {
			reference: {
				label: mw.msg( 'cite-wikieditor-tool-reference' ),
				filters: [ 'body.ns-subject' ],
				type: 'button',
				oouiIcon: 'reference',
				action: {
					type: 'encapsulate',
					options: {
						pre: '<ref>',
						post: '</ref>'
					}
				}
			}
		}
	} );

	/* Add reference help to the Help section */
	const parsedRef = function ( number ) {
		return $( '<sup>' )
			.addClass( 'reference' )
			.append(
				$( '<a>' )
					.attr( 'href', '#' )
					.text(
						mw.message( 'cite-wikieditor-help-content-reference-example-ref-result', mw.language.convertNumber( number ) ).text()
					)
			);
	};
	$textarea.wikiEditor( 'addToToolbar', {
		section: 'help',
		pages: {
			references: {
				label: mw.msg( 'cite-wikieditor-help-page-references' ),
				layout: 'table',
				headings: [
					{ html: mw.message( 'wikieditor-toolbar-help-heading-description' ).parse() },
					{ html: mw.message( 'wikieditor-toolbar-help-heading-syntax' ).parse() },
					{ html: mw.message( 'wikieditor-toolbar-help-heading-result' ).parse() }
				],
				rows: [
					{
						description: { html: mw.message( 'cite-wikieditor-help-content-reference-description' ).parse() },
						syntax: {
							html: mw.html.escape(
								mw.message( 'cite-wikieditor-help-content-reference-example-text1', mw.message( 'cite-wikieditor-help-content-reference-example-ref-normal', mw.message( 'cite-wikieditor-help-content-reference-example-text2', 'https://www.example.org/' ).plain() ).plain() ).plain()
							)
						},
						result: {
							html: mw.message( 'cite-wikieditor-help-content-reference-example-text1', parsedRef( 1 ) ).parse()
						}
					},
					{
						description: { html: mw.message( 'cite-wikieditor-help-content-named-reference-description' ).parse() },
						syntax: {
							html: mw.html.escape(
								mw.message( 'cite-wikieditor-help-content-reference-example-text1', mw.message( 'cite-wikieditor-help-content-reference-example-ref-named', mw.message( 'cite-wikieditor-help-content-reference-example-ref-id' ).plain(), mw.message( 'cite-wikieditor-help-content-reference-example-text3', 'https://www.example.org/' ).plain() ).plain() ).plain()
							)
						},
						result: {
							html: mw.message( 'cite-wikieditor-help-content-reference-example-text1', parsedRef( 2 ) ).parse()
						}
					},
					{
						description: { html: mw.message( 'cite-wikieditor-help-content-rereference-description' ).parse() },
						syntax: {
							html: mw.html.escape(
								mw.message( 'cite-wikieditor-help-content-reference-example-text1', mw.message( 'cite-wikieditor-help-content-reference-example-ref-reuse', mw.message( 'cite-wikieditor-help-content-reference-example-ref-id' ).plain() ).plain() ).plain()
							)
						},
						result: {
							html: mw.message( 'cite-wikieditor-help-content-reference-example-text1', parsedRef( 2 ) ).parse()
						}
					},
					{
						description: { html: mw.message( 'cite-wikieditor-help-content-showreferences-description' ).parse() },
						syntax: {
							html: mw.message( 'cite-wikieditor-help-content-reference-example-reflist' ).escaped()
						},
						result: {
							html: '<ol class="references">' +
								'<li><span class="mw-cite-backlink"><a href="#">' +
								mw.message( 'cite_reference_backlink_symbol' ).parse() + '</a></span> ' +
								mw.message( 'cite-wikieditor-help-content-reference-example-text2', window.location.href + '#' ).parse() +
								'</li>' +
								'<li><span class="mw-cite-backlink"><a href="#">' +
								mw.message( 'cite_reference_backlink_symbol' ).parse() +
								'</a></span> ' +
								mw.message( 'cite-wikieditor-help-content-reference-example-text3', window.location.href + '#' ).parse() +
								'</li></ol>'
						}
					}
				]
			}
		}
	} );
} );
