<?php

namespace Test\Parsoid\Language;

use PHPUnit\Framework\TestCase;
use Wikimedia\Bcp47Code\Bcp47CodeValue;
use Wikimedia\LangConv\ReplacementMachine;
use Wikimedia\Parsoid\Language\LanguageConverter;
use Wikimedia\Parsoid\Mocks\MockEnv;
use Wikimedia\Parsoid\Utils\DOMCompat;

class CrhTest extends TestCase {

	private const CODES = [ "crh-cyrl", "crh-latn" ];

	// phpcs:disable Generic.Files.LineLength.TooLong
	private const TEST_CASES = [
		[
			'title' => 'general words, covering more of the alphabet (1)',
			'output' => [
				'crh' => "рузгярнынъ ruzgârnıñ Париж Parij",
				'crh-cyrl' => "рузгярнынъ рузгярнынъ Париж Париж",
				'crh-latn' => "ruzgârnıñ ruzgârnıñ Parij Parij"
			],
			'input' => "рузгярнынъ ruzgârnıñ Париж Parij"
		],
		[
			'title' => 'general words, covering more of the alphabet (2)',
			'output' => [
				'crh' => "чёкюч çöküç элифбени elifbeni полициясы politsiyası",
				'crh-cyrl' => "чёкюч чёкюч элифбени элифбени полициясы полициясы",
				'crh-latn' => "çöküç çöküç elifbeni elifbeni politsiyası politsiyası"
			],
			'input' => "чёкюч çöküç элифбени elifbeni полициясы politsiyası"
		],
		[
			'title' => 'general words, covering more of the alphabet (3)',
			'output' => [
				'crh' => "хусусында hususında акъшамларны aqşamlarnı опькеленюв öpkelenüv",
				'crh-cyrl' => "хусусында хусусында акъшамларны акъшамларны опькеленюв опькеленюв",
				'crh-latn' => "hususında hususında aqşamlarnı aqşamlarnı öpkelenüv öpkelenüv"
			],
			'input' => "хусусында hususında акъшамларны aqşamlarnı опькеленюв öpkelenüv"
		],
		[
			'title' => 'general words, covering more of the alphabet (4)',
			'output' => [
				'crh' => "кулюмсиреди külümsiredi айтмайджагъым aytmaycağım козьяшсыз közyaşsız",
				'crh-cyrl' => "кулюмсиреди кулюмсиреди айтмайджагъым айтмайджагъым козьяшсыз козьяшсыз",
				'crh-latn' => "külümsiredi külümsiredi aytmaycağım aytmaycağım közyaşsız közyaşsız"
			],
			'input' => "кулюмсиреди külümsiredi айтмайджагъым aytmaycağım козьяшсыз közyaşsız"
		],
		[
			'title' => 'exception words',
			'output' => [
				'crh' => "инструменталь instrumental гургуль gürgül тюшюнмемек tüşünmemek",
				'crh-cyrl' => "инструменталь инструменталь гургуль гургуль тюшюнмемек тюшюнмемек",
				'crh-latn' => "instrumental instrumental gürgül gürgül tüşünmemek tüşünmemek"
			],
			'input' => "инструменталь instrumental гургуль gürgül тюшюнмемек tüşünmemek"
		],
		[
			'title' => 'recent problem words, part 1',
			'output' => [
				'crh' => "künü куню sürgünligi сюргюнлиги özü озю etti этти esas эсас dört дёрт",
				'crh-cyrl' => "куню куню сюргюнлиги сюргюнлиги озю озю этти этти эсас эсас дёрт дёрт",
				'crh-latn' => "künü künü sürgünligi sürgünligi özü özü etti etti esas esas dört dört"
			],
			'input' => "künü куню sürgünligi сюргюнлиги özü озю etti этти esas эсас dört дёрт"
		],
		[
			'title' => 'recent problem words, part 2',
			'output' => [
				'crh' => "keldi кельди km² км² yüz юзь AQŞ АКъШ ŞSCBnen ШСДжБнен iyül июль",
				'crh-cyrl' => "кельди кельди км² км² юзь юзь АКъШ АКъШ ШСДжБнен ШСДжБнен июль июль",
				'crh-latn' => "keldi keldi km² km² yüz yüz AQŞ AQŞ ŞSCBnen ŞSCBnen iyül iyül"
			],
			'input' => "keldi кельди km² км² yüz юзь AQŞ АКъШ ŞSCBnen ШСДжБнен iyül июль"
		],
		[
			'title' => 'recent problem words, part 3',
			'output' => [
				'crh' => "işğal ишгъаль işğalcilerine ишгъальджилерине rayon район üst усть",
				'crh-cyrl' => "ишгъаль ишгъаль ишгъальджилерине ишгъальджилерине район район усть усть",
				'crh-latn' => "işğal işğal işğalcilerine işğalcilerine rayon rayon üst üst"
			],
			'input' => "işğal ишгъаль işğalcilerine ишгъальджилерине rayon район üst усть"
		],
		[
			'title' => 'recent problem words, part 4',
			'output' => [
				'crh' => "rayonınıñ районынынъ Noğay Ногъай Yürtü Юрьтю vatandan ватандан",
				'crh-cyrl' => "районынынъ районынынъ Ногъай Ногъай Юрьтю Юрьтю ватандан ватандан",
				'crh-latn' => "rayonınıñ rayonınıñ Noğay Noğay Yürtü Yürtü vatandan vatandan"
			],
			'input' => "rayonınıñ районынынъ Noğay Ногъай Yürtü Юрьтю vatandan ватандан"
		],
		[
			'title' => 'recent problem words, part 5',
			'output' => [
				'crh' => "ком-кок köm-kök rol роль AQQI АКЪКЪЫ DAĞĞA ДАГЪГЪА 13-ünci 13-юнджи",
				'crh-cyrl' => "ком-кок ком-кок роль роль АКЪКЪЫ АКЪКЪЫ ДАГЪГЪА ДАГЪГЪА 13-юнджи 13-юнджи",
				'crh-latn' => "köm-kök köm-kök rol rol AQQI AQQI DAĞĞA DAĞĞA 13-ünci 13-ünci"
			],
			'input' => "ком-кок köm-kök rol роль AQQI АКЪКЪЫ DAĞĞA ДАГЪГЪА 13-ünci 13-юнджи"
		],
		[
			'title' => 'recent problem words, part 6',
			'output' => [
				'crh' => "ДЖУРЬМЕК CÜRMEK кетсин ketsin джумлеси cümlesi ильи ilyi Ильи İlyi",
				'crh-cyrl' => "ДЖУРЬМЕК ДЖУРЬМЕК кетсин кетсин джумлеси джумлеси ильи ильи Ильи Ильи",
				'crh-latn' => "CÜRMEK CÜRMEK ketsin ketsin cümlesi cümlesi ilyi ilyi İlyi İlyi"
			],
			'input' => "ДЖУРЬМЕК CÜRMEK кетсин ketsin джумлеси cümlesi ильи ilyi Ильи İlyi"
		],
		[
			'title' => 'recent problem words, part 7',
			'output' => [
				'crh' => "бруцел brutsel коцюб kotsüb плацен platsen эпицентр epitsentr",
				'crh-cyrl' => "бруцел бруцел коцюб коцюб плацен плацен эпицентр эпицентр",
				'crh-latn' => "brutsel brutsel kotsüb kotsüb platsen platsen epitsentr epitsentr"
			],
			'input' => "бруцел brutsel коцюб kotsüb плацен platsen эпицентр epitsentr"
		],
		[
			'title' => 'regex pattern words',
			'output' => [
				'crh' => "köyünden коюнден ange аньге",
				'crh-cyrl' => "коюнден коюнден аньге аньге",
				'crh-latn' => "köyünden köyünden ange ange"
			],
			'input' => "köyünden коюнден ange аньге"
		],
		[
			'title' => 'multi part words',
			'output' => [
				'crh' => "эки юз eki yüz",
				'crh-cyrl' => "эки юз эки юз",
				'crh-latn' => "eki yüz eki yüz"
			],
			'input' => "эки юз eki yüz"
		],
		[
			'title' => 'affix patterns',
			'output' => [
				'crh' => "köyniñ койнинъ Avcıköyde Авджыкойде ekvatorial экваториаль Canköy Джанкой",
				'crh-cyrl' => "койнинъ койнинъ Авджыкойде Авджыкойде экваториаль экваториаль Джанкой Джанкой",
				'crh-latn' => "köyniñ köyniñ Avcıköyde Avcıköyde ekvatorial ekvatorial Canköy Canköy"
			],
			'input' => "köyniñ койнинъ Avcıköyde Авджыкойде ekvatorial экваториаль Canköy Джанкой"
		],
		[
			'title' => 'Roman numerals and quotes, esp. single-letter Roman numerals at the end of a string',
			'output' => [
				'crh' => "VI,VII IX “dört” «дёрт» XI XII I V X L C D M",
				'crh-cyrl' => "VI,VII IX «дёрт» «дёрт» XI XII I V X L C D M",
				'crh-latn' => "VI,VII IX “dört” \"dört\" XI XII I V X L C D M"
			],
			'input' => "VI,VII IX “dört” «дёрт» XI XII I V X L C D M"
		],
		[
			'title' => 'Roman numerals vs Initials, part 1 - Roman numeral initials without spaces',
			'output' => [
				'crh' => "A.B.C.D.M. Qadırova XII, А.Б.Дж.Д.М. Къадырова XII",
				'crh-cyrl' => "А.Б.Дж.Д.М. Къадырова XII, А.Б.Дж.Д.М. Къадырова XII",
				'crh-latn' => "A.B.C.D.M. Qadırova XII, A.B.C.D.M. Qadırova XII"
			],
			'input' => "A.B.C.D.M. Qadırova XII, А.Б.Дж.Д.М. Къадырова XII"
		],
		[
			'title' => 'Roman numerals vs Initials, part 2 - Roman numeral initials with spaces',
			'output' => [
				'crh' => "G. H. I. V. X. L. Memetov III, Г. Х. Ы. В. X. Л. Меметов III",
				'crh-cyrl' => "Г. Х. Ы. В. X. Л. Меметов III, Г. Х. Ы. В. X. Л. Меметов III",
				'crh-latn' => 'G. H. I. V. X. L. Memetov III, G. H. I. V. X. L. Memetov III'
			],
			'input' => "G. H. I. V. X. L. Memetov III, Г. Х. Ы. В. X. Л. Меметов III"
		],
		[
			'title' => 'ALL CAPS, made up acronyms',
			'output' => [
				'crh' => "ÑAB QIC ĞUK COT НЪАБ КЪЫДЖ ГЪУК ДЖОТ CA ДЖА",
				'crh-cyrl' => "НЪАБ КЪЫДЖ ГЪУК ДЖОТ НЪАБ КЪЫДЖ ГЪУК ДЖОТ ДЖА ДЖА",
				'crh-latn' => "ÑAB QIC ĞUK COT ÑAB QIC ĞUK COT CA CA"
			],
			'input' => "ÑAB QIC ĞUK COT НЪАБ КЪЫДЖ ГЪУК ДЖОТ CA ДЖА"
		],
		[
			'title' => 'Many-to-one mappings: many Cyrillic to one Latin',
			'output' => [
				'crh' => "шофер шофёр şoför корбекул корьбекул корьбекуль körbekül",
				'crh-cyrl' => "шофер шофёр шофёр корбекул корьбекул корьбекуль корьбекуль",
				'crh-latn' => "şoför şoför şoför körbekül körbekül körbekül körbekül"
			],
			'input' => "шофер шофёр şoför корбекул корьбекул корьбекуль körbekül"
		],
		[
			'title' => 'Many-to-one mappings: many Latin to one Cyrillic',
			'output' => [
				'crh' => "fevqülade fevqulade февкъульаде beyude beyüde бейуде",
				'crh-cyrl' => "февкъульаде февкъульаде февкъульаде бейуде бейуде бейуде",
				'crh-latn' => "fevqülade fevqulade fevqulade beyude beyüde beyüde"
			],
			'input' => "fevqülade fevqulade февкъульаде beyude beyüde бейуде"
		]
	];

	/** @var ReplacementMachine */
	private static $machine;

	public static function setUpBeforeClass(): void {
		$lang = LanguageConverter::loadLanguage(
			new MockEnv( [] ),
			new Bcp47CodeValue( 'crh' )
		);
		self::$machine = $lang->getConverter()->getMachine();
	}

	public static function tearDownAfterClass(): void {
		self::$machine = null;
	}

	/**
	 * @covers \Wikimedia\LangConv\FST
	 * @dataProvider provideCrh
	 */
	public function testCrh( string $title, array $output, string $input, ?string $invertCode ) {
		foreach ( self::CODES as $variantCode ) {
			if ( !array_key_exists( $variantCode, $output ) ) {
				continue;
			}

			$doc = DOMCompat::newDocument( true );
			$out = self::$machine->convert(
				$doc, $input, $variantCode,
				$invertCode ?? $this->getInvertCode( $variantCode )
			);
			$expected = $output[$variantCode];
			$this->assertEquals( $expected, $out->textContent );
		}
	}

	public function provideCrh() {
		return array_map( static function ( $item ) {
			return [
				$item['title'],
				$item['output'],
				$item['input'],
				$item['code'] ?? null
			];
		}, self::TEST_CASES );
	}

	private function getInvertCode( $variantCode ) {
		return $variantCode === "crh-cyrl" ? "crh-latn" : "crh-cyrl";
	}

}
