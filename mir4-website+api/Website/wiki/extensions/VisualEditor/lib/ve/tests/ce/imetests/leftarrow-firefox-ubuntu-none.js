/*!
 * VisualEditor IME left arrow test for Firefox on Ubuntu.
 *
 * @copyright See AUTHORS.txt
 */

ve.ce.imetests.push( [ 'leftarrow-firefox-ubuntu-none', [
	{ imeIdentifier: 'none', userAgent: 'Mozilla/5.0 (X11; Linux i686 on x86_64; rv:24.0) Gecko/20100101 Firefox/24.0', startDom: '' },
	{ seq: 0, time: 7.941, action: 'changeText', args: [ '𨋢' ] },
	{ seq: 1, time: 7.941, action: 'changeSel', args: [ 2, 2 ] },
	{ seq: 2, time: 7.941, action: 'sendEvent', args: [ 'input', {} ] },
	{ seq: 3, time: 8.004, action: 'endLoop', args: [] },
	{ seq: 4, time: 9.64, action: 'sendEvent', args: [ 'keydown', { keyCode: 37 } ] },
	{ seq: 5, time: 9.642, action: 'sendEvent', args: [ 'keypress', { keyCode: 37 } ] },
	{ seq: 6, time: 9.652, action: 'changeSel', args: [ 0, 0 ] },
	{ seq: 7, time: 9.652, action: 'endLoop', args: [] },
	{ seq: 8, time: 9.722, action: 'sendEvent', args: [ 'keyup', { keyCode: 37 } ] },
	{ seq: 9, time: 9.726, action: 'endLoop', args: [] }
] ] );
