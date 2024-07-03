'use strict';

const { ArticlePage } = require( '../support/world' ),
	RunJobs = require( 'wdio-mediawiki/RunJobs' ),
	Api = require( 'wdio-mediawiki/Api' ),
	Page = require( 'wdio-mediawiki/Page' ),
	MWBot = require( 'mwbot' ),
	{
		iAmOnPage,
		waitForPropagation,
		createPages,
		createPage
	} = require( './common_steps' );

const iAmInAWikiThatHasCategories = ( title ) => {
	const msg = 'This page is used by Selenium to test category related features.',
		wikitext = `
            ${ msg }

            [[Category:Test category]]
            [[Category:Selenium artifacts]]
            [[Category:Selenium hidden category]]
        `;

	createPages( [
		[ 'create', 'Category:Selenium artifacts', msg ],
		[ 'create', 'Category:Test category', msg ],
		[ 'create', 'Category:Selenium hidden category', '__HIDDENCAT__' ]
	] );

	// A pause is necessary to let the categories register with database before trying to use
	// them in an article
	waitForPropagation( 5000 );
	browser.call( async () => {
		const bot = await Api.bot();
		await bot.edit( title, wikitext );
	} );

	browser.call( () => {
		// The category overlay uses the category API
		// which will only return results if the job queue has completed.
		// Run before continuing!
		return RunJobs.run();
	} );
};

const iAmOnAPageThatHasTheFollowingEdits = function ( table ) {
	const randomString = Math.random().toString( 36 ).slice( 7 ),
		pageTitle = `Selenium_diff_test_${ randomString }`,
		edits = table.rawTable.map( ( row, i ) =>
			[ i === 0 ? 'create' : 'edit', pageTitle, row[ 0 ] ] );

	browser.call( () => {
		const bot = new MWBot();
		return bot.loginGetEditToken( {
			username: browser.options.username,
			password: browser.options.password,
			apiUrl: `${ browser.options.baseUrl }/api.php`
		} )
			.then( () => bot.batch( edits ) )
			.catch( ( err ) => {
				throw err;
			} );
	} );

	browser.call( () => RunJobs.run() );
	ArticlePage.open( pageTitle );
};

const iGoToAPageThatHasLanguages = () => {
	const wikitext = `This page is used by Selenium to test language related features.

	[[es:Selenium language test page]]
`;

	browser.call( () => {
		createPage( 'Selenium language test page', wikitext );
	} );
	browser.call( () => {
		iAmOnPage( 'Selenium language test page' );
	} );
};

const watch = ( title ) => {
	// Ideally this would use the API but mwbot / Selenium's API can't do this right now
	// So we run the non-js workflow.
	const page = new Page();
	page.openTitle( title, { action: 'watch' } );
	$( '#mw-content-text button[type="submit"]' ).waitForDisplayed();
	$( '#mw-content-text button[type="submit"]' ).click();
	waitForPropagation( 10000 );
	page.openTitle( title );
};

const iAmViewingAWatchedPage = () => {
	const title = `I am on the "Selenium mobile watched page test ${ Date.now() }`;
	browser.call( () => {
		return createPage( title, 'watch test' );
	} );
	watch( title );
	// navigate away from page
	iAmOnPage( 'Main Page' );
	waitForPropagation( 5000 );
	// and back to page
	iAmOnPage( title );
	waitForPropagation( 5000 );
};

const iAmViewingAnUnwatchedPage = async () => {
	// new pages are watchable but unwatched by default
	const title = 'I am on the "Selenium mobile unwatched test ' + new Date();
	await iAmOnPage( title );
};

const iAmOnATalkPageWithNoTalkTopics = () => {
	const title = `Selenium talk test ${ new Date() }`;

	createPage( title, 'Selenium' );
	iAmOnPage( `Talk:${ title }` );
};

module.exports = {
	waitForPropagation,
	iAmOnAPageThatHasTheFollowingEdits,
	iAmOnATalkPageWithNoTalkTopics,
	iAmViewingAWatchedPage,
	iAmViewingAnUnwatchedPage,
	iAmInAWikiThatHasCategories,
	iGoToAPageThatHasLanguages
};
