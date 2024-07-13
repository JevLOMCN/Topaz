/**
 * @module
 */

'use strict';

require('../core-upgrade.js');

var entities = require('entities');

var ContentUtils = require('../lib/utils/ContentUtils.js').ContentUtils;
var DOMUtils = require('../lib/utils/DOMUtils.js').DOMUtils;
var DOMDataUtils = require('../lib/utils/DOMDataUtils.js').DOMDataUtils;
var Util = require('../lib/utils/Util.js').Util;
var WTUtils = require('../lib/utils/WTUtils.js').WTUtils;
var DOMNormalizer = require('../lib/html2wt/DOMNormalizer.js').DOMNormalizer;
var MockEnv = require('./MockEnv.js').MockEnv;

var TestUtils = {};

/**
 * Little helper function for encoding XML entities.
 *
 * @param {string} string
 * @return {string}
 */
TestUtils.encodeXml = function(string) {
	return entities.encodeXML(string);
};

/**
 * Specialized normalization of the PHP parser & Parsoid output, to ignore
 * a few known-ok differences in parser test runs.
 *
 * This code is also used by the Parsoid round-trip testing code.
 *
 * If parsoidOnly is true-ish, we allow more markup through (like property
 * and typeof attributes), for better checking of parsoid-only test cases.
 *
 * @param {string} domBody
 * @param {Object} options
 * @param {boolean} [options.parsoidOnly=false]
 * @param {boolean} [options.preserveIEW=false]
 * @param {boolean} [options.hackNormalize=false]
 * @return {string}
 */
TestUtils.normalizeOut = function(domBody, options) {
	if (!options) {
		options = {};
	}
	const parsoidOnly = options.parsoidOnly;
	const preserveIEW = options.preserveIEW;

	if (options.hackyNormalize) {
		// Mock env obj
		//
		// FIXME: This is ugly.
		// (a) The normalizer shouldn't need the full env.
		//     Pass options and a logger instead?
		// (b) DOM diff code is using page-id for some reason.
		//     That feels like a carryover of 2013 era code.
		//     If possible, get rid of it and diff-mark dependency
		//     on the env object.
		const env = new MockEnv({}, null);
		if (typeof (domBody) === 'string') {
			domBody = env.createDocument(domBody).body;
		}
		var mockState = {
			env,
			selserMode: false,
		};
		DOMDataUtils.visitAndLoadDataAttribs(domBody, { markNew: true });
		domBody = (new DOMNormalizer(mockState).normalize(domBody));
		DOMDataUtils.visitAndStoreDataAttribs(domBody);
	} else if (typeof (domBody) === 'string') {
		domBody = DOMUtils.parseHTML(domBody).body;
	}

	var stripTypeof = parsoidOnly ?
		/^mw:Placeholder$/ :
		/^mw:(?:DisplaySpace|Placeholder|Nowiki|Transclusion|Entity)$/;
	domBody = this.unwrapSpansAndNormalizeIEW(domBody, stripTypeof, parsoidOnly, preserveIEW);
	var out = ContentUtils.toXML(domBody, { innerXML: true });
	// NOTE that we use a slightly restricted regexp for "attribute"
	//  which works for the output of DOM serialization.  For example,
	//  we know that attribute values will be surrounded with double quotes,
	//  not unquoted or quoted with single quotes.  The serialization
	//  algorithm is given by:
	//  http://www.whatwg.org/specs/web-apps/current-work/multipage/the-end.html#serializing-html-fragments
	if (!/[^<]*(<\w+(\s+[^\0-\cZ\s"'>\/=]+(="[^"]*")?)*\/?>[^<]*)*/.test(out)) {
		throw new Error("normalizeOut input is not in standard serialized form");
	}

	// Eliminate a source of indeterminacy from leaked strip markers
	out = out.replace(/UNIQ-.*?-QINU/g, '');

	// Normalize COINS ids -- they aren't stable
	out = out.replace(/\s?id=['"]coins_\d+['"]/ig, '');

	// maplink extension
	out = out.replace(/\s?data-overlays='[^']*'/ig, '');

	if (parsoidOnly) {
		// unnecessary attributes, we don't need to check these
		// style is in there because we should only check classes.
		out = out.replace(/ (data-parsoid|prefix|about|rev|datatype|inlist|usemap|vocab)=\\?"[^\"]*\\?"/g, '');
		// single-quoted variant
		out = out.replace(/ (data-parsoid|prefix|about|rev|datatype|inlist|usemap|vocab)=\\?'[^\']*\\?'/g, '');
		// apos variant
		out = out.replace(/ (data-parsoid|prefix|about|rev|datatype|inlist|usemap|vocab)=&apos;.*?&apos;/g, '');

		// strip self-closed <nowiki /> because we frequently test WTS
		// <nowiki> insertion by providing an html/parsoid section with the
		// <meta> tags stripped out, allowing the html2wt test to verify that
		// the <nowiki> is correctly added during WTS, while still allowing
		// the html2html and wt2html versions of the test to pass as a
		// sanity check.  If <meta>s were not stripped, these tests would all
		// have to be modified and split up.  Not worth it at this time.
		// (see commit 689b22431ad690302420d049b10e689de6b7d426)
		out = out
			.replace(/<span typeof="mw:Nowiki"><\/span>/g, '');

		return out;
	}

	// Normalize headings by stripping out Parsoid-added ids so that we don't
	// have to add these ids to every parser test that uses headings.
	// We will test the id generation scheme separately via mocha tests.
	out = out.replace(/(<h[1-6].*?) id="[^"]*"([^>]*>)/g, '$1$2');

	// strip meta/link elements
	out = out
		.replace(/<\/?(?:meta|link)(?: [^\0-\cZ\s"'>\/=]+(?:=(?:"[^"]*"|'[^']*'))?)*\/?>/g, '');
	// Ignore troublesome attributes.
	// Strip JSON attributes like data-mw and data-parsoid early so that
	// comment stripping in normalizeNewlines does not match unbalanced
	// comments in wikitext source.
	out = out.replace(/ (data-parsoid|prefix|about|rev|datatype|inlist|usemap|vocab|data-mw|resource|rel|property|class)=\\?"[^\"]*\\?"/g, '');
	// single-quoted variant
	out = out.replace(/ (data-parsoid|prefix|about|rev|datatype|inlist|usemap|vocab|data-mw|resource|rel|property|class)=\\?'[^\']*\\?'/g, '');
	// strip typeof last
	out = out.replace(/ typeof="[^\"]*"/g, '');

	return out
		// replace mwt ids
		.replace(/ id="mw((t\d+)|([\w-]{2,}))"/g, '')
		.replace(/<span[^>]+about="[^"]*"[^>]*>/g, '')
		.replace(/(\s)<span>\s*<\/span>\s*/g, '$1')
		.replace(/<span>\s*<\/span>/g, '')
		.replace(/(href=")(?:\.?\.\/)+/g, '$1')
		// replace unnecessary URL escaping
		.replace(/ href="[^"]*"/g, Util.decodeURI)
		// strip thumbnail size prefixes
		.replace(/(src="[^"]*?)\/thumb(\/[0-9a-f]\/[0-9a-f]{2}\/[^\/]+)\/[0-9]+px-[^"\/]+(?=")/g, '$1$2');
};

/**
 * Normalize newlines in IEW to spaces instead.
 *
 * @param {Node} body
 *   The document `<body>` node to normalize.
 * @param {RegExp} [stripSpanTypeof]
 * @param {boolean} [parsoidOnly=false]
 * @param {boolean} [preserveIEW=false]
 * @return {Node}
 */
TestUtils.unwrapSpansAndNormalizeIEW = function(body, stripSpanTypeof, parsoidOnly, preserveIEW) {
	var newlineAround = function(node) {
		return node && /^(BODY|CAPTION|DIV|DD|DT|LI|P|TABLE|TR|TD|TH|TBODY|DL|OL|UL|H[1-6])$/.test(node.nodeName);
	};
	var unwrapSpan;  // forward declare
	var cleanSpans = function(node) {
		var child, next;
		if (!stripSpanTypeof) { return; }
		for (child = node.firstChild; child; child = next) {
			next = child.nextSibling;
			if (child.nodeName === 'SPAN' &&
				stripSpanTypeof.test(child.getAttribute('typeof') || '')) {
				unwrapSpan(node, child);
			}
		}
	};
	unwrapSpan = function(parent, node) {
		// first recurse to unwrap any spans in the immediate children.
		cleanSpans(node);
		// now unwrap this span.
		DOMUtils.migrateChildren(node, parent, node);
		parent.removeChild(node);
	};
	var visit = function(node, stripLeadingWS, stripTrailingWS, inPRE) {
		var child, next, prev;
		if (node.nodeName === 'PRE') {
			// Preserve newlines in <pre> tags
			inPRE = true;
		}
		if (!preserveIEW && DOMUtils.isText(node)) {
			if (!inPRE) {
				node.data = node.data.replace(/\s+/g, ' ');
			}
			if (stripLeadingWS) {
				node.data = node.data.replace(/^\s+/, '');
			}
			if (stripTrailingWS) {
				node.data = node.data.replace(/\s+$/, '');
			}
		}
		// unwrap certain SPAN nodes
		cleanSpans(node);
		// now remove comment nodes
		if (!parsoidOnly) {
			for (child = node.firstChild; child; child = next) {
				next = child.nextSibling;
				if (DOMUtils.isComment(child)) {
					node.removeChild(child);
				}
			}
		}
		// reassemble text nodes split by a comment or span, if necessary
		node.normalize();
		// now recurse.
		if (node.nodeName === 'PRE') {
			// hack, since PHP adds a newline before </pre>
			stripLeadingWS = false;
			stripTrailingWS = true;
		} else if (node.nodeName === 'SPAN' &&
				/^mw[:]/.test(node.getAttribute('typeof') || '')) {
			// SPAN is transparent; pass the strip parameters down to kids
		} else {
			stripLeadingWS = stripTrailingWS = newlineAround(node);
		}
		child = node.firstChild;
		// Skip over the empty mw:FallbackId <span> and strip leading WS
		// on the other side of it.
		if (/^H[1-6]$/.test(node.nodeName) &&
			child && WTUtils.isFallbackIdSpan(child)) {
			child = child.nextSibling;
		}
		for (; child; child = next) {
			next = child.nextSibling;
			visit(child,
				stripLeadingWS,
				stripTrailingWS && !child.nextSibling,
				inPRE);
			stripLeadingWS = false;
		}
		if (inPRE || preserveIEW) { return node; }
		// now add newlines around appropriate nodes.
		for (child = node.firstChild; child; child = next) {
			prev = child.previousSibling;
			next = child.nextSibling;
			if (newlineAround(child)) {
				if (prev && DOMUtils.isText(prev)) {
					prev.data = prev.data.replace(/\s*$/, '\n');
				} else {
					prev = node.ownerDocument.createTextNode('\n');
					node.insertBefore(prev, child);
				}
				if (next && DOMUtils.isText(next)) {
					next.data = next.data.replace(/^\s*/, '\n');
				} else {
					next = node.ownerDocument.createTextNode('\n');
					node.insertBefore(next, child.nextSibling);
				}
			}
		}
		return node;
	};
	// clone body first, since we're going to destructively mutate it.
	return visit(body.cloneNode(true), true, true, false);
};

if (typeof module === "object") {
	module.exports.TestUtils = TestUtils;
}
