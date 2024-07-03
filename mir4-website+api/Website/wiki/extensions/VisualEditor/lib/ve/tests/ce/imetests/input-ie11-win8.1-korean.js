/*!
 * VisualEditor IME test for Internet Explorer on Windows in Korean.
 *
 * @copyright See AUTHORS.txt
 */

ve.ce.imetests.push( [ 'input-ie11-win8.1-korean', [
	{ imeIdentifier: 'Korean', userAgent: 'Mozilla/5.0 (Windows NT 6.3; WOW64; Trident/7.0; .NET4.0E; .NET4.0C; .NET CLR 3.5.30729; .NET CLR 2.0.50727; .NET CLR 3.0.30729; GWX:QUALIFIED; MASMJS; rv:11.0) like Gecko', startDom: 'x' },
	{ seq: 0, time: 15.602, action: 'changeText', args: [ 'ㅎ' ] },
	{ seq: 1, time: 15.602, action: 'changeSel', args: [ 1, 1 ] },
	{ seq: 2, time: 15.602, action: 'sendEvent', args: [ 'keydown', { keyCode: 229 } ] },
	{ seq: 3, time: 15.629, action: 'sendEvent', args: [ 'compositionstart', {} ] },
	{ seq: 4, time: 15.648, action: 'changeSel', args: [ 0, 1 ] },
	{ seq: 5, time: 15.648, action: 'endLoop', args: [] },
	{ seq: 6, time: 15.698, action: 'sendEvent', args: [ 'keyup', { keyCode: 71 } ] },
	{ seq: 7, time: 15.732, action: 'endLoop', args: [] },
	{ seq: 8, time: 16.18, action: 'sendEvent', args: [ 'keydown', { keyCode: 229 } ] },
	{ seq: 9, time: 16.213, action: 'changeText', args: [ '하' ] },
	{ seq: 10, time: 16.213, action: 'endLoop', args: [] },
	{ seq: 11, time: 16.295, action: 'sendEvent', args: [ 'keyup', { keyCode: 75 } ] },
	{ seq: 12, time: 16.329, action: 'endLoop', args: [] },
	{ seq: 13, time: 16.82, action: 'sendEvent', args: [ 'keydown', { keyCode: 229 } ] },
	{ seq: 14, time: 16.848, action: 'changeText', args: [ '한' ] },
	{ seq: 15, time: 16.848, action: 'endLoop', args: [] },
	{ seq: 16, time: 16.93, action: 'sendEvent', args: [ 'keyup', { keyCode: 83 } ] },
	{ seq: 17, time: 16.964, action: 'endLoop', args: [] },
	{ seq: 18, time: 17.65, action: 'sendEvent', args: [ 'keydown', { keyCode: 229 } ] },
	{ seq: 19, time: 17.657, action: 'changeText', args: [ '한ㄱ' ] },
	{ seq: 20, time: 17.657, action: 'changeSel', args: [ 2, 2 ] },
	{ seq: 21, time: 17.657, action: 'sendEvent', args: [ 'compositionend', {} ] },
	{ seq: 22, time: 17.665, action: 'sendEvent', args: [ 'compositionstart', {} ] },
	{ seq: 23, time: 17.697, action: 'changeSel', args: [ 1, 2 ] },
	{ seq: 24, time: 17.697, action: 'endLoop', args: [] },
	{ seq: 25, time: 17.856, action: 'sendEvent', args: [ 'keyup', { keyCode: 82 } ] },
	{ seq: 26, time: 17.877, action: 'endLoop', args: [] },
	{ seq: 27, time: 18.063, action: 'sendEvent', args: [ 'keydown', { keyCode: 229 } ] },
	{ seq: 28, time: 18.094, action: 'changeText', args: [ '한그' ] },
	{ seq: 29, time: 18.094, action: 'endLoop', args: [] },
	{ seq: 30, time: 18.201, action: 'sendEvent', args: [ 'keyup', { keyCode: 77 } ] },
	{ seq: 31, time: 18.227, action: 'endLoop', args: [] },
	{ seq: 32, time: 18.601, action: 'sendEvent', args: [ 'keydown', { keyCode: 229 } ] },
	{ seq: 33, time: 18.629, action: 'changeText', args: [ '한글' ] },
	{ seq: 34, time: 18.629, action: 'endLoop', args: [] },
	{ seq: 35, time: 18.711, action: 'sendEvent', args: [ 'keyup', { keyCode: 70 } ] },
	{ seq: 36, time: 18.745, action: 'endLoop', args: [] }
] ] );
