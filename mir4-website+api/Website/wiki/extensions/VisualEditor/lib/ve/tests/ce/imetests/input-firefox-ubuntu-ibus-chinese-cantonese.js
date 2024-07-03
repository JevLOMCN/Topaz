/*!
 * VisualEditor IME test for Firefox on Ubuntu in Cantonese Chinese using iBus.
 *
 * @copyright See AUTHORS.txt
 */

ve.ce.imetests.push( [ 'input-firefox-ubuntu-ibus-chinese-cantonese', [
	{ imeIdentifier: 'ibus Chinese cantonese', userAgent: 'Mozilla/5.0 (X11; Linux i686 on x86_64; rv:24.0) Gecko/20100101 Firefox/24.0', startDom: 'x' },
	{ seq: 0, time: 12.252, action: 'sendEvent', args: [ 'compositionstart', {} ] },
	{ seq: 1, time: 12.26, action: 'changeText', args: [ '唔' ] },
	{ seq: 2, time: 12.26, action: 'changeSel', args: [ 1, 1 ] },
	{ seq: 3, time: 12.26, action: 'sendEvent', args: [ 'input', {} ] },
	{ seq: 4, time: 12.302, action: 'endLoop', args: [] },
	{ seq: 5, time: 13.784, action: 'changeText', args: [ '<br>' ] },
	{ seq: 6, time: 13.784, action: 'changeSel', args: [ 0, 0 ] },
	{ seq: 7, time: 13.784, action: 'sendEvent', args: [ 'compositionend', {} ] },
	{ seq: 8, time: 13.794, action: 'sendEvent', args: [ 'input', {} ] },
	{ seq: 9, time: 13.798, action: 'sendEvent', args: [ 'compositionstart', {} ] },
	{ seq: 10, time: 13.802, action: 'changeText', args: [ '唔<br>' ] },
	{ seq: 11, time: 13.802, action: 'changeSel', args: [ 1, 1 ] },
	{ seq: 12, time: 13.802, action: 'sendEvent', args: [ 'compositionend', {} ] },
	{ seq: 13, time: 13.81, action: 'sendEvent', args: [ 'input', {} ] },
	{ seq: 14, time: 13.852, action: 'endLoop', args: [] },
	{ seq: 15, time: 24.071, action: 'sendEvent', args: [ 'compositionstart', {} ] },
	{ seq: 16, time: 24.079, action: 'changeText', args: [ '唔家<br>' ] },
	{ seq: 17, time: 24.079, action: 'changeSel', args: [ 2, 2 ] },
	{ seq: 18, time: 24.079, action: 'sendEvent', args: [ 'input', {} ] },
	{ seq: 19, time: 24.115, action: 'endLoop', args: [] },
	{ seq: 20, time: 24.464, action: 'changeText', args: [ '唔高<br>' ] },
	{ seq: 21, time: 24.464, action: 'sendEvent', args: [ 'input', {} ] },
	{ seq: 22, time: 24.491, action: 'endLoop', args: [] },
	{ seq: 23, time: 24.796, action: 'changeText', args: [ '唔改<br>' ] },
	{ seq: 24, time: 24.796, action: 'sendEvent', args: [ 'input', {} ] },
	{ seq: 25, time: 24.836, action: 'endLoop', args: [] },
	{ seq: 26, time: 26.862, action: 'changeText', args: [ '唔<br>' ] },
	{ seq: 27, time: 26.862, action: 'changeSel', args: [ 1, 1 ] },
	{ seq: 28, time: 26.862, action: 'sendEvent', args: [ 'compositionend', {} ] },
	{ seq: 29, time: 26.877, action: 'sendEvent', args: [ 'input', {} ] },
	{ seq: 30, time: 26.882, action: 'sendEvent', args: [ 'compositionstart', {} ] },
	{ seq: 31, time: 26.888, action: 'changeText', args: [ '唔該<br>' ] },
	{ seq: 32, time: 26.888, action: 'changeSel', args: [ 2, 2 ] },
	{ seq: 33, time: 26.888, action: 'sendEvent', args: [ 'compositionend', {} ] },
	{ seq: 34, time: 26.902, action: 'sendEvent', args: [ 'input', {} ] },
	{ seq: 35, time: 27.036, action: 'endLoop', args: [] }
] ] );
