/*!
 * VisualEditor IME test for Internet Explorer on Windows in Taditional Chinese Handwriting.
 *
 * @copyright See AUTHORS.txt
 */

ve.ce.imetests.push( [ 'input-ie9-win7-chinese-traditional-handwriting', [
	{ imeIdentifier: 'Chinese Traditional Handwriting', userAgent: 'Mozilla/5.0 (compatible; MSIE 9.0; Windows NT 6.1; Trident/5.0; SLCC2; .NET CLR 2.0.50727; .NET CLR 3.5.30729; .NET CLR 3.0.30729; Media Center PC 6.0; OfficeLiveConnector.1.3; OfficeLivePatch.0.0; .NET4.0C)', startDom: '' },
	{ seq: 0, time: 33.748, action: 'sendEvent', args: [ 'compositionstart', {} ] },
	{ seq: 1, time: 33.752, action: 'changeText', args: [ '中' ] },
	{ seq: 2, time: 33.752, action: 'changeSel', args: [ 1, 1 ] },
	{ seq: 3, time: 33.752, action: 'sendEvent', args: [ 'compositionend', {} ] },
	{ seq: 4, time: 33.846, action: 'endLoop', args: [] },
	{ seq: 5, time: 49.047, action: 'sendEvent', args: [ 'compositionstart', {} ] },
	{ seq: 6, time: 49.049, action: 'changeText', args: [ '中國' ] },
	{ seq: 7, time: 49.049, action: 'changeSel', args: [ 2, 2 ] },
	{ seq: 8, time: 49.049, action: 'sendEvent', args: [ 'compositionend', {} ] },
	{ seq: 9, time: 49.091, action: 'endLoop', args: [] }
] ] );
