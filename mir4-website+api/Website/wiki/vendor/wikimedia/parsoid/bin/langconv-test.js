#!/usr/bin/env node

'use strict';

require('../core-upgrade.js');

const colors = require('colors');
const fs = require('pn/fs');
const path = require('path');
const yargs = require('yargs');

const { ApiRequest, DoesNotExistError } = require('../lib/mw/ApiRequest.js');
const { Diff } = require('../lib/utils/Diff.js');
const { DOMDataUtils } = require('../lib/utils/DOMDataUtils.js');
const { DOMTraverser } = require('../lib/utils/DOMTraverser.js');
const { DOMUtils } = require('../lib/utils/DOMUtils.js');
const { MWParserEnvironment } = require('../lib/config/MWParserEnvironment.js');
const { ParsoidConfig } = require('../lib/config/ParsoidConfig.js');
const Promise = require('../lib/utils/promise.js');
const { TemplateRequest } = require('../lib/mw/ApiRequest.js');
const { Util } = require('../lib/utils/Util.js');
const { ScriptUtils } = require('../tools/ScriptUtils.js');

const jsonFormat = function(error, domain, title, lang, options, results) {
	if (error) { return { error: error.stack || error.toString() }; }
	const p = Diff.patchDiff(results.php, results.parsoid);
	return { patch: p };
};

const plainFormat = function(error, domain, title, lang, options, results) {
	if (error) { return error.stack || error.toString(); }
	const article = `${domain} ${title} ${lang || ''}`;
	const diff = Diff.colorDiff(results.php, results.parsoid, {
		context: 1,
		noColor: (colors.mode === 'none'),
		diffCount: true,
	});
	if (diff.count === 0) { return ''; }
	return `== ${article} ==\n${diff.output}\n${diff.count} different words found.\n`;
};

const xmlFormat = function(error, domain, title, lang, options, results) {
	const article = `${domain} ${title} ${lang || ''}`;
	let output = '<testsuites>\n';
	output += `<testsuite name="Variant ${Util.escapeHtml(article)}">\n`;
	output += `<testcase name="revision ${results.revid}">\n`;
	if (error) {
		output += '<error type="parserFailedToFinish">';
		output += Util.escapeHtml(error.stack || error.toString());
		output += '</error>';
	} else if (results.php !== results.parsoid) {
		output += '<failure type="diff">\n<diff class="html">\n';
		output += Diff.colorDiff(results.php, results.parsoid, {
			context: 1,
			html: true,
			separator: '</diff></failure>\n' +
				'<failure type="diff"><diff class="html">',
		});
		output += '\n</diff>\n</failure>\n';
	}
	output += '</testcase>\n';
	output += '</testsuite>\n';
	output += '</testsuites>\n';
	return output;
};

const silentFormat = function(error, domain, title, lang, options, results) {
	return '';
};

class PHPVariantRequest extends ApiRequest {
	constructor(env, title, variant, revid) {
		super(env, title);
		this.reqType = "Variant Parse";

		const apiargs = {
			format: 'json',
			action: 'parse',
			page: title,
			prop: 'text|revid|displaytitle',
			uselang: 'content',
			wrapoutputclass: '',
			disableeditsection: 'true',
			disabletoc: 'true',
			disablelimitreport: 'true'
		};
		if (revid) {
			// The parameters `page` and `oldid` can't be used together
			apiargs.page = undefined;
			apiargs.oldid = revid;
		}
		// This argument to the API is not documented!  Except in
		// https://phabricator.wikimedia.org/T44356#439479 and
		// https://phabricator.wikimedia.org/T34906#381101
		if (variant) { apiargs.variant = variant; }

		const uri = env.conf.wiki.apiURI;
		this.requestOptions = {
			uri,
			method: 'POST',
			form: apiargs, // The API arguments
			followRedirect: true,
			timeout: env.conf.parsoid.timeouts.mwApi.extParse,
		};

		this.request(this.requestOptions);
	}
	_handleJSON(error, data) {
		if (!error && !(data && data.parse)) {
			error = this._errorObj(data, this.text, 'Missing data.parse.');
		}

		if (error) {
			this.env.log("error", error);
			this._processListeners(error, '');
		} else {
			this._processListeners(error, data.parse);
		}
	}
	_errorObj(data, requestStr, defaultMsg) {
		if (data && data.error && data.error.code === 'missingtitle') {
			return new DoesNotExistError(this.title);
		}
		return super._errorObj(data, requestStr, defaultMsg);
	}
}

const phpFetch = Promise.async(function *(env, title, revid) {
	const parse = yield new Promise((resolve, reject) => {
		const req = new PHPVariantRequest(
			env, title, env.htmlVariantLanguage, revid
		);
		req.once('src', (err, src) => {
			return err ? reject(err) : resolve(src);
		});
	});
	const document = DOMUtils.parseHTML(parse.text['*']);
	const displaytitle = parse.displaytitle;
	revid = parse.revid;
	return {
		document,
		revid,
		displaytitle
	};
});

const parsoidFetch = Promise.async(function *(env, title, options) {
	if (!options.useServer) {
		yield TemplateRequest.setPageSrcInfo(env, title, options.oldid);
		const revision = env.page.meta.revision;
		const handler = env.getContentHandler(revision.contentmodel);
		const document = yield handler.toHTML(env);
		return {
			document,
			revid: revision.revid,
			displaytitle: document.title,
		};
	}
	const domain = options.domain;
	let uri = options.uri;
	// Make sure the Parsoid URI ends with `/`
	if (!/\/$/.test(uri)) {
		uri += '/';
	}
	uri += `${domain}/v3/page/html/${encodeURIComponent(title)}`;
	if (options.oldid) {
		uri += `/${options.oldid}`;
	}
	const resp = yield ScriptUtils.retryingHTTPRequest(10, {
		method: 'GET',
		uri,
		headers: {
			'User-Agent': env.userAgent,
			'Accept-Language': env.htmlVariantLanguage,
		}
	});
	// We may have been redirected to the latest revision. Record oldid.
	const res = resp[0];
	const body = resp[1];
	if (res.statusCode !== 200) {
		throw new Error(`Can\'t fetch Parsoid source: ${uri}`);
	}
	const oldid = res.request.path.replace(/^(.*)\//, '');
	const document = DOMUtils.parseHTML(body);
	return {
		document,
		revid: oldid,
		displaytitle: document.title,
	};
});

const hrefToTitle = function(href) {
	return Util.decodeURIComponent(href.replace(/^(\.\.?|\/wiki)\//, ''))
		.replace(/_/g, ' ');
};

const nodeHrefToTitle = function(node, suppressCategory) {
	const href = node && node.hasAttribute('href') && node.getAttribute('href');
	if (!href) { return null; }
	const title = hrefToTitle(href);
	if (suppressCategory) {
		const categoryMatch = title.match(/^([^:]+)[:]/);
		if (categoryMatch) { return null; /* skip it */ }
	}
	return title;
};

/**
 * Pull a list of local titles from wikilinks in a Parsoid HTML document.
 *
 * @param env
 * @param document
 */
const spiderDocument = function(env, document) {
	const redirect = document.querySelector('link[rel~="mw:PageProp/redirect"]');
	const nodes = redirect ? [ redirect ] :
		Array.from(document.querySelectorAll('a[rel~="mw:WikiLink"][href]'));
	return new Set(
		nodes.map(node => nodeHrefToTitle(node, true)).filter(t => t !== null)
	);
};

/**
 * Pull "just the text" from an HTML document, normalizing whitespace
 * differences and suppressing places where Parsoid and PHP output
 * deliberately differs.
 *
 * @param env
 * @param document
 */
const extractText = function(env, document) {
	var dt = new DOMTraverser();
	var sep = '';
	var buf = '';
	/* We normalize all whitespace in text nodes to a single space. We
	 * do insert newlines in the output, but only to delimit block
	 * elements.  Even there, we are careful never to emit two newlines
	 * in a row, or whitespace before or after a newline. */
	const addSep = (s) => {
		if (s === '') { return; }
		if (/\n/.test(s)) { sep = '\n'; return; }
		if (sep === '\n') { return; }
		sep = ' ';
	};
	const emit = (s) => { if (s !== '') { buf += sep; buf += s; sep = ''; } };
	dt.addHandler('#text', (node, env2, atTopLevel, tplInfo) => {
		const v = node.nodeValue.replace(/\s+/g, ' ');
		const m = /^(\s*)(.*?)(\s*)$/.exec(v);
		addSep(m[1]);
		emit(m[2]);
		addSep(m[3]);
		return true;
	});
	dt.addHandler('div', (node) => {
		if (node.classList.contains('magnify') &&
			node.parentNode &&
			node.parentNode.classList.contains('thumbcaption')) {
			// Skip the "magnify" link, which PHP has and Parsoid doesn't.
			return node.nextSibling;
		}
		return true;
	});
	/* These are the block elements which we delimit with newlines (aka,
	 * we ensure they start on a line of their own). */
	var forceBreak = () => { addSep('\n'); return true; };
	for (const el of ['p','li','div','table','tr','h1','h2','h3','h4','h5','h6','figure', 'figcaption']) {
		dt.addHandler(el, forceBreak);
	}
	dt.addHandler('div', (node) => {
		if (node.classList.contains('thumbcaption')) {
			// <figcaption> (Parsoid) is marked as forceBreak,
			// so thumbcaption (PHP) should be, too.
			forceBreak();
		}
		return true;
	});
	/* Separate table columns with spaces */
	dt.addHandler('td', () => { addSep(' '); return true; });
	/* Suppress reference numbers and linkback text */
	dt.addHandler('sup', (node) => {
		if (
			node.classList.contains('reference') /* PHP */ ||
			node.classList.contains('mw-ref') /* Parsoid */
		) {
			return node.nextSibling; // Skip contents of this node
		}
		return true;
	});
	dt.addHandler('span', (node) => {
		if (
			node.classList.contains('mw-cite-backlink') ||
			/\bmw:referencedBy\b/.test(node.getAttribute('rel') || '')
		) {
			return node.nextSibling; // Skip contents of this node
		}
		return true;
	});
	dt.addHandler('figcaption', (node) => {
		/* Captions are suppressed in PHP for:
		 * figure[typeof~="mw:File/Frameless"], figure[typeof~="mw:File"]
		 * See Note 5 of https://www.mediawiki.org/wiki/Specs/HTML/1.7.0#Images
		 */
		if (DOMDataUtils.hasTypeOf(node.parentNode, 'mw:File/Frameless') ||
			DOMDataUtils.hasTypeOf(node.parentNode, 'mw:File')) {
			// Skip caption contents, since they don't appear in PHP output.
			return node.nextSibling;
		}
		return true;
	});
	/* Show the targets of wikilinks, since the titles should be
	 * language-converted too. */
	dt.addHandler('a', (node) => {
		const rel = node.getAttribute('rel') || '';
		if (/\bmw:referencedBy\b/.test(rel)) {
			// skip reference linkback
			return node.nextSibling;
		}
		let href = node.getAttribute('href') || '';
		// Rewrite red links as normal links
		let m = /^\/w\/index\.php\?title=(.*?)&.*redlink=1$/.exec(href);
		if (m) {
			href = `/wiki/${m[1]}`;
		}
		// Local links to this page, or self-links
		m = /^#/.test(href);
		if (m || node.classList.contains('mw-selflink')) {
			const title = encodeURIComponent(env.page.name);
			href = `/wiki/${title}${href}`;
		}
		// Now look for wiki links
		if (node.classList.contains('external')) {
			return true;
		}
		if (/^(\.\.?|\/wiki)\//.test(href)) {
			const title = hrefToTitle(href);
			addSep(' ');
			emit(`[${title}]`);
			addSep(' ');
		}
		return true;
	});
	dt.addHandler('link', (node) => {
		const rel = node.getAttribute('rel') || '';
		if (/\bmw:PageProp\/redirect\b/.test(rel)) {
			// Given Parsoid output, emulate PHP output for redirects.
			forceBreak();
			emit('Redirect to:');
			forceBreak();
			const title = nodeHrefToTitle(node);
			emit(`[${title}]`);
			addSep(' ');
			emit(title);
			return node.nextSibling;
		}
		return true;
	});
	dt.traverse(document.body);
	return buf;
};

// Wrap an asynchronous function in code to record/replay network requests
const nocksWrap = function(f) {
	return Promise.async(function *(domain, title, lang, options, formatter) {
		let nock, dir, nocksFile;
		if (options.record || options.replay) {
			dir = path.resolve(__dirname, '../nocks/');
			if (!(yield fs.exists(dir))) {
				yield fs.mkdir(dir);
			}
			dir = `${dir}/${domain}`;
			if (!(yield fs.exists(dir))) {
				yield fs.mkdir(dir);
			}
			nocksFile = `${dir}/lc-${encodeURIComponent(title)}-${lang}.js`;
			if (options.record) {
				nock = require('nock');
				nock.recorder.rec({ dont_print: true });
			} else {
				require(nocksFile);
			}
		}
		try {
			return (yield f(domain, title, lang, options, formatter));
		} finally {
			if (options.record) {
				const nockCalls = nock.recorder.play();
				yield fs.writeFile(
					nocksFile,
					`'use strict';\nlet nock = require('nock');\n${nockCalls.join('\n')}`,
					'utf8'
				);
				nock.recorder.clear();
				nock.restore();
			}
		}
	});
};

const runTest = nocksWrap(Promise.async(function *(domain, title, lang, options, formatter) {
	// Step 0: Configuration & setup
	const parsoidOptions = {
		loadWMF: true,
	};
	const envOptions = {
		domain,
		pageName: title,
		userAgent: 'LangConvTest',
		wtVariantLanguage: options.sourceVariant || null,
		htmlVariantLanguage: lang || null,
		logLevels: options.verbose ? undefined : ["fatal", "error", "warn"],
	};
	ScriptUtils.setTemplatingAndProcessingFlags(parsoidOptions, options);
	ScriptUtils.setDebuggingFlags(parsoidOptions, options);
	ScriptUtils.setColorFlags(options);

	const parsoidConfig = new ParsoidConfig(null, parsoidOptions);
	const env = yield MWParserEnvironment.getParserEnv(parsoidConfig, envOptions);

	// Step 1: Fetch page from PHP API
	const phpDoc = yield phpFetch(env, title, options.oldid);
	// Step 2: Fetch page from Parsoid API
	const parsoidDoc = yield parsoidFetch(env, title, {
		domain,
		uri: options.parsoidURL,
		oldid: options.oldid || phpDoc.revid,
		useServer: options.useServer,
	});
	// Step 3: Strip most markup (so we're comparing text, not markup)
	//  ...but eventually we'll leave <a href> since there's some title
	//    conversion that should be done.
	const normalize = out => `TITLE: ${out.displaytitle}\n\n` +
		extractText(env, out.document);
	const phpText = normalize(phpDoc);
	const parsoidText = normalize(parsoidDoc);
	// Step 4: Compare (and profit!)
	console.assert(+phpDoc.revid === +parsoidDoc.revid);
	const output = formatter(null, domain, title, lang, options, {
		php: phpText,
		parsoid: parsoidText,
		revid: phpDoc.revid,
	});
	const exitCode = (phpText === parsoidText) ? 0 : 1;

	return {
		output,
		exitCode,
		// List of local titles, in case we are spidering test cases
		linkedTitles: spiderDocument(env, parsoidDoc.document),
	};
}));

if (require.main === module) {
	const standardOpts = ScriptUtils.addStandardOptions({
		sourceVariant: {
			description: 'Force conversion to assume the given variant for' +
				' the source wikitext',
			boolean: false,
			default: null,
		},
		domain: {
			description: 'Which wiki to use; e.g. "sr.wikipedia.org" for' +
				' Serbian wikipedia',
			boolean: false,
			default: 'sr.wikipedia.org',
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
		apiURL: {
			description: 'http path to remote API,' +
				' e.g. http://sr.wikipedia.org/w/api.php',
			boolean: false,
			default: '',
		},
		xml: {
			description: 'Use xml output format',
			boolean: true,
			default: false,
		},
		check: {
			description: 'Exit with non-zero exit code if differences found using selser',
			boolean: true,
			default: false,
			alias: 'c',
		},
		'record': {
			description: 'Record http requests for later replay',
			'boolean': true,
			'default': false,
		},
		'replay': {
			description: 'Replay recorded http requests for later replay',
			'boolean': true,
			'default': false,
		},
		'spider': {
			description: 'Spider <number> additional pages past the given one',
			'boolean': false,
			'default': 0,
		},
		'silent': {
			description: 'Skip output (used with --record --spider to load caches)',
			'boolean': true,
			'default': false,
		},
		'verbose': {
			description: 'Log at level "info" as well',
			'boolean': true,
			'default': false,
		},
		'useServer': {
			description: 'Use a parsoid server',
			'boolean': true,
			'default': false,
		},
	});

	Promise.async(function *() {
		const opts = yargs
		.usage(
			'Usage: $0 [options] <page-title> <variantLanguage>\n' +
			'The page title should be the "true title",' +
			'i.e., without any url encoding which might be necessary if it appeared in wikitext.' +
			'\n\n'
		)
		.options(standardOpts)
		.strict();
		const argv = opts.argv;
		if (!argv._.length) {
			return opts.showHelp();
		}
		const title = String(argv._[0]);
		const lang = String(argv._[1]);
		if (argv.record || argv.replay) {
			// Don't fork a separate server if record/replay
			argv.useServer = false;
		}
		if (argv.useServer && !argv.parsoidURL) {
			throw new Error('No parsoidURL provided!');
		}
		const formatter =
			ScriptUtils.booleanOption(argv.silent) ? silentFormat :
			ScriptUtils.booleanOption(argv.xml) ? xmlFormat :
			plainFormat;
		const domain = argv.domain || 'sr.wikipedia.org';
		const queue = [title];
		const titlesDone = new Set();
		let exitCode = 0;
		let r;
		for (let i = 0; i < queue.length; i++) {
			if (titlesDone.has(queue[i])) {
				continue; // duplicate title
			}
			if (argv.spider > 1 && argv.verbose) {
				console.log('%s (%d/%d)', queue[i], titlesDone.size, argv.spider);
			}
			try {
				r = yield runTest(domain, queue[i], lang, argv, formatter);
			} catch (e) {
				if (e instanceof DoesNotExistError && argv.spider > 1) {
					// Ignore page-not-found if we are spidering.
					continue;
				}
				r = {
					error: true,
					output: formatter(e, domain, queue[i], lang, argv),
					exitCode: 2,
				};
			}
			exitCode = Math.max(exitCode, r.exitCode);
			if (r.output) {
				console.log(r.output);
			}
			// optionally, spider
			if (argv.spider > 1) {
				if (!r.error) {
					titlesDone.add(queue[i]);
					for (const t of r.linkedTitles) {
						if (/:/.test(t)) { continue; /* hack: no namespaces */ }
						queue.push(t);
					}
				}
				if (titlesDone.size >= argv.spider) {
					break; /* done! */
				}
			}
		}
		if (argv.check || exitCode > 1) {
			process.exit(exitCode);
		}
	})().done();
} else if (typeof module === 'object') {
	module.exports.runTest = runTest;

	module.exports.jsonFormat = jsonFormat;
	module.exports.plainFormat = plainFormat;
	module.exports.xmlFormat = xmlFormat;
}
