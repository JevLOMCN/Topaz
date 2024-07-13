'use strict';

/*!
 * VisualEditor DataModel Cite-specific Converter tests.
 *
 * @copyright 2011-2018 VisualEditor Team's Cite sub-team and others; see AUTHORS.txt
 * @license MIT
 */

QUnit.module( 've.dm.Converter (Cite)', ve.test.utils.newMwEnvironment() );

QUnit.test( 'getModelFromDom', function ( assert ) {
	const cases = ve.dm.citeExample.domToDataCases;

	for ( const msg in cases ) {
		const caseItem = ve.copy( cases[ msg ] );
		caseItem.base = ve.dm.citeExample.baseUri;
		caseItem.mwConfig = {
			wgArticlePath: '/wiki/$1'
		};
		// TODO: Cite tests contain unescaped < in attrs, handle this upstream somehow
		caseItem.ignoreXmlWarnings = true;
		if ( caseItem.mwConfig ) {
			mw.config.set( caseItem.mwConfig );
		}

		ve.test.utils.runGetModelFromDomTest( assert, caseItem, msg );
	}
} );

QUnit.test( 'getDomFromModel', function ( assert ) {
	const cases = ve.dm.citeExample.domToDataCases;

	for ( const msg in cases ) {
		const caseItem = ve.copy( cases[ msg ] );
		caseItem.base = ve.dm.citeExample.baseUri;
		caseItem.mwConfig = {
			wgArticlePath: '/wiki/$1'
		};
		// TODO: Cite tests contain unescaped < in attrs, handle this upstream somehow
		caseItem.ignoreXmlWarnings = true;
		if ( caseItem.mwConfig ) {
			mw.config.set( caseItem.mwConfig );
		}

		ve.test.utils.runGetDomFromModelTest( assert, caseItem, msg );
	}
} );
