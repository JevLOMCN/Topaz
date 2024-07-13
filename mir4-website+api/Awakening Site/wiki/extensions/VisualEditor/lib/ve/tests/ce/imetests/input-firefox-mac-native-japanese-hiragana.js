/*!
 * VisualEditor IME test for Firefox on Mac OS X in Hiragana Japanese using OS native IME.
 *
 * @copyright See AUTHORS.txt
 */

ve.ce.imetests.push( [ 'input-firefox-mac-native-japanese-hiragana', [
	{ imeIdentifier: 'Mac 10.10 native Japanese Hiragana in Firefox', userAgent: 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10.10; rv:38.0) Gecko/20100101 Firefox/38.0', startDom: '' },
	{ seq: 0, time: 14.841, action: 'sendEvent', args: [ 'keydown', { keyCode: 78 } ] },
	{ seq: 1, time: 14.847, action: 'sendEvent', args: [ 'compositionstart', {} ] },
	{ seq: 2, time: 14.849, action: 'changeText', args: [ 'n' ] },
	{ seq: 3, time: 14.849, action: 'changeSel', args: [ 1, 1 ] },
	{ seq: 4, time: 14.849, action: 'sendEvent', args: [ 'input', {} ] },
	{ seq: 5, time: 14.874, action: 'endLoop', args: [] },
	{ seq: 6, time: 15.057, action: 'changeText', args: [ 'に' ] },
	{ seq: 7, time: 15.057, action: 'sendEvent', args: [ 'input', {} ] },
	{ seq: 8, time: 15.099, action: 'endLoop', args: [] },
	{ seq: 9, time: 15.712, action: 'changeText', args: [ 'にh' ] },
	{ seq: 10, time: 15.712, action: 'changeSel', args: [ 2, 2 ] },
	{ seq: 11, time: 15.712, action: 'sendEvent', args: [ 'input', {} ] },
	{ seq: 12, time: 15.764, action: 'endLoop', args: [] },
	{ seq: 13, time: 15.976, action: 'changeText', args: [ 'にほ' ] },
	{ seq: 14, time: 15.976, action: 'sendEvent', args: [ 'input', {} ] },
	{ seq: 15, time: 16.027, action: 'endLoop', args: [] },
	{ seq: 16, time: 16.232, action: 'changeText', args: [ 'にほn' ] },
	{ seq: 17, time: 16.232, action: 'changeSel', args: [ 3, 3 ] },
	{ seq: 18, time: 16.232, action: 'sendEvent', args: [ 'input', {} ] },
	{ seq: 19, time: 16.241, action: 'endLoop', args: [] },
	{ seq: 20, time: 16.833, action: 'changeText', args: [ 'にほんg' ] },
	{ seq: 21, time: 16.833, action: 'changeSel', args: [ 4, 4 ] },
	{ seq: 22, time: 16.833, action: 'sendEvent', args: [ 'input', {} ] },
	{ seq: 23, time: 16.861, action: 'endLoop', args: [] },
	{ seq: 24, time: 17.16, action: 'changeText', args: [ 'にほんご' ] },
	{ seq: 25, time: 17.16, action: 'sendEvent', args: [ 'input', {} ] },
	{ seq: 26, time: 17.173, action: 'endLoop', args: [] },
	{ seq: 27, time: 24.4, action: 'changeText', args: [ '日本語' ] },
	{ seq: 28, time: 24.4, action: 'changeSel', args: [ 3, 3 ] },
	{ seq: 29, time: 24.4, action: 'sendEvent', args: [ 'input', {} ] },
	{ seq: 30, time: 24.41, action: 'endLoop', args: [] },
	{ seq: 31, time: 27.857, action: 'sendEvent', args: [ 'compositionend', {} ] },
	{ seq: 32, time: 27.86, action: 'sendEvent', args: [ 'input', {} ] },
	{ seq: 33, time: 27.884, action: 'endLoop', args: [] },
	{ seq: 34, time: 27.923, action: 'sendEvent', args: [ 'keyup', { keyCode: 13 } ] },
	{ seq: 35, time: 27.926, action: 'endLoop', args: [] }
] ] );
