'use strict';

const { DOMDataUtils } = require('../lib/utils/DOMDataUtils.js');
const { DOMUtils } = require('../lib/utils/DOMUtils.js');

class MockEnv {
	constructor(argv, pageSrc) {
		// Hack in bswPagePropRegexp to support Util.js function "isBehaviorSwitch: function(... "
		const bswRegexpSource = "\\/(?:NOGLOBAL|DISAMBIG|NOCOLLABORATIONHUBTOC|nocollaborationhubtoc|NOTOC|notoc|NOGALLERY|nogallery|FORCETOC|forcetoc|TOC|toc|NOEDITSECTION|noeditsection|NOTITLECONVERT|notitleconvert|NOTC|notc|NOCONTENTCONVERT|nocontentconvert|NOCC|nocc|NEWSECTIONLINK|NONEWSECTIONLINK|HIDDENCAT|INDEX|NOINDEX|STATICREDIRECT)";
		this.page = {
			src: pageSrc || "testing testing testing testing",
		};
		this.conf = {
			wiki: {
				bswPagePropRegexp: new RegExp(
					'(?:^|\\s)mw:PageProp/' + bswRegexpSource + '(?=$|\\s)'
				),
				magicWordCanonicalName: function() { return "toc"; }	// mock function returns string for BehaviorSwitchHandler
			},
			parsoid: {
				debug: argv.debug,
			},
		};
		this.log = argv.log ? this._log : this._emptyLog;
		this.wrapSections = true; // always wrap sections!

		this.setVariable = function(variable, state) { this[variable] = state; };	// mock function to set variable state for BehaviorSwitchHandler
	}

	_emptyLog() {}

	_log() {
		let output = arguments[0];
		for (let i = 1; i < arguments.length; i++) {
			if (typeof arguments[i] === 'function') {
				output = output + ' ' + arguments[i]();
			} else {
				output = output + ' ' + arguments[i];
			}
		}
		console.log(output);
	}

	// NOTE: Here's the spot to stuff references to $doc in the PHP port.
	referenceDataObject(doc, bag) {
		DOMDataUtils.setDocBag(doc, bag);
	}

	// NOTE: This a potential gotcha when it comes to the port.
	// Much like `env.createDocument()`, a reference to $doc is going to have to
	// be held onto so that the attached environment doesn't get GC'd.
	createDocument(html) {
		const doc = DOMUtils.parseHTML(html);
		this.referenceDataObject(doc);
		return doc;
	}
}

if (typeof module === "object") {
	module.exports.MockEnv = MockEnv;
}
