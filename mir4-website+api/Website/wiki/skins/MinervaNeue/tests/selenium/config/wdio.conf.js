/**
 * Extending the Mediawiki core webdriver config
 */

'use strict';

const path = require( 'path' ),
	coreConfig = require( '../wdio.conf' ),
	relPath = ( foo ) => path.resolve( __dirname, '../..', foo ),
	MinervaConfig = Object.assign( coreConfig.config, {
		services: [],
		specs: [
			relPath( './selenium/features/*.feature' )
		],
		cucumberOpts: {
			tagsInTitle: true,
			timeout: 20000, // 20 seconds
			require: [
				relPath( './selenium/features/support/world.js' ),
				relPath( './selenium/features/support/hooks.js' ),
				relPath( './selenium/features/step_definitions/create_page_api_steps.js' ),
				relPath( './selenium/features/step_definitions/common_steps.js' ),
				relPath( './selenium/features/step_definitions/category_steps.js' ),
				relPath( './selenium/features/step_definitions/editor_steps.js' ),
				relPath( './selenium/features/step_definitions/diff_steps.js' ),
				relPath( './selenium/features/step_definitions/special_history_steps.js' )
			]
		},
		framework: 'cucumber'
	} );

exports.config = MinervaConfig;
