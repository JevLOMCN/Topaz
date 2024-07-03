/*!
 * VisualEditor IME test for Firefox on Windows in Welsh.
 *
 * @copyright See AUTHORS.txt
 */

ve.ce.imetests.push( [ 'input-firefox-win7-welsh', [
	{ imeIdentifier: 'Welsh', userAgent: 'Mozilla/5.0 (Windows NT 6.1; rv:19.0) Gecko/20100101 Firefox/19.0', startDom: '' },
	{ seq: 0, time: 4.995, action: 'sendEvent', args: [ 'keydown', { keyCode: 68 } ] },
	{ seq: 1, time: 4.998, action: 'sendEvent', args: [ 'keypress', { keyCode: 0 } ] },
	{ seq: 2, time: 5.001, action: 'changeText', args: [ 'd' ] },
	{ seq: 3, time: 5.001, action: 'changeSel', args: [ 1, 1 ] },
	{ seq: 4, time: 5.001, action: 'sendEvent', args: [ 'input', {} ] },
	{ seq: 5, time: 5.004, action: 'endLoop', args: [] },
	{ seq: 6, time: 5.059, action: 'sendEvent', args: [ 'keyup', { keyCode: 68 } ] },
	{ seq: 7, time: 5.061, action: 'endLoop', args: [] },
	{ seq: 8, time: 6.707, action: 'sendEvent', args: [ 'keydown', { keyCode: 17 } ] },
	{ seq: 9, time: 6.709, action: 'sendEvent', args: [ 'keydown', { keyCode: 18 } ] },
	{ seq: 10, time: 6.711, action: 'endLoop', args: [] },
	{ seq: 11, time: 6.712, action: 'endLoop', args: [] },
	{ seq: 12, time: 7.179, action: 'sendEvent', args: [ 'keydown', { keyCode: 54 } ] },
	{ seq: 13, time: 7.181, action: 'endLoop', args: [] },
	{ seq: 14, time: 7.299, action: 'sendEvent', args: [ 'keyup', { keyCode: 54 } ] },
	{ seq: 15, time: 7.301, action: 'endLoop', args: [] },
	{ seq: 16, time: 7.533, action: 'sendEvent', args: [ 'keyup', { keyCode: 17 } ] },
	{ seq: 17, time: 7.535, action: 'endLoop', args: [] },
	{ seq: 18, time: 9.708, action: 'sendEvent', args: [ 'keydown', { keyCode: 87 } ] },
	{ seq: 19, time: 9.71, action: 'sendEvent', args: [ 'keypress', { keyCode: 0 } ] },
	{ seq: 20, time: 9.713, action: 'changeText', args: [ 'dŵ' ] },
	{ seq: 21, time: 9.713, action: 'changeSel', args: [ 2, 2 ] },
	{ seq: 22, time: 9.713, action: 'sendEvent', args: [ 'input', {} ] },
	{ seq: 23, time: 9.718, action: 'endLoop', args: [] },
	{ seq: 24, time: 9.817, action: 'sendEvent', args: [ 'keyup', { keyCode: 87 } ] },
	{ seq: 25, time: 9.82, action: 'endLoop', args: [] },
	{ seq: 26, time: 11.445, action: 'sendEvent', args: [ 'keydown', { keyCode: 82 } ] },
	{ seq: 27, time: 11.45, action: 'sendEvent', args: [ 'keypress', { keyCode: 0 } ] },
	{ seq: 28, time: 11.454, action: 'changeText', args: [ 'dŵr' ] },
	{ seq: 29, time: 11.454, action: 'changeSel', args: [ 3, 3 ] },
	{ seq: 30, time: 11.454, action: 'sendEvent', args: [ 'input', {} ] },
	{ seq: 31, time: 11.461, action: 'endLoop', args: [] },
	{ seq: 32, time: 11.519, action: 'sendEvent', args: [ 'keyup', { keyCode: 82 } ] },
	{ seq: 33, time: 11.523, action: 'endLoop', args: [] }
] ] );
