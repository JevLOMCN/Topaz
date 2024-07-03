/*!
 * VisualEditor MediaWiki-specific ContentEditable Surface tests.
 *
 * @copyright See AUTHORS.txt
 * @license The MIT License (MIT); see LICENSE.txt
 */

QUnit.module( 've.ce.Surface (MW)', ve.test.utils.newMwEnvironment() );

/* Tests */

QUnit.test( 'beforePaste/afterPaste', ( assert ) => {
	const cases = [
		{
			documentHtml: '<p></p>',
			rangeOrSelection: new ve.Range( 1 ),
			pasteHtml: '<span typeof="mw:Entity" id="mwAB">-</span><span typeof="mw:Entity" id="mw-reference-cite">-</span>',
			fromVe: true,
			expectedRangeOrSelection: new ve.Range( 5 ),
			expectedHtml: '<p><span typeof="mw:Entity">-</span><span typeof="mw:Entity" id="mw-reference-cite">-</span></p>',
			msg: 'RESTBase IDs stripped'
		},
		{
			documentHtml: '<p></p>',
			rangeOrSelection: new ve.Range( 1 ),
			pasteHtml: '<span typeof="mw:Entity" id="mwAB">-</span><span typeof="mw:Entity" id="mw-reference-cite">-</span>',
			pasteTargetHtml: '<span>-</span><span>-</span>',
			fromVe: true,
			expectedRangeOrSelection: new ve.Range( 5 ),
			expectedHtml: '<p><span typeof="mw:Entity">-</span><span typeof="mw:Entity" id="mw-reference-cite">-</span></p>',
			msg: 'RESTBase IDs still stripped if used when important attributes dropped'
		},
		{
			documentHtml: '<p></p>',
			rangeOrSelection: new ve.Range( 1 ),
			pasteHtml: 'a<sup id="cite_ref-1" class="reference"><a href="./Article#cite_note-1">[1]</a></sup>b',
			expectedRangeOrSelection: new ve.Range( 3 ),
			expectedHtml: '<p>ab</p>',
			msg: 'Read mode references stripped'
		},
		{
			documentHtml: '<p></p>',
			rangeOrSelection: new ve.Range( 1 ),
			pasteHtml: 'a<sup typeof="mw:Extension/ref" data-mw="{}" class="mw-ref reference" about="#mwt1" id="cite_ref-foo-0" rel="dc:references"><a href="./Article#cite_note-foo-0"><span class="mw-reflink-text ve-pasteProtect">[1]</span></a></sup>b',
			expectedRangeOrSelection: new ve.Range( 5 ),
			expectedHtml: '<p>a<sup typeof="mw:Extension/ref" data-mw="{&quot;name&quot;:&quot;ref&quot;}" class="mw-ref reference"><a style="counter-reset: mw-Ref 1;"><span class="mw-reflink-text">[1]</span></a></sup>b</p>',
			msg: 'VE references not stripped'
		},
		{
			documentHtml: '<p></p>',
			rangeOrSelection: new ve.Range( 1 ),
			pasteHtml: '<a href="https://example.net/">Lorem</a> <a href="not-a-protocol:Some%20text">ipsum</a> <a href="mailto:example@example.net">dolor</a> <a href="javascript:alert()">sit amet</a>',
			expectedRangeOrSelection: new ve.Range( 27 ),
			// hrefs with invalid protocols get removed by DOMPurify, and these links become spans in
			// ve.dm.LinkAnnotation.static.toDataElement (usually the span is stripped later)
			expectedHtml: '<p>Lorem <span>ipsum</span> dolor <span>sit amet</span></p>',
			config: {
				importRules: {
					external: {
						blacklist: {
							'link/mwExternal': true
						}
					}
				}
			},
			msg: 'External links stripped'
		},
		{
			documentHtml: '<p></p>',
			rangeOrSelection: new ve.Range( 1 ),
			pasteHtml: '<a href="https://example.net/">Lorem</a> <a href="not-a-protocol:Some%20text">ipsum</a> <a href="mailto:example@example.net">dolor</a> <a href="javascript:alert()">sit amet</a>',
			expectedRangeOrSelection: new ve.Range( 27 ),
			// hrefs with invalid protocols get removed by DOMPurify, and these links become spans in
			// ve.dm.LinkAnnotation.static.toDataElement (usually the span is stripped later)
			expectedHtml: '<p><a href="https://example.net/" rel="mw:ExtLink">Lorem</a> <span>ipsum</span> <a href="mailto:example@example.net" rel="mw:ExtLink">dolor</a> <span>sit amet</span></p>',
			config: {
				importRules: {
					external: {
						blacklist: {
							'link/mwExternal': false
						}
					}
				}
			},
			msg: 'External links not stripped, but only some protocols allowed'
		}
	];

	cases.forEach( ve.test.utils.runSurfacePasteTest.bind( this, assert ) );
} );
