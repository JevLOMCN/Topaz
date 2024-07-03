/*!
 * VisualEditor IME test for Firefox on Ubuntu in Japanese using iBus Mozc.
 *
 * @copyright See AUTHORS.txt
 */

ve.ce.imetests.push( [ 'input-firefox-ubuntu-ibus-japanese-mozc', [
	{ imeIdentifier: 'ibus Japanese mozc', userAgent: 'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv: 38.0) Gecko/20100101 Firefox/38.0', startDom: '' },
	{ seq: 0, time: 5.394, action: 'sendEvent', args: [ 'compositionstart', { } ] },
	{ seq: 1, time: 5.397, action: 'changeText', args: [ 'ｎ' ] },
	{ seq: 2, time: 5.397, action: 'changeSel', args: [ 1, 1 ] },
	{ seq: 3, time: 5.397, action: 'sendEvent', args: [ 'input', { } ] },
	{ seq: 4, time: 5.416, action: 'endLoop', args: [] },
	{ seq: 5, time: 6.206, action: 'changeText', args: [ 'に' ] },
	{ seq: 6, time: 6.206, action: 'sendEvent', args: [ 'input', { } ] },
	{ seq: 7, time: 6.214, action: 'endLoop', args: [] },
	{ seq: 8, time: 7.017, action: 'changeText', args: [ 'にｈ' ] },
	{ seq: 9, time: 7.017, action: 'changeSel', args: [ 2, 2 ] },
	{ seq: 10, time: 7.017, action: 'sendEvent', args: [ 'input', { } ] },
	{ seq: 11, time: 7.022, action: 'endLoop', args: [] },
	{ seq: 12, time: 7.712, action: 'changeText', args: [ 'にほ' ] },
	{ seq: 13, time: 7.712, action: 'sendEvent', args: [ 'input', { } ] },
	{ seq: 14, time: 7.716, action: 'endLoop', args: [] },
	{ seq: 15, time: 8.526, action: 'changeText', args: [ 'にほｎ' ] },
	{ seq: 16, time: 8.526, action: 'changeSel', args: [ 3, 3 ] },
	{ seq: 17, time: 8.526, action: 'sendEvent', args: [ 'input', { } ] },
	{ seq: 18, time: 8.532, action: 'endLoop', args: [] },
	{ seq: 19, time: 9.196, action: 'changeText', args: [ 'にほんｇ' ] },
	{ seq: 20, time: 9.196, action: 'changeSel', args: [ 4, 4 ] },
	{ seq: 21, time: 9.196, action: 'sendEvent', args: [ 'input', { } ] },
	{ seq: 22, time: 9.202, action: 'endLoop', args: [] },
	{ seq: 23, time: 10.043, action: 'changeText', args: [ 'にほんご' ] },
	{ seq: 24, time: 10.043, action: 'sendEvent', args: [ 'input', { } ] },
	{ seq: 25, time: 10.048, action: 'endLoop', args: [] },
	{ seq: 26, time: 10.798, action: 'changeText', args: [ '日本語' ] },
	{ seq: 27, time: 10.798, action: 'changeSel', args: [ 0, 0 ] },
	{ seq: 28, time: 10.798, action: 'sendEvent', args: [ 'input', { } ] },
	{ seq: 29, time: 10.806, action: 'endLoop', args: [] },
	{ seq: 30, time: 11.905, action: 'changeSel', args: [ 3, 3 ] },
	{ seq: 31, time: 11.905, action: 'sendEvent', args: [ 'compositionend', { } ] },
	{ seq: 32, time: 11.91, action: 'sendEvent', args: [ 'input', { } ] },
	{ seq: 33, time: 11.913, action: 'endLoop', args: [] },
	{ seq: 34, time: 12.027, action: 'sendEvent', args: [ 'keyup', { keyCode: 13 } ] },
	{ seq: 35, time: 12.03, action: 'endLoop', args: [] }
] ] );
