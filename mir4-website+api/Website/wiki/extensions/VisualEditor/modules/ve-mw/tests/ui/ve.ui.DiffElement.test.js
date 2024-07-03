/*!
 * VisualEditor MW-specific DiffElement tests.
 *
 * @copyright See AUTHORS.txt
 */

QUnit.module( 've.ui.DiffElement (MW)', ve.test.utils.newMwEnvironment() );

QUnit.test( 'Diffing', ( assert ) => {
	const
		cases = [
			{
				msg: 'Change template param',
				oldDoc: ve.test.utils.addBaseTag(
					ve.dm.mwExample.MWTransclusion.blockOpen + ve.dm.mwExample.MWTransclusion.blockContent,
					ve.dm.example.baseUri
				),
				newDoc: ve.test.utils.addBaseTag(
					ve.dm.mwExample.MWTransclusion.blockOpenModified + ve.dm.mwExample.MWTransclusion.blockContent,
					ve.dm.example.baseUri
				),
				expected:
					( ve.dm.mwExample.MWTransclusion.blockOpenModified + ve.dm.mwExample.MWTransclusion.blockContent )
						// FIXME: Use DOM modification instead of string replaces
						.replace( /#mwt1"/g, '#mwt1" data-diff-action="structural-change" data-diff-id="0"' ),
				expectedDescriptions: [
					ve.dm.example.singleLine`
						<div>visualeditor-changedesc-mwtransclusion</div>
						<div>
							<ul>
								<li>
									visualeditor-changedesc-changed-diff,1,<span>Hello, <del>world</del><ins>globe</ins>!</span>
								</li>
							</ul>
						</div>
					`
				]
			},
			{
				msg: 'Changed width of block image',
				oldDoc: ve.test.utils.addBaseTag(
					ve.dm.mwExample.MWBlockImage.html,
					ve.dm.example.baseUri
				),
				newDoc: ve.test.utils.addBaseTag(
					ve.dm.mwExample.MWBlockImage.html.replace( 'width="1"', 'width="3"' ),
					ve.dm.example.baseUri
				),
				expected:
					ve.dm.mwExample.MWBlockImage.html
						// FIXME: Use DOM modification instead of string replaces
						.replace( 'width="1"', 'width="3"' )
						.replace( 'href="./Foo"', 'href="' + new URL( './Foo', ve.dm.example.baseUri ) + '"' )
						.replace( 'foobar"', 'foobar" data-diff-action="structural-change" data-diff-id="0"' ),
				expectedDescriptions: [
					ve.dm.example.singleLine`
						<div>
							visualeditor-changedesc-image-size,
							<del>1visualeditor-dimensionswidget-times2visualeditor-dimensionswidget-px</del>,
							<ins>3visualeditor-dimensionswidget-times2visualeditor-dimensionswidget-px</ins>
						</div>
					`
				]
			}
		];

	for ( let i = 0; i < cases.length; i++ ) {
		ve.test.utils.runDiffElementTest( assert, cases[ i ] );
	}

} );
