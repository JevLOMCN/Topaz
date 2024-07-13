<?php

namespace Test\Parsoid\Html2Wt;

use PHPUnit\Framework\TestCase;
use Wikimedia\Parsoid\Core\SelserData;
use Wikimedia\Parsoid\Mocks\MockDataAccess;
use Wikimedia\Parsoid\Mocks\MockPageConfig;
use Wikimedia\Parsoid\Mocks\MockPageContent;
use Wikimedia\Parsoid\Mocks\MockSiteConfig;
use Wikimedia\Parsoid\Parsoid;

class TemplateDataTest extends TestCase {

	private static $defaultContentVersion = Parsoid::AVAILABLE_VERSIONS[0];

	private function verifyTransformation( string $newHTML, ?string $origHTML, ?string $origWT, string $expectedWT,
		string $description, ?string $contentVersion = null ) {
		$parserOpts = [];
		$opts = [];

		$siteConfig = new MockSiteConfig( $opts );
		$dataAccess = new MockDataAccess( $siteConfig, $opts );
		$parsoid = new Parsoid( $siteConfig, $dataAccess );

		$pageContent = new MockPageContent( [ 'main' => '' ] );
		$pageConfig = new MockPageConfig( $siteConfig, $opts, $pageContent );

		if ( isset( $origHTML ) && strlen( $origHTML ) > 0 ) {
			$selserData = new SelserData( $origWT, $origHTML );
		} else {
			$selserData = null;
		}
		if ( isset( $contentVersion ) ) {
			$parserOpts['inputContentVersion'] = $contentVersion;
		}

		$wt = $parsoid->html2wikitext( $pageConfig, $newHTML, $parserOpts, $selserData );
		$this->assertEquals( $expectedWT, $wt, $description );
	}

	public function defineTestData(): array {
		return [
			// 1. Transclusions without template data
			[
				'name' => 'Transclusions without template data',
				'html' => '<span about="#mwt1" typeof="mw:Transclusion" data-parsoid=' . "'" .
					'{"pi":[[{"k":"f2","spc":[""," "," ","\\n"]},{"k":"f1","spc":[""," "," ","\\n"]}]]}' .
					"' data-mw='" .
					'{"parts":[{"template":{"target":{"wt":"TplWithoutTemplateData\\n",' .
					'"href":"./Template:TplWithoutTemplateData"},"params":{"f1":{"wt":"foo"},' .
					'"f2":{"wt":"foo"}},"i":0}}]}' . "'" . '>foo</span>',
				'wt' => [
					'no_selser' => "{{TplWithoutTemplateData\n|f2 = foo\n|f1 = foo\n}}",
					'new_content' => "{{TplWithoutTemplateData|f1=foo|f2=foo}}",
					'edited' => "{{TplWithoutTemplateData\n|f2 = foo\n|f1 = BAR\n}}"
				]
			],

			// 2. normal
			[
				'name' => 'normal',
				'html' => '<span about="#mwt1" typeof="mw:Transclusion" data-parsoid=' . "'" .
					'{"pi":[[{"k":"f1"},{"k":"f2"}]]}' . "' data-mw='" .
					'{"parts":[{"template":{"target":{"wt":"NoFormatWithParamOrder",' .
					'"href":"./Template:NoFormatWithParamOrder"},"params":{"f1":{"wt":"foo"},' .
					'"f2":{"wt":"foo"}},"i":0}}]}' . "'" . '>foo</span>',
				'wt' => [
					'no_selser' => "{{NoFormatWithParamOrder|f1=foo|f2=foo}}",
					'new_content' => "{{NoFormatWithParamOrder|f1=foo|f2=foo}}",
					'edited' => "{{NoFormatWithParamOrder|f1=BAR|f2=foo}}"
				]
			],

			// 3. flipped f1 & f2 in data-parsoid + newly added f0
			[
				'name' => 'Preserve original param order + smart insertion of new params',
				'html' => '<span about="#mwt1" typeof="mw:Transclusion" data-parsoid=' . "'" .
					'{"pi":[[{"k":"f2"},{"k":"f1"}]]}' . "' data-mw='" .
					'{"parts":[{"template":{"target":{"wt":"NoFormatWithParamOrder",' .
					'"href":"./Template:NoFormatWithParamOrder"},"params":{"f1":{"wt":"foo"},' .
					'"f2":{"wt":"foo"},"f0":{"wt":"BOO"}},"i":0}}]}' . "'" . '>foo</span>',
				'wt' => [
					'no_selser' => "{{NoFormatWithParamOrder|f2=foo|f1=foo|f0=BOO}}",
					'new_content' => "{{NoFormatWithParamOrder|f0=BOO|f1=foo|f2=foo}}",
					// Preserve partial templatedata order
					'edited' => "{{NoFormatWithParamOrder|f2=foo|f0=BOO|f1=BAR}}"
				]
			],

			// 4. inline-tpl (but written in block format originally); no param order
			[
				'name' => 'Enforce inline format',
				'html' => '<span about="#mwt1" typeof="mw:Transclusion" data-parsoid=' . "'" .
					'{"pi":[[{"k":"f1","spc":[""," "," ","\\n"]},{"k":"f2","spc":[""," "," ","\\n"]}]]}' .
					"' data-mw='" . '{"parts":[{"template":{"target":{"wt":"InlineTplNoParamOrder\\n",' .
					'"href":"./Template:InlineTplNoParamOrder"},"params":{"f1":{"wt":"foo"},' .
					'"f2":{"wt":"foo"}},"i":0}}]}' . "'" . '>foo</span>',
				'wt' => [
					'no_selser' => "{{InlineTplNoParamOrder\n|f1 = foo\n|f2 = foo\n}}",
					'new_content' => "{{InlineTplNoParamOrder|f1=foo|f2=foo}}",
					'edited' => "{{InlineTplNoParamOrder|f1=BAR|f2=foo}}"
				]
			],

			// 5. block-tpl (but written in inline format originally); no param order
			[
				'name' => 'Enforce block format',
				'html' => '<span about="#mwt1" typeof="mw:Transclusion" data-parsoid=' . "'" .
					'{"pi":[[{"k":"f1"},{"k":"f2"}]]}' . "' data-mw='" .
					'{"parts":[{"template":{"target":{"wt":"BlockTplNoParamOrder",' .
					'"href":"./Template:BlockTplNoParamOrder"},"params":{"f1":{"wt":"foo"},' .
					'"f2":{"wt":"foo"}},"i":0}}]}' . "'" . '>foo</span>',
				'wt' => [
					'no_selser' => "{{BlockTplNoParamOrder|f1=foo|f2=foo}}",
					'new_content' => "{{BlockTplNoParamOrder\n| f1 = foo\n| f2 = foo\n}}",
					'edited' => "{{BlockTplNoParamOrder\n| f1 = BAR\n| f2 = foo\n}}"
				]
			],

			// 6. block-tpl (with non-standard spaces before pipe); no param order
			[
				'name' => 'Enforce block format (while preserving non-standard space before pipes)',
				'html' => '<span about="#mwt1" typeof="mw:Transclusion" data-parsoid=' . "'" .
					'{"pi":[[{"k":"f1","spc":[" ", " ", " ", "\\n <!--ha--> "]},' .
					'{"k":"f2","spc":[" ", " ", " ", ""]}]]}' . "' data-mw='" .
					'{"parts":[{"template":{"target":{"wt":"BlockTplNoParamOrder\\n ",' .
					'"href":"./Template:BlockTplNoParamOrder"},"params":{"f1":{"wt":"foo"},' .
					'"f2":{"wt":"foo"}},"i":0}}]}' . "'" . '>foo</span>',
				'wt' => [
					'no_selser' => "{{BlockTplNoParamOrder\n | f1 = foo\n <!--ha--> | f2 = foo}}",
					'new_content' => "{{BlockTplNoParamOrder\n| f1 = foo\n| f2 = foo\n}}",
					'edited' => "{{BlockTplNoParamOrder\n| f1 = BAR\n <!--ha--> | f2 = foo\n}}"
				]
			],

			// 7. inline-tpl (but written in block format originally); with param order
			[
				'name' => 'Enforce inline format + param order',
				'html' => '<span about="#mwt1" typeof="mw:Transclusion" data-parsoid=' . "'" .
					'{"pi":[[{"k":"f2","spc":[""," "," ","\\n"]},{"k":"f1","spc":[""," "," ","\\n"]}]]}' .
					"' data-mw='" . '{"parts":[{"template":{"target":{"wt":"InlineTplWithParamOrder\\n",' .
					'"href":"./Template:InlineTplWithParamOrder"},"params":{"f1":{"wt":"foo"},' .
					'"f2":{"wt":"foo"}},"i":0}}]}' . "'" . '>foo</span>',
				'wt' => [
					'no_selser' => "{{InlineTplWithParamOrder\n|f2 = foo\n|f1 = foo\n}}",
					'new_content' => "{{InlineTplWithParamOrder|f1=foo|f2=foo}}",
					'edited' => "{{InlineTplWithParamOrder|f2=foo|f1=BAR}}"
				]
			],

			// 8. block-tpl (but written in inline format originally); with param order
			[
				'name' => 'Enforce block format + param order',
				'html' => '<span about="#mwt1" typeof="mw:Transclusion" data-parsoid=' . "'" .
					'{"pi":[[{"k":"f2"},{"k":"f1"}]]}' . "'" . ' data-mw=' . "'" .
					'{"parts":[{"template":{"target":{"wt":"BlockTplWithParamOrder",' .
					'"href":"./Template:BlockTplWithParamOrder"},"params":{"f1":{"wt":"foo"},' .
					'"f2":{"wt":"foo"}},"i":0}}]}' . "'" . '>foo</span>',
				'wt' => [
					'no_selser' => "{{BlockTplWithParamOrder|f2=foo|f1=foo}}",
					'new_content' => "{{BlockTplWithParamOrder\n| f1 = foo\n| f2 = foo\n}}",
					'edited' => "{{BlockTplWithParamOrder\n| f2 = foo\n| f1 = BAR\n}}"
				]
			],

			// 9. Multiple transclusions
			[
				'name' => 'Multiple transclusions',
				'html' => '<span about="#mwt1" typeof="mw:Transclusion" data-parsoid=' . "'" .
					'{"pi":[[{"k":"f2","spc":[""," "," ","\\n"]},{"k":"f1","spc":[""," "," ","\\n"]}]]}' .
					"' data-mw='" . '{"parts":[{"template":{"target":{"wt":"TplWithoutTemplateData\\n",' .
					'"href":"./Template:TplWithoutTemplateData"},"params":{"f1":{"wt":"foo"},' .
					'"f2":{"wt":"foo"}},"i":0}}]}' . "'" . '>foo</span>' .
					' <span about="#mwt2" typeof="mw:Transclusion" data-parsoid=' . "'" .
					'{"pi":[[{"k":"f2"},{"k":"f1"}]]}' . "'" . ' data-mw=' . "'" .
					'{"parts":[{"template":{"target":{"wt":"BlockTplWithParamOrder",' .
					'"href":"./Template:BlockTplWithParamOrder"},"params":{"f1":{"wt":"foo"},' .
					'"f2":{"wt":"foo"}},"i":0}}]}' . "'" . '>foo</span>',
				'wt' => [
					'no_selser' => "{{TplWithoutTemplateData\n|f2 = foo\n|f1 = foo\n}} " .
						"{{BlockTplWithParamOrder|f2=foo|f1=foo}}",
					'new_content' => "{{TplWithoutTemplateData|f1=foo|f2=foo}} " .
						"{{BlockTplWithParamOrder\n| f1 = foo\n| f2 = foo\n}}",
					'edited' => "{{TplWithoutTemplateData\n|f2 = foo\n|f1 = BAR\n}} " .
						"{{BlockTplWithParamOrder|f2=foo|f1=foo}}"
				]
			],

			// 10. data-mw with multiple transclusions
			[
				'name' => 'Multiple transclusions',
				'html' => '<span about="#mwt1" typeof="mw:Transclusion" data-parsoid=' . "'" .
					'{"pi":[[{"k":"f2"},{"k":"f1"}], [{"k":"f2","spc":[""," "," ","\\n"]},' .
					'{"k":"f1","spc":[""," "," ","\\n"]}]]}' . "' data-mw='" .
					'{"parts":[{"template":{"target":{"wt":"BlockTplWithParamOrder",' .
					'"href":"./Template:BlockTplWithParamOrder"},"params":{"f1":{"wt":"foo"},' .
					'"f2":{"wt":"foo"}},"i":0}},"SOME TEXT",{"template":{"target":' .
					'{"wt":"InlineTplNoParamOrder\\n","href":"./Template:InlineTplNoParamOrder"},' .
					'"params":{"f1":{"wt":"foo"},"f2":{"wt":"foo"}},"i":1}}]}' . "'" . '>foo</span>',
				'wt' => [
					'no_selser' => "{{BlockTplWithParamOrder|f2=foo|f1=foo}}SOME TEXT" .
						"{{InlineTplNoParamOrder\n|f2 = foo\n|f1 = foo\n}}",
					'new_content' => "{{BlockTplWithParamOrder\n| f1 = foo\n| f2 = foo\n}}SOME TEXT" .
						"{{InlineTplNoParamOrder|f1=foo|f2=foo}}",
					'edited' => "{{BlockTplWithParamOrder\n| f2 = foo\n| f1 = BAR\n}}SOME TEXT" .
						"{{InlineTplNoParamOrder|f2=foo|f1=foo}}"
				]
			],

			// 11. Alias sort order
			[
				'name' => 'Enforce param order with aliases',
				'html' => '<span about="#mwt1" typeof="mw:Transclusion"' . " data-mw='" .
					'{"parts":[{"template":{"target":{"wt":"WithParamOrderAndAliases\\n",' .
					'"href":"./Template:WithParamOrderAndAliases"},"params":{"f2":{"wt":"foo"},' .
					'"f3":{"wt":"foo"}},"i":1}}]}' . "'" . '>foo</span>',
				'wt' => [
					'no_selser' => "{{WithParamOrderAndAliases|f3=foo|f2=foo}}",
					'new_content' => "{{WithParamOrderAndAliases|f3=foo|f2=foo}}",
					'edited' => "{{WithParamOrderAndAliases|f3=foo|f2=BAR}}"
				]
			],

			// 12. Alias sort order, with both original and alias params
			// Even aliased parameters should appear in the original order by default.
			[
				'name' => 'Enforce param order with aliases (aliases in original order)',
				'html' => '<span about="#mwt1" typeof="mw:Transclusion" data-parsoid=' . "'" .
					'{"pi":[[{"k":"f4"},{"k":"f3"},{"k":"f1"}]]}' . "' data-mw='" .
					'{"parts":[{"template":{"target":{"wt":"WithParamOrderAndAliases",' .
					'"href":"./Template:WithParamOrderAndAliases"},"params":{"f4":{"wt":"foo"},' .
					'"f3":{"wt":"foo"},"f1":{"wt":"foo"}},"i":0}}]}' . "'" . '>foo</span>',
				'wt' => [
					'no_selser' => "{{WithParamOrderAndAliases|f4=foo|f3=foo|f1=foo}}",
					'new_content' => "{{WithParamOrderAndAliases|f1=foo|f4=foo|f3=foo}}",
					'edited' => "{{WithParamOrderAndAliases|f4=BAR|f3=foo|f1=foo}}"
				]
			],

			// 13. Inline Formatted template 1
			[
				'name' => 'Inline Formatted template 1',
				'html' => 'x <span about="#mwt1" typeof="mw:Transclusion" data-parsoid=' . "'" .
					'{"pi":[[{"k":"f1"},{"k":"x"}]]}' . "' data-mw='" .
					'{"parts":[{"template":{"target":{"wt":"InlineFormattedTpl_1",' .
					'"href":"./Template:InlineFormattedTpl_1"},"params":{"f1":{"wt":""},' .
					'"x":{"wt":"foo"}},"i":0}}]}' . "'" . '>something</span> y',
				'wt' => [
					'no_selser' => "x {{InlineFormattedTpl_1|f1=|x=foo}} y",
					'new_content' => "x {{InlineFormattedTpl_1|f1=|x=foo}} y",
					'edited' => "x {{InlineFormattedTpl_1|f1=|x=BAR}} y"
				]
			],

			// 14. Inline Formatted template 2
			[
				'name' => 'Inline Formatted template 2',
				'html' => 'x <span about="#mwt1" typeof="mw:Transclusion" data-parsoid=' . "'" .
					'{"pi":[[{"k":"f1"},{"k":"x"}]]}' . "' data-mw='" .
					'{"parts":[{"template":{"target":{"wt":"InlineFormattedTpl_2",' .
					'"href":"./Template:InlineFormattedTpl_2"},"params":{"f1":{"wt":""},' .
					'"x":{"wt":"foo"}},"i":0}}]}' . "'" . '>something</span> y',
				'wt' => [
					'no_selser' => "x {{InlineFormattedTpl_2|f1=|x=foo}} y",
					'new_content' => "x \n{{InlineFormattedTpl_2 | f1 =  | x = foo}} y",
					'edited' => "x \n{{InlineFormattedTpl_2 | f1 =  | x = BAR}} y"
				]
			],

			// 15.1 Inline Formatted template 3
			[
				'name' => 'Inline Formatted template 3',
				'html' => 'x <span about="#mwt1" typeof="mw:Transclusion" data-parsoid=' . "'" .
					'{"pi":[[{"k":"f1"},{"k":"x"}]]}' . "' data-mw='" .
					'{"parts":[{"template":{"target":{"wt":"InlineFormattedTpl_3",' .
					'"href":"./Template:InlineFormattedTpl_3"},"params":{"f1":{"wt":""},' .
					'"x":{"wt":"foo"}},"i":0}}]}' . "'" . '>something</span> y',
				'wt' => [
					'no_selser' => "x {{InlineFormattedTpl_3|f1=|x=foo}} y",
					'new_content' => "x {{InlineFormattedTpl_3| f1    = | x     = foo}} y",
					'edited' => "x {{InlineFormattedTpl_3| f1    = | x     = BAR}} y"
				]
			],

			// 15.2 Inline Formatted template 3 with multibyte unicode chars (T245627)
			[
				'name' => 'Inline Formatted template 3 (multibyte chars)',
				'html' => 'x <span about="#mwt1" typeof="mw:Transclusion" data-parsoid=' . "'" .
					'{"pi":[[{"k":"f1"},{"k":"é"}]]}' . "' data-mw='" .
					'{"parts":[{"template":{"target":{"wt":"InlineFormattedTpl_3",' .
					'"href":"./Template:InlineFormattedTpl_3"},"params":{"f1":{"wt":""},' .
					'"é":{"wt":"foo"}},"i":0}}]}' . "'" . '>something</span> y',
				'wt' => [
					'no_selser' => "x {{InlineFormattedTpl_3|f1=|é=foo}} y",
					'new_content' => "x {{InlineFormattedTpl_3| f1    = | é     = foo}} y",
					'edited' => "x {{InlineFormattedTpl_3| f1    = | é     = BAR}} y"
				]
			],

			// 16. Custom block formatting 1
			[
				'name' => 'Custom block formatting 1',
				'html' => 'x<span about="#mwt1" typeof="mw:Transclusion" data-parsoid=' . "'" .
					'{"pi":[[{"k":"f1"},{"k":"f2"}]]}' . "' data-mw='" .
					'{"parts":[{"template":{"target":{"wt":"BlockFormattedTpl_1",' .
					'"href":"./Template:BlockFormattedTpl_1"},"params":{"f1":{"wt":""},' .
					'"f2":{"wt":"foo"}},"i":0}}]}' . "'" . '>something</span>y',
				'wt' => [
					'no_selser' => "x{{BlockFormattedTpl_1|f1=|f2=foo}}y", // dp spacing info is preserved
					'new_content' => "x{{BlockFormattedTpl_1\n| f1 = \n| f2 = foo\n}}y", // normalized
					'edited' => "x{{BlockFormattedTpl_1\n| f1 = \n| f2 = BAR\n}}y" // normalized
				]
			],

			// 17. Custom block formatting 2
			[
				'name' => 'Custom block formatting 2',
				'html' => 'x<span about="#mwt1" typeof="mw:Transclusion" data-parsoid=' . "'" .
					'{"pi":[[{"k":"f1"},{"k":"f2"}]]}' . "' data-mw='" .
					'{"parts":[{"template":{"target":{"wt":"BlockFormattedTpl_2",' .
					'"href":"./Template:BlockFormattedTpl_2"},"params":{"f1":{"wt":""},' .
					'"f2":{"wt":"foo"}},"i":0}}]}' . "'" . '>something</span>y',
				'wt' => [
					'no_selser' => "x{{BlockFormattedTpl_2|f1=|f2=foo}}y", // dp spacing info is preserved
					'new_content' => "x\n{{BlockFormattedTpl_2\n| f1 = \n| f2 = foo\n}}\ny", // normalized
					'edited' => "x\n{{BlockFormattedTpl_2\n| f1 = \n| f2 = BAR\n}}\ny" // normalized
				]
			],

			// 18. Custom block formatting 3 - T199849
			[
				'name' => 'Custom block formatting 3 - T199849',
				'html' => "x\n" . '<span about="#mwt1" typeof="mw:Transclusion" data-mw=' . "'" .
					'{"parts":[{"template":{"target":{"wt":"BlockFormattedTpl_2",' .
					'"href":"./Template:BlockFormattedTpl_2"},"params":{"f1":{"wt":""},' .
					'"f2":{"wt":"foo"}},"i":0}}]}' . "'" . '>something</span>' . "\ny",
				'wt' => [
					'new_content' => "x\n{{BlockFormattedTpl_2\n| f1 = \n| f2 = foo\n}}\ny", // normalized
					'edited' => "x\n{{BlockFormattedTpl_2\n| f1 = \n| f2 = BAR\n}}\ny" // normalized
				]
			],

			// 19. Custom block formatting 4 - T199849
			[
				'name' => 'Custom block formatting 4 - T199849',
				'html' => "x\n" . '<span about="#mwt1" typeof="mw:Transclusion" data-mw=' . "'" .
					'{"parts":["X", {"template":{"target":{"wt":"BlockFormattedTpl_2",' .
					'"href":"./Template:BlockFormattedTpl_2"},"params":{"f1":{"wt":""},' .
					'"f2":{"wt":"foo"}},"i":0}}, "Y"]}' . "'" . '>something</span>' . "\ny",
				'wt' => [
					'new_content' => "x\nX\n{{BlockFormattedTpl_2\n| f1 = \n| f2 = foo\n}}\nY\ny", // normalized
					'edited' => "x\nX\n{{BlockFormattedTpl_2\n| f1 = \n| f2 = BAR\n}}\nY\ny"// normalized
				]
			],

			// 20. Custom block formatting 5 - T199849
			[
				'name' => 'Custom block formatting 5 - T199849',
				'html' => "x\n" . '<span about="#mwt1" typeof="mw:Transclusion" data-mw=' . "'" .
					'{"parts":[{"template":{"target":{"wt":"BlockFormattedTpl_2",' .
					'"href":"./Template:BlockFormattedTpl_2"},"params":{"g1":{"wt":""},' .
					'"g2":{"wt":""}},"i":0}}, {"template":{"target":{"wt":"BlockFormattedTpl_2",' .
					'"href":"./Template:BlockFormattedTpl_2"},"params":{"f1":{"wt":""},' .
					'"f2":{"wt":"foo"}},"i":1}}]}' . "'" . '>something</span><!--cmt-->' . "\ny",
				'wt' => [
					'new_content' => "x\n{{BlockFormattedTpl_2\n| g1 = \n| g2 = \n}}\n{{BlockFormattedTpl_2\n| " .
						"f1 = \n| f2 = foo\n}}<!--cmt-->\ny", // normalized
					'edited' => "x\n{{BlockFormattedTpl_2\n| g1 = \n| g2 = \n}}\n{{BlockFormattedTpl_2\n| " .
						"f1 = \n| f2 = BAR\n}}<!--cmt-->\ny" // normalized
				]
			],

			// 21. Custom block formatting 6
			[
				'name' => 'Custom block formatting 6',
				'html' => 'x<span about="#mwt1" typeof="mw:Transclusion" data-parsoid=' . "'" .
					'{"pi":[[{"k":"f1"},{"k":"f2"}]]}' . "' data-mw='" .
					'{"parts":[{"template":{"target":{"wt":"BlockFormattedTpl_3",' .
					'"href":"./Template:BlockFormattedTpl_3"},"params":{"f1":{"wt":""},' .
					'"f2":{"wt":"foo"}},"i":0}}]}' . "'" . '>something</span>y',
				'wt' => [
					'no_selser' => "x{{BlockFormattedTpl_3|f1=|f2=foo}}y", // dp spacing info is preserved
					'new_content' => "x{{BlockFormattedTpl_3|\n f1    = |\n f2    = foo}}y", // normalized
					'edited' => "x{{BlockFormattedTpl_3|\n f1    = |\n f2    = BAR}}y" // normalized
				]
			],

			// 22. Block formatting without params - T282031
			[
				'name' => 'Block formatting without params',
				'html' => 'x<span about="#mwt1" typeof="mw:Transclusion" data-parsoid=' . "'" .
					'{"pi":[[]]}' . "' data-mw='" .
					'{"parts":[{"template":{"target":{"wt":"BlockTplNoParamOrder",' .
					'"href":"./Template:BlockTplNoParamOrder"},"params":{' .
					'},"i":0}}]}' . "'" . '>something</span>y',
				'wt' => [
					'no_selser' => "x{{BlockTplNoParamOrder}}y",
					'new_content' => "x{{BlockTplNoParamOrder}}y", // normalized
					'edited' => "x{{BlockTplNoParamOrder}}y" // normalized
				]
			],

			// 23. Sort arguments as in data-mw - T304730
			[
				'name' => 'Sort arguments as in data-mw',
				'html' => '<span about="#mwt1" typeof="mw:Transclusion" data-mw=\'' .
					'{"parts":[{"template":{"target":{"wt":"Meh", "href":"./Template:Meh"},"params":{' .
						'"f1":{"wt":"foo"},' .
						'"f2":{"wt":"foo"},' .
						'"f3":{"wt":"foo"},' .
						'"f4":{"wt":"foo"},' .
						'"f5":{"wt":"foo"},' .
						'"f6":{"wt":"foo"},' .
						'"f7":{"wt":"foo"},' .
						'"f8":{"wt":"foo"},' .
						'"f9":{"wt":"foo"},' .
						'"f10":{"wt":"foo"},' .
						'"f11":{"wt":"foo"},' .
						'"f12":{"wt":"foo"},' .
						'"f13":{"wt":"foo"},' .
						'"f14":{"wt":"foo"},' .
						'"f15":{"wt":"foo"},' .
						'"f16":{"wt":"foo"},' .
						'"f17":{"wt":"foo"}' .
					'},"i":0}}]}' . "'" . '>foo</span>',
				'wt' => [
					'no_selser' => "{{Meh|f1=foo|f2=foo|f3=foo|f4=foo|f5=foo|f6=foo|f7=foo|f8=foo|" .
						"f9=foo|f10=foo|f11=foo|f12=foo|f13=foo|f14=foo|f15=foo|f16=foo|f17=foo}}",
					'new_content' => "{{Meh|f1=foo|f2=foo|f3=foo|f4=foo|f5=foo|f6=foo|f7=foo|f8=foo|" .
						"f9=foo|f10=foo|f11=foo|f12=foo|f13=foo|f14=foo|f15=foo|f16=foo|f17=foo}}",
					'edited' => "{{Meh|f1=BAR|f2=foo|f3=foo|f4=foo|f5=foo|f6=foo|f7=foo|f8=foo|" .
						"f9=foo|f10=foo|f11=foo|f12=foo|f13=foo|f14=foo|f15=foo|f16=foo|f17=foo}}"
				]
			],

		];
	}

	/**
	 * @covers \Wikimedia\Parsoid\Parsoid::html2wikitext
	 * @covers \Wikimedia\Parsoid\Wikitext\ContentModelHandler::fromDOM
	 * @dataProvider defineTestData
	 */
	public function testTemplateData(
		string $name, string $html, array $wt
	): void {
		// Non-selser test
		if ( isset( $wt['no_selser'] ) ) {
			$desc = "$name: Default non-selser serialization should ignore templatedata";
			$this->verifyTransformation( $html, null, null, $wt['no_selser'], $desc );
		}

		// New content test
		$desc = "$name: Serialization of new content (no data-parsoid) should respect templatedata";
		// Remove data-parsoid making it look like new content
		$newHTML = preg_replace( '/data-parsoid.*? data-mw/', ' data-mw', $html );
		$this->verifyTransformation( $newHTML, '', '', $wt['new_content'], $desc );

		// Transclusion edit test
		$desc = "$name: Serialization of edited content should respect templatedata";
		// Replace only the first instance of 'foo' with 'BAR'
		// to simulate an edit of a transclusion.
		$newHTML = preg_replace( '/foo/', 'BAR', $html, 1 );
		$this->verifyTransformation(
			$newHTML, $html, $wt['no_selser'] ?? '', $wt['edited'], $desc
		);
	}

	public function defineVersionTestData(): array {
		return [
			[
				'contentVersion' => self::$defaultContentVersion,
				'html' => '<span about="#mwt1" typeof="mw:Transclusion" data-parsoid=' . "'" .
					'{"pi":[[{"k":"f1"},{"k":"f1"}]]}' . "' data-mw='" .
					'{"parts":[{"template":{"target":{"wt":"TplWithoutTemplateData",' .
					'"href":"./Template:TplWithoutTemplateData"},"params":{"f1":{"wt":"foo"},' .
					'"f2":{"wt":"foo"}},"i":0}}]}' . "'" . '>foo</span>',
				'wt' => [
					'orig' => "{{TplWithoutTemplateData|f1=foo|f2=foo}}",
					'edited' => "{{TplWithoutTemplateData|f1=BAR|f2=foo}}"
				]
			]
		];
	}

	/**
	 * @covers \Wikimedia\Parsoid\Parsoid::html2wikitext
	 * @covers \Wikimedia\Parsoid\Wikitext\ContentModelHandler::fromDOM
	 * @dataProvider defineVersionTestData
	 */
	public function testTemplateDataVersion(
		string $contentVersion, string $html, array $wt
	): void {
		$desc = "Serialization should use correct arg space defaults for " .
			"data-parsoid version $contentVersion";
		// Replace only the first instance of 'foo' with 'BAR'
		// to simulate an edit of a transclusion.
		$newHTML = preg_replace( '/foo/', 'BAR', $html, 1 );
		$this->verifyTransformation(
			$newHTML, $html, $wt['orig'], $wt['edited'], $desc, $contentVersion
		);
	}

}
