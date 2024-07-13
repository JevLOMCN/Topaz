'use strict';

const { action, assert, utils } = require( 'api-testing' );

describe( 'Visual Editor API', function () {
	const title = utils.title( 'VisualEditor' );

	let alice;
	let pageInfo;

	before( async () => {
		const textX = 'Hello World! {{Template Requests}}';

		alice = await action.alice();
		pageInfo = await alice.edit( title, { text: textX } );
	} );

	// VisualEditor: 'visualeditor' action API ///
	it( 'can load metadata', async () => {
		const result = await alice.action( 'visualeditor', { page: title, paction: 'metadata' } );
		assert.equal( result.visualeditor.oldid, pageInfo.newrevid );
		assert.nestedProperty( result.visualeditor, 'copyrightWarning' );
		assert.nestedProperty( result.visualeditor, 'checkboxesDef' );
		assert.nestedProperty( result.visualeditor, 'checkboxesMessages' );
		assert.equal( result.visualeditor.oldid, pageInfo.newrevid );
	} );

	it( 'able to parse', async () => {
		const result = await alice.action( 'visualeditor', { page: title, paction: 'parse' } );

		assert.equal( result.visualeditor.result, 'success' );
		assert.nestedProperty( result.visualeditor, 'copyrightWarning' );
		assert.nestedProperty( result.visualeditor, 'checkboxesDef' );
		assert.nestedProperty( result.visualeditor, 'checkboxesMessages' );

		assert.nestedProperty( result.visualeditor, 'etag' );
		assert.match( result.visualeditor.etag, /^(W\/)?".*\d+\// );

		assert.nestedProperty( result.visualeditor, 'oldid' );
		assert.equal( result.visualeditor.oldid, pageInfo.newrevid );

		assert.nestedProperty( result.visualeditor, 'content' );
		assert.include( result.visualeditor.content, 'Hello World!' );
		assert.include( result.visualeditor.content, '<html' );
	} );

	it( 'able to parsefragment', async () => {
		const result = await alice.action( 'visualeditor', { page: title, paction: 'parsefragment', wikitext: 'wonderer' } );
		assert.equal( result.visualeditor.result, 'success' );

		assert.nestedProperty( result.visualeditor, 'content' );
		assert.include( result.visualeditor.content, 'wonderer' );
		assert.notInclude( result.visualeditor.content, '<html' );
	} );

	it( 'templatesUsed', async () => {
		const result = await alice.action( 'visualeditor', { page: title, paction: 'templatesused', wikitext: 'test' } );
		assert.include( result.visualeditor, 'Template Requests' );
	} );

	it( 'can load wikitext', async () => {
		const result = await alice.action( 'visualeditor', { page: title, paction: 'wikitext' } );
		assert.equal( result.visualeditor.result, 'success' );
	} );

	// VisualEditor edit: 'visualeditoredit' action API ///
	const page = utils.title( 'VisualEditorNew' );

	describe( 'Editing', function () {
		it( 'Should create page, edit and save page with HTML', async () => {
			const token = await alice.token();
			const html = '<p>save paction</p>';
			const summary = 'save test workflow';
			const result = await alice.action(
				'visualeditoredit',
				{
					page: page,
					paction: 'save',
					token: token,
					html: html,
					summary: summary
				},
				'post'
			);

			assert.equal( result.visualeditoredit.result, 'success' );
		} );

		it( 'Should refuse to edit with a bad token', async () => {
			const token = 'dshfkjdsakf';
			const html = '<p>save paction</p>';
			const summary = 'save test workflow';
			const error = await alice.actionError(
				'visualeditoredit',
				{
					page: page,
					paction: 'save',
					token: token,
					html: html,
					summary: summary
				},
				'post'
			);

			assert.equal( error.code, 'badtoken' );
		} );

		it( 'Should use selser when editing', async () => {
			const token = await alice.token();
			let result;

			// Create a page with messy wikitext
			const originalWikitext = '*a\n* b\n*  <i>c</I>';

			result = await alice.action(
				'visualeditoredit',
				{
					page,
					paction: 'save',
					token,
					wikitext: originalWikitext,
					summary: 'editing wikitext'
				},
				'post'
			);
			assert.equal( result.visualeditoredit.result, 'success' );

			// Fetch HTML for editing
			result = await alice.action( 'visualeditor', { page, paction: 'parse' } );
			assert.equal( result.visualeditor.result, 'success' );

			let html = result.visualeditor.content;
			const etag = result.visualeditor.etag;
			const oldid = result.visualeditor.oldid;

			// Append to HTML
			html = html.replace( '</body>', '<p>More Text</p></body>' );
			result = await alice.action(
				'visualeditoredit',
				{
					page,
					paction: 'save',
					token,
					html,
					etag,
					oldid,
					summary: 'appending html'
				},
				'post'
			);

			// TODO: Make a test that will fail if the etag is not used to look up stashed HTML.
			//       This test will pass even if stashing is not used, because in that case
			//       the base revision will be re-rendered, and the HTML will still match.

			assert.equal( result.visualeditoredit.result, 'success' );

			// Fetch wikitext to check
			result = await alice.action( 'visualeditor', { page, paction: 'wikitext' } );
			assert.equal( result.visualeditor.result, 'success' );

			// Make sure the new content was appended, but the wikitext was kept
			// in its original messy state.
			const newWikitext = result.visualeditor.content;
			assert.include( newWikitext, originalWikitext );
			assert.include( newWikitext, 'More Text' );
		} );

		it( 'Should save edit after switching from source mode (T321862)', async () => {
			const token = await alice.token();
			let result;

			// Create a page with messy wikitext
			const originalWikitext = '*a\n* b\n*  <i>c</I>';

			result = await alice.action(
				'visualeditoredit',
				{
					page,
					paction: 'save',
					token,
					wikitext: originalWikitext,
					summary: 'editing wikitext'
				},
				'post'
			);
			assert.equal( result.visualeditoredit.result, 'success' );

			// Modify wikitext
			const modifiedWikitext = originalWikitext + '\nfirst addition';

			// Switch to HTML using modified wikitext
			result = await alice.action(
				'visualeditor',
				{
					page,
					paction: 'parse',
					wikitext: modifiedWikitext,
					stash: 'yes'
				},
				'post'
			);
			assert.equal( result.visualeditor.result, 'success' );

			// Append to HTML and save, using the etag produced when switching to HTML
			const html = result.visualeditor.content;
			const etag = result.visualeditor.etag;
			const modifiedHtml = html.replace( '</body>', '<p>second addition</p></body>' );

			result = await alice.action(
				'visualeditoredit',
				{
					page,
					paction: 'save',
					token,
					html: modifiedHtml,
					etag,
					summary: 'appending html'
				},
				'post'
			);

			assert.equal( result.visualeditoredit.result, 'success' );

			// Fetch wikitext to check
			result = await alice.action( 'visualeditor', { page, paction: 'wikitext' } );
			assert.equal( result.visualeditor.result, 'success' );

			// Make sure the new content was appended, but the wikitext was kept
			// in its original messy state.
			const newWikitext = result.visualeditor.content;
			assert.include( newWikitext, originalWikitext );
			assert.include( newWikitext, 'first addition' );
			assert.include( newWikitext, 'second addition' );
		} );
	} );

	it( 'Should show page diff', async () => {
		const token = await alice.token();
		const html = '<p>diff paction</p>';
		const summary = 'diff page test workflow';
		const result = await alice.action(
			'visualeditoredit',
			{
				page: title,
				paction: 'diff',
				token: token,
				html: html,
				summary: summary
			},
			'post'
		);
		assert.equal( result.visualeditoredit.result, 'success' );
	} );

	it( 'Should serialize page', async () => {
		const token = await alice.token();
		const html = '<h2>serialize paction test</h2>';
		const summary = 'serialize page test workflow';
		const result = await alice.action(
			'visualeditoredit',
			{
				page: title,
				paction: 'serialize',
				token: token,
				html: html,
				summary: summary
			},
			'post'
		);
		assert.equal( result.visualeditoredit.result, 'success' );

		// Trim to remove trailing newline in the content
		assert.equal( result.visualeditoredit.content.trim(), '== serialize paction test ==' );
	} );

	it( 'Should serialize page for cache', async () => {
		const token = await alice.token();
		const html = '<p>serialize for cache paction</p>';
		const summary = 'serializeforcache create page test workflow';
		const result = await alice.action(
			'visualeditoredit',
			{
				page: title,
				paction: 'serializeforcache',
				token: token,
				html: html,
				summary: summary
			},
			'post'
		);
		assert.equal( result.visualeditoredit.result, 'success' );
	} );
} );
