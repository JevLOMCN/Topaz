/*!
 * VisualEditor IME test for Chromium on Windows in Traditional Chinese Handwriting.
 *
 * @copyright See AUTHORS.txt
 */

ve.ce.imetests.push( [ 'input-chrome-win7-chinese-traditional-handwriting', [
	{ imeIdentifier: 'Chinese Traditional Handwriting', userAgent: 'Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/30.0.1599.101 Safari/537.36', startDom: 'x' },
	{ seq: 0, time: 8.491, action: 'changeText', args: [ '中' ] },
	{ seq: 1, time: 8.491, action: 'changeSel', args: [ 1, 1 ] },
	{ seq: 2, time: 8.491, action: 'sendEvent', args: [ 'input', {} ] },
	{ seq: 3, time: 8.536, action: 'endLoop', args: [] },
	{ seq: 4, time: 14.097, action: 'changeText', args: [ '中國' ] },
	{ seq: 5, time: 14.097, action: 'changeSel', args: [ 2, 2 ] },
	{ seq: 6, time: 14.097, action: 'sendEvent', args: [ 'input', {} ] },
	{ seq: 7, time: 14.112, action: 'endLoop', args: [] }
] ] );
