/*!
 * VisualEditor user interface MWMathDialog class.
 *
 * @copyright See AUTHORS.txt
 * @license MIT
 */

/**
 * Dialog for inserting and editing math formulas.
 *
 * @class
 * @extends ve.ui.MWExtensionPreviewDialog
 *
 * @constructor
 * @param {Object} [config] Configuration options
 */

ve.ui.MWMathDialog = function VeUiMWMathDialog( config ) {
	// Parent constructor
	ve.ui.MWMathDialog.super.call( this, config );

};

/* Inheritance */

OO.inheritClass( ve.ui.MWMathDialog, ve.ui.MWLatexDialog );

/* Static properties */

ve.ui.MWMathDialog.static.name = 'mathDialog';

ve.ui.MWMathDialog.static.title = OO.ui.deferMsg( 'math-visualeditor-mwmathdialog-title' );

ve.ui.MWMathDialog.static.modelClasses = [ ve.dm.MWMathNode ];

ve.ui.MWMathDialog.static.symbolsModule = 'ext.math.visualEditor.mathSymbols';

ve.ui.MWMathDialog.static.autocompleteWordList = [ '\\AA', '\\acute',
	'\\alef', '\\alefsym', '\\aleph', '\\alpha', '\\Alpha', '\\amalg', '\\And',
	'\\angle', '\\approx', '\\approxeq', '\\arccos', '\\arccot',
	'\\arccsc', '\\arcsec', '\\arcsin', '\\arctan', '\\arg', '\\ast',
	'\\asymp', '\\atop', '\\backepsilon', '\\backprime', '\\backsim',
	'\\backsimeq', '\\backslash', '\\bar', '\\barwedge', '\\Bbbk',
	'\\bcancel', '\\because', '\\beta', '\\Beta', '\\beth', '\\between',
	'\\big', '\\Big', '\\bigcap', '\\bigcirc', '\\bigcup', '\\bigg', '\\Bigg',
	'\\biggl', '\\Biggl', '\\biggr', '\\Biggr', '\\bigl', '\\Bigl',
	'\\bigodot', '\\bigoplus', '\\bigotimes', '\\bigr', '\\Bigr', '\\bigsqcup',
	'\\bigstar', '\\bigtriangledown', '\\bigtriangleup', '\\biguplus',
	'\\bigvee', '\\bigwedge', '\\binom', '\\blacklozenge', '\\blacksquare',
	'\\blacktriangle', '\\blacktriangledown', '\\blacktriangleleft',
	'\\blacktriangleright', '\\bmod', '\\boldsymbol', '\\bot',
	'\\bowtie', '\\Box', '\\boxdot', '\\boxminus', '\\boxplus', '\\boxtimes',
	'\\breve', '\\bull', '\\bullet', '\\bumpeq', '\\Bumpeq', '\\cancel',
	'\\cancelto', '\\cap', '\\Cap', '\\cdot', '\\cdots', '\\centerdot',
	'\\cfrac', '\\check', '\\checkmark', '\\chi', '\\Chi', '\\choose',
	'\\circ', '\\circeq', '\\circlearrowleft', '\\circlearrowright',
	'\\circledast', '\\circledcirc', '\\circleddash', '\\circledS', '\\clubs',
	'\\clubsuit', '\\cnums', '\\colon', '\\color', '\\complement', '\\Complex',
	'\\cong', '\\Coppa', '\\coppa', '\\coprod', '\\cos', '\\cosh', '\\cot',
	'\\coth', '\\csc', '\\cup', '\\Cup', '\\curlyeqprec', '\\curlyeqsucc',
	'\\curlyvee', '\\curlywedge', '\\curvearrowleft', '\\curvearrowright',
	'\\dagger', '\\Dagger', '\\daleth', '\\darr', '\\dArr', '\\Darr', '\\dashv',
	'\\dbinom', '\\ddagger', '\\ddot', '\\ddots', '\\definecolor', '\\deg',
	'\\delta', '\\Delta', '\\det', '\\dfrac', '\\diagdown', '\\diagup',
	'\\diamond', '\\Diamond', '\\diamonds', '\\diamondsuit', '\\digamma',
	'\\Digamma', '\\dim', '\\displaystyle', '\\div', '\\divideontimes',
	'\\dot', '\\doteq', '\\Doteq', '\\doteqdot', '\\dotplus', '\\dots', '\\dotsb',
	'\\dotsc', '\\dotsi', '\\dotsm', '\\dotso', '\\doublebarwedge',
	'\\doublecap', '\\doublecup', '\\downarrow', '\\Downarrow',
	'\\downdownarrows', '\\downharpoonleft', '\\downharpoonright', '\\ell',
	'\\emph', '\\empty', '\\emptyset', '\\epsilon', '\\Epsilon', '\\eqcirc',
	'\\eqsim', '\\eqslantgtr', '\\eqslantless', '\\equiv', '\\eta', '\\Eta',
	'\\eth', '\\euro', '\\exist', '\\exists', '\\exp', '\\fallingdotseq',
	'\\Finv', '\\flat', '\\forall', '\\frac', '\\frown', '\\Game', '\\gamma',
	'\\Gamma', '\\gcd', '\\ge', '\\geneuro', '\\geneuronarrow',
	'\\geneurowide', '\\geq', '\\geqq', '\\geqslant', '\\gets', '\\gg', '\\ggg',
	'\\gggtr', '\\gimel', '\\gnapprox', '\\gneq', '\\gneqq', '\\gnsim',
	'\\grave', '\\gtrapprox', '\\gtrdot', '\\gtreqless', '\\gtreqqless',
	'\\gtrless', '\\gtrsim', '\\gvertneqq', '\\hAar', '\\harr', '\\Harr',
	'\\hat', '\\hbar', '\\hearts', '\\heartsuit', '\\hline', '\\hom',
	'\\hookleftarrow', '\\hookrightarrow', '\\hslash', '\\iff', '\\iiiint',
	'\\iiint', '\\iint', '\\Im', '\\image', '\\imath', '\\implies', '\\in',
	'\\inf', '\\infin', '\\infty', '\\injlim', '\\int', '\\intercal',
	'\\iota', '\\Iota', '\\isin', '\\jmath', '\\kappa', '\\Kappa', '\\ker',
	'\\Koppa', '\\koppa', '\\lambda', '\\Lambda', '\\land', '\\lang',
	'\\langle', '\\larr', '\\Larr', '\\lArr', '\\lbrace', '\\lbrack',
	'\\lceil', '\\ldots', '\\le', '\\leftarrow', '\\Leftarrow', '\\leftarrowtail',
	'\\leftharpoondown', '\\leftharpoonup', '\\leftleftarrows',
	'\\leftrightarrow', '\\Leftrightarrow', '\\leftrightarrows',
	'\\leftrightharpoons', '\\leftrightsquigarrow', '\\leftthreetimes', '\\leq',
	'\\leqq', '\\leqslant', '\\lessapprox', '\\lessdot', '\\lesseqgtr',
	'\\lesseqqgtr', '\\lessgtr', '\\lesssim', '\\lfloor', '\\lg', '\\lim',
	'\\liminf', '\\limits', '\\limsup', '\\ll', '\\llcorner', '\\Lleftarrow',
	'\\lll', '\\ln', '\\lnapprox', '\\lneq', '\\lneqq', '\\lnot', '\\lnsim',
	'\\log', '\\longleftarrow', '\\Longleftarrow', '\\longleftrightarrow',
	'\\Longleftrightarrow', '\\longmapsto', '\\longrightarrow',
	'\\Longrightarrow', '\\looparrowleft', '\\looparrowright', '\\lor',
	'\\lozenge', '\\lrarr', '\\Lrarr', '\\lrArr', '\\lrcorner', '\\Lsh',
	'\\ltimes', '\\lVert', '\\lvertneqq', '\\mapsto', '\\mathbb', '\\mathbf',
	'\\mathbin', '\\mathcal', '\\mathclose', '\\mathfrak', '\\mathit', '\\mathop',
	'\\mathopen', '\\mathord', '\\mathpunct', '\\mathrel', '\\mathrm',
	'\\mathsf', '\\mathtt', '\\max', '\\measuredangle', '\\mho', '\\mid',
	'\\min', '\\mod', '\\models', '\\mp', '\\mu', '\\Mu', '\\multimap', '\\N',
	'\\nabla', '\\natnums', '\\natural', '\\ncong', '\\ne', '\\nearrow',
	'\\neg', '\\neq', '\\nexists', '\\ngeq', '\\ngeqq', '\\ngeqslant',
	'\\ngtr', '\\ni', '\\nleftarrow', '\\nLeftarrow', '\\nleftrightarrow',
	'\\nLeftrightarrow', '\\nleq', '\\nleqq', '\\nleqslant', '\\nless',
	'\\nmid', '\\nolimits', '\\not', '\\notin', '\\nparallel', '\\nprec',
	'\\npreceq', '\\nrightarrow', '\\nRightarrow', '\\nshortmid',
	'\\nshortparallel', '\\nsim', '\\nsubseteq', '\\nsubseteqq', '\\nsucc',
	'\\nsucceq', '\\nsupseteq', '\\nsupseteqq', '\\ntriangleleft',
	'\\ntrianglelefteq', '\\ntriangleright', '\\ntrianglerighteq', '\\nu',
	'\\Nu', '\\nvdash', '\\nVdash', '\\nvDash', '\\nVDash', '\\nwarrow', '\\O',
	'\\odot', '\\officialeuro', '\\oint', '\\omega', '\\Omega', '\\omicron',
	'\\Omicron', '\\ominus', '\\operatorname', '\\oplus', '\\oslash',
	'\\otimes', '\\over', '\\overbrace', '\\overleftarrow',
	'\\overleftrightarrow', '\\overline', '\\overrightarrow', '\\overset',
	'\\P', '\\parallel', '\\partial', '\\perp', '\\phi',
	'\\Phi', '\\pi', '\\Pi', '\\pitchfork', '\\plusmn', '\\pm', '\\pmod',
	'\\Pr', '\\prec', '\\precapprox', '\\preccurlyeq', '\\preceq',
	'\\precnapprox', '\\precneqq', '\\precnsim', '\\precsim', '\\prime',
	'\\prod', '\\projlim', '\\propto', '\\psi', '\\Psi', '\\Q', '\\qquad',
	'\\quad', '\\R', '\\rang', '\\rangle', '\\rarr', '\\Rarr', '\\rArr',
	'\\rbrace', '\\rbrack', '\\rceil', '\\Re', '\\real', '\\reals', '\\Reals',
	'\\restriction', '\\rfloor', '\\rho', '\\Rho', '\\rightarrow',
	'\\Rightarrow', '\\rightarrowtail', '\\rightharpoondown', '\\rightharpoonup',
	'\\rightleftarrows', '\\rightleftharpoons', '\\rightrightarrows',
	'\\rightsquigarrow', '\\rightthreetimes', '\\risingdotseq',
	'\\Rrightarrow', '\\Rsh', '\\rtimes', '\\rVert', '\\S', '\\Sampi', '\\sampi',
	'\\scriptscriptstyle', '\\scriptstyle', '\\sdot', '\\searrow', '\\sec',
	'\\sect', '\\sen', '\\setminus', '\\sgn', '\\sharp', '\\shortmid',
	'\\shortparallel', '\\sigma', '\\Sigma', '\\sim', '\\simeq', '\\sin',
	'\\sinh', '\\smallfrown', '\\smallsetminus', '\\smallsmile', '\\smile',
	'\\spades', '\\spadesuit', '\\sphericalangle', '\\sqcap', '\\sqcup',
	'\\sqrt', '\\sqsubset', '\\sqsubseteq', '\\sqsupset', '\\sqsupseteq',
	'\\square', '\\stackrel', '\\star', '\\Stigma', '\\stigma', '\\sub',
	'\\sube', '\\subset', '\\Subset', '\\subseteq', '\\subseteqq',
	'\\subsetneq', '\\subsetneqq', '\\succ', '\\succapprox', '\\succcurlyeq',
	'\\succeq', '\\succnapprox', '\\succneqq', '\\succnsim', '\\succsim',
	'\\sum', '\\sup', '\\supe', '\\supset', '\\Supset', '\\supseteq',
	'\\supseteqq', '\\supsetneq', '\\supsetneqq', '\\surd', '\\swarrow',
	'\\tan', '\\tanh', '\\tau', '\\Tau', '\\tbinom', '\\textbf', '\\textit',
	'\\textrm', '\\textsf', '\\textstyle', '\\texttt', '\\textvisiblespace',
	'\\tfrac', '\\therefore', '\\theta', '\\Theta', '\\thetasym', '\\thickapprox',
	'\\thicksim', '\\tilde', '\\times', '\\to', '\\top', '\\triangle',
	'\\triangledown', '\\triangleleft', '\\trianglelefteq', '\\triangleq',
	'\\triangleright', '\\trianglerighteq', '\\twoheadleftarrow',
	'\\twoheadrightarrow', '\\uarr', '\\uArr', '\\Uarr', '\\ulcorner',
	'\\underbrace', '\\underline', '\\underset', '\\uparrow', '\\Uparrow',
	'\\updownarrow', '\\Updownarrow', '\\upharpoonleft', '\\upharpoonright',
	'\\uplus', '\\upsilon', '\\Upsilon', '\\upuparrows', '\\urcorner',
	'\\varcoppa', '\\varepsilon', '\\varinjlim', '\\varkappa', '\\varliminf',
	'\\varlimsup', '\\varnothing', '\\varphi', '\\varpi', '\\varprojlim',
	'\\varpropto', '\\varrho', '\\varsigma', '\\varstigma', '\\varsubsetneq',
	'\\varsubsetneqq', '\\varsupsetneq', '\\varsupsetneqq', '\\vartheta',
	'\\vartriangle', '\\vartriangleleft', '\\vartriangleright', '\\vdash',
	'\\Vdash', '\\vDash', '\\vdots', '\\vec', '\\vee', '\\veebar', '\\Vert',
	'\\vert', '\\vline', '\\Vvdash', '\\wedge', '\\weierp', '\\widehat',
	'\\widetilde', '\\wp', '\\wr', '\\xcancel', '\\xi', '\\Xi',
	'\\xleftarrow', '\\xrightarrow', '\\Z', '\\zeta', '\\Zeta'
];

/* Registration */

ve.ui.windowFactory.register( ve.ui.MWMathDialog );
