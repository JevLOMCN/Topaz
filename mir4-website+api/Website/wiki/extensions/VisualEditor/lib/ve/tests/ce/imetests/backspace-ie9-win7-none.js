/*!
 * VisualEditor IME backspace test for Internet Explorer on Windows.
 *
 * @copyright See AUTHORS.txt
 */

ve.ce.imetests.push( [ 'backspace-ie9-win7-none', [
	{ imeIdentifier: 'none', userAgent: 'Mozilla/5.0 (compatible; MSIE 9.0; Windows NT 6.1; Trident/5.0; SLCC2; .NET CLR 2.0.50727; .NET CLR 3.5.30729; .NET CLR 3.0.30729; Media Center PC 6.0; OfficeLiveConnector.1.3; OfficeLivePatch.0.0; .NET4.0C)', startDom: '' },
	{ seq: 0, time: 15.234, action: 'changeText', args: [ 'गा' ] },
	{ seq: 1, time: 15.234, action: 'changeSel', args: [ 2, 2 ] },
	{ seq: 2, time: 15.234, action: 'sendEvent', args: [ 'keydown', { keyCode: 8 } ] },
	{ seq: 3, time: 15.242, action: 'changeText', args: [ 'ग' ] },
	{ seq: 4, time: 15.242, action: 'changeSel', args: [ 1, 1 ] },
	{ seq: 5, time: 15.242, action: 'endLoop', args: [] },
	{ seq: 6, time: 15.521, action: 'sendEvent', args: [ 'keyup', { keyCode: 8 } ] },
	{ seq: 7, time: 15.527, action: 'endLoop', args: [] },
	{ seq: 8, time: 16.086, action: 'sendEvent', args: [ 'keydown', { keyCode: 8 } ] },
	{ seq: 9, time: 16.097, action: 'changeText', args: [ '' ] },
	{ seq: 10, time: 16.097, action: 'changeSel', args: [ 0, 0 ] },
	{ seq: 11, time: 16.097, action: 'endLoop', args: [] },
	{ seq: 12, time: 16.378, action: 'sendEvent', args: [ 'keyup', { keyCode: 8 } ] },
	{ seq: 13, time: 16.399, action: 'endLoop', args: [] }
] ] );
