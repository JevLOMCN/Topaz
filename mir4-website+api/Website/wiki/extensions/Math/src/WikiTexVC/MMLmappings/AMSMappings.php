<?php
namespace MediaWiki\Extension\Math\WikiTexVC\MMLmappings;

use MediaWiki\Extension\Math\WikiTexVC\MMLmappings\Lengths\MathSpace;
use MediaWiki\Extension\Math\WikiTexVC\MMLmappings\TexConstants\Align;
use MediaWiki\Extension\Math\WikiTexVC\MMLmappings\TexConstants\TexClass;
use MediaWiki\Extension\Math\WikiTexVC\MMLmappings\TexConstants\Variants;
use MediaWiki\Extension\Math\WikiTexVC\MMLmappings\Util\MMLutil;

/**
 * Based on AMSMappings.js in MML3
 * Only importing infix atm
 * Singleton
 *
 */
class AMSMappings {

	private static $instance = null;
	private const AMSMACROS = [
		"mathring" => [ 'accent', '02DA' ],
		"nobreakspace" => 'Tilde',
		"negmedspace" => [ 'spacer', MathSpace::NEGATIVEMEDIUMMATHSPACE ],
		"negthickspace" => [ 'spacer', MathSpace::NEGATIVETHICKMATHSPACE ],
		"idotsint" => [ 'MultiIntegral', '\\int\\cdots\\int' ],
		"dddot" => [ 'accent', '20DB' ],
		"ddddot" => [ 'accent', '20DC' ],
		"sideset" => 'SideSet',
		"boxed" => [ 'macro', '\\fbox{$\\displaystyle{#1}$}', 1 ],
		"tag" => 'HandleTag',
		"notag" => 'HandleNoTag',
		"eqref" => [ 'HandleRef', true ],
		"substack" => [ 'macro', '\\begin{subarray}{c}#1\\end{subarray}', 1 ],
		"injlim" => [ 'namedOp', 'inj lim' ],
		"projlim" => [ 'namedOp', 'proj lim' ],
		"varliminf" => [ 'macro', '\\mathop{\\underline{\\mmlToken{mi}{lim}}}' ],
		"varlimsup" => [ 'macro', '\\mathop{\\overline{\\mmlToken{mi}{lim}}}' ],
		// replaced underrightarrow here not supported
		"varinjlim" => [ 'macro', '\\mathop{\\xrightarrow{\\mmlToken{mi}{lim}}}' ],
		// replaced underleftarrow here not supported
		"varprojlim" => [ 'macro', '\\mathop{\\xleftarrow{\\mmlToken{mi}{lim}}}' ],
		"DeclareMathOperator" => 'HandleDeclareOp',
		"operatorname" => 'handleOperatorName',
		"genfrac" => 'genFrac',
		"frac" => [ 'genFrac', '', '', '', '' ],
		"tfrac" => [ 'genFrac', '', '', '', '1' ],
		"dfrac" => [ 'genFrac', '', '', '', '0' ],
		"binom" => [ 'genFrac', '(', ')', '0', '0' ],
		"tbinom" => [ 'genFrac', '(', ')', '0', '1' ],
		"dbinom" => [ 'genFrac', '(', ')', '0', '0' ],
		"cfrac" => 'cFrac',
		"shoveleft" => [ 'HandleShove', Align::LEFT ],
		"shoveright" => [ 'HandleShove', Align::RIGHT ],
		"xrightarrow" => [ 'xArrow', 0x2192, 5, 10 ],
		"xleftarrow" => [ 'xArrow', 0x2190, 10, 5 ]
		];
	private const AMSMATHCHAR0MI = [
		"digamma" => '\u03DD',
		"varkappa" => '\u03F0',
		"varGamma" => [ '\u0393', [ "mathvariant" => Variants::ITALIC ] ],
		"varDelta" => [ '\u0394', [ "mathvariant" => Variants::ITALIC ] ],
		"varTheta" => [ '\u0398', [ "mathvariant" => Variants::ITALIC ] ],
		"varLambda" => [ '\u039B', [ "mathvariant" => Variants::ITALIC ] ],
		"varXi" => [ '\u039E', [ "mathvariant" => Variants::ITALIC ] ],
		"varPi" => [ '\u03A0', [ "mathvariant" => Variants::ITALIC ] ],
		"varSigma" => [ '\u03A3', [ "mathvariant" => Variants::ITALIC ] ],
		"varStigma" => [ '\u03DB', [ "mathvariant" => Variants::ITALIC ] ],
		"varUpsilon" => [ '\u03A5', [ "mathvariant" => Variants::ITALIC ] ],
		"varPhi" => [ '\u03A6', [ "mathvariant" => Variants::ITALIC ] ],
		"varPsi" => [ '\u03A8', [ "mathvariant" => Variants::ITALIC ] ],
		"varOmega" => [ '\u03A9', [ "mathvariant" => Variants::ITALIC ] ],
		"beth" => '\u2136',
		"gimel" => '\u2137',
		"daleth" => '\u2138',
		"backprime" => [ '\u2035', [ "variantForm" => "True" ] ], // actually: "variantForm" => "True"
		"hslash" => '\u210F',
		"varnothing" => [ '\u2205', [ "variantForm" => "True" ] ], // actually: "variantForm" => "True"
		"blacktriangle" => '\u25B4',
		"triangledown" => [ '\u25BD', [ "variantForm" => "True" ] ], // actually: "variantForm" => "True"
		"blacktriangledown" => '\u25BE',
		"square" => '\u25FB',
		"Box" => '\u25FB',
		"blacksquare" => '\u25FC',
		"lozenge" => '\u25CA',
		"Diamond" => '\u25CA',
		"blacklozenge" => '\u29EB',
		"circledS" => [ '\u24C8', [ "mathvariant" => Variants::NORMAL ] ],
		"bigstar" => '\u2605',
		"sphericalangle" => '\u2222',
		"measuredangle" => '\u2221',
		"nexists" => '\u2204',
		"complement" => '\u2201',
		"mho" => '\u2127',
		"eth" => [ '\u00F0', [ "mathvariant" => Variants::NORMAL ] ],
		"Finv" => '\u2132',
		"diagup" => '\u2571',
		"Game" => '\u2141',
		"diagdown" => '\u2572',
		"Bbbk" => [ '\u006B', [ "mathvariant" => Variants::DOUBLESTRUCK ] ],
		"yen" => '\u00A5',
		"circledR" => '\u00AE',
		"checkmark" => '\u2713',
		"maltese" => '\u2720'
		];
	private const AMSMATHCHAR0MO = [
		"iiiint" => [ '\u2A0C', [ "texClass" => TexClass::OP ] ], // added this mapping from other array
		"dotplus" => '\u2214',
		"ltimes" => '\u22C9',
		"smallsetminus" => [ '\u2216', [ "variantForm" => "True" ] ], // actually: "variantForm" => "True"
		"rtimes" => '\u22CA',
		"Cap" => '\u22D2',
		"doublecap" => '\u22D2',
		"leftthreetimes" => '\u22CB',
		"Cup" => '\u22D3',
		"doublecup" => '\u22D3',
		"rightthreetimes" => '\u22CC',
		"barwedge" => '\u22BC',
		"curlywedge" => '\u22CF',
		"veebar" => '\u22BB',
		"curlyvee" => '\u22CE',
		"doublebarwedge" => '\u2A5E',
		"boxminus" => '\u229F',
		"circleddash" => '\u229D',
		"boxtimes" => '\u22A0',
		"circledast" => '\u229B',
		"boxdot" => '\u22A1',
		"circledcirc" => '\u229A',
		"boxplus" => '\u229E',
		"centerdot" => [ '\u22C5', [ "variantForm" => "True" ] ], // actually: "variantForm" => "True"
		"divideontimes" => '\u22C7',
		"intercal" => '\u22BA',
		"leqq" => '\u2266',
		"geqq" => '\u2267',
		"leqslant" => '\u2A7D',
		"geqslant" => '\u2A7E',
		"eqslantless" => '\u2A95',
		"eqslantgtr" => '\u2A96',
		"lesssim" => '\u2272',
		"gtrsim" => '\u2273',
		"lessapprox" => '\u2A85',
		"gtrapprox" => '\u2A86',
		"approxeq" => '\u224A',
		"lessdot" => '\u22D6',
		"gtrdot" => '\u22D7',
		"lll" => '\u22D8',
		"llless" => '\u22D8',
		"ggg" => '\u22D9',
		"gggtr" => '\u22D9',
		"lessgtr" => '\u2276',
		"gtrless" => '\u2277',
		"lesseqgtr" => '\u22DA',
		"gtreqless" => '\u22DB',
		"lesseqqgtr" => '\u2A8B',
		"gtreqqless" => '\u2A8C',
		"doteqdot" => '\u2251',
		"Doteq" => '\u2251',
		"eqcirc" => '\u2256',
		"risingdotseq" => '\u2253',
		"circeq" => '\u2257',
		"fallingdotseq" => '\u2252',
		"triangleq" => '\u225C',
		"backsim" => '\u223D',
		"thicksim" => [ '\u223C', [ "variantForm" => "True" ] ], // actually: "variantForm" => "True"
		"backsimeq" => '\u22CD',
		"thickapprox" => [ '\u2248', [ "variantForm" => "True" ] ], // actually: "variantForm" => "True"
		"subseteqq" => '\u2AC5',
		"supseteqq" => '\u2AC6',
		"Subset" => '\u22D0',
		"Supset" => '\u22D1',
		"sqsubset" => '\u228F',
		"sqsupset" => '\u2290',
		"preccurlyeq" => '\u227C',
		"succcurlyeq" => '\u227D',
		"curlyeqprec" => '\u22DE',
		"curlyeqsucc" => '\u22DF',
		"precsim" => '\u227E',
		"succsim" => '\u227F',
		"precapprox" => '\u2AB7',
		"succapprox" => '\u2AB8',
		"vartriangleleft" => '\u22B2',
		"lhd" => '\u22B2',
		"vartriangleright" => '\u22B3',
		"rhd" => '\u22B3',
		"trianglelefteq" => '\u22B4',
		"unlhd" => '\u22B4',
		"trianglerighteq" => '\u22B5',
		"unrhd" => '\u22B5',
		"vDash" => [ '\u22A8', [ "variantForm" => true ] ],
		"Vdash" => '\u22A9',
		"Vvdash" => '\u22AA',
		"smallsmile" => [ '\u2323', [ "variantForm" => true ] ],
		"shortmid" => [ '\u2223', [ "variantForm" => true ] ],
		"smallfrown" => [ '\u2322', [ "variantForm" => true ] ],
		"shortparallel" => [ '\u2225', [ "variantForm" => true ] ],
		"bumpeq" => '\u224F',
		"between" => '\u226C',
		"Bumpeq" => '\u224E',
		"pitchfork" => '\u22D4',
		"varpropto" => [ '\u221D', [ "variantForm" => true ] ],
		"backepsilon" => '\u220D',
		"blacktriangleleft" => '\u25C2',
		"blacktriangleright" => '\u25B8',
		"therefore" => '\u2234',
		"because" => '\u2235',
		"eqsim" => '\u2242',
		"vartriangle" => [ '\u25B3', [ "variantForm" => true ] ],
		"Join" => '\u22C8',
		"nless" => '\u226E',
		"ngtr" => '\u226F',
		"nleq" => '\u2270',
		"ngeq" => '\u2271',
		"nleqslant" => [ '\u2A87', [ "variantForm" => true ] ],
		"ngeqslant" => [ '\u2A88', [ "variantForm" => true ] ],
		"nleqq" => [ '\u2270', [ "variantForm" => true ] ],
		"ngeqq" => [ '\u2271', [ "variantForm" => true ] ],
		"lneq" => '\u2A87',
		"gneq" => '\u2A88',
		"lneqq" => '\u2268',
		"gneqq" => '\u2269',
		"lvertneqq" => [ '\u2268', [ "variantForm" => true ] ],
		"gvertneqq" => [ '\u2269', [ "variantForm" => true ] ],
		"lnsim" => '\u22E6',
		"gnsim" => '\u22E7',
		"lnapprox" => '\u2A89',
		"gnapprox" => '\u2A8A',
		"nprec" => '\u2280',
		"nsucc" => '\u2281',
		"npreceq" => [ '\u22E0', [ "variantForm" => true ] ],
		"nsucceq" => [ '\u22E1', [ "variantForm" => true ] ],
		"precneqq" => '\u2AB5',
		"succneqq" => '\u2AB6',
		"precnsim" => '\u22E8',
		"succnsim" => '\u22E9',
		"precnapprox" => '\u2AB9',
		"succnapprox" => '\u2ABA',
		"nsim" => '\u2241',
		"ncong" => '\u2246',
		"nshortmid" => [ '\u2224', [ "variantForm" => true ] ],
		"nshortparallel" => [ '\u2226', [ "variantForm" => true ] ],
		"nmid" => '\u2224',
		"nparallel" => '\u2226',
		"nvdash" => '\u22AC',
		"nvDash" => '\u22AD',
		"nVdash" => '\u22AE',
		"nVDash" => '\u22AF',
		"ntriangleleft" => '\u22EA',
		"ntriangleright" => '\u22EB',
		"ntrianglelefteq" => '\u22EC',
		"ntrianglerighteq" => '\u22ED',
		"nsubseteq" => '\u2288',
		"nsupseteq" => '\u2289',
		"nsubseteqq" => [ '\u2288', [ "variantForm" => true ] ],
		"nsupseteqq" => [ '\u2289', [ "variantForm" => true ] ],
		"subsetneq" => '\u228A',
		"supsetneq" => '\u228B',
		"varsubsetneq" => [ '\u228A', [ "variantForm" => true ] ],
		"varsupsetneq" => [ '\u228B', [ "variantForm" => true ] ],
		"subsetneqq" => '\u2ACB',
		"supsetneqq" => '\u2ACC',
		"varsubsetneqq" => [ '\u2ACB', [ "variantForm" => true ] ],
		"varsupsetneqq" => [ '\u2ACC', [ "variantForm" => true ] ],
		"leftleftarrows" => '\u21C7',
		"rightrightarrows" => '\u21C9',
		"leftrightarrows" => '\u21C6',
		"rightleftarrows" => '\u21C4',
		"Lleftarrow" => '\u21DA',
		"Rrightarrow" => '\u21DB',
		"twoheadleftarrow" => '\u219E',
		"twoheadrightarrow" => '\u21A0',
		"leftarrowtail" => '\u21A2',
		"rightarrowtail" => '\u21A3',
		"looparrowleft" => '\u21AB',
		"looparrowright" => '\u21AC',
		"leftrightharpoons" => '\u21CB',
		"rightleftharpoons" => [ '\u21CC', [ "variantForm" => true ] ],
		"curvearrowleft" => '\u21B6',
		"curvearrowright" => '\u21B7',
		"circlearrowleft" => '\u21BA',
		"circlearrowright" => '\u21BB',
		"Lsh" => '\u21B0',
		"Rsh" => '\u21B1',
		"upuparrows" => '\u21C8',
		"downdownarrows" => '\u21CA',
		"upharpoonleft" => '\u21BF',
		"upharpoonright" => '\u21BE',
		"downharpoonleft" => '\u21C3',
		"restriction" => '\u21BE',
		"multimap" => '\u22B8',
		"downharpoonright" => '\u21C2',
		"leftrightsquigarrow" => '\u21AD',
		"rightsquigarrow" => '\u21DD',
		"leadsto" => '\u21DD',
		"dashrightarrow" => '\u21E2',
		"dashleftarrow" => '\u21E0',
		"nleftarrow" => '\u219A',
		"nrightarrow" => '\u219B',
		"nLeftarrow" => '\u21CD',
		"nRightarrow" => '\u21CF',
		"nleftrightarrow" => '\u21AE',
		"nLeftrightarrow" => '\u21CE'
	];

	private const AMSMATHENVIRONMENT = [
		'equation*' => [ 'Equation', null, false ],
		'eqnarray*' => [ 'EqnArray', null, false, true, 'rcl', null, '.5em' ],
		'align' => [ 'EqnArray', null, true, true, 'rl', 'ParseUtil_js_1.default.cols(0, 2)' ],
		'align*' => [ 'EqnArray', null, false, true, 'rl', "ParseUtil_js_1.default.cols(0, 2)" ],
		"multline" => [ 'Multline', null, true ],
		'multline*' => [ 'Multline', null, false ],
		"split" => [ 'EqnArray', null, false, false, 'rl', "ParseUtil_js_1.default.cols(0)" ],
		"gather" => [ 'EqnArray', null, true, true, 'c' ],
		'gather*' => [ 'EqnArray', null, false, true, 'c' ],
		"alignat" => [ 'alignAt', null, true, true ],
		'alignat*' => [ 'alignAt', null, false, true ],
		"alignedat" => [ 'alignAt', null, false, false ],
		"aligned" => [ 'amsEqnArray', null, null, null, 'rl', "ParseUtil_js_1.default.cols(0, 2)", '.5em', 'D' ],
		"gathered" => [ 'amsEqnArray', null, null, null, 'c', null, '.5em', 'D' ],
		"xalignat" => [ 'XalignAt', null, true, true ],
		'xalignat*' => [ 'XalignAt', null, false, true ],
		"xxalignat" => [ 'XalignAt', null, false, false ],
		"flalign" => [ 'FlalignArray', null, true, false, true, 'rlc', 'auto auto fit' ],
		'flalign*' => [ 'FlalignArray', null, false, false, true, 'rlc', 'auto auto fit' ],
		"subarray" => [ 'array', null, null, null, null, "ParseUtil_js_1.default.cols(0)", '0.1em', 'S', 1 ],
		"smallmatrix" => [ 'array', null, null, null, 'c', "ParseUtil_js_1.default.cols(1 / 3)",
			'.2em', 'S', 1 ],
		"matrix" => [ 'array', null, null, null, 'c' ],
		"pmatrix" => [ 'array', null, '(', ')', 'c' ],
		"bmatrix" => [ 'array', null, '[', ']', 'c' ],
		"Bmatrix" => [ 'array', null, '\\{', '\\}', 'c' ],
		"vmatrix" => [ 'array', null, '\\vert', '\\vert', 'c' ],
		"Vmatrix" => [ 'array', null, '\\Vert', '\\Vert', 'c' ],
		"cases" => [ 'array', null, '\\{', '.', 'll', null, '.2em', 'T' ]
	];
	private const AMSSYMBOLDELIMITERS = [
		'ulcorner' => '\u231C',
		'urcorner' => '\u231D',
		'llcorner' => '\u231E',
		'lrcorner' => '\u231F'
	];

	private const AMSSYMBOLMACROS = [
		"implies" => [ 'macro', '\\;\\Longrightarrow\\;' ],
		"impliedby" => [ 'macro', '\\;\\Longleftarrow\\;' ]
	];

	private const AMSMATHDELIMITERS = [
		'lvert' => [ '\u007C', [ "texClass" => TexClass::OPEN ] ],
		'rvert' => [ '\u007C', [ "texClass" => TexClass::CLOSE ] ],
		'lVert' => [ '\u2016', [ "texClass" => TexClass::OPEN ] ],
		'rVert' => [ '\u2016', [ "texClass" => TexClass::CLOSE ] ]
	];
	private const ALL = [
		"amsmathchar0mo" => self::AMSMATHCHAR0MO,
		"amsmathchar0mi" => self::AMSMATHCHAR0MI,
		"amsmacros" => self::AMSMACROS,
		"amssymbolmacros" => self::AMSSYMBOLMACROS,
		"amsdelimiters" => self::AMSSYMBOLDELIMITERS,
		"amsmathenvironment" => self::AMSMATHENVIRONMENT
	];

	private function __construct() {
		// Just an empty private constructor, for singleton pattern
	}

	public static function removeInstance() {
		self::$instance = null;
	}

	public static function getInstance() {
		if ( self::$instance == null ) {
			self::$instance = new AMSMappings();
		}

		return self::$instance;
	}

	public static function getEntryFromList( $keylist, $key ) {
		if ( isset( self::ALL[$keylist][$key] ) ) {
			return self::ALL[$keylist][$key];
		}
		return null;
	}

	public static function getOperatorByKey( $key ) {
		// &#x221A; to \\u.... this is only temporary probably entities.php will be refactored with \u vals again
		$key = MMLutil::x2uNotation( $key );
		return MMLutil::getMappingByKey( $key, self::AMSMATHCHAR0MO, true );
	}

	public static function getIdentifierByKey( $key ) {
		return MMLutil::getMappingByKey( $key, self::AMSMATHCHAR0MI, true );
	}

	public static function getSymbolDelimiterByKey( $key ) {
		return MMLutil::getMappingByKey( $key, self::AMSSYMBOLDELIMITERS, true );
	}

	public static function getMathDelimiterByKey( $key ) {
		return MMLutil::getMappingByKey( $key, self::AMSMATHDELIMITERS, true );
	}

	public static function getMacroByKey( $key ) {
		$ret = MMLutil::getMappingByKey( $key, self::AMSMACROS );
		if ( $ret != null ) {
			return $ret;
		}
		return MMLutil::getMappingByKey( $key, self::AMSSYMBOLMACROS );
	}

	public static function getEnvironmentByKey( $key ) {
		return MMLutil::getMappingByKey( $key, self::AMSMATHENVIRONMENT );
	}
}
