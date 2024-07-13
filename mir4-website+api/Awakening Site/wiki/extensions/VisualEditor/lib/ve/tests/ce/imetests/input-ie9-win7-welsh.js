/*!
 * VisualEditor IME test for Internet Explorer on Windows in Welsh.
 *
 * @copyright See AUTHORS.txt
 */

ve.ce.imetests.push( [ 'input-ie9-win7-welsh', [
	{ imeIdentifier: 'Welsh', userAgent: 'Mozilla/5.0 (compatible; MSIE 9.0; Windows NT 6.1; Trident/5.0; SLCC2; .NET CLR 2.0.50727; .NET CLR 3.5.30729; .NET CLR 3.0.30729; Media Center PC 6.0; OfficeLiveConnector.1.3; OfficeLivePatch.0.0; .NET4.0C)', startDom: '' },
	{ seq: 0, time: 4.868, action: 'sendEvent', args: [ 'keydown', { keyCode: 68 } ] },
	{ seq: 1, time: 4.87, action: 'sendEvent', args: [ 'keypress', { keyCode: 100 } ] },
	{ seq: 2, time: 4.876, action: 'changeText', args: [ 'd' ] },
	{ seq: 3, time: 4.876, action: 'changeSel', args: [ 1, 1 ] },
	{ seq: 4, time: 4.876, action: 'endLoop', args: [] },
	{ seq: 5, time: 4.928, action: 'sendEvent', args: [ 'keyup', { keyCode: 68 } ] },
	{ seq: 6, time: 4.937, action: 'endLoop', args: [] },
	{ seq: 7, time: 5.425, action: 'sendEvent', args: [ 'keydown', { keyCode: 17 } ] },
	{ seq: 8, time: 5.428, action: 'sendEvent', args: [ 'keydown', { keyCode: 18 } ] },
	{ seq: 9, time: 5.432, action: 'endLoop', args: [] },
	{ seq: 10, time: 5.793, action: 'sendEvent', args: [ 'keydown', { keyCode: 54 } ] },
	{ seq: 11, time: 5.799, action: 'endLoop', args: [] },
	{ seq: 12, time: 5.973, action: 'sendEvent', args: [ 'keyup', { keyCode: 54 } ] },
	{ seq: 13, time: 5.983, action: 'endLoop', args: [] },
	{ seq: 14, time: 6.022, action: 'sendEvent', args: [ 'keyup', { keyCode: 17 } ] },
	{ seq: 15, time: 6.025, action: 'sendEvent', args: [ 'keyup', { keyCode: 18 } ] },
	{ seq: 16, time: 6.033, action: 'endLoop', args: [] },
	{ seq: 17, time: 6.554, action: 'sendEvent', args: [ 'keydown', { keyCode: 87 } ] },
	{ seq: 18, time: 6.557, action: 'sendEvent', args: [ 'keypress', { keyCode: 373 } ] },
	{ seq: 19, time: 6.564, action: 'changeText', args: [ 'dŵ' ] },
	{ seq: 20, time: 6.564, action: 'changeSel', args: [ 2, 2 ] },
	{ seq: 21, time: 6.564, action: 'endLoop', args: [] },
	{ seq: 22, time: 6.754, action: 'sendEvent', args: [ 'keyup', { keyCode: 87 } ] },
	{ seq: 23, time: 6.768, action: 'endLoop', args: [] },
	{ seq: 24, time: 7.465, action: 'sendEvent', args: [ 'keydown', { keyCode: 82 } ] },
	{ seq: 25, time: 7.472, action: 'sendEvent', args: [ 'keypress', { keyCode: 114 } ] },
	{ seq: 26, time: 7.481, action: 'changeText', args: [ 'dŵr' ] },
	{ seq: 27, time: 7.481, action: 'changeSel', args: [ 3, 3 ] },
	{ seq: 28, time: 7.481, action: 'endLoop', args: [] },
	{ seq: 29, time: 7.579, action: 'sendEvent', args: [ 'keyup', { keyCode: 82 } ] },
	{ seq: 30, time: 7.591, action: 'endLoop', args: [] }
] ] );
