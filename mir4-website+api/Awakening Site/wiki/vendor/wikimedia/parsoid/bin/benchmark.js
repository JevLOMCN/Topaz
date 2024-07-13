'use strict';

const fs = require('fs');
const yaml = require('js-yaml');
require('../core-upgrade.js');
const Promise = require('../lib/utils/promise.js');
const request = Promise.promisify(require('request'), true);

// Some semi-arbitrary list of titles
const sampleTitles = [
	{ wiki: 'enwiki', title: 'Main_Page', revid: 917272779 },
	{ wiki: 'enwiki', title: 'Skating', revid: 921619251 },
	{ wiki: 'enwiki', title: 'Hospet', revid: 913341503 },
	{ wiki: 'enwiki', title: 'Hampi', revid: 921528573 },
	{ wiki: 'enwiki', title: 'Berlin', revid: 921687210 },
	{ wiki: 'enwiki', title: 'Barack_Obama', revid: 921752860 },
	{ wiki: 'enwiki', title: 'Max_Planck_Institute_for_Physics', revid: 921775647 },
	{ wiki: 'enwiki', title: 'Architects & Engineers for 9/11 Truth', revid: 921775875 },
	{ wiki: 'itwiki', title: 'Luna', revid: 108284424 },
	{ wiki: 'itwiki', title: 'Metro', revid: 108262882 },
	{ wiki: 'frwiki', title: 'Mulholland_Drive', revid: 149562710 },
	{ wiki: 'frwiki', title: 'Metro', revid: 108262882 },
	{ wiki: 'frwiki', title: 'François_de_La_Tour_du_Pin', revid: 163623032 },
	{ wiki: 'frwiki', title: 'Jason_Bateman', revid: 163623075 },
	{ wiki: 'jawiki', title: '人類学', revid: 74657621 },
	{ wiki: 'jawiki', title: 'パレオ・インディアン', revid: 70817191 },
	{ wiki: 'mediawiki', title: 'Parsoid', revid: 3453996 },
	{ wiki: 'mediawiki', title: 'RESTBase', revid: 2962542 },
	{ wiki: 'mediawiki', title: 'VisualEditor', revid: 3408339 },
	{ wiki: 'dewikivoyage', title: 'Bengaluru', revid: 1224432 },
	{ wiki: 'dewikivoyage', title: 'Kopenhagen', revid: 1240570 },
	{ wiki: 'dewikivoyage', title: 'Stuttgart', revid: 1226146 },
	{ wiki: 'hiwiktionary', title: 'परिवर्णी', revid: 467616 },
	{ wiki: 'hiwiktionary', title: 'चीन', revid: 456648 },
	{ wiki: 'knwikisource', title: 'ಪಂಪಭಾರತ_ಪ್ರಥಮಾಶ್ವಾಸಂ', revid: 170413 },
];

let outerConfig = {
	// File with \n-separated json blobs with at least (wiki, title, oldId / revid) properties
	// If domain is provided, it is used, if not wiki is treated as a prefix
	// All other properties are ignored.
	// If this property is null, sampleTitles above is used
	testTitles: null, // '/tmp/logs',
	mode: 'wt2html',
	jsServer: {
		baseURI: 'http://localhost:8142',
		proxy: '',
	},
	phpServer: {
		baseURI: 'http://DOMAIN/w/rest.php',
		proxy: '', // 'http://scandium.eqiad.wmnet:80',
	},
	maxOutstanding: 4,
	maxRequests: 25,
	verbose: true
};

const state = {
	times: [],
	numPendingRequests: 0,
	outStanding: 0
};

function genFullUrls(config, domain, title, revid) {
	let initRestFragment, restFragment;

	switch (config.mode || 'wt2html') {
		case 'wt2html':
			restFragment = `${domain}/v3/page/html/${encodeURIComponent(title)}/${revid}`;
			break;
		case 'wt2pb':
			restFragment = `${domain}/v3/page/pagebundle/${encodeURIComponent(title)}/${revid}`;
			break;
		case 'html2wt':
			initRestFragment = `${domain}/v3/page/html/${encodeURIComponent(title)}/${revid}`;
			restFragment = `${domain}/v3/transform/html/to/wikitext/${encodeURIComponent(title)}/${revid}`;
			break;
		case 'pb2wt':
			initRestFragment = `${domain}/v3/page/pagebundle/${encodeURIComponent(title)}/${revid}`;
			restFragment = `${domain}/v3/transform/pagebundle/to/wikitext/${encodeURIComponent(title)}/${revid}`;
			break;
		default:
			console.log("Mode " + config.mode + " is not supported right now.");
			process.exit(-1);
	}
	return {
		js : `${config.jsServer.baseURI}/${restFragment}`,
		php: `${config.phpServer.baseURI.replace(/DOMAIN/, domain)}/${restFragment}`,
		init: initRestFragment ? `${config.phpServer.baseURI.replace(/DOMAIN/, domain)}/${initRestFragment}` : null,
		jsTime: null,
		phpTime: null,
	};
}

function prefixToDomain(prefix) {
	if (prefix === 'commonswiki') {
		return 'commons.wikimedia.org';
	}

	if (prefix === 'metawiki') {
		return 'meta.wikimedia.org';
	}

	if (prefix === 'wikidatawiki') {
		return 'wikidata.org';
	}

	if (prefix === 'mediawiki' || prefix === 'mediawikiwiki') {
		return 'www.mediawiki.org';
	}

	if (/wiki$/.test(prefix)) {
		return prefix.replace(/wiki$/, '.wikipedia.org');
	}

	const project = [ 'wiktionary', 'wikisource', 'wikivoyage', 'wikibooks', 'wikiquote', 'wikinews', 'wikiversity' ].find(function(p) {
		return prefix.endsWith(p);
	});

	return project ? `${prefix.slice(0, Math.max(0, prefix.length - project.length))}.${project}.org` : null;
}

function contentFileName(url) {
	// Hacky
	const suffix = /.*v3\/(page|transform)\/pagebundle/.test(url) ? 'pb.json' : 'html';
	const wiki = url.replace(/\/v3\/.*/, '').replace(/.*\//, '');
	return '/tmp/' + wiki + "." + url.replace(/.*\//, '') + ".php." + suffix;
}

function fetchPageContent(url) {
	const fileName = contentFileName(url);
	return fs.existsSync(fileName) ? fs.readFileSync(fileName, 'utf8') : null;
}

function issueRequest(opts, url, finalizer) {
	const config = opts.config;
	const fromWT = opts.mode === 'wt2html' || opts.mode === 'wt2pb';
	const httpOptions = {
		method: fromWT ? 'GET' : 'POST',
		headers: { 'User-Agent': 'Parsoid-Test' },
		proxy: opts.proxy,
		uri: fromWT ? url : url.replace(/\/\d+$/, ''), // strip oldid to suppress selser
	};

	if (!fromWT) {
		httpOptions.headers['Content-Type'] = 'application/json';
		const content = fetchPageContent(url);
		if (!content) {
			console.log("Aborting request! Content not found @ " + contentFileName(url));
			// Abort
			state.numPendingRequests--;
			if (state.numPendingRequests === 0 && state.outStanding === 0) {
				console.log('resolving after abort');
				finalizer();
			}
			return;
		}

		if (opts.mode === 'pb2wt') {
			const pb = JSON.parse(content);
			httpOptions.body = {
				html: pb.html.body,
				original : {
					'data-parsoid': pb['data-parsoid']
					// non-selser mode, so don't need wikitext
				},
			};
		} else  {
			httpOptions.body = {
				'html': content
			};
		}
		httpOptions.body = JSON.stringify(httpOptions.body);
	}

	const reqId = state.numPendingRequests;
	if (config.verbose) {
		console.log(`--> ID=${reqId}; URL:${url}; PENDING=${state.numPendingRequests}; OUTSTANDING=${state.outStanding}`);
	}
	state.numPendingRequests--;
	state.outStanding++;
	const startTime = process.hrtime();
	return request(httpOptions)
	.catch(function(error) { console.log("errrorr!" + error); })
	.then(function(ret) {
		state.outStanding--;
		if (opts.type === 'init') {
			fs.writeFileSync(contentFileName(url), ret[1]);
			if (state.numPendingRequests === 0 && state.outStanding === 0) {
				finalizer();
			}
		} else {
			const endTime = process.hrtime();
			const reqTime = Math.round((endTime[0] * 1e9 + endTime[1]) / 1e6 - (startTime[0] * 1e9 + startTime[1]) / 1e6);
			if (config.verbose) {
				console.log(`<-- ID=${reqId}; URL:${url}; TIME=${reqTime}; STATUS: ${ret[0].statusCode}; LEN: ${ret[1].length}`);
			}
			if (!opts.results[reqId]) {
				opts.results[reqId] = {
					url: url,
				};
			}
			opts.results[reqId][opts.type + 'Time'] = reqTime;
			state.times.push(reqTime);
			if (state.numPendingRequests === 0 && state.outStanding === 0) {
				const res = state.times.reduce((stats, n) => {
					stats.sum += n;
					stats.min = n < stats.min ? n : stats.min;
					stats.max = n > stats.max ? n : stats.max;
					return stats;
				}, { sum: 0, min: 1000000, max: 0 });
				res.avg = res.sum / state.times.length;
				res.median = state.times.sort((a, b) => a - b)[Math.floor(state.times.length / 2)];
				console.log(`\n${opts.type.toUpperCase()} STATS: ${JSON.stringify(res)}`);
				finalizer();
			}
		}
	})
	.catch(function(error) { console.log("errrorr!" + error); });
}

function computeRandomRequestStream(testUrls, config) {
	const numReqs = config.maxRequests;
	const reqs = [];
	const n = testUrls.length;
	for (let i = 0; i < numReqs; i++) {
		// Pick a random url
		reqs.push(testUrls[Math.floor(Math.random() * n)]);
	}
	return reqs;
}

function reset(config) {
	state.times = [];
	state.numPendingRequests = config.maxRequests;
	state.outStanding = 0; // # outstanding reqs
}

function runTests(opts, finalizer) {
	if (state.numPendingRequests > 0) {
		if (state.outStanding < opts.config.maxOutstanding) {
			const url = opts.reqs[opts.reqs.length - state.numPendingRequests][opts.type];
			if (opts.type === 'js') {
				opts.proxy = opts.config.jsServer.proxy || '';
			} else { // 'php' or 'init' For init, content is always fetched from Parsoid/PHP
				opts.proxy = opts.config.phpServer.proxy || '';
			}
			if (opts.type === 'init' && fs.existsSync(contentFileName(url))) {
				// Content exists. Don't fetch.
				state.numPendingRequests--;
				if (state.numPendingRequests === 0 && state.outStanding === 0) {
					finalizer();
					return;
				}
			} else {
				issueRequest(opts, url, finalizer);
			}
		}
		setImmediate(() => runTests(opts, finalizer));
	}
}

// Override default config
if (process.argv.length > 2) {
	outerConfig = yaml.load(fs.readFileSync(process.argv[2], 'utf8'));
}

// CLI overrides config
if (process.argv.length > 3) {
	outerConfig.maxOutstanding = parseInt(process.argv[3], 10);
}

// CLI overrides config
if (process.argv.length > 4) {
	outerConfig.maxRequests = parseInt(process.argv[4], 10);
}

let testUrls;
if (outerConfig.testTitles) {
	// Parse production logs and generate test urls
	const logs = fs.readFileSync(outerConfig.testTitles, 'utf8');
	const lines = logs.split(/\n/);
	testUrls = [];
	lines.forEach(function(l) {
		if (l) {
			const log = JSON.parse(l);
			const domain = log.domain || prefixToDomain(log.wiki);
			if (domain) {
				testUrls.push(genFullUrls(outerConfig, domain, log.title, log.oldId || log.revid));
			}
		}
	});
} else {
	testUrls = [];
	sampleTitles.forEach(function(t) {
		testUrls.push(genFullUrls(outerConfig, t.domain || prefixToDomain(t.wiki), t.title, t.revid));
	});
}

const reqStream = computeRandomRequestStream(testUrls, outerConfig);
const opts = {
	config: outerConfig,
	reqs: reqStream,
	results: [],
};

let p;
if (/2wt$/.test(outerConfig.mode)) {
	// Fetch pb / html as necessary and save to disk
	// so we can run and benchmark pb2wt or html2wt after
	p = new Promise(function(resolve, reject) {
		opts.type = 'init';
		opts.mode = outerConfig.mode === 'pb2wt' ? 'wt2pb' : 'wt2html';
		console.log("--- Initialization ---");
		reset(outerConfig);
		runTests(opts, function() {
			console.log("--- Initialization done---");
			resolve();
		});
	});
} else {
	p = Promise.resolve();
}

p.then(function() {
	reset(outerConfig);
	opts.type = 'js';
	opts.mode = outerConfig.mode;
	console.log("\n\n--- JS tests ---");
	runTests(opts, function() {
		console.log("\n\n--- PHP tests---");
		reset(outerConfig);
		opts.type = 'php';
		opts.mode = outerConfig.mode;
		runTests(opts, function() {
			console.log("\n--- All done---\n");
			let numJSFaster = 0;
			let numPHPFaster = 0;
			opts.results.forEach(function(r) {
				if (r.jsTime < r.phpTime) {
					numJSFaster++;
					console.log(`For ${r.url}, Parsoid/JS was faster than Parsoid/PHP (${r.jsTime} vs. ${r.phpTime})`);
				} else {
					numPHPFaster++;
				}
			});
			console.log('\n# of reqs where Parsoid/JS was faster than Parsoid/PHP: ' + numJSFaster);
			console.log('# of reqs where Parsoid/PHP was faster than Parsoid/JS: ' + numPHPFaster);
			process.exit(0);
		});
	});
}).done();
