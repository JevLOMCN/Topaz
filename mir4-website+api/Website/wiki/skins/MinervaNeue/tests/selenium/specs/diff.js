'use strict';

const { iAmOnAPageThatHasTheFollowingEdits
	} = require( '../features/step_definitions/create_page_api_steps' ),
	{
		iAmLoggedIntoTheMobileWebsite
	} = require( '../features/step_definitions/common_steps' ),
	{
		iShouldSeeAddedContent, iShouldSeeRemovedContent
	} = require( '../features/step_definitions/diff_steps' ),
	{
		iOpenTheLatestDiff,
		iClickOnTheHistoryLinkInTheLastModifiedBar
	} = require( '../features/step_definitions/history_steps' );

describe.skip( 'Page diff', () => {
	it( 'Added and removed content', () => {
		iAmLoggedIntoTheMobileWebsite();
		iAmOnAPageThatHasTheFollowingEdits( {
			rawTable: [
				[ ' text     ' ],
				[ ' ABC DEF  ' ],
				[ ' ABC GHI  ' ]
			]
		} );
		iClickOnTheHistoryLinkInTheLastModifiedBar();
		iOpenTheLatestDiff();
		iShouldSeeAddedContent( 'GHI' );
		iShouldSeeRemovedContent( 'DEF' );
	} );
} );
