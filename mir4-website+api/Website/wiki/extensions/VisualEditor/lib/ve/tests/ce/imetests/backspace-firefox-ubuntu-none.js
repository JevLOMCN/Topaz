/*!
 * VisualEditor IME backspace test for Firefox on Ubuntu.
 *
 * @copyright See AUTHORS.txt
 */

ve.ce.imetests.push( [ 'backspace-firefox-ubuntu-none', [
	{ imeIdentifier: 'none', userAgent: 'Mozilla/5.0 (X11; Linux i686 on x86_64; rv:24.0) Gecko/20100101 Firefox/24.0', startDom: '' },
	{ seq: 0, time: 3.341, action: 'changeText', args: [ 'गा' ] },
	{ seq: 1, time: 3.341, action: 'changeSel', args: [ 2, 2 ] },
	{ seq: 2, time: 3.341, action: 'sendEvent', args: [ 'input', {} ] },
	{ seq: 3, time: 3.402, action: 'endLoop', args: [] },
	{ seq: 4, time: 4.394, action: 'sendEvent', args: [ 'keydown', { keyCode: 8 } ] },
	{ seq: 5, time: 4.396, action: 'sendEvent', args: [ 'keypress', { keyCode: 8 } ] },
	{ seq: 6, time: 4.4, action: 'changeText', args: [ 'ग' ] },
	{ seq: 7, time: 4.4, action: 'changeSel', args: [ 1, 1 ] },
	{ seq: 8, time: 4.4, action: 'sendEvent', args: [ 'input', {} ] },
	{ seq: 9, time: 4.406, action: 'endLoop', args: [] },
	{ seq: 10, time: 4.461, action: 'sendEvent', args: [ 'keyup', { keyCode: 8 } ] },
	{ seq: 11, time: 4.465, action: 'endLoop', args: [] },
	{ seq: 12, time: 5.446, action: 'sendEvent', args: [ 'keydown', { keyCode: 8 } ] },
	{ seq: 13, time: 5.449, action: 'sendEvent', args: [ 'keypress', { keyCode: 8 } ] },
	{ seq: 14, time: 5.453, action: 'changeText', args: [ '' ] },
	{ seq: 15, time: 5.453, action: 'changeSel', args: [ 0, 0 ] },
	{ seq: 16, time: 5.453, action: 'sendEvent', args: [ 'input', {} ] },
	{ seq: 17, time: 5.466, action: 'endLoop', args: [] },
	{ seq: 18, time: 5.519, action: 'sendEvent', args: [ 'keyup', { keyCode: 8 } ] },
	{ seq: 19, time: 5.524, action: 'endLoop', args: [] }
] ] );
