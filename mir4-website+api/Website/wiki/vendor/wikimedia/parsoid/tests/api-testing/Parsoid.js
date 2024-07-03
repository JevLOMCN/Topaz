/** Cases for testing the Parsoid API through HTTP */
/* global describe, it, before */

'use strict';

const { action, assert, REST, utils } = require( 'api-testing' );

var domino = require('domino');
var should = require('chai').should();
var semver = require('semver');
var url = require('url');
const { DOMUtils } = require( "../../lib/utils/DOMUtils" );

var Util = require('../../lib/utils/Util.js').Util;
var JSUtils = require('../../lib/utils/jsutils').JSUtils;

const parsoidOptions = {
	limits: {
		wt2html: { maxWikitextSize: 20000 },
		html2wt: { maxHTMLSize: 10000 },
	},
};

// FIXME(T283875): These should all be re-enabled
var skipForNow = true;

var defaultContentVersion = '2.8.0';

// section wrappers are a distraction from the main business of
// this file which is to verify functionality of API end points
// independent of what they are returning and computing.
//
// Verifying the correctness of content is actually the job of
// parser tests and other tests.
//
// So, hide most of that that distraction in a helper.
//
// Right now, all uses of this helper have empty lead sections.
// But, maybe in the future, this may change. So, retain the option.
function validateDoc(doc, nodeName, emptyLead) {
	var leadSection = doc.body.firstChild;
	leadSection.nodeName.should.equal('SECTION');
	if (emptyLead) {
		// Could have whitespace and comments
		leadSection.childElementCount.should.equal(0);
	}
	var nonEmptySection = emptyLead ? leadSection.nextSibling : leadSection;
	nonEmptySection.firstChild.nodeName.should.equal(nodeName);
}

function status200( res ) {
	assert.strictEqual( res.status, 200, res.text );
}

// Return a matcher function that checks whether a content type matches the given parameters.
function contentTypeMatcher( expectedMime, expectedSpec, expectedVersion ) {
	const pattern = /^([-\w]+\/[-\w]+);(?: charset=utf-8;)? profile="https:\/\/www.mediawiki.org\/wiki\/Specs\/([-\w]+)\/(\d+\.\d+\.\d+)"$/;

	return ( actual ) => {
		const parts = pattern.exec( actual );
		if ( !parts ) {
			return false;
		}

		const [ , mime, spec, version ] = parts;

		// match version using caret semantics
		if ( !semver.satisfies( version, `^${expectedVersion || defaultContentVersion}` ) ) {
			return false;
		}

		if ( mime !== expectedMime || spec !== expectedSpec ) {
			return false;
		}

		return true;
	};
}

describe('Parsoid API', function() {
	const client = new REST();
	const parsedUrl = new url.URL(client.req.app);
	const hostname = parsedUrl.hostname;
	const mockDomain = client.pathPrefix = `rest.php/${hostname}`;
	const page = utils.title( 'Lint Page ' );
	const pageEncoded = encodeURIComponent( page );
	let revid, oldrevid;

	before(async function() {
		this.timeout(30000);

		const alice = await action.alice();

		// Create pages
		const oldEdit = await alice.edit(page, { text: 'First Revision Content' });
		oldEdit.result.should.equal('Success');
		oldrevid = oldEdit.newrevid;

		let edit = await alice.edit(page, { text: '{|\n|hi\n|ho\n|}' });
		edit.result.should.equal('Success');
		revid = edit.newrevid;

		edit = await alice.edit('JSON Page', {
			text: '[1]', contentmodel: 'json'
		});
		edit.result.should.equal('Success');
	});

	const acceptableHtmlResponse = function(contentVersion, expectFunc) {
		return function(res) {
			res.statusCode.should.equal(200, res.text);
			res.headers.should.have.property('content-type');
			res.headers['content-type'].should.equal(
				'text/html; charset=utf-8; profile="https://www.mediawiki.org/wiki/Specs/HTML/' + contentVersion + '"'
			);
			res.text.should.not.equal('');
			if (expectFunc) {
				return expectFunc(res.text);
			}
		};
	};

	const acceptablePageBundleResponse = function(contentVersion, expectFunc) {
		return function(res) {
			res.statusCode.should.equal(200, res.text);
			res.headers.should.have.property('content-type');
			res.headers['content-type'].should.satisfy( contentTypeMatcher( 'application/json', 'pagebundle', contentVersion ) );
			res.body.should.have.property('html');
			res.body.html.should.have.property('headers');
			res.body.html.headers.should.have.property('content-type');
			res.body.html.headers['content-type'].should.satisfy( contentTypeMatcher( 'text/html', 'HTML', contentVersion ) );
			res.body.html.should.have.property('body');
			res.body.should.have.property('data-parsoid');
			res.body['data-parsoid'].should.have.property('headers');
			res.body['data-parsoid'].headers.should.have.property('content-type');
			res.body['data-parsoid'].headers['content-type'].should.satisfy( contentTypeMatcher( 'application/json', 'data-parsoid', contentVersion ) );
			res.body['data-parsoid'].should.have.property('body');
			if (semver.gte(contentVersion, '999.0.0')) {
				res.body.should.have.property('data-mw');
				res.body['data-mw'].should.have.property('headers');
				res.body['data-mw'].headers.should.have.property('content-type');
				res.body['data-mw'].headers['content-type'].should.satisfy( contentTypeMatcher( 'application/json', 'data-mw', contentVersion ) );
				res.body['data-mw'].should.have.property('body');
			}
			if (expectFunc) {
				return expectFunc(res.body.html.body);
			}
		};
	};

	describe('domain check', function() {
		it('should apply to /v3/transform endpoint', function(done) {
			client.req
				.get('rest.php/the.wrong.domain/v3/page/wikitext/' + pageEncoded )
				.expect(function(res) {
					res.status.should.equal(400, res.text);
					res.body.error.should.equal('parameter-validation-failed');
					res.body.failureCode.should.equal('invalid-domain');
				})
				.end(done);
		});

		it('should apply to /v3/transform endpoint', function(done) {
			client.req
				.post('rest.php/the.wrong.domain/v3/transform/wikitext/to/html/')
				.send({ wikitext: '== h2 ==' })
				.expect(function(res) {
					res.status.should.equal(400, res.text);
					res.body.error.should.equal('parameter-validation-failed');
					res.body.failureCode.should.equal('invalid-domain');
				})
				.end(done);
		});
	});

	describe('formats', function() {

		it('should accept application/x-www-form-urlencoded', function(done) {
			client.req
				.post(mockDomain + '/v3/transform/wikitext/to/html/')
				.type('form')
				.send({
					wikitext: '== h2 ==',
				})
				.expect(status200)
				.expect(function(res) {
					validateDoc(domino.createDocument(res.text), 'H2', true);
				})
				.end(done);
		});

		it('should accept application/json', function(done) {
			client.req
				.post(mockDomain + '/v3/transform/wikitext/to/html/')
				.type('json')
				.send({
					wikitext: '== h2 ==',
				})
				.expect(status200)
				.expect(function(res) {
					validateDoc(domino.createDocument(res.text), 'H2', true);
				})
				.end(done);
		});

		it('should accept multipart/form-data', function(done) {
			client.req
				.post(mockDomain + '/v3/transform/wikitext/to/html/')
				.field('wikitext', '== h2 ==')
				.expect(status200)
				.expect(function(res) {
					validateDoc(domino.createDocument(res.text), 'H2', true);
				})
				.end(done);
		});

		// Skipped because all errors are returned as JSON in Parsoid/PHP
		it.skip('should return a plaintext error', function(done) {
			client.req
				.get(mockDomain + '/v3/page/wikitext/Doesnotexist')
				.expect(404)
				.expect(function(res) {
					res.headers['content-type'].should.equal(
						'text/plain; charset=utf-8'
					);
					res.text.should.equal('Did not find page revisions for Doesnotexist');
				})
				.end(done);
		});

		it('should return a json error', function(done) {
			client.req
				.get(mockDomain + '/v3/page/pagebundle/Doesnotexist')
				.expect(404)
				.expect(function(res) {
					res.headers['content-type'].should.equal(
						'application/json'
					);
					res.body.message.should.equal('The specified revision does not exist.');
				})
				.end(done);
		});

		// Skipped because all errors are returned as JSON in Parsoid/PHP
		it.skip('should return an html error', function(done) {
			client.req
				.get('<img src=x onerror="javascript:alert(\'hi\')">/v3/page/html/XSS')
				.expect(404)
				.expect(function(res) {
					res.headers['content-type'].should.equal(
						'text/html; charset=utf-8'
					);
					res.text.should.equal('Invalid domain: &lt;img src=x onerror=&quot;javascript:alert(&apos;hi&apos;)&quot;&gt;');
				})
				.end(done);
		});

	});  // formats

	describe('accepts', function() {

		it('should not accept requests for older content versions (html)', function(done) {
			client.req
			.post(mockDomain + '/v3/transform/wikitext/to/html/')
			.set('Accept', 'text/html; profile="https://www.mediawiki.org/wiki/Specs/HTML/0.0.0"')
			.send({ wikitext: '== h2 ==' })
			.expect(406)
			.expect(function(res) {
				// FIXME: See skipped html error test above
				JSON.parse(res.error.text).errorKey.should.equal(
					'rest-unsupported-target-format'
				);
			})
			.end(done);
		});

		it('should not accept requests for older content versions (pagebundle)', function(done) {
			client.req
			.post(mockDomain + '/v3/transform/wikitext/to/pagebundle/')
			.set('Accept', 'application/json; profile="https://www.mediawiki.org/wiki/Specs/HTML/0.0.0"')
			.send({ wikitext: '== h2 ==' })
			.expect(406)
			.expect(function(res) {
				JSON.parse(res.error.text).errorKey.should.equal(
					'rest-unsupported-target-format'
				);
			})
			.end(done);
		});

		it('should not accept requests for other profiles (html)', function(done) {
			client.req
			.post(mockDomain + '/v3/transform/wikitext/to/html/')
			.set('Accept', 'text/html; profile="something different"')
			.send({ wikitext: '== h2 ==' })
			.expect(406)
			.end(done);
		});

		it('should not accept requests for other profiles (pagebundle)', function(done) {
			client.req
			.post(mockDomain + '/v3/transform/wikitext/to/pagebundle/')
			.set('Accept', 'application/json; profile="something different"')
			.send({ wikitext: '== h2 ==' })
			.expect(406)
			.end(done);
		});

		it('should accept wildcards (html)', function(done) {
			client.req
			.post(mockDomain + '/v3/transform/wikitext/to/html/')
			.set('Accept', '*/*')
			.send({ wikitext: '== h2 ==' })
			.expect(status200)
			.expect(acceptableHtmlResponse(defaultContentVersion))
			.end(done);
		});

		it('should accept wildcards (pagebundle)', function(done) {
			client.req
			.post(mockDomain + '/v3/transform/wikitext/to/pagebundle/')
			.set('Accept', '*/*')
			.send({ wikitext: '== h2 ==' })
			.expect(status200)
			.expect(acceptablePageBundleResponse(defaultContentVersion))
			.end(done);
		});

		// T347426: Support for non-default major HTML versions has been disabled
		it.skip('should prefer higher quality (html)', function(done) {
			var contentVersion = '999.0.0';
			client.req
			.post(mockDomain + '/v3/transform/wikitext/to/html/')
			.set('Accept',
				'text/html; profile="https://www.mediawiki.org/wiki/Specs/HTML/2.8.0"; q=0.5,' +
				'text/html; profile="https://www.mediawiki.org/wiki/Specs/HTML/999.0.0"; q=0.8')
			.send({ wikitext: '== h2 ==' })
			.expect(status200)
			.expect(acceptableHtmlResponse(contentVersion))
			.end(done);
		});

		// T347426: Support for non-default major HTML versions has been disabled
		it.skip('should prefer higher quality (pagebundle)', function(done) {
			var contentVersion = '999.0.0';
			client.req
			.post(mockDomain + '/v3/transform/wikitext/to/pagebundle/')
			.set('Accept',
				'application/json; profile="https://www.mediawiki.org/wiki/Specs/pagebundle/2.8.0"; q=0.5,' +
				'application/json; profile="https://www.mediawiki.org/wiki/Specs/pagebundle/999.0.0"; q=0.8')
			.send({ wikitext: '== h2 ==' })
			.expect(status200)
			.expect(acceptablePageBundleResponse(contentVersion))
			.end(done);
		});

		it('should accept requests for the latest content version (html)', function(done) {
			client.req
			.post(mockDomain + '/v3/transform/wikitext/to/html/')
			.send({ wikitext: '== h2 ==' })
			.expect(status200)
			.expect(acceptableHtmlResponse(defaultContentVersion))
			.end(done);
		});

		it('should accept requests for the latest content version (pagebundle)', function(done) {
			client.req
			.post(mockDomain + '/v3/transform/wikitext/to/pagebundle/')
			.send({ wikitext: '== h2 ==' })
			.expect(status200)
			.expect(acceptablePageBundleResponse(defaultContentVersion))
			.end(done);
		});

		it('should accept requests for content version 2.x (html)', function(done) {
			var contentVersion = '2.8.0';
			client.req
			.post(mockDomain + '/v3/transform/wikitext/to/html/')
			.set('Accept', 'text/html; profile="https://www.mediawiki.org/wiki/Specs/HTML/' + contentVersion + '"')
			.send({ wikitext: '{{1x|hi}}' })
			.expect(status200)
			.expect(acceptableHtmlResponse(contentVersion))
			.end(done);
		});

		it('should accept requests for content version 2.x (pagebundle)', function(done) {
			var contentVersion = '2.8.0';
			client.req
			.post(mockDomain + '/v3/transform/wikitext/to/pagebundle/')
			.set('Accept', 'application/json; profile="https://www.mediawiki.org/wiki/Specs/pagebundle/' + contentVersion + '"')
			.send({ wikitext: '{{1x|hi}}' })
			.expect(status200)
			.expect(acceptablePageBundleResponse(contentVersion, function(html) {
				// In < 999.x, data-mw is still inline.
				html.should.match(/\s+data-mw\s*=\s*['"]/);
			}))
			.end(done);
		});

		// Note that these tests aren't that useful directly after a major version bump

		it('should accept requests for older content version 2.x (html)', function(done) {
			var contentVersion = '2.8.0';
			client.req
			.post(mockDomain + '/v3/transform/wikitext/to/html/')
			.set('Accept', 'text/html; profile="https://www.mediawiki.org/wiki/Specs/HTML/2.0.0"')  // Keep this on the older version
			.send({ wikitext: '{{1x|hi}}' })
			.expect(status200)
			.expect(acceptableHtmlResponse(contentVersion))
			.end(done);
		});

		it('should accept requests for older content version 2.x (pagebundle)', function(done) {
			var contentVersion = '2.8.0';
			client.req
			.post(mockDomain + '/v3/transform/wikitext/to/pagebundle/')
			.set('Accept', 'application/json; profile="https://www.mediawiki.org/wiki/Specs/pagebundle/2.0.0"')  // Keep this on the older version
			.send({ wikitext: '{{1x|hi}}' })
			.expect(status200)
			.expect(acceptablePageBundleResponse(contentVersion, function(html) {
				// In < 999.x, data-mw is still inline.
				html.should.match(/\s+data-mw\s*=\s*['"]/);
			}))
			.end(done);
		});

		it('should sanity check 2.x content (pagebundle)', function(done) {
			if (skipForNow) { return this.skip(); }  // Missing files in wiki
			var contentVersion = '2.8.0';
			client.req
			.post(mockDomain + '/v3/transform/wikitext/to/pagebundle/')
			.set('Accept', 'application/json; profile="https://www.mediawiki.org/wiki/Specs/pagebundle/' + contentVersion + '"')
			.send({ wikitext: '[[File:Audio.oga]]' })
			.expect(status200)
			.expect(acceptablePageBundleResponse(contentVersion, function(html) {
				var doc = domino.createDocument(html);
				doc.querySelectorAll('audio').length.should.equal(1);
				doc.querySelectorAll('video').length.should.equal(0);
			}))
			.end(done);
		});

		// T347426: Support for non-default major HTML versions has been disabled
		it.skip('should accept requests for content version 999.x (html)', function(done) {
			var contentVersion = '999.0.0';
			client.req
			.post(mockDomain + '/v3/transform/wikitext/to/html/')
			.set('Accept', 'text/html; profile="https://www.mediawiki.org/wiki/Specs/HTML/' + contentVersion + '"')
			.send({ wikitext: '{{1x|hi}}' })
			.expect(status200)
			.expect(acceptableHtmlResponse(contentVersion))
			.end(done);
		});

		// T347426: Support for non-default major HTML versions has been disabled
		it.skip('should accept requests for content version 999.x (pagebundle)', function(done) {
			var contentVersion = '999.0.0';
			client.req
			.post(mockDomain + '/v3/transform/wikitext/to/pagebundle/')
			.set('Accept', 'application/json; profile="https://www.mediawiki.org/wiki/Specs/pagebundle/' + contentVersion + '"')
			.send({ wikitext: '{{1x|hi}}' })
			.expect(status200)
			.expect(acceptablePageBundleResponse(contentVersion, function(html) {
				// In 999.x, data-mw is in the pagebundle.
				html.should.not.match(/\s+data-mw\s*=\s*['"]/);
			}))
			.end(done);
		});

	});  // accepts

	const validWikitextResponse = function(expected) {
		return function(res) {
			res.statusCode.should.equal(200, res.text);
			res.headers.should.have.property('content-type');
			res.headers['content-type'].should.satisfy( contentTypeMatcher( 'text/plain', 'wikitext', '1.0.0' ) );
			if (expected !== undefined) {
				res.text.should.equal(expected);
			} else {
				res.text.should.not.equal('');
			}
		};
	};

	const validHtmlResponse = function(expectFunc) {
		return function(res) {
			res.statusCode.should.equal(200, res.text);
			res.headers.should.have.property('content-type');
			res.headers['content-type'].should.satisfy( contentTypeMatcher( 'text/html', 'HTML' ) );
			var doc = domino.createDocument(res.text);
			if (expectFunc) {
				return expectFunc(doc);
			} else {
				res.text.should.not.equal('');
			}
		};
	};

	const validJsonResponse = function(expected) {
		return function(res) {
			res.statusCode.should.equal(200, res.text);
			res.headers.should.have.property('content-type');
			res.headers['content-type'].should.equal( 'application/json' );
			if (expected !== undefined) {
				res.text.should.equal(expected);
			} else {
				res.text.should.not.equal('');
			}
		};
	};

	const validPageBundleResponse = function(expectFunc) {
		return function(res) {
			res.statusCode.should.equal(200, res.text);
			res.body.should.have.property('html');
			res.body.html.should.have.property('headers');
			res.body.html.headers.should.have.property('content-type');
			res.body.html.headers['content-type'].should.satisfy( contentTypeMatcher( 'text/html', 'HTML' ) );
			res.body.html.should.have.property('body');
			res.body.should.have.property('data-parsoid');
			res.body['data-parsoid'].should.have.property('headers');
			res.body['data-parsoid'].headers.should.have.property('content-type');
			res.body['data-parsoid'].headers['content-type'].should.satisfy( contentTypeMatcher( 'application/json', 'data-parsoid' ) );
			res.body['data-parsoid'].should.have.property('body');
			// TODO: Check data-mw when 999.x is the default.
			console.assert(!semver.gte(defaultContentVersion, '999.0.0'));
			var doc = domino.createDocument(res.body.html.body);
			if (expectFunc) {
				return expectFunc(doc, res.body['data-parsoid'].body);
			}
		};
	};

	describe('wt2lint', function() {

		it('should lint the given page', function(done) {
			if (skipForNow) { return this.skip(); }  // Enable linting config
			client.req
			.get(mockDomain + '/v3/page/lint/' + page + '/' + revid )
			.expect(status200)
			.expect(function(res) {
				res.body.should.be.instanceof(Array);
				res.body.length.should.equal(1);
				res.body[0].type.should.equal('fostered');
			})
			.end(done);
		});

		it('should lint the given wikitext', function(done) {
			if (skipForNow) { return this.skip(); }  // Enable linting config
			client.req
			.post(mockDomain + '/v3/transform/wikitext/to/lint/')
			.send({
				wikitext: {
					headers: {
						'content-type': 'text/plain;profile="https://www.mediawiki.org/wiki/Specs/wikitext/1.0.0"',
					},
					body: "{|\nhi\n|ho\n|}",
				},
			})
			.expect(status200)
			.expect(function(res) {
				res.body.should.be.instanceof(Array);
				res.body.length.should.equal(1);
				res.body[0].type.should.equal('fostered');
			})
			.end(done);
		});

		it('should lint the given page, transform', function(done) {
			if (skipForNow) { return this.skip(); }  // Enable linting config
			client.req
			.post(mockDomain + '/v3/transform/wikitext/to/lint/' + page + '/' + revid)
			.send({})
			.expect(status200)
			.expect(function(res) {
				res.body.should.be.instanceof(Array);
				res.body.length.should.equal(1);
				res.body[0].type.should.equal('fostered');
			})
			.end(done);
		});

		it('should redirect title to latest revision (lint)', function(done) {
			client.req
			.post(mockDomain + '/v3/transform/wikitext/to/lint/')
			.send({
				// no revid or wikitext source provided
				original: {
					title: page,
				},
			})
			.expect( validJsonResponse() )
			.end(done);
		});

	});

	describe("wt2html", function() {

		it('should get from a title and old revision (html)', function(done) {
			client.req
			.get(mockDomain + `/v3/page/html/${page}/${oldrevid}`)
			.redirects( 0 )
			.expect(validHtmlResponse(function(doc) {
				doc.body.firstChild.firstChild.textContent.should.contain('First Revision Content');
			}))
			.end(done);
		});

		it('should get from a title and current revision (html)', function(done) {
			client.req
			.get(mockDomain + `/v3/page/html/${page}`)
			.redirects( 1 )
			.expect(validHtmlResponse(function(doc) {
				doc.body.firstChild.firstChild.textContent.should.contain('hi');
			}))
			.end(done);
		});

		// Disabled for T349098
		it.skip('should get from a title and current revision (html, json content)', function(done) {
			client.req
			.get(mockDomain + '/v3/page/html/JSON_Page')
			.redirects(1)
			.expect(validHtmlResponse(function(doc) {
				doc.body.firstChild.nodeName.should.equal('TABLE');
			}))
			.end(done);
		});

		it('should get from a title and revision (pagebundle)', function(done) {
			client.req
			.get(mockDomain + `/v3/page/pagebundle/${page}/${revid}`)
			.redirects( 0 )
			.expect(validPageBundleResponse())
			.end(done);
		});

		// Disabled for T349098
		it.skip('should get from a title and revision (pagebundle, json content)', function(done) {
			client.req
			.get(mockDomain + '/v3/page/pagebundle/JSON_Page')
			.redirects(1)
			.expect(validPageBundleResponse(function(doc) {
				doc.body.firstChild.nodeName.should.equal('TABLE');
			}))
			.end(done);
		});

		it('should get from a title and revision (wikitext)', function(done) {
			client.req
			.get(mockDomain + `/v3/page/wikitext/${page}/${oldrevid}`)
			.redirects( 0 )
			.expect(validWikitextResponse('First Revision Content'))
			.end(done);
		});

		it.skip('should set a custom etag for get requests (html)', function(done) {
			const etagPrefixExp = new RegExp( '^"' + revid + '/' );
			client.req
			.get(mockDomain + `/v3/page/html/${page}/${revid}`)
			.expect(validHtmlResponse())
			.expect((res) => {
				res.headers.should.have.property('etag');
				res.headers.etag.should.match( etagPrefixExp );
			})
			.end(done);
		});

		it.skip('should set a custom etag for get requests (pagebundle)', function(done) {
			const etagPrefixExp = new RegExp( '^"' + revid + '/' );
			client.req
			.get(mockDomain + `/v3/page/pagebundle/${page}/${revid}`)
			.expect(validPageBundleResponse())
			.expect((res) => {
				res.headers.should.have.property('etag');
				res.headers.etag.should.match( etagPrefixExp );
			})
			.end(done);
		});

		it('should accept wikitext as a string for html', function(done) {
			client.req
			.post(mockDomain + '/v3/transform/wikitext/to/html/')
			.send({
				wikitext: "== h2 ==",
			})
			.expect(validHtmlResponse(function(doc) {
				validateDoc(doc, 'H2', true);
			}))
			.end(done);
		});

		it('should accept json contentmodel as a string for html', function(done) {
			client.req
			.post(mockDomain + '/v3/transform/wikitext/to/html/')
			.send({
				wikitext: '{"1":2}',
				contentmodel: 'json',
			})
			.expect(validHtmlResponse(function(doc) {
				doc.body.firstChild.nodeName.should.equal('TABLE');
			}))
			.end(done);
		});

		it('should accept wikitext as a string for pagebundle', function(done) {
			client.req
			.post(mockDomain + '/v3/transform/wikitext/to/pagebundle/')
			.send({
				wikitext: "== h2 ==",
			})
			.expect(validPageBundleResponse(function(doc) {
				validateDoc(doc, 'H2', true);
			}))
			.end(done);
		});

		it('should accept json contentmodel as a string for pagebundle', function(done) {
			client.req
			.post(mockDomain + '/v3/transform/wikitext/to/pagebundle/')
			.send({
				wikitext: '{"1":2}',
				contentmodel: 'json',
			})
			.expect(validPageBundleResponse(function(doc) {
				doc.body.firstChild.nodeName.should.equal('TABLE');
				should.not.exist(doc.querySelector('*[typeof="mw:Error"]'));
			}))
			.end(done);
		});

		it('should accept wikitext with headers', function(done) {
			client.req
			.post(mockDomain + '/v3/transform/wikitext/to/html/')
			.send({
				wikitext: {
					headers: {
						'content-type': 'text/plain;profile="https://www.mediawiki.org/wiki/Specs/wikitext/1.0.0"',
					},
					body: "== h2 ==",
				},
			})
			.expect(validHtmlResponse(function(doc) {
				validateDoc(doc, 'H2', true);
			}))
			.end(done);
		});

		it('should require a title when no wikitext is provided (html)', function(done) {
			client.req
			.post(mockDomain + '/v3/transform/wikitext/to/html/')
			.send({})
			.expect(400)
			.end(done);
		});

		it('should require a title when no wikitext is provided (pagebundle)', function(done) {
			client.req
			.post(mockDomain + '/v3/transform/wikitext/to/pagebundle/')
			.send({})
			.expect(400)
			.end(done);
		});

		it('should error when revision not found (page, html)', function(done) {
			client.req
			.get(mockDomain + '/v3/page/html/Doesnotexist')
			.expect(404)
			.end(done);
		});

		it('should error when revision not found (page, pagebundle)', function(done) {
			client.req
			.get(mockDomain + '/v3/page/pagebundle/Doesnotexist')
			.expect(404)
			.end(done);
		});

		it('should error when revision not found (transform, wt2html)', function(done) {
			client.req
			.post(mockDomain + '/v3/transform/wikitext/to/html/Doesnotexist')
			.send({})
			.expect(404)
			.end(done);
		});

		it('should error when revision not found (transform, wt2pb)', function(done) {
			client.req
			.post(mockDomain + '/v3/transform/wikitext/to/pagebundle/Doesnotexist')
			.send({})
			.expect(404)
			.end(done);
		});

		it('should accept an original title (html)', function(done) {
			client.req
			.post(mockDomain + '/v3/transform/wikitext/to/html/')
			.send({
				// no revid or wikitext source provided
				original: {
					title: page,
				},
			})
			.expect(validHtmlResponse())
			.end(done);
		});

		it('should accept an original title (pagebundle)', function(done) {
			client.req
			.post(mockDomain + '/v3/transform/wikitext/to/pagebundle/')
			.send({
				// no revid or wikitext source provided
				original: {
					title: page,
				},
			})
			.expect(validPageBundleResponse())
			.end(done);
		});

		it('should accept an original title, other than main', function(done) {
			client.req
			.post(mockDomain + '/v3/transform/wikitext/to/html/')
			.send({
				// no revid or wikitext source provided
				original: {
					title: page,
				},
			})
			.expect(validHtmlResponse())
			.end(done);
		});

		it('should not require a title when empty wikitext is provided (html)', function(done) {
			client.req
			.post(mockDomain + '/v3/transform/wikitext/to/html/')
			.send({
				wikitext: '',
			})
			.expect(validHtmlResponse(function(doc) {
				doc.body.children.length.should.equal(1); // empty lead section
				doc.body.firstChild.nodeName.should.equal('SECTION');
				doc.body.firstChild.children.length.should.equal(0);
			}))
			.end(done);
		});

		it('should not require a title when empty wikitext is provided (pagebundle)', function(done) {
			client.req
			.post(mockDomain + '/v3/transform/wikitext/to/pagebundle/')
			.send({
				wikitext: '',
			})
			.expect(validPageBundleResponse())
			.end(done);
		});

		it('should not require a title when wikitext is provided', function(done) {
			client.req
			.post(mockDomain + '/v3/transform/wikitext/to/html/')
			.send({
				wikitext: "== h2 ==",
			})
			.expect(validHtmlResponse(function(doc) {
				validateDoc(doc, 'H2', true);
			}))
			.end(done);
		});

		it('should not require a rev id when wikitext and a title is provided', function(done) {
			client.req
			.post(mockDomain + '/v3/transform/wikitext/to/html/Main_Page')
			.send({
				wikitext: "== h2 ==",
			})
			.expect(validHtmlResponse(function(doc) {
				validateDoc(doc, 'H2', true);
			}))
			.end(done);
		});

		it('should accept the wikitext source as original data', function(done) {
			client.req
			.post(mockDomain + `/v3/transform/wikitext/to/html/${page}/${revid}`)
			.send({
				original: {
					wikitext: {
						headers: {
							'content-type': 'text/plain;profile="https://www.mediawiki.org/wiki/Specs/wikitext/1.0.0"',
						},
						body: "== h2 ==",
					},
				},
			})
			.expect(validHtmlResponse(function(doc) {
				validateDoc(doc, 'H2', true);
			}))
			.end(done);
		});

		it('should use the proper source text', function(done) {
			if (skipForNow) { return this.skip(); }  // Missing template 1x
			client.req
			.post(mockDomain + `/v3/transform/wikitext/to/html/${page}/${revid}`)
			.send({
				original: {
					wikitext: {
						headers: {
							'content-type': 'text/plain;profile="https://www.mediawiki.org/wiki/Specs/wikitext/1.0.0"',
						},
						body: "{{1x|foo|bar=bat}}",
					},
				},
			})
			.expect(validHtmlResponse(function(doc) {
				validateDoc(doc, 'P', false);
				var span = doc.querySelector('span[typeof="mw:Transclusion"]');
				var dmw = JSON.parse(span.getAttribute('data-mw'));
				var template = dmw.parts[0].template;
				template.target.wt.should.equal('1x');
				template.params[1].wt.should.equal('foo');
				template.params.bar.wt.should.equal('bat');
			}))
			.end(done);
		});

		it('should accept the wikitext source as original without a title or revision', function(done) {
			client.req
			.post(mockDomain + '/v3/transform/wikitext/to/html/')
			.send({
				original: {
					wikitext: {
						headers: {
							'content-type': 'text/plain;profile="https://www.mediawiki.org/wiki/Specs/wikitext/1.0.0"',
						},
						body: "== h2 ==",
					},
				},
			})
			.expect(validHtmlResponse(function(doc) {
				validateDoc(doc, 'H2', true);
			}))
			.end(done);
		});

		it("should respect body parameter in wikitext->html (body_only)", function(done) {
			client.req
			.post(mockDomain + '/v3/transform/wikitext/to/html/')
			.send({
				wikitext: "''foo''",
				body_only: 1,
			})
			.expect(validHtmlResponse())
			.expect(function(res) {
				// v3 only returns children of <body>
				res.text.should.not.match(/<body/);
				res.text.should.match(/<p/);
			})
			.end(done);
		});

		it("should respect body parameter in wikitext->pagebundle requests (body_only)", function(done) {
			client.req
			.post(mockDomain + '/v3/transform/wikitext/to/pagebundle/')
			.send({
				wikitext: "''foo''",
				body_only: 1,
			})
			.expect(validPageBundleResponse())
			.expect(function(res) {
				// v3 only returns children of <body>
				res.body.html.body.should.not.match(/<body/);
				res.body.html.body.should.match(/<p/);
				// No section wrapping in body-only mode
				res.body.html.body.should.not.match(/<section/);
			})
			.end(done);
		});

		it('should not include captured offsets', function(done) {
			client.req
			.get(mockDomain + `/v3/page/pagebundle/${page}/${revid}`)
			.expect(validPageBundleResponse(function(doc, dp) {
				dp.should.not.have.property('sectionOffsets');
			}))
			.end(done);
		});

		it('should return a request too large error (post wt)', function(done) {
			if (skipForNow) { return this.skip(); }  // Set limits in config
			client.req
			.post(mockDomain + '/v3/transform/wikitext/to/pagebundle/')
			.send({
				original: {
					title: 'Large_Page',
				},
				wikitext: "a".repeat(parsoidOptions.limits.wt2html.maxWikitextSize + 1),
			})
			.expect(413)
			.end(done);
		});

		it('should return a request too large error (get page)', function(done) {
			if (skipForNow) { return this.skip(); }  // Set limits in config
			client.req
			.get(mockDomain + '/v3/page/html/Large_Page/3')
			.expect(413)
			.end(done);
		});

		it('should add redlinks for get (html)', function(done) {
			if (skipForNow) { return this.skip(); }  // Create Relinks_Page
			client.req
			.get(mockDomain + '/v3/page/html/Redlinks_Page/103')
			.expect(validHtmlResponse(function(doc) {
				doc.body.querySelectorAll('a').length.should.equal(3);
				var redLinks = doc.body.querySelectorAll('.new');
				redLinks.length.should.equal(1);
				redLinks[0].getAttribute('title').should.equal('Doesnotexist');
				var redirects = doc.body.querySelectorAll('.mw-redirect');
				redirects.length.should.equal(1);
				redirects[0].getAttribute('title').should.equal('Redirected');
			}))
			.end(done);
		});

		it('should add redlinks for get (pagebundle)', function(done) {
			if (skipForNow) { return this.skip(); }  // Create Redlinks_Page
			client.req
			.get(mockDomain + '/v3/page/pagebundle/Redlinks_Page/103')
			.expect(validPageBundleResponse(function(doc) {
				doc.body.querySelectorAll('a').length.should.equal(3);
				var redLinks = doc.body.querySelectorAll('.new');
				redLinks.length.should.equal(1);
				redLinks[0].getAttribute('title').should.equal('Doesnotexist');
				var redirects = doc.body.querySelectorAll('.mw-redirect');
				redirects.length.should.equal(1);
				redirects[0].getAttribute('title').should.equal('Redirected');
			}))
			.end(done);
		});

		it('should add redlinks for transform (html)', function(done) {
			if (skipForNow) { return this.skip(); }  // Fix redlinks count, by creating pages
			client.req
			.post(mockDomain + '/v3/transform/wikitext/to/html/')
			.send({
				wikitext: "[[Special:Version]] [[Doesnotexist]] [[Redirected]]",
			})
			.expect(validHtmlResponse(function(doc) {
				doc.body.querySelectorAll('a').length.should.equal(3);
				var redLinks = doc.body.querySelectorAll('.new');
				redLinks.length.should.equal(1);
				redLinks[0].getAttribute('title').should.equal('Doesnotexist');
				var redirects = doc.body.querySelectorAll('.mw-redirect');
				redirects.length.should.equal(1);
				redirects[0].getAttribute('title').should.equal('Redirected');
			}))
			.end(done);
		});

		it('should add redlinks for transform (pagebundle)', function(done) {
			if (skipForNow) { return this.skip(); }  // Fix redlinks count, by creating pages
			client.req
			.post(mockDomain + '/v3/transform/wikitext/to/pagebundle/')
			.send({
				wikitext: "[[Special:Version]] [[Doesnotexist]] [[Redirected]]",
			})
			.expect(validPageBundleResponse(function(doc) {
				doc.body.querySelectorAll('a').length.should.equal(3);
				var redLinks = doc.body.querySelectorAll('.new');
				redLinks.length.should.equal(1);
				redLinks[0].getAttribute('title').should.equal('Doesnotexist');
				var redirects = doc.body.querySelectorAll('.mw-redirect');
				redirects.length.should.equal(1);
				redirects[0].getAttribute('title').should.equal('Redirected');
			}))
			.end(done);
		});

		(skipForNow ? describe.skip : describe)('Variant conversion', function() {

			it('should not perform unnecessary variant conversion for get of en page (html)', function(done) {
				client.req
				.get(mockDomain + `/v3/page/html/${page}/${revid}`)
				.set('Accept-Language', 'sr-el')
				.expect(validHtmlResponse())
				.expect('Content-Language', 'en')
				.expect((res) => {
					const vary = res.headers.vary || '';
					vary.should.not.match(/\bAccept-Language\b/i);
				})
				.end(done);
			});

			it('should not perform unnecessary variant conversion for get of en page (pagebundle)', function(done) {
				client.req
				.get(mockDomain + `/v3/page/pagebundle/${page}/${revid}`)
				.set('Accept-Language', 'sr-el')
				.expect(validPageBundleResponse())
				.expect((res) => {
					// HTTP headers should not be set.
					const vary1 = res.headers.vary || '';
					vary1.should.not.match(/\bAccept-Language\b/i);
					const lang1 = res.headers['content-language'] || '';
					lang1.should.equal('');
					// But equivalent headers should be present in the JSON body.
					const headers = res.body.html.headers;
					const vary2 = headers.vary || '';
					vary2.should.not.match(/\bAccept-Language\b/i);
					const lang2 = headers['content-language'];
					lang2.should.equal('en');
				})
				.end(done);
			});

			it('should not perform unnecessary variant conversion for get on page w/ magic word (html)', function(done) {
				client.req
				.get(mockDomain + '/v3/page/html/No_Variant_Page/105')
				.set('Accept-Language', 'sr-el')
				.expect(validHtmlResponse((doc) => {
					// No conversion done since __NOCONTENTCONVERT__ is set
					doc.body.textContent.should.equal('абвг abcd\n');
				}))
				// But the vary/language headers are still set.
				.expect('Content-Language', 'sr-el')
				.expect('Vary', /\bAccept-Language\b/i)
				.end(done);
			});

			it('should not perform unnecessary variant conversion for get on page w/ magic word (pagebundle)', function(done) {
				client.req
				.get(mockDomain + '/v3/page/pagebundle/No_Variant_Page/105')
				.set('Accept-Language', 'sr-el')
				.expect(validPageBundleResponse((doc) => {
					// No conversion done since __NOCONTENTCONVERT__ is set
					doc.body.textContent.should.equal('абвг abcd\n');
				}))
				.expect((res) => {
					// HTTP headers should not be set.
					const vary1 = res.headers.vary || '';
					vary1.should.not.match(/\bAccept-Language\b/i);
					const lang1 = res.headers['content-language'] || '';
					lang1.should.equal('');
					// But vary/language headers should be set in JSON body.
					const headers = res.body.html.headers;
					const vary2 = headers.vary || '';
					vary2.should.match(/\bAccept-Language\b/i);
					const lang2 = headers['content-language'];
					lang2.should.equal('sr-el');
				})
				.end(done);
			});

			it('should not perform unrequested variant conversion for get w/ no accept-language header (html)', function(done) {
				client.req
				.get(mockDomain + '/v3/page/html/Variant_Page/104')
				// no accept-language header sent
				.expect('Content-Language', 'sr')
				.expect('Vary', /\bAccept-Language\b/i)
				.expect(validHtmlResponse((doc) => {
					doc.body.textContent.should.equal('абвг abcd');
				}))
				.end(done);
			});

			it('should not perform unrequested variant conversion for get w/ no accept-language header (pagebundle)', function(done) {
				client.req
				.get(mockDomain + '/v3/page/pagebundle/Variant_Page/104')
				// no accept-language header sent
				.expect(validPageBundleResponse((doc) => {
					doc.body.textContent.should.equal('абвг abcd');
				}))
				.expect((res) => {
					// HTTP headers should not be set.
					const vary1 = res.headers.vary || '';
					vary1.should.not.match(/\bAccept-Language\b/i);
					const lang1 = res.headers['content-language'] || '';
					lang1.should.equal('');
					// But vary/language headers should be set in JSON body.
					const headers = res.body.html.headers;
					const vary2 = headers.vary || '';
					vary2.should.match(/\bAccept-Language\b/i);
					const lang2 = headers['content-language'];
					lang2.should.equal('sr');
				})
				.end(done);
			});

			it('should not perform variant conversion for get w/ base variant specified (html)', function(done) {
				client.req
				.get(mockDomain + '/v3/page/html/Variant_Page/104')
				.set('Accept-Language', 'sr') // this is base variant
				.expect('Content-Language', 'sr')
				.expect('Vary', /\bAccept-Language\b/i)
				.expect(validHtmlResponse((doc) => {
					doc.body.textContent.should.equal('абвг abcd');
				}))
				.end(done);
			});

			it('should not perform variant conversion for get w/ base variant specified (pagebundle)', function(done) {
				client.req
				.get(mockDomain + '/v3/page/pagebundle/Variant_Page/104')
				.set('Accept-Language', 'sr') // this is base variant
				.expect(validPageBundleResponse((doc) => {
					doc.body.textContent.should.equal('абвг abcd');
				}))
				.expect((res) => {
					// HTTP headers should not be set.
					const vary1 = res.headers.vary || '';
					vary1.should.not.match(/\bAccept-Language\b/i);
					const lang1 = res.headers['content-language'] || '';
					lang1.should.equal('');
					// But vary/language headers should be set in JSON body.
					const headers = res.body.html.headers;
					const vary2 = headers.vary || '';
					vary2.should.match(/\bAccept-Language\b/i);
					const lang2 = headers['content-language'];
					lang2.should.equal('sr');
				})
				.end(done);
			});

			it('should not perform variant conversion for get w/ invalid variant specified (html)', function(done) {
				client.req
				.get(mockDomain + '/v3/page/html/Variant_Page/104')
				.set('Accept-Language', 'sr-BOGUS') // this doesn't exist
				.expect('Content-Language', 'sr')
				.expect('Vary', /\bAccept-Language\b/i)
				.expect(validHtmlResponse((doc) => {
					doc.body.textContent.should.equal('абвг abcd');
				}))
				.end(done);
			});

			it('should not perform variant conversion for get w/ invalid variant specified (pagebundle)', function(done) {
				client.req
				.get(mockDomain + '/v3/page/pagebundle/Variant_Page/104')
				.set('Accept-Language', 'sr-BOGUS') // this doesn't exist
				.expect(validPageBundleResponse((doc) => {
					doc.body.textContent.should.equal('абвг abcd');
				}))
				.expect((res) => {
					// HTTP headers should not be set.
					const vary1 = res.headers.vary || '';
					vary1.should.not.match(/\bAccept-Language\b/i);
					const lang1 = res.headers['content-language'] || '';
					lang1.should.equal('');
					// But vary/language headers should be set in JSON body.
					const headers = res.body.html.headers;
					const vary2 = headers.vary || '';
					vary2.should.match(/\bAccept-Language\b/i);
					const lang2 = headers['content-language'];
					lang2.should.equal('sr');
				})
				.end(done);
			});

			it('should perform variant conversion for get (html)', function(done) {
				client.req
				.get(mockDomain + '/v3/page/html/Variant_Page/104')
				.set('Accept-Language', 'sr-el')
				.expect('Content-Language', 'sr-el')
				.expect('Vary', /\bAccept-Language\b/i)
				.expect(validHtmlResponse((doc) => {
					doc.body.textContent.should.equal('abvg abcd');
				}))
				.end(done);
			});

			it('should perform variant conversion for get (pagebundle)', function(done) {
				client.req
				.get(mockDomain + '/v3/page/pagebundle/Variant_Page/104')
				.set('Accept-Language', 'sr-el')
				.expect(validPageBundleResponse((doc) => {
					doc.body.textContent.should.equal('abvg abcd');
				}))
				.expect((res) => {
					const headers = res.body.html.headers;
					headers.should.have.property('content-language');
					headers.should.have.property('vary');
					headers['content-language'].should.equal('sr-el');
					headers.vary.should.match(/\bAccept-Language\b/i);
				})
				.end(done);
			});

			it('should perform variant conversion for transform given pagelanguage in HTTP header (html)', function(done) {
				client.req
				.post(mockDomain + '/v3/transform/wikitext/to/html/')
				.set('Accept-Language', 'sr-el')
				.set('Content-Language', 'sr')
				.send({
					wikitext: "абвг abcd x",
				})
				.expect('Content-Language', 'sr-el')
				.expect('Vary', /\bAccept-Language\b/i)
				.expect(validHtmlResponse((doc) => {
					doc.body.textContent.should.equal('abvg abcd x');
				}))
				.end(done);
			});

			it('should perform variant conversion for transform given pagelanguage in HTTP header (pagebundle)', function(done) {
				client.req
				.post(mockDomain + '/v3/transform/wikitext/to/pagebundle/')
				.set('Accept-Language', 'sr-el')
				.set('Content-Language', 'sr')
				.send({
					wikitext: "абвг abcd x",
				})
				.expect(validPageBundleResponse((doc) => {
					doc.body.textContent.should.equal('abvg abcd x');
				}))
				.expect((res) => {
					const headers = res.body.html.headers;
					headers.should.have.property('content-language');
					headers.should.have.property('vary');
					headers['content-language'].should.equal('sr-el');
					headers.vary.should.match(/\bAccept-Language\b/i);
				})
				.end(done);
			});

			it('should perform variant conversion for transform given pagelanguage in JSON header (html)', function(done) {
				client.req
				.post(mockDomain + '/v3/transform/wikitext/to/html/')
				.set('Accept-Language', 'sr-el')
				.send({
					wikitext: {
						headers: {
							'content-language': 'sr',
						},
						body: "абвг abcd x",
					},
				})
				.expect('Content-Language', 'sr-el')
				.expect('Vary', /\bAccept-Language\b/i)
				.expect(validHtmlResponse((doc) => {
					doc.body.textContent.should.equal('abvg abcd x');
				}))
				.end(done);
			});

			it('should perform variant conversion for transform given pagelanguage in JSON header (pagebundle)', function(done) {
				client.req
				.post(mockDomain + '/v3/transform/wikitext/to/pagebundle/')
				.set('Accept-Language', 'sr-el')
				.send({
					wikitext: {
						headers: {
							'content-language': 'sr',
						},
						body: "абвг abcd",
					},
				})
				.expect(validPageBundleResponse((doc) => {
					doc.body.textContent.should.equal('abvg abcd');
				}))
				.expect((res) => {
					const headers = res.body.html.headers;
					headers.should.have.property('content-language');
					headers.should.have.property('vary');
					headers['content-language'].should.equal('sr-el');
					headers.vary.should.match(/\bAccept-Language\b/i);
				})
				.end(done);
			});

			it('should perform variant conversion for transform given pagelanguage from oldid (html)', function(done) {
				client.req
				.post(mockDomain + '/v3/transform/wikitext/to/html/')
				.set('Accept-Language', 'sr-el')
				.send({
					original: { revid: 104 },
					wikitext: {
						body: "абвг abcd x",
					},
				})
				.expect('Content-Language', 'sr-el')
				.expect('Vary', /\bAccept-Language\b/i)
				.expect(validHtmlResponse((doc) => {
					doc.body.textContent.should.equal('abvg abcd x');
				}))
				.end(done);
			});

			it('should perform variant conversion for transform given pagelanguage from oldid (pagebundle)', function(done) {
				client.req
				.post(mockDomain + '/v3/transform/wikitext/to/pagebundle/')
				.set('Accept-Language', 'sr-el')
				.send({
					original: { revid: 104 },
					wikitext: "абвг abcd",
				})
				.expect(validPageBundleResponse((doc) => {
					doc.body.textContent.should.equal('abvg abcd');
				}))
				.expect((res) => {
					const headers = res.body.html.headers;
					headers.should.have.property('content-language');
					headers.should.have.property('vary');
					headers['content-language'].should.equal('sr-el');
					headers.vary.should.match(/\bAccept-Language\b/i);
				})
				.end(done);
			});

		});

	}); // end wt2html

	describe("html2wt", function() {

		it('should require html when serializing', function(done) {
			client.req
			.post(mockDomain + '/v3/transform/html/to/wikitext/')
			.send({})
			.expect(400)
			.end(done);
		});

		it('should error when revision not found (transform, html2wt)', function(done) {
			client.req
			.post(mockDomain + '/v3/transform/html/to/wikitext/Doesnotexist/1122334455')
			.send({
				html: '<pre>hi ho</pre>'
			})
			.expect(404)
			.end(done);
		});

		it('should error when if-match doesn\'t match', function(done) {
			client.req
				.post(mockDomain + '/v3/transform/html/to/wikitext/')
				.set( 'if-match', '"1234/deadbeef"' )
				.send({
					html: '<pre>hi ho</pre>'
				})
				.expect(412)
				.end(done);
		});

		it('should not error when oldid not supplied (transform, html2wt)', function(done) {
			client.req
			.post(mockDomain + '/v3/transform/html/to/wikitext/Doesnotexist')
			.send({
				html: '<pre>hi ho</pre>'
			})
			.expect(validWikitextResponse(' hi ho\n'))
			.end(done);
		});

		it('should accept html as a string', function(done) {
			client.req
			.post(mockDomain + '/v3/transform/html/to/wikitext/')
			.send({
				html: '<!DOCTYPE html>\n<html prefix="dc: http://purl.org/dc/terms/ mw: http://mediawiki.org/rdf/" about="http://localhost/index.php/Special:Redirect/revision/1"><head prefix="mwr: http://localhost/index.php/Special:Redirect/"><meta property="mw:articleNamespace" content="0"/><link rel="dc:replaces" resource="mwr:revision/0"/><meta property="dc:modified" content="2014-09-12T22:46:59.000Z"/><meta about="mwr:user/0" property="dc:title" content="MediaWiki default"/><link rel="dc:contributor" resource="mwr:user/0"/><meta property="mw:revisionSHA1" content="8e0aa2f2a7829587801db67d0424d9b447e09867"/><meta property="dc:description" content=""/><link rel="dc:isVersionOf" href="http://localhost/index.php/Main_Page"/><title>Main_Page</title><base href="http://localhost/index.php/"/><link rel="stylesheet" href="//localhost/load.php?modules=mediawiki.legacy.commonPrint,shared|mediawiki.skinning.elements|mediawiki.skinning.content|mediawiki.skinning.interface|skins.vector.styles|site|mediawiki.skinning.content.parsoid&amp;only=styles&amp;debug=true&amp;skin=vector"/></head><body data-parsoid=\'{"dsr":[0,592,0,0]}\' lang="en" class="mw-content-ltr sitedir-ltr ltr mw-body mw-body-content mediawiki" dir="ltr"><p data-parsoid=\'{"dsr":[0,59,0,0]}\'><strong data-parsoid=\'{"stx":"html","dsr":[0,59,8,9]}\'>MediaWiki has been successfully installed.</strong></p>\n\n<p data-parsoid=\'{"dsr":[61,171,0,0]}\'>Consult the <a rel="mw:ExtLink" href="//meta.wikimedia.org/wiki/Help:Contents" data-parsoid=\'{"dsr":[73,127,41,1]}\'>User\'s Guide</a> for information on using the wiki software.</p>\n\n<h2 data-parsoid=\'{"dsr":[173,194,2,2]}\'> Getting started </h2>\n<ul data-parsoid=\'{"dsr":[195,592,0,0]}\'><li data-parsoid=\'{"dsr":[195,300,1,0]}\'> <a rel="mw:ExtLink" href="//www.mediawiki.org/wiki/Special:MyLanguage/Manual:Configuration_settings" data-parsoid=\'{"dsr":[197,300,75,1]}\'>Configuration settings list</a></li>\n<li data-parsoid=\'{"dsr":[301,373,1,0]}\'> <a rel="mw:ExtLink" href="//www.mediawiki.org/wiki/Special:MyLanguage/Manual:FAQ" data-parsoid=\'{"dsr":[303,373,56,1]}\'>MediaWiki FAQ</a></li>\n<li data-parsoid=\'{"dsr":[374,472,1,0]}\'> <a rel="mw:ExtLink" href="https://lists.wikimedia.org/mailman/listinfo/mediawiki-announce" data-parsoid=\'{"dsr":[376,472,65,1]}\'>MediaWiki release mailing list</a></li>\n<li data-parsoid=\'{"dsr":[473,592,1,0]}\'> <a rel="mw:ExtLink" href="//www.mediawiki.org/wiki/Special:MyLanguage/Localisation#Translation_resources" data-parsoid=\'{"dsr":[475,592,80,1]}\'>Localise MediaWiki for your language</a></li></ul></body></html>',
			})
			.expect(validWikitextResponse())
			.end(done);
		});

		it('should accept html for json contentmodel as a string', function(done) {
			client.req
			.post(mockDomain + '/v3/transform/html/to/wikitext/')
			.send({
				html: '<!DOCTYPE html>\n<html prefix="dc: http://purl.org/dc/terms/ mw: http://mediawiki.org/rdf/"><head prefix="mwr: http://en.wikipedia.org/wiki/Special:Redirect/"><meta charset="utf-8"/><meta property="mw:articleNamespace" content="0"/><link rel="dc:isVersionOf" href="//en.wikipedia.org/wiki/Main_Page"/><title></title><base href="//en.wikipedia.org/wiki/"/><link rel="stylesheet" href="//en.wikipedia.org/w/load.php?modules=mediawiki.legacy.commonPrint,shared|mediawiki.skinning.elements|mediawiki.skinning.content|mediawiki.skinning.interface|skins.vector.styles|site|mediawiki.skinning.content.parsoid|ext.cite.style&amp;only=styles&amp;skin=vector"/></head><body lang="en" class="mw-content-ltr sitedir-ltr ltr mw-body mw-body-content mediawiki" dir="ltr"><table class="mw-json mw-json-object"><tbody><tr><th>a</th><td class="value mw-json-number">4</td></tr><tr><th>b</th><td class="value mw-json-number">3</td></tr></tbody></table></body></html>',
				contentmodel: 'json',
			})
			.expect(validJsonResponse('{"a":4,"b":3}'))
			.end(done);
		});

		it('should accept html with headers', function(done) {
			client.req
			.post(mockDomain + '/v3/transform/html/to/wikitext/')
			.send({
				html: {
					headers: {
						'content-type': 'text/html;profile="https://www.mediawiki.org/wiki/Specs/HTML/' + defaultContentVersion + '"',
					},
					body: '<!DOCTYPE html>\n<html prefix="dc: http://purl.org/dc/terms/ mw: http://mediawiki.org/rdf/" about="http://localhost/index.php/Special:Redirect/revision/1"><head prefix="mwr: http://localhost/index.php/Special:Redirect/"><meta property="mw:articleNamespace" content="0"/><link rel="dc:replaces" resource="mwr:revision/0"/><meta property="dc:modified" content="2014-09-12T22:46:59.000Z"/><meta about="mwr:user/0" property="dc:title" content="MediaWiki default"/><link rel="dc:contributor" resource="mwr:user/0"/><meta property="mw:revisionSHA1" content="8e0aa2f2a7829587801db67d0424d9b447e09867"/><meta property="dc:description" content=""/><link rel="dc:isVersionOf" href="http://localhost/index.php/Main_Page"/><title>Main_Page</title><base href="http://localhost/index.php/"/><link rel="stylesheet" href="//localhost/load.php?modules=mediawiki.legacy.commonPrint,shared|mediawiki.skinning.elements|mediawiki.skinning.content|mediawiki.skinning.interface|skins.vector.styles|site|mediawiki.skinning.content.parsoid&amp;only=styles&amp;debug=true&amp;skin=vector"/></head><body data-parsoid=\'{"dsr":[0,592,0,0]}\' lang="en" class="mw-content-ltr sitedir-ltr ltr mw-body mw-body-content mediawiki" dir="ltr"><p data-parsoid=\'{"dsr":[0,59,0,0]}\'><strong data-parsoid=\'{"stx":"html","dsr":[0,59,8,9]}\'>MediaWiki has been successfully installed.</strong></p>\n\n<p data-parsoid=\'{"dsr":[61,171,0,0]}\'>Consult the <a rel="mw:ExtLink" href="//meta.wikimedia.org/wiki/Help:Contents" data-parsoid=\'{"dsr":[73,127,41,1]}\'>User\'s Guide</a> for information on using the wiki software.</p>\n\n<h2 data-parsoid=\'{"dsr":[173,194,2,2]}\'> Getting started </h2>\n<ul data-parsoid=\'{"dsr":[195,592,0,0]}\'><li data-parsoid=\'{"dsr":[195,300,1,0]}\'> <a rel="mw:ExtLink" href="//www.mediawiki.org/wiki/Special:MyLanguage/Manual:Configuration_settings" data-parsoid=\'{"dsr":[197,300,75,1]}\'>Configuration settings list</a></li>\n<li data-parsoid=\'{"dsr":[301,373,1,0]}\'> <a rel="mw:ExtLink" href="//www.mediawiki.org/wiki/Special:MyLanguage/Manual:FAQ" data-parsoid=\'{"dsr":[303,373,56,1]}\'>MediaWiki FAQ</a></li>\n<li data-parsoid=\'{"dsr":[374,472,1,0]}\'> <a rel="mw:ExtLink" href="https://lists.wikimedia.org/mailman/listinfo/mediawiki-announce" data-parsoid=\'{"dsr":[376,472,65,1]}\'>MediaWiki release mailing list</a></li>\n<li data-parsoid=\'{"dsr":[473,592,1,0]}\'> <a rel="mw:ExtLink" href="//www.mediawiki.org/wiki/Special:MyLanguage/Localisation#Translation_resources" data-parsoid=\'{"dsr":[475,592,80,1]}\'>Localise MediaWiki for your language</a></li></ul></body></html>',
				},
			})
			.expect(validWikitextResponse())
			.end(done);
		});

		it('should allow a title in the url', function(done) {
			client.req
			.post(mockDomain + '/v3/transform/html/to/wikitext/Main_Page')
			.send({
				html: '<!DOCTYPE html>\n<html prefix="dc: http://purl.org/dc/terms/ mw: http://mediawiki.org/rdf/" about="http://localhost/index.php/Special:Redirect/revision/1"><head prefix="mwr: http://localhost/index.php/Special:Redirect/"><meta property="mw:articleNamespace" content="0"/><link rel="dc:replaces" resource="mwr:revision/0"/><meta property="dc:modified" content="2014-09-12T22:46:59.000Z"/><meta about="mwr:user/0" property="dc:title" content="MediaWiki default"/><link rel="dc:contributor" resource="mwr:user/0"/><meta property="mw:revisionSHA1" content="8e0aa2f2a7829587801db67d0424d9b447e09867"/><meta property="dc:description" content=""/><link rel="dc:isVersionOf" href="http://localhost/index.php/Main_Page"/><title>Main_Page</title><base href="http://localhost/index.php/"/><link rel="stylesheet" href="//localhost/load.php?modules=mediawiki.legacy.commonPrint,shared|mediawiki.skinning.elements|mediawiki.skinning.content|mediawiki.skinning.interface|skins.vector.styles|site|mediawiki.skinning.content.parsoid&amp;only=styles&amp;debug=true&amp;skin=vector"/></head><body data-parsoid=\'{"dsr":[0,592,0,0]}\' lang="en" class="mw-content-ltr sitedir-ltr ltr mw-body mw-body-content mediawiki" dir="ltr"><p data-parsoid=\'{"dsr":[0,59,0,0]}\'><strong data-parsoid=\'{"stx":"html","dsr":[0,59,8,9]}\'>MediaWiki has been successfully installed.</strong></p>\n\n<p data-parsoid=\'{"dsr":[61,171,0,0]}\'>Consult the <a rel="mw:ExtLink" href="//meta.wikimedia.org/wiki/Help:Contents" data-parsoid=\'{"dsr":[73,127,41,1]}\'>User\'s Guide</a> for information on using the wiki software.</p>\n\n<h2 data-parsoid=\'{"dsr":[173,194,2,2]}\'> Getting started </h2>\n<ul data-parsoid=\'{"dsr":[195,592,0,0]}\'><li data-parsoid=\'{"dsr":[195,300,1,0]}\'> <a rel="mw:ExtLink" href="//www.mediawiki.org/wiki/Special:MyLanguage/Manual:Configuration_settings" data-parsoid=\'{"dsr":[197,300,75,1]}\'>Configuration settings list</a></li>\n<li data-parsoid=\'{"dsr":[301,373,1,0]}\'> <a rel="mw:ExtLink" href="//www.mediawiki.org/wiki/Special:MyLanguage/Manual:FAQ" data-parsoid=\'{"dsr":[303,373,56,1]}\'>MediaWiki FAQ</a></li>\n<li data-parsoid=\'{"dsr":[374,472,1,0]}\'> <a rel="mw:ExtLink" href="https://lists.wikimedia.org/mailman/listinfo/mediawiki-announce" data-parsoid=\'{"dsr":[376,472,65,1]}\'>MediaWiki release mailing list</a></li>\n<li data-parsoid=\'{"dsr":[473,592,1,0]}\'> <a rel="mw:ExtLink" href="//www.mediawiki.org/wiki/Special:MyLanguage/Localisation#Translation_resources" data-parsoid=\'{"dsr":[475,592,80,1]}\'>Localise MediaWiki for your language</a></li></ul></body></html>',
			})
			.expect(validWikitextResponse())
			.end(done);
		});

		it('should allow a title in the original data', function(done) {
			client.req
			.post(mockDomain + '/v3/transform/html/to/wikitext/')
			.send({
				html: '<!DOCTYPE html>\n<html prefix="dc: http://purl.org/dc/terms/ mw: http://mediawiki.org/rdf/" about="http://localhost/index.php/Special:Redirect/revision/1"><head prefix="mwr: http://localhost/index.php/Special:Redirect/"><meta property="mw:articleNamespace" content="0"/><link rel="dc:replaces" resource="mwr:revision/0"/><meta property="dc:modified" content="2014-09-12T22:46:59.000Z"/><meta about="mwr:user/0" property="dc:title" content="MediaWiki default"/><link rel="dc:contributor" resource="mwr:user/0"/><meta property="mw:revisionSHA1" content="8e0aa2f2a7829587801db67d0424d9b447e09867"/><meta property="dc:description" content=""/><link rel="dc:isVersionOf" href="http://localhost/index.php/Main_Page"/><title>Main_Page</title><base href="http://localhost/index.php/"/><link rel="stylesheet" href="//localhost/load.php?modules=mediawiki.legacy.commonPrint,shared|mediawiki.skinning.elements|mediawiki.skinning.content|mediawiki.skinning.interface|skins.vector.styles|site|mediawiki.skinning.content.parsoid&amp;only=styles&amp;debug=true&amp;skin=vector"/></head><body data-parsoid=\'{"dsr":[0,592,0,0]}\' lang="en" class="mw-content-ltr sitedir-ltr ltr mw-body mw-body-content mediawiki" dir="ltr"><p data-parsoid=\'{"dsr":[0,59,0,0]}\'><strong data-parsoid=\'{"stx":"html","dsr":[0,59,8,9]}\'>MediaWiki has been successfully installed.</strong></p>\n\n<p data-parsoid=\'{"dsr":[61,171,0,0]}\'>Consult the <a rel="mw:ExtLink" href="//meta.wikimedia.org/wiki/Help:Contents" data-parsoid=\'{"dsr":[73,127,41,1]}\'>User\'s Guide</a> for information on using the wiki software.</p>\n\n<h2 data-parsoid=\'{"dsr":[173,194,2,2]}\'> Getting started </h2>\n<ul data-parsoid=\'{"dsr":[195,592,0,0]}\'><li data-parsoid=\'{"dsr":[195,300,1,0]}\'> <a rel="mw:ExtLink" href="//www.mediawiki.org/wiki/Special:MyLanguage/Manual:Configuration_settings" data-parsoid=\'{"dsr":[197,300,75,1]}\'>Configuration settings list</a></li>\n<li data-parsoid=\'{"dsr":[301,373,1,0]}\'> <a rel="mw:ExtLink" href="//www.mediawiki.org/wiki/Special:MyLanguage/Manual:FAQ" data-parsoid=\'{"dsr":[303,373,56,1]}\'>MediaWiki FAQ</a></li>\n<li data-parsoid=\'{"dsr":[374,472,1,0]}\'> <a rel="mw:ExtLink" href="https://lists.wikimedia.org/mailman/listinfo/mediawiki-announce" data-parsoid=\'{"dsr":[376,472,65,1]}\'>MediaWiki release mailing list</a></li>\n<li data-parsoid=\'{"dsr":[473,592,1,0]}\'> <a rel="mw:ExtLink" href="//www.mediawiki.org/wiki/Special:MyLanguage/Localisation#Translation_resources" data-parsoid=\'{"dsr":[475,592,80,1]}\'>Localise MediaWiki for your language</a></li></ul></body></html>',
				original: {
					title: "Main_Page",
				},
			})
			.expect(validWikitextResponse())
			.end(done);
		});

		it('should allow a revision id in the url', function(done) {
			client.req
			.post(mockDomain + `/v3/transform/html/to/wikitext/${page}/${revid}`)
			.send({
				html: '<!DOCTYPE html>\n<html prefix="dc: http://purl.org/dc/terms/ mw: http://mediawiki.org/rdf/" about="http://localhost/index.php/Special:Redirect/revision/1"><head prefix="mwr: http://localhost/index.php/Special:Redirect/"><meta property="mw:articleNamespace" content="0"/><link rel="dc:replaces" resource="mwr:revision/0"/><meta property="dc:modified" content="2014-09-12T22:46:59.000Z"/><meta about="mwr:user/0" property="dc:title" content="MediaWiki default"/><link rel="dc:contributor" resource="mwr:user/0"/><meta property="mw:revisionSHA1" content="8e0aa2f2a7829587801db67d0424d9b447e09867"/><meta property="dc:description" content=""/><link rel="dc:isVersionOf" href="http://localhost/index.php/Main_Page"/><title>Main_Page</title><base href="http://localhost/index.php/"/><link rel="stylesheet" href="//localhost/load.php?modules=mediawiki.legacy.commonPrint,shared|mediawiki.skinning.elements|mediawiki.skinning.content|mediawiki.skinning.interface|skins.vector.styles|site|mediawiki.skinning.content.parsoid&amp;only=styles&amp;debug=true&amp;skin=vector"/></head><body data-parsoid=\'{"dsr":[0,592,0,0]}\' lang="en" class="mw-content-ltr sitedir-ltr ltr mw-body mw-body-content mediawiki" dir="ltr"><p data-parsoid=\'{"dsr":[0,59,0,0]}\'><strong data-parsoid=\'{"stx":"html","dsr":[0,59,8,9]}\'>MediaWiki has been successfully installed.</strong></p>\n\n<p data-parsoid=\'{"dsr":[61,171,0,0]}\'>Consult the <a rel="mw:ExtLink" href="//meta.wikimedia.org/wiki/Help:Contents" data-parsoid=\'{"dsr":[73,127,41,1]}\'>User\'s Guide</a> for information on using the wiki software.</p>\n\n<h2 data-parsoid=\'{"dsr":[173,194,2,2]}\'> Getting started </h2>\n<ul data-parsoid=\'{"dsr":[195,592,0,0]}\'><li data-parsoid=\'{"dsr":[195,300,1,0]}\'> <a rel="mw:ExtLink" href="//www.mediawiki.org/wiki/Special:MyLanguage/Manual:Configuration_settings" data-parsoid=\'{"dsr":[197,300,75,1]}\'>Configuration settings list</a></li>\n<li data-parsoid=\'{"dsr":[301,373,1,0]}\'> <a rel="mw:ExtLink" href="//www.mediawiki.org/wiki/Special:MyLanguage/Manual:FAQ" data-parsoid=\'{"dsr":[303,373,56,1]}\'>MediaWiki FAQ</a></li>\n<li data-parsoid=\'{"dsr":[374,472,1,0]}\'> <a rel="mw:ExtLink" href="https://lists.wikimedia.org/mailman/listinfo/mediawiki-announce" data-parsoid=\'{"dsr":[376,472,65,1]}\'>MediaWiki release mailing list</a></li>\n<li data-parsoid=\'{"dsr":[473,592,1,0]}\'> <a rel="mw:ExtLink" href="//www.mediawiki.org/wiki/Special:MyLanguage/Localisation#Translation_resources" data-parsoid=\'{"dsr":[475,592,80,1]}\'>Localise MediaWiki for your language</a></li></ul></body></html>',
			})
			.expect(validWikitextResponse())
			.end(done);
		});

		it('should allow a revision id in the original data', function(done) {
			client.req
			.post(mockDomain + '/v3/transform/html/to/wikitext/')
			.send({
				html: '<!DOCTYPE html>\n<html prefix="dc: http://purl.org/dc/terms/ mw: http://mediawiki.org/rdf/" about="http://localhost/index.php/Special:Redirect/revision/1"><head prefix="mwr: http://localhost/index.php/Special:Redirect/"><meta property="mw:articleNamespace" content="0"/><link rel="dc:replaces" resource="mwr:revision/0"/><meta property="dc:modified" content="2014-09-12T22:46:59.000Z"/><meta about="mwr:user/0" property="dc:title" content="MediaWiki default"/><link rel="dc:contributor" resource="mwr:user/0"/><meta property="mw:revisionSHA1" content="8e0aa2f2a7829587801db67d0424d9b447e09867"/><meta property="dc:description" content=""/><link rel="dc:isVersionOf" href="http://localhost/index.php/Main_Page"/><title>Main_Page</title><base href="http://localhost/index.php/"/><link rel="stylesheet" href="//localhost/load.php?modules=mediawiki.legacy.commonPrint,shared|mediawiki.skinning.elements|mediawiki.skinning.content|mediawiki.skinning.interface|skins.vector.styles|site|mediawiki.skinning.content.parsoid&amp;only=styles&amp;debug=true&amp;skin=vector"/></head><body data-parsoid=\'{"dsr":[0,592,0,0]}\' lang="en" class="mw-content-ltr sitedir-ltr ltr mw-body mw-body-content mediawiki" dir="ltr"><p data-parsoid=\'{"dsr":[0,59,0,0]}\'><strong data-parsoid=\'{"stx":"html","dsr":[0,59,8,9]}\'>MediaWiki has been successfully installed.</strong></p>\n\n<p data-parsoid=\'{"dsr":[61,171,0,0]}\'>Consult the <a rel="mw:ExtLink" href="//meta.wikimedia.org/wiki/Help:Contents" data-parsoid=\'{"dsr":[73,127,41,1]}\'>User\'s Guide</a> for information on using the wiki software.</p>\n\n<h2 data-parsoid=\'{"dsr":[173,194,2,2]}\'> Getting started </h2>\n<ul data-parsoid=\'{"dsr":[195,592,0,0]}\'><li data-parsoid=\'{"dsr":[195,300,1,0]}\'> <a rel="mw:ExtLink" href="//www.mediawiki.org/wiki/Special:MyLanguage/Manual:Configuration_settings" data-parsoid=\'{"dsr":[197,300,75,1]}\'>Configuration settings list</a></li>\n<li data-parsoid=\'{"dsr":[301,373,1,0]}\'> <a rel="mw:ExtLink" href="//www.mediawiki.org/wiki/Special:MyLanguage/Manual:FAQ" data-parsoid=\'{"dsr":[303,373,56,1]}\'>MediaWiki FAQ</a></li>\n<li data-parsoid=\'{"dsr":[374,472,1,0]}\'> <a rel="mw:ExtLink" href="https://lists.wikimedia.org/mailman/listinfo/mediawiki-announce" data-parsoid=\'{"dsr":[376,472,65,1]}\'>MediaWiki release mailing list</a></li>\n<li data-parsoid=\'{"dsr":[473,592,1,0]}\'> <a rel="mw:ExtLink" href="//www.mediawiki.org/wiki/Special:MyLanguage/Localisation#Translation_resources" data-parsoid=\'{"dsr":[475,592,80,1]}\'>Localise MediaWiki for your language</a></li></ul></body></html>',
				original: {
					revid,
				},
			})
			.expect(validWikitextResponse())
			.end(done);
		});

		it('should accept original wikitext as src', function(done) {
			client.req
			.post(mockDomain + '/v3/transform/html/to/wikitext/')
			.send({
				html: '<!DOCTYPE html>\n<html prefix="dc: http://purl.org/dc/terms/ mw: http://mediawiki.org/rdf/" about="http://localhost/index.php/Special:Redirect/revision/1"><head prefix="mwr: http://localhost/index.php/Special:Redirect/"><meta property="mw:articleNamespace" content="0"/><link rel="dc:replaces" resource="mwr:revision/0"/><meta property="dc:modified" content="2014-09-12T22:46:59.000Z"/><meta about="mwr:user/0" property="dc:title" content="MediaWiki default"/><link rel="dc:contributor" resource="mwr:user/0"/><meta property="mw:revisionSHA1" content="8e0aa2f2a7829587801db67d0424d9b447e09867"/><meta property="dc:description" content=""/><link rel="dc:isVersionOf" href="http://localhost/index.php/Main_Page"/><title>Main_Page</title><base href="http://localhost/index.php/"/><link rel="stylesheet" href="//localhost/load.php?modules=mediawiki.legacy.commonPrint,shared|mediawiki.skinning.elements|mediawiki.skinning.content|mediawiki.skinning.interface|skins.vector.styles|site|mediawiki.skinning.content.parsoid&amp;only=styles&amp;debug=true&amp;skin=vector"/></head><body data-parsoid=\'{"dsr":[0,592,0,0]}\' lang="en" class="mw-content-ltr sitedir-ltr ltr mw-body mw-body-content mediawiki" dir="ltr"><p data-parsoid=\'{"dsr":[0,59,0,0]}\'><strong data-parsoid=\'{"stx":"html","dsr":[0,59,8,9]}\'>MediaWiki has been successfully installed.</strong></p>\n\n<p data-parsoid=\'{"dsr":[61,171,0,0]}\'>Consult the <a rel="mw:ExtLink" href="//meta.wikimedia.org/wiki/Help:Contents" data-parsoid=\'{"dsr":[73,127,41,1]}\'>User\'s Guide</a> for information on using the wiki software.</p>\n\n<h2 data-parsoid=\'{"dsr":[173,194,2,2]}\'> Getting started </h2>\n<ul data-parsoid=\'{"dsr":[195,592,0,0]}\'><li data-parsoid=\'{"dsr":[195,300,1,0]}\'> <a rel="mw:ExtLink" href="//www.mediawiki.org/wiki/Special:MyLanguage/Manual:Configuration_settings" data-parsoid=\'{"dsr":[197,300,75,1]}\'>Configuration settings list</a></li>\n<li data-parsoid=\'{"dsr":[301,373,1,0]}\'> <a rel="mw:ExtLink" href="//www.mediawiki.org/wiki/Special:MyLanguage/Manual:FAQ" data-parsoid=\'{"dsr":[303,373,56,1]}\'>MediaWiki FAQ</a></li>\n<li data-parsoid=\'{"dsr":[374,472,1,0]}\'> <a rel="mw:ExtLink" href="https://lists.wikimedia.org/mailman/listinfo/mediawiki-announce" data-parsoid=\'{"dsr":[376,472,65,1]}\'>MediaWiki release mailing list</a></li>\n<li data-parsoid=\'{"dsr":[473,592,1,0]}\'> <a rel="mw:ExtLink" href="//www.mediawiki.org/wiki/Special:MyLanguage/Localisation#Translation_resources" data-parsoid=\'{"dsr":[475,592,80,1]}\'>Localise MediaWiki for your language</a></li></ul></body></html>',
				original: {
					wikitext: {
						headers: {
							'content-type': 'text/plain;profile="https://www.mediawiki.org/wiki/Specs/wikitext/1.0.0"',
						},
						body: '<strong>MediaWiki has been successfully installed.</strong>\n\nConsult the [//meta.wikimedia.org/wiki/Help:Contents User\'s Guide] for information on using the wiki software.\n\n== Getting started ==\n* [//www.mediawiki.org/wiki/Special:MyLanguage/Manual:Configuration_settings Configuration settings list]\n* [//www.mediawiki.org/wiki/Special:MyLanguage/Manual:FAQ MediaWiki FAQ]\n* [https://lists.wikimedia.org/mailman/listinfo/mediawiki-announce MediaWiki release mailing list]\n* [//www.mediawiki.org/wiki/Special:MyLanguage/Localisation#Translation_resources Localise MediaWiki for your language]\n',
					},
				},
			})
			.expect(validWikitextResponse())
			.end(done);
		});

		it('should accept original html for selser (default)', function(done) {
			client.req
			.post(mockDomain + '/v3/transform/pagebundle/to/wikitext/')
			.send({
				html: '<!DOCTYPE html>\n<html prefix="dc: http://purl.org/dc/terms/ mw: http://mediawiki.org/rdf/" about="http://localhost/index.php/Special:Redirect/revision/1"><head prefix="mwr: http://localhost/index.php/Special:Redirect/"><meta property="mw:articleNamespace" content="0"/><link rel="dc:replaces" resource="mwr:revision/0"/><meta property="dc:modified" content="2014-09-12T22:46:59.000Z"/><meta about="mwr:user/0" property="dc:title" content="MediaWiki default"/><link rel="dc:contributor" resource="mwr:user/0"/><meta property="mw:revisionSHA1" content="8e0aa2f2a7829587801db67d0424d9b447e09867"/><meta property="dc:description" content=""/><link rel="dc:isVersionOf" href="http://localhost/index.php/Main_Page"/><title>Main_Page</title><base href="http://localhost/index.php/"/><link rel="stylesheet" href="//localhost/load.php?modules=mediawiki.legacy.commonPrint,shared|mediawiki.skinning.elements|mediawiki.skinning.content|mediawiki.skinning.interface|skins.vector.styles|site|mediawiki.skinning.content.parsoid&amp;only=styles&amp;debug=true&amp;skin=vector"/></head><body data-parsoid=\'{"dsr":[0,592,0,0]}\' lang="en" class="mw-content-ltr sitedir-ltr ltr mw-body mw-body-content mediawiki" dir="ltr"><p data-parsoid=\'{"dsr":[0,59,0,0]}\'><strong data-parsoid=\'{"stx":"html","dsr":[0,59,8,9]}\'>MediaWiki has been successfully installed.</strong></p>\n\n<p data-parsoid=\'{"dsr":[61,171,0,0]}\'>Consult the <a rel="mw:ExtLink" href="//meta.wikimedia.org/wiki/Help:Contents" data-parsoid=\'{"dsr":[73,127,41,1]}\'>User\'s Guide</a> for information on using the wiki software.</p>\n\n<h2 data-parsoid=\'{"dsr":[173,194,2,2]}\'> Getting started </h2>\n<ul data-parsoid=\'{"dsr":[195,592,0,0]}\'><li data-parsoid=\'{"dsr":[195,300,1,0]}\'> <a rel="mw:ExtLink" href="//www.mediawiki.org/wiki/Special:MyLanguage/Manual:Configuration_settings" data-parsoid=\'{"dsr":[197,300,75,1]}\'>Configuration settings list</a></li>\n<li data-parsoid=\'{"dsr":[301,373,1,0]}\'> <a rel="mw:ExtLink" href="//www.mediawiki.org/wiki/Special:MyLanguage/Manual:FAQ" data-parsoid=\'{"dsr":[303,373,56,1]}\'>MediaWiki FAQ</a></li>\n<li data-parsoid=\'{"dsr":[374,472,1,0]}\'> <a rel="mw:ExtLink" href="https://lists.wikimedia.org/mailman/listinfo/mediawiki-announce" data-parsoid=\'{"dsr":[376,472,65,1]}\'>MediaWiki release mailing list</a></li>\n<li data-parsoid=\'{"dsr":[473,592,1,0]}\'> <a rel="mw:ExtLink" href="//www.mediawiki.org/wiki/Special:MyLanguage/Localisation#Translation_resources" data-parsoid=\'{"dsr":[475,592,80,1]}\'>Localise MediaWiki for your language</a></li></ul></body></html>',
				original: {
					html: {
						headers: {
							'content-type': 'text/html;profile="https://www.mediawiki.org/wiki/Specs/HTML/' + defaultContentVersion + '"',
						},
						body: "<!DOCTYPE html>\n<html prefix=\"dc: http://purl.org/dc/terms/ mw: http://mediawiki.org/rdf/\" about=\"http://localhost/index.php/Special:Redirect/revision/1\"><head prefix=\"mwr: http://localhost/index.php/Special:Redirect/\"><meta property=\"mw:articleNamespace\" content=\"0\"/><link rel=\"dc:replaces\" resource=\"mwr:revision/0\"/><meta property=\"dc:modified\" content=\"2014-09-12T22:46:59.000Z\"/><meta about=\"mwr:user/0\" property=\"dc:title\" content=\"MediaWiki default\"/><link rel=\"dc:contributor\" resource=\"mwr:user/0\"/><meta property=\"mw:revisionSHA1\" content=\"8e0aa2f2a7829587801db67d0424d9b447e09867\"/><meta property=\"dc:description\" content=\"\"/><link rel=\"dc:isVersionOf\" href=\"http://localhost/index.php/Main_Page\"/><title>Main_Page</title><base href=\"http://localhost/index.php/\"/><link rel=\"stylesheet\" href=\"//localhost/load.php?modules=mediawiki.legacy.commonPrint,shared|mediawiki.skinning.elements|mediawiki.skinning.content|mediawiki.skinning.interface|skins.vector.styles|site|mediawiki.skinning.content.parsoid&amp;only=styles&amp;debug=true&amp;skin=vector\"/></head><body id=\"mwAA\" lang=\"en\" class=\"mw-content-ltr sitedir-ltr ltr mw-body mw-body-content mediawiki\" dir=\"ltr\"><p id=\"mwAQ\"><strong id=\"mwAg\">MediaWiki has been successfully installed.</strong></p>\n\n<p id=\"mwAw\">Consult the <a rel=\"mw:ExtLink\" href=\"//meta.wikimedia.org/wiki/Help:Contents\" id=\"mwBA\">User's Guide</a> for information on using the wiki software.</p>\n\n<h2 id=\"mwBQ\"> Getting started </h2>\n<ul id=\"mwBg\"><li id=\"mwBw\"> <a rel=\"mw:ExtLink\" href=\"//www.mediawiki.org/wiki/Special:MyLanguage/Manual:Configuration_settings\" id=\"mwCA\">Configuration settings list</a></li>\n<li id=\"mwCQ\"> <a rel=\"mw:ExtLink\" href=\"//www.mediawiki.org/wiki/Special:MyLanguage/Manual:FAQ\" id=\"mwCg\">MediaWiki FAQ</a></li>\n<li id=\"mwCw\"> <a rel=\"mw:ExtLink\" href=\"https://lists.wikimedia.org/mailman/listinfo/mediawiki-announce\" id=\"mwDA\">MediaWiki release mailing list</a></li>\n<li id=\"mwDQ\"> <a rel=\"mw:ExtLink\" href=\"//www.mediawiki.org/wiki/Special:MyLanguage/Localisation#Translation_resources\" id=\"mwDg\">Localise MediaWiki for your language</a></li></ul></body></html>",
					},
					"data-parsoid": {
						headers: {
							'content-type': 'application/json;profile="https://www.mediawiki.org/wiki/Specs/data-parsoid/' + defaultContentVersion + '"',
						},
						body: {
							"counter": 14,
							"ids": {
								"mwAA": { "dsr": [0, 592, 0, 0] }, "mwAQ": { "dsr": [0, 59, 0, 0] }, "mwAg": { "stx": "html", "dsr": [0, 59, 8, 9] }, "mwAw": { "dsr": [61, 171, 0, 0] }, "mwBA": { "dsr": [73, 127, 41, 1] }, "mwBQ": { "dsr": [173, 194, 2, 2] }, "mwBg": { "dsr": [195, 592, 0, 0] }, "mwBw": { "dsr": [195, 300, 1, 0] }, "mwCA": { "dsr": [197, 300, 75, 1] }, "mwCQ": { "dsr": [301, 373, 1, 0] }, "mwCg": { "dsr": [303, 373, 56, 1] }, "mwCw": { "dsr": [374, 472, 1, 0] }, "mwDA": { "dsr": [376, 472, 65, 1] }, "mwDQ": { "dsr": [473, 592, 1, 0] }, "mwDg": { "dsr": [475, 592, 80, 1] },
							},
						},
					},
				},
			})
			.expect(validWikitextResponse())
			.end(done);
		});

		it('should accept original html for selser (1.1.1, meta)', function(done) {
			client.req
			.post(mockDomain + '/v3/transform/pagebundle/to/wikitext/')
			.send({
				html: '<!DOCTYPE html>\n<html prefix="dc: http://purl.org/dc/terms/ mw: http://mediawiki.org/rdf/" about="http://localhost/index.php/Special:Redirect/revision/1"><head prefix="mwr: http://localhost/index.php/Special:Redirect/"><meta property="mw:articleNamespace" content="0"/><link rel="dc:replaces" resource="mwr:revision/0"/><meta property="dc:modified" content="2014-09-12T22:46:59.000Z"/><meta about="mwr:user/0" property="dc:title" content="MediaWiki default"/><link rel="dc:contributor" resource="mwr:user/0"/><meta property="mw:revisionSHA1" content="8e0aa2f2a7829587801db67d0424d9b447e09867"/><meta property="dc:description" content=""/><meta property="mw:html:version" content="1.1.1"/><link rel="dc:isVersionOf" href="http://localhost/index.php/Main_Page"/><title>Main_Page</title><base href="http://localhost/index.php/"/><link rel="stylesheet" href="//localhost/load.php?modules=mediawiki.legacy.commonPrint,shared|mediawiki.skinning.elements|mediawiki.skinning.content|mediawiki.skinning.interface|skins.vector.styles|site|mediawiki.skinning.content.parsoid&amp;only=styles&amp;debug=true&amp;skin=vector"/></head><body data-parsoid=\'{"dsr":[0,592,0,0]}\' lang="en" class="mw-content-ltr sitedir-ltr ltr mw-body mw-body-content mediawiki" dir="ltr"><p data-parsoid=\'{"dsr":[0,59,0,0]}\'><strong data-parsoid=\'{"stx":"html","dsr":[0,59,8,9]}\'>MediaWiki has been successfully installed.</strong></p>\n\n<p data-parsoid=\'{"dsr":[61,171,0,0]}\'>Consult the <a rel="mw:ExtLink" href="//meta.wikimedia.org/wiki/Help:Contents" data-parsoid=\'{"dsr":[73,127,41,1]}\'>User\'s Guide</a> for information on using the wiki software.</p>\n\n<h2 data-parsoid=\'{"dsr":[173,194,2,2]}\'> Getting started </h2>\n<ul data-parsoid=\'{"dsr":[195,592,0,0]}\'><li data-parsoid=\'{"dsr":[195,300,1,0]}\'> <a rel="mw:ExtLink" href="//www.mediawiki.org/wiki/Special:MyLanguage/Manual:Configuration_settings" data-parsoid=\'{"dsr":[197,300,75,1]}\'>Configuration settings list</a></li>\n<li data-parsoid=\'{"dsr":[301,373,1,0]}\'> <a rel="mw:ExtLink" href="//www.mediawiki.org/wiki/Special:MyLanguage/Manual:FAQ" data-parsoid=\'{"dsr":[303,373,56,1]}\'>MediaWiki FAQ</a></li>\n<li data-parsoid=\'{"dsr":[374,472,1,0]}\'> <a rel="mw:ExtLink" href="https://lists.wikimedia.org/mailman/listinfo/mediawiki-announce" data-parsoid=\'{"dsr":[376,472,65,1]}\'>MediaWiki release mailing list</a></li>\n<li data-parsoid=\'{"dsr":[473,592,1,0]}\'> <a rel="mw:ExtLink" href="//www.mediawiki.org/wiki/Special:MyLanguage/Localisation#Translation_resources" data-parsoid=\'{"dsr":[475,592,80,1]}\'>Localise MediaWiki for your language</a></li></ul></body></html>',
				original: {
					html: {
						headers: {
							'content-type': 'text/html; profile="mediawiki.org/specs/html/1.1.1"',
						},
						body: "<!DOCTYPE html>\n<html prefix=\"dc: http://purl.org/dc/terms/ mw: http://mediawiki.org/rdf/\" about=\"http://localhost/index.php/Special:Redirect/revision/1\"><head prefix=\"mwr: http://localhost/index.php/Special:Redirect/\"><meta property=\"mw:articleNamespace\" content=\"0\"/><link rel=\"dc:replaces\" resource=\"mwr:revision/0\"/><meta property=\"dc:modified\" content=\"2014-09-12T22:46:59.000Z\"/><meta about=\"mwr:user/0\" property=\"dc:title\" content=\"MediaWiki default\"/><link rel=\"dc:contributor\" resource=\"mwr:user/0\"/><meta property=\"mw:revisionSHA1\" content=\"8e0aa2f2a7829587801db67d0424d9b447e09867\"/><meta property=\"dc:description\" content=\"\"/><link rel=\"dc:isVersionOf\" href=\"http://localhost/index.php/Main_Page\"/><title>Main_Page</title><base href=\"http://localhost/index.php/\"/><link rel=\"stylesheet\" href=\"//localhost/load.php?modules=mediawiki.legacy.commonPrint,shared|mediawiki.skinning.elements|mediawiki.skinning.content|mediawiki.skinning.interface|skins.vector.styles|site|mediawiki.skinning.content.parsoid&amp;only=styles&amp;debug=true&amp;skin=vector\"/></head><body id=\"mwAA\" lang=\"en\" class=\"mw-content-ltr sitedir-ltr ltr mw-body mw-body-content mediawiki\" dir=\"ltr\"><p id=\"mwAQ\"><strong id=\"mwAg\">MediaWiki has been successfully installed.</strong></p>\n\n<p id=\"mwAw\">Consult the <a rel=\"mw:ExtLink\" href=\"//meta.wikimedia.org/wiki/Help:Contents\" id=\"mwBA\">User's Guide</a> for information on using the wiki software.</p>\n\n<h2 id=\"mwBQ\"> Getting started </h2>\n<ul id=\"mwBg\"><li id=\"mwBw\"> <a rel=\"mw:ExtLink\" href=\"//www.mediawiki.org/wiki/Special:MyLanguage/Manual:Configuration_settings\" id=\"mwCA\">Configuration settings list</a></li>\n<li id=\"mwCQ\"> <a rel=\"mw:ExtLink\" href=\"//www.mediawiki.org/wiki/Special:MyLanguage/Manual:FAQ\" id=\"mwCg\">MediaWiki FAQ</a></li>\n<li id=\"mwCw\"> <a rel=\"mw:ExtLink\" href=\"https://lists.wikimedia.org/mailman/listinfo/mediawiki-announce\" id=\"mwDA\">MediaWiki release mailing list</a></li>\n<li id=\"mwDQ\"> <a rel=\"mw:ExtLink\" href=\"//www.mediawiki.org/wiki/Special:MyLanguage/Localisation#Translation_resources\" id=\"mwDg\">Localise MediaWiki for your language</a></li></ul></body></html>",
					},
					"data-parsoid": {
						headers: {
							'content-type': 'application/json;profile="https://www.mediawiki.org/wiki/Specs/data-parsoid/0.0.1"',
						},
						body: {
							"counter": 14,
							"ids": {
								"mwAA": { "dsr": [0, 592, 0, 0] }, "mwAQ": { "dsr": [0, 59, 0, 0] }, "mwAg": { "stx": "html", "dsr": [0, 59, 8, 9] }, "mwAw": { "dsr": [61, 171, 0, 0] }, "mwBA": { "dsr": [73, 127, 41, 1] }, "mwBQ": { "dsr": [173, 194, 2, 2] }, "mwBg": { "dsr": [195, 592, 0, 0] }, "mwBw": { "dsr": [195, 300, 1, 0] }, "mwCA": { "dsr": [197, 300, 75, 1] }, "mwCQ": { "dsr": [301, 373, 1, 0] }, "mwCg": { "dsr": [303, 373, 56, 1] }, "mwCw": { "dsr": [374, 472, 1, 0] }, "mwDA": { "dsr": [376, 472, 65, 1] }, "mwDQ": { "dsr": [473, 592, 1, 0] }, "mwDg": { "dsr": [475, 592, 80, 1] },
							},
						},
					},
				},
			})
			.expect(validWikitextResponse())
			.end(done);
		});

		it('should accept original html for selser (1.1.1, headers)', function(done) {
			client.req
			.post(mockDomain + '/v3/transform/pagebundle/to/wikitext/')
			.send({
				// Don't set the mw:html:version so that we get it from the original/headers
				html: '<!DOCTYPE html>\n<html prefix="dc: http://purl.org/dc/terms/ mw: http://mediawiki.org/rdf/" about="http://localhost/index.php/Special:Redirect/revision/1"><head prefix="mwr: http://localhost/index.php/Special:Redirect/"><meta property="mw:articleNamespace" content="0"/><link rel="dc:replaces" resource="mwr:revision/0"/><meta property="dc:modified" content="2014-09-12T22:46:59.000Z"/><meta about="mwr:user/0" property="dc:title" content="MediaWiki default"/><link rel="dc:contributor" resource="mwr:user/0"/><meta property="mw:revisionSHA1" content="8e0aa2f2a7829587801db67d0424d9b447e09867"/><meta property="dc:description" content=""/><link rel="dc:isVersionOf" href="http://localhost/index.php/Main_Page"/><title>Main_Page</title><base href="http://localhost/index.php/"/><link rel="stylesheet" href="//localhost/load.php?modules=mediawiki.legacy.commonPrint,shared|mediawiki.skinning.elements|mediawiki.skinning.content|mediawiki.skinning.interface|skins.vector.styles|site|mediawiki.skinning.content.parsoid&amp;only=styles&amp;debug=true&amp;skin=vector"/></head><body data-parsoid=\'{"dsr":[0,592,0,0]}\' lang="en" class="mw-content-ltr sitedir-ltr ltr mw-body mw-body-content mediawiki" dir="ltr"><p data-parsoid=\'{"dsr":[0,59,0,0]}\'><strong data-parsoid=\'{"stx":"html","dsr":[0,59,8,9]}\'>MediaWiki has been successfully installed.</strong></p>\n\n<p data-parsoid=\'{"dsr":[61,171,0,0]}\'>Consult the <a rel="mw:ExtLink" href="//meta.wikimedia.org/wiki/Help:Contents" data-parsoid=\'{"dsr":[73,127,41,1]}\'>User\'s Guide</a> for information on using the wiki software.</p>\n\n<h2 data-parsoid=\'{"dsr":[173,194,2,2]}\'> Getting started </h2>\n<ul data-parsoid=\'{"dsr":[195,592,0,0]}\'><li data-parsoid=\'{"dsr":[195,300,1,0]}\'> <a rel="mw:ExtLink" href="//www.mediawiki.org/wiki/Special:MyLanguage/Manual:Configuration_settings" data-parsoid=\'{"dsr":[197,300,75,1]}\'>Configuration settings list</a></li>\n<li data-parsoid=\'{"dsr":[301,373,1,0]}\'> <a rel="mw:ExtLink" href="//www.mediawiki.org/wiki/Special:MyLanguage/Manual:FAQ" data-parsoid=\'{"dsr":[303,373,56,1]}\'>MediaWiki FAQ</a></li>\n<li data-parsoid=\'{"dsr":[374,472,1,0]}\'> <a rel="mw:ExtLink" href="https://lists.wikimedia.org/mailman/listinfo/mediawiki-announce" data-parsoid=\'{"dsr":[376,472,65,1]}\'>MediaWiki release mailing list</a></li>\n<li data-parsoid=\'{"dsr":[473,592,1,0]}\'> <a rel="mw:ExtLink" href="//www.mediawiki.org/wiki/Special:MyLanguage/Localisation#Translation_resources" data-parsoid=\'{"dsr":[475,592,80,1]}\'>Localise MediaWiki for your language</a></li></ul></body></html>',
				original: {
					html: {
						headers: {
							'content-type': 'text/html; profile="mediawiki.org/specs/html/1.1.1"',
						},
						body: "<!DOCTYPE html>\n<html prefix=\"dc: http://purl.org/dc/terms/ mw: http://mediawiki.org/rdf/\" about=\"http://localhost/index.php/Special:Redirect/revision/1\"><head prefix=\"mwr: http://localhost/index.php/Special:Redirect/\"><meta property=\"mw:articleNamespace\" content=\"0\"/><link rel=\"dc:replaces\" resource=\"mwr:revision/0\"/><meta property=\"dc:modified\" content=\"2014-09-12T22:46:59.000Z\"/><meta about=\"mwr:user/0\" property=\"dc:title\" content=\"MediaWiki default\"/><link rel=\"dc:contributor\" resource=\"mwr:user/0\"/><meta property=\"mw:revisionSHA1\" content=\"8e0aa2f2a7829587801db67d0424d9b447e09867\"/><meta property=\"dc:description\" content=\"\"/><link rel=\"dc:isVersionOf\" href=\"http://localhost/index.php/Main_Page\"/><title>Main_Page</title><base href=\"http://localhost/index.php/\"/><link rel=\"stylesheet\" href=\"//localhost/load.php?modules=mediawiki.legacy.commonPrint,shared|mediawiki.skinning.elements|mediawiki.skinning.content|mediawiki.skinning.interface|skins.vector.styles|site|mediawiki.skinning.content.parsoid&amp;only=styles&amp;debug=true&amp;skin=vector\"/></head><body id=\"mwAA\" lang=\"en\" class=\"mw-content-ltr sitedir-ltr ltr mw-body mw-body-content mediawiki\" dir=\"ltr\"><p id=\"mwAQ\"><strong id=\"mwAg\">MediaWiki has been successfully installed.</strong></p>\n\n<p id=\"mwAw\">Consult the <a rel=\"mw:ExtLink\" href=\"//meta.wikimedia.org/wiki/Help:Contents\" id=\"mwBA\">User's Guide</a> for information on using the wiki software.</p>\n\n<h2 id=\"mwBQ\"> Getting started </h2>\n<ul id=\"mwBg\"><li id=\"mwBw\"> <a rel=\"mw:ExtLink\" href=\"//www.mediawiki.org/wiki/Special:MyLanguage/Manual:Configuration_settings\" id=\"mwCA\">Configuration settings list</a></li>\n<li id=\"mwCQ\"> <a rel=\"mw:ExtLink\" href=\"//www.mediawiki.org/wiki/Special:MyLanguage/Manual:FAQ\" id=\"mwCg\">MediaWiki FAQ</a></li>\n<li id=\"mwCw\"> <a rel=\"mw:ExtLink\" href=\"https://lists.wikimedia.org/mailman/listinfo/mediawiki-announce\" id=\"mwDA\">MediaWiki release mailing list</a></li>\n<li id=\"mwDQ\"> <a rel=\"mw:ExtLink\" href=\"//www.mediawiki.org/wiki/Special:MyLanguage/Localisation#Translation_resources\" id=\"mwDg\">Localise MediaWiki for your language</a></li></ul></body></html>",
					},
					"data-parsoid": {
						headers: {
							'content-type': 'application/json;profile="https://www.mediawiki.org/wiki/Specs/data-parsoid/0.0.1"',
						},
						body: {
							"counter": 14,
							"ids": {
								"mwAA": { "dsr": [0, 592, 0, 0] }, "mwAQ": { "dsr": [0, 59, 0, 0] }, "mwAg": { "stx": "html", "dsr": [0, 59, 8, 9] }, "mwAw": { "dsr": [61, 171, 0, 0] }, "mwBA": { "dsr": [73, 127, 41, 1] }, "mwBQ": { "dsr": [173, 194, 2, 2] }, "mwBg": { "dsr": [195, 592, 0, 0] }, "mwBw": { "dsr": [195, 300, 1, 0] }, "mwCA": { "dsr": [197, 300, 75, 1] }, "mwCQ": { "dsr": [301, 373, 1, 0] }, "mwCg": { "dsr": [303, 373, 56, 1] }, "mwCw": { "dsr": [374, 472, 1, 0] }, "mwDA": { "dsr": [376, 472, 65, 1] }, "mwDQ": { "dsr": [473, 592, 1, 0] }, "mwDg": { "dsr": [475, 592, 80, 1] },
							},
						},
					},
				},
			})
			.expect(validWikitextResponse())
			.end(done);
		});

		it('should return http 400 if supplied data-parsoid is empty', function(done) {
			client.req
			.post(mockDomain + '/v3/transform/pagebundle/to/wikitext/')
			.send({
				html: '<html><head></head><body><p>hi</p></body></html>',
				original: {
					html: {
						headers: {
							'content-type': 'text/html;profile="https://www.mediawiki.org/wiki/Specs/HTML/' + defaultContentVersion + '"',
						},
						body: '<html><head></head><body><p>ho</p></body></html>',
					},
					'data-parsoid': {
						headers: {
							'content-type': 'application/json;profile="https://www.mediawiki.org/wiki/Specs/data-parsoid/' + defaultContentVersion + '"',
						},
						body: {},
					},
				},
			})
			.expect(400)
			.end(done);
		});

		// FIXME: Pagebundle validation in general is needed
		it.skip('should return http 400 if supplied data-parsoid is a string', function(done) {
			client.req
			.post(mockDomain + '/v3/transform/pagebundle/to/wikitext/')
			.send({
				html: '<html><head></head><body><p>hi</p></body></html>',
				original: {
					html: {
						headers: {
							'content-type': 'text/html;profile="https://www.mediawiki.org/wiki/Specs/HTML/' + defaultContentVersion + '"',
						},
						body: '<html><head></head><body><p>ho</p></body></html>',
					},
					'data-parsoid': {
						headers: {
							'content-type': 'application/json;profile="https://www.mediawiki.org/wiki/Specs/data-parsoid/' + defaultContentVersion + '"',
						},
						body: 'Garbled text from RESTBase.',
					},
				},
			})
			.expect(400)
			.end(done);
		});

		// The following three tests should all serialize as:
		//   "<div>Selser test"
		// However, we're deliberately setting the original wikitext in
		// the first two to garbage so that when selser doesn't detect any
		// difference between the new and old html, it'll just reuse that
		// string and we have a reliable way of determining that selser
		// was used.

		it('should use selser with supplied wikitext', function(done) {
			if (skipForNow) { return this.skip(); }  // Create Junk Page
			// New and old html are identical, which should produce no diffs
			// and reuse the original wikitext.
			client.req
			// Need to provide an oldid so that selser mode is enabled
			// Without an oldid, serialization falls back to non-selser wts.
			// The oldid is used to fetch wikitext, but if wikitext is provided
			// (as in this test), it is not used. So, for testing purposes,
			// we can use any old random id, as long as something is present.
			.post(mockDomain + '/v3/transform/pagebundle/to/wikitext/')
			.send({
				html: "<html><body id=\"mwAA\"><div id=\"mwBB\">Selser test</div></body></html>",
				original: {
					title: "Junk Page",
					revid: 1234,
					wikitext: {
						body: "1. This is just some junk. See the comment above.",
					},
					html: {
						body: "<html><body id=\"mwAA\"><div id=\"mwBB\">Selser test</div></body></html>",
						headers: {
							'content-type': 'text/html;profile="https://www.mediawiki.org/wiki/Specs/HTML/' + defaultContentVersion + '"',
						},
					},
					"data-parsoid": {
						body: {
							"ids": {
								mwAA: {},
								mwBB: { "autoInsertedEnd": true, "stx": "html" },
							},
						},
					},
				},
			})
			.expect(validWikitextResponse(
				"1. This is just some junk. See the comment above."
			))
			.end(done);
		});

		it('should use selser with wikitext fetched from the mw api', function(done) {
			if (skipForNow) { return this.skip(); }  // Creat Junk Page
			// New and old html are identical, which should produce no diffs
			// and reuse the original wikitext.
			client.req
			.post(mockDomain + '/v3/transform/pagebundle/to/wikitext/')
			.send({
				html: "<html><body id=\"mwAA\"><div id=\"mwBB\">Selser test</div></body></html>",
				original: {
					revid: 2,
					title: "Junk Page",
					html: {
						body: "<html><body id=\"mwAA\"><div id=\"mwBB\">Selser test</div></body></html>",
						headers: {
							'content-type': 'text/html;profile="https://www.mediawiki.org/wiki/Specs/HTML/' + defaultContentVersion + '"',
						},
					},
					"data-parsoid": {
						body: {
							"ids": {
								mwAA: {},
								mwBB: { "autoInsertedEnd": true, "stx": "html" },
							},
						},
					},
				},
			})
			.expect(validWikitextResponse(
				"2. This is just some junk. See the comment above."
			))
			.end(done);
		});

		it('should fallback to non-selective serialization', function(done) {
			// Without the original wikitext and an unavailable
			// TemplateFetch for the source (no revision id provided),
			// it should fallback to non-selective serialization.
			client.req
			.post(mockDomain + '/v3/transform/pagebundle/to/wikitext/')
			.send({
				html: "<html><body id=\"mwAA\"><div id=\"mwBB\">Selser test</div></body></html>",
				original: {
					title: "Junk Page",
					html: {
						body: "<html><body id=\"mwAA\"><div id=\"mwBB\">Selser test</div></body></html>",
						headers: {
							'content-type': 'text/html;profile="https://www.mediawiki.org/wiki/Specs/HTML/' + defaultContentVersion + '"',
						},
					},
					"data-parsoid": {
						body: {
							"ids": {
								mwAA: {},
								mwBB: { "autoInsertedEnd": true, "stx": "html" },
							},
						},
					},
				},
			})
			.expect(validWikitextResponse(
				"<div>Selser test"
			))
			.end(done);
		});

		it('should apply data-parsoid to duplicated ids', function(done) {
			client.req
			.post(mockDomain + '/v3/transform/pagebundle/to/wikitext/')
			.send({
				html: "<html><body id=\"mwAA\"><div id=\"mwBB\">data-parsoid test</div><div id=\"mwBB\">data-parsoid test</div></body></html>",
				original: {
					title: "Doesnotexist",
					html: {
						body: "<html><body id=\"mwAA\"><div id=\"mwBB\">data-parsoid test</div></body></html>",
						headers: {
							'content-type': 'text/html;profile="https://www.mediawiki.org/wiki/Specs/HTML/' + defaultContentVersion + '"',
						},
					},
					"data-parsoid": {
						body: {
							"ids": {
								mwAA: {},
								mwBB: { "autoInsertedEnd": true, "stx": "html" },
							},
						},
					},
				},
			})
			.expect(validWikitextResponse(
				"<div>data-parsoid test<div>data-parsoid test"
			))
			.end(done);
		});

		it('should return a 400 for missing inline data-mw (2.x)', function(done) {
			client.req
			.post(mockDomain + '/v3/transform/pagebundle/to/wikitext/')
			.send({
				html: '<p about="#mwt1" typeof="mw:Transclusion" id="mwAQ">hi</p>',
				original: {
					title: 'Doesnotexist',
					'data-parsoid': {
						body: {
							ids: { "mwAQ": { "pi": [[{ "k": "1" }]] } },
						},
					},
					html: {
						headers: {
							'content-type': 'text/html;profile="https://www.mediawiki.org/wiki/Specs/HTML/2.8.0"',
						},
						body: '<p about="#mwt1" typeof="mw:Transclusion" id="mwAQ">ho</p>',
					},
				},
			})
			.expect(400)
			.end(done);
		});

		it('should return a 400 for not supplying data-mw', function(done) {
			client.req
			.post(mockDomain + '/v3/transform/pagebundle/to/wikitext/')
			.send({
				html: '<p about="#mwt1" typeof="mw:Transclusion" id="mwAQ">hi</p>',
				original: {
					title: 'Doesnotexist',
					'data-parsoid': {
						body: {
							ids: { "mwAQ": { "pi": [[{ "k": "1" }]] } },
						},
					},
					html: {
						headers: {
							'content-type': 'text/html;profile="https://www.mediawiki.org/wiki/Specs/HTML/999.0.0"',
						},
						body: '<p about="#mwt1" typeof="mw:Transclusion" id="mwAQ">ho</p>',
					},
				},
			})
			.expect(400)
			.end(done);
		});

		it('should apply original data-mw', function(done) {
			client.req
			.post(mockDomain + '/v3/transform/pagebundle/to/wikitext/')
			.send({
				html: '<p about="#mwt1" typeof="mw:Transclusion" id="mwAQ">hi</p>',
				original: {
					title: 'Doesnotexist',
					'data-parsoid': {
						body: {
							ids: { "mwAQ": { "pi": [[{ "k": "1" }]] } },
						},
					},
					'data-mw': {
						body: {
							ids: { "mwAQ": { "parts": [{ "template": { "target": { "wt": "1x", "href": "./Template:1x" }, "params": { "1": { "wt": "hi" } }, "i": 0 } }] } },
						},
					},
					html: {
						headers: {
							'content-type': 'text/html;profile="https://www.mediawiki.org/wiki/Specs/HTML/999.0.0"',
						},
						body: '<p about="#mwt1" typeof="mw:Transclusion" id="mwAQ">ho</p>',
					},
				},
			})
			.expect(validWikitextResponse('{{1x|hi}}'))
			.end(done);
		});

		// Sanity check data-mw was applied in the previous test
		it('should return a 400 for missing modified data-mw', function(done) {
			client.req
			.post(mockDomain + '/v3/transform/pagebundle/to/wikitext/')
			.send({
				html: '<p about="#mwt1" typeof="mw:Transclusion" id="mwAQ">hi</p>',
				original: {
					title: 'Doesnotexist',
					'data-parsoid': {
						body: {
							ids: { "mwAQ": { "pi": [[{ "k": "1" }]] } },
						},
					},
					'data-mw': {
						body: {
							ids: { "mwAQ": { } },  // Missing data-mw.parts!
						},
					},
					html: {
						headers: {
							'content-type': 'text/html;profile="https://www.mediawiki.org/wiki/Specs/HTML/999.0.0"',
						},
						body: '<p about="#mwt1" typeof="mw:Transclusion" id="mwAQ">ho</p>',
					},
				},
			})
			.expect(400)
			.end(done);
		});

		it('should give precedence to inline data-mw over original', function(done) {
			client.req
			.post(mockDomain + '/v3/transform/pagebundle/to/wikitext/')
			.send({
				html: '<p about="#mwt1" typeof="mw:Transclusion" data-mw=\'{"parts":[{"template":{"target":{"wt":"1x","href":"./Template:1x"},"params":{"1":{"wt":"hi"}},"i":0}}]}\' id="mwAQ">hi</p>',
				original: {
					title: 'Doesnotexist',
					'data-parsoid': {
						body: {
							ids: { "mwAQ": { "pi": [[{ "k": "1" }]] } },
						},
					},
					'data-mw': {
						body: {
							ids: { "mwAQ": { } },  // Missing data-mw.parts!
						},
					},
					html: {
						headers: {
							'content-type': 'text/html;profile="https://www.mediawiki.org/wiki/Specs/HTML/999.0.0"',
						},
						body: '<p about="#mwt1" typeof="mw:Transclusion" id="mwAQ">ho</p>',
					},
				},
			})
			.expect(validWikitextResponse('{{1x|hi}}'))
			.end(done);
		});

		it('should not apply original data-mw if modified is supplied', function(done) {
			client.req
			.post(mockDomain + '/v3/transform/pagebundle/to/wikitext/')
			.send({
				html: '<p about="#mwt1" typeof="mw:Transclusion" id="mwAQ">hi</p>',
				'data-mw': {
					body: {
						ids: { "mwAQ": { "parts": [{ "template": { "target": { "wt": "1x", "href": "./Template:1x" }, "params": { "1": { "wt": "hi" } }, "i": 0 } }] } },
					},
				},
				original: {
					title: 'Doesnotexist',
					'data-parsoid': {
						body: {
							ids: { "mwAQ": { "pi": [[{ "k": "1" }]] } },
						},
					},
					'data-mw': {
						body: {
							ids: { "mwAQ": { } },  // Missing data-mw.parts!
						},
					},
					html: {
						headers: {
							'content-type': 'text/html;profile="https://www.mediawiki.org/wiki/Specs/HTML/999.0.0"',
						},
						body: '<p about="#mwt1" typeof="mw:Transclusion" id="mwAQ">ho</p>',
					},
				},
			})
			.expect(validWikitextResponse('{{1x|hi}}'))
			.end(done);
		});

		// The next three tests, although redundant with the above precedence
		// tests, are an attempt to show clients the semantics of separate
		// data-mw in the API.  The main idea is,
		//
		//   non-inline-data-mw = modified || original
		//   inline-data-mw > non-inline-data-mw

		it('should apply original data-mw when modified is absent (captions 1)', function(done) {
			client.req
			.post(mockDomain + '/v3/transform/pagebundle/to/wikitext/')
			.send({
				html: '<p><span class="mw-default-size" typeof="mw:File" id="mwAg"><a href="./File:Foobar.jpg" id="mwAw"><img resource="./File:Foobar.jpg" src="//upload.wikimedia.org/wikipedia/commons/3/3a/Foobar.jpg" data-file-width="240" data-file-height="28" data-file-type="bitmap" height="28" width="240" id="mwBA"/></a></span></p>',
				original: {
					title: 'Doesnotexist',
					'data-parsoid': {
						body: {
							ids: {
								"mwAg": { "optList": [{ "ck": "caption", "ak": "Testing 123" }] },
								"mwAw": { "a": { "href": "./File:Foobar.jpg" }, "sa": {} },
								"mwBA": { "a": { "resource": "./File:Foobar.jpg", "height": "28", "width": "240" },"sa": { "resource": "File:Foobar.jpg" } },
							},
						},
					},
					'data-mw': {
						body: {
							ids: {
								"mwAg": { "caption": "Testing 123" },
							},
						},
					},
					html: {
						headers: {
							'content-type': 'text/html;profile="https://www.mediawiki.org/wiki/Specs/HTML/999.0.0"',
						},
						body: '<p><span class="mw-default-size" typeof="mw:File" id="mwAg"><a href="./File:Foobar.jpg" id="mwAw"><img resource="./File:Foobar.jpg" src="//upload.wikimedia.org/wikipedia/commons/3/3a/Foobar.jpg" data-file-width="240" data-file-height="28" data-file-type="bitmap" height="28" width="240" id="mwBA"/></a></span></p>',
					},
				},
			})
			.expect(validWikitextResponse('[[File:Foobar.jpg|Testing 123]]'))
			.end(done);
		});

		it('should give precedence to inline data-mw over modified (captions 2)', function(done) {
			client.req
			.post(mockDomain + '/v3/transform/pagebundle/to/wikitext/')
			.send({
				html: '<p><span class="mw-default-size" typeof="mw:File" data-mw="{}" id="mwAg"><a href="./File:Foobar.jpg" id="mwAw"><img resource="./File:Foobar.jpg" src="//upload.wikimedia.org/wikipedia/commons/3/3a/Foobar.jpg" data-file-width="240" data-file-height="28" data-file-type="bitmap" height="28" width="240" id="mwBA"/></a></span></p>',
				'data-mw': {
					body: {
						ids: {
							"mwAg": { "caption": "Testing 123" },
						},
					},
				},
				original: {
					title: 'Doesnotexist',
					'data-parsoid': {
						body: {
							ids: {
								"mwAg": { "optList": [{ "ck": "caption", "ak": "Testing 123" }] },
								"mwAw": { "a": { "href": "./File:Foobar.jpg" }, "sa": {} },
								"mwBA": { "a": { "resource": "./File:Foobar.jpg", "height": "28", "width": "240" },"sa": { "resource": "File:Foobar.jpg" } },
							},
						},
					},
					'data-mw': {
						body: {
							ids: {
								"mwAg": { "caption": "Testing 123" },
							},
						},
					},
					html: {
						headers: {
							'content-type': 'text/html;profile="https://www.mediawiki.org/wiki/Specs/HTML/999.0.0"',
						},
						body: '<p><span class="mw-default-size" typeof="mw:File" id="mwAg"><a href="./File:Foobar.jpg" id="mwAw"><img resource="./File:Foobar.jpg" src="//upload.wikimedia.org/wikipedia/commons/3/3a/Foobar.jpg" data-file-width="240" data-file-height="28" data-file-type="bitmap" height="28" width="240" id="mwBA"/></a></span></p>',
					},
				},
			})
			.expect(validWikitextResponse('[[File:Foobar.jpg]]'))
			.end(done);
		});

		it('should give precedence to modified data-mw over original (captions 3)', function(done) {
			client.req
			.post(mockDomain + '/v3/transform/pagebundle/to/wikitext/')
			.send({
				html: '<p><span class="mw-default-size" typeof="mw:File" id="mwAg"><a href="./File:Foobar.jpg" id="mwAw"><img resource="./File:Foobar.jpg" src="//upload.wikimedia.org/wikipedia/commons/3/3a/Foobar.jpg" data-file-width="240" data-file-height="28" data-file-type="bitmap" height="28" width="240" id="mwBA"/></a></span></p>',
				'data-mw': {
					body: {
						ids: {
							"mwAg": {},
						},
					},
				},
				original: {
					title: 'Doesnotexist',
					'data-parsoid': {
						body: {
							ids: {
								"mwAg": { "optList": [{ "ck": "caption", "ak": "Testing 123" }] },
								"mwAw": { "a": { "href": "./File:Foobar.jpg" }, "sa": {} },
								"mwBA": { "a": { "resource": "./File:Foobar.jpg", "height": "28", "width": "240" },"sa": { "resource": "File:Foobar.jpg" } },
							},
						},
					},
					'data-mw': {
						body: {
							ids: {
								"mwAg": { "caption": "Testing 123" },
							},
						},
					},
					html: {
						headers: {
							'content-type': 'text/html;profile="https://www.mediawiki.org/wiki/Specs/HTML/999.0.0"',
						},
						body: '<p><span class="mw-default-size" typeof="mw:File" id="mwAg"><a href="./File:Foobar.jpg" id="mwAw"><img resource="./File:Foobar.jpg" src="//upload.wikimedia.org/wikipedia/commons/3/3a/Foobar.jpg" data-file-width="240" data-file-height="28" data-file-type="bitmap" height="28" width="240" id="mwBA"/></a></span></p>',
					},
				},
			})
			.expect(validWikitextResponse('[[File:Foobar.jpg]]'))
			.end(done);
		});

		it('should apply extra normalizations', function(done) {
			client.req
			.post(mockDomain + '/v3/transform/html/to/wikitext/')
			.send({
				html: '<h2></h2>',
				original: { title: 'Doesnotexist' },
			})
			.expect(validWikitextResponse(
				''
			))
			.end(done);
		});

		it('should return a request too large error', function(done) {
			if (skipForNow) { return this.skip(); }  // Set limits in config
			client.req
			.post(mockDomain + '/v3/transform/html/to/wikitext/')
			.send({
				original: {
					title: 'Large_Page',
				},
				html: "a".repeat(parsoidOptions.limits.html2wt.maxHTMLSize + 1),
			})
			.expect(413)
			.end(done);
		});

		it('should fail to downgrade the original version for an unknown transition', function(done) {
			client.req
			.post(mockDomain + '/v3/transform/pagebundle/to/wikitext/')
			.send({
				html: '<!DOCTYPE html>\n<html><head><meta charset="utf-8"/><meta property="mw:htmlVersion" content="2.8.0"/></head><body id="mwAA" lang="en" class="mw-content-ltr sitedir-ltr ltr mw-body-content parsoid-body mediawiki mw-parser-output" dir="ltr">123</body></html>',
				original: {
					title: 'Doesnotexist',
					'data-parsoid': { body: { "ids": {} } },
					html: {
						headers: {
							'content-type': 'text/html;profile="https://www.mediawiki.org/wiki/Specs/HTML/2222.0.0"',
						},
						body: '<!DOCTYPE html>\n<html><head><meta charset="utf-8"/><meta property="mw:html:version" content="2222.0.0"/></head><body id="mwAA" lang="en" class="mw-content-ltr sitedir-ltr ltr mw-body-content parsoid-body mediawiki mw-parser-output" dir="ltr">123</body></html>',
					},
				},
			})
			.expect(400)
			.end(done);
		});

	}); // end html2wt

	describe('pb2pb', function() {

		it('should require an original or previous version', function(done) {
			client.req
			.post(mockDomain + '/v3/transform/pagebundle/to/pagebundle/Reuse_Page/100')
			.send({})
			.expect(400)
			.end(done);
		});

		var previousRevHTML = {
			revid: 99,
			html: {
				headers: {
					'content-type': 'text/html;profile="https://www.mediawiki.org/wiki/Specs/HTML/' + defaultContentVersion + '"',
				},
				body: '<p about="#mwt1" typeof="mw:Transclusion" data-mw=\'{"parts":[{"template":{"target":{"wt":"colours of the rainbow","href":"./Template:Colours_of_the_rainbow"},"params":{},"i":0}}]}\' id="mwAg">pink</p>',
			},
			"data-parsoid": {
				headers: {
					'content-type': 'application/json;profile="https://www.mediawiki.org/wiki/Specs/data-parsoid/' + defaultContentVersion + '"',
				},
				body: {
					'counter': 2,
					'ids': {
						'mwAg': { 'pi': [[]], 'src': '{{colours of the rainbow}}' },  // artificially added src
					},
				},
			},
		};

		it('should error when revision not found (transform, pb2pb)', function(done) {
			client.req
			.post(mockDomain + '/v3/transform/pagebundle/to/pagebundle/Doesnotexist')
			.send({
				previous: previousRevHTML,
			})
			.expect(404)
			.end(done);
		});

		// FIXME: Expansion reuse wasn't ported, see T98995
		it.skip('should accept the previous revision to reuse expansions', function(done) {
			client.req
			.post(mockDomain + '/v3/transform/pagebundle/to/pagebundle/Reuse_Page/100')
			.send({
				previous: previousRevHTML,
			})
			.expect(validPageBundleResponse(function(doc) {
				doc.body.firstChild.textContent.should.match(/pink/);
			}))
			.end(done);
		});

		var origHTML = Util.clone(previousRevHTML);
		origHTML.revid = 100;

		// FIXME: Expansion reuse wasn't ported, see T98995
		it.skip('should accept the original and reuse certain expansions', function(done) {
			client.req
			.post(mockDomain + '/v3/transform/pagebundle/to/pagebundle/Reuse_Page/100')
			.send({
				updates: {
					transclusions: true,
				},
				original: origHTML,
			})
			.expect(validPageBundleResponse(function(doc) {
				doc.body.firstChild.textContent.should.match(/purple/);
			}))
			.end(done);
		});

		it('should refuse an unknown conversion (2.x -> 999.x)', function(done) {
			previousRevHTML.html.headers['content-type'].should.equal('text/html;profile="https://www.mediawiki.org/wiki/Specs/HTML/2.8.0"');
			client.req
			.post(mockDomain + '/v3/transform/pagebundle/to/pagebundle/Reuse_Page/100')
			.set('Accept', 'application/json; profile="https://www.mediawiki.org/wiki/Specs/pagebundle/999.0.0"')
			.send({
				previous: previousRevHTML,
			})
			.expect(415)
			.end(done);
		});

		it('should downgrade 999.x content to 2.x', function(done) {
			var contentVersion = '2.8.0';
			client.req
			.post(mockDomain + '/v3/transform/pagebundle/to/pagebundle/')
			.set('Accept', 'application/json; profile="https://www.mediawiki.org/wiki/Specs/pagebundle/' + contentVersion + '"')
			.send({
				original: {
					title: 'Doesnotexist',
					'data-parsoid': {
						body: {
							ids: { "mwAQ": { "pi": [[{ "k": "1" }]] } },
						},
					},
					'data-mw': {
						body: {
							ids: { "mwAQ": { "parts": [{ "template": { "target": { "wt": "1x", "href": "./Template:1x" }, "params": { "1": { "wt": "hi" } }, "i": 0 } }] } },
						},
					},
					html: {
						headers: {
							'content-type': 'text/html;profile="https://www.mediawiki.org/wiki/Specs/HTML/999.0.0"',
						},
						body: '<!DOCTYPE html>\n<html><head><meta charset="utf-8"/><meta property="mw:htmlVersion" content="999.0.0"/></head><body><p about="#mwt1" typeof="mw:Transclusion" id="mwAQ">ho</p></body></html>',
					},
				},
			})
			.expect(status200)
			.expect(acceptablePageBundleResponse(contentVersion, function(html) {
				// In < 999.x, data-mw is still inline.
				html.should.match(/\s+data-mw\s*=\s*['"]/);
				html.should.not.match(/\s+data-parsoid\s*=\s*['"]/);
				var doc = domino.createDocument(html);
				var meta = doc.querySelector('meta[property="mw:html:version"], meta[property="mw:htmlVersion"]');
				meta.getAttribute('content').should.equal(contentVersion);
			}))
			.end(done);
		});

		it('should accept the original and update the redlinks', function(done) {
			if (skipForNow) { return this.skip(); }  // Create pages to fix redlinks count
			// NOTE: Keep this on an older version to show that it's preserved
			// through the transformation.
			var contentVersion = '2.0.0';
			client.req
			.post(mockDomain + '/v3/transform/pagebundle/to/pagebundle/')
			.send({
				updates: {
					redlinks: true,
				},
				original: {
					title: 'Doesnotexist',
					'data-parsoid': {
						body: {
							ids: {},
						},
					},
					html: {
						headers: {
							'content-type': 'text/html;profile="https://www.mediawiki.org/wiki/Specs/HTML/' + contentVersion + '"',
						},
						body: '<p><a rel="mw:WikiLink" href="./Special:Version" title="Special:Version">Special:Version</a> <a rel="mw:WikiLink" href="./Doesnotexist" title="Doesnotexist">Doesnotexist</a> <a rel="mw:WikiLink" href="./Redirected" title="Redirected">Redirected</a></p>',
					},
				},
			})
			.expect(acceptablePageBundleResponse(contentVersion, function(html) {
				var doc = domino.createDocument(html);
				doc.body.querySelectorAll('a').length.should.equal(3);
				var redLinks = doc.body.querySelectorAll('.new');
				redLinks.length.should.equal(1);
				redLinks[0].getAttribute('title').should.equal('Doesnotexist');
				var redirects = doc.body.querySelectorAll('.mw-redirect');
				redirects.length.should.equal(1);
				redirects[0].getAttribute('title').should.equal('Redirected');
			}))
			.end(done);
		});

		describe('Variant conversion', function() {

			it('should return unconverted nl page if sr variant conversion is requested on nl page', function(done) {
				client.req
				.post(mockDomain + '/v3/transform/pagebundle/to/pagebundle/MediaWiki:ok%2Fnl')
				.send({
					updates: {
						variant: { target: 'sr-el' },
					},
					original: {
						revid,
						html: {
							headers: {
								'content-type': 'text/html; profile="https://www.mediawiki.org/wiki/Specs/HTML/' + defaultContentVersion + '"',
							},
							body: '<p>абвг abcd</p>',
						},
					},
				})
				.expect(status200)
				.expect(validPageBundleResponse(function(doc) {
					doc.body.textContent.should.equal('абвг abcd');
				}))
				.end(done);
			});

			it('should accept the original and do variant conversion (given oldid)', function(done) {
				if (skipForNow) { return this.skip(); } // Enabled language conversion
				client.req
				.post(mockDomain + '/v3/transform/pagebundle/to/pagebundle/')
				.send({
					updates: {
						variant: { target: 'sr-el' },
					},
					original: {
						revid: 104, /* sets the pagelanguage */
						html: {
							headers: {
								'content-type': 'text/html;profile="https://www.mediawiki.org/wiki/Specs/HTML/' + defaultContentVersion + '"',
							},
							body: '<p>абвг abcd x</p>',
						},
					},
				})
				.expect(status200)
				.expect((res) => {
					// We don't actually require the result to have data-parsoid
					// if the input didn't have data-parsoid; hack the result
					// in order to make validPageBundleResponse() pass.
					res.body['data-parsoid'].body = {};
				})
				.expect(validPageBundleResponse(function(doc) {
					doc.body.textContent.should.equal('abvg abcd x');
				}))
				.expect((res) => {
					const headers = res.body.html.headers;
					headers.should.have.property('content-language');
					headers['content-language'].should.equal('sr-el');
					headers.should.have.property('vary');
					headers.vary.should.match(/\bAccept-Language\b/i);
				})
				.end(done);
			});

			it('should accept the original and do variant conversion (given pagelanguage)', function(done) {
				if (skipForNow) { return this.skip(); } // Enabled language conversion
				client.req
				.post(mockDomain + '/v3/transform/pagebundle/to/pagebundle/')
				.set('Content-Language', 'sr')
				.set('Accept-Language', 'sr-el')
				.send({
					updates: {
						variant: { /* target implicit from accept-language */ },
					},
					original: {
						html: {
							headers: {
								'content-type': 'text/html;profile="https://www.mediawiki.org/wiki/Specs/HTML/' + defaultContentVersion + '"',
							},
							body: '<p>абвг abcd</p>',
						},
					},
				})
				.expect(status200)
				.expect((res) => {
					// We don't actually require the result to have data-parsoid
					// if the input didn't have data-parsoid; hack the result
					// in order to make validPageBundleResponse() pass.
					res.body['data-parsoid'].body = {};
				})
				.expect(validPageBundleResponse(function(doc) {
					doc.body.textContent.should.equal('abvg abcd');
				}))
				.expect((res) => {
					const headers = res.body.html.headers;
					headers.should.have.property('content-language');
					headers['content-language'].should.equal('sr-el');
					headers.should.have.property('vary');
					headers.vary.should.match(/\bAccept-Language\b/i);
				})
				.end(done);
			});

			it('should not perform variant conversion w/ invalid variant (given pagelanguage)', function(done) {
				client.req
				.post(mockDomain + '/v3/transform/pagebundle/to/pagebundle/')
				.set('Content-Language', 'sr')
				.set('Accept-Language', 'sr-BOGUS')
				.send({
					updates: {
						variant: { /* target implicit from accept-language */ },
					},
					original: {
						html: {
							headers: {
								'content-type': 'text/html;profile="https://www.mediawiki.org/wiki/Specs/HTML/' + defaultContentVersion + '"',
							},
							body: '<p>абвг abcd</p>',
						},
					},
				})
				.expect(status200)
				.expect((res) => {
					// We don't actually require the result to have data-parsoid
					// if the input didn't have data-parsoid; hack the result
					// in order to make validPageBundleResponse() pass.
					res.body['data-parsoid'].body = {};
				})
				.expect(validPageBundleResponse(function(doc) {
					doc.body.textContent.should.equal('абвг abcd');
				}))
				.expect((res) => {
					const headers = res.body.html.headers;
					headers.should.have.property('content-language');
					headers['content-language'].should.equal('sr');
					headers.should.have.property('vary');
					headers.vary.should.match(/\bAccept-Language\b/i);
				})
				.end(done);
			});

		});

	});  // end pb2pb

	describe( 'roundtrip-test', function () {
		// This mirrors the behavior of the runTests function in roundtrip-test.js

		const transformOptions = {
			// For compatibility with Parsoid/PHP service, per roundtrip-test.js
			offsetType: 'ucs2'
		};

		it( 'Roundtrip should convert wikitext to HTML and back, with selser working', async () => {
			// get the wikitext of the page
			const { text: oldWt } = await client.req
				.get( mockDomain + `/v3/page/wikitext/${pageEncoded}` );

			// First, fetch the HTML for the requested page's wikitext
			const wt2html = await client.req
				.post( mockDomain + `/v3/transform/wikitext/to/pagebundle/${pageEncoded}` )
				.send( Object.assign( {
					wikitext: oldWt,
				}, transformOptions ) );

			assert.equal( 200, wt2html.statusCode, wt2html.text );

			// Now, request the wikitext for the obtained HTML, without providing the original HTML
			const pb2wt1 = await client.req
				.post( mockDomain + `/v3/transform/pagebundle/to/wikitext/${pageEncoded}` )
				.send( Object.assign( {
					html: wt2html.body.html,
				}, transformOptions ) );

			assert.equal( 200, pb2wt1.statusCode, pb2wt1.text );

			// This might fail, since we are not applying selser.
			// We are trusting that the conversion back to wikitext will be clean.
			assert.equal( pb2wt1.text, oldWt );

			// Now, request the wikitext for the obtained HTML, with Selser
			var newDocument = DOMUtils.parseHTML( wt2html.body.html.body );
			var newNode = newDocument.createComment('rtSelserEditTestComment');
			newDocument.body.appendChild(newNode);

			const pb2wt2 = await client.req
				.post( mockDomain + `/v3/transform/pagebundle/to/wikitext/${pageEncoded}` )
				.send( {
					html: newDocument.outerHTML,
					oldid: revid,
					original: {
						'data-parsoid': wt2html.body['data-parsoid'],
						'data-mw': wt2html.body['data-mw'],
						wikitext: { body: oldWt },
						html: wt2html.body.html,
					},
					...transformOptions
				} );

			assert.equal( 200, pb2wt2.statusCode, pb2wt2.text );

			// Check that the comment was included in the wikitext
			assert.include( pb2wt2.text, 'rtSelserEditTestComment' );

			// Remove the selser trigger comment
			const actual2 = pb2wt2.text.replace(/<!--rtSelserEditTestComment-->\n*$/, '');

			assert.equal( actual2, oldWt );
		} );

	} );

	describe( 'ETags', function () {

		it( '/transform/html must accept the ETag in the If-Match header, if one is returned by /page/html (T238849)', async () => {
			const { statusCode: status1, headers: headers1, text: text1 } = await client.req
				.get( mockDomain + `/v3/page/html/${page}/${revid}` )
				.query( { stash: 'yes' } );

			assert.deepEqual( status1, 200, text1 );

			if ( !headers1.etag ) {
				// This is a structure test that is asserting one of two acceptable situations:
				// Either, the page endpoint doesn't return an ETag. This implies that the
				// HTML wasn't stored anywhere, so it can't be re-used.
				// Or the transform endpoint accepts the ETag returned by the page endpoint.
				// This ensures that clients can loop through any ETags they receive.
				return;
			}

			// The request above should have stashed a rendering associated with the ETag it
			// returned. Pass the ETag in the If-Match header.
			const { statusCode: status2, text: text2 } = await client.req
				.post( mockDomain + `/v3/transform/html/to/wikitext/${page}/${revid}` )
				.set( 'If-Match', headers1.etag )
				.send( {
					html: text1
				} );

			// Just check we got a 200. The returned wikitext may or may not be "dirty".
			assert.deepEqual( status2, 200, text2 );
		} );

	} );
});
