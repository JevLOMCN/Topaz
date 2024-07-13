/* eslint-env node */
const { defineConfig } = require( 'cypress' );
// const { mwApiCommands } = require( './tests/cypress/support/MwApiPlugin.js' );

const envLogDir = process.env.LOG_DIR ? process.env.LOG_DIR + '/Cite' : null;

module.exports = defineConfig( {
	e2e: {
		supportFile: false,
		specPattern: 'tests/cypress/e2e/**/*.cy.js',
		baseUrl: process.env.MW_SERVER + process.env.MW_SCRIPT_PATH,
		mediawikiAdminUsername: process.env.MEDIAWIKI_USER,
		mediawikiAdminPassword: process.env.MEDIAWIKI_PASSWORD
	},

	retries: 2,
	defaultCommandTimeout: 5000, // ms; default is 4000ms

	screenshotsFolder: envLogDir || 'tests/cypress/screenshots',
	video: true,
	videosFolder: envLogDir || 'tests/cypress/videos',
	downloadsFolder: envLogDir || 'tests/cypress/downloads'
} );
