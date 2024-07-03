<?php

namespace MediaWiki\Extension\Math\Tests\WikiTexVC;

use MediaWiki\Extension\Math\WikiTexVC\TexVC;
use MediaWikiUnitTestCase;

/**
 * @covers \MediaWiki\Extension\Math\WikiTexVC\TexVC
 * @covers \MediaWiki\Extension\Math\WikiTexVC\Parser
 * @covers \MediaWiki\Extension\Math\WikiTexVC\TexUtil
 * @covers \MediaWiki\Extension\Math\WikiTexVC\ParserUtil
 */
class AllTest extends MediaWikiUnitTestCase {
	private $testCases;
	private $texVC;
	private $DELIMITERS1;
	private $DELIMITERS2;
	private $DELIMITERS3;

	protected function setUp(): void {
		parent::setUp();
		$this->texVC = new TexVC();
	}

	public static function provideTestCases() {
		$DELIMITERS1 = [ '(', ')', '[', ']', '\\{', '\\}', '|' ];
		$delim2 = '\\backslash\\downarrow\\Downarrow\\langle\\lbrace\\lceil\\lfloor' .
			'\\llcorner\\lrcorner\\rangle\\rbrace\\rceil\\rfloor\\rightleftharpoons' .
			'\\twoheadleftarrow\\twoheadrightarrow\\ulcorner\\uparrow\\Uparrow' .
			'\\updownarrow\\Updownarrow\\urcorner\\Vert\\vert\\lbrack\\rbrack';
		$DELIMITERS2 = array_map( static function ( $f ) {
			return '\\' . $f;
		}, explode( '\\', $delim2 ) );
		array_shift( $DELIMITERS2 );

		$delim3 = '\\darr\\dArr\\Darr\\lang\\rang\\uarr\\uArr\\Uarr';
		$DELIMITERS3 = array_map( static function ( $f ) {
			return '\\' . $f;
		}, explode( '\\', $delim3 ) );
		array_shift( $DELIMITERS3 );

		return [
			'Box functions' => [
				[
					"input" =>
						"\\text {-0-9a-zA-Z+*,=():/;?.!'` \u{0080}-\u{00FF}} " .
						"\\mbox {-0-9a-zA-Z+*,=():/;?.!'` \u{0080}-\u{00FF}} " .
						"\\hbox {-0-9a-zA-Z+*,=():/;?.!'` \u{0080}-\u{00FF}} " .
						"\\vbox {-0-9a-zA-Z+*,=():/;?.!'` \u{0080}-\u{00FF}} ",
					"output" =>
						"{\\text{-0-9a-zA-Z+*,=():/;?.!'` \u{0080}-\u{00FF}}}" .
						"{\\mbox{-0-9a-zA-Z+*,=():/;?.!'` \u{0080}-\u{00FF}}}" .
						"{\\hbox{-0-9a-zA-Z+*,=():/;?.!'` \u{0080}-\u{00FF}}}" .
						"{\\vbox{-0-9a-zA-Z+*,=():/;?.!'` \u{0080}-\u{00FF}}}"
				]
			],
			'Box functions (2)' => [
				[
					'input' => '{\\text{ABC}}{\\mbox{ABC}}{\\hbox{ABC}}{\\vbox{ABC}}',
					'skipOcaml' => true /* extra braces in ocaml version */
				]
			],
			'LaTeX functions' => [
				[
					'input' =>
						'\\arccos \\arcsin \\arctan \\arg \\cosh \\cos \\cot \\coth ' .
						'\\csc \\deg \\det \\dim \\exp \\gcd \\hom \\inf \\ker \\lg ' .
						'\\lim \\liminf \\limsup \\ln \\log \\max \\min \\Pr \\sec ' .
						'\\sin \\sinh \\sup \\tan \\tanh '
				]
			],
			'MediaWiki functions' => [
				[
					'input' => '\\arccot\\arcsec\\arccsc\\sgn\\sen',
					'output' =>
						'\\operatorname {arccot} \\operatorname {arcsec} ' .
						'\\operatorname {arccsc} \\operatorname {sgn} ' .
						'\\operatorname {sen} '
				]
			],
			'Literals (1)' => [
				[
					'input' =>
						'\\aleph \\alpha \\amalg \\And \\angle \\approx ' .
						'\\approxeq \\ast \\asymp \\backepsilon \\backprime ' .
						'\\backsim \\backsimeq \\barwedge \\Bbbk \\because \\beta ' .
						'\\beth \\between \\bigcap \\bigcirc \\bigcup \\bigodot ' .
						'\\bigoplus \\bigotimes \\bigsqcup \\bigstar ' .
						'\\bigtriangledown \\bigtriangleup \\biguplus \\bigvee ' .
						'\\bigwedge \\blacklozenge \\blacksquare \\blacktriangle ' .
						'\\blacktriangledown \\blacktriangleleft \\blacktriangleright ' .
						'\\bot \\bowtie \\Box \\boxdot \\boxminus \\boxplus ' .
						'\\boxtimes \\bullet \\bumpeq \\Bumpeq \\cap \\Cap \\cdot ' .
						'\\cdots \\centerdot \\checkmark \\chi \\circ \\circeq ' .
						'\\circlearrowleft \\circlearrowright \\circledast ' .
						'\\circledcirc \\circleddash \\circledS \\clubsuit \\colon ' .
						'\\complement \\cong \\coprod \\cup \\Cup ' .
						'\\curlyeqprec \\curlyeqsucc \\curlyvee \\curlywedge ' .
						'\\curvearrowleft \\curvearrowright \\dagger \\daleth ' .
						'\\dashv \\ddagger \\ddots \\delta \\Delta ' .
						'\\diagdown \\diagup \\diamond \\Diamond \\diamondsuit ' .
						'\\digamma \\displaystyle \\div \\divideontimes \\doteq ' .
						'\\doteqdot \\dotplus \\dots \\dotsb \\dotsc \\dotsi \\dotsm ' .
						'\\dotso \\doublebarwedge \\downdownarrows \\downharpoonleft ' .
						'\\downharpoonright \\ell \\emptyset \\epsilon \\eqcirc ' .
						'\\eqsim \\eqslantgtr \\eqslantless \\equiv \\eta \\eth ' .
						'\\exists \\fallingdotseq \\Finv \\flat \\forall \\frown ' .
						'\\Game \\gamma \\Gamma \\geq \\geqq \\geqslant \\gets \\gg ' .
						'\\ggg \\gimel \\gnapprox \\gneq \\gneqq \\gnsim \\gtrapprox ' .
						'\\gtrdot \\gtreqless \\gtreqqless \\gtrless \\gtrsim ' .
						'\\gvertneqq \\hbar \\heartsuit \\hookleftarrow ' .
						'\\hookrightarrow \\hslash \\iff \\iiiint \\iiint \\iint ' .
						'\\Im \\imath \\implies \\in \\infty \\injlim \\int ' .
						'\\intercal \\iota \\jmath \\kappa \\lambda \\Lambda \\land ' .
						'\\ldots \\leftarrow \\Leftarrow \\leftarrowtail ' .
						'\\leftharpoondown \\leftharpoonup \\leftleftarrows ' .
						'\\leftrightarrow \\Leftrightarrow \\leftrightarrows ' .
						'\\leftrightharpoons \\leftrightsquigarrow \\leftthreetimes ' .
						'\\leq \\leqq \\leqslant \\lessapprox \\lessdot ' .
						'\\lesseqgtr \\lesseqqgtr \\lessgtr \\lesssim \\limits \\ll ' .
						'\\Lleftarrow \\lll \\lnapprox \\lneq \\lneqq \\lnot \\lnsim ' .
						'\\longleftarrow \\Longleftarrow \\longleftrightarrow ' .
						'\\Longleftrightarrow \\longmapsto \\longrightarrow ' .
						'\\Longrightarrow \\looparrowleft \\looparrowright \\lor ' .
						'\\lozenge \\Lsh \\ltimes \\lVert \\lvertneqq \\mapsto ' .
						'\\measuredangle \\mho \\mid \\mod \\models \\mp \\mu ' .
						'\\multimap \\nabla \\natural \\ncong \\nearrow \\neg \\neq ' .
						'\\nexists \\ngeq \\ngeqq \\ngeqslant \\ngtr \\ni ' .
						'\\nleftarrow \\nLeftarrow \\nleftrightarrow ' .
						'\\nLeftrightarrow \\nleq \\nleqq \\nleqslant \\nless \\nmid ' .
						'\\nolimits \\not \\notin \\nparallel \\nprec \\npreceq ' .
						'\\nrightarrow \\nRightarrow \\nshortmid \\nshortparallel ' .
						'\\nsim \\nsubseteq \\nsubseteqq \\nsucc \\nsucceq ' .
						'\\nsupseteq \\nsupseteqq \\ntriangleleft \\ntrianglelefteq ' .
						'\\ntriangleright \\ntrianglerighteq \\nu \\nvdash \\nVdash ' .
						'\\nvDash \\nVDash \\nwarrow \\odot \\oint \\omega \\Omega ' .
						'\\ominus \\oplus \\oslash \\otimes ' .
						'\\P \\parallel \\partial ' .
						'\\perp \\phi \\Phi \\pi \\Pi \\pitchfork \\pm \\prec ' .
						'\\precapprox \\preccurlyeq \\preceq \\precnapprox ' .
						'\\precneqq \\precnsim \\precsim \\prime \\prod \\projlim ' .
						'\\propto \\psi \\Psi \\qquad \\quad \\Re \\rho \\rightarrow ' .
						'\\Rightarrow \\rightarrowtail \\rightharpoondown ' .
						'\\rightharpoonup \\rightleftarrows \\rightrightarrows ' .
						'\\rightsquigarrow \\rightthreetimes \\risingdotseq ' .
						'\\Rrightarrow \\Rsh \\rtimes \\rVert \\S ' .
						'\\scriptscriptstyle \\scriptstyle \\searrow \\setminus ' .
						'\\sharp \\shortmid \\shortparallel \\sigma \\Sigma \\sim ' .
						'\\simeq \\smallfrown \\smallsetminus \\smallsmile \\smile ' .
						'\\spadesuit \\sphericalangle \\sqcap \\sqcup \\sqsubset ' .
						'\\sqsubseteq \\sqsupset \\sqsupseteq \\square \\star ' .
						'\\subset \\Subset \\subseteq \\subseteqq \\subsetneq ' .
						'\\subsetneqq \\succ \\succapprox \\succcurlyeq \\succeq ' .
						'\\succnapprox \\succneqq \\succnsim \\succsim \\sum ' .
						'\\supset \\Supset \\supseteq \\supseteqq \\supsetneq ' .
						'\\supsetneqq \\surd \\swarrow \\tau \\textstyle ' .
						'\\therefore \\theta \\Theta ' .
						'\\thickapprox \\thicksim \\times \\to \\top \\triangle ' .
						'\\triangledown \\triangleleft \\trianglelefteq \\triangleq ' .
						'\\triangleright \\trianglerighteq ' .
						'\\upharpoonleft \\upharpoonright \\uplus \\upsilon ' .
						'\\Upsilon \\upuparrows \\varDelta \\varepsilon \\varGamma ' .
						'\\varinjlim \\varkappa \\varLambda \\varliminf \\varlimsup ' .
						'\\varnothing \\varOmega \\varphi \\varPhi \\varpi \\varPhi ' .
						'\\varprojlim \\varpropto \\varrho \\varsigma \\varSigma ' .
						'\\varsubsetneq \\varsubsetneqq \\varsupsetneq ' .
						'\\varsupsetneqq \\vartheta \\varTheta \\vartriangle ' .
						'\\vartriangleleft \\vartriangleright \\varUpsilon \\varXi ' .
						'\\vdash \\Vdash \\vDash \\vdots \\vee ' .
						'\\veebar \\vline \\Vvdash \\wedge ' .
						'\\wp \\wr \\xi \\Xi \\zeta '
				]
			],
			'Literals (2)' => [
				[
					'input' =>
						'\\AA\\Coppa\\coppa\\Digamma\\euro\\geneuro\\geneuronarrow' .
						'\\geneurowide\\Koppa\\koppa\\officialeuro\\Sampi\\sampi' .
						'\\Stigma\\stigma\\textvisiblespace\\varstigma',
					'output' =>
						'\\mbox{\\AA} \\mbox{\\Coppa} \\mbox{\\coppa} ' .
						'\\mbox{\\Digamma} \\mbox{\\euro} \\mbox{\\geneuro} ' .
						'\\mbox{\\geneuronarrow} \\mbox{\\geneurowide} ' .
						'\\mbox{\\Koppa} \\mbox{\\koppa} \\mbox{\\officialeuro} ' .
						'\\mbox{\\Sampi} \\mbox{\\sampi} \\mbox{\\Stigma} ' .
						'\\mbox{\\stigma} \\mbox{\\textvisiblespace} ' .
						'\\mbox{\\varstigma} '
				]
			],
			'Literals (2\')' => [
				[
					/* We can parse what we emit (but the ocaml version can't) */
					'input' =>
						'\\mbox{\\AA} \\mbox{\\Coppa} \\mbox{\\coppa} ' .
						'\\mbox{\\Digamma} \\mbox{\\euro} \\mbox{\\geneuro} ' .
						'\\mbox{\\geneuronarrow} \\mbox{\\geneurowide} ' .
						'\\mbox{\\Koppa} \\mbox{\\koppa} \\mbox{\\officialeuro} ' .
						'\\mbox{\\Sampi} \\mbox{\\sampi} \\mbox{\\Stigma} ' .
						'\\mbox{\\stigma} \\mbox{\\textvisiblespace} ' .
						'\\mbox{\\varstigma} ',
					'skipOcaml' => true
				]
			],
			'Literals (2) MJ' => [
				[
					'usemathrm' => true,
					'input' =>
						'\\AA\\Coppa\\coppa\\Digamma\\euro\\geneuro\\geneuronarrow' .
						'\\geneurowide\\Koppa\\koppa\\officialeuro\\Sampi\\sampi' .
						'\\Stigma\\stigma\\textvisiblespace\\varstigma',
					'output' =>
						'\\mathrm {\\AA} \\mathrm {\\Coppa} \\mathrm {\\coppa} ' .
						'\\mathrm {\\Digamma} \\mathrm {\\euro} \\mathrm {\\geneuro} ' .
						'\\mathrm {\\geneuronarrow} \\mathrm {\\geneurowide} ' .
						'\\mathrm {\\Koppa} \\mathrm {\\koppa} \\mathrm {\\officialeuro} ' .
						'\\mathrm {\\Sampi} \\mathrm {\\sampi} \\mathrm {\\Stigma} ' .
						'\\mathrm {\\stigma} \\mathrm {\\textvisiblespace} ' .
						'\\mathrm {\\varstigma} '
				]
			],
			'Literals (2\') MJ' => [
				[
					'usemathrm' => true,
					/* We can parse what we emit (but the ocaml version can't) */
					'input' =>
						'\\mathrm {\\AA} \\mathrm {\\Coppa} \\mathrm {\\coppa} ' .
						'\\mathrm {\\Digamma} \\mathrm {\\euro} \\mathrm {\\geneuro} ' .
						'\\mathrm {\\geneuronarrow} \\mathrm {\\geneurowide} ' .
						'\\mathrm {\\Koppa} \\mathrm {\\koppa} \\mathrm {\\officialeuro} ' .
						'\\mathrm {\\Sampi} \\mathrm {\\sampi} \\mathrm {\\Stigma} ' .
						'\\mathrm {\\stigma} \\mathrm {\\textvisiblespace} ' .
						'\\mathrm {\\varstigma} ',
					'skipOcaml' => true
				]
			],
			'Literals (3)' => [
				[
					'oldtexvc' => true,
					'input' =>
						'\\C\\H\\N\\Q\\R\\Z\\alef\\alefsym\\Alpha\\and\\ang\\Beta' .
						'\\bull\\Chi\\clubs\\cnums\\Complex\\Dagger\\diamonds\\Doteq' .
						'\\doublecap\\doublecup\\empty\\Epsilon\\Eta\\exist\\ge' .
						'\\gggtr\\hArr\\harr\\Harr\\hearts\\image\\infin\\Iota\\isin' .
						'\\Kappa\\larr\\Larr\\lArr\\le\\lrarr\\Lrarr\\lrArr\\Mu' .
						'\\natnums\\ne\\Nu\\O\\omicron\\Omicron\\or\\part\\plusmn' .
						'\\rarr\\Rarr\\rArr\\real\\reals\\Reals\\restriction\\Rho' .
						'\\sdot\\sect\\spades\\sub\\sube\\supe\\Tau\\thetasym' .
						'\\varcoppa\\weierp\\Zeta',
					'output' =>
						'\\mathbb {C} \\mathbb {H} \\mathbb {N} \\mathbb {Q} ' .
						'\\mathbb {R} \\mathbb {Z} \\aleph \\aleph \\mathrm {A} ' .
						'\\land \\angle \\mathrm {B} \\bullet \\mathrm {X} ' .
						'\\clubsuit \\mathbb {C} \\mathbb {C} \\ddagger ' .
						'\\diamondsuit \\doteqdot \\Cap \\Cup \\emptyset ' .
						'\\mathrm {E} \\mathrm {H} \\exists \\geq \\ggg ' .
						'\\Leftrightarrow \\leftrightarrow \\Leftrightarrow ' .
						'\\heartsuit \\Im \\infty \\mathrm {I} \\in \\mathrm {K} ' .
						'\\leftarrow \\Leftarrow \\Leftarrow \\leq ' .
						'\\leftrightarrow \\Leftrightarrow \\Leftrightarrow ' .
						'\\mathrm {M} \\mathbb {N} \\neq \\mathrm {N} \\emptyset ' .
						'oO\\lor \\partial \\pm ' .
						'\\rightarrow \\Rightarrow \\Rightarrow \\Re \\mathbb {R} ' .
						'\\mathbb {R} \\upharpoonright \\mathrm {P} \\cdot ' .
						'\\S \\spadesuit \\subset \\subseteq \\supseteq ' .
						'\\mathrm {T} \\vartheta \\mbox{\\coppa} \\wp \\mathrm {Z} '
				]
			],
			'Big' => [
				self::bigs( $DELIMITERS1, $DELIMITERS2 ),
			],
			'Delimiters (1)' => [
				[
					'input' => implode( '', $DELIMITERS1 ) . implode( ' ', $DELIMITERS2 ) . ' '
				],
			],
			'Delimiters (2)' => [
				[
					'input' =>
						'\\darr\\dArr\\Darr\\lang\\rang\\uarr\\uArr\\Uarr',
					'output' =>
						'\\downarrow \\Downarrow \\Downarrow \\langle \\rangle ' .
						'\\uparrow \\Uparrow \\Uparrow '
				]
			],
			'Delimiters (3)' => [
				[
					'input' =>
						'\\left' . implode( '\\left', $DELIMITERS1 ) .
						'\\right' . implode( '\\right', array_reverse( $DELIMITERS1 ) )
				]
			],
			'Delimiters (4)' => [
				[
					'input' =>
						'\\left' . implode( ' \\left', $DELIMITERS2 ) .
						' \\right' . implode( ' \\right', array_reverse( $DELIMITERS2 ) ) . ' '
				]
			],
			'Delimiters (5)' => [
				[
					'input' =>
						'\\left\\darr \\left\\dArr \\left\\Darr \\left\\lang ' .
						'\\right\\rang \\right\\uarr \\right\\uArr \\right\\Uarr ',
					'output' =>
						'\\left\\downarrow \\left\\Downarrow \\left\\Downarrow ' .
						'\\left\\langle \\right\\rangle ' .
						'\\right\\uparrow \\right\\Uparrow \\right\\Uparrow '
				]
			],
			'FUN_AR1' => [
				[
					'input' =>
						'\\acute{A}\\bar{A}\\bcancel{A}\\bmod{A}\\boldsymbol{A}' .
						'\\breve{A}\\cancel{A}\\check{A}\\ddot{A}\\dot{A}\\emph{A}' .
						'\\grave{A}\\hat{A}\\hphantom{A}\\mathbb{A}\\mathbf{A}' .
						'\\mathcal{A}\\mathclose{A}\\mathfrak{A}\\mathit{A}' .
						'\\mathop{A}\\mathopen{A}\\mathord{A}\\mathpunct{A}' .
						'\\mathrm{A}\\mathsf{A}\\mathtt{A}' .
						'\\operatorname{A}\\overleftarrow{A}\\overleftrightarrow{A}' .
						'\\overline{A}\\overrightarrow{A}\\phantom{A}\\pmod{A}\\sqrt{A}' .
						'\\textbf{A}\\textit{A}\\textrm{A}\\textsf{A}\\texttt{A}' .
						'\\tilde{A}\\underline{A}\\vec{A}\\vphantom{A}\\widehat{A}' .
						'\\widetilde{A}\\xcancel{A}',
					'output' =>
						'{\\acute {A}}{\\bar {A}}{\\bcancel {A}}{\\bmod {A}}' .
						'{\\boldsymbol {A}}{\\breve {A}}{\\cancel {A}}{\\check {A}}' .
						'{\\ddot {A}}{\\dot {A}}{\\emph {A}}{\\grave {A}}{\\hat {A}}' .
						'{\\hphantom {A}}\\mathbb {A} \\mathbf {A} {\\mathcal {A}}' .
						'{\\mathclose {A}}{\\mathfrak {A}}{\\mathit {A}}' .
						'\\mathop {A} {\\mathopen {A}}{\\mathord {A}}' .
						'{\\mathpunct {A}}\\mathrm {A} {\\mathsf {A}}' .
						'{\\mathtt {A}}\\operatorname {A} {\\overleftarrow {A}}' .
						'{\\overleftrightarrow {A}}{\\overline {A}}' .
						'{\\overrightarrow {A}}{\\phantom {A}}{\\pmod {A}}{\\sqrt {A}}' .
						'{\\textbf {A}}{\\textit {A}}{\\textrm {A}}{\\textsf {A}}' .
						'{\\texttt {A}}{\\tilde {A}}{\\underline {A}}{\\vec {A}}' .
						'{\\vphantom {A}}{\\widehat {A}}{\\widetilde {A}}{\\xcancel {A}}',
					'skipOcaml' => 'double spacing and extra braces',
				]
			],
			'FUN_AR1NB (1)' => [
				[
					'input' => '\\operatorname {sin} ',
					'skipOcaml' => 'missing space'
				]
			],
			'FUN_AR1NB (2)' => [
				[
					'input' => '\\mathbb {A} \\mathbf {B} \\mathrm {C} ',
					'skipOcaml' => 'extra braces'
				]
			],
			'FUN_AR1NB (3)' => [
				[
					'input' => '\\overbrace {A} _{b}^{c}\\underbrace {C} _{d}^{e}',
					'skipOcaml' => 'ocaml bug'
				]
			],
			'FUN_AR1NB (4)' => [
				[
					'input' => '\\xleftarrow{A}\\xrightarrow{A}',
					'output' => '\\xleftarrow {A} \\xrightarrow {A} '
				]
			],
			'FUN_AR1NB (5)' => [
				[
					'input' => '\\mathrel{A}\\mathbin{A}',
					'output' => '\\mathrel {A} \\mathbin {A} '
				]
			],
			'FUN_AR1OPT' => [
				[
					'input' =>
						'\\sqrt{2}\\sqrt[3]{2}' .
						'\\xleftarrow{above}\\xleftarrow[below]{above}' .
						'\\xrightarrow{above}\\xrightarrow[below]{above}',
					'output' =>
						'{\\sqrt {2}}{\\sqrt[{3}]{2}}' .
						'\\xleftarrow {above} {\\xleftarrow[{below}]{above}}' .
						'\\xrightarrow {above} {\\xrightarrow[{below}]{above}}',
					'skipOcaml' => 'spacing'
				]
			],
			'FUN_AR2' => [
				[
					'input' =>
						'\\binom{A}{B}\\cancelto{A}{B}\\cfrac{A}{B}\\dbinom{A}{B}' .
						'\\dfrac{A}{B}\\frac{A}{B}\\overset{A}{B}\\stackrel{A}{B}' .
						'\\tbinom{A}{B}\\tfrac{A}{B}\\underset{A}{B}',
					'output' =>
						'{\\binom {A}{B}}{\\cancelto {A}{B}}{\\cfrac {A}{B}}' .
						'{\\dbinom {A}{B}}{\\dfrac {A}{B}}{\\frac {A}{B}}' .
						'{\\overset {A}{B}}{\\stackrel {A}{B}}{\\tbinom {A}{B}}' .
						'{\\tfrac {A}{B}}{\\underset {A}{B}}',
					'skipOcaml' => 'double spacing'
				]
			],
			'FUN_AR2nb' => [
				[
					'input' => '\\sideset{_\\dagger^*}{_\\dagger^*}\\prod',
					'output' => '\\sideset {_{\\dagger }^{*}}{_{\\dagger }^{*}}\\prod '
				]
			],
			'FUN_INFIX (1)' => [
				[
					'input' => '\\left({a\\atop 1}{b\\atop m}{c\\atop n}\\right)',
					'output' => '\\left({a \\atop 1}{b \\atop m}{c \\atop n}\\right)'
				]
			],
			'FUN_INFIX (2)' => [
				[
					'input' => '{1\\,0\\choose0\\,1}',
					'output' => '{1\\,0 \\choose 0\\,1}'
				]
			],
			'FUN_INFIX (3)' => [
				[
					'input' => '{a\\over b}',
					'output' => '{a \\over b}'
				]
			],
			'FUN_INFIX (4)' => [
				[
					'input' => 'a\\over b',
					'output' => '{a \\over b}'
				]
			],
			'DECLh' => [ [
				'input' => '{abc \\rm def \\it ghi \\cal jkl \\bf mno}',
				'output' => '{abc{\\rm {def{\\it {ghi{\\cal {jkl{\\bf {mno}}}}}}}}}'
				]
			],
			'litsq_zq' => [ [
				'input' => ']^2',
				'output' => ']^{2}'
				]
			],
			'Matrices' => [
				self::matrices(),
			],
			'Matrices (2)' => [
				[
					'input' => '{\\begin{array}{|c|}\\hline {\\!n\\!}\\\\\\hline \\end{array}}'
				]
			],
			'Matrices (3)' => [
				[
					'input' => '\\begin{alignedat} { 3 } a & b & c \\end{alignedat}',
					'output' => '{\\begin{alignedat}{3}a&b&c\\end{alignedat}}'
				]
			],
			'Color (1)' => [
				[
					'input' => '\\definecolor {mycolor}{rgb}{0.1,.2,0.}\\color {mycolor}'
				]
			],
			'Color (2)' => [
				[
					'input' =>
						'\\color {blue}\\color [named]{blue}\\color [gray]{0.5}' .
						'\\color [rgb]{0,1,0}\\color [cmyk]{1,0,0,0}'
				]
			],
			'Color (3)' => [
				[
					'input' =>
						'\\pagecolor {blue}\\pagecolor [named]{blue}' .
						'\\pagecolor [gray]{0.5}\\pagecolor [rgb]{0,1,0}' .
						'\\pagecolor [cmyk]{1,0,0,0}'
				]
			],
			'Color (4)' => [
				[
					'input' =>
						'\\definecolor{mycolor}{rgb}{0.1,.2,0.}\\color[CMYK]{0,1,0,1}',
					'output' =>
						'\\definecolor {mycolor}{rgb}{0.1,.2,0.}\\color [cmyk]{0,1,0,1}'
				]
			],
			'Color (5)' => [
				[
					'input' =>
						'\\definecolor{mycolor}{RGB}{255,102,51}' .
						'\\pagecolor [RGB]{51,102,255}',
					'output' =>
						'\\definecolor {mycolor}{rgb}{1,0.4,0.2}' .
						'\\pagecolor [rgb]{0.2,0.4,1}'
				]
			]
		];
	}

	private static function bigs( $DELIMITERS1, $DELIMITERS2 ) {
		$bigs = explode( '\\', '\\big\\Big\\bigg\\Bigg\\biggl\\Biggl\\biggr\\Biggr' .
			'\\bigl\\Bigl\\bigr\\Bigr' );
		array_shift( $bigs );
		$BIGS = $bigs;

		$DELIMITERS = array_merge( $DELIMITERS1, $DELIMITERS2, [ '\\darr', '\\uarr' ] );

		$input = implode( '', array_map( static function ( $b ) use ( $DELIMITERS ) {
			return implode( '', array_map( static function ( $d ) use ( $DELIMITERS, $b )  {
				return '\\' . $b . $d;
			}, $DELIMITERS ) );
		}, $BIGS ) );
		$output = implode( '', array_map( static function ( $b ) use ( $DELIMITERS ) {
			return implode( '', array_map( static function ( $d ) use ( $DELIMITERS, $b )  {
				if ( $d === '\\darr' ) {
					$d = '\\downarrow';
				}
				if ( $d === '\\uarr' ) {
					$d = '\\uparrow';
				}
				if ( substr( $d, 0, 1 ) === '\\' && strlen( $d ) > 2 ) {
					$d = $d . ' ';
				}
				return '{\\' . $b . ' ' . $d . '}';
			}, $DELIMITERS ) );
		}, $BIGS ) );

		return [ 'input' => $input, 'output' => $output ];
	}

	private static function argi( $env ) {
		switch ( $env ) {
			case 'array':
				return '{|c||c|}';
			case 'alignedat':
			case 'alignat':
				return '{3}';
			default:
				return '';
		}
	}

	private static function matrices() {
		$ENV = [ 'matrix', 'pmatrix', 'bmatrix', 'Bmatrix', 'vmatrix', 'Vmatrix',
			'array', 'align', 'alignat', 'smallmatrix', 'cases' ];

		$input = implode( '', array_map( static function ( $env ) {
			return '\\begin{' . $env . '}' . AllTest::argi( $env ) . ' a & b \\\\\\hline c & d \\end{' . $env . '}';
		}, $ENV ) );
		$output = implode( '', array_map( static function ( $env ) {
			if ( $env === 'align' ) {
				$env = 'aligned';
			}
			if ( $env === 'alignat' ) {
				$env = 'alignedat';
			}
			return '{\\begin{' . $env . '}' . AllTest::argi( $env ) . 'a&b\\\\\\hline c&d\\end{' . $env . '}}';
		}, $ENV ) );

		return [ 'input' => $input, 'output' => $output ];
	}

	/**
	 * @dataProvider provideTestCases
	 */
	public function testRunCases( $tc ) {
		$tc['output'] = $tc['output'] ?? $tc['input'];
		if ( !array_key_exists( 'skipJs', $tc ) || !$tc['skipJs'] ) {
			$message = 'output should be correct';
			$result = $this->texVC->check( $tc['input'], [
				'debug' => true,
				'usemathrm' => $tc['usemathrm'] ?? false,
				'oldtexvc' => $tc['oldtexvc'] ?? false
			] );
			$this->assertEquals( '+', $result['status'], $message );
			$this->assertEquals( $result['output'], $tc['output'], $message );
		}
		if ( !array_key_exists( 'skipReparse', $tc ) || !$tc['skipReparse'] ) {
			// verify that the output doesn't change if we feed it
			// through again.
			$message = 'should parse its own output';
			$result1 = $this->texVC->check( $tc['output'], [ 'debug' => true ] );
			$result2 = $this->texVC->check( $result1['output'], [ 'debug' => true ] );
			$this->assertEquals( '+', $result2['status'], $message );
			$this->assertEquals( $result2['output'], $result1['output'], $message );
		}
	}
}
