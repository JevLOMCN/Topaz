/*!
 * Parsoid utilities tests.
 *
 * @copyright See AUTHORS.txt
 * @license The MIT License (MIT); see LICENSE.txt
 */

QUnit.module( 've.utils.parsoid', ve.test.utils.newMwEnvironment() );

QUnit.test( 'reduplicateStyles/deduplicateStyles', ( assert ) => {
	// Test cases based on this page and the templates there:
	// https://en.wikipedia.beta.wmflabs.org/wiki/Table_templated
	const stylesCases = [
		{
			msg: 'styles are deduplicated',
			deduplicated: `<span about="#mwt1" typeof="mw:Transclusion" data-parsoid='{"pi":[[],[{"k":"1"},{"k":"2"}],[{"k":"1"},{"k":"2"}],[{"k":"1"},{"k":"2"}],[]],"dsr":[0,107,null,null]}' data-mw='{"parts":[{"template":{"target":{"wt":"table start","href":"./Template:Table_start"},"params":{},"i":0}},"\\n",{"template":{"target":{"wt":"table row","href":"./Template:Table_row"},"params":{"1":{"wt":"Hello"},"2":{"wt":"good"}},"i":1}},"\\n",{"template":{"target":{"wt":"table row","href":"./Template:Table_row"},"params":{"1":{"wt":"Goodbye"},"2":{"wt":"bad"}},"i":2}},"\\n",{"template":{"target":{"wt":"table row","href":"./Template:Table_row"},"params":{"1":{"wt":"Welcome"},"2":{"wt":"good"}},"i":3}},"\\n",{"template":{"target":{"wt":"table end","href":"./Template:Table_end"},"params":{},"i":4}}]}'>
</span><table class="wikitable" about="#mwt1">
<tbody><tr>
<td class="good"><style data-mw-deduplicate="TemplateStyles:r5432" typeof="mw:Extension/templatestyles" about="#mwt4" data-mw='{"name":"templatestyles","attrs":{"src":"Table row/styles.css"}}'>.mw-parser-output .good{background:#9EFF9E}.mw-parser-output .bad{background:#FFC7C7}</style>Hello</td></tr>
<tr>
<td class="bad"><link rel="mw-deduplicated-inline-style" href="mw-data:TemplateStyles:r5432" about="#mwt7" typeof="mw:Extension/templatestyles" data-mw='{"name":"templatestyles","attrs":{"src":"Table row/styles.css"}}'/>Goodbye</td></tr>
<tr>
<td class="good"><link rel="mw-deduplicated-inline-style" href="mw-data:TemplateStyles:r5432" about="#mwt10" typeof="mw:Extension/templatestyles" data-mw='{"name":"templatestyles","attrs":{"src":"Table row/styles.css"}}'/>Welcome</td></tr>
</tbody></table>`,
			reduplicated: `<span about="#mwt1" typeof="mw:Transclusion" data-parsoid='{"pi":[[],[{"k":"1"},{"k":"2"}],[{"k":"1"},{"k":"2"}],[{"k":"1"},{"k":"2"}],[]],"dsr":[0,107,null,null]}' data-mw='{"parts":[{"template":{"target":{"wt":"table start","href":"./Template:Table_start"},"params":{},"i":0}},"\\n",{"template":{"target":{"wt":"table row","href":"./Template:Table_row"},"params":{"1":{"wt":"Hello"},"2":{"wt":"good"}},"i":1}},"\\n",{"template":{"target":{"wt":"table row","href":"./Template:Table_row"},"params":{"1":{"wt":"Goodbye"},"2":{"wt":"bad"}},"i":2}},"\\n",{"template":{"target":{"wt":"table row","href":"./Template:Table_row"},"params":{"1":{"wt":"Welcome"},"2":{"wt":"good"}},"i":3}},"\\n",{"template":{"target":{"wt":"table end","href":"./Template:Table_end"},"params":{},"i":4}}]}'>
</span><table class="wikitable" about="#mwt1">
<tbody><tr>
<td class="good"><style data-mw-deduplicate="TemplateStyles:r5432" typeof="mw:Extension/templatestyles" about="#mwt4" data-mw='{"name":"templatestyles","attrs":{"src":"Table row/styles.css"}}'>.mw-parser-output .good{background:#9EFF9E}.mw-parser-output .bad{background:#FFC7C7}</style>Hello</td></tr>
<tr>
<td class="bad"><style data-mw-deduplicate="TemplateStyles:r5432" typeof="mw:Extension/templatestyles" about="#mwt7" data-mw='{"name":"templatestyles","attrs":{"src":"Table row/styles.css"}}'>.mw-parser-output .good{background:#9EFF9E}.mw-parser-output .bad{background:#FFC7C7}</style>Goodbye</td></tr>
<tr>
<td class="good"><style data-mw-deduplicate="TemplateStyles:r5432" typeof="mw:Extension/templatestyles" about="#mwt10" data-mw='{"name":"templatestyles","attrs":{"src":"Table row/styles.css"}}'>.mw-parser-output .good{background:#9EFF9E}.mw-parser-output .bad{background:#FFC7C7}</style>Welcome</td></tr>
</tbody></table>`
		},
		{
			msg: 'styles in fosterable positions are NOT deduplicated, but they are emptied',
			deduplicated: `<span about="#mwt1" typeof="mw:Transclusion" data-parsoid='{"pi":[[],[{"k":"1"},{"k":"2"}],[{"k":"1"},{"k":"2"}],[{"k":"1"},{"k":"2"}],[]],"dsr":[0,107,null,null]}' data-mw='{"parts":[{"template":{"target":{"wt":"table start","href":"./Template:Table_start"},"params":{},"i":0}},"\\n",{"template":{"target":{"wt":"table row","href":"./Template:Table_row"},"params":{"1":{"wt":"Hello"},"2":{"wt":"good"}},"i":1}},"\\n",{"template":{"target":{"wt":"table row","href":"./Template:Table_row"},"params":{"1":{"wt":"Goodbye"},"2":{"wt":"bad"}},"i":2}},"\\n",{"template":{"target":{"wt":"table row","href":"./Template:Table_row"},"params":{"1":{"wt":"Welcome"},"2":{"wt":"good"}},"i":3}},"\\n",{"template":{"target":{"wt":"table end","href":"./Template:Table_end"},"params":{},"i":4}}]}'>
</span><table class="wikitable" about="#mwt1">
<tbody><tr>
<style data-mw-deduplicate="TemplateStyles:r5432" typeof="mw:Extension/templatestyles" about="#mwt4" data-mw='{"name":"templatestyles","attrs":{"src":"Table row/styles.css"}}'>.mw-parser-output .good{background:#9EFF9E}.mw-parser-output .bad{background:#FFC7C7}</style>
<td class="good">Hello</td></tr>
<tr>
<style data-mw-deduplicate="TemplateStyles:r5432" typeof="mw:Extension/templatestyles" about="#mwt7" data-mw='{"name":"templatestyles","attrs":{"src":"Table row/styles.css"}}'></style>
<td class="bad">Goodbye</td></tr>
<tr>
<style data-mw-deduplicate="TemplateStyles:r5432" typeof="mw:Extension/templatestyles" about="#mwt10" data-mw='{"name":"templatestyles","attrs":{"src":"Table row/styles.css"}}'></style>
<td class="good">Welcome</td></tr>
</tbody></table>`,
			reduplicated: `<span about="#mwt1" typeof="mw:Transclusion" data-parsoid='{"pi":[[],[{"k":"1"},{"k":"2"}],[{"k":"1"},{"k":"2"}],[{"k":"1"},{"k":"2"}],[]],"dsr":[0,107,null,null]}' data-mw='{"parts":[{"template":{"target":{"wt":"table start","href":"./Template:Table_start"},"params":{},"i":0}},"\\n",{"template":{"target":{"wt":"table row","href":"./Template:Table_row"},"params":{"1":{"wt":"Hello"},"2":{"wt":"good"}},"i":1}},"\\n",{"template":{"target":{"wt":"table row","href":"./Template:Table_row"},"params":{"1":{"wt":"Goodbye"},"2":{"wt":"bad"}},"i":2}},"\\n",{"template":{"target":{"wt":"table row","href":"./Template:Table_row"},"params":{"1":{"wt":"Welcome"},"2":{"wt":"good"}},"i":3}},"\\n",{"template":{"target":{"wt":"table end","href":"./Template:Table_end"},"params":{},"i":4}}]}'>
</span><table class="wikitable" about="#mwt1">
<tbody><tr>
<style data-mw-deduplicate="TemplateStyles:r5432" typeof="mw:Extension/templatestyles" about="#mwt4" data-mw='{"name":"templatestyles","attrs":{"src":"Table row/styles.css"}}'>.mw-parser-output .good{background:#9EFF9E}.mw-parser-output .bad{background:#FFC7C7}</style>
<td class="good">Hello</td></tr>
<tr>
<style data-mw-deduplicate="TemplateStyles:r5432" typeof="mw:Extension/templatestyles" about="#mwt7" data-mw='{"name":"templatestyles","attrs":{"src":"Table row/styles.css"}}'>.mw-parser-output .good{background:#9EFF9E}.mw-parser-output .bad{background:#FFC7C7}</style>
<td class="bad">Goodbye</td></tr>
<tr>
<style data-mw-deduplicate="TemplateStyles:r5432" typeof="mw:Extension/templatestyles" about="#mwt10" data-mw='{"name":"templatestyles","attrs":{"src":"Table row/styles.css"}}'>.mw-parser-output .good{background:#9EFF9E}.mw-parser-output .bad{background:#FFC7C7}</style>
<td class="good">Welcome</td></tr>
</tbody></table>`
		}
	];

	stylesCases.forEach( ( caseItem ) => {
		const doc = ve.createDocumentFromHtml( caseItem.deduplicated );

		// Test that we can re-duplicate styles, which were de-duplicated in Parsoid HTML
		mw.libs.ve.reduplicateStyles( doc.body );
		assert.equalDomElement(
			doc.body,
			ve.createDocumentFromHtml( caseItem.reduplicated ).body,
			caseItem.msg + ' (reduplicated)'
		);

		// Test that we can de-duplicate styles again, producing a result identical to the Parsoid HTML
		mw.libs.ve.deduplicateStyles( doc.body );
		assert.equalDomElement(
			doc.body,
			ve.createDocumentFromHtml( caseItem.deduplicated ).body,
			caseItem.msg + ' (deduplicated)'
		);
	} );
} );

QUnit.test( 'getTargetDataFromHref', ( assert ) => {
	const doc = ve.createDocumentFromHtml( ve.test.utils.makeBaseTag( ve.dm.mwExample.baseUri ) );
	mw.config.set( {
		wgScript: '/w/index.php',
		wgArticlePath: '/wiki/$1'
	} );

	const hrefCases = [
		{
			msg: 'Parsoid link',
			href: './Foo',
			expected: {
				title: 'Foo',
				isInternal: true
			}
		},
		{
			msg: 'Parsoid red link',
			href: './Foo?action=edit&redlink=1',
			expected: {
				title: 'Foo',
				isInternal: true
			}
		},
		{
			msg: 'Parsoid link with fragment',
			href: './Foo#Bar',
			expected: {
				title: 'Foo#Bar',
				isInternal: true
			}
		},
		{
			msg: 'Parsoid red link with fragment',
			href: './Foo?action=edit&redlink=1#Bar',
			expected: {
				title: 'Foo#Bar',
				isInternal: true
			}
		},
		{
			msg: 'Old parser link',
			href: '/wiki/Foo',
			expected: {
				title: 'Foo',
				isInternal: true
			}
		},
		{
			msg: 'Old parser red link',
			href: '/w/index.php?title=Foo&action=edit&redlink=1',
			expected: {
				title: 'Foo',
				isInternal: true
			}
		},
		{
			msg: 'Old parser link with fragment',
			href: '/wiki/Foo#Bar',
			expected: {
				title: 'Foo#Bar',
				isInternal: true
			}
		},
		{
			msg: 'Old parser red link with fragment (old parser does not actually generate links like this, but we recognize them)',
			href: '/w/index.php?title=Foo&action=edit&redlink=1#Bar',
			expected: {
				title: 'Foo#Bar',
				isInternal: true
			}
		},
		{
			msg: 'Full URL link to current wiki',
			href: 'http://example.com/wiki/Foo',
			expected: {
				title: 'Foo',
				isInternal: true
			}
		},
		{
			msg: 'Full URL red link to current wiki',
			href: 'http://example.com/w/index.php?title=Foo&action=edit&redlink=1',
			expected: {
				title: 'Foo',
				isInternal: true
			}
		},
		{
			msg: 'Full URL link to current wiki with different protocol',
			href: 'https://example.com/wiki/Foo',
			expected: {
				title: 'Foo',
				isInternal: true
			}
		},
		{
			msg: 'Full URL link to current wiki, but with no title',
			href: 'http://example.com/wiki/',
			expected: {
				title: '',
				isInternal: true
			}
		},
		{
			msg: 'Full URL link to current wiki, but with extra parameters (1)',
			href: 'http://example.com/wiki/Foo?action=history',
			expected: {
				isInternal: false
			}
		},
		{
			msg: 'Full URL link to current wiki, but with extra parameters (2)',
			href: 'http://example.com/w/index.php?title=Foo&action=edit&redlink=1&preload=Blah',
			expected: {
				isInternal: false
			}
		},
		{
			msg: 'Full URL link to current wiki that may be valid, but uses a weird URL pattern',
			href: 'http://example.com/wiki/?title=Foo',
			expected: {
				isInternal: false
			}
		},
		{
			msg: 'Full URL link to another wiki',
			href: 'http://example.net/wiki/Foo',
			expected: {
				isInternal: false
			}
		},
		{
			msg: 'Full URL red link to another wiki',
			href: 'http://example.net/w/index.php?title=Foo&action=edit&redlink=1',
			expected: {
				isInternal: false
			}
		},
		{
			/* eslint-disable no-script-url */
			msg: 'Invalid protocol is handled as internal link',
			href: 'javascript:alert()',
			expected: {
				title: 'javascript:alert()',
				isInternal: true
			}
			/* eslint-enable no-script-url */
		},
		{
			msg: 'Invalid protocol is handled as internal link',
			href: 'not-a-protocol:Some%20text',
			expected: {
				title: 'not-a-protocol:Some text',
				isInternal: true
			}
		},
		{
			msg: 'Valid protocol is handled as external link',
			href: 'https://example.net/',
			expected: {
				isInternal: false
			}
		},
		{
			msg: 'Valid protocol is handled as external link',
			href: 'mailto:example@example.net',
			expected: {
				isInternal: false
			}
		}
	];
	hrefCases.forEach( ( caseItem ) => {
		const actualInfo = mw.libs.ve.getTargetDataFromHref( caseItem.href, doc );
		assert.deepEqual( actualInfo, caseItem.expected, caseItem.msg );
	} );
} );
