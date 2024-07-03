/*!
 * VisualEditor IME left arrow test for Chromium on Ubuntu.
 *
 * @copyright See AUTHORS.txt
 */

ve.ce.imetests.push( [ 'leftarrow-chromium-ubuntu-none', [
	{ imeIdentifier: 'none', userAgent: 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.22 (KHTML, like Gecko) Ubuntu Chromium/25.0.1364.160 Chrome/25.0.1364.160 Safari/537.22', startDom: '' },
	{ seq: 0, time: 12.452, action: 'changeText', args: [ '𨋢' ] },
	{ seq: 1, time: 12.452, action: 'sendEvent', args: [ 'input', {} ] },
	{ seq: 2, time: 12.477, action: 'changeSel', args: [ 2, 2 ] },
	{ seq: 3, time: 12.477, action: 'endLoop', args: [] },
	{ seq: 4, time: 13.366, action: 'sendEvent', args: [ 'keydown', { keyCode: 37 } ] },
	{ seq: 5, time: 13.374, action: 'changeSel', args: [ 0, 0 ] },
	{ seq: 6, time: 13.374, action: 'endLoop', args: [] },
	{ seq: 7, time: 13.487, action: 'sendEvent', args: [ 'keyup', { keyCode: 37 } ] },
	{ seq: 8, time: 13.492, action: 'endLoop', args: [] }
] ] );
