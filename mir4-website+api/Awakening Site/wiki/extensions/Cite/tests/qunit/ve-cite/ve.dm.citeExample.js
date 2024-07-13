'use strict';

/*!
 * VisualEditor DataModel Cite-specific example data sets.
 *
 * @copyright 2011-2018 VisualEditor Team's Cite sub-team and others; see AUTHORS.txt
 * @license MIT
 */

ve.dm.citeExample = {};

ve.dm.citeExample.baseUri = 'http://example.com/wiki/';

ve.dm.citeExample.createExampleDocument = function ( name, store, base ) {
	return ve.dm.example.createExampleDocumentFromObject(
		name, store, ve.dm.citeExample, base || ve.dm.citeExample.baseUri );
};

ve.dm.citeExample.refListItemClipboard = function ( text ) {
	return ve.dm.example.singleLine`
		<span class="reference-text">
		<div class="mw-content-ltr ve-ui-previewElement ve-ui-mwPreviewElement mw-body-content mw-parser-output">
			<span class="ve-ce-branchNode ve-ce-internalItemNode">
				<p class="ve-ce-branchNode ve-ce-contentBranchNode ve-ce-paragraphNode ve-ce-generated-wrapper">
					${ text }
				</p>
			</span>
		</div>
		</span>
	`;
};

ve.dm.citeExample.domToDataCases = {
	'mw:Reference': {
		// Wikitext:
		// Foo<ref name="bar" /> Baz<ref group="g1" name=":0">Quux</ref> Whee<ref name="bar">[[Bar]]
		// </ref> Yay<ref group="g1">No name</ref> Quux<ref name="bar">Different content</ref> Foo
		// <ref group="g1" name="foo" />
		//
		// <references group="g1"><ref group="g1" name="foo">Ref in refs</ref></references>
		body: ve.dm.example.singleLine`
			<p>
				Foo
				<sup about="#mwt1" class="mw-ref reference" data-mw='{"name":"ref","attrs":{"name":"bar"}}' id="cite_ref-bar-1-0" rel="dc:references" typeof="mw:Extension/ref">
					<a href="#cite_note-bar-1">[1]</a>
				</sup>
				 Baz
				<sup about="#mwt2" class="mw-ref reference" data-mw='{"name":"ref","body":{"html":"Quux"},"attrs":{"group":"g1","name":":0"}}' id="cite_ref-quux-2-0" rel="dc:references" typeof="mw:Extension/ref">
					<a href="#cite_note-.3A0-2">[g1 1]</a>
				</sup>
				 Whee
				<sup about="#mwt3" class="mw-ref reference" data-mw='{"name":"ref","body":{"html":"
				<a rel=\\"mw:WikiLink\\" href=\\"./Bar\\">Bar
				</a>"},"attrs":{"name":"bar"}}' id="cite_ref-bar-1-1" rel="dc:references" typeof="mw:Extension/ref">
					<a href="#cite_note-bar-1">[1]</a>
				</sup>
				 Yay
				${ /* This reference has .body.id instead of .body.html */'' }
				<sup about="#mwt4" class="mw-ref reference" data-mw='{"name":"ref","body":{"id":"mw-cite-3"},"attrs":{"group":"g1"}}' id="cite_ref-1-0" rel="dc:references" typeof="mw:Extension/ref">
					<a href="#cite_note-3">[g1 2]</a>
				</sup>
				 Quux
				<sup about="#mwt5" class="mw-ref reference" data-mw='{"name":"ref","body":{"html":"Different content"},"attrs":{"name":"bar"}}' id="cite_ref-bar-1-2" rel="dc:references" typeof="mw:Extension/ref">
					<a href="#cite_note-bar-1">[1]</a>
				</sup>
				 Foo
				<sup about="#mwt6" class="mw-ref reference" data-mw='{"name":"ref","attrs":{"group":"g1","name":"foo"}}'
					 id="cite_ref-foo-4" rel="dc:references" typeof="mw:Extension/ref">
					<a href="#cite_ref-foo-4">[g1 3]</a>
				</sup>
			</p>
			${ /* The HTML below is enriched to wrap reference contents in <span id="mw-cite-[...]"> */'' }
			${ /* which Parsoid doesn't do yet, but T88290 asks for */'' }
			<ol class="references" typeof="mw:Extension/references" about="#mwt7"
				data-mw='{"name":"references","body":{
				"html":"<sup about=\\"#mwt8\\" class=\\"mw-ref reference\\"
				 data-mw=&apos;{&amp;quot;name&amp;quot;:&amp;quot;ref&amp;quot;,&amp;quot;body&amp;quot;:{&amp;quot;html&amp;quot;:&amp;quot;Ref in refs&amp;quot;},
				&amp;quot;attrs&amp;quot;:{&amp;quot;group&amp;quot;:&amp;quot;g1&amp;quot;,&amp;quot;name&amp;quot;:&amp;quot;foo&amp;quot;}}&apos;
				 rel=\\"dc:references\\" typeof=\\"mw:Extension/ref\\">
				<a href=\\"#cite_note-foo-3\\">[3]</a></sup>"},"attrs":{"group":"g1"}}'>
				<li about="#cite_note-.3A0-2" id="cite_note-.3A0-2"><span rel="mw:referencedBy"><a href="#cite_ref-.3A0_2-0">↑</a></span> <span id="mw-cite-:0">Quux</span></li>
				<li about="#cite_note-3" id="cite_note-3"><span rel="mw:referencedBy"><a href="#cite_ref-3">↑</a></span> <span id="mw-cite-3">No name</span></li>
				<li about="#cite_note-foo-4" id="cite_note-foo-4"><span rel="mw:referencedBy"><a href="#cite_ref-foo_4-0">↑</a></span> <span id="mw-cite-foo">Ref in refs</span></li>
			</ol>
		`,
		fromDataBody: ve.dm.example.singleLine`
			<p>
				Foo
				<sup data-mw='{"name":"ref","attrs":{"name":"bar"}}' typeof="mw:Extension/ref">
				</sup>
				 Baz
				<sup data-mw='{"name":"ref","body":{"html":"Quux"},"attrs":{"group":"g1","name":":0"}}' typeof="mw:Extension/ref">
				</sup>
				 Whee
				<sup data-mw='{"name":"ref","body":{"html":"
				<a rel=\\"mw:WikiLink\\" href=\\"./Bar\\">Bar
				</a>"},"attrs":{"name":"bar"}}' typeof="mw:Extension/ref">
				</sup>
				 Yay
				<sup data-mw='{"name":"ref","body":{"id":"mw-cite-3"},"attrs":{"group":"g1"}}' typeof="mw:Extension/ref">
				</sup>
				 Quux
				<sup data-mw='{"name":"ref","body":{"html":"Different content"},"attrs":{"name":"bar"}}' typeof="mw:Extension/ref">
				</sup>
				 Foo
				<sup data-mw='{"name":"ref","attrs":{"group":"g1","name":"foo"}}'
					 typeof="mw:Extension/ref">
				</sup>
			</p>
			<div typeof="mw:Extension/references"
				 data-mw='{"name":"references","attrs":{"group":"g1"},"body":{
				"html":"<sup typeof=\\"mw:Extension/ref\\"
				 data-mw=&apos;{&amp;quot;name&amp;quot;:&amp;quot;ref&amp;quot;,&amp;quot;body&amp;quot;:{&amp;quot;html&amp;quot;:&amp;quot;Ref in refs&amp;quot;},
				&amp;quot;attrs&amp;quot;:{&amp;quot;group&amp;quot;:&amp;quot;g1&amp;quot;,&amp;quot;name&amp;quot;:&amp;quot;foo&amp;quot;}}&apos;>
				</sup>"}}'>
			</div>
		`,
		clipboardBody: ve.dm.example.singleLine`
			<p>
				Foo
				<sup typeof="mw:Extension/ref" data-mw='{"name":"ref","attrs":{"name":"bar"}}' class="mw-ref reference">
					<a style="counter-reset: mw-Ref 1;"><span class="mw-reflink-text">[1]</span></a>
				</sup>
				 Baz
				<sup typeof="mw:Extension/ref" data-mw='{"name":"ref","body":{"html":"Quux"},"attrs":{"group":"g1","name":":0"}}' class="mw-ref reference">
					<a data-mw-group="g1" style="counter-reset: mw-Ref 1;"><span class="mw-reflink-text">[g1 1]</span></a>
				</sup>
				 Whee
				<sup typeof="mw:Extension/ref" data-mw='{"name":"ref","body":{"html":"
				<a href=\\"./Bar\\" rel=\\"mw:WikiLink\\">Bar
				</a>"},"attrs":{"name":"bar"}}' class="mw-ref reference">
					<a style="counter-reset: mw-Ref 1;"><span class="mw-reflink-text">[1]</span></a>
				</sup>
				 Yay
				${ /* This reference has .body.id instead of .body.html */'' }
				<sup typeof="mw:Extension/ref" data-mw='{"name":"ref","body":{"id":"mw-cite-3","html":"No name"},"attrs":{"group":"g1"}}' class="mw-ref reference">
					<a data-mw-group="g1" style="counter-reset: mw-Ref 2;"><span class="mw-reflink-text">[g1 2]</span></a>
				</sup>
				 Quux
				<sup typeof="mw:Extension/ref" data-mw='{"name":"ref","body":{"html":"Different content"},"attrs":{"name":"bar"}}' class="mw-ref reference">
					<a style="counter-reset: mw-Ref 1;"><span class="mw-reflink-text">[1]</span></a>
				</sup>
				 Foo
				<sup typeof="mw:Extension/ref" data-mw='{"name":"ref","attrs":{"group":"g1","name":"foo"}}' class="mw-ref reference">
					<a data-mw-group="g1" style="counter-reset: mw-Ref 3;"><span class="mw-reflink-text">[g1 3]</span></a>
				</sup>
			</p>
			${ /* The HTML below is enriched to wrap reference contents in <span id="mw-cite-[...]"> */'' }
			${ /* which Parsoid doesn't do yet, but T88290 asks for */'' }
			<div typeof="mw:Extension/references"
				 data-mw='{"name":"references","attrs":{"group":"g1"},"body":{
				"html":"<sup typeof=\\"mw:Extension/ref\\"
				 data-mw=&apos;{&amp;quot;name&amp;quot;:&amp;quot;ref&amp;quot;,&amp;quot;attrs&amp;quot;:{&amp;quot;group&amp;quot;:&amp;quot;g1&amp;quot;,&amp;quot;name&amp;quot;:&amp;quot;foo&amp;quot;},&amp;quot;body&amp;quot;:{&amp;quot;html&amp;quot;:&amp;quot;Ref in refs&amp;quot;}}
				&apos; class=\\"mw-ref reference\\"><a data-mw-group=\\"g1\\" style=\\"counter-reset: mw-Ref 3;\\"><span class=\\"mw-reflink-text\\">[g1 3]</span></a></sup>"}}'>
					<ol class="mw-references references" data-mw-group="g1">
						<li>
							<a rel="mw:referencedBy" data-mw-group="g1"><span class="mw-linkback-text">↑ </span></a>
								 ${ ve.dm.citeExample.refListItemClipboard( 'Quux' ) }
						</li>
						<li>
							<a rel="mw:referencedBy" data-mw-group="g1"><span class="mw-linkback-text">↑ </span></a>
								 ${ ve.dm.citeExample.refListItemClipboard( 'No name' ) }
						</li>
						<li>
							<a rel="mw:referencedBy" data-mw-group="g1"><span class="mw-linkback-text">↑ </span></a>
								 ${ ve.dm.citeExample.refListItemClipboard( 'Ref in refs' ) }
						</li>
					</ol>
			</div>
		`,
		data: [
			{ type: 'paragraph' },
			'F', 'o', 'o',
			{
				type: 'mwReference',
				attributes: {
					listIndex: 0,
					listGroup: 'mwReference/',
					listKey: 'literal/bar',
					refGroup: '',
					mw: { name: 'ref', attrs: { name: 'bar' } },
					originalMw: '{"name":"ref","attrs":{"name":"bar"}}',
					contentsUsed: false
				}
			},
			{ type: '/mwReference' },
			' ', 'B', 'a', 'z',
			{
				type: 'mwReference',
				attributes: {
					listIndex: 1,
					listGroup: 'mwReference/g1',
					listKey: 'literal/:0',
					refGroup: 'g1',
					mw: { name: 'ref', body: { html: 'Quux' }, attrs: { group: 'g1', name: ':0' } },
					originalMw: '{"name":"ref","body":{"html":"Quux"},"attrs":{"group":"g1","name":":0"}}',
					contentsUsed: true
				}
			},
			{ type: '/mwReference' },
			' ', 'W', 'h', 'e', 'e',
			{
				type: 'mwReference',
				attributes: {
					listIndex: 0,
					listGroup: 'mwReference/',
					listKey: 'literal/bar',
					refGroup: '',
					mw: { name: 'ref', body: { html: '<a rel="mw:WikiLink" href="./Bar">Bar</a>' }, attrs: { name: 'bar' } },
					originalMw: '{"name":"ref","body":{"html":"<a rel=\\"mw:WikiLink\\" href=\\"./Bar\\">Bar</a>"},"attrs":{"name":"bar"}}',
					contentsUsed: true
				}
			},
			{ type: '/mwReference' },
			' ', 'Y', 'a', 'y',
			{
				type: 'mwReference',
				attributes: {
					listIndex: 2,
					listGroup: 'mwReference/g1',
					listKey: 'auto/0',
					refGroup: 'g1',
					mw: { name: 'ref', body: { id: 'mw-cite-3' }, attrs: { group: 'g1' } },
					originalMw: '{"name":"ref","body":{"id":"mw-cite-3"},"attrs":{"group":"g1"}}',
					contentsUsed: true,
					refListItemId: 'mw-cite-3'
				}
			},
			{ type: '/mwReference' },
			' ', 'Q', 'u', 'u', 'x',
			{
				type: 'mwReference',
				attributes: {
					listIndex: 0,
					listGroup: 'mwReference/',
					listKey: 'literal/bar',
					refGroup: '',
					mw: { name: 'ref', body: { html: 'Different content' }, attrs: { name: 'bar' } },
					originalMw: '{"name":"ref","body":{"html":"Different content"},"attrs":{"name":"bar"}}',
					contentsUsed: false
				}
			},
			{ type: '/mwReference' },
			' ', 'F', 'o', 'o',
			{
				type: 'mwReference',
				attributes: {
					listGroup: 'mwReference/g1',
					listIndex: 3,
					listKey: 'literal/foo',
					refGroup: 'g1',
					mw: { name: 'ref', attrs: { group: 'g1', name: 'foo' } },
					originalMw: '{"name":"ref","attrs":{"group":"g1","name":"foo"}}',
					contentsUsed: false
				}
			},
			{ type: '/mwReference' },
			{ type: '/paragraph' },
			{
				type: 'mwReferencesList',
				attributes: {
					mw: {
						name: 'references',
						attrs: { group: 'g1' },
						body: {
							html: ve.dm.example.singleLine`
								<sup about="#mwt8" class="mw-ref reference" data-mw='{&quot;name&quot;:&quot;ref&quot;,&quot;body&quot;:{&quot;html&quot;:&quot;Ref in refs&quot;},&quot;attrs&quot;:{&quot;group&quot;:&quot;g1&quot;,&quot;name&quot;:&quot;foo&quot;}}' rel="dc:references" typeof="mw:Extension/ref">
									<a href="#cite_note-foo-3">[3]</a>
								</sup>
							`
						}
					},
					originalMw: '{"name":"references","body":{"html":"<sup about=\\"#mwt8\\" class=\\"mw-ref reference\\" data-mw=\'{&quot;name&quot;:&quot;ref&quot;,&quot;body&quot;:{&quot;html&quot;:&quot;Ref in refs&quot;},&quot;attrs&quot;:{&quot;group&quot;:&quot;g1&quot;,&quot;name&quot;:&quot;foo&quot;}}\' rel=\\"dc:references\\" typeof=\\"mw:Extension/ref\\"><a href=\\"#cite_note-foo-3\\">[3]</a></sup>"},"attrs":{"group":"g1"}}',
					listGroup: 'mwReference/g1',
					refGroup: 'g1',
					isResponsive: true,
					templateGenerated: false
				}
			},
			{ type: 'paragraph', internal: { generated: 'wrapper' } },
			{
				type: 'mwReference',
				attributes: {
					contentsUsed: true,
					listGroup: 'mwReference/g1',
					listIndex: 3,
					listKey: 'literal/foo',
					mw: { name: 'ref', attrs: { group: 'g1', name: 'foo' }, body: { html: 'Ref in refs' } },
					originalMw: '{"name":"ref","body":{"html":"Ref in refs"},"attrs":{"group":"g1","name":"foo"}}',
					refGroup: 'g1'
				}
			},
			{ type: '/mwReference' },
			{ type: '/paragraph' },
			{ type: '/mwReferencesList' },
			{ type: 'internalList' },
			{ type: 'internalItem', attributes: { originalHtml: '<a rel="mw:WikiLink" href="./Bar">Bar</a>' } },
			{ type: 'paragraph', internal: { generated: 'wrapper' } },
			[
				'B',
				[ {
					type: 'link/mwInternal',
					attributes: {
						title: 'Bar',
						normalizedTitle: 'Bar',
						lookupTitle: 'Bar'
					}
				} ]
			],
			[
				'a',
				[ {
					type: 'link/mwInternal',
					attributes: {
						title: 'Bar',
						normalizedTitle: 'Bar',
						lookupTitle: 'Bar'
					}
				} ]
			],
			[
				'r',
				[ {
					type: 'link/mwInternal',
					attributes: {
						title: 'Bar',
						normalizedTitle: 'Bar',
						lookupTitle: 'Bar'
					}
				} ]
			],
			{ type: '/paragraph' },
			{ type: '/internalItem' },
			{ type: 'internalItem', attributes: { originalHtml: 'Quux' } },
			{ type: 'paragraph', internal: { generated: 'wrapper' } },
			'Q', 'u', 'u', 'x',
			{ type: '/paragraph' },
			{ type: '/internalItem' },
			{ type: 'internalItem', attributes: { originalHtml: 'No name' } },
			{ type: 'paragraph', internal: { generated: 'wrapper' } },
			'N', 'o', ' ', 'n', 'a', 'm', 'e',
			{ type: '/paragraph' },
			{ type: '/internalItem' },
			{ type: 'internalItem', attributes: { originalHtml: 'Ref in refs' } },
			{ type: 'paragraph', internal: { generated: 'wrapper' } },
			'R', 'e', 'f', ' ', 'i', 'n', ' ', 'r', 'e', 'f', 's',
			{ type: '/paragraph' },
			{ type: '/internalItem' },
			{ type: '/internalList' }
		]
	},
	'mw:Reference: Simple reference re-use (T296044)': {
		// Wikitext:
		// Foo<ref name="bar">[[Bar]]</ref> Baz<ref name="bar" />
		body: ve.dm.example.singleLine`
			<p>
				Foo
				<sup about="#mwt1" class="mw-ref reference" data-mw='{"name":"ref","body":{"html":"
				<a rel=\\"mw:WikiLink\\" href=\\"./Bar\\">Bar
				</a>"},"attrs":{"name":"bar"}}' id="cite_ref-bar-1-1" rel="dc:references" typeof="mw:Extension/ref">
					<a href="#cite_note-bar-1">[1]</a>
				</sup>
				 Baz
				<sup about="#mwt2" class="mw-ref reference" data-mw='{"name":"ref","attrs":{"name":"bar"}}' id="cite_ref-bar-1-3" rel="dc:references" typeof="mw:Extension/ref">
					<a href="#cite_note-bar-1">[1]</a>
				</sup>
			</p>
		`,
		fromDataBody: ve.dm.example.singleLine`
			<p>
				Foo
				<sup data-mw='{"name":"ref","body":{"html":"
				<a rel=\\"mw:WikiLink\\" href=\\"./Bar\\">Bar
				</a>"},"attrs":{"name":"bar"}}' typeof="mw:Extension/ref">
				</sup>
				 Baz
				<sup data-mw='{"name":"ref","attrs":{"name":"bar"}}' typeof="mw:Extension/ref">
				</sup>
			</p>
		`,
		clipboardBody: ve.dm.example.singleLine`
			<p>
				Foo
				<sup typeof="mw:Extension/ref" data-mw='{"name":"ref","body":{"html":"
				<a href=\\"./Bar\\" rel=\\"mw:WikiLink\\">Bar
				</a>"},"attrs":{"name":"bar"}}' class="mw-ref reference">
					<a style="counter-reset: mw-Ref 1;"><span class="mw-reflink-text">[1]</span></a>
				</sup>
				 Baz
				<sup typeof="mw:Extension/ref" data-mw='{"name":"ref","attrs":{"name":"bar"}}' class="mw-ref reference">
					<a style="counter-reset: mw-Ref 1;"><span class="mw-reflink-text">[1]</span></a>
				</sup>
			</p>
		`,
		data: [
			{ type: 'paragraph' },
			'F', 'o', 'o',
			{
				type: 'mwReference',
				attributes: {
					listIndex: 0,
					listGroup: 'mwReference/',
					listKey: 'literal/bar',
					refGroup: '',
					mw: { name: 'ref', body: { html: '<a rel="mw:WikiLink" href="./Bar">Bar</a>' }, attrs: { name: 'bar' } },
					originalMw: '{"name":"ref","body":{"html":"<a rel=\\"mw:WikiLink\\" href=\\"./Bar\\">Bar</a>"},"attrs":{"name":"bar"}}',
					contentsUsed: true
				}
			},
			{ type: '/mwReference' },
			' ', 'B', 'a', 'z',
			{
				type: 'mwReference',
				attributes: {
					listIndex: 0,
					listGroup: 'mwReference/',
					listKey: 'literal/bar',
					refGroup: '',
					mw: { name: 'ref', attrs: { name: 'bar' } },
					originalMw: '{"name":"ref","attrs":{"name":"bar"}}',
					contentsUsed: false
				}
			},
			{ type: '/mwReference' },
			{ type: '/paragraph' },
			{ type: 'internalList' },
			{ type: 'internalItem', attributes: { originalHtml: '<a rel="mw:WikiLink" href="./Bar">Bar</a>' } },
			{ type: 'paragraph', internal: { generated: 'wrapper' } },
			[
				'B',
				[ {
					type: 'link/mwInternal',
					attributes: {
						title: 'Bar',
						normalizedTitle: 'Bar',
						lookupTitle: 'Bar'
					}
				} ]
			],
			[
				'a',
				[ {
					type: 'link/mwInternal',
					attributes: {
						title: 'Bar',
						normalizedTitle: 'Bar',
						lookupTitle: 'Bar'
					}
				} ]
			],
			[
				'r',
				[ {
					type: 'link/mwInternal',
					attributes: {
						title: 'Bar',
						normalizedTitle: 'Bar',
						lookupTitle: 'Bar'
					}
				} ]
			],
			{ type: '/paragraph' },
			{ type: '/internalItem' },
			{ type: '/internalList' }
		]
	},
	'mw:Reference with comment': {
		body: ve.dm.example.singleLine`
			<p>
				<sup about="#mwt2" class="mw-ref reference"
				 data-mw='{"name":"ref","body":
				{"html":"Foo<!-- bar -->"},"attrs":{}}'
				 id="cite_ref-1-0" rel="dc:references" typeof="mw:Extension/ref">
					<a href="#cite_note-bar-1">[1]</a>
				</sup>
			</p>
		`,
		fromDataBody: ve.dm.example.singleLine`
			<p>
				<sup
				 data-mw='{"name":"ref","body":
				{"html":"Foo<!-- bar -->"},"attrs":{}}'
				 typeof="mw:Extension/ref"></sup>
			</p>
		`,
		clipboardBody: ve.dm.example.singleLine`
			<p>
				<sup typeof="mw:Extension/ref"
				 data-mw='{"attrs":{},"body":
			{"html":"Foo<span rel=\\"ve:Comment\\" data-ve-comment=\\" bar \\">&amp;nbsp;</span>"},"name":"ref"}'
			 class="mw-ref reference">
					<a style="counter-reset: mw-Ref 1;"><span class="mw-reflink-text">[1]</span></a>
				</sup>
			</p>
		`,
		previewBody: ve.dm.example.singleLine`
			<p>
				<sup typeof="mw:Extension/ref"
				 data-mw='{"attrs":{},"body":
				{"html":"Foo<!-- bar -->"},"name":"ref"}'
				 class="mw-ref reference">
					<a style="counter-reset: mw-Ref 1;"><span class="mw-reflink-text">[1]</span></a>
				</sup>
			</p>
		`,
		data: [
			{ type: 'paragraph' },
			{
				type: 'mwReference',
				attributes: {
					contentsUsed: true,
					listGroup: 'mwReference/',
					listIndex: 0,
					listKey: 'auto/0',
					mw: {
						attrs: {},
						body: {
							html: 'Foo<!-- bar -->'
						},
						name: 'ref'
					},
					originalMw: '{"name":"ref","body":{"html":"Foo<!-- bar -->"},"attrs":{}}',
					refGroup: ''
				}
			},
			{ type: '/mwReference' },
			{ type: '/paragraph' },
			{ type: 'internalList' },
			{ type: 'internalItem', attributes: { originalHtml: 'Foo<!-- bar -->' } },
			{
				internal: {
					generated: 'wrapper'
				},
				type: 'paragraph'
			},
			'F', 'o', 'o',
			{
				type: 'comment',
				attributes: {
					text: ' bar '
				}
			},
			{ type: '/comment' },
			{ type: '/paragraph' },
			{ type: '/internalItem' },
			{ type: '/internalList' }
		]
	},
	'Template generated reflist': {
		body: ve.dm.example.singleLine`
			<p><sup about="#mwt2" class="mw-ref reference" id="cite_ref-1" rel="dc:references" typeof="mw:Extension/ref" data-mw='{"name":"ref","body":{"id":"mw-reference-text-cite_note-1"},"attrs":{"group":"notes"}}'><a href="./Main_Page#cite_note-1" style="counter-reset: mw-Ref 1;" data-mw-group="notes"><span class="mw-reflink-text">[notes 1]</span></a></sup></p>
			<div class="mw-references-wrap" typeof="mw:Extension/references mw:Transclusion" about="#mwt4" data-mw='{"parts":[{"template":{"target":{"wt":"echo","href":"./Template:Echo"},"params":{"1":{"wt":"<references group=\\"notes\\" />"}},"i":0}}]}'>
				<ol class="mw-references references" data-mw-group="notes">
					<li about="#cite_note-1" id="cite_note-1"><a href="./Main_Page#cite_ref-1" data-mw-group="notes" rel="mw:referencedBy"><span class="mw-linkback-text">↑ </span></a> <span id="mw-reference-text-cite_note-1" class="mw-reference-text">Foo</span></li>
				</ol>
			</div>
		`,
		fromDataBody: ve.dm.example.singleLine`
			<p><sup typeof="mw:Extension/ref" data-mw='{"name":"ref","body":{"id":"mw-reference-text-cite_note-1"},"attrs":{"group":"notes"}}'></sup></p>
			<span typeof="mw:Transclusion" data-mw='{"parts":[{"template":{"target":{"wt":"echo","href":"./Template:Echo"},"params":{"1":{"wt":"<references group=\\"notes\\" />"}},"i":0}}]}'></span>
		`,
		clipboardBody: ve.dm.example.singleLine`
			<p><sup typeof="mw:Extension/ref" data-mw='{"attrs":{"group":"notes"},"body":{"id":"mw-reference-text-cite_note-1","html":"Foo"},"name":"ref"}' class="mw-ref reference"><a data-mw-group="notes" style="counter-reset: mw-Ref 1;"><span class="mw-reflink-text">[notes 1]</span></a></sup></p>
			<div typeof="mw:Extension/references" data-mw='{"parts":[{"template":{"params":{"1":{"wt":"<references group=\\"notes\\" />"}},"target":{"wt":"echo","href":"./Template:Echo"},"i":0}}],"name":"references"}'>
				${ /* TODO: This should list should get populated on copy */'' }
				<ol class="mw-references references"></ol>
			</div>
		`,
		previewBody: false,
		data: [
			{ type: 'paragraph' },
			{
				type: 'mwReference',
				attributes: {
					contentsUsed: true,
					listGroup: 'mwReference/notes',
					listIndex: 0,
					listKey: 'auto/0',
					mw: {
						attrs: {
							group: 'notes'
						},
						body: {
							id: 'mw-reference-text-cite_note-1'
						},
						name: 'ref'
					},
					originalMw: '{"name":"ref","body":{"id":"mw-reference-text-cite_note-1"},"attrs":{"group":"notes"}}',
					refGroup: 'notes',
					refListItemId: 'mw-reference-text-cite_note-1'
				}
			},
			{ type: '/mwReference' },
			{ type: '/paragraph' },
			{
				type: 'mwReferencesList',
				attributes: {
					mw: {
						parts: [ {
							template: {
								params: {
									1: { wt: '<references group="notes" />' }
								},
								target: { wt: 'echo', href: './Template:Echo' },
								i: 0
							}
						} ]
					},
					originalMw: '{"parts":[{"template":{"target":{"wt":"echo","href":"./Template:Echo"},"params":{"1":{"wt":"<references group=\\"notes\\" />"}},"i":0}}]}',
					refGroup: '',
					listGroup: 'mwReference/',
					isResponsive: true,
					templateGenerated: true
				}
			},
			{ type: '/mwReferencesList' },
			{ type: 'internalList' },
			{ type: 'internalItem', attributes: { originalHtml: 'Foo' } },
			{
				internal: {
					generated: 'wrapper'
				},
				type: 'paragraph'
			},
			'F', 'o', 'o',
			{ type: '/paragraph' },
			{ type: '/internalItem' },
			{ type: '/internalList' }
		]
	},
	'Template generated reflist (div wrapped)': {
		body: ve.dm.example.singleLine`
			<p><sup about="#mwt2" class="mw-ref reference" id="cite_ref-1" rel="dc:references" typeof="mw:Extension/ref" data-mw='{"name":"ref","body":{"id":"mw-reference-text-cite_note-1"},"attrs":{}}'><a href="./Main_Page#cite_note-1" style="counter-reset: mw-Ref 1;"><span class="mw-reflink-text">[1]</span></a></sup></p>
			<div about="#mwt3" typeof="mw:Transclusion" data-mw='{"parts":[{"template":{"target":{"wt":"reflist","href":"./Template:Reflist"},"params":{},"i":0}}]}'>
				<div typeof="mw:Extension/references" about="#mwt5" data-mw='{"name":"references","attrs":{}}'>
					<ol class="mw-references references">
						<li about="#cite_note-1" id="cite_note-1"><a href="./Main_Page#cite_ref-1" rel="mw:referencedBy"><span class="mw-linkback-text">↑ </span></a> <span id="mw-reference-text-cite_note-1" class="mw-reference-text">Foo</span></li>
					</ol>
				</div>
			</div>
		`,
		fromDataBody: ve.dm.example.singleLine`
			<p><sup typeof="mw:Extension/ref" data-mw='{"name":"ref","body":{"id":"mw-reference-text-cite_note-1"},"attrs":{}}'></sup></p>
			<span typeof="mw:Transclusion" data-mw='{"name":"references","attrs":{}}'></span>
		`,
		clipboardBody: ve.dm.example.singleLine`
			<p><sup typeof="mw:Extension/ref" data-mw='{"attrs":{},"body":{"id":"mw-reference-text-cite_note-1","html":"Foo"},"name":"ref"}' class="mw-ref reference"><a style="counter-reset: mw-Ref 1;"><span class="mw-reflink-text">[1]</span></a></sup></p>
			<div typeof="mw:Extension/references" data-mw='{"name":"references","attrs":{}}'>
				<ol class="mw-references references">
					<li>
						<a rel="mw:referencedBy"><span class="mw-linkback-text">↑ </span></a>
							 ${ ve.dm.citeExample.refListItemClipboard( 'Foo' ) }
					</li>
				</ol>
			</div>
		`,
		previewBody: false,
		data: [
			{ type: 'paragraph' },
			{
				type: 'mwReference',
				attributes: {
					contentsUsed: true,
					listGroup: 'mwReference/',
					listIndex: 0,
					listKey: 'auto/0',
					mw: {
						attrs: {},
						body: {
							id: 'mw-reference-text-cite_note-1'
						},
						name: 'ref'
					},
					originalMw: '{"name":"ref","body":{"id":"mw-reference-text-cite_note-1"},"attrs":{}}',
					refGroup: '',
					refListItemId: 'mw-reference-text-cite_note-1'
				}
			},
			{ type: '/mwReference' },
			{ type: '/paragraph' },
			{
				type: 'mwReferencesList',
				attributes: {
					mw: {
						name: 'references',
						attrs: {}
					},
					originalMw: '{"name":"references","attrs":{}}',
					refGroup: '',
					listGroup: 'mwReference/',
					isResponsive: true,
					templateGenerated: true
				}
			},
			{ type: '/mwReferencesList' },
			{ type: 'internalList' },
			{ type: 'internalItem', attributes: { originalHtml: 'Foo' } },
			{
				internal: {
					generated: 'wrapper'
				},
				type: 'paragraph'
			},
			'F', 'o', 'o',
			{ type: '/paragraph' },
			{ type: '/internalItem' },
			{ type: '/internalList' }
		]
	},
	'Extend reference': {
		body: ve.dm.example.singleLine`
			<p>
				<sup typeof="mw:Extension/ref" class="mw-ref reference"
				 data-mw='{"name":"ref","body":{"html":"Bar"},"attrs":{"extends":"foo"}}'>
				</sup>
			</p>
		`,
		fromDataBody: ve.dm.example.singleLine`
			<p>
				<sup typeof="mw:Extension/ref"
				 data-mw='{"name":"ref","body":{"html":"Bar"},"attrs":{"extends":"foo"}}'>
				</sup>
			</p>
		`,
		clipboardBody: ve.dm.example.singleLine`
			<p>
				<sup typeof="mw:Extension/ref"
				 data-mw='{"name":"ref","body":{"html":"Bar"},"attrs":{"extends":"foo"}}'
				 class="mw-ref reference">
					<a style="counter-reset: mw-Ref 1;">
						<span class="mw-reflink-text">[1]</span>
					</a>
				</sup>
			</p>
		`,
		data: [
			{ type: 'paragraph' },
			{
				type: 'mwReference',
				attributes: {
					contentsUsed: true,
					extendsRef: 'foo',
					listGroup: 'mwReference/',
					listIndex: 0,
					listKey: 'auto/0',
					mw: {
						attrs: { extends: 'foo' },
						body: { html: 'Bar' },
						name: 'ref'
					},
					originalMw: '{"name":"ref","body":{"html":"Bar"},"attrs":{"extends":"foo"}}',
					refGroup: ''
				}
			},
			{ type: '/mwReference' },
			{ type: '/paragraph' },
			{ type: 'internalList' },
			{
				attributes: { originalHtml: 'Bar' },
				type: 'internalItem'
			},
			{
				internal: { generated: 'wrapper' },
				type: 'paragraph'
			},
			'B', 'a', 'r',
			{ type: '/paragraph' },
			{ type: '/internalItem' },
			{ type: '/internalList' }
		]
	}
};

ve.dm.citeExample.references = [
	{ type: 'paragraph' },
	{
		type: 'mwReference',
		attributes: {
			contentsUsed: true,
			listGroup: 'mwReference/',
			listIndex: 0,
			listKey: 'auto/0',
			mw: {
				attrs: {},
				body: { html: 'No name 1' },
				name: 'ref'
			},
			originalMw: '{"name":"ref","body":{"html":"No name 1"},"attrs":{}}',
			refGroup: ''
		}
	},
	{ type: '/mwReference' },
	{ type: '/paragraph' },
	{ type: 'paragraph' },
	'F', 'o', 'o',
	{
		type: 'mwReference',
		attributes: {
			contentsUsed: true,
			listGroup: 'mwReference/',
			listIndex: 1,
			listKey: 'literal/bar',
			mw: {
				attrs: { name: 'bar' },
				body: { html: 'Bar' },
				name: 'ref'
			},
			originalMw: '{"body":{"html":""},"attrs":{"name":"bar"}}',
			refGroup: ''
		}
	},
	{ type: '/mwReference' },
	' ', 'B', 'a', 'z',
	{
		type: 'mwReference',
		attributes: {
			contentsUsed: true,
			listGroup: 'mwReference/',
			listIndex: 2,
			listKey: 'literal/:3',
			mw: {
				attrs: { name: ':3' },
				body: { html: 'Quux' },
				name: 'ref'
			},
			originalMw: '{"name":"ref","body":{"html":"Quux"},"attrs":{"name":":3"}}',
			refGroup: ''
		}
	},
	{ type: '/mwReference' },
	' ', 'W', 'h', 'e', 'e',
	{
		type: 'mwReference',
		attributes: {
			contentsUsed: false,
			listGroup: 'mwReference/',
			listIndex: 1,
			listKey: 'literal/bar',
			mw: {
				attrs: { name: 'bar' },
				name: 'ref'
			},
			originalMw: '{"body":{"html":""},"attrs":{"name":"bar"}}',
			refGroup: ''
		}
	},
	{ type: '/mwReference' },
	' ', 'Y', 'a', 'y',
	{ type: '/paragraph' },
	{ type: 'paragraph' },
	{
		type: 'mwReference',
		attributes: {
			contentsUsed: true,
			listGroup: 'mwReference/',
			listIndex: 3,
			listKey: 'auto/1',
			mw: {
				attrs: {},
				body: { html: 'No name 2' },
				name: 'ref'
			},
			originalMw: '{"name":"ref","body":{"html":"No name 2"},"attrs":{}}',
			refGroup: ''
		}
	},
	{ type: '/mwReference' },
	{
		type: 'mwReference',
		attributes: {
			contentsUsed: true,
			listGroup: 'mwReference/foo',
			listIndex: 4,
			listKey: 'auto/2',
			mw: {
				attrs: { group: 'foo' },
				body: { html: 'No name 3' },
				name: 'ref'
			},
			originalMw: '{"name":"ref","body":{"html":"No name 3"},"attrs":{"group":"foo"}}',
			refGroup: 'foo'
		}
	},
	{ type: '/mwReference' },
	{ type: '/paragraph' },
	{
		type: 'mwReferencesList',
		// originalDomElements: HTML,
		attributes: {
			mw: {
				name: 'references',
				attrs: { group: 'g1' }
			},
			originalMw: '{"name":"references","attrs":{"group":"g1"}"}',
			listGroup: 'mwReference/',
			refGroup: '',
			isResponsive: true,
			templateGenerated: false
		}
	},
	{ type: '/mwReferencesList' },
	{ type: 'internalList' },
	{ type: 'internalItem' },
	{ type: 'paragraph' },
	'N', 'o', ' ', 'n', 'a', 'm', 'e', ' ', '1',
	{ type: '/paragraph' },
	{ type: '/internalItem' },
	{ type: 'internalItem' },
	{ type: 'paragraph' },
	'B', 'a', 'r',
	{ type: '/paragraph' },
	{ type: '/internalItem' },
	{ type: 'internalItem' },
	{ type: 'paragraph' },
	'Q', 'u', 'u', 'x',
	{ type: '/paragraph' },
	{ type: '/internalItem' },
	{ type: 'internalItem' },
	{ type: 'paragraph' },
	'N', 'o', ' ', 'n', 'a', 'm', 'e', ' ', '2',
	{ type: '/paragraph' },
	{ type: '/internalItem' },
	{ type: 'internalItem' },
	{ type: 'paragraph' },
	'N', 'o', ' ', 'n', 'a', 'm', 'e', ' ', '3',
	{ type: '/paragraph' },
	{ type: '/internalItem' },
	{ type: '/internalList' }
];

ve.dm.citeExample.complexInternalData = [
	// 0
	{ type: 'paragraph' },
	'F', [ 'o', [ ve.dm.example.bold ] ], [ 'o', [ ve.dm.example.italic ] ],
	// 4
	{ type: 'mwReference', attributes: {
		about: '#mwt1',
		listIndex: 0,
		listGroup: 'mwReference/',
		listKey: 'auto/0',
		refGroup: ''
	} },
	// 5
	{ type: '/mwReference' },
	// 6
	{ type: '/paragraph' },
	// 7
	{ type: 'internalList' },
	// 8
	{ type: 'internalItem' },
	// 9
	{ type: 'paragraph', internal: { generated: 'wrapper' } },
	'R', [ 'e', [ ve.dm.example.bold ] ], 'f',
	// 13
	'e', [ 'r', [ ve.dm.example.italic ] ], [ 'e', [ ve.dm.example.italic ] ],
	// 16
	{ type: 'mwReference', attributes: {
		mw: {},
		about: '#mwt2',
		listIndex: 1,
		listGroup: 'mwReference/',
		listKey: 'foo',
		refGroup: '',
		contentsUsed: true
	} },
	// 17
	{ type: '/mwReference' },
	'n', 'c', 'e',
	// 21
	{ type: '/paragraph' },
	// 22
	{ type: '/internalItem' },
	// 23
	{ type: 'internalItem' },
	// 24
	{ type: 'preformatted' },
	// 25
	{ type: 'mwEntity', attributes: { character: '€' } },
	// 26
	{ type: '/mwEntity' },
	'2', '5', '0',
	// 30
	{ type: '/preformatted' },
	// 31
	{ type: '/internalItem' },
	// 32
	{ type: '/internalList' }
	// 33
];

ve.dm.citeExample.complexInternalData.internalItems = [
	{ group: 'mwReference', key: null, body: 'First reference' },
	{ group: 'mwReference', key: 'foo', body: 'Table in ref: <table><tr><td>because I can</td></tr></table>' }
];

ve.dm.citeExample.complexInternalData.internalListNextUniqueNumber = 1;
