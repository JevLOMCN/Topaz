/*!
 * VisualEditor IME test for Firefox on Mac OS X in Katakana Japanese using OS native IME.
 *
 * @copyright See AUTHORS.txt
 */

ve.ce.imetests.push( [ 'input-firefox-mac-native-japanese-katakana', [
	{ imeIdentifier: 'Mac 10.10 native Japanese Katakana in Firefox', userAgent: 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10.10; rv:38.0) Gecko/20100101 Firefox/38.0', startDom: '' },
	{ seq: 0, time: 8.682, action: 'sendEvent', args: [ 'keydown', { keyCode: 84 } ] },
	{ seq: 1, time: 8.687, action: 'sendEvent', args: [ 'compositionstart', {} ] },
	{ seq: 2, time: 8.688, action: 'changeText', args: [ 't' ] },
	{ seq: 3, time: 8.688, action: 'changeSel', args: [ 1, 1 ] },
	{ seq: 4, time: 8.688, action: 'sendEvent', args: [ 'input', {} ] },
	{ seq: 5, time: 8.711, action: 'endLoop', args: [] },
	{ seq: 6, time: 8.973, action: 'changeText', args: [ 'ト' ] },
	{ seq: 7, time: 8.973, action: 'sendEvent', args: [ 'input', {} ] },
	{ seq: 8, time: 8.979, action: 'endLoop', args: [] },
	{ seq: 9, time: 9.356, action: 'changeText', args: [ 'トt' ] },
	{ seq: 10, time: 9.356, action: 'changeSel', args: [ 2, 2 ] },
	{ seq: 11, time: 9.356, action: 'sendEvent', args: [ 'input', {} ] },
	{ seq: 12, time: 9.365, action: 'endLoop', args: [] },
	{ seq: 13, time: 9.652, action: 'changeText', args: [ 'トト' ] },
	{ seq: 14, time: 9.652, action: 'sendEvent', args: [ 'input', {} ] },
	{ seq: 15, time: 9.659, action: 'endLoop', args: [] },
	{ seq: 16, time: 10.676, action: 'changeText', args: [ 'トトr' ] },
	{ seq: 17, time: 10.676, action: 'changeSel', args: [ 3, 3 ] },
	{ seq: 18, time: 10.676, action: 'sendEvent', args: [ 'input', {} ] },
	{ seq: 19, time: 10.695, action: 'endLoop', args: [] },
	{ seq: 20, time: 11.28, action: 'changeText', args: [ 'トトロ' ] },
	{ seq: 21, time: 11.28, action: 'sendEvent', args: [ 'input', {} ] },
	{ seq: 22, time: 11.309, action: 'endLoop', args: [] },
	{ seq: 23, time: 12.844, action: 'sendEvent', args: [ 'input', {} ] },
	{ seq: 24, time: 12.851, action: 'endLoop', args: [] },
	{ seq: 25, time: 13.7, action: 'sendEvent', args: [ 'compositionend', {} ] },
	{ seq: 26, time: 13.702, action: 'sendEvent', args: [ 'input', {} ] },
	{ seq: 27, time: 13.734, action: 'sendEvent', args: [ 'keyup', { keyCode: 13 } ] },
	{ seq: 28, time: 13.77, action: 'endLoop', args: [] }
] ] );
