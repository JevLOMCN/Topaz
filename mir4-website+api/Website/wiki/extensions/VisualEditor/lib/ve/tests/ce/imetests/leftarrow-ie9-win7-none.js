/*!
 * VisualEditor IME left arrow test for Internet Explorer on Windows.
 *
 * @copyright See AUTHORS.txt
 */

ve.ce.imetests.push( [ 'leftarrow-ie9-win7-none', [
	{ imeIdentifier: 'none', userAgent: 'Mozilla/5.0 (compatible; MSIE 9.0; Windows NT 6.1; Trident/5.0; SLCC2; .NET CLR 2.0.50727; .NET CLR 3.5.30729; .NET CLR 3.0.30729; Media Center PC 6.0; OfficeLiveConnector.1.3; OfficeLivePatch.0.0; .NET4.0C)', startDom: '' },
	{ seq: 0, time: 8.513, action: 'changeText', args: [ '𨋢' ] },
	{ seq: 1, time: 8.513, action: 'changeSel', args: [ 2, 2 ] },
	{ seq: 2, time: 8.513, action: 'sendEvent', args: [ 'keydown', { keyCode: 37 } ] },
	{ seq: 3, time: 8.532, action: 'changeSel', args: [ 0, 0 ] },
	{ seq: 4, time: 8.532, action: 'endLoop', args: [] },
	{ seq: 5, time: 8.652, action: 'sendEvent', args: [ 'keyup', { keyCode: 37 } ] },
	{ seq: 6, time: 8.665, action: 'endLoop', args: [] }
] ] );
