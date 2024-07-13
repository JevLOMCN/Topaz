#!/usr/bin/env node

'use strict';

require('colors');
const yaml = require('js-yaml');
const fs = require('fs');
const Promise = require('../lib/utils/promise.js');
const { Diff } = require('../lib/utils/Diff.js');
const { DOMUtils } = require('../lib/utils/DOMUtils.js');
const { ScriptUtils } = require('../tools/ScriptUtils.js');
const XMLSerializer = require('../lib/wt2html/XMLSerializer.js');

let jsServer = { baseURI: 'http://localhost:8142', proxy: '' };
let phpServer = { baseURI: 'http://localhost/rest.php', proxy: '' };

function normalizeHTML(html, isPHP) {
	// Normalize about ids
	html = html.replace(/#mwt[0-9]*/gm, '#mwtX');

	// Normalized unexpanded DOM fragments (T235656)
	html = html.replace(/mwf[0-9]*/gm, 'mwfX');

	// Remove stray nowiki strip tags left behind by extensions
	html = html.replace(/UNIQ--nowiki-.*?QINU/gm, '');

	// JS output has bad maplinks (on wikivoyages). Strip out maplink
	// wrapper in both JS & PHP HTML
	html = html.replace(/<div class="magnify" title="Enlarge map">.*?<\/div>/gm, '');

	// JS & PHP introduce differing # of significant digits after the decimal
	// In any case, fractional pixels don't make sense, so simply strip those out.
	// (T229594 -- Neither PHP nor JS should emit fractional dims here)
	html = html.replace(/((?:width|height):\s*[0-9]*)(?:\.[0-9]*)?(px)?/gm, '$1$2');

	// Normalize minor variations in dsr (T231570 is one source)
	// ,null as well as ,null,null (Ex: jawiki:J-WAVE)
	html = html.replace(/,null,null/gm, '');
	html = html.replace(/,null/gm, ',0');
	html = html.replace(/,0,0/gm, '');

	// Parse to DOM for additional normalization easier done
	// on the DOM vs a HTML string.
	const body = DOMUtils.parseHTML(html).body;
	// data-parsoid:
	// - JS & PHP seem to be inserting some keys in different order
	//   (Ex: jawiki:ICalendar)
	//   Sort keys
	DOMUtils.visitDOM(body, function(node) {
		if (DOMUtils.isElt(node)) {
			if (node.hasAttribute('data-parsoid')) {
				const dpStr = node.getAttribute('data-parsoid');
				const dp = JSON.parse(dpStr);
				const dp2 = {};
				Object.keys(dp).sort().forEach(function(k) {
					dp2[k] = dp[k];
				});
				node.setAttribute('data-parsoid', JSON.stringify(dp2));
			}
		}
	});

	// Serialize back the parsed DOM - use XMLSerializer for smart quoting.
	// Return body inner HTML (normalize diffs in <head> and <body> attributes)
	// This is a lazy normalization. We can do more finer-grained normalization
	// and include the <head> tag once we narrow down diffs elsewhere.
	html = XMLSerializer.serialize(body, { innerXML: true, smartQuote: true }).html;

	// Add copious new lines to generate finer-grained diffs
	return html.replace(/</gm, '\n<');
}

const fetchHTML = Promise.async(function *(server, proxy, domain, title, isPHP) {
	const httpOptions = {
		method: 'GET',
		headers: { 'User-Agent': 'Parsoid-Test' },
		proxy: proxy,
		uri: server.replace(/\/$/, '') + '/' + domain + '/v3/page/html/' + encodeURIComponent(title),
	};

	if (isPHP) {
		// Append a trailing / to workaround T232556
		// Request ucs2 offsets to ensure dsr offsets line up
		httpOptions.uri += '/?offsetType=ucs2';
	}

	const result = yield ScriptUtils.retryingHTTPRequest(2, httpOptions);
	return normalizeHTML(result[1], isPHP);
});

function genLineLengths(str) {
	return str.split(/^/m).map(function(l) {
		return l.length;
	});
}

const fetchAllHTML = Promise.async(function *(domain, title) {
	const jsHTML = yield fetchHTML(jsServer.baseURI, '', domain, title);
	const phpHTML = yield fetchHTML(phpServer.baseURI.replace(/DOMAIN/, domain), phpServer.proxy, domain, title, true);
	return {
		js: {
			html: jsHTML,
			lineLens: genLineLengths(jsHTML),
		},
		php: {
			html: phpHTML,
			lineLens: genLineLengths(phpHTML),
		}
	};
});

// Get diff slices from offsets
function formatDiff(str1, str2, offset, context) {
	return [
		`----- JS:[${offset[0].start}, ${offset[0].end}] -----`,
		str1.slice(offset[0].start - context, offset[0].start).blue +
		str1.slice(offset[0].start, offset[0].end).green +
		str1.slice(offset[0].end, offset[0].end + context).blue,
		`+++++ PHP:[${offset[1].start}, ${offset[1].end}] +++++`,
		str2.slice(offset[1].start - context, offset[1].start).blue +
		str2.slice(offset[1].start, offset[1].end).red +
		str2.slice(offset[1].end, offset[1].end + context).blue,
	].join('\n');
}

function afterFetch(res) {
	const diff = Diff.diffLines(res.js.html, res.php.html);
	const offsets = Diff.convertDiffToOffsetPairs(diff, res.js.lineLens, res.php.lineLens);
	const lineDiffs = [];
	if (offsets.length > 0) {
		for (let i = 0; i < offsets.length; i++) {
			lineDiffs.push(formatDiff(res.js.html, res.php.html, offsets[i], 0));
		}
	}
	return lineDiffs;
}

function htmlDiff(config, domain, title) {
	jsServer = config.jsServer || jsServer;
	phpServer = config.phpServer || phpServer;

	return fetchAllHTML(domain, title)
	.then(afterFetch)
	.catch(function(e) {
		console.error(e);
	});
}

function fileDiff(jsFilename, phpFilename) {
	const jsOut = fs.readFileSync(jsFilename, 'utf8');
	const phpOut = fs.readFileSync(phpFilename, 'utf8');
	const out = {
		js: {
			html: normalizeHTML(jsOut, false),
		},
		php: {
			html: normalizeHTML(phpOut, true),
		}
	};
	out.js.lineLens = genLineLengths(out.js.html);
	out.php.lineLens = genLineLengths(out.php.html);
	return afterFetch(out);
}

function displayResult(diffs, domain, title) {
	domain = domain || '<unknown>';
	title = title || '<unknown>';
	if (diffs.length === 0) {
		console.log(`${domain}:${title}: NO HTML DIFFS FOUND!`);
	} else {
		console.log(`Parsoid/JS vs. Parsoid/PHP HTML diffs for ${domain}:${title}`);
		console.log(diffs.join('\n'));
	}
}

if (require.main === module) {
	const config = yaml.load(fs.readFileSync(process.argv[2], 'utf8'));
	const domain = process.argv[3];
	const title = process.argv[4];
	htmlDiff(config, domain, title).then(function(diffs) {
		displayResult(diffs, domain, title);
	}).done();
} else if (typeof module === "object") {
	module.exports.htmlDiff = htmlDiff;
	module.exports.fileDiff = fileDiff;
	module.exports.displayResult = displayResult;
	module.exports.fetchHTML = fetchHTML;
}
