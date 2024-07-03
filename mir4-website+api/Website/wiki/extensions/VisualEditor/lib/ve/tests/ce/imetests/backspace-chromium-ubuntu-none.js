/*!
 * VisualEditor IME backspace test for Chromium on Ubuntu.
 *
 * @copyright See AUTHORS.txt
 */

ve.ce.imetests.push( [ 'backspace-chromium-ubuntu-none', [
	{ imeIdentifier: 'none', userAgent: 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.22 (KHTML, like Gecko) Ubuntu Chromium/25.0.1364.160 Chrome/25.0.1364.160 Safari/537.22', startDom: '' },
	{ seq: 0, time: 4.732, action: 'changeText', args: [ 'गा' ] },
	{ seq: 1, time: 4.732, action: 'sendEvent', args: [ 'input', {} ] },
	{ seq: 2, time: 4.76, action: 'changeSel', args: [ 2, 2 ] },
	{ seq: 3, time: 4.76, action: 'endLoop', args: [] },
	{ seq: 4, time: 7.508, action: 'sendEvent', args: [ 'keydown', { keyCode: 8 } ] },
	{ seq: 5, time: 7.511, action: 'changeText', args: [ 'ग' ] },
	{ seq: 6, time: 7.511, action: 'changeSel', args: [ 1, 1 ] },
	{ seq: 7, time: 7.511, action: 'sendEvent', args: [ 'input', {} ] },
	{ seq: 8, time: 7.521, action: 'endLoop', args: [] },
	{ seq: 9, time: 7.619, action: 'sendEvent', args: [ 'keyup', { keyCode: 8 } ] },
	{ seq: 10, time: 7.625, action: 'endLoop', args: [] },
	{ seq: 11, time: 8.234, action: 'sendEvent', args: [ 'keydown', { keyCode: 8 } ] },
	{ seq: 12, time: 8.238, action: 'changeText', args: [ '<br>' ] },
	{ seq: 13, time: 8.238, action: 'changeSel', args: [ 0, 0 ] },
	{ seq: 14, time: 8.238, action: 'sendEvent', args: [ 'input', {} ] },
	{ seq: 15, time: 8.249, action: 'endLoop', args: [] },
	{ seq: 16, time: 8.331, action: 'sendEvent', args: [ 'keyup', { keyCode: 8 } ] },
	{ seq: 17, time: 8.338, action: 'endLoop', args: [] }
] ] );
