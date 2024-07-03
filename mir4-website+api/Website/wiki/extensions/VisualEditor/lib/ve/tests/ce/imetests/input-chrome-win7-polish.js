/*!
 * VisualEditor IME test for Chromium on Windows in Polish.
 *
 * @copyright See AUTHORS.txt
 */

ve.ce.imetests.push( [ 'input-chrome-win7-polish', [
	{ imeIdentifier: 'Polish', userAgent: 'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/40.0.2214.28 Safari/537.36 OPR/27.0.1689.29 (Edition beta)', startDom: '' },
	{ seq: 0, time: 5.323, action: 'sendEvent', args: [ 'keydown', { keyCode: 17 } ] },
	{ seq: 1, time: 5.325, action: 'sendEvent', args: [ 'keydown', { keyCode: 18 } ] },
	{ seq: 2, time: 5.328, action: 'endLoop', args: [] },
	{ seq: 3, time: 5.505, action: 'sendEvent', args: [ 'keydown', { keyCode: 65 } ] },
	{ seq: 4, time: 5.507, action: 'sendEvent', args: [ 'keypress', { keyCode: 261 } ] },
	{ seq: 5, time: 5.509, action: 'changeText', args: [ 'ą' ] },
	{ seq: 6, time: 5.509, action: 'changeSel', args: [ 1, 1 ] },
	{ seq: 7, time: 5.509, action: 'sendEvent', args: [ 'input', {} ] },
	{ seq: 8, time: 5.522, action: 'endLoop', args: [] },
	{ seq: 9, time: 5.585, action: 'sendEvent', args: [ 'keyup', { keyCode: 65 } ] },
	{ seq: 10, time: 5.592, action: 'endLoop', args: [] },
	{ seq: 11, time: 5.647, action: 'sendEvent', args: [ 'keyup', { keyCode: 17 } ] },
	{ seq: 12, time: 5.653, action: 'sendEvent', args: [ 'keyup', { keyCode: 18 } ] },
	{ seq: 13, time: 5.661, action: 'endLoop', args: [] },
	{ seq: 14, time: 5.814, action: 'sendEvent', args: [ 'keydown', { keyCode: 65 } ] },
	{ seq: 15, time: 5.822, action: 'sendEvent', args: [ 'keypress', { keyCode: 97 } ] },
	{ seq: 16, time: 5.831, action: 'changeText', args: [ 'ąa' ] },
	{ seq: 17, time: 5.831, action: 'changeSel', args: [ 2, 2 ] },
	{ seq: 18, time: 5.831, action: 'sendEvent', args: [ 'input', {} ] },
	{ seq: 19, time: 5.863, action: 'endLoop', args: [] },
	{ seq: 20, time: 5.894, action: 'sendEvent', args: [ 'keyup', { keyCode: 65 } ] },
	{ seq: 21, time: 5.911, action: 'endLoop', args: [] }
] ] );
