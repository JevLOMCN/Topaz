/*!
 * VisualEditor IME test for Firefox on Windows in Traditional Cantonese Handwriting.
 *
 * @copyright See AUTHORS.txt
 */

ve.ce.imetests.push( [ 'input-firefox-win7-chinese-traditional-handwriting', [
	{ imeIdentifier: 'Chinese Traditional Handwriting', userAgent: 'Mozilla/5.0 (Windows NT 6.1; rv:19.0) Gecko/20100101 Firefox/19.0', startDom: 'x' },
	{ seq: 0, time: 20.16, action: 'sendEvent', args: [ 'compositionstart', {} ] },
	{ seq: 1, time: 20.205, action: 'changeText', args: [ '中' ] },
	{ seq: 2, time: 20.205, action: 'changeSel', args: [ 1, 1 ] },
	{ seq: 3, time: 20.205, action: 'sendEvent', args: [ 'compositionend', {} ] },
	{ seq: 4, time: 20.209, action: 'sendEvent', args: [ 'input', {} ] },
	{ seq: 5, time: 20.217, action: 'endLoop', args: [] },
	{ seq: 6, time: 29.676, action: 'sendEvent', args: [ 'compositionstart', {} ] },
	{ seq: 7, time: 29.681, action: 'changeText', args: [ '中國' ] },
	{ seq: 8, time: 29.681, action: 'changeSel', args: [ 2, 2 ] },
	{ seq: 9, time: 29.681, action: 'sendEvent', args: [ 'compositionend', {} ] },
	{ seq: 10, time: 29.686, action: 'sendEvent', args: [ 'input', {} ] },
	{ seq: 11, time: 29.688, action: 'endLoop', args: [] }
] ] );
