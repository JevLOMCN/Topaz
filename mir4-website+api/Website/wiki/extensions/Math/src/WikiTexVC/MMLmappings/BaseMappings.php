<?php
namespace MediaWiki\Extension\Math\WikiTexVC\MMLmappings;

use MediaWiki\Extension\Math\WikiTexVC\MMLmappings\Lengths\MathSpace;
use MediaWiki\Extension\Math\WikiTexVC\MMLmappings\TexConstants\Notation;
use MediaWiki\Extension\Math\WikiTexVC\MMLmappings\TexConstants\Tag;
use MediaWiki\Extension\Math\WikiTexVC\MMLmappings\TexConstants\TexClass;
use MediaWiki\Extension\Math\WikiTexVC\MMLmappings\TexConstants\Variants;
use MediaWiki\Extension\Math\WikiTexVC\MMLmappings\Util\MMLutil;

/**
 * Based on BaseMappings.js in MML3
 * Singleton
 */
class BaseMappings {

	private static $instance = null;

	// Macro Map 'special'
	private const SPECIAL = [
		'{' => 'open',
		'}' => 'close',
		'~' => [ 'tilde', true ],
		'^' => 'superscript',
		'_' => 'subscript',
		' ' => 'space',
		'\t' => 'space',
		'\r' => 'space',
		'\n' => 'space',
		'\\' => 'prime',
		'%' => 'comment',
		'&' => 'entry',
		'#' => 'hash',
		'\u00A0' => 'space',
		'\u2019' => 'prime'
	];

	private const MATHCHAR0MI = [
		"alpha" => '\u03B1',
		"beta" => '\u03B2',
		"gamma" => '\u03B3',
		"delta" => '\u03B4',
		"epsilon" => '\u03F5',
		"zeta" => '\u03B6',
		"eta" => '\u03B7',
		"theta" => '\u03B8',
		"iota" => '\u03B9',
		"kappa" => '\u03BA',
		"lambda" => '\u03BB',
		"mu" => '\u03BC',
		"nu" => '\u03BD',
		"xi" => '\u03BE',
		"omicron" => '\u03BF',
		"pi" => '\u03C0',
		"rho" => '\u03C1',
		"sigma" => '\u03C3',
		"tau" => '\u03C4',
		"upsilon" => '\u03C5',
		"phi" => '\u03D5',
		"chi" => '\u03C7',
		"psi" => '\u03C8',
		"omega" => '\u03C9',
		"varepsilon" => '\u03B5',
		"vartheta" => '\u03D1',
		"varpi" => '\u03D6',
		"varrho" => '\u03F1',
		"varsigma" => '\u03C2',
		"varstigma" => "\u03DB",
		"varphi" => '\u03C6',
		// special case with \\ to distinguish from literal:
		 "\\S" => [ '\u00A7', [ "mathvariant" => Variants::NORMAL ] ],
		"aleph" => [ '\u2135', [ "mathvariant" => Variants::NORMAL ] ],
		"hbar" => [ '\u210F', [ Tag::ALTERNATE => "1" ] ], // actually: "variantForm" => "True"
		"imath" => '\u0131',
		"jmath" => '\u0237',
		"ell" => '\u2113',
		"wp" => [ '\u2118', [ "mathvariant" => Variants::NORMAL ] ],
		"Re" => [ '\u211C', [ "mathvariant" => Variants::NORMAL ] ],
		"Im" => [ '\u2111', [ "mathvariant" => Variants::NORMAL ] ],
		"partial" => [ '\u2202', [] ], // "mathvariant" => Variants::ITALIC ] this leads to 'wrong' output
		"infty" => [ '\u221E', [ "mathvariant" => Variants::NORMAL ] ],
		"prime" => [ '\u2032', [ Tag::ALTERNATE => "1" ] ], // actually: "variantForm" => "True"
		"emptyset" => [ '\u2205', [ "mathvariant" => Variants::NORMAL ] ],
		"nabla" => [ '\u2207', [ "mathvariant" => Variants::NORMAL ] ],
		"top" => [ '\u22A4', [ "mathvariant" => Variants::NORMAL ] ],
		"bot" => [ '\u22A5', [ "mathvariant" => Variants::NORMAL ] ],
		"angle" => [ '\u2220', [ "mathvariant" => Variants::NORMAL ] ],
		"triangle" => [ '\u25B3', [ "mathvariant" => Variants::NORMAL ] ],
		"backslash" => [ '\u2216', [ "mathvariant" => Variants::NORMAL ] ],
		"forall" => [ '\u2200', [ "mathvariant" => Variants::NORMAL ] ],
		"exists" => [ '\u2203', [ "mathvariant" => Variants::NORMAL ] ],
		"neg" => [ '\u00AC', [ "mathvariant" => Variants::NORMAL ] ],
		"lnot" => [ '\u00AC', [ "mathvariant" => Variants::NORMAL ] ],
		"flat" => [ '\u266D', [ "mathvariant" => Variants::NORMAL ] ],
		"natural" => [ '\u266E', [ "mathvariant" => Variants::NORMAL ] ],
		"sharp" => [ '\u266F', [ "mathvariant" => Variants::NORMAL ] ],
		"clubsuit" => [ '\u2663', [ "mathvariant" => Variants::NORMAL ] ],
		"diamondsuit" => [ '\u2662', [ "mathvariant" => Variants::NORMAL ] ],
		"heartsuit" => [ '\u2661', [ "mathvariant" => Variants::NORMAL ] ],
		"spadesuit" => [ '\u2660', [ "mathvariant" => Variants::NORMAL ] ]
	];

	private const MATHCHAR0MO = [
		"-" => '\u2212', // added this additionally for running all tc
		"surd" => '\u221A',
		"coprod" => [ '\u2210', [ "texClass" => TexClass::OP,
			"movesupsub" => true ] ],
		"bigvee" => [ '\u22C1', [ "texClass" => TexClass::OP,
			"movesupsub" => true ] ],
		"bigwedge" => [ '\u22C0', [ "texClass" => TexClass::OP,
			"movesupsub" => true ] ],
		"biguplus" => [ '\u2A04', [ "texClass" => TexClass::OP,
			"movesupsub" => true ] ],
		"bigcap" => [ '\u22C2', [ "texClass" => TexClass::OP,
		   "movesupsub" => true ] ],
		"bigcup" => [ '\u22C3', [ "texClass" => TexClass::OP,
			"movesupsub" => true ] ],
		"int" => [ '\u222B', [ "texClass" => TexClass::OP ] ],
		"intbar" => [ '\u2A0D', [ "texClass" => TexClass::OP ] ],
		"intBar" => [ '\u2A0E', [ "texClass" => TexClass::OP ] ],
		"intop" => [ '\u222B', [ "texClass" => TexClass::OP,
			"movesupsub" => true, "movablelimits" => true ] ],
		"iint" => [ '\u222C', [ "texClass" => TexClass::OP ] ],
		"iiint" => [ '\u222D', [ "texClass" => TexClass::OP ] ],
		"prod" => [ '\u220F', [ "texClass" => TexClass::OP,
			"movesupsub" => true ] ],
		"sum" => [ '\u2211', [ "texClass" => TexClass::OP,
			"movesupsub" => true ] ],
		"bigotimes" => [ '\u2A02', [ "texClass" => TexClass::OP,
			"movesupsub" => true ] ],
		"bigoplus" => [ '\u2A01', [ "texClass" => TexClass::OP,
			"movesupsub" => true ] ],
		"bigodot" => [ '\u2A00', [ "texClass" => TexClass::OP,
			"movesupsub" => true ] ],
		"bigsqcup" => [ '\u2A06', [ "texClass" => TexClass::OP,
			"movesupsub" => true ] ],
		"smallint" => [ '\u222B', [ "largeop" => false ] ],
		"triangleleft" => '\u25C3',
		"triangleright" => '\u25B9',
		"bigtriangleup" => '\u25B3',
		"bigtriangledown" => '\u25BD',
		"wedge" => '\u2227',
		"land" => '\u2227',
		"vee" => '\u2228',
		"lor" => '\u2228',
		"cap" => '\u2229',
		"cup" => '\u222A',
		"ddagger" => '\u2021',
		"dagger" => '\u2020',
		"sqcap" => '\u2293',
		"sqcup" => '\u2294',
		"uplus" => '\u228E',
		"amalg" => '\u2A3F',
		"diamond" => '\u22C4',
		"bullet" => '\u2219',
		"wr" => '\u2240',
		"div" => '\u00F7',
		"divsymbol" => '\u00F7',
		"odot" => [ '\u2299', [ "largeop" => false ] ],
		"oslash" => [ '\u2298', [ "largeop" => false ] ],
		"otimes" => [ '\u2297', [ "largeop" => false ] ],
		"ominus" => [ '\u2296', [ "largeop" => false ] ],
		"oplus" => [ '\u2295', [ "largeop" => false ] ],
		"mp" => '\u2213',
		"pm" => '\u00B1',
		"circ" => '\u2218',
		"bigcirc" => '\u25EF',
		"setminus" => '\u2216',
		"cdot" => '\u22C5',
		"ast" => '\u2217',
		"times" => '\u00D7',
		"star" => '\u22C6',
		"propto" => '\u221D',
		"sqsubseteq" => '\u2291',
		"sqsupseteq" => '\u2292',
		"parallel" => '\u2225',
		"mid" => '\u2223',
		"dashv" => '\u22A3',
		"vdash" => '\u22A2',
		"leq" => '\u2264',
		"le" => '\u2264',
		"geq" => '\u2265',
		"ge" => '\u2265',
		"lt" => '\u003C',
		"gt" => '\u003E',
		"succ" => '\u227B',
		"prec" => '\u227A',
		"approx" => '\u2248',
		"succeq" => '\u2AB0',
		"preceq" => '\u2AAF',
		"supset" => '\u2283',
		"subset" => '\u2282',
		"supseteq" => '\u2287',
		"subseteq" => '\u2286',
		"in" => '\u2208',
		"ni" => '\u220B',
		"notin" => '\u2209',
		"owns" => '\u220B',
		"gg" => '\u226B',
		"ll" => '\u226A',
		"sim" => '\u223C',
		"simeq" => '\u2243',
		"perp" => '\u22A5',
		"equiv" => '\u2261',
		"asymp" => '\u224D',
		"smile" => '\u2323',
		"frown" => '\u2322',
		"ne" => '\u2260',
		"neq" => '\u2260',
		"cong" => '\u2245',
		"doteq" => '\u2250',
		"bowtie" => '\u22C8',
		"models" => '\u22A8',
		"notChar" => '\u29F8',
		"Leftrightarrow" => '\u21D4',
		"Leftarrow" => '\u21D0',
		"Rightarrow" => '\u21D2',
		"leftrightarrow" => '\u2194',
		"leftarrow" => '\u2190',
		"gets" => '\u2190',
		"rightarrow" => '\u2192',
		"to" => [ '\u2192', [ "accent" => "false" ] ],
		"mapsto" => [ '\u21A6', [ "stretchy" => "false" ] ], // added stretchy for tests
		"leftharpoonup" => '\u21BC',
		"leftharpoondown" => '\u21BD',
		"rightharpoonup" => '\u21C0',
		"rightharpoondown" => '\u21C1',
		"nearrow" => '\u2197',
		"searrow" => '\u2198',
		"nwarrow" => '\u2196',
		"swarrow" => '\u2199',
		"rightleftharpoons" => '\u21CC',
		"hookrightarrow" => '\u21AA',
		"hookleftarrow" => '\u21A9',
		"longleftarrow" => '\u27F5',
		"Longleftarrow" => '\u27F8',
		"longrightarrow" => '\u27F6',
		"Longrightarrow" => '\u27F9',
		"Longleftrightarrow" => '\u27FA',
		"longleftrightarrow" => '\u27F7',
		"longmapsto" => [ '\u27FC', [ "stretchy" => "false" ] ], // added stretchy for test
		"ldots" => '\u2026',
		"cdots" => '\u22EF',
		// "cdots" => '\u2026', // fallback
		"vdots" => '\u22EE',
		"ddots" => '\u22F1',
		"dotsc" => '\u2026',
		"dotsb" => '\u22EF',
		// "dotsb" => '\u2026', // fallback
		"dotsm" => '\u22EF',
		// "dotsm" => '\u2026', // fallback
		"dotsi" => '\u22EF',
		// "dotsi" => '\u2026', // fallback

		"dotso" => '\u2026',
		"ldotp" => [ '\u002E', [ "texClass" => TexClass::PUNCT ] ],
		"cdotp" => [ '\u22C5', [ "texClass" => TexClass::PUNCT ] ],
		"colon" => [ '\u003A', [ "texClass" => TexClass::PUNCT ] ]
	];

	private const MATCHAR7 = [
		"Gamma" => '\u0393',
		"Delta" => '\u0394',
		"Theta" => '\u0398',
		"Lambda" => '\u039B',
		"Xi" => '\u039E',
		"Pi" => '\u03A0',
		"Sigma" => '\u03A3',
		"Upsilon" => '\u03A5',
		"Phi" => '\u03A6',
		"Psi" => '\u03A8',
		"Omega" => '\u03A9',
		'_' => '\u005F',
		'#' => '\u0023',
		'$' => '\u0024',
		'%' => '\u0025',
		'&' => '\u0026',
		'And' => '\u0026'
	];

	private const DELIMITER = [
		'(' => [ '(', [ "stretchy" => "false" ] ], // added this additionally for running all tc
		')' => [ ')', [ "stretchy" => "false" ] ], // added this additionally for running all tc
		'[' => [ '[', [ "stretchy" => "false" ] ], // added this additionally for running all tc
		']' => [ ']', [ "stretchy" => "false" ] ], // added this additionally for running all tc
		'<' => '\u27E8',
		'>' => '\u27E9',
		'\\lt' => '\u27E8',
		'\\gt' => '\u27E9',
		'/' => '/',
		'|' => [ '|', [ "texClass" => TexClass::ORD ] ],
		'.' => '',
		'\\\\' => '\\',
		'\\lmoustache' => '\u23B0',
		'\\rmoustache' => '\u23B1',
		'\\lgroup' => '\u27EE',
		'\\rgroup' => '\u27EF',
		'\\arrowvert' => '\u23D0',
		'\\Arrowvert' => '\u2016',
		'\\bracevert' => '\u23AA',
		'\\Vert' => [ '\u2016', [ "texClass" => TexClass::ORD ] ],
		'\\|' => [ '\u2016', [ "texClass" => TexClass::ORD ] ],
		'\\vert' => [ '|', [ "texClass" => TexClass::ORD ] ],
		'\\uparrow' => '\u2191',
		'\\downarrow' => '\u2193',
		'\\updownarrow' => '\u2195',
		'\\Uparrow' => '\u21D1',
		'\\Downarrow' => '\u21D3',
		'\\Updownarrow' => '\u21D5',
		'\\backslash' => '\\',
		"\\rangle" => '\u27E9',
		'\\langle' => '\u27E8',
		'\\rbrace' => '}',
		'\\lbrace' => '{',
		// added this attrs additionally for running all tc:
		'\\}' => [ '}', [ "fence" => "false", "stretchy" => "false" ] ],
		// added this attrs additionally for running all tc:
		'\\{' => [ '{', [ "fence" => "false", "stretchy" => "false" ] ],
		'\\rceil' => '\u2309',
		'\\lceil' => '\u2308',
		'\\rfloor' => '\u230B',
		'\\lfloor' => '\u230A',
		'\\lbrack' => '[',
		'\\rbrack' => ']'
	];

	private const MACROS = [
		"displaystyle" => [ 'setStyle', 'D', true, 0 ],
		"textstyle" => [ 'setStyle', 'T', false, 0 ],
		"scriptstyle" => [ 'setStyle', 'S', false, 1 ],
		"scriptscriptstyle" => [ 'setStyle', 'SS', false, 2 ],
		"rm" => [ 'setFont', Variants::NORMAL ],
		"mit" => [ 'setFont', Variants::ITALIC ],
		"oldstyle" => [ 'setFont', Variants::OLDSTYLE ],
		"cal" => [ 'setFont', Variants::CALLIGRAPHIC ],
		"it" => [ 'setFont', Variants::MATHITALIC ],
		"bf" => [ 'setFont', Variants::BOLD ],
		"bbFont" => [ 'setFont', Variants::DOUBLESTRUCK ],
		"scr" => [ 'setFont', Variants::SCRIPT ],
		"frak" => [ 'setFont', Variants::FRAKTUR ],
		"sf" => [ 'setFont', Variants::SANSSERIF ],
		"tt" => [ 'setFont', Variants::MONOSPACE ],
		"mathrm" => [ 'mathFont', Variants::NORMAL ],
		"mathup" => [ 'mathFont', Variants::NORMAL ],
		"mathnormal" => [ 'mathFont', '' ],
		"mathbf" => [ 'mathFont', Variants::BOLD ],
		"mathbfup" => [ 'mathFont', Variants::BOLD ],
		"mathit" => [ 'mathFont', Variants::MATHITALIC ],
		"mathbfit" => [ 'mathFont', Variants::BOLDITALIC ],
		"mathbb" => [ 'mathFont', Variants::DOUBLESTRUCK ],
		"Bbb" => [ 'mathFont', Variants::DOUBLESTRUCK ],
		"mathfrak" => [ 'mathFont', Variants::FRAKTUR ],
		"mathbffrak" => [ 'mathFont', Variants::BOLDFRAKTUR ],
		"mathscr" => [ 'mathFont', Variants::SCRIPT ],
		"mathbfscr" => [ 'mathFont', Variants::BOLDSCRIPT ],
		"mathsf" => [ 'mathFont', Variants::SANSSERIF ],
		"mathsfup" => [ 'mathFont', Variants::SANSSERIF ],
		"mathbfsf" => [ 'mathFont', Variants::BOLDSANSSERIF ],
		"mathbfsfup" => [ 'mathFont', Variants::BOLDSANSSERIF ],
		"mathsfit" => [ 'mathFont', Variants::SANSSERIFITALIC ],
		"mathbfsfit" => [ 'mathFont', Variants::SANSSERIFBOLDITALIC ],
		"mathtt" => [ 'mathFont', Variants::MONOSPACE ],
		"mathcal" => [ 'mathFont', Variants::CALLIGRAPHIC ],
		"mathbfcal" => [ 'mathFont', Variants::BOLDCALLIGRAPHIC ],
		"emph" => [ 'mathFont', Variants::ITALIC ], // added this specific case, toggles roman/italic fonts
		"symrm" => [ 'mathFont', Variants::NORMAL ],
		"symup" => [ 'mathFont', Variants::NORMAL ],
		"symnormal" => [ 'mathFont', '' ],
		"symbf" => [ 'mathFont', Variants::BOLD ],
		"symbfup" => [ 'mathFont', Variants::BOLD ],
		"symit" => [ 'mathFont', Variants::ITALIC ],
		"symbfit" => [ 'mathFont', Variants::BOLDITALIC ],
		"symbb" => [ 'mathFont', Variants::DOUBLESTRUCK ],
		"symfrak" => [ 'mathFont', Variants::FRAKTUR ],
		"symbffrak" => [ 'mathFont', Variants::BOLDFRAKTUR ],
		"symscr" => [ 'mathFont', Variants::SCRIPT ],
		"symbfscr" => [ 'mathFont', Variants::BOLDSCRIPT ],
		"symsf" => [ 'mathFont', Variants::SANSSERIF ],
		"symsfup" => [ 'mathFont', Variants::SANSSERIF ],
		"symbfsf" => [ 'mathFont', Variants::BOLDSANSSERIF ],
		"symbfsfup" => [ 'mathFont', Variants::BOLDSANSSERIF ],
		"symsfit" => [ 'mathFont', Variants::SANSSERIFITALIC ],
		"symbfsfit" => [ 'mathFont', Variants::SANSSERIFBOLDITALIC ],
		"symtt" => [ 'mathFont', Variants::MONOSPACE ],
		"symcal" => [ 'mathFont', Variants::CALLIGRAPHIC ],
		"symbfcal" => [ 'mathFont', Variants::BOLDCALLIGRAPHIC ],
		"textrm" => [ 'hBox', null, Variants::NORMAL ],
		"textup" => [ 'hBox', null, Variants::NORMAL ],
		"textnormal" => [ 'hBox' ],
		"textit" => [ 'hBox', null, Variants::ITALIC ],
		"textbf" => [ 'hBox', null, Variants::BOLD ],
		"textsf" => [ 'hBox', null, Variants::SANSSERIF ],
		"texttt" => [ 'hBox', null, Variants::MONOSPACE ],
		"tiny" => [ 'SetSize', 0.5 ],
		"Tiny" => [ 'SetSize', 0.6 ],
		"scriptsize" => [ 'SetSize', 0.7 ],
		"small" => [ 'SetSize', 0.85 ],
		"normalsize" => [ 'SetSize', 1.0 ],
		"large" => [ 'SetSize', 1.2 ],
		"Large" => [ 'SetSize', 1.44 ],
		"LARGE" => [ 'SetSize', 1.73 ],
		"huge" => [ 'SetSize', 2.07 ],
		"Huge" => [ 'SetSize', 2.49 ],
		"arcsin" => 'namedFn',
		"arccos" => 'namedFn',
		"arctan" => 'namedFn',
		"arg" => 'namedFn',
		"cos" => 'namedFn',
		"cosh" => 'namedFn',
		"cot" => 'namedFn',
		"coth" => 'namedFn',
		"csc" => 'namedFn',
		"deg" => 'namedFn',
		"det" => 'namedOp',
		"dim" => 'namedFn',
		"exp" => 'namedFn',
		"gcd" => 'namedOp',
		"hom" => 'namedFn',
		"inf" => 'namedOp',
		"ker" => 'namedFn',
		"lg" => 'namedFn',
		"lim" => 'namedOp',
		"liminf" => [ 'namedOp', 'lim inf' ],
		"limsup" => [ 'namedOp', 'lim sup' ],
		"ln" => 'namedFn',
		"log" => 'namedFn',
		"max" => 'namedOp',
		"min" => 'namedOp',
		"Pr" => 'namedOp',
		"sec" => 'namedFn',
		"sin" => 'namedFn',
		"sinh" => 'namedFn',
		"sup" => 'namedOp',
		"tan" => 'namedFn',
		"tanh" => 'namedFn',
		"limits" => [ 'limits', 1 ],
		"nolimits" => [ 'limits', 0 ],
		"overline" => [ 'underOver', '203E' ],
		"underline" => [ 'underOver', '_', null, true ],
		"overarc" => [ 'underOver', '23DC', 1 ],
		"overbrace" => [ 'underOver', '23DE', 1 ],
		"underbrace" => [ 'underOver', '23DF', 1 ],
		"overparen" => [ 'underOver', '23DC' ],
		"underparen" => [ 'underOver', '23DD' ],
		"overrightarrow" => [ 'underOver', '2192' ],
		"underrightarrow" => [ 'underOver', '2192' ],
		"overleftarrow" => [ 'underOver', '2190' ],
		"underleftarrow" => [ 'underOver', '2190' ],
		"overleftrightarrow" => [ 'underOver', '2194' ],
		"underleftrightarrow" => [ 'underOver', '2194' ],
		"overset" => 'overset',
		"underset" => 'underset',
		"overunderset" => 'Overunderset',
		"stackrel" => [ 'macro', '\\mathrel{\\mathop{#2}\\limits^{#1}}', 2 ],
		"stackbin" => [ 'macro', '\\mathbin{\\mathop{#2}\\limits^{#1}}', 2 ],
		"over" => 'over',
		"overwithdelims" => 'over',
		"atop" => 'over',
		"atopwithdelims" => 'over',
		"above" => 'over',
		"abovewithdelims" => 'over',
		"brace" => [ 'over', '{', '}' ],
		"brack" => [ 'over', '[', ']' ],
		"choose" => [ 'over', '(', ')' ],
		"frac" => 'frac',
		"sqrt" => 'sqrt',
		"root" => 'Root',
		"uproot" => [ 'MoveRoot', 'upRoot' ],
		"leftroot" => [ 'MoveRoot', 'leftRoot' ],
		"left" => 'LeftRight',
		"right" => 'LeftRight',
		"middle" => 'LeftRight',
		"llap" => 'lap',
		"rlap" => 'lap',
		"raise" => 'raiseLower',
		"lower" => 'raiseLower',
		"moveleft" => 'MoveLeftRight',
		"moveright" => 'MoveLeftRight',
		'\\,' => [ 'spacer', MathSpace::THINMATHSPACE ],
		"\\'" => [ 'spacer', MathSpace::MEDIUMMATHSPACE ],
		'\\>' => [ 'spacer', MathSpace::MEDIUMMATHSPACE ],
		'\\;' => [ 'spacer', MathSpace::THICKMATHSPACE ],
		'\\!' => [ 'spacer', MathSpace::NEGATIVETHINMATHSPACE ],
		"enspace" => [ 'spacer', 0.5 ],
		"quad" => [ 'spacer', 1 ],
		"qquad" => [ 'spacer', 2 ],
		"thinspace" => [ 'spacer', MathSpace::THINMATHSPACE ],
		"negthinspace" => [ 'spacer', MathSpace::NEGATIVETHINMATHSPACE ],
		"hskip" => 'hskip',
		"hspace" => 'hskip',
		"kern" => 'hskip',
		"mskip" => 'hskip',
		"mspace" => 'hskip',
		"mkern" => 'hskip',
		"rule" => 'rule',
		"Rule" => [ 'Rule' ],
		"Space" => [ 'Rule', 'blank' ],
		"nonscript" => 'Nonscript',
		"big" => [ 'makeBig', TexClass::ORD, 0.85 ],
		"Big" => [ 'makeBig', TexClass::ORD, 1.15 ],
		"bigg" => [ 'makeBig', TexClass::ORD, 1.45 ],
		"Bigg" => [ 'makeBig', TexClass::ORD, 1.75 ],
		"bigl" => [ 'makeBig', TexClass::OPEN, 0.85 ],
		"Bigl" => [ 'makeBig', TexClass::OPEN, 1.15 ],
		"biggl" => [ 'makeBig', TexClass::OPEN, 1.45 ],
		"Biggl" => [ 'makeBig', TexClass::OPEN, 1.75 ],
		"bigr" => [ 'makeBig', TexClass::CLOSE, 0.85 ],
		"Bigr" => [ 'makeBig', TexClass::CLOSE, 1.15 ],
		"biggr" => [ 'makeBig', TexClass::CLOSE, 1.45 ],
		"Biggr" => [ 'makeBig', TexClass::CLOSE, 1.75 ],
		"bigm" => [ 'makeBig', TexClass::REL, 0.85 ],
		"Bigm" => [ 'makeBig', TexClass::REL, 1.15 ],
		"biggm" => [ 'makeBig', TexClass::REL, 1.45 ],
		"Biggm" => [ 'makeBig', TexClass::REL, 1.75 ],
		"mathord" => [ 'TeXAtom', TexClass::ORD ],
		"mathop" => [ 'TeXAtom', TexClass::OP ],
		"mathopen" => [ 'TeXAtom', TexClass::OPEN ],
		"mathclose" => [ 'TeXAtom', TexClass::CLOSE ],
		"mathbin" => [ 'TeXAtom', TexClass::BIN ],
		"mathrel" => [ 'TeXAtom', TexClass::REL ],
		"mathpunct" => [ 'TeXAtom', TexClass::PUNCT ],
		"mathinner" => [ 'TeXAtom', TexClass::INNER ],
		"vcenter" => [ 'TeXAtom', TexClass::VCENTER ],
		"buildrel" => 'BuildRel',
		"hbox" => [ 'hBox', 0 ],
		"text" => 'hBox',
		"mbox" => [ 'hBox', 0 ],
		"vbox" => [ 'vbox', 0 ], // added this here in addition
		"fbox" => 'FBox',
		"boxed" => [ 'macro', '\\fbox{$\\displaystyle{#1}$}', 1 ],
		"framebox" => 'FrameBox',
		"strut" => 'Strut',
		"mathstrut" => [ 'macro', '\\vphantom{(}' ],
		"phantom" => 'phantom',
		"vphantom" => [ 'phantom', 1, 0 ],
		"hphantom" => [ 'phantom', 0, 1 ],
		"smash" => 'smash',
		"acute" => [ 'accent', '00B4' ],
		"grave" => [ 'accent', '0060' ],
		"ddot" => [ 'accent', '00A8' ],
		"tilde" => [ 'accent', '007E' ],
		"bar" => [ 'accent', '00AF' ],
		"breve" => [ 'accent', '02D8' ],
		"check" => [ 'accent', '02C7' ],
		"hat" => [ 'accent', '005E' ],
		"vec" => [ 'accent', '2192' ],
		"dot" => [ 'accent', '02D9' ],
		"widetilde" => [ 'accent', '007E', 1 ],
		"widehat" => [ 'accent', '005E', 1 ],
		"matrix" => 'matrix',
		"array" => 'matrix',
		"pmatrix" => [ 'matrix', '(', ')' ],
		"cases" => [ 'matrix', '{', '', 'left left', null, '.1em', null,
		true ],
		"eqalign" => [ 'matrix', null, null, 'right left',
		"(0, lengths_js_1.em)(MathSpace::thickmathspace)", '.5em', 'D' ],
		"displaylines" => [ 'matrix', null, null, 'center', null, '.5em', 'D' ],
		"cr" => 'Cr',
		"\\" => 'crLaTeX',
		"newline" => [ 'crLaTeX', true ],
		"hline" => [ 'hline', 'solid' ],
		"hdashline" => [ 'hline', 'dashed' ],
		"eqalignno" => [ 'matrix', null, null, 'right left',
		"(0, lengths_js_1.em)(MathSpace::thickmathspace)", '.5em', 'D', null,
		'right' ],
		"leqalignno" => [ 'matrix', null, null, 'right left',
		"(0, lengths_js_1.em)(MathSpace::thickmathspace)", '.5em', 'D', null,
		'left' ],
		"hfill" => 'HFill',
		"hfil" => 'HFill',
		"hfilll" => 'HFill',
		"bmod" => [ 'macro', '\\mmlToken{mo}[lspace="0.2777777777777778em"' .
			// "0.2777777777777778em" is equivlent to thickmathspace T320910
			' rspace="0.2777777777777778em"]{mod}' ],
		"pmod" => [ 'macro', '\\pod{\\mmlToken{mi}{mod}\\kern 6mu #1}', 1 ],
		"mod" => [ 'macro', '\\mathchoice{\\kern18mu}{\\kern12mu}' .
			'{\\kern12mu}{\\kern12mu}\\mmlToken{mi}{mod}\\,\\,#1',
		1 ],
		"pod" => [ 'macro', '\\mathchoice{\\kern18mu}{\\kern8mu}' .
			'{\\kern8mu}{\\kern8mu}(#1)', 1 ],
		"iff" => [ 'macro', '\\;\\Longleftrightarrow\\;' ],
		"skew" => [ 'macro', '{{#2{#3\\mkern#1mu}\\mkern-#1mu}{}}', 3 ],
		"pmb" => [ 'macro', '\\rlap{#1}\\kern1px{#1}', 1 ],
		"TeX" => [ 'macro', 'T\\kern-.14em\\lower.5ex{E}\\kern-.115em X' ],
		"LaTeX" => [ 'macro', 'L\\kern-.325em\\raise.21em' .
			'{\\scriptstyle{A}}\\kern-.17em\\TeX' ],
		' ' => [ 'macro', '\\text{ }' ],
		"not" => 'not',
		"dots" => 'dots',
		"space" => 'Tilde',
		'\u00A0' => 'Tilde',
		"begin" => 'BeginEnd',
		"end" => 'BeginEnd',
		"label" => 'HandleLabel',
		"ref" => 'HandleRef',
		"nonumber" => 'HandleNoTag',
		"mathchoice" => 'mathChoice',
		"mmlToken" => 'MmlToken',
		"intent" => 'intent'
	];

	private const NULLARYMACROS = [
		"AA" => "\u00C5",
		"Coppa" => "\u03D8",
		"Digamma" => "\u03DC",
		"Koppa" => "\u03DE",
		"Sampi" => "\u03E0",
		"Stigma" => "\u03DA",
		"coppa" => "\u03D9",
		"euro" => "\u20AC",
		"geneuro" => "\u20AC",
		"geneuronarrow" => "\u20AC",
		"geneurowide" => "\u20AC",
		"koppa" => "\u03DF",
		"officialeuro" => "\u20AC",
		"sampi" => "\u03E1",
		"stigma" => "\u03DB",
		"textvisiblespace" => "\u2423"
	];

	private const ENVIRONMENT = [
		"array" => [ 'AlignedArray' ],
		"equation" => [ 'Equation', null, true ],
		"eqnarray" => [ 'EqnArray', null, true, true, 'rcl', null, '.5em' ]
	];

	// Mathtools environment actually from Mathtools mappings tbd refactor
	private const ENVIRONMNENTMT = [
		'dcases' => [ 'array', null, '\\{', '', 'll', null, '.2em', 'D' ],
		'rcases' => [ 'array', null, '', '\\}', 'll', null, '.2em' ],
		'drcases' => [ 'array', null, '', '\\}', 'll', null, '.2em', 'D' ],
		'dcases*' => [ 'Cases', null, '{', '', 'D' ],
		'rcases*' => [ 'Cases', null, '', '}' ],
		'drcases*' => [ 'Cases', null, '', '}', 'D' ],
		'cases*' => [ 'Cases', null, '{', '' ],
		'matrix*' => [ 'MtMatrix', null, null, null ],
		'pmatrix*' => [ 'MtMatrix', null, '(', ')' ],
		'bmatrix*' => [ 'MtMatrix', null, '[', ']' ],
		'Bmatrix*' => [ 'MtMatrix', null, '\\{', '\\}' ],
		'vmatrix*' => [ 'MtMatrix', null, '\\vert', '\\vert' ],
		'Vmatrix*' => [ 'MtMatrix', null, '\\Vert', '\\Vert' ],
		'smallmatrix*' => [ 'MtSmallMatrix', null, null, null ],
		'psmallmatrix' => [ 'MtSmallMatrix', null, '(', ')', 'c' ],
		'psmallmatrix*' => [ 'MtSmallMatrix', null, '(', ')' ],
		'bsmallmatrix' => [ 'MtSmallMatrix', null, '[', ']', 'c' ],
		'bsmallmatrix*' => [ 'MtSmallMatrix', null, '[', ']' ],
		'Bsmallmatrix' => [ 'MtSmallMatrix', null, '\\{', '\\}', 'c' ],
		'Bsmallmatrix*' => [ 'MtSmallMatrix', null, '\\{', '\\}' ],
		'vsmallmatrix' => [ 'MtSmallMatrix', null, '\\vert', '\\vert', 'c' ],
		'vsmallmatrix*' => [ 'MtSmallMatrix', null, '\\vert', '\\vert' ],
		'Vsmallmatrix' => [ 'MtSmallMatrix', null, '\\Vert', '\\Vert', 'c' ],
		'Vsmallmatrix*' => [ 'MtSmallMatrix', null, '\\Vert', '\\Vert' ],
		'crampedsubarray' => [ 'array', null, null, null, null, '0em', '0.1em', 'S\'', 1 ],
		'multlined' => 'MtMultlined',
		'spreadlines' => [ 'SpreadLines', true ],
		'lgathered' => [ 'amsEqnArray', null, null, null, 'l', null, '.5em', 'D' ],
		'rgathered' => [ 'amsEqnArray', null, null, null, 'r', null, '.5em', 'D' ],
	];

	private const COLORS = [
		'Apricot' => '#FBB982',
		'Aquamarine' => '#00B5BE',
		'Bittersweet' => '#C04F17',
		'Black' => '#221E1F',
		'Blue' => '#2D2F92',
		'BlueGreen' => '#00B3B8',
		'BlueViolet' => '#473992',
		'BrickRed' => '#B6321C',
		'Brown' => '#792500',
		'BurntOrange' => '#F7921D',
		'CadetBlue' => '#74729A',
		'CarnationPink' => '#F282B4',
		'Cerulean' => '#00A2E3',
		'CornflowerBlue' => '#41B0E4',
		'Cyan' => '#00AEEF',
		'Dandelion' => '#FDBC42',
		'DarkOrchid' => '#A4538A',
		'Emerald' => '#00A99D',
		'ForestGreen' => '#009B55',
		'Fuchsia' => '#8C368C',
		'Goldenrod' => '#FFDF42',
		'Gray' => '#949698',
		'Green' => '#00A64F',
		'GreenYellow' => '#DFE674',
		'JungleGreen' => '#00A99A',
		'Lavender' => '#F49EC4',
		'LimeGreen' => '#8DC73E',
		'Magenta' => '#EC008C',
		'Mahogany' => '#A9341F',
		'Maroon' => '#AF3235',
		'Melon' => '#F89E7B',
		'MidnightBlue' => '#006795',
		'Mulberry' => '#A93C93',
		'NavyBlue' => '#006EB8',
		'OliveGreen' => '#3C8031',
		'Orange' => '#F58137',
		'OrangeRed' => '#ED135A',
		'Orchid' => '#AF72B0',
		'Peach' => '#F7965A',
		'Periwinkle' => '#7977B8',
		'PineGreen' => '#008B72',
		'Plum' => '#92268F',
		'ProcessBlue' => '#00B0F0',
		'Purple' => '#99479B',
		'RawSienna' => '#974006',
		'Red' => '#ED1B23',
		'RedOrange' => '#F26035',
		'RedViolet' => '#A1246B',
		'Rhodamine' => '#EF559F',
		'RoyalBlue' => '#0071BC',
		'RoyalPurple' => '#613F99',
		'RubineRed' => '#ED017D',
		'Salmon' => '#F69289',
		'SeaGreen' => '#3FBC9D',
		'Sepia' => '#671800',
		'SkyBlue' => '#46C5DD',
		'SpringGreen' => '#C6DC67',
		'Tan' => '#DA9D76',
		'TealBlue' => '#00AEB3',
		'Thistle' => '#D883B7',
		'Turquoise' => '#00B4CE',
		'Violet' => '#58429B',
		'VioletRed' => '#EF58A0',
		'White' => '#FFFFFF',
		'WildStrawberry' => '#EE2967',
		'Yellow' => '#FFF200',
		'YellowGreen' => '#98CC70',
		'YellowOrange' => '#FAA21A',
	];

	// This is from cancelConfiguration.js
	private const CANCEL = [
		"cancel" => [ 'cancel', Notation::UPDIAGONALSTRIKE ],
		"bcancel" => [ 'cancel', Notation::DOWNDIAGONALSTRIKE ],
		"xcancel" => [ 'cancel', Notation::UPDIAGONALSTRIKE . ' ' .
			Notation::DOWNDIAGONALSTRIKE ],
		"cancelto" => [ 'cancelTo', Notation::UPDIAGONALSTRIKE . " " . Notation::UPDIAGONALARROW .
			" " . Notation::NORTHEASTARROW ]
	];
	// They are currently from mhchemConfiguration.js
	private const MHCHEM = [
		"ce" => [ 'machine', 'ce' ],
		"pu" => [ 'machine', 'pu' ],
		"longrightleftharpoons" => [
			'macro',
			'\\stackrel{\\textstyle{-}\\!\\!{\\rightharpoonup}}{\\smash{{\\leftharpoondown}\\!\\!{-}}}'
		],
		"longRightleftharpoons" => [
			'macro',
			'\\stackrel{\\textstyle{-}\\!\\!{\\rightharpoonup}}{\\smash{\\leftharpoondown}}'
		],
		"longLeftrightharpoons" => [
			'macro',
			'\\stackrel{\\textstyle\\vphantom{{-}}{\\rightharpoonup}}{\\smash{{\\leftharpoondown}\\!\\!{-}}}'
		],
		"longleftrightarrows" => [
			'macro',
			'\\stackrel{\\longrightarrow}{\\smash{\\longleftarrow}\\Rule{0px}{.25em}{0px}}'
		],
		"tripledash" => [
			'macro',
			'\\vphantom{-}\\raise{2mu}\\\kern{2mu}\\tiny\\text{-}\\kern{1mu}\\text{-}\\kern{1mu}\\text{-}\\kern{2mu}}'
		],
		"xleftrightarrow" => [ 'xArrow', 0x2194, 6, 6 ],
		"xrightleftharpoons" => [ 'xArrow', 0x21CC, 5, 7 ],
		"xRightleftharpoons" => [ 'xArrow', 0x21CC, 5, 7 ],
		"xLeftrightharpoons" => [ 'xArrow', 0x21CC, 5, 7 ],

		"bond" => [ "chemCustom", "\\bond" ],
	];
	// These are some mappings which are created customly for this
	private const CUSTOM = [
		"boldsymbol" => [ 'boldsymbol', '' ], // see boldsymbolConfiguration.js
		"oint" => [ 'oint', '\u222E', [ "texClass" => TexClass::OP ] ],
		"oiint" => [ 'oint', '\u222F', [ "texClass" => TexClass::OP ] ],
		"oiiint" => [ 'oint', '\u2230', [ "texClass" => TexClass::OP ] ],
		"ointctrclockwise" => [ 'oint', '\u2233', [ "texClass" => TexClass::OP ] ],
		"varointclockwise" => [ 'oint', '\u2232', [ "texClass" => TexClass::OP ] ],
		"\\P" => [ 'oint', '\u00B6', [ "texClass" => TexClass::OP ] ],
		'textvisiblespace' => [ 'Insert', '\u2423' ], // From TextCompMappings.js (only makro it seems)
		"Alpha" => [ 'customLetters', "A" ],
		"Beta" => [ 'customLetters', "B" ],
		"Chi" => [ 'customLetters', "X" ],
		"Epsilon" => [ 'customLetters', "E" ],
		"Eta" => [ 'customLetters', "H" ],
		"Iota" => [ 'customLetters', "I" ],
		"Kappa" => [ 'customLetters', "K" ],
		"Mu" => [ 'customLetters', "M" ],
		"Nu" => [ 'customLetters', "N" ],
		"Omicron" => [ 'customLetters', "O" ],
		"Rho" => [ 'customLetters', "P" ],
		"Tau" => [ 'customLetters', "T" ],
		"Zeta" => [ 'customLetters', "Z" ],
		"ca" => [ "customLetters", "&#x223C;", true ]
	];

	private const ALL = [
		"special" => self::SPECIAL,
		"macros" => self::MACROS,
		"delimiter" => self::DELIMITER,
		"mathchar7" => self::MATCHAR7,
		"mathchar0mi" => self::MATHCHAR0MI,
		"mathchar0mo" => self::MATHCHAR0MO,
		"environment" => self::ENVIRONMENT,
		"environmentMT" => self::ENVIRONMNENTMT,
		"colors" => self::COLORS,
		"cancel" => self::CANCEL,
		"mhchem" => self::MHCHEM,
		"custom" => self::CUSTOM
	];

	private function __construct() {
		// Just an empty private constructor, for singleton pattern
	}

	public static function removeInstance() {
		self::$instance = null;
	}

	public static function getInstance() {
		if ( self::$instance == null ) {
			self::$instance = new BaseMappings();
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
		return MMLutil::getMappingByKey( $key, self::MATHCHAR0MO, true );
	}

	public static function getIdentifierByKey( $key ) {
		return MMLutil::getMappingByKey( $key, self::MATHCHAR0MI, true );
	}

	public static function getMacroByKey( $key ) {
		return MMLutil::getMappingByKeySimple( $key, self::MACROS );
	}

	public static function getMTenvByKey( $key ) {
		return MMLutil::getMappingByKeySimple( $key, self::ENVIRONMNENTMT );
	}

	public static function getSpecialByKey( $key ) {
		$ret = MMLutil::getMappingByKeySimple( $key, self::SPECIAL );
		// Only activated elements get found in this mapping currently.
		if ( is_array( $ret ) && count( $ret ) >= 2 && $ret[1] ) {
			return $ret;
		}
		return null;
	}

	public static function getCancelByKey( $key ) {
		return MMLutil::getMappingByKeySimple( $key, self::CANCEL );
	}

	public static function getCharacterByKey( $key ) {
		return MMLutil::getMappingByKeySimple( $key, self::MATCHAR7 );
	}

	public static function getCustomByKey( $key ) {
		return MMLutil::getMappingByKeySimple( $key, self::CUSTOM );
	}

	public static function getMhChemByKey( $key ) {
		return MMLutil::getMappingByKeySimple( $key, self::MHCHEM );
	}

	public static function getColorByKey( $key ) {
		// Cast to uppercase first letter since mapping is structured that way.
		$key = ucfirst( $key );
		return MMLutil::getMappingByKey( $key, self::COLORS );
	}

	public static function getDelimiterByKey( $key ) {
		return MMLutil::getMappingByKey( $key, self::DELIMITER, true );
	}

	public static function getNullaryMacro( $key ) {
		return MMLutil::getMappingByKey( $key, self::NULLARYMACROS, true );
	}
}
