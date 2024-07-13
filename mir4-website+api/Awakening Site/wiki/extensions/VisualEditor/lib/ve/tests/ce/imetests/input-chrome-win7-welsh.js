/*!
 * VisualEditor IME test for Chromium on Windows in Welsh.
 *
 * @copyright See AUTHORS.txt
 */

ve.ce.imetests.push( [ 'input-chrome-win7-welsh', [
	{ imeIdentifier: 'Welsh', userAgent: 'Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/30.0.1599.101 Safari/537.36', startDom: '' },
	{ seq: 0, time: 10.479, action: 'sendEvent', args: [ 'keydown', { keyCode: 68 } ] },
	{ seq: 1, time: 10.481, action: 'sendEvent', args: [ 'keypress', { keyCode: 100 } ] },
	{ seq: 2, time: 10.483, action: 'changeText', args: [ 'd' ] },
	{ seq: 3, time: 10.483, action: 'changeSel', args: [ 1, 1 ] },
	{ seq: 4, time: 10.483, action: 'sendEvent', args: [ 'input', {} ] },
	{ seq: 5, time: 10.5, action: 'endLoop', args: [] },
	{ seq: 6, time: 10.537, action: 'sendEvent', args: [ 'keyup', { keyCode: 68 } ] },
	{ seq: 7, time: 10.545, action: 'endLoop', args: [] },
	{ seq: 8, time: 11.019, action: 'sendEvent', args: [ 'keydown', { keyCode: 17 } ] },
	{ seq: 9, time: 11.021, action: 'sendEvent', args: [ 'keydown', { keyCode: 18 } ] },
	{ seq: 10, time: 11.028, action: 'endLoop', args: [] },
	{ seq: 11, time: 11.248, action: 'sendEvent', args: [ 'keydown', { keyCode: 54 } ] },
	{ seq: 12, time: 11.257, action: 'endLoop', args: [] },
	{ seq: 13, time: 11.437, action: 'sendEvent', args: [ 'keyup', { keyCode: 54 } ] },
	{ seq: 14, time: 11.448, action: 'endLoop', args: [] },
	{ seq: 15, time: 11.512, action: 'sendEvent', args: [ 'keyup', { keyCode: 17 } ] },
	{ seq: 16, time: 11.513, action: 'sendEvent', args: [ 'keyup', { keyCode: 18 } ] },
	{ seq: 17, time: 11.524, action: 'endLoop', args: [] },
	{ seq: 18, time: 12.283, action: 'sendEvent', args: [ 'keydown', { keyCode: 87 } ] },
	{ seq: 19, time: 12.284, action: 'sendEvent', args: [ 'keypress', { keyCode: 373 } ] },
	{ seq: 20, time: 12.287, action: 'changeText', args: [ 'dŵ' ] },
	{ seq: 21, time: 12.287, action: 'changeSel', args: [ 2, 2 ] },
	{ seq: 22, time: 12.287, action: 'sendEvent', args: [ 'input', {} ] },
	{ seq: 23, time: 12.308, action: 'endLoop', args: [] },
	{ seq: 24, time: 12.502, action: 'sendEvent', args: [ 'keyup', { keyCode: 87 } ] },
	{ seq: 25, time: 12.517, action: 'endLoop', args: [] },
	{ seq: 26, time: 13.203, action: 'sendEvent', args: [ 'keydown', { keyCode: 82 } ] },
	{ seq: 27, time: 13.206, action: 'sendEvent', args: [ 'keypress', { keyCode: 114 } ] },
	{ seq: 28, time: 13.209, action: 'changeText', args: [ 'dŵr' ] },
	{ seq: 29, time: 13.209, action: 'changeSel', args: [ 3, 3 ] },
	{ seq: 30, time: 13.209, action: 'sendEvent', args: [ 'input', {} ] },
	{ seq: 31, time: 13.231, action: 'endLoop', args: [] },
	{ seq: 32, time: 13.318, action: 'sendEvent', args: [ 'keyup', { keyCode: 82 } ] },
	{ seq: 33, time: 13.332, action: 'endLoop', args: [] }
] ] );
