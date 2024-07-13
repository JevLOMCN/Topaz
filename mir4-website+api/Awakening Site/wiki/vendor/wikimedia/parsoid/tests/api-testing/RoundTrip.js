/**
 * Cases for testing the Parsoid round trip conversions: WT -> HTML -> WT
 * in both SelSer and non-SelSer modes.
 */

'use strict';

const { REST, assert, action, utils } = require("api-testing");

const url = require('url');
const rt = require('../../bin/roundtrip-test');
function status200( res ) {
	assert.strictEqual( res.status, 200, res.text );
}

describe( 'Parsoid round-trip e2e testing with MW REST endpoints', function () {
	this.timeout( 30000 );
	const client = new REST();
	const parsedUrl = new url.URL(client.req.app);
	client.pathPrefix = 'rest.php';
	const page = utils.title( 'RoundTrip ' );
	let revid;
	const httpClient = {
		request: function (httpOptions) {
			return client.request(
				httpOptions.uri,
				httpOptions.method,
				httpOptions.body || httpOptions.params,
				httpOptions.headers
			)
				.redirects(1) // roundtrip-test.js executes at least one redirect per request
				.expect(status200)
				.then((res) => {
					return [
						{ request: res.req, headers: res.headers },
						Object.keys(res.body).length !== 0 ? res.body : res.text
					];
				});
		}
	};

	before( async function () {
		const alice = await action.alice();

		// Create pages
		const edit = await alice.edit( page, { text: '{|\nhi\n|ho\n|}' } );
		assert.strictEqual(edit.result, 'Success');
		revid = edit.newrevid;
	} );

	it( 'rt-testing e2e', async function () {
		const result = await rt.runTests(page, {
			httpClient,
			domain: parsedUrl.hostname,
			parsoidURLOpts: {
				baseUrl: '',
			}
		}, rt.jsonFormat);
		assert.strictEqual(result.output.error, undefined, result.output.error);
		assert.strictEqual(result.exitCode, 0, result.output.error);
	} );
} );
