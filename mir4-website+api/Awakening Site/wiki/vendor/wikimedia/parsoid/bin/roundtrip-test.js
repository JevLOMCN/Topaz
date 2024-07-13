#!/usr/bin/env node

'use strict';

require('../core-upgrade.js');
require('colors');

var entities = require('entities');
var fs = require('fs');
var yargs = require('yargs');
var zlib = require('pn/zlib');

var Promise = require('../lib/utils/promise.js');
var Util = require('../lib/utils/Util.js').Util;
var ScriptUtils = require('../tools/ScriptUtils.js').ScriptUtils;
var ContentUtils = require('../lib/utils/ContentUtils.js').ContentUtils;
var DOMUtils = require('../lib/utils/DOMUtils.js').DOMUtils;
var DOMDataUtils = require('../lib/utils/DOMDataUtils.js').DOMDataUtils;
var TestUtils = require('../tests/TestUtils.js').TestUtils;
var WTUtils = require('../lib/utils/WTUtils.js').WTUtils;
var ParsoidConfig = require('../lib/config/ParsoidConfig.js').ParsoidConfig;
var Diff = require('../lib/utils/Diff.js').Diff;
var JSUtils = require('../lib/utils/jsutils.js').JSUtils;
var MockEnv = require('../tests/MockEnv.js').MockEnv;

var defaultContentVersion = '2.8.0';

var MAX_RETRIES = 10;

function displayDiff(type, count) {
	var pad = (10 - type.length);  // Be positive!
	type = type[0].toUpperCase() + type.slice(1);
	return type + ' differences' + ' '.repeat(pad) + ': ' + count + '\n';
}

var jsonFormat = function(error, prefix, title, results, profile) {
	var diffs = {
		html2wt: { semantic: 0, syntactic: 0 },
		selser: { semantic: 0, syntactic: 0 },
	};
	if (!error) {
		results.forEach(function(result) {
			var mode = diffs[result.selser ? 'selser' : 'html2wt'];
			mode[result.type === 'fail' ? 'semantic' : 'syntactic']++;
		});
	}
	return {
		error: error,
		results: diffs,
	};
};

var plainFormat = function(err, prefix, title, results, profile) {
	var testDivider = '='.repeat(70) + '\n';
	var diffDivider = '-'.repeat(70) + '\n';
	var output = '';

	if (err) {
		output += 'Parser failure!\n\n';
		output += diffDivider;
		output += err;
		if (err.stack) {
			output += '\nStack trace: ' + err.stack;
		}
	} else {
		var diffs = {
			html2wt: { semantic: 0, syntactic: 0 },
			selser: { semantic: 0, syntactic: 0 },
		};
		for (var i = 0; i < results.length; i++) {
			var result = results[i];
			output += testDivider;
			if (result.type === 'fail') {
				output += 'Semantic difference' +
					(result.selser ? ' (selser)' : '') + ':\n\n';
				output += result.wtDiff + '\n';
				output += diffDivider + 'HTML diff:\n\n' +
					result.htmlDiff + '\n';
				diffs[result.selser ? 'selser' : 'html2wt'].semantic++;
			} else {
				output += 'Syntactic difference' +
					(result.selser ? ' (selser)' : '') + ':\n\n';
				output += result.wtDiff + '\n';
				diffs[result.selser ? 'selser' : 'html2wt'].syntactic++;
			}
		}
		output += testDivider;
		output += testDivider;
		output += 'SUMMARY:\n';
		output += diffDivider;
		var total = 0;
		Object.keys(diffs).forEach(function(diff) {
			output += diff + '\n';
			output += diffDivider;
			Object.keys(diffs[diff]).forEach(function(type) {
				var count = diffs[diff][type];
				total += count;
				output += displayDiff(type, count);
			});
			output += diffDivider;
		});
		output += displayDiff('all', total);
		output += testDivider;
		output += testDivider;
	}

	return output;
};

var xmlFormat = function(err, prefix, title, results, profile) {
	var i, result;
	var article = Util.escapeHtml(prefix + ':' + title);
	var output = '<testsuites>\n';
	var outputTestSuite = function(selser) {
		output += '<testsuite name="Roundtrip article ' + article;
		if (selser) {
			output += ' (selser)';
		}
		output += '">\n';
	};

	if (err) {
		outputTestSuite(false);
		output += '<testcase name="entire article">';
		output += '<error type="parserFailedToFinish">';
		output += Util.escapeHtml(err.stack || err.toString());
		output += '</error></testcase>';
	} else if (!results.length) {
		outputTestSuite(false);
	} else {
		var currentSelser = results[0].selser;
		outputTestSuite(currentSelser);
		for (i = 0; i < results.length; i++) {
			result = results[i];

			// When going from normal to selser results, switch to a new
			// test suite.
			if (currentSelser !== result.selser) {
				output += '</testsuite>\n';
				currentSelser = result.selser;
				outputTestSuite(currentSelser);
			}

			output += '<testcase name="' + article;
			output += ' character ' + result.offset[0].start + '">\n';

			if (result.type === 'fail') {
				output += '<failure type="significantHtmlDiff">\n';

				output += '<diff class="wt">\n';
				output += Util.escapeHtml(result.wtDiff);
				output += '\n</diff>\n';

				output += '<diff class="html">\n';
				output += Util.escapeHtml(result.htmlDiff);
				output += '\n</diff>\n';

				output += '</failure>\n';
			} else {
				output += '<skipped type="insignificantWikitextDiff">\n';
				output += Util.escapeHtml(result.wtDiff);
				output += '\n</skipped>\n';
			}

			output += '</testcase>\n';
		}
	}
	output += '</testsuite>\n';

	// Output the profiling data
	if (profile) {
		// Delete the start time to avoid serializing it
		if (profile.time && profile.time.start) {
			delete profile.time.start;
		}
		output += '<perfstats>\n';
		Object.keys(profile).forEach(function(type) {
			Object.keys(profile[type]).forEach(function(prop) {
				output += '<perfstat type="' + TestUtils.encodeXml(type) + ':';
				output += TestUtils.encodeXml(prop);
				output += '">';
				output += TestUtils.encodeXml(profile[type][prop].toString());
				output += '</perfstat>\n';
			});
		});
		output += '</perfstats>\n';
	}
	output += '</testsuites>';

	return output;
};

// Find the subset of leaf/non-leaf nodes whose DSR ranges
// span the wikitext range provided as input.
var findMatchingNodes = function(node, range) {
	console.assert(DOMUtils.isElt(node));

	// Skip subtrees that are outside our target range
	var dp = DOMDataUtils.getDataParsoid(node);
	if (!Util.isValidDSR(dp.dsr) || dp.dsr[0] > range.end || dp.dsr[1] < range.start) {
		return [];
	}

	// If target range subsumes the node, we are done.
	if (dp.dsr[0] >= range.start && dp.dsr[1] <= range.end) {
		return [node];
	}

	// Cannot inspect template content subtree at a finer grained level
	if (WTUtils.isFirstEncapsulationWrapperNode(node)) {
		return [node];
	}

	// Cannot inspect image subtree at a finer grained level
	var typeOf = node.getAttribute('typeof') || '';
	if (/\bmw:File(\/|\s|$)/.test(typeOf) && /^(FIGURE|SPAN)$/.test(node.nodeName)) {
		return [node];
	}

	// We are in the target range -- examine children.
	// 1. Walk past nodes that are before our desired range.
	// 2. Collect nodes within our desired range.
	// 3. Stop walking once you move beyond the desired range.
	var elts = [];
	var offset = dp.dsr[0];
	var c = node.firstChild;
	while (c) {
		if (DOMUtils.isElt(c)) {
			dp = DOMDataUtils.getDataParsoid(c);
			var dsr = dp.dsr;
			if (Util.isValidDSR(dsr)) {
				if (dsr[1] >= range.start) {
					// We have an overlap!
					elts = elts.concat(findMatchingNodes(c, range));
				}
				offset = dp.dsr[1];
			} else {
				// SSS FIXME: This is defensive coding here.
				//
				// This should not happen really anymore.
				// DSR computation is fairly solid now and
				// shouldn't be leaving holes.
				//
				// If we see no errors in rt-testing runs,
				// I am going to rip this out.

				console.log("error/diff", "Bad dsr for " + c.nodeName + ": "
					+ c.outerHTML.slice(0, 50));

				if (dp.dsr && typeof (dsr[1]) === 'number') {
					// We can cope in this case
					if (dsr[1] >= range.start) {
						// Update dsr[0]
						dp.dsr[0] = offset;

						// We have an overlap!
						elts = elts.concat(findMatchingNodes(c, range));
					}
					offset = dp.dsr[1];
				} else if (offset >= range.start) {
					// Swallow it wholesale rather than try
					// to find finer-grained matches in the subtree
					elts.push(c);

					// offset will now be out-of-sync till we hit
					// another element with a valid DSR[1] value.
				}
			}
		} else {
			var len = DOMUtils.isText(c) ? c.nodeValue.length : WTUtils.decodedCommentLength(c);
			if (offset + len >= range.start) {
				// We have an overlap!
				elts.push(c);
			}
			offset += len;
		}

		// All done!
		if (offset > range.end) {
			break;
		}

		// Skip over encapsulated content
		if (WTUtils.isFirstEncapsulationWrapperNode(c)) {
			c = WTUtils.skipOverEncapsulatedContent(c);
		} else {
			c = c.nextSibling;
		}
	}

	return elts;
};

function stripTranscludedWhitespaceSpans(node) {
	while (node) {
		const sibling = node.nextSibling;
		if (DOMUtils.isElt(node)) {
			const about = node.getAttribute('about');
			const nTypeOf = node.getAttribute('typeof') || '';

			// remove whitespace spans that are first nodes of a transclusion,
			// have whitespace content, and transfer attributes to their sibling.
			if (node.nodeName === 'SPAN' &&
				/^\s*$/.test(node.textContent) &&
				/mw:Transclusion/.test(nTypeOf) &&
				DOMUtils.isElt(sibling) &&
				sibling.getAttribute('about') === about
			) {
				var sTypeOf = sibling.getAttribute('typeof') || '';
				if (sTypeOf) {
					sTypeOf = sTypeOf + ' ' + nTypeOf;
				} else {
					sTypeOf = nTypeOf;
				}
				sibling.setAttribute('typeof', sTypeOf);
				sibling.setAttribute('data-mw', node.getAttribute('data-mw'));
				sibling.setAttribute('data-parsoid', node.getAttribute('data-parsoid'));

				const whitespace = node.ownerDocument.createTextNode(node.textContent);
				node.parentNode.replaceChild(whitespace, node);

				// Skip transclusion nodes
				node = sibling;
				while (node && DOMUtils.isElt(node) && node.getAttribute('about') === about) {
					node = node.nextSibling;
				}
			} else if (/mw:Transclusion/.test(nTypeOf)) {
				node = WTUtils.skipOverEncapsulatedContent(node);
				// No skipping to nextSibling here!
			} else if (node.firstChild) {
				stripTranscludedWhitespaceSpans(node.firstChild);
				node = sibling;
			} else {
				node = sibling;
			}
		} else {
			node = sibling;
		}
	}
}

var getMatchingHTML = function(body, offsetRange, nlDiffs) {
	// If the diff context straddles a template boundary (*) and if
	// the HTML context includes the template content in only one
	// the new/old DOMs, we can falsely flag this as a semantic
	// diff. To improve the possibility of including the template
	// content in both DOMs, expand range at both ends by 1 char.
	//
	// (*) This happens because our P-wrapping code occasionally
	//     swallows newlines into template context.
	// See https://phabricator.wikimedia.org/T89628
	if (nlDiffs) {
		offsetRange.start -= 1;
		offsetRange.end += 1;
	}

	var html = '';
	var out = findMatchingNodes(body, offsetRange);
	for (var i = 0; i < out.length; i++) {
		// node need not be an element always!
		const node = out[i];
		DOMDataUtils.visitAndStoreDataAttribs(node);
		html += ContentUtils.toXML(node, { smartQuote: false });
		DOMDataUtils.visitAndLoadDataAttribs(node);
	}
	html = TestUtils.normalizeOut(html);

	// Normalize away <br/>'s added by Parsoid because of newlines in wikitext.
	// Do this always, not just when nlDiffs is true, because newline diffs
	// can show up at extremities of other wt diffs.
	return html.replace(/<p>\s*<br\s*\/?>\s*/g, '<p>').replace(/<p><\/p>/g, '').replace(/(^\s+|\s+$)/g, '');
};

/* This doesn't try to do a really thorough job of normalization and misses a number
 * of scenarios, for example, anywhere where sol-transparent markup like comments,
 * noinclude, category links, etc. are present.
 *
 * On the flip side, it can occasionally do incorrect normalization when this markup
 * is present in extension blocks (nowiki, syntaxhighlight, etc.) where this text
 * is not really interpreted as wikitext.
 */
function normalizeWikitext(wt, opts) {
	if (opts.preDiff) {
		// Whitespace in ordered, unordered, definition lists
		// Whitespace in first table cell/header, row, and caption
		wt = wt.replace(/^([*#:;]|\|[-+|]?|!!?)[ \t]*(.*?)[ \t]*$/mg, "$1$1");

		// Whitespace in headings
		wt = wt.replace(/^(=+)[ \t]*([^\n]*?)[ \t]*(=+)[ \t]*$/mg, "$1$2$3");
	}

	if (opts.newlines) {
		// Normalize newlines before/after headings
		wt = wt.replace(/\n*(\n=[^\n]*=$\n)\n*/mg, "$1");

		// Normalize newlines before lists
		wt = wt.replace(/(^[^*][^\n]*$\n)\n+([*])/mg, "$1$2");
		wt = wt.replace(/(^[^#][^\n]*$\n)\n+([#])/mg, "$1$2");
		wt = wt.replace(/(^[^:][^\n]*$\n)\n+([:])/mg, "$1$2");
		wt = wt.replace(/(^[^;][^\n]*$\n)\n+([;])/mg, "$1$2");

		// Normalize newlines after lists
		wt = wt.replace(/(^[*][^\n]*$\n)\n+([^*])/mg, "$1$2");
		wt = wt.replace(/(^[#][^\n]*$\n)\n+([^#])/mg, "$1$2");
		wt = wt.replace(/(^[:][^\n]*$\n)\n+([^:])/mg, "$1$2");
		wt = wt.replace(/(^[;][^\n]*$\n)\n+([^;])/mg, "$1$2");

		// Normalize newlines before/after tables
		wt = wt.replace(/\n+(\n{\|)/mg, "$1");
		wt = wt.replace(/(\|}\n)\n+/mg, "$1");

		// Strip leading & trailing newlines
		wt = wt.replace(/^\n+|\n$/, '');
	}

	if (opts.postDiff) {
		// Ignore leading tabs vs. leading spaces
		wt = wt.replace(/^\t/, ' ');
		wt = wt.replace(/\n\t/g, '\n ');
		// Normalize multiple spaces to single space
		wt = wt.replace(/ +/g, ' ');
		// Ignore capitalization of tags and void tag indications
		wt = wt.replace(/<(\/?)([^ >\/]+)((?:[^>\/]|\/(?!>))*)\/?>/g,
			function(match, close, name, remaining) {
				return '<' + close + name.toLowerCase() +
					remaining.replace(/ $/, '') + '>';
			});
		// Ignore whitespace in table cell attributes
		wt = wt.replace(/(^|\n|\|(?=\|)|!(?=!))(\{\||\|[\-+]*|!) *([^|\n]*?) *(?=[|\n]|$)/g, '$1$2$3');
		// Ignore trailing semicolons and spaces in style attributes
		wt = wt.replace(/style\s*=\s*"[^"]+"/g, function(match) {
			return match.replace(/\s|;(?=")/g, '');
		});
		// Strip double-quotes
		wt = wt.replace(/"([^"]*?)"/g, '$1');
		// Ignore implicit </small> and </center> in table cells or the end
		// of the wting for now
		wt = wt.replace(/(^|\n)<\/(?:small|center)>(?=\n[|!]|\n?$)/g, '');
		wt = wt.replace(/([|!].*?)<\/(?:small|center)>(?=\n[|!]|\n?$)/gi, '$1');
	}

	return wt;
}

// Get diff slices from offsets
var formatDiff = function(oldWt, newWt, offset, context) {
	return [
		'------',
		oldWt.slice(offset[0].start - context, offset[0].start).blue +
		oldWt.slice(offset[0].start, offset[0].end).green +
		oldWt.slice(offset[0].end, offset[0].end + context).blue,
		'++++++',
		newWt.slice(offset[1].start - context, offset[1].start).blue +
		newWt.slice(offset[1].start, offset[1].end).red +
		newWt.slice(offset[1].end, offset[1].end + context).blue,
	].join('\n');
};

function stripElementIds(node) {
	while (node) {
		if (DOMUtils.isElt(node)) {
			var id = node.getAttribute('id') || '';
			if (/^mw[\w-]{2,}$/.test(id)) {
				node.removeAttribute('id');
			}
			if (node.firstChild) {
				stripElementIds(node.firstChild);
			}
		}
		node = node.nextSibling;
	}
}

function genSyntacticDiffs(data) {
	var results = [];
	var diff = Diff.diffLines(data.oldWt, data.newWt);
	var offsets = Diff.convertDiffToOffsetPairs(diff, data.oldLineLengths, data.newLineLengths);
	for (var i = 0; i < offsets.length; i++) {
		var offset = offsets[i];
		results.push({
			type: 'skip',
			offset: offset,
			wtDiff: formatDiff(data.oldWt, data.newWt, offset, 0),
		});
	}
	return results;
}

function normalizeDocumentHTML(body) {
	// Strip whitspace spans that are first elements of a transclusion
	stripTranscludedWhitespaceSpans(body);

	// Strip 'mw..' ids from the DOMs. This matters for 2 scenarios:
	// * reduces noise in visual diffs
	// * all other things being equal after normalization, we don't
	//   assume DOMs are different simply because ids are different
	stripElementIds(body);

	// Strip section tags from the DOMs
	ContentUtils.stripUnnecessaryWrappersAndFallbackIds(body);
}

var checkIfSignificant = function(offsets, data) {
	var oldWt = data.oldWt;
	var newWt = data.newWt;

	const dummyEnv = new MockEnv({}, null);

	var oldBody = dummyEnv.createDocument(data.oldHTML.body).body;
	var newBody = dummyEnv.createDocument(data.newHTML.body).body;

	// Merge pagebundles so that HTML nodes can be compared and diff'ed.
	DOMDataUtils.applyPageBundle(oldBody.ownerDocument, {
		parsoid: data.oldDp.body,
		mw: data.oldMw && data.oldMw.body,
	});
	DOMDataUtils.applyPageBundle(newBody.ownerDocument, {
		parsoid: data.newDp.body,
		mw: data.newMw && data.newMw.body,
	});

	normalizeDocumentHTML(oldBody.ownerDocument.body);
	normalizeDocumentHTML(newBody.ownerDocument.body);

	var i, offset;
	var results = [];
	// Use the full tests for fostered content.
	// Fostered/misnested content => semantic diffs.
	if (!/("|&quot;)(fostered|misnested)("|&quot;)\s*:\s*true\b/.test(oldBody.outerHTML)) {
		// Quick test for no semantic diffs
		// If parsoid-normalized HTML for old and new wikitext is identical,
		// the wt-diffs are purely syntactic.
		//
		// FIXME: abstract to ensure same opts are used for parsoidPost and normalizeOut
		const normOpts = {
			parsoidOnly: true,
			// Eliminate spurious semantic errors that may arise because
			// of the normalization done to new html before it got serialized.
			// For example,
			//   "== ==" will parse to "<h2><h2>" and then serialize to ""
			//
			// FIXME: Normally we would only run this on the old DOM since that is
			// sufficient. BUT, for links with trailing whitespace like [[Foo ]],
			// wt2wt has special handling to use syntactic variations from data-parsoid
			// independent of what the DOM iself says. Try wt2wt on that wikitext to verify.
			// But, till such time we strip data-parsoid based syntactic variations in
			// link handlers, run DOM normalizations on both old and new HTML
			// so that we don't report spurious semantic diffs because of this.
			// In any case, DOM normalizations are idempotent and so at best, rerunning
			// DOM normalization is wasteful and not harmful.
			hackyNormalize: true
		};
		const normalizedOld = TestUtils.normalizeOut(oldBody, normOpts);
		const normalizedNew = TestUtils.normalizeOut(newBody, normOpts);
		if (normalizedOld === normalizedNew) {
			return genSyntacticDiffs(data);
		} else {
			// Uncomment to log the cause of the failure.  This is often useful
			// for determining the root of non-determinism in rt.  See T151474
			// console.log(Diff.diffLines(normalizedOld, normalizedNew));
		}
	}

	/*
	console.log("---------OLD DOC HTML---------\n" + oldBody.ownerDocument.body.innerHTML);
	console.log("---------NEW DOC HTML---------\n" + newBody.ownerDocument.body.innerHTML);
	*/

	// FIXME: In this code path below, the returned diffs might
	// underreport syntactic diffs since these are based on
	// diffs on normalized wikitext. Unclear how to tackle this.

	// Do this after the quick test above because in `parsoidOnly`
	// normalization, data-mw is not stripped.
	DOMDataUtils.visitAndLoadDataAttribs(oldBody);
	DOMDataUtils.visitAndLoadDataAttribs(newBody);

	// Now, proceed with full blown diffs
	for (i = 0; i < offsets.length; i++) {
		offset = offsets[i];
		var thisResult = { offset: offset };

		// Default: syntactic diff + no diff context
		thisResult.type = 'skip';
		thisResult.wtDiff = formatDiff(oldWt, newWt, offset, 0);

		// Is this a newline separator diff?
		var oldStr = oldWt.slice(offset[0].start, offset[0].end);
		var newStr = newWt.slice(offset[1].start, offset[1].end);
		var nlDiffs = /^\s*$/.test(oldStr) && /^\s*$/.test(newStr)
			&& (/\n/.test(oldStr) || /\n/.test(newStr));

		// Check if this is really a semantic diff
		var oldHTML = getMatchingHTML(oldBody, offset[0], nlDiffs);
		var newHTML = getMatchingHTML(newBody, offset[1], nlDiffs);
		var diff = Diff.patchDiff(oldHTML, newHTML);
		if (diff !== null) {
			// Normalize wts to check if we really have a semantic diff
			var wt1 = normalizeWikitext(oldWt.slice(offset[0].start, offset[0].end), { newlines: true, postDiff: true });
			var wt2 = normalizeWikitext(newWt.slice(offset[1].start, offset[1].end), { newlines: true, postDiff: true });
			if (wt1 !== wt2) {
				// Syntatic diff + provide context for semantic diffs
				thisResult.type = 'fail';
				thisResult.wtDiff = formatDiff(oldWt, newWt, offset, 25);

				// Don't clog the rt-test server db with humongous diffs
				if (diff.length > 1000) {
					diff = diff.slice(0, 1000) + "-- TRUNCATED TO 1000 chars --";
				}
				thisResult.htmlDiff = diff;
			}
		}
		results.push(thisResult);
	}

	return results;
};

var UA = 'Roundtrip-Test';

var parsoidPost = Promise.async(function *(profile, options) {
	var httpOptions = {
		method: 'POST',
		body: options.data,
		headers: {
			'User-Agent': UA,
		},
	};
	// For compatibility with Parsoid/PHP service
	httpOptions.body.offsetType = 'ucs2';

	var uri = options.uri + 'transform/';
	if (options.html2wt) {
		uri += 'pagebundle/to/wikitext/' + options.title;
		if (options.oldid) {
			uri += '/' + options.oldid;
		}
		// We want to encode the request but *not* decode the response.
		httpOptions.body = JSON.stringify(httpOptions.body);
		httpOptions.headers['Content-Type'] = 'application/json';
	} else {  // wt2html
		uri += 'wikitext/to/pagebundle/' + options.title;
		if (options.oldid) {
			uri += '/' + options.oldid;
		}
		httpOptions.headers.Accept = 'application/json; charset=utf-8; profile="https://www.mediawiki.org/wiki/Specs/pagebundle/' + options.outputContentVersion + '"';
		// setting json here encodes the request *and* decodes the response.
		httpOptions.json = true;
	}
	httpOptions.uri = uri;
	httpOptions.proxy = options.proxy;

	var result = yield issueRequest(httpOptions);
	var body = result[1];

	// FIXME: Parse time was removed from profiling when we stopped
	// sending the x-parsoid-performance header.
	if (options.recordSizes) {
		var pre = '';
		if (options.profilePrefix) {
			pre += options.profilePrefix + ':';
		}
		var str;

		// Detect standard error response
		if ( typeof body === 'object' && body.httpCode >= 400 ) {
			throw new Error('Received error: ' + body.reason);
		}

		if (options.html2wt) {
			pre += 'wt:';
			str = body;
		} else {
			pre += 'html:';
			str = body.html.body;
		}

		profile.size[pre + 'raw'] = str.length;
		// Compress to record the gzipped size
		var gzippedbuf = yield zlib.gzip(str);
		profile.size[pre + 'gzip'] = gzippedbuf.length;
	}
	return body;
});

function genLineLengths(str) {
	return str.split(/^/m).map(function(l) {
		return l.length;
	});
}

var roundTripDiff = Promise.async(function *(profile, parsoidOptions, data) {
	var normOpts = { preDiff: true, newlines: true };
	data.oldLineLengths = genLineLengths(data.oldWt);
	data.newLineLengths = genLineLengths(data.newWt);

	// Newline normalization to see if we can get to identical wt.
	var wt1 = normalizeWikitext(data.oldWt, normOpts);
	var wt2 = normalizeWikitext(data.newWt, normOpts);
	if (wt1 === wt2) {
		return genSyntacticDiffs(data);
	}

	// Do another diff without normalizations
	// More conservative normalization this time around
	normOpts.newlines = false;
	wt1 = normalizeWikitext(data.oldWt, normOpts);
	wt2 = normalizeWikitext(data.newWt, normOpts);
	var diff = Diff.diffLines(wt1, wt2);
	var offsets = Diff.convertDiffToOffsetPairs(diff, data.oldLineLengths, data.newLineLengths);
	if (!offsets.length) {
		// FIXME: Can this really happen??
		return genSyntacticDiffs(data);
	}

	var contentmodel = data.contentmodel || 'wikitext';
	var options = Object.assign({
		wt2html: true,
		data: { wikitext: data.newWt, contentmodel: contentmodel },
	}, parsoidOptions);
	var body = yield parsoidPost(profile, options);
	data.newHTML = body.html;
	data.newDp = body['data-parsoid'];
	data.newMw = body['data-mw'];
	return checkIfSignificant(offsets, data);
});

// Custom httpClient global variable that can be set by ci tests
var httpClient;

var issueRequest = function(httpOptions) {
	if (httpClient) {
		return httpClient.request(httpOptions);
	} else {
		return ScriptUtils.retryingHTTPRequest(MAX_RETRIES, httpOptions);
	}
};

// Returns a Promise for a object containing a formatted string and an
// exitCode.
var runTests = Promise.async(function *(title, options, formatter) {
	// Only support lookups for WMF domains.  At some point we should rid
	// ourselves of prefixes in this file entirely, but that'll take some
	// coordination in rt.
	var parsoidConfig = new ParsoidConfig(null, { loadWMF: true });

	var domain = options.domain;
	var prefix = options.prefix;

	if (options.httpClient) {
		httpClient = options.httpClient;
	}

	// Preserve the default, but only if neither was provided.
	if (!prefix && !domain) { domain = 'en.wikipedia.org'; }

	if (domain && prefix) {
		// All good.
	} else if (!domain && prefix) {
		// Get the domain from the mw api map.
		if (parsoidConfig.mwApiMap.has(prefix)) {
			domain = parsoidConfig.mwApiMap.get(prefix).domain;
		} else {
			throw new Error('Couldn\'t find the domain for prefix: ' + prefix);
		}
	} else if (!prefix && domain) {
		// Get the prefix from the reverse mw api map.
		prefix = parsoidConfig.getPrefixFor(domain);
		if (!prefix) {
			// Bogus, but `prefix` is only used for reporting.
			prefix = domain;
		}
	} else {
		// Should be unreachable.
		throw new Error('No domain or prefix provided.');
	}

	const uriOpts = options.parsoidURLOpts;
	let uri = uriOpts.baseUrl;
	let proxy;
	if (uriOpts.proxy) {
		proxy = uriOpts.proxy.host;
		if (uriOpts.proxy.port) {
			proxy += ":" + uriOpts.proxy.port;
		}
		// Special support for the WMF cluster
		uri = uri.replace(/DOMAIN/, domain);
	}

	// make sure the Parsoid URI ends on /
	if (!/\/$/.test(uri)) {
		uri += '/';
	}
	var parsoidOptions = {
		uri: uri + domain + '/v3/',
		proxy: proxy,
		title: encodeURIComponent(title),
		outputContentVersion: options.outputContentVersion || defaultContentVersion,
	};
	var uri2 = parsoidOptions.uri + 'page/wikitext/' + parsoidOptions.title;
	if (options.oldid) {
		uri2 += '/' + options.oldid;
	}

	var profile = { time: { total: 0, start: 0 }, size: {} };
	var data = {};
	var error;
	var exitCode;
	try {
		var opts;
		var req = yield issueRequest({
			method: 'GET',
			uri: uri2,
			proxy: proxy,
			headers: {
				'User-Agent': UA,
			},
		});
		profile.time.start = JSUtils.startTime();
		// We may have been redirected to the latest revision.  Record the
		// oldid for later use in selser.
		data.oldid = req[0].request.path.replace(/^(.*)\//, '');
		data.oldWt = req[1];
		data.contentmodel = req[0].headers['x-contentmodel'] || 'wikitext';
		// First, fetch the HTML for the requested page's wikitext
		opts = Object.assign({
			wt2html: true,
			recordSizes: true,
			data: { wikitext: data.oldWt, contentmodel: data.contentmodel },
		}, parsoidOptions);
		var body = yield parsoidPost(profile, opts);

		// Check for wikitext redirects
		const redirectMatch = body.html.body.match(/<link rel="mw:PageProp\/redirect" href="([^"]*)"/);
		if (redirectMatch) {
			const target = Util.decodeURIComponent(entities.decodeHTML5(redirectMatch[1].replace(/^(\.\/)?/, '')));
			// Log this so we can collect these and update the database titles
			console.error(`REDIRECT: ${prefix}:${title.replace(/"/g, '\\"')} -> ${prefix}:${target.replace(/"/g, '\\"')}`);
			return yield runTests(target, options, formatter);
		}

		data.oldHTML = body.html;
		data.oldDp = body['data-parsoid'];
		data.oldMw = body['data-mw'];
		// Now, request the wikitext for the obtained HTML
		opts = Object.assign({
			html2wt: true,
			recordSizes: true,
			data: {
				html: data.oldHTML.body,
				contentmodel: data.contentmodel,
				original: {
					'data-parsoid': data.oldDp,
					'data-mw': data.oldMw,
				},
			},
		}, parsoidOptions);
		data.newWt = yield parsoidPost(profile, opts);
		data.diffs = yield roundTripDiff(profile, parsoidOptions, data);
		// Once we have the diffs between the round-tripped wt,
		// to test rt selser we need to modify the HTML and request
		// the wt again to compare with selser, and then concat the
		// resulting diffs to the ones we got from basic rt
		var newDocument = DOMUtils.parseHTML(data.oldHTML.body);
		var newNode = newDocument.createComment('rtSelserEditTestComment');
		newDocument.body.appendChild(newNode);
		opts = Object.assign({
			html2wt: true,
			useSelser: true,
			oldid: data.oldid,
			data: {
				html: newDocument.outerHTML,
				contentmodel: data.contentmodel,
				original: {
					'data-parsoid': data.oldDp,
					'data-mw': data.oldMw,
					wikitext: { body: data.oldWt },
					html: data.oldHTML,
				},
			},
			profilePrefix: 'selser',
		}, parsoidOptions);
		var out = yield parsoidPost(profile, opts);
		// Finish the total time now
		// FIXME: Is the right place to end it?
		profile.time.total = JSUtils.elapsedTime(profile.time.start);
		// Remove the selser trigger comment
		data.newWt = out.replace(/<!--rtSelserEditTestComment-->\n*$/, '');
		var selserDiffs = yield roundTripDiff(profile, parsoidOptions, data);
		selserDiffs.forEach(function(diff) {
			diff.selser = true;
		});
		if (selserDiffs.length) {
			data.diffs = data.diffs.concat(selserDiffs);
			exitCode = 1;
		} else {
			exitCode = 0;
		}
	} catch (e) {
		error = e;
		exitCode = 1;
	}

	var output = formatter(error, prefix, title, data.diffs, profile);

	return {
		output: output,
		exitCode: exitCode
	};
});


if (require.main === module) {
	var standardOpts = {
		xml: {
			description: 'Use xml callback',
			boolean: true,
			default: false,
		},
		prefix: {
			description: 'Deprecated.  Please provide a domain.',
			boolean: false,
			default: '',
		},
		domain: {
			description: 'Which wiki to use; e.g. "en.wikipedia.org" for' +
				' English wikipedia',
			boolean: false,
			default: '',  // Add a default when `prefix` is removed.
		},
		oldid: {
			description: 'Optional oldid of the given page. If not given,' +
				' will use the latest revision.',
			boolean: false,
			default: null,
		},
		parsoidURL: {
			description: 'The URL for the Parsoid API',
			boolean: false,
			default: '',
		},
		proxyURL: {
			description: 'URL (with protocol and port, if any) for the proxy fronting Parsoid',
			boolean: false,
			default: null,
		},
		apiURL: {
			description: 'http path to remote API,' +
				' e.g. http://en.wikipedia.org/w/api.php',
			boolean: false,
			default: '',
		},
		outputContentVersion: {
			description: 'The acceptable content version.',
			boolean: false,
			default: defaultContentVersion,
		},
		check: {
			description: 'Exit with non-zero exit code if differences found using selser',
			boolean: true,
			default: false,
			alias: 'c',
		},
	};

	Promise.async(function *() {
		var opts = yargs
		.usage(
			'Usage: $0 [options] <page-title> \n' +
			'The page title should be the "true title",' +
			'i.e., without any url encoding which might be necessary if it appeared in wikitext.' +
			'\n\n'
		)
		.options(standardOpts)
		.strict();

		var argv = opts.argv;
		if (!argv._.length) {
			return opts.showHelp();
		}
		var title = String(argv._[0]);

		if (!argv.parsoidURL) {
			throw new Error('No parsoidURL provided!');
		}
		argv.parsoidURLOpts = { baseUrl: argv.parsoidURL };
		if (argv.proxyURL) {
			argv.parsoidURLOpts.proxy = { host: argv.proxyURL };
		}
		var formatter = ScriptUtils.booleanOption(argv.xml) ? xmlFormat : plainFormat;
		var r = yield runTests(title, argv, formatter);
		console.log(r.output);
		if (argv.check) {
			process.exit(r.exitCode);
		}
	})().done();
} else if (typeof module === 'object') {
	module.exports.runTests = runTests;
	module.exports.jsonFormat = jsonFormat;
	module.exports.plainFormat = plainFormat;
	module.exports.xmlFormat = xmlFormat;
}
