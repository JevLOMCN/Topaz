<?php
/*
 * DO NOT EDIT MANUALLY.
 * File generated from Grammar.pegphp with `composer regen-tokenizer`.
 */


namespace Wikimedia\Parsoid\Wt2Html;


	use Wikimedia\Assert\UnreachableException;
	use Wikimedia\Parsoid\Config\Env;
	use Wikimedia\Parsoid\Config\SiteConfig;
	use Wikimedia\Parsoid\Core\DomSourceRange;
	use Wikimedia\Parsoid\NodeData\DataParsoid;
	use Wikimedia\Parsoid\Tokens\CommentTk;
	use Wikimedia\Parsoid\Tokens\EndTagTk;
	use Wikimedia\Parsoid\Tokens\EOFTk;
	use Wikimedia\Parsoid\Tokens\KV;
	use Wikimedia\Parsoid\Tokens\KVSourceRange;
	use Wikimedia\Parsoid\Tokens\NlTk;
	use Wikimedia\Parsoid\Tokens\SelfclosingTagTk;
	use Wikimedia\Parsoid\Tokens\SourceRange;
	use Wikimedia\Parsoid\Tokens\TagTk;
	use Wikimedia\Parsoid\Tokens\Token;
	use Wikimedia\Parsoid\Utils\TokenUtils;
	use Wikimedia\Parsoid\Utils\Utils;
	use Wikimedia\Parsoid\Utils\PHPUtils;
	use Wikimedia\Parsoid\Utils\WTUtils;
	use Wikimedia\Parsoid\Wikitext\Consts;



class GrammarCacheEntry {
	public $nextPos;
	public $result;
	public $preproc;
	public $th;


	public function __construct( $nextPos, $result, $preproc, $th ) {
		$this->nextPos = $nextPos;
		$this->result = $result;
		$this->preproc = $preproc;
		$this->th = $th;

	}
}


class Grammar extends \Wikimedia\WikiPEG\PEGParserBase {
	// initializer
	
	/** @var Env */
	private $env;

	/** @var SiteConfig */
	private $siteConfig;

	/** @var array */
	private $pipelineOpts;

	/** @var int */
	private $pipelineOffset;

	private $extTags;

	private $startTime;

	/** @var string */
	private $reUrltextLookahead;

	/** @var string */
	private $urltextPlainSegment = '';

	/** @var bool */
	private $urltextFoundAutolink = false;

	protected function initialize() {
		$this->env = $this->options['env'];
		$this->siteConfig = $this->env->getSiteConfig();

		$tokenizer = $this->options['pegTokenizer'];
		$this->pipelineOpts = $tokenizer->getOptions();
		// FIXME: inTemplate option may not always be set in
		// standalone tokenizers user by some pipelines handlers.
		$this->pipelineOffset = $this->options['pipelineOffset'] ?? 0;
		$this->extTags = $this->siteConfig->getExtensionTagNameMap();

		// Non-greedy text_char sequence: stop at ampersand, double-underscore,
		 // magic link prefix or protocol
		$this->reUrltextLookahead = '!(?:' .
			'([^-\'<[{\n\r:;\]}|\!=&]*?)' .
			'(?:__|$|[-\'<[{\n\r:;\]}|\!=&]|(RFC|PMID|ISBN|' .
			'(?i)' . $this->siteConfig->getProtocolsRegex( true ) .
			')))!A';
	}

	private $prevOffset = 0;
	private $headingIndex = 0;

	private function assert( $condition, $text ) {
		if ( !$condition ) {
			throw new \RuntimeException( "Grammar.pegphp assertion failure: $text" );
		}
	}

	private function unreachable() {
		throw new UnreachableException( "Grammar.pegphp: this should be unreachable" );
	}

	// Some shorthands for legibility
	private function startOffset() {
		return $this->savedPos;
	}

	private function endOffset() {
		return $this->currPos;
	}

	private function tsrOffsets( $flag = 'default' ): SourceRange {
		switch ( $flag ) {
			case 'start':
				return new SourceRange( $this->savedPos, $this->savedPos );
			case 'end':
				return new SourceRange( $this->currPos, $this->currPos );
			default:
				return new SourceRange( $this->savedPos, $this->currPos );
		}
	}

	/*
	 * Emit a chunk of tokens to our consumers.  Once this has been done, the
	 * current expression can return an empty list (true).
	 */
	private function emitChunk( $tokens ) {
		// FIXME: We don't expect nulls here, but looks like
		// hack from I1c695ab6cdd3655e98877c175ddbabdee9dc44b7
		// introduces them. Work around it for now!
		if ( !$tokens ) {
			return [];
		}

		// Shift tsr of all tokens by the pipeline offset
		TokenUtils::shiftTokenTSR( $tokens, $this->pipelineOffset );
		$this->env->log( 'trace/peg', $this->options['pipelineId'] ?? '0', '---->   ', $tokens );

		$i = null;
		$n = count( $tokens );

		// Enforce parsing resource limits
		for ( $i = 0;  $i < $n;  $i++ ) {
			TokenizerUtils::enforceParserResourceLimits( $this->env, $tokens[ $i ] );
		}

		return $tokens;
	}

	/* ------------------------------------------------------------------------
	 * Extension tags should be parsed with higher priority than anything else.
	 *
	 * The trick we use is to strip out the content inside a matching tag-pair
	 * and not tokenize it. The content, if it needs to parsed (for example,
	 * for <ref>, <*include*> tags), is parsed in a fresh tokenizer context
	 * which means any error correction that needs to happen is restricted to
	 * the scope of the extension content and doesn't spill over to the higher
	 * level.  Ex: <math><!--foo</math>.
	 *
	 * IGNORE: {{ this just balances the blocks in this comment for pegjs
	 *
	 * This trick also lets us prevent extension content (that don't accept WT)
	 * from being parsed as wikitext (Ex: <math>\frac{foo\frac{bar}}</math>)
	 * We don't want the "}}" being treated as a template closing tag and
	 * closing outer templates.
	 * --------------------------------------------------------------------- */

	private function isXMLTag( string $name ): bool {
		$lName = mb_strtolower( $name );
		return isset( Consts::$HTML['HTML5Tags'][$lName] ) ||
			isset( Consts::$HTML['OlderHTMLTags'][$lName] );
	}

	private function maybeAnnotationOrExtensionTag( Token $t, ?bool $end, array $attribs, SourceRange $tsr ) {
		$tagName = mb_strtolower( $t->getName() );

		$isAnnotationTag = $this->siteConfig->isAnnotationTag( $tagName );
		if ( !$isAnnotationTag ) {
			$pipepos = strpos( $tagName, '|' );
			if ( $pipepos ) {
				$strBeforePipe = substr( $tagName, 0, $pipepos );
				$isAnnotationTag = $this->siteConfig->isAnnotationTag( $strBeforePipe );
				if ( $isAnnotationTag ) {
					$attribs = [ new KV( "name", substr( $tagName, $pipepos + 1, strlen( $tagName ) - $pipepos - 1 ) ) ];
					$tagName = $strBeforePipe;
				}
			}
		}

		if ( $isAnnotationTag ) {
			$metaAttrs = [ new KV( 'typeof', 'mw:Annotation/' . $tagName . ($end ? '/End' : '') ) ];
			if ( count( $attribs ) > 0 ) {
				$attrMap = [];
				foreach ( $attribs as $attr ) {
					// If the key or the value is not a string, we replace it by the thing that generated it and
					// consider that wikitext as a raw string instead.
					$k = is_string( $attr->k ) ? $attr->k : $attr->ksrc;
					$v = is_string( $attr->v ) ? $attr->v : $attr->vsrc;
					$attrMap[$k] = $v;
				}
				$datamw = [];
				// Possible follow-up in T295168 for attribute sanitation
				$datamw['attrs'] = $attrMap;
				array_push( $metaAttrs, new KV( 'data-mw', PHPUtils::jsonEncode( $datamw ) ) );
			}
			$dp = new DataParsoid();
			$dp->tsr = $tsr;
			$this->env->hasAnnotations = true;
			// FIXME: Suppress annotation meta tokens from template pipelines
			// since they may not have TSR values and won't get recognized as
			// annotation ranges. Without TSR, they might end up stuck in
			// fosterable positions and cause havoc on edits by breaking selser.
			if ( empty( $this->pipelineOpts['inTemplate'] ) ) {
				return [ new SelfclosingTagTk ( 'meta', $metaAttrs, $dp ) ];
			} else {
				return [];
			}
		}

		$isInstalledExt = isset( $this->extTags[$tagName] );
		$isIncludeTag = WTUtils::isIncludeTag( $tagName );

		// Extensions have higher precedence when they shadow html tags.
		if ( !( $isInstalledExt || $isIncludeTag ) ) {
			return $t;
		}

		$dp = $t->dataParsoid;
		$skipPos = $this->currPos;

		switch ( get_class( $t ) ) {
			case EndTagTk::class:
				if ( $isIncludeTag ) {
					return $t;
				}
				// Similar to TagTk, we rely on the sanitizer to convert to text
				// where necessary and emit tokens to ease the wikitext escaping
				// code.  However, extension tags that shadow html tags will see
				// their unmatched end tags dropped while tree building, since
				// the sanitizer will let them through.
				return $t; // not text()

			case SelfclosingTagTk::class:
				$dp->src = $dp->tsr->substr( $this->input );
				$dp->extTagOffsets = new DomSourceRange(
					$dp->tsr->start, $dp->tsr->end,
					$dp->tsr->length(), 0
				);
				if ( $isIncludeTag ) {
					return $t;
				}
				break;

			case TagTk::class:
				$endTagRE = '~.*?(</' . preg_quote( $tagName, '~' ) . '\s*>)~iusA';
				$tagContentFound = preg_match( $endTagRE, $this->input, $tagContent, 0, $dp->tsr->start );

				if ( !$tagContentFound ) {
					$dp->src = $dp->tsr->substr( $this->input );
					$dp->extTagOffsets = new DomSourceRange(
						$dp->tsr->start, $dp->tsr->end,
						$dp->tsr->length(), 0
					);
					if ( $isIncludeTag ) {
						return $t;
					} else {
						// This is undefined behaviour.  The old parser currently
						// returns text here (see core commit 674e8388cba),
						// whereas this results in unclosed
						// extension tags that shadow html tags falling back to
						// their html equivalent.  The sanitizer will take care
						// of converting to text where necessary.  We do this to
						// simplify `hasWikitextTokens` when escaping wikitext,
						// which wants these as tokens because it's otherwise
						// lacking in context.
						return $t; // not text()
					}
				}

				$extSrc = $tagContent[0];
				$extEndOffset = $dp->tsr->start + strlen( $extSrc );
				$extEndTagWidth = strlen( $tagContent[1] );

				if ( !empty( $this->pipelineOpts['inTemplate'] ) ) {
					// Support nesting in extensions tags while tokenizing in templates
					// to support the #tag parser function.
					//
					// It's necessary to permit this broadly in templates because
					// there's no way to distinguish whether the nesting happened
					// while expanding the #tag parser function, or just a general
					// syntax errors.  In other words,
					//
					//   hi<ref>ho<ref>hi</ref>ho</ref>
					//
					// and
					//
					//   hi{{#tag:ref|ho<ref>hi</ref>ho}}
					//
					// found in template are returned indistinguishably after a
					// preprocessing request, though the old parser renders them
					// differently.  #tag in template is probably a common enough
					// use case that we want to accept these false positives,
					// though another approach could be to drop this code here, and
					// invoke a native #tag handler and forgo those in templates.
					//
					// Expand `extSrc` as long as there is a <tagName> found in the
					// extension source body.
					$startTagRE = '~<' . preg_quote( $tagName, '~' ) . '(?:[^/>]|/(?!>))*>~i';
					$s = substr( $extSrc, $dp->tsr->end - $dp->tsr->start );
					$openTags = 0;
					while ( true ) {
						if ( preg_match_all( $startTagRE, $s, $matches ) ) {
							$openTags += count( $matches[0] );
						}
						if ( !$openTags ) {
							break;
						}
						if ( !preg_match( $endTagRE, $this->input, $tagContent, 0, $extEndOffset ) ) {
							break;
						}
						$openTags -= 1;
						$s = $tagContent[0];
						$extEndOffset += strlen( $s );
						$extEndTagWidth = strlen( $tagContent[1] );
						$extSrc .= $s;
					}
				}

				// Extension content source
				$dp->src = $extSrc;
				$dp->extTagOffsets = new DomSourceRange(
					$dp->tsr->start, $extEndOffset,
					$dp->tsr->length(), $extEndTagWidth
				);

				$skipPos = $dp->extTagOffsets->innerEnd();

				// If the xml-tag is a known installed (not native) extension,
				// skip the end-tag as well.
				if ( $isInstalledExt ) {
					$skipPos = $dp->extTagOffsets->end;
				}
				break;

			default:
				$this->unreachable();
		}

		$this->currPos = $skipPos;

		if ( $isInstalledExt ) {
			// update tsr->end to span the start and end tags.
			$dp->tsr->end = $this->endOffset(); // was just modified above
			return new SelfclosingTagTk( 'extension', [
					new KV( 'typeof', 'mw:Extension' ),
					new KV( 'name', $tagName ),
					new KV( 'about', $this->env->newAboutId() ),
					new KV( 'source', $dp->src ),
					new KV( 'options', $t->attribs )
				], $dp
			);
		} elseif ( $isIncludeTag ) {
			// Parse ext-content, strip eof, and shift tsr
			$extContent = $dp->extTagOffsets->stripTags( $dp->src );
			$tokenizer = new PegTokenizer( $this->env );
			$tokenizer->setSourceOffsets( new SourceRange( $dp->extTagOffsets->innerStart(), $dp->extTagOffsets->innerEnd() ) );
			$extContentToks = $tokenizer->tokenizeSync( $extContent );
			if ( $dp->extTagOffsets->closeWidth > 0 ) {
				TokenUtils::stripEOFTkFromTokens( $extContentToks );
			}
			array_unshift( $extContentToks, $t );
			return $extContentToks;
		} else {
			$this->unreachable();
		}
	}


	// cache init
	  protected $cache = [];

	// expectations
	protected $expectations = [
		0 => ["type" => "end", "description" => "end of input"],
1 => ["type" => "other", "description" => "start"],
2 => ["type" => "other", "description" => "table_start_tag"],
3 => ["type" => "class", "value" => "['{]", "description" => "['{]"],
4 => ["type" => "literal", "value" => "&", "description" => "\"&\""],
5 => ["type" => "other", "description" => "table_attributes"],
6 => ["type" => "other", "description" => "generic_newline_attributes"],
7 => ["type" => "any", "description" => "any character"],
8 => ["type" => "other", "description" => "extlink"],
9 => ["type" => "other", "description" => "tlb"],
10 => ["type" => "class", "value" => "[ \\t]", "description" => "[ \\t]"],
11 => ["type" => "literal", "value" => "<!--", "description" => "\"<!--\""],
12 => ["type" => "literal", "value" => "-->", "description" => "\"-->\""],
13 => ["type" => "literal", "value" => "|", "description" => "\"|\""],
14 => ["type" => "literal", "value" => "{{!}}", "description" => "\"{{!}}\""],
15 => ["type" => "literal", "value" => "//", "description" => "\"//\""],
16 => ["type" => "class", "value" => "[A-Za-z]", "description" => "[A-Za-z]"],
17 => ["type" => "class", "value" => "[-A-Za-z0-9+.]", "description" => "[-A-Za-z0-9+.]"],
18 => ["type" => "literal", "value" => ":", "description" => "\":\""],
19 => ["type" => "literal", "value" => "[", "description" => "\"[\""],
20 => ["type" => "class", "value" => "[0-9A-Fa-f:.]", "description" => "[0-9A-Fa-f:.]"],
21 => ["type" => "literal", "value" => "]", "description" => "\"]\""],
22 => ["type" => "class", "value" => "[^ \\]\\[\\r\\n\"'<>\\x00-\\x20\\x7f&\\u00A0\\u1680\\u180E\\u2000-\\u200A\\u202F\\u205F\\u3000{]", "description" => "[^ \\]\\[\\r\\n\"'<>\\x00-\\x20\\x7f&\\u00A0\\u1680\\u180E\\u2000-\\u200A\\u202F\\u205F\\u3000{]"],
23 => ["type" => "literal", "value" => "=", "description" => "\"=\""],
24 => ["type" => "class", "value" => "[\\0/=>]", "description" => "[\\0/=>]"],
25 => ["type" => "class", "value" => "[ \\u00A0\\u1680\\u2000-\\u200A\\u202F\\u205F\\u3000]", "description" => "[ \\u00A0\\u1680\\u2000-\\u200A\\u202F\\u205F\\u3000]"],
26 => ["type" => "literal", "value" => ";", "description" => "\";\""],
27 => ["type" => "literal", "value" => "\x0a", "description" => "\"\\n\""],
28 => ["type" => "literal", "value" => "\x0d\x0a", "description" => "\"\\r\\n\""],
29 => ["type" => "literal", "value" => "{", "description" => "\"{\""],
30 => ["type" => "class", "value" => "[#0-9a-zA-Z\u{5e8}\u{5dc}\u{5de}\u{631}\u{644}\u{645}]", "description" => "[#0-9a-zA-Z\u{5e8}\u{5dc}\u{5de}\u{631}\u{644}\u{645}]"],
31 => ["type" => "class", "value" => "[\"'=]", "description" => "[\"'=]"],
32 => ["type" => "class", "value" => "[^ \\t\\r\\n\\0/=><&{}\\-!|\\[]", "description" => "[^ \\t\\r\\n\\0/=><&{}\\-!|\\[]"],
33 => ["type" => "literal", "value" => "'", "description" => "\"'\""],
34 => ["type" => "literal", "value" => "\"", "description" => "\"\\\"\""],
35 => ["type" => "literal", "value" => "/", "description" => "\"/\""],
36 => ["type" => "class", "value" => "[^ \\t\\r\\n\\0/=><&{}\\-!|]", "description" => "[^ \\t\\r\\n\\0/=><&{}\\-!|]"],
37 => ["type" => "class", "value" => "[ \\t\\n\\r\\x0c]", "description" => "[ \\t\\n\\r\\x0c]"],
38 => ["type" => "class", "value" => "[^<[{\\n\\r|!\\]}\\-\\t&=\"' \\u00A0\\u1680\\u180E\\u2000-\\u200A\\u202F\\u205F\\u3000]", "description" => "[^<[{\\n\\r|!\\]}\\-\\t&=\"' \\u00A0\\u1680\\u180E\\u2000-\\u200A\\u202F\\u205F\\u3000]"],
39 => ["type" => "class", "value" => "[&|{\\-!}=]", "description" => "[&|{\\-!}=]"],
40 => ["type" => "class", "value" => "[']", "description" => "[']"],
41 => ["type" => "literal", "value" => "[[", "description" => "\"[[\""],
42 => ["type" => "class", "value" => "[*#:;]", "description" => "[*#:;]"],
43 => ["type" => "literal", "value" => "{{", "description" => "\"{{\""],
44 => ["type" => "literal", "value" => "<", "description" => "\"<\""],
45 => ["type" => "class", "value" => "[^{}&<\\-!\\['\\r\\n|]", "description" => "[^{}&<\\-!\\['\\r\\n|]"],
46 => ["type" => "class", "value" => "[{}&<\\-!\\[]", "description" => "[{}&<\\-!\\[]"],
47 => ["type" => "class", "value" => "[^{}&<\\-!\\[\"\\r\\n|]", "description" => "[^{}&<\\-!\\[\"\\r\\n|]"],
48 => ["type" => "class", "value" => "[^{}&<\\-!\\[ \\t\\n\\r\\x0c|]", "description" => "[^{}&<\\-!\\[ \\t\\n\\r\\x0c|]"],
49 => ["type" => "class", "value" => "[^{}&<\\-|/'>]", "description" => "[^{}&<\\-|/'>]"],
50 => ["type" => "class", "value" => "[{}&\\-|/]", "description" => "[{}&\\-|/]"],
51 => ["type" => "class", "value" => "[^{}&<\\-|/\">]", "description" => "[^{}&<\\-|/\">]"],
52 => ["type" => "class", "value" => "[^{}&<\\-|/ \\t\\n\\r\\x0c>]", "description" => "[^{}&<\\-|/ \\t\\n\\r\\x0c>]"],
53 => ["type" => "literal", "value" => "__", "description" => "\"__\""],
54 => ["type" => "class", "value" => "[^-'<[{\\n\\r:;\\]}|!=]", "description" => "[^-'<[{\\n\\r:;\\]}|!=]"],
55 => ["type" => "literal", "value" => "-", "description" => "\"-\""],
56 => ["type" => "literal", "value" => "''", "description" => "\"''\""],
57 => ["type" => "literal", "value" => "}", "description" => "\"}\""],
58 => ["type" => "class", "value" => "[ \\t\\n\\r\\0\\x0b]", "description" => "[ \\t\\n\\r\\0\\x0b]"],
59 => ["type" => "literal", "value" => "----", "description" => "\"----\""],
60 => ["type" => "literal", "value" => "{{{", "description" => "\"{{{\""],
61 => ["type" => "literal", "value" => "}}}", "description" => "\"}}}\""],
62 => ["type" => "literal", "value" => "}}", "description" => "\"}}\""],
63 => ["type" => "literal", "value" => "]]", "description" => "\"]]\""],
64 => ["type" => "literal", "value" => ">", "description" => "\">\""],
65 => ["type" => "literal", "value" => "/>", "description" => "\"/>\""],
66 => ["type" => "literal", "value" => "RFC", "description" => "\"RFC\""],
67 => ["type" => "literal", "value" => "PMID", "description" => "\"PMID\""],
68 => ["type" => "class", "value" => "[0-9]", "description" => "[0-9]"],
69 => ["type" => "literal", "value" => "ISBN", "description" => "\"ISBN\""],
70 => ["type" => "class", "value" => "[xX]", "description" => "[xX]"],
71 => ["type" => "literal", "value" => "+", "description" => "\"+\""],
72 => ["type" => "class", "value" => "[^<[{\\n\\r\\t|!\\]}{ &\\-]", "description" => "[^<[{\\n\\r\\t|!\\]}{ &\\-]"],
73 => ["type" => "class", "value" => "[!<\\-\\}\\]\\n\\r]", "description" => "[!<\\-\\}\\]\\n\\r]"],
74 => ["type" => "class", "value" => "[^\\t\\n\\v />\\0]", "description" => "[^\\t\\n\\v />\\0]"],
75 => ["type" => "literal", "value" => "-{", "description" => "\"-{\""],
76 => ["type" => "literal", "value" => "}-", "description" => "\"}-\""],
77 => ["type" => "literal", "value" => "!", "description" => "\"!\""],
78 => ["type" => "literal", "value" => "!!", "description" => "\"!!\""],
79 => ["type" => "literal", "value" => "||", "description" => "\"||\""],
80 => ["type" => "literal", "value" => "{{!}}{{!}}", "description" => "\"{{!}}{{!}}\""],
81 => ["type" => "literal", "value" => "=>", "description" => "\"=>\""],
82 => ["type" => "class", "value" => "[-+A-Z]", "description" => "[-+A-Z]"],
83 => ["type" => "class", "value" => "[^{}|;]", "description" => "[^{}|;]"],
84 => ["type" => "class", "value" => "[a-z]", "description" => "[a-z]"],
85 => ["type" => "class", "value" => "[-a-zA-Z]", "description" => "[-a-zA-Z]"],
	];

	// actions
	private function a0() {

				$this->startTime = null;
				if ( $this->env->profiling() ) {
					$profile = $this->env->getCurrentProfile();
					$this->startTime = microtime( true );
				}
				return true;
			
}
private function a1($t) {

				if ( $this->env->profiling() ) {
					$profile = $this->env->getCurrentProfile();
					$profile->bumpTimeUse(
						'PEG', 1000 * ( microtime( true ) - $this->startTime ), 'PEG' );
				}
				return true;
			
}
private function a2($t) {
 return $t; 
}
private function a3() {

			// "tlb" matches "block" matches "sol" matches "newlineToken"
			// But, "tlb" is prefixed with a !eof clause, so, we should only
			// get here on eof. So, safe to unconditionally terminate the
			// generator loop here.
			return false;
		
}
private function a4($t, $n) {

		if ( count( $t ) ) {
			$ret = TokenizerUtils::flattenIfArray( $t );
		} else {
			$ret = [];
		}
		if ( count( $n ) ) {
			PHPUtils::pushArray($ret, $n);
		}
		$ret[] = new EOFTk();
		return $ret;
	
}
private function a5($sc) {
 return $this->endOffset(); 
}
private function a6($sc, $startPos, $b, $p) {
 $this->unreachable(); 
}
private function a7($sc, $startPos, $b, $p, $ta) {
 return $this->endOffset(); 
}
private function a8($sc, $startPos, $b, $p, $ta, $tsEndPos, $s2) {

		$coms = TokenizerUtils::popComments( $ta );
		if ( $coms ) {
			$tsEndPos = $coms['commentStartPos'];
		}

		$dp = new DataParsoid;
		$dp->tsr = new SourceRange( $startPos, $tsEndPos );
		if ( $p !== '|' ) {
			// Variation from default
			$dp->startTagSrc = $b . $p;
		}

		return array_merge( $sc,
			[ new TagTk( 'table', $ta, $dp ) ],
			$coms ? $coms['buf'] : [],
			$s2 );
	
}
private function a9($proto, $addr, $he) {
 return $he; 
}
private function a10($proto, $addr, $r) {
 return $r; 
}
private function a11($proto, $addr, $c) {
 return $c; 
}
private function a12($proto, $addr, $path) {
 return $addr !== '' || count( $path ) > 0; 
}
private function a13($proto, $addr, $path) {

		return TokenizerUtils::flattenString( array_merge( [ $proto, $addr ], $path ) );
	
}
private function a14($as, $s, $p) {

		return [ $as, $s, $p ];
	
}
private function a15($b) {
 return $b; 
}
private function a16($r) {
 return TokenizerUtils::flattenIfArray( $r ); 
}
private function a17() {
 return $this->endOffset(); 
}
private function a18($p0, $addr, $target) {
 return TokenizerUtils::flattenString( [ $addr, $target ] ); 
}
private function a19($p0, $flat) {

			// Protocol must be valid and there ought to be at least one
			// post-protocol character.  So strip last char off target
			// before testing protocol.
			if ( is_array( $flat ) ) {
				// There are templates present, alas.
				return count( $flat ) > 0;
			}
			return Utils::isProtocolValid( substr( $flat, 0, -1 ), $this->env );
		
}
private function a20($p0, $flat) {
 return $this->endOffset(); 
}
private function a21($p0, $flat, $p1, $sp) {
 return $this->endOffset(); 
}
private function a22($p0, $flat, $p1, $sp, $p2, $content) {
 return $this->endOffset(); 
}
private function a23($p0, $flat, $p1, $sp, $p2, $content, $p3) {

			$tsr1 = new SourceRange( $p0, $p1 );
			$tsr2 = new SourceRange( $p2, $p3 );
			$dp = new DataParsoid;
			$dp->tsr = $this->tsrOffsets();
			$dp->extLinkContentOffsets = $tsr2;
			return [
				new SelfclosingTagTk(
					'extlink',
					[
						new KV( 'href', $flat, $tsr1->expandTsrV() ),
						new KV( 'mw:content', $content ?? '', $tsr2->expandTsrV() ),
						new KV( 'spaces', $sp )
					],
					$dp
				)
			]; 
}
private function a24($r) {
 return $r; 
}
private function a25($b) {

		// Clear the tokenizer's backtracking cache after matching each
		// toplevelblock. There won't be any backtracking as a document is just a
		// sequence of toplevelblocks, so the cache for previous toplevelblocks
		// will never be needed.
		$end = $this->startOffset();
		for ( ;  $this->prevOffset < $end;  $this->prevOffset++ ) {
			unset( $this->cache[$this->prevOffset] );
		}

		$tokens = null;
		if ( is_array( $b ) && count( $b ) ) {
			$tokens = TokenizerUtils::flattenIfArray( $b );
		} elseif ( is_string( $b ) ) {
			$tokens = [ $b ];
		}

		// Emit tokens for this toplevelblock. This feeds a chunk to the parser pipeline.
		return $this->emitChunk( $tokens );
	
}
private function a26() {
 return [ new NlTk( $this->tsrOffsets() ) ]; 
}
private function a27($c, $cEnd) {

		$data = WTUtils::encodeComment( $c );
		$dp = new DataParsoid;
		$dp->tsr = $this->tsrOffsets();
		if ( $cEnd !== '-->' ) {
			$dp->unclosedComment = true;
		}
		return [ new CommentTk( $data, $dp ) ];
	
}
private function a28($p) {
 return Utils::isProtocolValid( $p, $this->env ); 
}
private function a29($p) {
 return $p; 
}
private function a30($annOrExtTag, $h, $extlink, $intemplate, &$preproc, $equal, $table, $templateArg, $tableCellArg, $semicolon, $arrow, $linkdesc, $colon, &$th) {

			return TokenizerUtils::inlineBreaks( $this->input, $this->endOffset(), [
				'annOrExtTag' => $annOrExtTag,
				'h' => $h,
				'extlink' => $extlink,
				'intemplate' => $intemplate,
				'preproc' => $preproc,
				'equal' => $equal,
				'table' => $table,
				'templateArg' => $templateArg,
				'tableCellArg' => $tableCellArg,
				'semicolon' => $semicolon,
				'arrow' => $arrow,
				'linkdesc' => $linkdesc,
				'colon' => $colon,
				'th' => $th
			], $this->env );
		
}
private function a31($t) {

		return $t;
	
}
private function a32($cc) {

		// if this is an invalid entity, don't tag it with 'mw:Entity'
		// note that some entities (like &acE;) decode to 2 codepoints!
		if ( mb_strlen( $cc ) > 2 /* decoded entity would be 1-2 codepoints */ ) {
			return $cc;
		}
		$dpStart = new DataParsoid;
		$dpStart->src = $this->text();
		$dpStart->srcContent = $cc;
		$dpStart->tsr = $this->tsrOffsets( 'start' );
		$dpEnd = new DataParsoid;
		$dpEnd->tsr = $this->tsrOffsets( 'end' );
		return [
			// If this changes, the nowiki extension's toDOM will need to follow suit
			new TagTk( 'span', [ new KV( 'typeof', 'mw:Entity' ) ], $dpStart ),
			$cc,
			new EndTagTk( 'span', [], $dpEnd )
		];
	
}
private function a33($s) {
 return $this->endOffset(); 
}
private function a34($s, $namePos0, $name) {
 return $this->endOffset(); 
}
private function a35($s, $namePos0, $name, $namePos, $v) {
 return $v; 
}
private function a36($s, $namePos0, $name, $namePos, $vd) {

	// NB: Keep in sync w/ generic_newline_attribute
	$res = null;
	// Encapsulate protected attributes.
	if ( gettype( $name ) === 'string' ) {
		$name = TokenizerUtils::protectAttrs( $name );
	}
	$nameSO = new SourceRange( $namePos0, $namePos );
	if ( $vd !== null ) {
		$res = new KV( $name, $vd['value'], $nameSO->join( $vd['srcOffsets'] ) );
		$res->vsrc = $vd['srcOffsets']->substr( $this->input );
	} else {
		$res = new KV( $name, '', $nameSO->expandTsrK() );
	}
	if ( is_array( $name ) ) {
		$res->ksrc = $nameSO->substr( $this->input );
	}
	return $res;

}
private function a37($s) {

		if ( $s !== '' ) {
			return [ $s ];
		} else {
			return [];
		}
	
}
private function a38($c) {
 return new KV( $c, '' ); 
}
private function a39($namePos0, $name) {
 return $this->endOffset(); 
}
private function a40($namePos0, $name, $namePos, $v) {
 return $v; 
}
private function a41($namePos0, $name, $namePos, $vd) {

	// NB: Keep in sync w/ table_attibute
	$res = null;
	// Encapsulate protected attributes.
	if ( is_string( $name ) ) {
		$name = TokenizerUtils::protectAttrs( $name );
	}
	$nameSO = new SourceRange( $namePos0, $namePos );
	if ( $vd !== null ) {
		$res = new KV( $name, $vd['value'], $nameSO->join( $vd['srcOffsets'] ) );
		$res->vsrc = $vd['srcOffsets']->substr( $this->input );
	} else {
		$res = new KV( $name, '', $nameSO->expandTsrK() );
	}
	if ( is_array( $name ) ) {
		$res->ksrc = $nameSO->substr( $this->input );
	}
	return $res;

}
private function a42($s) {
 return $s; 
}
private function a43($c) {

		return TokenizerUtils::flattenStringlist( $c );
	
}
private function a44($lc) {
 return $lc; 
}
private function a45($bullets, $c) {
 return $this->endOffset(); 
}
private function a46($bullets, $c, $cpos, $d) {

		// Leave bullets as an array -- list handler expects this
		// TSR: +1 for the leading ";"
		$numBullets = count( $bullets ) + 1;
		$tsr = $this->tsrOffsets( 'start' );
		$tsr->end += $numBullets;
		$li1Bullets = $bullets;
		$li1Bullets[] = ';';
		$dp = new DataParsoid;
		$dp->tsr = $tsr;
		$li1 = new TagTk( 'listItem', [ new KV( 'bullets', $li1Bullets, $tsr->expandTsrV() ) ], $dp );
		// TSR: -1 for the intermediate ":"
		$li2Bullets = $bullets;
		$li2Bullets[] = ':';
		$tsr2 = new SourceRange( $cpos - 1, $cpos );
		$dp2 = new DataParsoid;
		$dp2->tsr = $tsr2;
		$dp2->stx = 'row';
		$li2 = new TagTk( 'listItem', [ new KV( 'bullets', $li2Bullets, $tsr2->expandTsrV() ) ], $dp2 );

		return array_merge( [ $li1 ], $c ?: [], [ $li2 ], $d ?: [] );
	
}
private function a47($bullets, $tbl, $line) {

	// Leave bullets as an array -- list handler expects this
	$tsr = $this->tsrOffsets( 'start' );
	$tsr->end += count( $bullets );
	$dp = new DataParsoid;
	$dp->tsr = $tsr;
	$li = new TagTk( 'listItem', [ new KV( 'bullets', $bullets, $tsr->expandTsrV() ) ], $dp );
	return TokenizerUtils::flattenIfArray( [ $li, $tbl, $line ?: [] ] );

}
private function a48($bullets, $c) {

		// Leave bullets as an array -- list handler expects this
		$tsr = $this->tsrOffsets( 'start' );
		$tsr->end += count( $bullets );
		$dp = new DataParsoid;
		$dp->tsr = $tsr;
		$li = new TagTk( 'listItem', [ new KV( 'bullets', $bullets, $tsr->expandTsrV() ) ], $dp );
		return array_merge( [ $li ], $c ?: [] );
	
}
private function a49() {
 return $this->endOffset() === $this->inputLength; 
}
private function a50($r, $cil, $bl) {

		return array_merge( [ $r ], $cil, $bl ?: [] );
	
}
private function a51($a) {
 return $a; 
}
private function a52($a, $b) {
 return [ $a, $b ]; 
}
private function a53($m) {

		return Utils::decodeWtEntities( $m );
	
}
private function a54($q, $x, $ill) {
 return array_merge( $x, $ill ?: [] ); 
}
private function a55($q, $t) {
 return $t; 
}
private function a56($q, $r) {
 return count( $r ) > 0 || $q !== ''; 
}
private function a57($q, $r) {

		array_unshift( $r, $q );
		return TokenizerUtils::flattenString( $r );
	
}
private function a58($s, $t, $q) {

		return TokenizerUtils::getAttrVal( $t, $this->startOffset() + strlen( $s ), $this->endOffset() - strlen( $q ) );
	
}
private function a59($s, $t) {

		return TokenizerUtils::getAttrVal( $t, $this->startOffset() + strlen( $s ), $this->endOffset() );
	
}
private function a60($r) {

		return TokenizerUtils::flattenString( $r );
	
}
private function a61() {

			if ( preg_match( $this->reUrltextLookahead, $this->input, $m, 0, $this->currPos ) ) {
				$plain = $m[1];
				$this->urltextPlainSegment = $plain;
				$this->urltextFoundAutolink = ( $m[2] ?? '' ) !== '';
				return (bool)strlen( $plain );
			} else {
				$this->urltextFoundAutolink = false;
				return false;
			}
		
}
private function a62() {

			$this->currPos += strlen( $this->urltextPlainSegment );
			return $this->urltextPlainSegment;
		
}
private function a63() {
 return $this->urltextFoundAutolink; 
}
private function a64($al) {
 return $al; 
}
private function a65($he) {
 return $he; 
}
private function a66($bs) {
 return $bs; 
}
private function a67($tl) {

		return $tl;
	
}
private function a68() {
 return $this->endOffset() === 0 && !$this->pipelineOffset; 
}
private function a69($rw, $sp, $c, $wl) {

		return count( $wl ) === 1 && $wl[0] instanceof Token;
	
}
private function a70($rw, $sp, $c, $wl) {

		$link = $wl[0];
		if ( $sp ) {
			$rw .= $sp;
		}
		if ( $c ) {
			$rw .= $c;
		}
		// Build a redirect token
		$dp = new DataParsoid;
		$dp->src = $rw;
		$dp->tsr = $this->tsrOffsets();
		$dp->linkTk = $link;
		$redirect = new SelfclosingTagTk( 'mw:redirect',
			// Put 'href' into attributes so it gets template-expanded
			[ $link->getAttributeKV( 'href' ) ],
			$dp
		);
		return $redirect;
	
}
private function a71($st, $tl) {

		return array_merge( $st, $tl );
	
}
private function a72($s, $os, $so) {
 return array_merge( $os, $so ); 
}
private function a73($s, $s2, $bl) {

		return array_merge( $s, $s2 ?: [], $bl );
	
}
private function a74(&$preproc, $t) {

		$preproc = null;
		return $t;
	
}
private function a75($v) {
 return $v; 
}
private function a76($e) {
 return $e; 
}
private function a77($tag) {
 return $tag; 
}
private function a78() {
 return Utils::isUniWord(Utils::lastUniChar( $this->input, $this->endOffset() ) ); 
}
private function a79($bs) {

		if ( $this->siteConfig->isBehaviorSwitch( $bs ) ) {
			$dp = new DataParsoid;
			$dp->tsr = $this->tsrOffsets();
			$dp->src = $bs;
			$dp->magicSrc = $bs;
			return [
				new SelfclosingTagTk( 'behavior-switch', [ new KV( 'word', $bs ) ], $dp )
			];
		} else {
			return [ $bs ];
		}
	
}
private function a80($quotes) {

		// sequences of four or more than five quotes are assumed to start
		// with some number of plain-text apostrophes.
		$plainticks = 0;
		$result = [];
		if ( strlen( $quotes ) === 4 ) {
			$plainticks = 1;
		} elseif ( strlen( $quotes ) > 5 ) {
			$plainticks = strlen( $quotes ) - 5;
		}
		if ( $plainticks > 0 ) {
			$result[] = substr( $quotes, 0, $plainticks );
		}
		// mw-quote token will be consumed in token transforms
		$tsr = $this->tsrOffsets();
		$tsr->start += $plainticks;
		$dp = new DataParsoid;
		$dp->tsr = $tsr;
		$mwq = new SelfclosingTagTk( 'mw-quote',
			[ new KV( 'value', substr( $quotes, $plainticks ) ) ],
			$dp );
		if ( strlen( $quotes ) > 2 ) {
			$mwq->addAttribute( 'isSpace_1', $tsr->start > 0 && substr( $this->input, $tsr->start - 1, 1 ) === ' ');
			$mwq->addAttribute( 'isSpace_2', $tsr->start > 1 && substr( $this->input, $tsr->start - 2, 1 ) === ' ');
		}
		$result[] = $mwq;
		return $result;
	
}
private function a81($sc, $startPos, $p, $b) {

		$dp = new DataParsoid;
		$dp->tsr = new SourceRange( $startPos, $this->endOffset() );
		$tblEnd = new EndTagTk( 'table', [], $dp );
		if ( $p !== '|' ) {
			// p+"<brace-char>" is triggering some bug in pegJS
			// I cannot even use that expression in the comment!
			$tblEnd->dataParsoid->endTagSrc = $p . $b;
		}
		array_push( $sc, $tblEnd );
		return $sc;
	
}
private function a82($sp) {
 return $this->endOffset(); 
}
private function a83($sp, $p, $c) {

		$dp = new DataParsoid;
		$dp->tsr = new SourceRange( $p, $this->endOffset() );
		$dp->tokens = TokenizerUtils::flattenIfArray( $c );
		return [
			$sp,
			new SelfclosingTagTk( 'meta', [ new KV( 'typeof', 'mw:EmptyLine' ) ], $dp )
		];
	
}
private function a84() {

		// Use the sol flag only at the start of the input
		// Flag should always be an actual boolean (not falsy or undefined)
		$this->assert( is_bool( $this->options['sol'] ), 'sol should be boolean' );
		return $this->endOffset() === 0 && $this->options['sol'];
	
}
private function a85() {

		return [];
	
}
private function a86($rw) {

			return preg_match( $this->env->getSiteConfig()->getMagicWordMatcher( 'redirect' ), $rw );
		
}
private function a87($il, $sol_il) {

		$il = $il[0];
		$lname = mb_strtolower( $il->getName() );
		if ( !WTUtils::isIncludeTag( $lname ) ) { return false;  }
		// Preserve SOL where necessary (for onlyinclude and noinclude)
		// Note that this only works because we encounter <*include*> tags in
		// the toplevel content and we rely on the php preprocessor to expand
		// templates, so we shouldn't ever be tokenizing inInclude.
		// Last line should be empty (except for comments)
		if ( $lname !== 'includeonly' && $sol_il && $il instanceof TagTk ) {
			$dp = $il->dataParsoid;
			$inclContent = $dp->extTagOffsets->stripTags( $dp->src );
			$nlpos = strrpos( $inclContent, "\n" );
			$last = $nlpos === false ? $inclContent : substr( $inclContent, $nlpos + 1 );
			if ( !preg_match( '/^(<!--([^-]|-(?!->))*-->)*$/D', $last ) ) {
				return false;
			}
		}
		return true;
	
}
private function a88($il, $sol_il) {

		return $il;
	
}
private function a89($annToken) {

		return ( $annToken instanceof Token && $annToken->getName() !== 'extension' );
	
}
private function a90($annToken) {
 return $annToken; 
}
private function a91($s, $ill) {
 return $ill ?: []; 
}
private function a92($s, $ce) {
 return $ce || strlen( $s ) > 2; 
}
private function a93($s, $ce) {
 return $this->endOffset(); 
}
private function a94($s, $ce, $endTPos, $spc) {

			$c = null;
			$e = null;
			$level = null;
			if ( $ce ) {
				$c = $ce[0];
				$e = $ce[1];
				$level = min( strlen( $s ), strlen( $e ) );
			} else {
				// split up equal signs into two equal parts, with at least
				// one character in the middle.
				$level = (int)floor( ( strlen( $s ) - 1 ) / 2 );
				$c = [ str_repeat( '=', strlen( $s ) - 2 * $level ) ];
				$s = $e = str_repeat( '=', $level );
			}
			$level = min( 6, $level );
			// convert surplus equals into text
			if ( strlen( $s ) > $level ) {
				$extras1 = substr( $s, 0, strlen( $s ) - $level );
				if ( is_string( $c[0] ) ) {
					$c[0] = $extras1 . $c[0];
				} else {
					array_unshift( $c, $extras1 );
				}
			}
			if ( strlen( $e ) > $level ) {
				$extras2 = substr( $e, 0, strlen( $e ) - $level );
				$lastElem = PHPUtils::lastItem( $c );
				if ( is_string( $lastElem ) ) {
					$c[count( $c ) - 1] .= $extras2;
				} else {
					$c[] = $extras2;
				}
			}

			$tagDP = new DataParsoid;
			$tagDP->tsr = $this->tsrOffsets( 'start' );
			$tagDP->tsr->end += $level;
			// Match the old parser's behavior by (a) making headingIndex part of tokenizer
			// state(don't reuse pipeline!) and (b) assigning the index when
			// ==*== is tokenized, even if we're inside a template argument
			// or other context which won't end up putting the heading
			// on the output page.  T213468/T214538
			$this->headingIndex++;
			$tagDP->getTemp()->headingIndex = $this->headingIndex;
			$res = [ new TagTk( 'h' . $level, [], $tagDP ) ];

			PHPUtils::pushArray( $res, $c );

			$endTagDP = new DataParsoid;
			$endTagDP->tsr = new SourceRange( $endTPos - $level, $endTPos );
			$res[] = new EndTagTk( 'h' . $level, [], $endTagDP );
			$res[] = $spc;
			return $res;
		
}
private function a95($d) {
 return null; 
}
private function a96($d) {
 return true; 
}
private function a97($d, $lineContent) {

		$dataParsoid = new DataParsoid;
		$dataParsoid->tsr = $this->tsrOffsets();
		if ( $lineContent !== null ) {
			$dataParsoid->lineContent = $lineContent;
		}
		if ( strlen( $d ) > 0 ) {
			$dataParsoid->extra_dashes = strlen( $d );
		}
		return [new SelfclosingTagTk( 'hr', [], $dataParsoid )];
	
}
private function a98($p, $target) {
 return $this->endOffset(); 
}
private function a99($p, $target, $p0, $v) {
 return $this->endOffset(); 
}
private function a100($p, $target, $p0, $v, $p1) {

				// empty argument
				return [ 'tokens' => $v, 'srcOffsets' => new SourceRange( $p0, $p1 ) ];
			
}
private function a101($p, $target, $r) {
 return $r; 
}
private function a102($p, $target, $params) {

		$kvs = [];

		if ( $target === null ) {
			$target = [ 'tokens' => '', 'srcOffsets' => new SourceRange( $p, $p ) ];
		}
		// Insert target as first positional attribute, so that it can be
		// generically expanded. The TemplateHandler then needs to shift it out
		// again.
		$kvs[] = new KV( TokenizerUtils::flattenIfArray( $target['tokens'] ), '', $target['srcOffsets']->expandTsrK() );

		foreach ( $params as $o ) {
			$s = $o['srcOffsets'];
			$kvs[] = new KV( '', TokenizerUtils::flattenIfArray( $o['tokens'] ), $s->expandTsrV() );
		}

		$dp = new DataParsoid;
		$dp->tsr = $this->tsrOffsets();
		$dp->src = $this->text();
		$obj = new SelfclosingTagTk( 'templatearg', $kvs, $dp );
		return $obj;
	
}
private function a103($leadWS, $target) {
 return $this->endOffset(); 
}
private function a104($leadWS, $target, $p0, $v) {
 return $this->endOffset(); 
}
private function a105($leadWS, $target, $p0, $v, $p) {

				// empty argument
				$tsr0 = new SourceRange( $p0, $p );
				return new KV( '', TokenizerUtils::flattenIfArray( $v ), $tsr0->expandTsrV() );
			
}
private function a106($leadWS, $target, $r) {
 return $r; 
}
private function a107($leadWS, $target, $params, $trailWS) {

		// Insert target as first positional attribute, so that it can be
		// generically expanded. The TemplateHandler then needs to shift it out
		// again.
		array_unshift( $params, new KV( TokenizerUtils::flattenIfArray( $target['tokens'] ), '', $target['srcOffsets']->expandTsrK() ) );
		$dp = new DataParsoid;
		$dp->tsr = $this->tsrOffsets();
		$dp->src = $this->text();
		$tmp = $dp->getTemp();
		$tmp->leadWS = $leadWS;
		$tmp->trailWS = $trailWS;
		$obj = new SelfclosingTagTk( 'template', $params, $dp );
		return $obj;
	
}
private function a108($spos, $target) {
 return $this->endOffset(); 
}
private function a109($spos, $target, $tpos, $lcs) {

		$pipeTrick = count( $lcs ) === 1 && count( $lcs[0]->v ) === 0;
		$textTokens = [];
		if ( $target === null || $pipeTrick ) {
			$textTokens[] = '[[';
			if ( $target ) {
				$textTokens[] = $target;
			}
			foreach ( $lcs as $a ) {
				// a is a mw:maybeContent attribute
				$textTokens[] = '|';
				if ( count( $a->v ) > 0 ) {
					$textTokens[] = $a->v;
				}
			}
			$textTokens[] = ']]';
			return $textTokens;
		}
		$obj = new SelfclosingTagTk( 'wikilink' );
		$tsr = new SourceRange( $spos, $tpos );
		$hrefKV = new KV( 'href', $target, $tsr->expandTsrV() );
		$hrefKV->vsrc = $tsr->substr( $this->input );
		// XXX: Point to object with path, revision and input information
		// obj.source = input;
		$obj->attribs[] = $hrefKV;
		$obj->attribs = array_merge( $obj->attribs, $lcs );
		$dp = new DataParsoid;
		$dp->tsr = $this->tsrOffsets();
		$dp->src = $this->text();
		$obj->dataParsoid = $dp;
		return [ $obj ];
	
}
private function a110(&$preproc) {
 $preproc =  null; return true; 
}
private function a111(&$preproc, $a) {

		return $a;
	
}
private function a112($extToken) {
 return !$extToken || $extToken[0]->getName() === 'extension' ||
			($extToken[0]->getName() === 'meta' && preg_match( WTUtils::ANNOTATION_META_TYPE_REGEXP, $extToken[0]->getAttributeV( 'typeof' ) ?? '' ) > 0); 
}
private function a113($extToken) {
 return !$extToken ? '' : $extToken[0]; 
}
private function a114() {

		$this->assert(
			$this->input[$this->currPos - 1] === '<',
			'Failed to open xmlish_tag before entering.'
		);
		return true;
	
}
private function a115($end, $name, $annOrExtTag) {

		if ( $annOrExtTag ) {
			return WTUtils::isAnnOrExtTag( $this->env, $name );
		} else {
			// Only enforce ascii alpha first char for non-extension tags.
			// See tag_name above for the details.
			return preg_match( '/^[A-Za-z]/', $name ) && $this->isXMLTag( $name );
		}
	
}
private function a116($end, $name, $annOrExtTag, $attribs, $selfclose) {

		$lcName = mb_strtolower( $name );

		// Extension tags don't necessarily have the same semantics as html tags,
		// so don't treat them as void elements.
		$isVoidElt = Utils::isVoidElement( $lcName ) && !$annOrExtTag;

		// Support </br>
		if ( $lcName === 'br' && $end ) {
			$end = null;
		}

		$tsr = $this->tsrOffsets();
		$tsr->start--; // For "<" matched at the start of xmlish_tag rule
		$res = TokenizerUtils::buildXMLTag( $name, $lcName, $attribs, $end, !!$selfclose || $isVoidElt, $tsr );

		// change up data-attribs in one scenario
		// void-elts that aren't self-closed ==> useful for accurate RT-ing
		if ( !$selfclose && $isVoidElt ) {
			unset( $res->dataParsoid->selfClose );
			$res->dataParsoid->noClose = true;
		}

		$met = $this->maybeAnnotationOrExtensionTag( $res, $end, $attribs, $tsr );
		return is_array( $met ) ? $met : [ $met ];
	
}
private function a117() {
 return $this->env->hasAnnotations && $this->siteConfig->isAnnotationTag( 'tvar' ); 
}
private function a118() {

		$metaAttrs = [ new KV( 'typeof', 'mw:Annotation/tvar/End' ) ];
		$dp = new DataParsoid();
		$dp->tsr = $this->tsrOffsets();
		$dp->tsr->start--; // For "<" matched at the start of xmlish_tag rule
		if ( empty( $this->pipelineOpts['inTemplate'] ) ) {
			return [ new SelfclosingTagTk ( 'meta', $metaAttrs, $dp ) ];
		} else {
			// suppress meta tags from pipeline output
			return [];
		}
	
}
private function a119($proto, $addr, $rhe) {
 return $rhe === '<' || $rhe === '>' || $rhe === "\u{A0}"; 
}
private function a120($proto, $addr, $path) {

			// as in Parser.php::makeFreeExternalLink, we're going to
			// yank trailing punctuation out of this match.
			$url = TokenizerUtils::flattenStringlist( array_merge( [ $proto, $addr ], $path ) );
			// only need to look at last element; HTML entities are strip-proof.
			$last = PHPUtils::lastItem( $url );
			$trim = 0;
			if ( is_string( $last ) ) {
				$strip = TokenizerUtils::getAutoUrlTerminatingChars( in_array( '(', $path, true ) );
				$trim = strspn( strrev( $last ), $strip );
				$url[ count( $url ) - 1 ] = substr( $last, 0, strlen( $last ) - $trim );
			}
			$url = TokenizerUtils::flattenStringlist( $url );
			if ( count( $url ) === 1 && is_string( $url[0] ) && strlen( $url[0] ) <= strlen( $proto ) ) {
				return null; // ensure we haven't stripped everything: T106945
			}
			$this->currPos -= $trim;
			return $url;
		
}
private function a121($r) {
 return $r !== null; 
}
private function a122($r) {

		$tsr = $this->tsrOffsets();
		$dp = new DataParsoid;
		$dp->tsr = $tsr;
		$res = [ new SelfclosingTagTk( 'urllink', [ new KV( 'href', $r, $tsr->expandTsrV() ) ], $dp ) ];
		return $res;
	
}
private function a123($ref, $sp, $identifier) {

		$base_urls = [
			'RFC' => 'https://tools.ietf.org/html/rfc%s',
			'PMID' => '//www.ncbi.nlm.nih.gov/pubmed/%s?dopt=Abstract'
		];
		$tsr = $this->tsrOffsets();
		$dp = new DataParsoid;
		$dp->tsr = $tsr;
		$dp->stx = 'magiclink';
		return [
			new SelfclosingTagTk( 'extlink', [
					new KV( 'href', sprintf( $base_urls[ $ref ], $identifier ) ),
					new KV( 'mw:content', TokenizerUtils::flattenString( [ $ref, $sp, $identifier ] ), $tsr->expandTsrV() ),
					new KV( 'typeof', 'mw:ExtLink/' . $ref )
				],
				$dp
			)
		];
	
}
private function a124($sp, $isbn) {

			// Convert isbn token-and-entity array to stripped string.
			$stripped = '';
			foreach ( TokenizerUtils::flattenStringlist( $isbn ) as $part ) {
				if ( is_string( $part ) ) {
					$stripped .= $part;
				}
			}
			return strtoupper( preg_replace( '/[^\dX]/i', '', $stripped ) );
		
}
private function a125($sp, $isbn, $isbncode) {

		// ISBNs can only be 10 or 13 digits long (with a specific format)
		return strlen( $isbncode ) === 10
			|| ( strlen( $isbncode ) === 13 && preg_match( '/^97[89]/', $isbncode ) );
	
}
private function a126($sp, $isbn, $isbncode) {

		$tsr = $this->tsrOffsets();
		$dp = new DataParsoid;
		$dp->stx = 'magiclink';
		$dp->tsr = $tsr;
		return [
			new SelfclosingTagTk( 'extlink', [
					new KV( 'href', 'Special:BookSources/' . $isbncode ),
					new KV( 'mw:content', TokenizerUtils::flattenString( [ 'ISBN', $sp, $isbn ] ), $tsr->expandTsrV() ),
					new KV( 'typeof', 'mw:WikiLink/ISBN' )
				],
				$dp
			)
		];
	
}
private function a127($p, $dashes) {
 $this->unreachable(); 
}
private function a128($p, $dashes, $a) {
 return $this->endOffset(); 
}
private function a129($p, $dashes, $a, $tagEndPos, $s2) {

		$coms = TokenizerUtils::popComments( $a );
		if ( $coms ) {
			$tagEndPos = $coms['commentStartPos'];
		}

		$da = new DataParsoid;
		$da->tsr = new SourceRange( $this->startOffset(), $tagEndPos );
		$da->startTagSrc = $p . $dashes;

		// We rely on our tree builder to close the row as needed. This is
		// needed to support building tables from fragment templates with
		// individual cells or rows.
		$trToken = new TagTk( 'tr', $a, $da );

		return array_merge( [ $trToken ], $coms ? $coms['buf'] : [], $s2 );
	
}
private function a130($p, $td) {
 return $this->endOffset(); 
}
private function a131($p, $td, $tagEndPos, $tds) {

		// Avoid modifying a cached result
		$td[0] = clone $td[0];
		$da = $td[0]->dataParsoid = clone $td[0]->dataParsoid;
		$da->tsr = clone $da->tsr;

		$da->tsr->start -= strlen( $p ); // include "|"
		if ( $p !== '|' ) {
			// Variation from default
			$da->startTagSrc = $p;
		}
		return array_merge( $td, $tds );
	
}
private function a132($p, $args) {
 return $this->endOffset(); 
}
private function a133($p, $args, $tagEndPos, $c) {

		$tsr = new SourceRange( $this->startOffset(), $tagEndPos );
		return TokenizerUtils::buildTableTokens(
			'caption', '|+', $args, $tsr, $this->endOffset(), $c, true );
	
}
private function a134($tpt) {

		return [ 'tokens' => $tpt, 'srcOffsets' => $this->tsrOffsets() ];
	
}
private function a135($name) {
 return $this->endOffset(); 
}
private function a136($name, $kEndPos) {
 return $this->endOffset(); 
}
private function a137($name, $kEndPos, $vStartPos, $optSp, $tpv) {

			return [
				'kEndPos' => $kEndPos,
				'vStartPos' => $vStartPos,
				'value' => TokenizerUtils::flattenString( [ $optSp, $tpv['tokens'] ?? [] ] ),
			];
		
}
private function a138($name, $val) {

		if ( $val !== null ) {
			if ( $val['value'] !== null ) {
				$so = new KVSourceRange(
					$this->startOffset(), $val['kEndPos'],
					$val['vStartPos'], $this->endOffset()
				);
				return new KV(
					$name,
					TokenizerUtils::flattenIfArray( $val['value'] ),
					$so
				);
			} else {
				return new KV(
					TokenizerUtils::flattenIfArray( $name ),
					'',
					$so
				);
			}
		} else {
			$so = new SourceRange( $this->startOffset(), $this->endOffset() );
			return new KV(
				'',
				TokenizerUtils::flattenIfArray( $name ),
				$so->expandTsrV()
			);
		}
	
}
private function a139() {

		$so = new SourceRange( $this->startOffset(), $this->endOffset() );
		return new KV( '', '', $so->expandTsrV() );
	
}
private function a140($t, $wr) {
 return $wr; 
}
private function a141($r) {

		return TokenizerUtils::flattenStringlist( $r );
	
}
private function a142($startPos, $lt) {

			$tsr = new SourceRange( $startPos, $this->endOffset() );
			$maybeContent = new KV( 'mw:maybeContent', $lt ?? [], $tsr->expandTsrV() );
			$maybeContent->vsrc = substr( $this->input, $startPos, $this->endOffset() - $startPos );
			return $maybeContent;
		
}
private function a143($he) {
 return is_array( $he ) && $he[ 1 ] === "\u{A0}"; 
}
private function a144() {
 return $this->startOffset(); 
}
private function a145($lv0) {
 return $this->env->langConverterEnabled(); 
}
private function a146($lv0, $ff) {

			// if flags contains 'R', then don't treat ; or : specially inside.
			if ( isset( $ff['flags'] ) ) {
				$ff['raw'] = isset( $ff['flags']['R'] ) || isset( $ff['flags']['N'] );
			} elseif ( isset( $ff['variants'] ) ) {
				$ff['raw'] = true;
			}
			return $ff;
		
}
private function a147($lv0) {
 return !$this->env->langConverterEnabled(); 
}
private function a148($lv0) {

			// if language converter not enabled, don't try to parse inside.
			return [ 'raw' => true ];
		
}
private function a149($lv0, $f) {
 return $f['raw']; 
}
private function a150($lv0, $f, $lv) {
 return [ [ 'text' => $lv ] ]; 
}
private function a151($lv0, $f) {
 return !$f['raw']; 
}
private function a152($lv0, $f, $lv) {
 return $lv; 
}
private function a153($lv0, $f, $ts) {
 return $this->endOffset(); 
}
private function a154($lv0, $f, $ts, $lv1) {

		if ( !$this->env->langConverterEnabled() ) {
			return [ '-{', $ts[0]['text']['tokens'], '}-' ];
		}
		$lvsrc = substr( $this->input, $lv0, $lv1 - $lv0 );
		$attribs = [];

		foreach ( $ts as &$t ) {
			// move token strings into KV attributes so that they are
			// properly expanded by early stages of the token pipeline
			foreach ( [ 'text', 'from', 'to' ] as $fld ) {
				if ( !isset( $t[$fld] ) ) {
					continue;
				}
				$name = 'mw:lv' . count( $attribs );
				// Note that AttributeExpander will expect the tokens array to be
				// flattened.  We do that in lang_variant_text / lang_variant_nowiki
				$attribs[] = new KV( $name, $t[$fld]['tokens'], $t[$fld]['srcOffsets']->expandTsrV() );
				$t[$fld] = $name;
			}
		}
		unset( $t );

		$flags = isset( $f['flags'] ) ? array_keys( $f['flags'] ) : [];
		sort( $flags );
		$variants = isset( $f['variants'] ) ? array_keys( $f['variants'] ) : [];
		sort( $variants );

		$dp = new DataParsoid;
		$dp->tsr = new SourceRange( $lv0, $lv1 );
		$dp->src = $lvsrc;
		$dp->flags = $flags;
		$dp->variants = $variants;
		$dp->original = $f['original'];
		$dp->flagSp = $f['sp'];
		$dp->texts = $ts;

		return [
			new SelfclosingTagTk(
				'language-variant',
				$attribs,
				$dp
			)
		];
	
}
private function a155($r, &$preproc) {

		$preproc = null;
		return $r;
	
}
private function a156($thTag, $pp, $tht) {

			// Avoid modifying a cached result
			$tht[0] = clone $tht[0];
			$da = $tht[0]->dataParsoid = clone $tht[0]->dataParsoid;
			$da->tsr = clone $da->tsr;

			$da->stx = 'row';
			$da->tsr->start -= strlen( $pp ); // include "!!" or "||"

			if ( $pp !== '!!' || ( isset( $da->startTagSrc ) && $da->startTagSrc !== $pp ) ) {
				// Variation from default
				$da->startTagSrc = $pp . ( isset( $da->startTagSrc ) ? substr( $da->startTagSrc, 1 ) : '' );
			}
			return $tht;
		
}
private function a157($thTag, $thTags) {

		$thTag[0] = clone $thTag[0];
		$da = $thTag[0]->dataParsoid = clone $thTag[0]->dataParsoid;
		$da->tsr = clone $da->tsr;
		$da->tsr->start--; // include "!"
		array_unshift( $thTags, $thTag );
		return $thTags;
	
}
private function a158($arg) {
 return $this->endOffset(); 
}
private function a159($arg, $tagEndPos, $td) {

		$tsr = new SourceRange( $this->startOffset(), $tagEndPos );
		return TokenizerUtils::buildTableTokens( 'td', '|', $arg,
			$tsr, $this->endOffset(), $td );
	
}
private function a160($pp, $tdt) {

			// Avoid modifying cached dataParsoid object
			$tdt[0] = clone $tdt[0];
			$da = $tdt[0]->dataParsoid = clone $tdt[0]->dataParsoid;
			$da->tsr = clone $da->tsr;

			$da->stx = 'row';
			$da->tsr->start -= strlen( $pp ); // include "||"
			if ( $pp !== '||' || ( isset( $da->startTagSrc ) && $da->startTagSrc !== $pp ) ) {
				// Variation from default
				$da->startTagSrc = $pp . ( isset( $da->startTagSrc ) ? substr( $da->startTagSrc, 1 ) : '' );
			}
			return $tdt;
		
}
private function a161($b) {

		return $b;
	
}
private function a162($il) {

		// il is guaranteed to be an array -- so, tu.flattenIfArray will
		// always return an array
		$r = TokenizerUtils::flattenIfArray( $il );
		if ( count( $r ) === 1 && is_string( $r[0] ) ) {
			$r = $r[0];
		}
		return $r;
	
}
private function a163() {
 return ''; 
}
private function a164($ff) {
 return $ff; 
}
private function a165($f) {

		// Collect & separate flags and variants into a hashtable (by key) and ordered list
		$flags = [];
		$variants = [];
		$flagList = [];
		$flagSpace = [];
		$variantList = [];
		$variantSpace = [];
		$useVariants = false;
		if ( $f !== null ) {
			// lang_variant_flags returns arrays in reverse order.
			$spPtr = count( $f['sp'] ) - 1;
			for ( $i = count( $f['flags'] ) - 1; $i >= 0; $i--) {
				$item = $f['flags'][$i];
				if ( isset( $item['flag'] ) ) {
					$flagSpace[] = $f['sp'][$spPtr--];
					$flags[$item['flag']] = true;
					$flagList[] = $item['flag'];
					$flagSpace[] = $f['sp'][$spPtr--];
				}
				if ( isset( $item['variant'] ) ) {
					$variantSpace[] = $f['sp'][$spPtr--];
					$variants[$item['variant']] = true;
					$variantList[] = $item['variant'];
					$variantSpace[] = $f['sp'][$spPtr--];
				}
			}
			if ( $spPtr >= 0 ) {
				// handle space after a trailing semicolon
				$flagSpace[] = $f['sp'][$spPtr];
				$variantSpace[] = $f['sp'][$spPtr];
			}
		}
		// Parse flags (this logic is from core/languages/ConverterRule.php
		// in the parseFlags() function)
		if ( count( $flags ) === 0 && count( $variants ) === 0 ) {
			$flags['$S'] = true;
		} elseif ( isset( $flags['R'] ) ) {
			$flags = [ 'R' => true ]; // remove other flags
		} elseif ( isset( $flags['N'] ) ) {
			$flags = [ 'N' => true ]; // remove other flags
		} elseif ( isset( $flags['-'] ) ) {
			$flags = [ '-' => true ]; // remove other flags
		} elseif ( isset( $flags['T'] ) && count( $flags ) === 1 ) {
			$flags['H'] = true;
		} elseif ( isset( $flags['H'] ) ) {
			// Replace A flag, and remove other flags except T and D
			$nf = [ '$+' => true, 'H' => true ];
			if ( isset( $flags['T'] ) ) { $nf['T'] = true; }
			if ( isset( $flags['D'] ) ) { $nf['D'] = true; }
			$flags = $nf;
		} elseif ( count( $variants ) > 0 ) {
			$useVariants = true;
		} else {
			if ( isset( $flags['A'] ) ) {
				$flags['$+'] = true;
				$flags['$S'] = true;
			}
			if ( isset( $flags['D'] ) ) {
				unset( $flags['$S'] );
			}
		}
		if ( $useVariants ) {
			return [ 'variants' => $variants, 'original' => $variantList, 'sp' => $variantSpace ];
		} else {
			return [ 'flags' => $flags, 'original' => $flagList, 'sp' => $flagSpace ];
		}
	
}
private function a166($tokens) {

		return [
			'tokens' => TokenizerUtils::flattenStringlist( $tokens ),
			'srcOffsets' => $this->tsrOffsets(),
		];
	
}
private function a167($o, $oo) {
 return $oo; 
}
private function a168($o, $rest, $tr) {

		array_unshift( $rest, $o );
		// if the last bogus option is just spaces, keep them; otherwise
		// drop all this bogus stuff on the ground
		if ( count($tr) > 0 ) {
			$last = $tr[count($tr)-1];
			if (preg_match('/^\s*$/Du', $last[1])) {
				$rest[] = [ 'semi' => true, 'sp' => $last[1] ];
			}
		}
		return $rest;
	
}
private function a169($lvtext) {
 return [ [ 'text' => $lvtext ] ]; 
}
private function a170($arg, $tagEndPos, &$th, $d) {

			if ( $th !== false && strpos( $this->text(), "\n" ) !== false ) {
				// There's been a newline. Remove the break and continue
				// tokenizing nested_block_in_tables.
				$th = false;
			}
			return $d;
		
}
private function a171($arg, $tagEndPos, $c) {

		$tsr = new SourceRange( $this->startOffset(), $tagEndPos );
		return TokenizerUtils::buildTableTokens( 'th', '!', $arg,
			$tsr, $this->endOffset(), $c );
	
}
private function a172($sp1, $f, $sp2, $more) {

		$r = ( $more && $more[1] ) ? $more[1] : [ 'sp' => [], 'flags' => [] ];
		// Note that sp and flags are in reverse order, since we're using
		// right recursion and want to push instead of unshift.
		$r['sp'][] = $sp2;
		$r['sp'][] = $sp1;
		$r['flags'][] = $f;
		return $r;
	
}
private function a173($sp) {

		return [ 'sp' => [ $sp ], 'flags' => [] ];
	
}
private function a174($sp1, $lang, $sp2, $sp3, $lvtext) {

		return [
			'twoway' => true,
			'lang' => $lang,
			'text' => $lvtext,
			'sp' => [ $sp1, $sp2, $sp3 ]
		];
	
}
private function a175($sp1, $from, $sp2, $lang, $sp3, $sp4, $to) {

		return [
			'oneway' => true,
			'from' => $from,
			'lang' => $lang,
			'to' => $to,
			'sp' => [ $sp1, $sp2, $sp3, $sp4 ]
		];
	
}
private function a176($r) {

		return $r;
	
}
private function a177($f) {
 return [ 'flag' => $f ]; 
}
private function a178($v) {
 return [ 'variant' => $v ]; 
}
private function a179($b) {
 return [ 'bogus' => $b ]; /* bad flag */
}
private function a180($n, $sp) {

		$tsr = $this->tsrOffsets();
		$tsr->end -= strlen( $sp );
		return [
			'tokens' => [ $n ],
			'srcOffsets' => $tsr,
		];
	
}
private function a181($extToken) {
 return $extToken; 
}
private function a182($extToken) {

		$txt = Utils::extractExtBody( $extToken );
		return Utils::decodeWtEntities( $txt );
	
}

	// generated
	private function streamstart_async($silence, &$param_preproc) {
  for (;;) {
    // start choice_1
    $p2 = $this->currPos;
    // start seq_1
    $p3 = $this->currPos;
    $this->savedPos = $this->currPos;
    $r4 = $this->a0();
    if ($r4) {
      $r4 = false;
    } else {
      $r4 = self::$FAILED;
      $r1 = self::$FAILED;
      goto seq_1;
    }
    $r5 = $this->parsetlb($silence, $param_preproc);
    // t <- $r5
    if ($r5===self::$FAILED) {
      $this->currPos = $p3;
      $r1 = self::$FAILED;
      goto seq_1;
    }
    $this->savedPos = $this->currPos;
    $r6 = $this->a1($r5);
    if ($r6) {
      $r6 = false;
    } else {
      $r6 = self::$FAILED;
      $this->currPos = $p3;
      $r1 = self::$FAILED;
      goto seq_1;
    }
    $r1 = true;
    seq_1:
    if ($r1!==self::$FAILED) {
      $this->savedPos = $p2;
      $r1 = $this->a2($r5);
      goto choice_1;
    }
    // free $p3
    // start seq_2
    $p3 = $this->currPos;
    $r7 = [];
    for (;;) {
      $r8 = $this->parsenewlineToken($silence);
      if ($r8!==self::$FAILED) {
        $r7[] = $r8;
      } else {
        break;
      }
    }
    // free $r8
    $this->savedPos = $this->currPos;
    $r8 = $this->a3();
    if ($r8) {
      $r8 = false;
    } else {
      $r8 = self::$FAILED;
      $this->currPos = $p3;
      $r1 = self::$FAILED;
      goto seq_2;
    }
    $r1 = [$r7,$r8];
    seq_2:
    // free $p3
    choice_1:
    if ($r1!==self::$FAILED) {
      yield $r1;
    } else {
      if ($this->currPos < $this->inputLength) {
        $this->fail(0);
        throw $this->buildParseException();
      }
      break;
    }
  }
}
private function parsestart($silence, &$param_preproc) {
  $key = json_encode([274, $param_preproc]);
  $bucket = $this->currPos;
  $cached = $this->cache[$bucket][$key] ?? null;
  if ($cached) {
    $this->currPos = $cached->nextPos;
    if ($cached->preproc !== self::$UNDEFINED) { $param_preproc = $cached->preproc; }
    return $cached->result;
  }
  $saved_preproc=$param_preproc;
  $p2 = $this->currPos;
  // start seq_1
  $p3 = $this->currPos;
  $r4 = [];
  for (;;) {
    $r5 = $this->parsetlb(true, $param_preproc);
    if ($r5!==self::$FAILED) {
      $r4[] = $r5;
    } else {
      break;
    }
  }
  // t <- $r4
  // free $r5
  $r5 = [];
  for (;;) {
    $r6 = $this->parsenewlineToken(true);
    if ($r6!==self::$FAILED) {
      $r5[] = $r6;
    } else {
      break;
    }
  }
  // n <- $r5
  // free $r6
  $r1 = true;
  seq_1:
  if ($r1!==self::$FAILED) {
    $this->savedPos = $p2;
    $r1 = $this->a4($r4, $r5);
  } else {
    if (!$silence) {$this->fail(1);}
  }
  // free $p3
  $this->cache[$bucket][$key] = new GrammarCacheEntry(
    $this->currPos,
    $r1,
    $saved_preproc !== $param_preproc ? $param_preproc : self::$UNDEFINED,
    self::$UNDEFINED
  );
  return $r1;
}
private function parsetable_start_tag($silence, $boolParams, &$param_preproc, &$param_th) {
  $key = json_encode([454, $boolParams & 0x3fbf, $param_preproc, $param_th]);
  $bucket = $this->currPos;
  $cached = $this->cache[$bucket][$key] ?? null;
  if ($cached) {
    $this->currPos = $cached->nextPos;
    if ($cached->preproc !== self::$UNDEFINED) { $param_preproc = $cached->preproc; }
    if ($cached->th !== self::$UNDEFINED) { $param_th = $cached->th; }
    return $cached->result;
  }
  $saved_preproc=$param_preproc;
  $saved_th=$param_th;
  $p2 = $this->currPos;
  // start seq_1
  $p3 = $this->currPos;
  $r4 = [];
  for (;;) {
    // start choice_1
    $r5 = $this->parsespace(true);
    if ($r5!==self::$FAILED) {
      goto choice_1;
    }
    $r5 = $this->parsecomment(true);
    choice_1:
    if ($r5!==self::$FAILED) {
      $r4[] = $r5;
    } else {
      break;
    }
  }
  // sc <- $r4
  // free $r5
  $p6 = $this->currPos;
  $r5 = '';
  // startPos <- $r5
  if ($r5!==self::$FAILED) {
    $this->savedPos = $p6;
    $r5 = $this->a5($r4);
  } else {
    $this->currPos = $p3;
    $r1 = self::$FAILED;
    goto seq_1;
  }
  // b <- $r7
  if (($this->input[$this->currPos] ?? null) === "{") {
    $this->currPos++;
    $r7 = "{";
  } else {
    $r7 = self::$FAILED;
    $this->currPos = $p3;
    $r1 = self::$FAILED;
    goto seq_1;
  }
  $r8 = $this->parsepipe(true);
  // p <- $r8
  if ($r8===self::$FAILED) {
    $this->currPos = $p3;
    $r1 = self::$FAILED;
    goto seq_1;
  }
  // start choice_2
  $r9 = $this->parsetable_attributes(true, $boolParams & ~0x40, $param_preproc, $param_th);
  if ($r9!==self::$FAILED) {
    goto choice_2;
  }
  $this->savedPos = $this->currPos;
  $r9 = $this->a6($r4, $r5, $r7, $r8);
  if ($r9) {
    $r9 = false;
  } else {
    $r9 = self::$FAILED;
  }
  choice_2:
  // ta <- $r9
  if ($r9===self::$FAILED) {
    $this->currPos = $p3;
    $r1 = self::$FAILED;
    goto seq_1;
  }
  $p11 = $this->currPos;
  $r10 = '';
  // tsEndPos <- $r10
  if ($r10!==self::$FAILED) {
    $this->savedPos = $p11;
    $r10 = $this->a7($r4, $r5, $r7, $r8, $r9);
  } else {
    $this->currPos = $p3;
    $r1 = self::$FAILED;
    goto seq_1;
  }
  $r12 = [];
  for (;;) {
    $r13 = $this->parsespace(true);
    if ($r13!==self::$FAILED) {
      $r12[] = $r13;
    } else {
      break;
    }
  }
  // s2 <- $r12
  // free $r13
  $r1 = true;
  seq_1:
  if ($r1!==self::$FAILED) {
    $this->savedPos = $p2;
    $r1 = $this->a8($r4, $r5, $r7, $r8, $r9, $r10, $r12);
  } else {
    if (!$silence) {$this->fail(2);}
  }
  // free $p3
  $this->cache[$bucket][$key] = new GrammarCacheEntry(
    $this->currPos,
    $r1,
    $saved_preproc !== $param_preproc ? $param_preproc : self::$UNDEFINED,
    $saved_th !== $param_th ? $param_th : self::$UNDEFINED
  );
  return $r1;
}
private function parseurl($silence, &$param_preproc) {
  $key = json_encode([328, $param_preproc]);
  $bucket = $this->currPos;
  $cached = $this->cache[$bucket][$key] ?? null;
  if ($cached) {
    $this->currPos = $cached->nextPos;
    if ($cached->preproc !== self::$UNDEFINED) { $param_preproc = $cached->preproc; }
    return $cached->result;
  }
  $saved_preproc=$param_preproc;
  $p2 = $this->currPos;
  // start seq_1
  $p3 = $this->currPos;
  $r4 = $this->parseurl_protocol($silence);
  // proto <- $r4
  if ($r4===self::$FAILED) {
    $r1 = self::$FAILED;
    goto seq_1;
  }
  // start choice_1
  $r5 = $this->parseipv6urladdr($silence);
  if ($r5!==self::$FAILED) {
    goto choice_1;
  }
  $r5 = '';
  choice_1:
  // addr <- $r5
  if ($r5===self::$FAILED) {
    $this->currPos = $p3;
    $r1 = self::$FAILED;
    goto seq_1;
  }
  $r6 = [];
  for (;;) {
    $p8 = $this->currPos;
    // start seq_2
    $p9 = $this->currPos;
    $p10 = $this->currPos;
    $r11 = $this->discardinline_breaks(true, 0x0, $param_preproc, self::newRef(null));
    if ($r11 === self::$FAILED) {
      $r11 = false;
    } else {
      $r11 = self::$FAILED;
      $this->currPos = $p10;
      $r7 = self::$FAILED;
      goto seq_2;
    }
    // free $p10
    // start choice_2
    $r12 = $this->parseno_punctuation_char($silence);
    if ($r12!==self::$FAILED) {
      goto choice_2;
    }
    $r12 = $this->parsecomment($silence);
    if ($r12!==self::$FAILED) {
      goto choice_2;
    }
    $r12 = $this->parsetplarg_or_template($silence, 0x0, self::newRef(null), $param_preproc);
    if ($r12!==self::$FAILED) {
      goto choice_2;
    }
    $r12 = $this->input[$this->currPos] ?? '';
    if ($r12 === "'" || $r12 === "{") {
      $this->currPos++;
      goto choice_2;
    } else {
      $r12 = self::$FAILED;
      if (!$silence) {$this->fail(3);}
    }
    $p10 = $this->currPos;
    // start seq_3
    $p13 = $this->currPos;
    $p14 = $this->currPos;
    // start seq_4
    $p16 = $this->currPos;
    if (($this->input[$this->currPos] ?? null) === "&") {
      $this->currPos++;
      $r17 = "&";
    } else {
      $r17 = self::$FAILED;
      $r15 = self::$FAILED;
      goto seq_4;
    }
    // start choice_3
    // start seq_5
    $p19 = $this->currPos;
    $r20 = $this->input[$this->currPos] ?? '';
    if ($r20 === "l" || $r20 === "L") {
      $this->currPos++;
    } else {
      $r20 = self::$FAILED;
      $r18 = self::$FAILED;
      goto seq_5;
    }
    $r21 = $this->input[$this->currPos] ?? '';
    if ($r21 === "t" || $r21 === "T") {
      $this->currPos++;
    } else {
      $r21 = self::$FAILED;
      $this->currPos = $p19;
      $r18 = self::$FAILED;
      goto seq_5;
    }
    $r18 = true;
    seq_5:
    if ($r18!==self::$FAILED) {
      goto choice_3;
    }
    // free $p19
    // start seq_6
    $p19 = $this->currPos;
    $r22 = $this->input[$this->currPos] ?? '';
    if ($r22 === "g" || $r22 === "G") {
      $this->currPos++;
    } else {
      $r22 = self::$FAILED;
      $r18 = self::$FAILED;
      goto seq_6;
    }
    $r23 = $this->input[$this->currPos] ?? '';
    if ($r23 === "t" || $r23 === "T") {
      $this->currPos++;
    } else {
      $r23 = self::$FAILED;
      $this->currPos = $p19;
      $r18 = self::$FAILED;
      goto seq_6;
    }
    $r18 = true;
    seq_6:
    // free $p19
    choice_3:
    if ($r18===self::$FAILED) {
      $this->currPos = $p16;
      $r15 = self::$FAILED;
      goto seq_4;
    }
    if (($this->input[$this->currPos] ?? null) === ";") {
      $this->currPos++;
      $r24 = ";";
    } else {
      $r24 = self::$FAILED;
      $this->currPos = $p16;
      $r15 = self::$FAILED;
      goto seq_4;
    }
    $r15 = true;
    seq_4:
    // free $p16
    if ($r15 === self::$FAILED) {
      $r15 = false;
    } else {
      $r15 = self::$FAILED;
      $this->currPos = $p14;
      $r12 = self::$FAILED;
      goto seq_3;
    }
    // free $p14
    // start choice_4
    $p14 = $this->currPos;
    // start seq_7
    $p16 = $this->currPos;
    $p19 = $this->currPos;
    if (($this->input[$this->currPos] ?? null) === "&") {
      $this->currPos++;
      $r26 = "&";
      $r26 = false;
      $this->currPos = $p19;
    } else {
      $r26 = self::$FAILED;
      $r25 = self::$FAILED;
      goto seq_7;
    }
    // free $p19
    $r27 = $this->parsehtmlentity($silence);
    // he <- $r27
    if ($r27===self::$FAILED) {
      $this->currPos = $p16;
      $r25 = self::$FAILED;
      goto seq_7;
    }
    $r25 = true;
    seq_7:
    if ($r25!==self::$FAILED) {
      $this->savedPos = $p14;
      $r25 = $this->a9($r4, $r5, $r27);
      goto choice_4;
    }
    // free $p16
    if (($this->input[$this->currPos] ?? null) === "&") {
      $this->currPos++;
      $r25 = "&";
    } else {
      if (!$silence) {$this->fail(4);}
      $r25 = self::$FAILED;
    }
    choice_4:
    // r <- $r25
    if ($r25===self::$FAILED) {
      $this->currPos = $p13;
      $r12 = self::$FAILED;
      goto seq_3;
    }
    $r12 = true;
    seq_3:
    if ($r12!==self::$FAILED) {
      $this->savedPos = $p10;
      $r12 = $this->a10($r4, $r5, $r25);
    }
    // free $p13
    choice_2:
    // c <- $r12
    if ($r12===self::$FAILED) {
      $this->currPos = $p9;
      $r7 = self::$FAILED;
      goto seq_2;
    }
    $r7 = true;
    seq_2:
    if ($r7!==self::$FAILED) {
      $this->savedPos = $p8;
      $r7 = $this->a11($r4, $r5, $r12);
      $r6[] = $r7;
    } else {
      break;
    }
    // free $p9
  }
  // path <- $r6
  // free $r7
  $this->savedPos = $this->currPos;
  $r7 = $this->a12($r4, $r5, $r6);
  if ($r7) {
    $r7 = false;
  } else {
    $r7 = self::$FAILED;
    $this->currPos = $p3;
    $r1 = self::$FAILED;
    goto seq_1;
  }
  $r1 = true;
  seq_1:
  if ($r1!==self::$FAILED) {
    $this->savedPos = $p2;
    $r1 = $this->a13($r4, $r5, $r6);
  }
  // free $p3
  $this->cache[$bucket][$key] = new GrammarCacheEntry(
    $this->currPos,
    $r1,
    $saved_preproc !== $param_preproc ? $param_preproc : self::$UNDEFINED,
    self::$UNDEFINED
  );
  return $r1;
}
private function parserow_syntax_table_args($silence, $boolParams, &$param_preproc, &$param_th) {
  $key = json_encode([474, $boolParams & 0x3eff, $param_preproc, $param_th]);
  $bucket = $this->currPos;
  $cached = $this->cache[$bucket][$key] ?? null;
  if ($cached) {
    $this->currPos = $cached->nextPos;
    if ($cached->preproc !== self::$UNDEFINED) { $param_preproc = $cached->preproc; }
    if ($cached->th !== self::$UNDEFINED) { $param_th = $cached->th; }
    return $cached->result;
  }
  $saved_preproc=$param_preproc;
  $saved_th=$param_th;
  $p2 = $this->currPos;
  // start seq_1
  $p3 = $this->currPos;
  $r4 = $this->parsetable_attributes($silence, $boolParams | 0x100, $param_preproc, $param_th);
  // as <- $r4
  if ($r4===self::$FAILED) {
    $r1 = self::$FAILED;
    goto seq_1;
  }
  $r5 = $this->parseoptional_spaces($silence);
  // s <- $r5
  if ($r5===self::$FAILED) {
    $this->currPos = $p3;
    $r1 = self::$FAILED;
    goto seq_1;
  }
  $r6 = $this->parsepipe($silence);
  // p <- $r6
  if ($r6===self::$FAILED) {
    $this->currPos = $p3;
    $r1 = self::$FAILED;
    goto seq_1;
  }
  $p7 = $this->currPos;
  $r8 = $this->discardpipe(true);
  if ($r8 === self::$FAILED) {
    $r8 = false;
  } else {
    $r8 = self::$FAILED;
    $this->currPos = $p7;
    $this->currPos = $p3;
    $r1 = self::$FAILED;
    goto seq_1;
  }
  // free $p7
  $r1 = true;
  seq_1:
  if ($r1!==self::$FAILED) {
    $this->savedPos = $p2;
    $r1 = $this->a14($r4, $r5, $r6);
  }
  // free $p3
  $this->cache[$bucket][$key] = new GrammarCacheEntry(
    $this->currPos,
    $r1,
    $saved_preproc !== $param_preproc ? $param_preproc : self::$UNDEFINED,
    $saved_th !== $param_th ? $param_th : self::$UNDEFINED
  );
  return $r1;
}
private function parsetable_attributes($silence, $boolParams, &$param_preproc, &$param_th) {
  $key = json_encode([278, $boolParams & 0x3fff, $param_preproc, $param_th]);
  $bucket = $this->currPos;
  $cached = $this->cache[$bucket][$key] ?? null;
  if ($cached) {
    $this->currPos = $cached->nextPos;
    if ($cached->preproc !== self::$UNDEFINED) { $param_preproc = $cached->preproc; }
    if ($cached->th !== self::$UNDEFINED) { $param_th = $cached->th; }
    return $cached->result;
  }
  $saved_preproc=$param_preproc;
  $saved_th=$param_th;
  $r1 = [];
  for (;;) {
    // start choice_1
    $r2 = $this->parsetable_attribute(true, $boolParams, $param_preproc, $param_th);
    if ($r2!==self::$FAILED) {
      goto choice_1;
    }
    $p3 = $this->currPos;
    // start seq_1
    $p4 = $this->currPos;
    $r5 = $this->discardoptionalSpaceToken(true);
    if ($r5===self::$FAILED) {
      $r2 = self::$FAILED;
      goto seq_1;
    }
    $r6 = $this->parsebroken_table_attribute_name_char(true);
    // b <- $r6
    if ($r6===self::$FAILED) {
      $this->currPos = $p4;
      $r2 = self::$FAILED;
      goto seq_1;
    }
    $r2 = true;
    seq_1:
    if ($r2!==self::$FAILED) {
      $this->savedPos = $p3;
      $r2 = $this->a15($r6);
    }
    // free $p4
    choice_1:
    if ($r2!==self::$FAILED) {
      $r1[] = $r2;
    } else {
      break;
    }
  }
  // free $r2
  $this->cache[$bucket][$key] = new GrammarCacheEntry(
    $this->currPos,
    $r1,
    $saved_preproc !== $param_preproc ? $param_preproc : self::$UNDEFINED,
    $saved_th !== $param_th ? $param_th : self::$UNDEFINED
  );
  return $r1;
}
private function parsegeneric_newline_attributes($silence, $boolParams, &$param_preproc, &$param_th) {
  $key = json_encode([276, $boolParams & 0x3fff, $param_preproc, $param_th]);
  $bucket = $this->currPos;
  $cached = $this->cache[$bucket][$key] ?? null;
  if ($cached) {
    $this->currPos = $cached->nextPos;
    if ($cached->preproc !== self::$UNDEFINED) { $param_preproc = $cached->preproc; }
    if ($cached->th !== self::$UNDEFINED) { $param_th = $cached->th; }
    return $cached->result;
  }
  $saved_preproc=$param_preproc;
  $saved_th=$param_th;
  $r1 = [];
  for (;;) {
    $r2 = $this->parsegeneric_newline_attribute(true, $boolParams, $param_preproc, $param_th);
    if ($r2!==self::$FAILED) {
      $r1[] = $r2;
    } else {
      break;
    }
  }
  // free $r2
  $this->cache[$bucket][$key] = new GrammarCacheEntry(
    $this->currPos,
    $r1,
    $saved_preproc !== $param_preproc ? $param_preproc : self::$UNDEFINED,
    $saved_th !== $param_th ? $param_th : self::$UNDEFINED
  );
  return $r1;
}
private function parsetplarg_or_template_or_bust($silence, &$param_preproc) {
  $key = json_encode([338, $param_preproc]);
  $bucket = $this->currPos;
  $cached = $this->cache[$bucket][$key] ?? null;
  if ($cached) {
    $this->currPos = $cached->nextPos;
    if ($cached->preproc !== self::$UNDEFINED) { $param_preproc = $cached->preproc; }
    return $cached->result;
  }
  $saved_preproc=$param_preproc;
  $p2 = $this->currPos;
  $r3 = [];
  for (;;) {
    // start choice_1
    $r4 = $this->parsetplarg_or_template($silence, 0x0, self::newRef(null), $param_preproc);
    if ($r4!==self::$FAILED) {
      goto choice_1;
    }
    if ($this->currPos < $this->inputLength) {
      $r4 = self::consumeChar($this->input, $this->currPos);;
    } else {
      $r4 = self::$FAILED;
      if (!$silence) {$this->fail(7);}
    }
    choice_1:
    if ($r4!==self::$FAILED) {
      $r3[] = $r4;
    } else {
      break;
    }
  }
  if (count($r3) === 0) {
    $r3 = self::$FAILED;
  }
  // r <- $r3
  // free $r4
  $r1 = $r3;
  if ($r1!==self::$FAILED) {
    $this->savedPos = $p2;
    $r1 = $this->a16($r3);
  }
  $this->cache[$bucket][$key] = new GrammarCacheEntry(
    $this->currPos,
    $r1,
    $saved_preproc !== $param_preproc ? $param_preproc : self::$UNDEFINED,
    self::$UNDEFINED
  );
  return $r1;
}
private function parseextlink($silence, $boolParams, &$param_preproc, &$param_th) {
  $key = json_encode([318, $boolParams & 0x3fff, $param_preproc, $param_th]);
  $bucket = $this->currPos;
  $cached = $this->cache[$bucket][$key] ?? null;
  if ($cached) {
    $this->currPos = $cached->nextPos;
    if ($cached->preproc !== self::$UNDEFINED) { $param_preproc = $cached->preproc; }
    if ($cached->th !== self::$UNDEFINED) { $param_th = $cached->th; }
    return $cached->result;
  }
  $saved_preproc=$param_preproc;
  $saved_th=$param_th;
  $p2 = $this->currPos;
  // start seq_1
  $p3 = $this->currPos;
  if (!(/*extlink*/($boolParams & 0x8) !== 0)) {
    $r4 = false;
  } else {
    $r4 = self::$FAILED;
    $r1 = self::$FAILED;
    goto seq_1;
  }
  $p6 = $this->currPos;
  // start seq_2
  $p7 = $this->currPos;
  if (($this->input[$this->currPos] ?? null) === "[") {
    $this->currPos++;
    $r8 = "[";
  } else {
    $r8 = self::$FAILED;
    $r5 = self::$FAILED;
    goto seq_2;
  }
  $p10 = $this->currPos;
  $r9 = '';
  // p0 <- $r9
  if ($r9!==self::$FAILED) {
    $this->savedPos = $p10;
    $r9 = $this->a17();
  } else {
    $this->currPos = $p7;
    $r5 = self::$FAILED;
    goto seq_2;
  }
  $p12 = $this->currPos;
  // start seq_3
  $p13 = $this->currPos;
  // start choice_1
  // start seq_4
  $p15 = $this->currPos;
  $r16 = $this->parseurl_protocol(true);
  if ($r16===self::$FAILED) {
    $r14 = self::$FAILED;
    goto seq_4;
  }
  $r17 = $this->parseipv6urladdr(true);
  if ($r17===self::$FAILED) {
    $this->currPos = $p15;
    $r14 = self::$FAILED;
    goto seq_4;
  }
  $r14 = [$r16,$r17];
  seq_4:
  if ($r14!==self::$FAILED) {
    goto choice_1;
  }
  // free $p15
  $r14 = '';
  choice_1:
  // addr <- $r14
  if ($r14===self::$FAILED) {
    $r11 = self::$FAILED;
    goto seq_3;
  }
  // start choice_2
  $r18 = $this->parseextlink_nonipv6url(true, $boolParams | 0x8, $param_preproc, $param_th);
  if ($r18!==self::$FAILED) {
    goto choice_2;
  }
  $r18 = '';
  choice_2:
  // target <- $r18
  if ($r18===self::$FAILED) {
    $this->currPos = $p13;
    $r11 = self::$FAILED;
    goto seq_3;
  }
  $r11 = true;
  seq_3:
  // flat <- $r11
  if ($r11!==self::$FAILED) {
    $this->savedPos = $p12;
    $r11 = $this->a18($r9, $r14, $r18);
  } else {
    $this->currPos = $p7;
    $r5 = self::$FAILED;
    goto seq_2;
  }
  // free $p13
  $this->savedPos = $this->currPos;
  $r19 = $this->a19($r9, $r11);
  if ($r19) {
    $r19 = false;
  } else {
    $r19 = self::$FAILED;
    $this->currPos = $p7;
    $r5 = self::$FAILED;
    goto seq_2;
  }
  $p13 = $this->currPos;
  $r20 = '';
  // p1 <- $r20
  if ($r20!==self::$FAILED) {
    $this->savedPos = $p13;
    $r20 = $this->a20($r9, $r11);
  } else {
    $this->currPos = $p7;
    $r5 = self::$FAILED;
    goto seq_2;
  }
  $p15 = $this->currPos;
  for (;;) {
    // start choice_3
    $r22 = $this->discardspace(true);
    if ($r22!==self::$FAILED) {
      goto choice_3;
    }
    $r22 = $this->discardunispace(true);
    choice_3:
    if ($r22===self::$FAILED) {
      break;
    }
  }
  // free $r22
  $r21 = true;
  // sp <- $r21
  if ($r21!==self::$FAILED) {
    $r21 = substr($this->input, $p15, $this->currPos - $p15);
  } else {
    $r21 = self::$FAILED;
    $this->currPos = $p7;
    $r5 = self::$FAILED;
    goto seq_2;
  }
  // free $p15
  $p15 = $this->currPos;
  $r22 = '';
  // p2 <- $r22
  if ($r22!==self::$FAILED) {
    $this->savedPos = $p15;
    $r22 = $this->a21($r9, $r11, $r20, $r21);
  } else {
    $this->currPos = $p7;
    $r5 = self::$FAILED;
    goto seq_2;
  }
  $r23 = $this->parseinlineline(true, $boolParams | 0x8, $param_preproc, $param_th);
  if ($r23===self::$FAILED) {
    $r23 = null;
  }
  // content <- $r23
  $p25 = $this->currPos;
  $r24 = '';
  // p3 <- $r24
  if ($r24!==self::$FAILED) {
    $this->savedPos = $p25;
    $r24 = $this->a22($r9, $r11, $r20, $r21, $r22, $r23);
  } else {
    $this->currPos = $p7;
    $r5 = self::$FAILED;
    goto seq_2;
  }
  if (($this->input[$this->currPos] ?? null) === "]") {
    $this->currPos++;
    $r26 = "]";
  } else {
    $r26 = self::$FAILED;
    $this->currPos = $p7;
    $r5 = self::$FAILED;
    goto seq_2;
  }
  $r5 = true;
  seq_2:
  // r <- $r5
  if ($r5!==self::$FAILED) {
    $this->savedPos = $p6;
    $r5 = $this->a23($r9, $r11, $r20, $r21, $r22, $r23, $r24);
  } else {
    $this->currPos = $p3;
    $r1 = self::$FAILED;
    goto seq_1;
  }
  // free $p7
  $r1 = true;
  seq_1:
  if ($r1!==self::$FAILED) {
    $this->savedPos = $p2;
    $r1 = $this->a24($r5);
  } else {
    if (!$silence) {$this->fail(8);}
  }
  // free $p3
  $this->cache[$bucket][$key] = new GrammarCacheEntry(
    $this->currPos,
    $r1,
    $saved_preproc !== $param_preproc ? $param_preproc : self::$UNDEFINED,
    $saved_th !== $param_th ? $param_th : self::$UNDEFINED
  );
  return $r1;
}
private function parselist_item($silence, $boolParams, &$param_preproc, &$param_th) {
  $key = json_encode([434, $boolParams & 0x3fff, $param_preproc, $param_th]);
  $bucket = $this->currPos;
  $cached = $this->cache[$bucket][$key] ?? null;
  if ($cached) {
    $this->currPos = $cached->nextPos;
    if ($cached->preproc !== self::$UNDEFINED) { $param_preproc = $cached->preproc; }
    if ($cached->th !== self::$UNDEFINED) { $param_th = $cached->th; }
    return $cached->result;
  }
  $saved_preproc=$param_preproc;
  $saved_th=$param_th;
  // start choice_1
  $r1 = $this->parsedtdd($silence, $boolParams, $param_preproc, $param_th);
  if ($r1!==self::$FAILED) {
    goto choice_1;
  }
  $r1 = $this->parsehacky_dl_uses($silence, $boolParams, $param_preproc, $param_th);
  if ($r1!==self::$FAILED) {
    goto choice_1;
  }
  $r1 = $this->parseli($silence, $boolParams, $param_preproc, $param_th);
  choice_1:
  $this->cache[$bucket][$key] = new GrammarCacheEntry(
    $this->currPos,
    $r1,
    $saved_preproc !== $param_preproc ? $param_preproc : self::$UNDEFINED,
    $saved_th !== $param_th ? $param_th : self::$UNDEFINED
  );
  return $r1;
}
private function parsetlb($silence, &$param_preproc) {
  $key = json_encode([282, $param_preproc]);
  $bucket = $this->currPos;
  $cached = $this->cache[$bucket][$key] ?? null;
  if ($cached) {
    $this->currPos = $cached->nextPos;
    if ($cached->preproc !== self::$UNDEFINED) { $param_preproc = $cached->preproc; }
    return $cached->result;
  }
  $saved_preproc=$param_preproc;
  $p2 = $this->currPos;
  // start seq_1
  $p3 = $this->currPos;
  $p4 = $this->currPos;
  $r5 = $this->discardeof(true);
  if ($r5 === self::$FAILED) {
    $r5 = false;
  } else {
    $r5 = self::$FAILED;
    $this->currPos = $p4;
    $r1 = self::$FAILED;
    goto seq_1;
  }
  // free $p4
  $r6 = $this->parseblock(true, 0x0, self::newRef(null), $param_preproc);
  // b <- $r6
  if ($r6===self::$FAILED) {
    $this->currPos = $p3;
    $r1 = self::$FAILED;
    goto seq_1;
  }
  $r1 = true;
  seq_1:
  if ($r1!==self::$FAILED) {
    $this->savedPos = $p2;
    $r1 = $this->a25($r6);
  } else {
    if (!$silence) {$this->fail(9);}
  }
  // free $p3
  $this->cache[$bucket][$key] = new GrammarCacheEntry(
    $this->currPos,
    $r1,
    $saved_preproc !== $param_preproc ? $param_preproc : self::$UNDEFINED,
    self::$UNDEFINED
  );
  return $r1;
}
private function parsenewlineToken($silence) {
  $key = 520;
  $bucket = $this->currPos;
  $cached = $this->cache[$bucket][$key] ?? null;
  if ($cached) {
    $this->currPos = $cached->nextPos;

    return $cached->result;
  }

  $p2 = $this->currPos;
  $r1 = $this->discardnewline($silence);
  if ($r1!==self::$FAILED) {
    $this->savedPos = $p2;
    $r1 = $this->a26();
  }
  $this->cache[$bucket][$key] = new GrammarCacheEntry(
    $this->currPos,
    $r1,
    self::$UNDEFINED,
    self::$UNDEFINED
  );
  return $r1;
}
private function parsespace($silence) {
  $key = 510;
  $bucket = $this->currPos;
  $cached = $this->cache[$bucket][$key] ?? null;
  if ($cached) {
    $this->currPos = $cached->nextPos;

    return $cached->result;
  }

  $r1 = $this->input[$this->currPos] ?? '';
  if ($r1 === " " || $r1 === "\x09") {
    $this->currPos++;
  } else {
    $r1 = self::$FAILED;
    if (!$silence) {$this->fail(10);}
  }
  $this->cache[$bucket][$key] = new GrammarCacheEntry(
    $this->currPos,
    $r1,
    self::$UNDEFINED,
    self::$UNDEFINED
  );
  return $r1;
}
private function parsecomment($silence) {
  $key = 524;
  $bucket = $this->currPos;
  $cached = $this->cache[$bucket][$key] ?? null;
  if ($cached) {
    $this->currPos = $cached->nextPos;

    return $cached->result;
  }

  $p2 = $this->currPos;
  // start seq_1
  $p3 = $this->currPos;
  if ($this->currPos >= $this->inputLength ? false : substr_compare($this->input, "<!--", $this->currPos, 4, false) === 0) {
    $r4 = "<!--";
    $this->currPos += 4;
  } else {
    if (!$silence) {$this->fail(11);}
    $r4 = self::$FAILED;
    $r1 = self::$FAILED;
    goto seq_1;
  }
  $p6 = $this->currPos;
  for (;;) {
    // start seq_2
    $p8 = $this->currPos;
    $p9 = $this->currPos;
    if ($this->currPos >= $this->inputLength ? false : substr_compare($this->input, "-->", $this->currPos, 3, false) === 0) {
      $r10 = "-->";
      $this->currPos += 3;
    } else {
      $r10 = self::$FAILED;
    }
    if ($r10 === self::$FAILED) {
      $r10 = false;
    } else {
      $r10 = self::$FAILED;
      $this->currPos = $p9;
      $r7 = self::$FAILED;
      goto seq_2;
    }
    // free $p9
    if ($this->currPos < $this->inputLength) {
      $r11 = self::consumeChar($this->input, $this->currPos);;
    } else {
      $r11 = self::$FAILED;
      if (!$silence) {$this->fail(7);}
      $this->currPos = $p8;
      $r7 = self::$FAILED;
      goto seq_2;
    }
    $r7 = true;
    seq_2:
    if ($r7===self::$FAILED) {
      break;
    }
    // free $p8
  }
  // free $r7
  $r5 = true;
  // c <- $r5
  if ($r5!==self::$FAILED) {
    $r5 = substr($this->input, $p6, $this->currPos - $p6);
  } else {
    $r5 = self::$FAILED;
    $this->currPos = $p3;
    $r1 = self::$FAILED;
    goto seq_1;
  }
  // free $p6
  $p6 = $this->currPos;
  // start choice_1
  if ($this->currPos >= $this->inputLength ? false : substr_compare($this->input, "-->", $this->currPos, 3, false) === 0) {
    $r7 = "-->";
    $this->currPos += 3;
    goto choice_1;
  } else {
    if (!$silence) {$this->fail(12);}
    $r7 = self::$FAILED;
  }
  $r7 = $this->discardeof($silence);
  choice_1:
  // cEnd <- $r7
  if ($r7!==self::$FAILED) {
    $r7 = substr($this->input, $p6, $this->currPos - $p6);
  } else {
    $r7 = self::$FAILED;
    $this->currPos = $p3;
    $r1 = self::$FAILED;
    goto seq_1;
  }
  // free $p6
  $r1 = true;
  seq_1:
  if ($r1!==self::$FAILED) {
    $this->savedPos = $p2;
    $r1 = $this->a27($r5, $r7);
  }
  // free $p3
  $this->cache[$bucket][$key] = new GrammarCacheEntry(
    $this->currPos,
    $r1,
    self::$UNDEFINED,
    self::$UNDEFINED
  );
  return $r1;
}
private function parsepipe($silence) {
  $key = 506;
  $bucket = $this->currPos;
  $cached = $this->cache[$bucket][$key] ?? null;
  if ($cached) {
    $this->currPos = $cached->nextPos;

    return $cached->result;
  }

  // start choice_1
  if (($this->input[$this->currPos] ?? null) === "|") {
    $this->currPos++;
    $r1 = "|";
    goto choice_1;
  } else {
    if (!$silence) {$this->fail(13);}
    $r1 = self::$FAILED;
  }
  if ($this->currPos >= $this->inputLength ? false : substr_compare($this->input, "{{!}}", $this->currPos, 5, false) === 0) {
    $r1 = "{{!}}";
    $this->currPos += 5;
  } else {
    if (!$silence) {$this->fail(14);}
    $r1 = self::$FAILED;
  }
  choice_1:
  $this->cache[$bucket][$key] = new GrammarCacheEntry(
    $this->currPos,
    $r1,
    self::$UNDEFINED,
    self::$UNDEFINED
  );
  return $r1;
}
private function parseurl_protocol($silence) {
  $key = 324;
  $bucket = $this->currPos;
  $cached = $this->cache[$bucket][$key] ?? null;
  if ($cached) {
    $this->currPos = $cached->nextPos;

    return $cached->result;
  }

  $p2 = $this->currPos;
  // start seq_1
  $p3 = $this->currPos;
  $p5 = $this->currPos;
  // start choice_1
  if ($this->currPos >= $this->inputLength ? false : substr_compare($this->input, "//", $this->currPos, 2, false) === 0) {
    $r4 = "//";
    $this->currPos += 2;
    goto choice_1;
  } else {
    if (!$silence) {$this->fail(15);}
    $r4 = self::$FAILED;
  }
  // start seq_2
  $p6 = $this->currPos;
  $r7 = $this->input[$this->currPos] ?? '';
  if (preg_match("/^[A-Za-z]/", $r7)) {
    $this->currPos++;
  } else {
    $r7 = self::$FAILED;
    if (!$silence) {$this->fail(16);}
    $r4 = self::$FAILED;
    goto seq_2;
  }
  for (;;) {
    $r9 = $this->input[$this->currPos] ?? '';
    if (preg_match("/^[\\-A-Za-z0-9+.]/", $r9)) {
      $this->currPos++;
    } else {
      $r9 = self::$FAILED;
      if (!$silence) {$this->fail(17);}
      break;
    }
  }
  // free $r9
  $r8 = true;
  if ($r8===self::$FAILED) {
    $this->currPos = $p6;
    $r4 = self::$FAILED;
    goto seq_2;
  }
  // free $r8
  if (($this->input[$this->currPos] ?? null) === ":") {
    $this->currPos++;
    $r8 = ":";
  } else {
    if (!$silence) {$this->fail(18);}
    $r8 = self::$FAILED;
    $this->currPos = $p6;
    $r4 = self::$FAILED;
    goto seq_2;
  }
  if ($this->currPos >= $this->inputLength ? false : substr_compare($this->input, "//", $this->currPos, 2, false) === 0) {
    $r9 = "//";
    $this->currPos += 2;
  } else {
    if (!$silence) {$this->fail(15);}
    $r9 = self::$FAILED;
    $r9 = null;
  }
  $r4 = true;
  seq_2:
  // free $p6
  choice_1:
  // p <- $r4
  if ($r4!==self::$FAILED) {
    $r4 = substr($this->input, $p5, $this->currPos - $p5);
  } else {
    $r4 = self::$FAILED;
    $r1 = self::$FAILED;
    goto seq_1;
  }
  // free $p5
  $this->savedPos = $this->currPos;
  $r10 = $this->a28($r4);
  if ($r10) {
    $r10 = false;
  } else {
    $r10 = self::$FAILED;
    $this->currPos = $p3;
    $r1 = self::$FAILED;
    goto seq_1;
  }
  $r1 = true;
  seq_1:
  if ($r1!==self::$FAILED) {
    $this->savedPos = $p2;
    $r1 = $this->a29($r4);
  }
  // free $p3
  $this->cache[$bucket][$key] = new GrammarCacheEntry(
    $this->currPos,
    $r1,
    self::$UNDEFINED,
    self::$UNDEFINED
  );
  return $r1;
}
private function parseipv6urladdr($silence) {
  $key = 332;
  $bucket = $this->currPos;
  $cached = $this->cache[$bucket][$key] ?? null;
  if ($cached) {
    $this->currPos = $cached->nextPos;

    return $cached->result;
  }

  $p1 = $this->currPos;
  // start seq_1
  $p3 = $this->currPos;
  if (($this->input[$this->currPos] ?? null) === "[") {
    $this->currPos++;
    $r4 = "[";
  } else {
    if (!$silence) {$this->fail(19);}
    $r4 = self::$FAILED;
    $r2 = self::$FAILED;
    goto seq_1;
  }
  $r5 = self::$FAILED;
  for (;;) {
    $r6 = $this->input[$this->currPos] ?? '';
    if (preg_match("/^[0-9A-Fa-f:.]/", $r6)) {
      $this->currPos++;
      $r5 = true;
    } else {
      $r6 = self::$FAILED;
      if (!$silence) {$this->fail(20);}
      break;
    }
  }
  if ($r5===self::$FAILED) {
    $this->currPos = $p3;
    $r2 = self::$FAILED;
    goto seq_1;
  }
  // free $r6
  if (($this->input[$this->currPos] ?? null) === "]") {
    $this->currPos++;
    $r6 = "]";
  } else {
    if (!$silence) {$this->fail(21);}
    $r6 = self::$FAILED;
    $this->currPos = $p3;
    $r2 = self::$FAILED;
    goto seq_1;
  }
  $r2 = true;
  seq_1:
  if ($r2!==self::$FAILED) {
    $r2 = substr($this->input, $p1, $this->currPos - $p1);
  } else {
    $r2 = self::$FAILED;
  }
  // free $p3
  // free $p1
  $this->cache[$bucket][$key] = new GrammarCacheEntry(
    $this->currPos,
    $r2,
    self::$UNDEFINED,
    self::$UNDEFINED
  );
  return $r2;
}
private function discardinline_breaks($silence, $boolParams, &$param_preproc, &$param_th) {
  $key = json_encode([303, $boolParams & 0x1ffe, $param_preproc, $param_th]);
  $bucket = $this->currPos;
  $cached = $this->cache[$bucket][$key] ?? null;
  if ($cached) {
    $this->currPos = $cached->nextPos;
    if ($cached->preproc !== self::$UNDEFINED) { $param_preproc = $cached->preproc; }
    if ($cached->th !== self::$UNDEFINED) { $param_th = $cached->th; }
    return $cached->result;
  }
  $saved_preproc=$param_preproc;
  $saved_th=$param_th;
  // start seq_1
  $p1 = $this->currPos;
  $p3 = $this->currPos;
  if (strspn($this->input, "=|!{}:;\x0d\x0a[]-", $this->currPos, 1) !== 0) {
    $r4 = $this->input[$this->currPos++];
    $r4 = false;
    $this->currPos = $p3;
  } else {
    $r4 = self::$FAILED;
    $r2 = self::$FAILED;
    goto seq_1;
  }
  // free $p3
  // start seq_2
  $p3 = $this->currPos;
  $this->savedPos = $this->currPos;
  $r6 = $this->a30(/*annOrExtTag*/($boolParams & 0x2) !== 0, /*h*/($boolParams & 0x4) !== 0, /*extlink*/($boolParams & 0x8) !== 0, /*intemplate*/($boolParams & 0x10) !== 0, $param_preproc, /*equal*/($boolParams & 0x20) !== 0, /*table*/($boolParams & 0x40) !== 0, /*templateArg*/($boolParams & 0x80) !== 0, /*tableCellArg*/($boolParams & 0x100) !== 0, /*semicolon*/($boolParams & 0x200) !== 0, /*arrow*/($boolParams & 0x400) !== 0, /*linkdesc*/($boolParams & 0x800) !== 0, /*colon*/($boolParams & 0x1000) !== 0, $param_th);
  if ($r6) {
    $r6 = false;
  } else {
    $r6 = self::$FAILED;
    $this->currPos = $p3;
    $r5 = self::$FAILED;
    goto seq_2;
  }
  $r5 = true;
  seq_2:
  if ($r5===self::$FAILED) {
    $this->currPos = $p1;
    $r2 = self::$FAILED;
    goto seq_1;
  }
  // free $r5,$p3
  $r2 = true;
  seq_1:
  // free $r2,$p1
  $this->cache[$bucket][$key] = new GrammarCacheEntry(
    $this->currPos,
    $r2,
    $saved_preproc !== $param_preproc ? $param_preproc : self::$UNDEFINED,
    $saved_th !== $param_th ? $param_th : self::$UNDEFINED
  );
  return $r2;
}
private function parseno_punctuation_char($silence) {
  $key = 326;
  $bucket = $this->currPos;
  $cached = $this->cache[$bucket][$key] ?? null;
  if ($cached) {
    $this->currPos = $cached->nextPos;

    return $cached->result;
  }

  $r1 = self::charAt($this->input, $this->currPos);
  if (preg_match("/^[^ \\]\\[\\x0d\\x0a\"'<>\\x00- \\x7f&\\x{a0}\\x{1680}\\x{180e}\\x{2000}-\\x{200a}\\x{202f}\\x{205f}\\x{3000}{]/u", $r1)) {
    $this->currPos += strlen($r1);
  } else {
    $r1 = self::$FAILED;
    if (!$silence) {$this->fail(22);}
  }
  $this->cache[$bucket][$key] = new GrammarCacheEntry(
    $this->currPos,
    $r1,
    self::$UNDEFINED,
    self::$UNDEFINED
  );
  return $r1;
}
private function parsetplarg_or_template($silence, $boolParams, &$param_th, &$param_preproc) {
  $key = json_encode([334, $boolParams & 0x3fef, $param_th, $param_preproc]);
  $bucket = $this->currPos;
  $cached = $this->cache[$bucket][$key] ?? null;
  if ($cached) {
    $this->currPos = $cached->nextPos;
    if ($cached->th !== self::$UNDEFINED) { $param_th = $cached->th; }
    if ($cached->preproc !== self::$UNDEFINED) { $param_preproc = $cached->preproc; }
    return $cached->result;
  }
  $saved_th=$param_th;
  $saved_preproc=$param_preproc;
  $p2 = $this->currPos;
  // start seq_1
  $p3 = $this->currPos;
  $p4 = $this->currPos;
  if ($this->currPos >= $this->inputLength ? false : substr_compare($this->input, "{{", $this->currPos, 2, false) === 0) {
    $r5 = "{{";
    $this->currPos += 2;
    $r5 = false;
    $this->currPos = $p4;
  } else {
    $r5 = self::$FAILED;
    $r1 = self::$FAILED;
    goto seq_1;
  }
  // free $p4
  $r6 = $this->parsetplarg_or_template_guarded($silence, $boolParams | 0x10, $param_th, $param_preproc);
  // t <- $r6
  if ($r6===self::$FAILED) {
    $this->currPos = $p3;
    $r1 = self::$FAILED;
    goto seq_1;
  }
  $r1 = true;
  seq_1:
  if ($r1!==self::$FAILED) {
    $this->savedPos = $p2;
    $r1 = $this->a31($r6);
  }
  // free $p3
  $this->cache[$bucket][$key] = new GrammarCacheEntry(
    $this->currPos,
    $r1,
    $saved_preproc !== $param_preproc ? $param_preproc : self::$UNDEFINED,
    $saved_th !== $param_th ? $param_th : self::$UNDEFINED
  );
  return $r1;
}
private function parsehtmlentity($silence) {
  $key = 482;
  $bucket = $this->currPos;
  $cached = $this->cache[$bucket][$key] ?? null;
  if ($cached) {
    $this->currPos = $cached->nextPos;

    return $cached->result;
  }

  $p2 = $this->currPos;
  $r3 = $this->parseraw_htmlentity($silence);
  // cc <- $r3
  $r1 = $r3;
  if ($r1!==self::$FAILED) {
    $this->savedPos = $p2;
    $r1 = $this->a32($r3);
  }
  $this->cache[$bucket][$key] = new GrammarCacheEntry(
    $this->currPos,
    $r1,
    self::$UNDEFINED,
    self::$UNDEFINED
  );
  return $r1;
}
private function parseoptional_spaces($silence) {
  $key = 512;
  $bucket = $this->currPos;
  $cached = $this->cache[$bucket][$key] ?? null;
  if ($cached) {
    $this->currPos = $cached->nextPos;

    return $cached->result;
  }

  $p1 = $this->currPos;
  for (;;) {
    $r3 = $this->input[$this->currPos] ?? '';
    if ($r3 === " " || $r3 === "\x09") {
      $this->currPos++;
    } else {
      $r3 = self::$FAILED;
      if (!$silence) {$this->fail(10);}
      break;
    }
  }
  // free $r3
  $r2 = true;
  if ($r2!==self::$FAILED) {
    $r2 = substr($this->input, $p1, $this->currPos - $p1);
  } else {
    $r2 = self::$FAILED;
  }
  // free $p1
  $this->cache[$bucket][$key] = new GrammarCacheEntry(
    $this->currPos,
    $r2,
    self::$UNDEFINED,
    self::$UNDEFINED
  );
  return $r2;
}
private function discardpipe($silence) {
  $key = 507;
  $bucket = $this->currPos;
  $cached = $this->cache[$bucket][$key] ?? null;
  if ($cached) {
    $this->currPos = $cached->nextPos;

    return $cached->result;
  }

  // start choice_1
  if (($this->input[$this->currPos] ?? null) === "|") {
    $this->currPos++;
    $r1 = "|";
    goto choice_1;
  } else {
    if (!$silence) {$this->fail(13);}
    $r1 = self::$FAILED;
  }
  if ($this->currPos >= $this->inputLength ? false : substr_compare($this->input, "{{!}}", $this->currPos, 5, false) === 0) {
    $r1 = "{{!}}";
    $this->currPos += 5;
  } else {
    if (!$silence) {$this->fail(14);}
    $r1 = self::$FAILED;
  }
  choice_1:
  $this->cache[$bucket][$key] = new GrammarCacheEntry(
    $this->currPos,
    $r1,
    self::$UNDEFINED,
    self::$UNDEFINED
  );
  return $r1;
}
private function parsetable_attribute($silence, $boolParams, &$param_preproc, &$param_th) {
  $key = json_encode([420, $boolParams & 0x3fff, $param_preproc, $param_th]);
  $bucket = $this->currPos;
  $cached = $this->cache[$bucket][$key] ?? null;
  if ($cached) {
    $this->currPos = $cached->nextPos;
    if ($cached->preproc !== self::$UNDEFINED) { $param_preproc = $cached->preproc; }
    if ($cached->th !== self::$UNDEFINED) { $param_th = $cached->th; }
    return $cached->result;
  }
  $saved_preproc=$param_preproc;
  $saved_th=$param_th;
  $p2 = $this->currPos;
  // start seq_1
  $p3 = $this->currPos;
  $r4 = $this->parseoptionalSpaceToken($silence);
  // s <- $r4
  if ($r4===self::$FAILED) {
    $r1 = self::$FAILED;
    goto seq_1;
  }
  $p6 = $this->currPos;
  $r5 = '';
  // namePos0 <- $r5
  if ($r5!==self::$FAILED) {
    $this->savedPos = $p6;
    $r5 = $this->a33($r4);
  } else {
    $this->currPos = $p3;
    $r1 = self::$FAILED;
    goto seq_1;
  }
  $r7 = $this->parsetable_attribute_name($silence, $boolParams, $param_preproc, $param_th);
  // name <- $r7
  if ($r7===self::$FAILED) {
    $this->currPos = $p3;
    $r1 = self::$FAILED;
    goto seq_1;
  }
  $p9 = $this->currPos;
  $r8 = '';
  // namePos <- $r8
  if ($r8!==self::$FAILED) {
    $this->savedPos = $p9;
    $r8 = $this->a34($r4, $r5, $r7);
  } else {
    $this->currPos = $p3;
    $r1 = self::$FAILED;
    goto seq_1;
  }
  $p11 = $this->currPos;
  // start seq_2
  $p12 = $this->currPos;
  $r13 = $this->discardoptionalSpaceToken($silence);
  if ($r13===self::$FAILED) {
    $r10 = self::$FAILED;
    goto seq_2;
  }
  if (($this->input[$this->currPos] ?? null) === "=") {
    $this->currPos++;
    $r14 = "=";
  } else {
    if (!$silence) {$this->fail(23);}
    $r14 = self::$FAILED;
    $this->currPos = $p12;
    $r10 = self::$FAILED;
    goto seq_2;
  }
  $r15 = $this->parsetable_att_value($silence, $boolParams, $param_preproc, $param_th);
  if ($r15===self::$FAILED) {
    $r15 = null;
  }
  // v <- $r15
  $r10 = true;
  seq_2:
  if ($r10!==self::$FAILED) {
    $this->savedPos = $p11;
    $r10 = $this->a35($r4, $r5, $r7, $r8, $r15);
  } else {
    $r10 = null;
  }
  // free $p12
  // vd <- $r10
  $r1 = true;
  seq_1:
  if ($r1!==self::$FAILED) {
    $this->savedPos = $p2;
    $r1 = $this->a36($r4, $r5, $r7, $r8, $r10);
  }
  // free $p3
  $this->cache[$bucket][$key] = new GrammarCacheEntry(
    $this->currPos,
    $r1,
    $saved_preproc !== $param_preproc ? $param_preproc : self::$UNDEFINED,
    $saved_th !== $param_th ? $param_th : self::$UNDEFINED
  );
  return $r1;
}
private function discardoptionalSpaceToken($silence) {
  $key = 529;
  $bucket = $this->currPos;
  $cached = $this->cache[$bucket][$key] ?? null;
  if ($cached) {
    $this->currPos = $cached->nextPos;

    return $cached->result;
  }

  $p2 = $this->currPos;
  $r3 = $this->parseoptional_spaces($silence);
  // s <- $r3
  $r1 = $r3;
  if ($r1!==self::$FAILED) {
    $this->savedPos = $p2;
    $r1 = $this->a37($r3);
  }
  $this->cache[$bucket][$key] = new GrammarCacheEntry(
    $this->currPos,
    $r1,
    self::$UNDEFINED,
    self::$UNDEFINED
  );
  return $r1;
}
private function parsebroken_table_attribute_name_char($silence) {
  $key = 426;
  $bucket = $this->currPos;
  $cached = $this->cache[$bucket][$key] ?? null;
  if ($cached) {
    $this->currPos = $cached->nextPos;

    return $cached->result;
  }

  $p2 = $this->currPos;
  // c <- $r3
  if (strspn($this->input, "\x00/=>", $this->currPos, 1) !== 0) {
    $r3 = $this->input[$this->currPos++];
  } else {
    $r3 = self::$FAILED;
    if (!$silence) {$this->fail(24);}
  }
  $r1 = $r3;
  if ($r1!==self::$FAILED) {
    $this->savedPos = $p2;
    $r1 = $this->a38($r3);
  }
  $this->cache[$bucket][$key] = new GrammarCacheEntry(
    $this->currPos,
    $r1,
    self::$UNDEFINED,
    self::$UNDEFINED
  );
  return $r1;
}
private function parsegeneric_newline_attribute($silence, $boolParams, &$param_preproc, &$param_th) {
  $key = json_encode([418, $boolParams & 0x3fff, $param_preproc, $param_th]);
  $bucket = $this->currPos;
  $cached = $this->cache[$bucket][$key] ?? null;
  if ($cached) {
    $this->currPos = $cached->nextPos;
    if ($cached->preproc !== self::$UNDEFINED) { $param_preproc = $cached->preproc; }
    if ($cached->th !== self::$UNDEFINED) { $param_th = $cached->th; }
    return $cached->result;
  }
  $saved_preproc=$param_preproc;
  $saved_th=$param_th;
  $p2 = $this->currPos;
  // start seq_1
  $p3 = $this->currPos;
  for (;;) {
    $r5 = $this->discardspace_or_newline_or_solidus($silence);
    if ($r5===self::$FAILED) {
      break;
    }
  }
  // free $r5
  $r4 = true;
  if ($r4===self::$FAILED) {
    $r1 = self::$FAILED;
    goto seq_1;
  }
  // free $r4
  $p6 = $this->currPos;
  $r4 = '';
  // namePos0 <- $r4
  if ($r4!==self::$FAILED) {
    $this->savedPos = $p6;
    $r4 = $this->a17();
  } else {
    $this->currPos = $p3;
    $r1 = self::$FAILED;
    goto seq_1;
  }
  $r5 = $this->parsegeneric_attribute_name($silence, $boolParams, $param_preproc, $param_th);
  // name <- $r5
  if ($r5===self::$FAILED) {
    $this->currPos = $p3;
    $r1 = self::$FAILED;
    goto seq_1;
  }
  $p8 = $this->currPos;
  $r7 = '';
  // namePos <- $r7
  if ($r7!==self::$FAILED) {
    $this->savedPos = $p8;
    $r7 = $this->a39($r4, $r5);
  } else {
    $this->currPos = $p3;
    $r1 = self::$FAILED;
    goto seq_1;
  }
  $p10 = $this->currPos;
  // start seq_2
  $p11 = $this->currPos;
  for (;;) {
    $r13 = $this->discardspace_or_newline($silence);
    if ($r13===self::$FAILED) {
      break;
    }
  }
  // free $r13
  $r12 = true;
  if ($r12===self::$FAILED) {
    $r9 = self::$FAILED;
    goto seq_2;
  }
  // free $r12
  if (($this->input[$this->currPos] ?? null) === "=") {
    $this->currPos++;
    $r12 = "=";
  } else {
    if (!$silence) {$this->fail(23);}
    $r12 = self::$FAILED;
    $this->currPos = $p11;
    $r9 = self::$FAILED;
    goto seq_2;
  }
  $r13 = $this->parsegeneric_att_value($silence, $boolParams, $param_preproc, $param_th);
  if ($r13===self::$FAILED) {
    $r13 = null;
  }
  // v <- $r13
  $r9 = true;
  seq_2:
  if ($r9!==self::$FAILED) {
    $this->savedPos = $p10;
    $r9 = $this->a40($r4, $r5, $r7, $r13);
  } else {
    $r9 = null;
  }
  // free $p11
  // vd <- $r9
  $r1 = true;
  seq_1:
  if ($r1!==self::$FAILED) {
    $this->savedPos = $p2;
    $r1 = $this->a41($r4, $r5, $r7, $r9);
  }
  // free $p3
  $this->cache[$bucket][$key] = new GrammarCacheEntry(
    $this->currPos,
    $r1,
    $saved_preproc !== $param_preproc ? $param_preproc : self::$UNDEFINED,
    $saved_th !== $param_th ? $param_th : self::$UNDEFINED
  );
  return $r1;
}
private function parseextlink_nonipv6url($silence, $boolParams, &$param_preproc, &$param_th) {
  $key = json_encode([490, $boolParams & 0x37ff, $param_preproc, $param_th]);
  $bucket = $this->currPos;
  $cached = $this->cache[$bucket][$key] ?? null;
  if ($cached) {
    $this->currPos = $cached->nextPos;
    if ($cached->preproc !== self::$UNDEFINED) { $param_preproc = $cached->preproc; }
    if ($cached->th !== self::$UNDEFINED) { $param_th = $cached->th; }
    return $cached->result;
  }
  $saved_preproc=$param_preproc;
  $saved_th=$param_th;
  $r1 = $this->parseextlink_nonipv6url_parameterized($silence, $boolParams & ~0x800, $param_preproc, $param_th);
  $this->cache[$bucket][$key] = new GrammarCacheEntry(
    $this->currPos,
    $r1,
    $saved_preproc !== $param_preproc ? $param_preproc : self::$UNDEFINED,
    $saved_th !== $param_th ? $param_th : self::$UNDEFINED
  );
  return $r1;
}
private function discardspace($silence) {
  $key = 511;
  $bucket = $this->currPos;
  $cached = $this->cache[$bucket][$key] ?? null;
  if ($cached) {
    $this->currPos = $cached->nextPos;

    return $cached->result;
  }

  $r1 = $this->input[$this->currPos] ?? '';
  if ($r1 === " " || $r1 === "\x09") {
    $this->currPos++;
  } else {
    $r1 = self::$FAILED;
    if (!$silence) {$this->fail(10);}
  }
  $this->cache[$bucket][$key] = new GrammarCacheEntry(
    $this->currPos,
    $r1,
    self::$UNDEFINED,
    self::$UNDEFINED
  );
  return $r1;
}
private function discardunispace($silence) {
  $key = 535;
  $bucket = $this->currPos;
  $cached = $this->cache[$bucket][$key] ?? null;
  if ($cached) {
    $this->currPos = $cached->nextPos;

    return $cached->result;
  }

  $r1 = self::charAt($this->input, $this->currPos);
  if (preg_match("/^[ \\x{a0}\\x{1680}\\x{2000}-\\x{200a}\\x{202f}\\x{205f}\\x{3000}]/u", $r1)) {
    $this->currPos += strlen($r1);
  } else {
    $r1 = self::$FAILED;
    if (!$silence) {$this->fail(25);}
  }
  $this->cache[$bucket][$key] = new GrammarCacheEntry(
    $this->currPos,
    $r1,
    self::$UNDEFINED,
    self::$UNDEFINED
  );
  return $r1;
}
private function parseinlineline($silence, $boolParams, &$param_preproc, &$param_th) {
  $key = json_encode([304, $boolParams & 0x3fff, $param_preproc, $param_th]);
  $bucket = $this->currPos;
  $cached = $this->cache[$bucket][$key] ?? null;
  if ($cached) {
    $this->currPos = $cached->nextPos;
    if ($cached->preproc !== self::$UNDEFINED) { $param_preproc = $cached->preproc; }
    if ($cached->th !== self::$UNDEFINED) { $param_th = $cached->th; }
    return $cached->result;
  }
  $saved_preproc=$param_preproc;
  $saved_th=$param_th;
  $p2 = $this->currPos;
  $r3 = [];
  for (;;) {
    // start choice_1
    $r4 = $this->parseurltext($silence, $boolParams, $param_preproc, $param_th);
    if ($r4!==self::$FAILED) {
      goto choice_1;
    }
    $p5 = $this->currPos;
    // start seq_1
    $p6 = $this->currPos;
    $p7 = $this->currPos;
    $r8 = $this->discardinline_breaks(true, $boolParams, $param_preproc, $param_th);
    if ($r8 === self::$FAILED) {
      $r8 = false;
    } else {
      $r8 = self::$FAILED;
      $this->currPos = $p7;
      $r4 = self::$FAILED;
      goto seq_1;
    }
    // free $p7
    // start choice_2
    $r9 = $this->parseinline_element($silence, $boolParams, $param_preproc, $param_th);
    if ($r9!==self::$FAILED) {
      goto choice_2;
    }
    $p7 = $this->currPos;
    // start seq_2
    $p10 = $this->currPos;
    $p11 = $this->currPos;
    $r12 = $this->discardnewline(true);
    if ($r12 === self::$FAILED) {
      $r12 = false;
    } else {
      $r12 = self::$FAILED;
      $this->currPos = $p11;
      $r9 = self::$FAILED;
      goto seq_2;
    }
    // free $p11
    // s <- $r13
    if ($this->currPos < $this->inputLength) {
      $r13 = self::consumeChar($this->input, $this->currPos);;
    } else {
      $r13 = self::$FAILED;
      if (!$silence) {$this->fail(7);}
      $this->currPos = $p10;
      $r9 = self::$FAILED;
      goto seq_2;
    }
    $r9 = true;
    seq_2:
    if ($r9!==self::$FAILED) {
      $this->savedPos = $p7;
      $r9 = $this->a42($r13);
    }
    // free $p10
    choice_2:
    // r <- $r9
    if ($r9===self::$FAILED) {
      $this->currPos = $p6;
      $r4 = self::$FAILED;
      goto seq_1;
    }
    $r4 = true;
    seq_1:
    if ($r4!==self::$FAILED) {
      $this->savedPos = $p5;
      $r4 = $this->a24($r9);
    }
    // free $p6
    choice_1:
    if ($r4!==self::$FAILED) {
      $r3[] = $r4;
    } else {
      break;
    }
  }
  if (count($r3) === 0) {
    $r3 = self::$FAILED;
  }
  // c <- $r3
  // free $r4
  $r1 = $r3;
  if ($r1!==self::$FAILED) {
    $this->savedPos = $p2;
    $r1 = $this->a43($r3);
  }
  $this->cache[$bucket][$key] = new GrammarCacheEntry(
    $this->currPos,
    $r1,
    $saved_preproc !== $param_preproc ? $param_preproc : self::$UNDEFINED,
    $saved_th !== $param_th ? $param_th : self::$UNDEFINED
  );
  return $r1;
}
private function parsedtdd($silence, $boolParams, &$param_preproc, &$param_th) {
  $key = json_encode([440, $boolParams & 0x3fff, $param_preproc, $param_th]);
  $bucket = $this->currPos;
  $cached = $this->cache[$bucket][$key] ?? null;
  if ($cached) {
    $this->currPos = $cached->nextPos;
    if ($cached->preproc !== self::$UNDEFINED) { $param_preproc = $cached->preproc; }
    if ($cached->th !== self::$UNDEFINED) { $param_th = $cached->th; }
    return $cached->result;
  }
  $saved_preproc=$param_preproc;
  $saved_th=$param_th;
  $p2 = $this->currPos;
  // start seq_1
  $p3 = $this->currPos;
  $r4 = [];
  for (;;) {
    $p6 = $this->currPos;
    // start seq_2
    $p7 = $this->currPos;
    $p8 = $this->currPos;
    // start seq_3
    $p10 = $this->currPos;
    if (($this->input[$this->currPos] ?? null) === ";") {
      $this->currPos++;
      $r11 = ";";
    } else {
      $r11 = self::$FAILED;
      $r9 = self::$FAILED;
      goto seq_3;
    }
    $p12 = $this->currPos;
    $r13 = $this->discardlist_char(true);
    if ($r13 === self::$FAILED) {
      $r13 = false;
    } else {
      $r13 = self::$FAILED;
      $this->currPos = $p12;
      $this->currPos = $p10;
      $r9 = self::$FAILED;
      goto seq_3;
    }
    // free $p12
    $r9 = true;
    seq_3:
    // free $p10
    if ($r9 === self::$FAILED) {
      $r9 = false;
    } else {
      $r9 = self::$FAILED;
      $this->currPos = $p8;
      $r5 = self::$FAILED;
      goto seq_2;
    }
    // free $p8
    $r14 = $this->parselist_char($silence);
    // lc <- $r14
    if ($r14===self::$FAILED) {
      $this->currPos = $p7;
      $r5 = self::$FAILED;
      goto seq_2;
    }
    $r5 = true;
    seq_2:
    if ($r5!==self::$FAILED) {
      $this->savedPos = $p6;
      $r5 = $this->a44($r14);
      $r4[] = $r5;
    } else {
      break;
    }
    // free $p7
  }
  // bullets <- $r4
  // free $r5
  if (($this->input[$this->currPos] ?? null) === ";") {
    $this->currPos++;
    $r5 = ";";
  } else {
    if (!$silence) {$this->fail(26);}
    $r5 = self::$FAILED;
    $this->currPos = $p3;
    $r1 = self::$FAILED;
    goto seq_1;
  }
  $r15 = $this->parseinlineline_break_on_colon($silence, $boolParams, $param_preproc, $param_th);
  if ($r15===self::$FAILED) {
    $r15 = null;
  }
  // c <- $r15
  $p7 = $this->currPos;
  // cpos <- $r16
  if (($this->input[$this->currPos] ?? null) === ":") {
    $this->currPos++;
    $r16 = ":";
    $this->savedPos = $p7;
    $r16 = $this->a45($r4, $r15);
  } else {
    if (!$silence) {$this->fail(18);}
    $r16 = self::$FAILED;
    $this->currPos = $p3;
    $r1 = self::$FAILED;
    goto seq_1;
  }
  $r17 = $this->parseinlineline($silence, $boolParams, $param_preproc, $param_th);
  if ($r17===self::$FAILED) {
    $r17 = null;
  }
  // d <- $r17
  $p8 = $this->currPos;
  $r18 = $this->discardeolf(true);
  if ($r18!==self::$FAILED) {
    $r18 = false;
    $this->currPos = $p8;
  } else {
    $this->currPos = $p3;
    $r1 = self::$FAILED;
    goto seq_1;
  }
  // free $p8
  $r1 = true;
  seq_1:
  if ($r1!==self::$FAILED) {
    $this->savedPos = $p2;
    $r1 = $this->a46($r4, $r15, $r16, $r17);
  }
  // free $p3
  $this->cache[$bucket][$key] = new GrammarCacheEntry(
    $this->currPos,
    $r1,
    $saved_preproc !== $param_preproc ? $param_preproc : self::$UNDEFINED,
    $saved_th !== $param_th ? $param_th : self::$UNDEFINED
  );
  return $r1;
}
private function parsehacky_dl_uses($silence, $boolParams, &$param_preproc, &$param_th) {
  $key = json_encode([438, $boolParams & 0x3fff, $param_preproc, $param_th]);
  $bucket = $this->currPos;
  $cached = $this->cache[$bucket][$key] ?? null;
  if ($cached) {
    $this->currPos = $cached->nextPos;
    if ($cached->preproc !== self::$UNDEFINED) { $param_preproc = $cached->preproc; }
    if ($cached->th !== self::$UNDEFINED) { $param_th = $cached->th; }
    return $cached->result;
  }
  $saved_preproc=$param_preproc;
  $saved_th=$param_th;
  $p2 = $this->currPos;
  // start seq_1
  $p3 = $this->currPos;
  $r4 = [];
  for (;;) {
    if (($this->input[$this->currPos] ?? null) === ":") {
      $this->currPos++;
      $r5 = ":";
      $r4[] = $r5;
    } else {
      if (!$silence) {$this->fail(18);}
      $r5 = self::$FAILED;
      break;
    }
  }
  if (count($r4) === 0) {
    $r4 = self::$FAILED;
  }
  // bullets <- $r4
  if ($r4===self::$FAILED) {
    $r1 = self::$FAILED;
    goto seq_1;
  }
  // free $r5
  // start seq_2
  $p6 = $this->currPos;
  $r7 = $this->parsetable_line($silence, $boolParams, $param_preproc, $param_th);
  if ($r7===self::$FAILED) {
    $r5 = self::$FAILED;
    goto seq_2;
  }
  $r8 = [];
  for (;;) {
    // start seq_3
    $p10 = $this->currPos;
    $r11 = [];
    for (;;) {
      $r12 = $this->parsesol($silence, $boolParams, $param_preproc, $param_th);
      if ($r12!==self::$FAILED) {
        $r11[] = $r12;
      } else {
        break;
      }
    }
    if (count($r11) === 0) {
      $r11 = self::$FAILED;
    }
    if ($r11===self::$FAILED) {
      $r9 = self::$FAILED;
      goto seq_3;
    }
    // free $r12
    $r12 = $this->parsetable_line($silence, $boolParams, $param_preproc, $param_th);
    if ($r12===self::$FAILED) {
      $this->currPos = $p10;
      $r9 = self::$FAILED;
      goto seq_3;
    }
    $r9 = [$r11,$r12];
    seq_3:
    if ($r9!==self::$FAILED) {
      $r8[] = $r9;
    } else {
      break;
    }
    // free $p10
  }
  // free $r9
  $r5 = [$r7,$r8];
  seq_2:
  // tbl <- $r5
  if ($r5===self::$FAILED) {
    $this->currPos = $p3;
    $r1 = self::$FAILED;
    goto seq_1;
  }
  // free $p6
  $r9 = $this->parseinlineline($silence, $boolParams, $param_preproc, $param_th);
  if ($r9===self::$FAILED) {
    $r9 = null;
  }
  // line <- $r9
  $p6 = $this->currPos;
  // start seq_4
  $p10 = $this->currPos;
  for (;;) {
    // start choice_1
    $r15 = $this->discardspace(true);
    if ($r15!==self::$FAILED) {
      goto choice_1;
    }
    $r15 = $this->discardcomment(true);
    choice_1:
    if ($r15===self::$FAILED) {
      break;
    }
  }
  // free $r15
  $r14 = true;
  if ($r14===self::$FAILED) {
    $r13 = self::$FAILED;
    goto seq_4;
  }
  // free $r14
  $r14 = $this->discardeolf(true);
  if ($r14===self::$FAILED) {
    $this->currPos = $p10;
    $r13 = self::$FAILED;
    goto seq_4;
  }
  $r13 = true;
  seq_4:
  if ($r13!==self::$FAILED) {
    $r13 = false;
    $this->currPos = $p6;
  } else {
    $this->currPos = $p3;
    $r1 = self::$FAILED;
    goto seq_1;
  }
  // free $p10
  // free $p6
  $r1 = true;
  seq_1:
  if ($r1!==self::$FAILED) {
    $this->savedPos = $p2;
    $r1 = $this->a47($r4, $r5, $r9);
  }
  // free $p3
  $this->cache[$bucket][$key] = new GrammarCacheEntry(
    $this->currPos,
    $r1,
    $saved_preproc !== $param_preproc ? $param_preproc : self::$UNDEFINED,
    $saved_th !== $param_th ? $param_th : self::$UNDEFINED
  );
  return $r1;
}
private function parseli($silence, $boolParams, &$param_preproc, &$param_th) {
  $key = json_encode([436, $boolParams & 0x3fff, $param_preproc, $param_th]);
  $bucket = $this->currPos;
  $cached = $this->cache[$bucket][$key] ?? null;
  if ($cached) {
    $this->currPos = $cached->nextPos;
    if ($cached->preproc !== self::$UNDEFINED) { $param_preproc = $cached->preproc; }
    if ($cached->th !== self::$UNDEFINED) { $param_th = $cached->th; }
    return $cached->result;
  }
  $saved_preproc=$param_preproc;
  $saved_th=$param_th;
  $p2 = $this->currPos;
  // start seq_1
  $p3 = $this->currPos;
  $r4 = [];
  for (;;) {
    $r5 = $this->parselist_char($silence);
    if ($r5!==self::$FAILED) {
      $r4[] = $r5;
    } else {
      break;
    }
  }
  if (count($r4) === 0) {
    $r4 = self::$FAILED;
  }
  // bullets <- $r4
  if ($r4===self::$FAILED) {
    $r1 = self::$FAILED;
    goto seq_1;
  }
  // free $r5
  $r5 = $this->parseinlineline($silence, $boolParams, $param_preproc, $param_th);
  if ($r5===self::$FAILED) {
    $r5 = null;
  }
  // c <- $r5
  $p6 = $this->currPos;
  // start choice_1
  $r7 = $this->discardeolf(true);
  if ($r7!==self::$FAILED) {
    goto choice_1;
  }
  $r7 = $this->discardinline_breaks(true, $boolParams, $param_preproc, $param_th);
  choice_1:
  if ($r7!==self::$FAILED) {
    $r7 = false;
    $this->currPos = $p6;
  } else {
    $this->currPos = $p3;
    $r1 = self::$FAILED;
    goto seq_1;
  }
  // free $p6
  $r1 = true;
  seq_1:
  if ($r1!==self::$FAILED) {
    $this->savedPos = $p2;
    $r1 = $this->a48($r4, $r5);
  }
  // free $p3
  $this->cache[$bucket][$key] = new GrammarCacheEntry(
    $this->currPos,
    $r1,
    $saved_preproc !== $param_preproc ? $param_preproc : self::$UNDEFINED,
    $saved_th !== $param_th ? $param_th : self::$UNDEFINED
  );
  return $r1;
}
private function discardeof($silence) {
  $key = 517;
  $bucket = $this->currPos;
  $cached = $this->cache[$bucket][$key] ?? null;
  if ($cached) {
    $this->currPos = $cached->nextPos;

    return $cached->result;
  }

  $this->savedPos = $this->currPos;
  $r1 = $this->a49();
  if ($r1) {
    $r1 = false;
  } else {
    $r1 = self::$FAILED;
  }
  $this->cache[$bucket][$key] = new GrammarCacheEntry(
    $this->currPos,
    $r1,
    self::$UNDEFINED,
    self::$UNDEFINED
  );
  return $r1;
}
private function parseblock($silence, $boolParams, &$param_th, &$param_preproc) {
  $key = json_encode([288, $boolParams & 0x3fff, $param_th, $param_preproc]);
  $bucket = $this->currPos;
  $cached = $this->cache[$bucket][$key] ?? null;
  if ($cached) {
    $this->currPos = $cached->nextPos;
    if ($cached->th !== self::$UNDEFINED) { $param_th = $cached->th; }
    if ($cached->preproc !== self::$UNDEFINED) { $param_preproc = $cached->preproc; }
    return $cached->result;
  }
  $saved_th=$param_th;
  $saved_preproc=$param_preproc;
  // start choice_1
  $p2 = $this->currPos;
  // start seq_1
  $p3 = $this->currPos;
  $p4 = $this->currPos;
  $r5 = $this->discardsof(true);
  if ($r5!==self::$FAILED) {
    $r5 = false;
    $this->currPos = $p4;
  } else {
    $r1 = self::$FAILED;
    goto seq_1;
  }
  // free $p4
  $r6 = $this->parseredirect($silence, $boolParams, $param_th, $param_preproc);
  // r <- $r6
  if ($r6===self::$FAILED) {
    $this->currPos = $p3;
    $r1 = self::$FAILED;
    goto seq_1;
  }
  $r7 = [];
  for (;;) {
    $r8 = $this->parsesol_transparent($silence, $boolParams, $param_preproc, $param_th);
    if ($r8!==self::$FAILED) {
      $r7[] = $r8;
    } else {
      break;
    }
  }
  // cil <- $r7
  // free $r8
  $r8 = $this->parseblock_line($silence, $boolParams, $param_preproc, $param_th);
  if ($r8===self::$FAILED) {
    $r8 = null;
  }
  // bl <- $r8
  $r1 = true;
  seq_1:
  if ($r1!==self::$FAILED) {
    $this->savedPos = $p2;
    $r1 = $this->a50($r6, $r7, $r8);
    goto choice_1;
  }
  // free $p3
  $r1 = $this->parseblock_lines($silence, $boolParams, $param_preproc, $param_th);
  if ($r1!==self::$FAILED) {
    goto choice_1;
  }
  $r1 = $this->parseinlineline($silence, $boolParams, $param_preproc, $param_th);
  if ($r1!==self::$FAILED) {
    goto choice_1;
  }
  $p3 = $this->currPos;
  // start seq_2
  $p4 = $this->currPos;
  $r9 = $this->parsesol($silence, $boolParams, $param_preproc, $param_th);
  // s <- $r9
  if ($r9===self::$FAILED) {
    $r1 = self::$FAILED;
    goto seq_2;
  }
  $p10 = $this->currPos;
  $r11 = $this->discardsof(true);
  if ($r11 === self::$FAILED) {
    $r11 = false;
  } else {
    $r11 = self::$FAILED;
    $this->currPos = $p10;
    $this->currPos = $p4;
    $r1 = self::$FAILED;
    goto seq_2;
  }
  // free $p10
  $p10 = $this->currPos;
  $r12 = $this->discardinline_breaks(true, $boolParams, $param_preproc, $param_th);
  if ($r12 === self::$FAILED) {
    $r12 = false;
  } else {
    $r12 = self::$FAILED;
    $this->currPos = $p10;
    $this->currPos = $p4;
    $r1 = self::$FAILED;
    goto seq_2;
  }
  // free $p10
  $r1 = true;
  seq_2:
  if ($r1!==self::$FAILED) {
    $this->savedPos = $p3;
    $r1 = $this->a42($r9);
  }
  // free $p4
  choice_1:
  $this->cache[$bucket][$key] = new GrammarCacheEntry(
    $this->currPos,
    $r1,
    $saved_preproc !== $param_preproc ? $param_preproc : self::$UNDEFINED,
    $saved_th !== $param_th ? $param_th : self::$UNDEFINED
  );
  return $r1;
}
private function discardnewline($silence) {
  $key = 519;
  $bucket = $this->currPos;
  $cached = $this->cache[$bucket][$key] ?? null;
  if ($cached) {
    $this->currPos = $cached->nextPos;

    return $cached->result;
  }

  // start choice_1
  if (($this->input[$this->currPos] ?? null) === "\x0a") {
    $this->currPos++;
    $r1 = "\x0a";
    goto choice_1;
  } else {
    if (!$silence) {$this->fail(27);}
    $r1 = self::$FAILED;
  }
  if ($this->currPos >= $this->inputLength ? false : substr_compare($this->input, "\x0d\x0a", $this->currPos, 2, false) === 0) {
    $r1 = "\x0d\x0a";
    $this->currPos += 2;
  } else {
    if (!$silence) {$this->fail(28);}
    $r1 = self::$FAILED;
  }
  choice_1:
  $this->cache[$bucket][$key] = new GrammarCacheEntry(
    $this->currPos,
    $r1,
    self::$UNDEFINED,
    self::$UNDEFINED
  );
  return $r1;
}
private function parsetplarg_or_template_guarded($silence, $boolParams, &$param_th, &$param_preproc) {
  $key = json_encode([336, $boolParams & 0x3fff, $param_th, $param_preproc]);
  $bucket = $this->currPos;
  $cached = $this->cache[$bucket][$key] ?? null;
  if ($cached) {
    $this->currPos = $cached->nextPos;
    if ($cached->th !== self::$UNDEFINED) { $param_th = $cached->th; }
    if ($cached->preproc !== self::$UNDEFINED) { $param_preproc = $cached->preproc; }
    return $cached->result;
  }
  $saved_th=$param_th;
  $saved_preproc=$param_preproc;
  // start choice_1
  $p2 = $this->currPos;
  // start seq_1
  $p3 = $this->currPos;
  $p4 = $this->currPos;
  // start seq_2
  $p6 = $this->currPos;
  if ($this->currPos >= $this->inputLength ? false : substr_compare($this->input, "{{", $this->currPos, 2, false) === 0) {
    $r7 = "{{";
    $this->currPos += 2;
  } else {
    $r7 = self::$FAILED;
    $r5 = self::$FAILED;
    goto seq_2;
  }
  $p8 = $this->currPos;
  // start seq_3
  $p10 = $this->currPos;
  $r11 = self::$FAILED;
  for (;;) {
    if ($this->currPos >= $this->inputLength ? false : substr_compare($this->input, "{{{", $this->currPos, 3, false) === 0) {
      $r12 = "{{{";
      $this->currPos += 3;
      $r11 = true;
    } else {
      $r12 = self::$FAILED;
      break;
    }
  }
  if ($r11===self::$FAILED) {
    $r9 = self::$FAILED;
    goto seq_3;
  }
  // free $r12
  $p13 = $this->currPos;
  if (($this->input[$this->currPos] ?? null) === "{") {
    $this->currPos++;
    $r12 = "{";
  } else {
    $r12 = self::$FAILED;
  }
  if ($r12 === self::$FAILED) {
    $r12 = false;
  } else {
    $r12 = self::$FAILED;
    $this->currPos = $p13;
    $this->currPos = $p10;
    $r9 = self::$FAILED;
    goto seq_3;
  }
  // free $p13
  $r9 = true;
  seq_3:
  if ($r9!==self::$FAILED) {
    $r9 = false;
    $this->currPos = $p8;
  } else {
    $this->currPos = $p6;
    $r5 = self::$FAILED;
    goto seq_2;
  }
  // free $p10
  // free $p8
  $r14 = $this->discardtplarg(true, $boolParams, $param_th);
  if ($r14===self::$FAILED) {
    $this->currPos = $p6;
    $r5 = self::$FAILED;
    goto seq_2;
  }
  $r5 = true;
  seq_2:
  if ($r5!==self::$FAILED) {
    $r5 = false;
    $this->currPos = $p4;
  } else {
    $r1 = self::$FAILED;
    goto seq_1;
  }
  // free $p6
  // free $p4
  // start choice_2
  $r15 = $this->parsetemplate($silence, $boolParams, $param_th);
  if ($r15!==self::$FAILED) {
    goto choice_2;
  }
  $r15 = $this->parsebroken_template($silence, $param_preproc);
  choice_2:
  // a <- $r15
  if ($r15===self::$FAILED) {
    $this->currPos = $p3;
    $r1 = self::$FAILED;
    goto seq_1;
  }
  $r1 = true;
  seq_1:
  if ($r1!==self::$FAILED) {
    $this->savedPos = $p2;
    $r1 = $this->a51($r15);
    goto choice_1;
  }
  // free $p3
  $p3 = $this->currPos;
  // start seq_4
  $p4 = $this->currPos;
  $p6 = $this->currPos;
  // start seq_5
  $p8 = $this->currPos;
  if (($this->input[$this->currPos] ?? null) === "{") {
    $this->currPos++;
    $r17 = "{";
  } else {
    if (!$silence) {$this->fail(29);}
    $r17 = self::$FAILED;
    $r16 = self::$FAILED;
    goto seq_5;
  }
  $p10 = $this->currPos;
  // start seq_6
  $p13 = $this->currPos;
  $r19 = self::$FAILED;
  for (;;) {
    if ($this->currPos >= $this->inputLength ? false : substr_compare($this->input, "{{{", $this->currPos, 3, false) === 0) {
      $r20 = "{{{";
      $this->currPos += 3;
      $r19 = true;
    } else {
      $r20 = self::$FAILED;
      break;
    }
  }
  if ($r19===self::$FAILED) {
    $r18 = self::$FAILED;
    goto seq_6;
  }
  // free $r20
  $p21 = $this->currPos;
  if (($this->input[$this->currPos] ?? null) === "{") {
    $this->currPos++;
    $r20 = "{";
  } else {
    $r20 = self::$FAILED;
  }
  if ($r20 === self::$FAILED) {
    $r20 = false;
  } else {
    $r20 = self::$FAILED;
    $this->currPos = $p21;
    $this->currPos = $p13;
    $r18 = self::$FAILED;
    goto seq_6;
  }
  // free $p21
  $r18 = true;
  seq_6:
  if ($r18!==self::$FAILED) {
    $r18 = false;
    $this->currPos = $p10;
  } else {
    $this->currPos = $p8;
    $r16 = self::$FAILED;
    goto seq_5;
  }
  // free $p13
  // free $p10
  $r16 = true;
  seq_5:
  if ($r16===self::$FAILED) {
    $r16 = null;
  }
  // free $p8
  // a <- $r16
  $r16 = substr($this->input, $p6, $this->currPos - $p6);
  // free $p6
  $r22 = $this->parsetplarg($silence, $boolParams, $param_th);
  // b <- $r22
  if ($r22===self::$FAILED) {
    $this->currPos = $p4;
    $r1 = self::$FAILED;
    goto seq_4;
  }
  $r1 = true;
  seq_4:
  if ($r1!==self::$FAILED) {
    $this->savedPos = $p3;
    $r1 = $this->a52($r16, $r22);
    goto choice_1;
  }
  // free $p4
  $p4 = $this->currPos;
  // start seq_7
  $p6 = $this->currPos;
  $p8 = $this->currPos;
  // start seq_8
  $p10 = $this->currPos;
  if (($this->input[$this->currPos] ?? null) === "{") {
    $this->currPos++;
    $r24 = "{";
  } else {
    if (!$silence) {$this->fail(29);}
    $r24 = self::$FAILED;
    $r23 = self::$FAILED;
    goto seq_8;
  }
  $p13 = $this->currPos;
  // start seq_9
  $p21 = $this->currPos;
  if ($this->currPos >= $this->inputLength ? false : substr_compare($this->input, "{{", $this->currPos, 2, false) === 0) {
    $r26 = "{{";
    $this->currPos += 2;
  } else {
    $r26 = self::$FAILED;
    $r25 = self::$FAILED;
    goto seq_9;
  }
  $p27 = $this->currPos;
  if (($this->input[$this->currPos] ?? null) === "{") {
    $this->currPos++;
    $r28 = "{";
  } else {
    $r28 = self::$FAILED;
  }
  if ($r28 === self::$FAILED) {
    $r28 = false;
  } else {
    $r28 = self::$FAILED;
    $this->currPos = $p27;
    $this->currPos = $p21;
    $r25 = self::$FAILED;
    goto seq_9;
  }
  // free $p27
  $r25 = true;
  seq_9:
  if ($r25!==self::$FAILED) {
    $r25 = false;
    $this->currPos = $p13;
  } else {
    $this->currPos = $p10;
    $r23 = self::$FAILED;
    goto seq_8;
  }
  // free $p21
  // free $p13
  $r23 = true;
  seq_8:
  if ($r23===self::$FAILED) {
    $r23 = null;
  }
  // free $p10
  // a <- $r23
  $r23 = substr($this->input, $p8, $this->currPos - $p8);
  // free $p8
  $r29 = $this->parsetemplate($silence, $boolParams, $param_th);
  // b <- $r29
  if ($r29===self::$FAILED) {
    $this->currPos = $p6;
    $r1 = self::$FAILED;
    goto seq_7;
  }
  $r1 = true;
  seq_7:
  if ($r1!==self::$FAILED) {
    $this->savedPos = $p4;
    $r1 = $this->a52($r23, $r29);
    goto choice_1;
  }
  // free $p6
  $r1 = $this->parsebroken_template($silence, $param_preproc);
  choice_1:
  $this->cache[$bucket][$key] = new GrammarCacheEntry(
    $this->currPos,
    $r1,
    $saved_preproc !== $param_preproc ? $param_preproc : self::$UNDEFINED,
    $saved_th !== $param_th ? $param_th : self::$UNDEFINED
  );
  return $r1;
}
private function parseraw_htmlentity($silence) {
  $key = 480;
  $bucket = $this->currPos;
  $cached = $this->cache[$bucket][$key] ?? null;
  if ($cached) {
    $this->currPos = $cached->nextPos;

    return $cached->result;
  }

  $p2 = $this->currPos;
  $p4 = $this->currPos;
  // start seq_1
  $p5 = $this->currPos;
  if (($this->input[$this->currPos] ?? null) === "&") {
    $this->currPos++;
    $r6 = "&";
  } else {
    if (!$silence) {$this->fail(4);}
    $r6 = self::$FAILED;
    $r3 = self::$FAILED;
    goto seq_1;
  }
  $r7 = self::$FAILED;
  for (;;) {
    $r8 = self::charAt($this->input, $this->currPos);
    if (preg_match("/^[#0-9a-zA-Z\\x{5e8}\\x{5dc}\\x{5de}\\x{631}\\x{644}\\x{645}]/u", $r8)) {
      $this->currPos += strlen($r8);
      $r7 = true;
    } else {
      $r8 = self::$FAILED;
      if (!$silence) {$this->fail(30);}
      break;
    }
  }
  if ($r7===self::$FAILED) {
    $this->currPos = $p5;
    $r3 = self::$FAILED;
    goto seq_1;
  }
  // free $r8
  if (($this->input[$this->currPos] ?? null) === ";") {
    $this->currPos++;
    $r8 = ";";
  } else {
    if (!$silence) {$this->fail(26);}
    $r8 = self::$FAILED;
    $this->currPos = $p5;
    $r3 = self::$FAILED;
    goto seq_1;
  }
  $r3 = true;
  seq_1:
  // m <- $r3
  if ($r3!==self::$FAILED) {
    $r3 = substr($this->input, $p4, $this->currPos - $p4);
  } else {
    $r3 = self::$FAILED;
  }
  // free $p5
  // free $p4
  $r1 = $r3;
  if ($r1!==self::$FAILED) {
    $this->savedPos = $p2;
    $r1 = $this->a53($r3);
  }
  $this->cache[$bucket][$key] = new GrammarCacheEntry(
    $this->currPos,
    $r1,
    self::$UNDEFINED,
    self::$UNDEFINED
  );
  return $r1;
}
private function parseoptionalSpaceToken($silence) {
  $key = 528;
  $bucket = $this->currPos;
  $cached = $this->cache[$bucket][$key] ?? null;
  if ($cached) {
    $this->currPos = $cached->nextPos;

    return $cached->result;
  }

  $p2 = $this->currPos;
  $r3 = $this->parseoptional_spaces($silence);
  // s <- $r3
  $r1 = $r3;
  if ($r1!==self::$FAILED) {
    $this->savedPos = $p2;
    $r1 = $this->a37($r3);
  }
  $this->cache[$bucket][$key] = new GrammarCacheEntry(
    $this->currPos,
    $r1,
    self::$UNDEFINED,
    self::$UNDEFINED
  );
  return $r1;
}
private function parsetable_attribute_name($silence, $boolParams, &$param_preproc, &$param_th) {
  $key = json_encode([428, $boolParams & 0x3fff, $param_preproc, $param_th]);
  $bucket = $this->currPos;
  $cached = $this->cache[$bucket][$key] ?? null;
  if ($cached) {
    $this->currPos = $cached->nextPos;
    if ($cached->preproc !== self::$UNDEFINED) { $param_preproc = $cached->preproc; }
    if ($cached->th !== self::$UNDEFINED) { $param_th = $cached->th; }
    return $cached->result;
  }
  $saved_preproc=$param_preproc;
  $saved_th=$param_th;
  $p2 = $this->currPos;
  // start seq_1
  $p3 = $this->currPos;
  $p5 = $this->currPos;
  if (strspn($this->input, "\"'=", $this->currPos, 1) !== 0) {
    $r4 = $this->input[$this->currPos++];
  } else {
    $r4 = self::$FAILED;
    if (!$silence) {$this->fail(31);}
    $r4 = null;
  }
  // q <- $r4
  $r4 = substr($this->input, $p5, $this->currPos - $p5);
  // free $p5
  $r6 = [];
  for (;;) {
    // start choice_1
    $p5 = $this->currPos;
    $r7 = self::$FAILED;
    for (;;) {
      if (strcspn($this->input, " \x09\x0d\x0a\x00/=><&{}-!|[", $this->currPos, 1) !== 0) {
        $r8 = self::consumeChar($this->input, $this->currPos);
        $r7 = true;
      } else {
        $r8 = self::$FAILED;
        if (!$silence) {$this->fail(32);}
        break;
      }
    }
    if ($r7!==self::$FAILED) {
      $r7 = substr($this->input, $p5, $this->currPos - $p5);
      goto choice_1;
    } else {
      $r7 = self::$FAILED;
    }
    // free $r8
    // free $p5
    $p5 = $this->currPos;
    // start seq_2
    $p9 = $this->currPos;
    $p10 = $this->currPos;
    $r8 = $this->discardinline_breaks(true, $boolParams, $param_preproc, $param_th);
    if ($r8 === self::$FAILED) {
      $r8 = false;
    } else {
      $r8 = self::$FAILED;
      $this->currPos = $p10;
      $r7 = self::$FAILED;
      goto seq_2;
    }
    // free $p10
    // start choice_2
    $p10 = $this->currPos;
    $r11 = $this->discardwikilink($silence, $boolParams, $param_th, $param_preproc);
    if ($r11!==self::$FAILED) {
      $r11 = substr($this->input, $p10, $this->currPos - $p10);
      goto choice_2;
    } else {
      $r11 = self::$FAILED;
    }
    // free $p10
    $r11 = $this->parsedirective($silence, $boolParams, $param_preproc, $param_th);
    if ($r11!==self::$FAILED) {
      goto choice_2;
    }
    $p10 = $this->currPos;
    // start seq_3
    $p12 = $this->currPos;
    $r13 = $this->parseinline_xmlish_tag($silence, $boolParams, $param_preproc, $param_th);
    // x <- $r13
    if ($r13===self::$FAILED) {
      $r11 = self::$FAILED;
      goto seq_3;
    }
    $r14 = $this->parseinlineline($silence, $boolParams, $param_preproc, $param_th);
    if ($r14===self::$FAILED) {
      $r14 = null;
    }
    // ill <- $r14
    $r11 = true;
    seq_3:
    if ($r11!==self::$FAILED) {
      $this->savedPos = $p10;
      $r11 = $this->a54($r4, $r13, $r14);
      goto choice_2;
    }
    // free $p12
    $p12 = $this->currPos;
    // start seq_4
    $p15 = $this->currPos;
    $p16 = $this->currPos;
    // start choice_3
    $r17 = $this->discardspace_or_newline(true);
    if ($r17!==self::$FAILED) {
      goto choice_3;
    }
    if (strspn($this->input, "\x00/=>", $this->currPos, 1) !== 0) {
      $r17 = $this->input[$this->currPos++];
    } else {
      $r17 = self::$FAILED;
    }
    choice_3:
    if ($r17 === self::$FAILED) {
      $r17 = false;
    } else {
      $r17 = self::$FAILED;
      $this->currPos = $p16;
      $r11 = self::$FAILED;
      goto seq_4;
    }
    // free $p16
    if ($this->currPos < $this->inputLength) {
      $r18 = self::consumeChar($this->input, $this->currPos);;
    } else {
      $r18 = self::$FAILED;
      if (!$silence) {$this->fail(7);}
      $this->currPos = $p15;
      $r11 = self::$FAILED;
      goto seq_4;
    }
    $r11 = true;
    seq_4:
    if ($r11!==self::$FAILED) {
      $r11 = substr($this->input, $p12, $this->currPos - $p12);
    } else {
      $r11 = self::$FAILED;
    }
    // free $p15
    // free $p12
    choice_2:
    // t <- $r11
    if ($r11===self::$FAILED) {
      $this->currPos = $p9;
      $r7 = self::$FAILED;
      goto seq_2;
    }
    $r7 = true;
    seq_2:
    if ($r7!==self::$FAILED) {
      $this->savedPos = $p5;
      $r7 = $this->a55($r4, $r11);
    }
    // free $p9
    choice_1:
    if ($r7!==self::$FAILED) {
      $r6[] = $r7;
    } else {
      break;
    }
  }
  // r <- $r6
  // free $r7
  $this->savedPos = $this->currPos;
  $r7 = $this->a56($r4, $r6);
  if ($r7) {
    $r7 = false;
  } else {
    $r7 = self::$FAILED;
    $this->currPos = $p3;
    $r1 = self::$FAILED;
    goto seq_1;
  }
  $r1 = true;
  seq_1:
  if ($r1!==self::$FAILED) {
    $this->savedPos = $p2;
    $r1 = $this->a57($r4, $r6);
  }
  // free $p3
  $this->cache[$bucket][$key] = new GrammarCacheEntry(
    $this->currPos,
    $r1,
    $saved_preproc !== $param_preproc ? $param_preproc : self::$UNDEFINED,
    $saved_th !== $param_th ? $param_th : self::$UNDEFINED
  );
  return $r1;
}
private function parsetable_att_value($silence, $boolParams, &$param_preproc, &$param_th) {
  $key = json_encode([432, $boolParams & 0x3fff, $param_preproc, $param_th]);
  $bucket = $this->currPos;
  $cached = $this->cache[$bucket][$key] ?? null;
  if ($cached) {
    $this->currPos = $cached->nextPos;
    if ($cached->preproc !== self::$UNDEFINED) { $param_preproc = $cached->preproc; }
    if ($cached->th !== self::$UNDEFINED) { $param_th = $cached->th; }
    return $cached->result;
  }
  $saved_preproc=$param_preproc;
  $saved_th=$param_th;
  // start choice_1
  $p2 = $this->currPos;
  // start seq_1
  $p3 = $this->currPos;
  $p5 = $this->currPos;
  // start seq_2
  $p6 = $this->currPos;
  for (;;) {
    $r8 = $this->discardspace($silence);
    if ($r8===self::$FAILED) {
      break;
    }
  }
  // free $r8
  $r7 = true;
  if ($r7===self::$FAILED) {
    $r4 = self::$FAILED;
    goto seq_2;
  }
  // free $r7
  if (($this->input[$this->currPos] ?? null) === "'") {
    $this->currPos++;
    $r7 = "'";
  } else {
    if (!$silence) {$this->fail(33);}
    $r7 = self::$FAILED;
    $this->currPos = $p6;
    $r4 = self::$FAILED;
    goto seq_2;
  }
  $r4 = true;
  seq_2:
  // s <- $r4
  if ($r4!==self::$FAILED) {
    $r4 = substr($this->input, $p5, $this->currPos - $p5);
  } else {
    $r4 = self::$FAILED;
    $r1 = self::$FAILED;
    goto seq_1;
  }
  // free $p6
  // free $p5
  $r8 = $this->parsetable_attribute_preprocessor_text_single($silence, $boolParams, $param_preproc, $param_th);
  if ($r8===self::$FAILED) {
    $r8 = null;
  }
  // t <- $r8
  $p5 = $this->currPos;
  // start choice_2
  if (($this->input[$this->currPos] ?? null) === "'") {
    $this->currPos++;
    $r9 = "'";
    goto choice_2;
  } else {
    if (!$silence) {$this->fail(33);}
    $r9 = self::$FAILED;
  }
  $p6 = $this->currPos;
  // start choice_3
  if ($this->currPos >= $this->inputLength ? false : substr_compare($this->input, "!!", $this->currPos, 2, false) === 0) {
    $r9 = "!!";
    $this->currPos += 2;
    goto choice_3;
  } else {
    $r9 = self::$FAILED;
  }
  if (strspn($this->input, "|\x0d\x0a", $this->currPos, 1) !== 0) {
    $r9 = $this->input[$this->currPos++];
  } else {
    $r9 = self::$FAILED;
  }
  choice_3:
  if ($r9!==self::$FAILED) {
    $r9 = false;
    $this->currPos = $p6;
  }
  // free $p6
  choice_2:
  // q <- $r9
  if ($r9!==self::$FAILED) {
    $r9 = substr($this->input, $p5, $this->currPos - $p5);
  } else {
    $r9 = self::$FAILED;
    $this->currPos = $p3;
    $r1 = self::$FAILED;
    goto seq_1;
  }
  // free $p5
  $r1 = true;
  seq_1:
  if ($r1!==self::$FAILED) {
    $this->savedPos = $p2;
    $r1 = $this->a58($r4, $r8, $r9);
    goto choice_1;
  }
  // free $p3
  $p3 = $this->currPos;
  // start seq_3
  $p5 = $this->currPos;
  $p6 = $this->currPos;
  // start seq_4
  $p11 = $this->currPos;
  for (;;) {
    $r13 = $this->discardspace($silence);
    if ($r13===self::$FAILED) {
      break;
    }
  }
  // free $r13
  $r12 = true;
  if ($r12===self::$FAILED) {
    $r10 = self::$FAILED;
    goto seq_4;
  }
  // free $r12
  if (($this->input[$this->currPos] ?? null) === "\"") {
    $this->currPos++;
    $r12 = "\"";
  } else {
    if (!$silence) {$this->fail(34);}
    $r12 = self::$FAILED;
    $this->currPos = $p11;
    $r10 = self::$FAILED;
    goto seq_4;
  }
  $r10 = true;
  seq_4:
  // s <- $r10
  if ($r10!==self::$FAILED) {
    $r10 = substr($this->input, $p6, $this->currPos - $p6);
  } else {
    $r10 = self::$FAILED;
    $r1 = self::$FAILED;
    goto seq_3;
  }
  // free $p11
  // free $p6
  $r13 = $this->parsetable_attribute_preprocessor_text_double($silence, $boolParams, $param_preproc, $param_th);
  if ($r13===self::$FAILED) {
    $r13 = null;
  }
  // t <- $r13
  $p6 = $this->currPos;
  // start choice_4
  if (($this->input[$this->currPos] ?? null) === "\"") {
    $this->currPos++;
    $r14 = "\"";
    goto choice_4;
  } else {
    if (!$silence) {$this->fail(34);}
    $r14 = self::$FAILED;
  }
  $p11 = $this->currPos;
  // start choice_5
  if ($this->currPos >= $this->inputLength ? false : substr_compare($this->input, "!!", $this->currPos, 2, false) === 0) {
    $r14 = "!!";
    $this->currPos += 2;
    goto choice_5;
  } else {
    $r14 = self::$FAILED;
  }
  if (strspn($this->input, "|\x0d\x0a", $this->currPos, 1) !== 0) {
    $r14 = $this->input[$this->currPos++];
  } else {
    $r14 = self::$FAILED;
  }
  choice_5:
  if ($r14!==self::$FAILED) {
    $r14 = false;
    $this->currPos = $p11;
  }
  // free $p11
  choice_4:
  // q <- $r14
  if ($r14!==self::$FAILED) {
    $r14 = substr($this->input, $p6, $this->currPos - $p6);
  } else {
    $r14 = self::$FAILED;
    $this->currPos = $p5;
    $r1 = self::$FAILED;
    goto seq_3;
  }
  // free $p6
  $r1 = true;
  seq_3:
  if ($r1!==self::$FAILED) {
    $this->savedPos = $p3;
    $r1 = $this->a58($r10, $r13, $r14);
    goto choice_1;
  }
  // free $p5
  $p5 = $this->currPos;
  // start seq_5
  $p6 = $this->currPos;
  $p11 = $this->currPos;
  for (;;) {
    $r16 = $this->discardspace($silence);
    if ($r16===self::$FAILED) {
      break;
    }
  }
  // free $r16
  $r15 = true;
  // s <- $r15
  if ($r15!==self::$FAILED) {
    $r15 = substr($this->input, $p11, $this->currPos - $p11);
  } else {
    $r15 = self::$FAILED;
    $r1 = self::$FAILED;
    goto seq_5;
  }
  // free $p11
  $r16 = $this->parsetable_attribute_preprocessor_text($silence, $boolParams, $param_preproc, $param_th);
  // t <- $r16
  if ($r16===self::$FAILED) {
    $this->currPos = $p6;
    $r1 = self::$FAILED;
    goto seq_5;
  }
  $p11 = $this->currPos;
  // start choice_6
  $r17 = $this->discardspace_or_newline(true);
  if ($r17!==self::$FAILED) {
    goto choice_6;
  }
  $r17 = $this->discardeof(true);
  if ($r17!==self::$FAILED) {
    goto choice_6;
  }
  if ($this->currPos >= $this->inputLength ? false : substr_compare($this->input, "!!", $this->currPos, 2, false) === 0) {
    $r17 = "!!";
    $this->currPos += 2;
    goto choice_6;
  } else {
    $r17 = self::$FAILED;
  }
  if (($this->input[$this->currPos] ?? null) === "|") {
    $this->currPos++;
    $r17 = "|";
  } else {
    $r17 = self::$FAILED;
  }
  choice_6:
  if ($r17!==self::$FAILED) {
    $r17 = false;
    $this->currPos = $p11;
  } else {
    $this->currPos = $p6;
    $r1 = self::$FAILED;
    goto seq_5;
  }
  // free $p11
  $r1 = true;
  seq_5:
  if ($r1!==self::$FAILED) {
    $this->savedPos = $p5;
    $r1 = $this->a59($r15, $r16);
  }
  // free $p6
  choice_1:
  $this->cache[$bucket][$key] = new GrammarCacheEntry(
    $this->currPos,
    $r1,
    $saved_preproc !== $param_preproc ? $param_preproc : self::$UNDEFINED,
    $saved_th !== $param_th ? $param_th : self::$UNDEFINED
  );
  return $r1;
}
private function discardspace_or_newline_or_solidus($silence) {
  $key = 415;
  $bucket = $this->currPos;
  $cached = $this->cache[$bucket][$key] ?? null;
  if ($cached) {
    $this->currPos = $cached->nextPos;

    return $cached->result;
  }

  // start choice_1
  $r1 = $this->discardspace_or_newline($silence);
  if ($r1!==self::$FAILED) {
    goto choice_1;
  }
  $p2 = $this->currPos;
  // start seq_1
  $p3 = $this->currPos;
  // s <- $r4
  if (($this->input[$this->currPos] ?? null) === "/") {
    $this->currPos++;
    $r4 = "/";
  } else {
    if (!$silence) {$this->fail(35);}
    $r4 = self::$FAILED;
    $r1 = self::$FAILED;
    goto seq_1;
  }
  $p5 = $this->currPos;
  if (($this->input[$this->currPos] ?? null) === ">") {
    $this->currPos++;
    $r6 = ">";
  } else {
    $r6 = self::$FAILED;
  }
  if ($r6 === self::$FAILED) {
    $r6 = false;
  } else {
    $r6 = self::$FAILED;
    $this->currPos = $p5;
    $this->currPos = $p3;
    $r1 = self::$FAILED;
    goto seq_1;
  }
  // free $p5
  $r1 = true;
  seq_1:
  if ($r1!==self::$FAILED) {
    $this->savedPos = $p2;
    $r1 = $this->a42($r4);
  }
  // free $p3
  choice_1:
  $this->cache[$bucket][$key] = new GrammarCacheEntry(
    $this->currPos,
    $r1,
    self::$UNDEFINED,
    self::$UNDEFINED
  );
  return $r1;
}
private function parsegeneric_attribute_name($silence, $boolParams, &$param_preproc, &$param_th) {
  $key = json_encode([424, $boolParams & 0x3fff, $param_preproc, $param_th]);
  $bucket = $this->currPos;
  $cached = $this->cache[$bucket][$key] ?? null;
  if ($cached) {
    $this->currPos = $cached->nextPos;
    if ($cached->preproc !== self::$UNDEFINED) { $param_preproc = $cached->preproc; }
    if ($cached->th !== self::$UNDEFINED) { $param_th = $cached->th; }
    return $cached->result;
  }
  $saved_preproc=$param_preproc;
  $saved_th=$param_th;
  $p2 = $this->currPos;
  // start seq_1
  $p3 = $this->currPos;
  $p5 = $this->currPos;
  if (strspn($this->input, "\"'=", $this->currPos, 1) !== 0) {
    $r4 = $this->input[$this->currPos++];
  } else {
    $r4 = self::$FAILED;
    if (!$silence) {$this->fail(31);}
    $r4 = null;
  }
  // q <- $r4
  $r4 = substr($this->input, $p5, $this->currPos - $p5);
  // free $p5
  $r6 = [];
  for (;;) {
    // start choice_1
    $p5 = $this->currPos;
    $r7 = self::$FAILED;
    for (;;) {
      if (strcspn($this->input, " \x09\x0d\x0a\x00/=><&{}-!|", $this->currPos, 1) !== 0) {
        $r8 = self::consumeChar($this->input, $this->currPos);
        $r7 = true;
      } else {
        $r8 = self::$FAILED;
        if (!$silence) {$this->fail(36);}
        break;
      }
    }
    if ($r7!==self::$FAILED) {
      $r7 = substr($this->input, $p5, $this->currPos - $p5);
      goto choice_1;
    } else {
      $r7 = self::$FAILED;
    }
    // free $r8
    // free $p5
    $p5 = $this->currPos;
    // start seq_2
    $p9 = $this->currPos;
    $p10 = $this->currPos;
    $r8 = $this->discardinline_breaks(true, $boolParams, $param_preproc, $param_th);
    if ($r8 === self::$FAILED) {
      $r8 = false;
    } else {
      $r8 = self::$FAILED;
      $this->currPos = $p10;
      $r7 = self::$FAILED;
      goto seq_2;
    }
    // free $p10
    // start choice_2
    $r11 = $this->parsedirective($silence, $boolParams, $param_preproc, $param_th);
    if ($r11!==self::$FAILED) {
      goto choice_2;
    }
    $r11 = $this->parseless_than($silence, $boolParams);
    if ($r11!==self::$FAILED) {
      goto choice_2;
    }
    $p10 = $this->currPos;
    // start seq_3
    $p12 = $this->currPos;
    $p13 = $this->currPos;
    // start choice_3
    $r14 = $this->discardspace_or_newline(true);
    if ($r14!==self::$FAILED) {
      goto choice_3;
    }
    if (strspn($this->input, "\x00/=><", $this->currPos, 1) !== 0) {
      $r14 = $this->input[$this->currPos++];
    } else {
      $r14 = self::$FAILED;
    }
    choice_3:
    if ($r14 === self::$FAILED) {
      $r14 = false;
    } else {
      $r14 = self::$FAILED;
      $this->currPos = $p13;
      $r11 = self::$FAILED;
      goto seq_3;
    }
    // free $p13
    if ($this->currPos < $this->inputLength) {
      $r15 = self::consumeChar($this->input, $this->currPos);;
    } else {
      $r15 = self::$FAILED;
      if (!$silence) {$this->fail(7);}
      $this->currPos = $p12;
      $r11 = self::$FAILED;
      goto seq_3;
    }
    $r11 = true;
    seq_3:
    if ($r11!==self::$FAILED) {
      $r11 = substr($this->input, $p10, $this->currPos - $p10);
    } else {
      $r11 = self::$FAILED;
    }
    // free $p12
    // free $p10
    choice_2:
    // t <- $r11
    if ($r11===self::$FAILED) {
      $this->currPos = $p9;
      $r7 = self::$FAILED;
      goto seq_2;
    }
    $r7 = true;
    seq_2:
    if ($r7!==self::$FAILED) {
      $this->savedPos = $p5;
      $r7 = $this->a55($r4, $r11);
    }
    // free $p9
    choice_1:
    if ($r7!==self::$FAILED) {
      $r6[] = $r7;
    } else {
      break;
    }
  }
  // r <- $r6
  // free $r7
  $this->savedPos = $this->currPos;
  $r7 = $this->a56($r4, $r6);
  if ($r7) {
    $r7 = false;
  } else {
    $r7 = self::$FAILED;
    $this->currPos = $p3;
    $r1 = self::$FAILED;
    goto seq_1;
  }
  $r1 = true;
  seq_1:
  if ($r1!==self::$FAILED) {
    $this->savedPos = $p2;
    $r1 = $this->a57($r4, $r6);
  }
  // free $p3
  $this->cache[$bucket][$key] = new GrammarCacheEntry(
    $this->currPos,
    $r1,
    $saved_preproc !== $param_preproc ? $param_preproc : self::$UNDEFINED,
    $saved_th !== $param_th ? $param_th : self::$UNDEFINED
  );
  return $r1;
}
private function discardspace_or_newline($silence) {
  $key = 531;
  $bucket = $this->currPos;
  $cached = $this->cache[$bucket][$key] ?? null;
  if ($cached) {
    $this->currPos = $cached->nextPos;

    return $cached->result;
  }

  if (strspn($this->input, " \x09\x0a\x0d\x0c", $this->currPos, 1) !== 0) {
    $r1 = $this->input[$this->currPos++];
  } else {
    $r1 = self::$FAILED;
    if (!$silence) {$this->fail(37);}
  }
  $this->cache[$bucket][$key] = new GrammarCacheEntry(
    $this->currPos,
    $r1,
    self::$UNDEFINED,
    self::$UNDEFINED
  );
  return $r1;
}
private function parsegeneric_att_value($silence, $boolParams, &$param_preproc, &$param_th) {
  $key = json_encode([430, $boolParams & 0x3fff, $param_preproc, $param_th]);
  $bucket = $this->currPos;
  $cached = $this->cache[$bucket][$key] ?? null;
  if ($cached) {
    $this->currPos = $cached->nextPos;
    if ($cached->preproc !== self::$UNDEFINED) { $param_preproc = $cached->preproc; }
    if ($cached->th !== self::$UNDEFINED) { $param_th = $cached->th; }
    return $cached->result;
  }
  $saved_preproc=$param_preproc;
  $saved_th=$param_th;
  // start choice_1
  $p2 = $this->currPos;
  // start seq_1
  $p3 = $this->currPos;
  $p5 = $this->currPos;
  // start seq_2
  $p6 = $this->currPos;
  for (;;) {
    $r8 = $this->discardspace_or_newline($silence);
    if ($r8===self::$FAILED) {
      break;
    }
  }
  // free $r8
  $r7 = true;
  if ($r7===self::$FAILED) {
    $r4 = self::$FAILED;
    goto seq_2;
  }
  // free $r7
  if (($this->input[$this->currPos] ?? null) === "'") {
    $this->currPos++;
    $r7 = "'";
  } else {
    if (!$silence) {$this->fail(33);}
    $r7 = self::$FAILED;
    $this->currPos = $p6;
    $r4 = self::$FAILED;
    goto seq_2;
  }
  $r4 = true;
  seq_2:
  // s <- $r4
  if ($r4!==self::$FAILED) {
    $r4 = substr($this->input, $p5, $this->currPos - $p5);
  } else {
    $r4 = self::$FAILED;
    $r1 = self::$FAILED;
    goto seq_1;
  }
  // free $p6
  // free $p5
  $r8 = $this->parseattribute_preprocessor_text_single($silence, $boolParams, $param_preproc, $param_th);
  if ($r8===self::$FAILED) {
    $r8 = null;
  }
  // t <- $r8
  $p5 = $this->currPos;
  // start choice_2
  if (($this->input[$this->currPos] ?? null) === "'") {
    $this->currPos++;
    $r9 = "'";
    goto choice_2;
  } else {
    if (!$silence) {$this->fail(33);}
    $r9 = self::$FAILED;
  }
  $p6 = $this->currPos;
  // start seq_3
  $p10 = $this->currPos;
  if (($this->input[$this->currPos] ?? null) === "/") {
    $this->currPos++;
    $r11 = "/";
  } else {
    $r11 = self::$FAILED;
    $r11 = null;
  }
  if (($this->input[$this->currPos] ?? null) === ">") {
    $this->currPos++;
    $r12 = ">";
  } else {
    $r12 = self::$FAILED;
    $this->currPos = $p10;
    $r9 = self::$FAILED;
    goto seq_3;
  }
  $r9 = true;
  seq_3:
  if ($r9!==self::$FAILED) {
    $r9 = false;
    $this->currPos = $p6;
  }
  // free $p10
  // free $p6
  choice_2:
  // q <- $r9
  if ($r9!==self::$FAILED) {
    $r9 = substr($this->input, $p5, $this->currPos - $p5);
  } else {
    $r9 = self::$FAILED;
    $this->currPos = $p3;
    $r1 = self::$FAILED;
    goto seq_1;
  }
  // free $p5
  $r1 = true;
  seq_1:
  if ($r1!==self::$FAILED) {
    $this->savedPos = $p2;
    $r1 = $this->a58($r4, $r8, $r9);
    goto choice_1;
  }
  // free $p3
  $p3 = $this->currPos;
  // start seq_4
  $p5 = $this->currPos;
  $p6 = $this->currPos;
  // start seq_5
  $p10 = $this->currPos;
  for (;;) {
    $r15 = $this->discardspace_or_newline($silence);
    if ($r15===self::$FAILED) {
      break;
    }
  }
  // free $r15
  $r14 = true;
  if ($r14===self::$FAILED) {
    $r13 = self::$FAILED;
    goto seq_5;
  }
  // free $r14
  if (($this->input[$this->currPos] ?? null) === "\"") {
    $this->currPos++;
    $r14 = "\"";
  } else {
    if (!$silence) {$this->fail(34);}
    $r14 = self::$FAILED;
    $this->currPos = $p10;
    $r13 = self::$FAILED;
    goto seq_5;
  }
  $r13 = true;
  seq_5:
  // s <- $r13
  if ($r13!==self::$FAILED) {
    $r13 = substr($this->input, $p6, $this->currPos - $p6);
  } else {
    $r13 = self::$FAILED;
    $r1 = self::$FAILED;
    goto seq_4;
  }
  // free $p10
  // free $p6
  $r15 = $this->parseattribute_preprocessor_text_double($silence, $boolParams, $param_preproc, $param_th);
  if ($r15===self::$FAILED) {
    $r15 = null;
  }
  // t <- $r15
  $p6 = $this->currPos;
  // start choice_3
  if (($this->input[$this->currPos] ?? null) === "\"") {
    $this->currPos++;
    $r16 = "\"";
    goto choice_3;
  } else {
    if (!$silence) {$this->fail(34);}
    $r16 = self::$FAILED;
  }
  $p10 = $this->currPos;
  // start seq_6
  $p17 = $this->currPos;
  if (($this->input[$this->currPos] ?? null) === "/") {
    $this->currPos++;
    $r18 = "/";
  } else {
    $r18 = self::$FAILED;
    $r18 = null;
  }
  if (($this->input[$this->currPos] ?? null) === ">") {
    $this->currPos++;
    $r19 = ">";
  } else {
    $r19 = self::$FAILED;
    $this->currPos = $p17;
    $r16 = self::$FAILED;
    goto seq_6;
  }
  $r16 = true;
  seq_6:
  if ($r16!==self::$FAILED) {
    $r16 = false;
    $this->currPos = $p10;
  }
  // free $p17
  // free $p10
  choice_3:
  // q <- $r16
  if ($r16!==self::$FAILED) {
    $r16 = substr($this->input, $p6, $this->currPos - $p6);
  } else {
    $r16 = self::$FAILED;
    $this->currPos = $p5;
    $r1 = self::$FAILED;
    goto seq_4;
  }
  // free $p6
  $r1 = true;
  seq_4:
  if ($r1!==self::$FAILED) {
    $this->savedPos = $p3;
    $r1 = $this->a58($r13, $r15, $r16);
    goto choice_1;
  }
  // free $p5
  $p5 = $this->currPos;
  // start seq_7
  $p6 = $this->currPos;
  $p10 = $this->currPos;
  for (;;) {
    $r21 = $this->discardspace_or_newline($silence);
    if ($r21===self::$FAILED) {
      break;
    }
  }
  // free $r21
  $r20 = true;
  // s <- $r20
  if ($r20!==self::$FAILED) {
    $r20 = substr($this->input, $p10, $this->currPos - $p10);
  } else {
    $r20 = self::$FAILED;
    $r1 = self::$FAILED;
    goto seq_7;
  }
  // free $p10
  $r21 = $this->parseattribute_preprocessor_text($silence, $boolParams, $param_preproc, $param_th);
  // t <- $r21
  if ($r21===self::$FAILED) {
    $this->currPos = $p6;
    $r1 = self::$FAILED;
    goto seq_7;
  }
  $p10 = $this->currPos;
  // start choice_4
  $r22 = $this->discardspace_or_newline(true);
  if ($r22!==self::$FAILED) {
    goto choice_4;
  }
  $r22 = $this->discardeof(true);
  if ($r22!==self::$FAILED) {
    goto choice_4;
  }
  // start seq_8
  $p17 = $this->currPos;
  if (($this->input[$this->currPos] ?? null) === "/") {
    $this->currPos++;
    $r23 = "/";
  } else {
    $r23 = self::$FAILED;
    $r23 = null;
  }
  if (($this->input[$this->currPos] ?? null) === ">") {
    $this->currPos++;
    $r24 = ">";
  } else {
    $r24 = self::$FAILED;
    $this->currPos = $p17;
    $r22 = self::$FAILED;
    goto seq_8;
  }
  $r22 = true;
  seq_8:
  // free $p17
  choice_4:
  if ($r22!==self::$FAILED) {
    $r22 = false;
    $this->currPos = $p10;
  } else {
    $this->currPos = $p6;
    $r1 = self::$FAILED;
    goto seq_7;
  }
  // free $p10
  $r1 = true;
  seq_7:
  if ($r1!==self::$FAILED) {
    $this->savedPos = $p5;
    $r1 = $this->a59($r20, $r21);
  }
  // free $p6
  choice_1:
  $this->cache[$bucket][$key] = new GrammarCacheEntry(
    $this->currPos,
    $r1,
    $saved_preproc !== $param_preproc ? $param_preproc : self::$UNDEFINED,
    $saved_th !== $param_th ? $param_th : self::$UNDEFINED
  );
  return $r1;
}
private function parseextlink_nonipv6url_parameterized($silence, $boolParams, &$param_preproc, &$param_th) {
  $key = json_encode([492, $boolParams & 0x3fff, $param_preproc, $param_th]);
  $bucket = $this->currPos;
  $cached = $this->cache[$bucket][$key] ?? null;
  if ($cached) {
    $this->currPos = $cached->nextPos;
    if ($cached->preproc !== self::$UNDEFINED) { $param_preproc = $cached->preproc; }
    if ($cached->th !== self::$UNDEFINED) { $param_th = $cached->th; }
    return $cached->result;
  }
  $saved_preproc=$param_preproc;
  $saved_th=$param_th;
  $p2 = $this->currPos;
  $r3 = [];
  for (;;) {
    // start choice_1
    $p5 = $this->currPos;
    $r4 = self::$FAILED;
    for (;;) {
      $r6 = self::charAt($this->input, $this->currPos);
      if (preg_match("/^[^<\\[{\\x0a\\x0d|!\\]}\\-\\x09&=\"' \\x{a0}\\x{1680}\\x{180e}\\x{2000}-\\x{200a}\\x{202f}\\x{205f}\\x{3000}]/u", $r6)) {
        $this->currPos += strlen($r6);
        $r4 = true;
      } else {
        $r6 = self::$FAILED;
        if (!$silence) {$this->fail(38);}
        break;
      }
    }
    if ($r4!==self::$FAILED) {
      $r4 = substr($this->input, $p5, $this->currPos - $p5);
      goto choice_1;
    } else {
      $r4 = self::$FAILED;
    }
    // free $r6
    // free $p5
    $p5 = $this->currPos;
    // start seq_1
    $p7 = $this->currPos;
    $p8 = $this->currPos;
    $r6 = $this->discardinline_breaks(true, $boolParams, $param_preproc, $param_th);
    if ($r6 === self::$FAILED) {
      $r6 = false;
    } else {
      $r6 = self::$FAILED;
      $this->currPos = $p8;
      $r4 = self::$FAILED;
      goto seq_1;
    }
    // free $p8
    // start choice_2
    $r9 = $this->parsedirective($silence, $boolParams, $param_preproc, $param_th);
    if ($r9!==self::$FAILED) {
      goto choice_2;
    }
    if (strspn($this->input, "&|{-!}=", $this->currPos, 1) !== 0) {
      $r9 = $this->input[$this->currPos++];
    } else {
      $r9 = self::$FAILED;
      if (!$silence) {$this->fail(39);}
    }
    choice_2:
    // s <- $r9
    if ($r9===self::$FAILED) {
      $this->currPos = $p7;
      $r4 = self::$FAILED;
      goto seq_1;
    }
    $r4 = true;
    seq_1:
    if ($r4!==self::$FAILED) {
      $this->savedPos = $p5;
      $r4 = $this->a42($r9);
      goto choice_1;
    }
    // free $p7
    $p7 = $this->currPos;
    // start seq_2
    $p8 = $this->currPos;
    $r10 = $this->input[$this->currPos] ?? '';
    if ($r10 === "'") {
      $this->currPos++;
    } else {
      $r10 = self::$FAILED;
      if (!$silence) {$this->fail(40);}
      $r4 = self::$FAILED;
      goto seq_2;
    }
    $p11 = $this->currPos;
    $r12 = $this->input[$this->currPos] ?? '';
    if ($r12 === "'") {
      $this->currPos++;
    } else {
      $r12 = self::$FAILED;
    }
    if ($r12 === self::$FAILED) {
      $r12 = false;
    } else {
      $r12 = self::$FAILED;
      $this->currPos = $p11;
      $this->currPos = $p8;
      $r4 = self::$FAILED;
      goto seq_2;
    }
    // free $p11
    $r4 = true;
    seq_2:
    if ($r4!==self::$FAILED) {
      $r4 = substr($this->input, $p7, $this->currPos - $p7);
    } else {
      $r4 = self::$FAILED;
    }
    // free $p8
    // free $p7
    choice_1:
    if ($r4!==self::$FAILED) {
      $r3[] = $r4;
    } else {
      break;
    }
  }
  if (count($r3) === 0) {
    $r3 = self::$FAILED;
  }
  // r <- $r3
  // free $r4
  $r1 = $r3;
  if ($r1!==self::$FAILED) {
    $this->savedPos = $p2;
    $r1 = $this->a60($r3);
  }
  $this->cache[$bucket][$key] = new GrammarCacheEntry(
    $this->currPos,
    $r1,
    $saved_preproc !== $param_preproc ? $param_preproc : self::$UNDEFINED,
    $saved_th !== $param_th ? $param_th : self::$UNDEFINED
  );
  return $r1;
}
private function parseurltext($silence, $boolParams, &$param_preproc, &$param_th) {
  $key = json_encode([478, $boolParams & 0x3fff, $param_preproc, $param_th]);
  $bucket = $this->currPos;
  $cached = $this->cache[$bucket][$key] ?? null;
  if ($cached) {
    $this->currPos = $cached->nextPos;
    if ($cached->preproc !== self::$UNDEFINED) { $param_preproc = $cached->preproc; }
    if ($cached->th !== self::$UNDEFINED) { $param_th = $cached->th; }
    return $cached->result;
  }
  $saved_preproc=$param_preproc;
  $saved_th=$param_th;
  $r1 = [];
  for (;;) {
    // start choice_1
    $p3 = $this->currPos;
    // start seq_1
    $p4 = $this->currPos;
    $this->savedPos = $this->currPos;
    $r5 = $this->a61();
    if ($r5) {
      $r5 = false;
    } else {
      $r5 = self::$FAILED;
      $r2 = self::$FAILED;
      goto seq_1;
    }
    $r2 = true;
    seq_1:
    if ($r2!==self::$FAILED) {
      $this->savedPos = $p3;
      $r2 = $this->a62();
      goto choice_1;
    }
    // free $p4
    $p4 = $this->currPos;
    // start seq_2
    $p6 = $this->currPos;
    $this->savedPos = $this->currPos;
    $r7 = $this->a63();
    if ($r7) {
      $r7 = false;
    } else {
      $r7 = self::$FAILED;
      $r2 = self::$FAILED;
      goto seq_2;
    }
    $r8 = $this->parseautolink($silence, $boolParams, $param_preproc, $param_th);
    // al <- $r8
    if ($r8===self::$FAILED) {
      $this->currPos = $p6;
      $r2 = self::$FAILED;
      goto seq_2;
    }
    $r2 = true;
    seq_2:
    if ($r2!==self::$FAILED) {
      $this->savedPos = $p4;
      $r2 = $this->a64($r8);
      goto choice_1;
    }
    // free $p6
    $p6 = $this->currPos;
    // start seq_3
    $p9 = $this->currPos;
    $p10 = $this->currPos;
    if (($this->input[$this->currPos] ?? null) === "&") {
      $this->currPos++;
      $r11 = "&";
      $r11 = false;
      $this->currPos = $p10;
    } else {
      $r11 = self::$FAILED;
      $r2 = self::$FAILED;
      goto seq_3;
    }
    // free $p10
    $r12 = $this->parsehtmlentity($silence);
    // he <- $r12
    if ($r12===self::$FAILED) {
      $this->currPos = $p9;
      $r2 = self::$FAILED;
      goto seq_3;
    }
    $r2 = true;
    seq_3:
    if ($r2!==self::$FAILED) {
      $this->savedPos = $p6;
      $r2 = $this->a65($r12);
      goto choice_1;
    }
    // free $p9
    $p9 = $this->currPos;
    // start seq_4
    $p10 = $this->currPos;
    $p13 = $this->currPos;
    if ($this->currPos >= $this->inputLength ? false : substr_compare($this->input, "__", $this->currPos, 2, false) === 0) {
      $r14 = "__";
      $this->currPos += 2;
      $r14 = false;
      $this->currPos = $p13;
    } else {
      $r14 = self::$FAILED;
      $r2 = self::$FAILED;
      goto seq_4;
    }
    // free $p13
    $r15 = $this->parsebehavior_switch($silence);
    // bs <- $r15
    if ($r15===self::$FAILED) {
      $this->currPos = $p10;
      $r2 = self::$FAILED;
      goto seq_4;
    }
    $r2 = true;
    seq_4:
    if ($r2!==self::$FAILED) {
      $this->savedPos = $p9;
      $r2 = $this->a66($r15);
      goto choice_1;
    }
    // free $p10
    $r2 = $this->parsetext_char($silence);
    choice_1:
    if ($r2!==self::$FAILED) {
      $r1[] = $r2;
    } else {
      break;
    }
  }
  if (count($r1) === 0) {
    $r1 = self::$FAILED;
  }
  // free $r2
  $this->cache[$bucket][$key] = new GrammarCacheEntry(
    $this->currPos,
    $r1,
    $saved_preproc !== $param_preproc ? $param_preproc : self::$UNDEFINED,
    $saved_th !== $param_th ? $param_th : self::$UNDEFINED
  );
  return $r1;
}
private function parseinline_element($silence, $boolParams, &$param_preproc, &$param_th) {
  $key = json_encode([308, $boolParams & 0x3fff, $param_preproc, $param_th]);
  $bucket = $this->currPos;
  $cached = $this->cache[$bucket][$key] ?? null;
  if ($cached) {
    $this->currPos = $cached->nextPos;
    if ($cached->preproc !== self::$UNDEFINED) { $param_preproc = $cached->preproc; }
    if ($cached->th !== self::$UNDEFINED) { $param_th = $cached->th; }
    return $cached->result;
  }
  $saved_preproc=$param_preproc;
  $saved_th=$param_th;
  // start choice_1
  $p2 = $this->currPos;
  // start seq_1
  $p3 = $this->currPos;
  $p4 = $this->currPos;
  if (($this->input[$this->currPos] ?? null) === "<") {
    $this->currPos++;
    $r5 = "<";
    $r5 = false;
    $this->currPos = $p4;
  } else {
    $r5 = self::$FAILED;
    $r1 = self::$FAILED;
    goto seq_1;
  }
  // free $p4
  // start choice_2
  $r6 = $this->parseinline_xmlish_tag($silence, $boolParams, $param_preproc, $param_th);
  if ($r6!==self::$FAILED) {
    goto choice_2;
  }
  $r6 = $this->parsecomment($silence);
  choice_2:
  // r <- $r6
  if ($r6===self::$FAILED) {
    $this->currPos = $p3;
    $r1 = self::$FAILED;
    goto seq_1;
  }
  $r1 = true;
  seq_1:
  if ($r1!==self::$FAILED) {
    $this->savedPos = $p2;
    $r1 = $this->a24($r6);
    goto choice_1;
  }
  // free $p3
  $p3 = $this->currPos;
  // start seq_2
  $p4 = $this->currPos;
  $p7 = $this->currPos;
  if (($this->input[$this->currPos] ?? null) === "{") {
    $this->currPos++;
    $r8 = "{";
    $r8 = false;
    $this->currPos = $p7;
  } else {
    $r8 = self::$FAILED;
    $r1 = self::$FAILED;
    goto seq_2;
  }
  // free $p7
  $r9 = $this->parsetplarg_or_template($silence, $boolParams, $param_th, $param_preproc);
  // r <- $r9
  if ($r9===self::$FAILED) {
    $this->currPos = $p4;
    $r1 = self::$FAILED;
    goto seq_2;
  }
  $r1 = true;
  seq_2:
  if ($r1!==self::$FAILED) {
    $this->savedPos = $p3;
    $r1 = $this->a24($r9);
    goto choice_1;
  }
  // free $p4
  $p4 = $this->currPos;
  // start seq_3
  $p7 = $this->currPos;
  $p10 = $this->currPos;
  if ($this->currPos >= $this->inputLength ? false : substr_compare($this->input, "-{", $this->currPos, 2, false) === 0) {
    $r11 = "-{";
    $this->currPos += 2;
    $r11 = false;
    $this->currPos = $p10;
  } else {
    $r11 = self::$FAILED;
    $r1 = self::$FAILED;
    goto seq_3;
  }
  // free $p10
  $r12 = $this->parselang_variant_or_tpl($silence, $boolParams, $param_th, $param_preproc);
  // r <- $r12
  if ($r12===self::$FAILED) {
    $this->currPos = $p7;
    $r1 = self::$FAILED;
    goto seq_3;
  }
  $r1 = true;
  seq_3:
  if ($r1!==self::$FAILED) {
    $this->savedPos = $p4;
    $r1 = $this->a24($r12);
    goto choice_1;
  }
  // free $p7
  $p7 = $this->currPos;
  $r1 = self::$FAILED;
  for (;;) {
    // start seq_4
    $p10 = $this->currPos;
    if ($this->currPos >= $this->inputLength ? false : substr_compare($this->input, "[[", $this->currPos, 2, false) === 0) {
      $r14 = "[[";
      $this->currPos += 2;
    } else {
      if (!$silence) {$this->fail(41);}
      $r14 = self::$FAILED;
      $r13 = self::$FAILED;
      goto seq_4;
    }
    $p15 = $this->currPos;
    if (($this->input[$this->currPos] ?? null) === "[") {
      $this->currPos++;
      $r16 = "[";
      $r16 = false;
      $this->currPos = $p15;
    } else {
      $r16 = self::$FAILED;
      $this->currPos = $p10;
      $r13 = self::$FAILED;
      goto seq_4;
    }
    // free $p15
    $r13 = true;
    seq_4:
    if ($r13!==self::$FAILED) {
      $r1 = true;
    } else {
      break;
    }
    // free $p10
  }
  if ($r1!==self::$FAILED) {
    $r1 = substr($this->input, $p7, $this->currPos - $p7);
    goto choice_1;
  } else {
    $r1 = self::$FAILED;
  }
  // free $r13
  // free $p7
  $p7 = $this->currPos;
  // start seq_5
  $p10 = $this->currPos;
  $p15 = $this->currPos;
  if (($this->input[$this->currPos] ?? null) === "[") {
    $this->currPos++;
    $r13 = "[";
    $r13 = false;
    $this->currPos = $p15;
  } else {
    $r13 = self::$FAILED;
    $r1 = self::$FAILED;
    goto seq_5;
  }
  // free $p15
  // start choice_3
  $r17 = $this->parsewikilink($silence, $boolParams, $param_th, $param_preproc);
  if ($r17!==self::$FAILED) {
    goto choice_3;
  }
  $r17 = $this->parseextlink($silence, $boolParams, $param_preproc, $param_th);
  choice_3:
  // r <- $r17
  if ($r17===self::$FAILED) {
    $this->currPos = $p10;
    $r1 = self::$FAILED;
    goto seq_5;
  }
  $r1 = true;
  seq_5:
  if ($r1!==self::$FAILED) {
    $this->savedPos = $p7;
    $r1 = $this->a24($r17);
    goto choice_1;
  }
  // free $p10
  $p10 = $this->currPos;
  // start seq_6
  $p15 = $this->currPos;
  $p18 = $this->currPos;
  if (($this->input[$this->currPos] ?? null) === "'") {
    $this->currPos++;
    $r19 = "'";
    $r19 = false;
    $this->currPos = $p18;
  } else {
    $r19 = self::$FAILED;
    $r1 = self::$FAILED;
    goto seq_6;
  }
  // free $p18
  $r20 = $this->parsequote($silence);
  // r <- $r20
  if ($r20===self::$FAILED) {
    $this->currPos = $p15;
    $r1 = self::$FAILED;
    goto seq_6;
  }
  $r1 = true;
  seq_6:
  if ($r1!==self::$FAILED) {
    $this->savedPos = $p10;
    $r1 = $this->a24($r20);
  }
  // free $p15
  choice_1:
  $this->cache[$bucket][$key] = new GrammarCacheEntry(
    $this->currPos,
    $r1,
    $saved_preproc !== $param_preproc ? $param_preproc : self::$UNDEFINED,
    $saved_th !== $param_th ? $param_th : self::$UNDEFINED
  );
  return $r1;
}
private function discardlist_char($silence) {
  $key = 443;
  $bucket = $this->currPos;
  $cached = $this->cache[$bucket][$key] ?? null;
  if ($cached) {
    $this->currPos = $cached->nextPos;

    return $cached->result;
  }

  if (strspn($this->input, "*#:;", $this->currPos, 1) !== 0) {
    $r1 = $this->input[$this->currPos++];
  } else {
    $r1 = self::$FAILED;
    if (!$silence) {$this->fail(42);}
  }
  $this->cache[$bucket][$key] = new GrammarCacheEntry(
    $this->currPos,
    $r1,
    self::$UNDEFINED,
    self::$UNDEFINED
  );
  return $r1;
}
private function parselist_char($silence) {
  $key = 442;
  $bucket = $this->currPos;
  $cached = $this->cache[$bucket][$key] ?? null;
  if ($cached) {
    $this->currPos = $cached->nextPos;

    return $cached->result;
  }

  if (strspn($this->input, "*#:;", $this->currPos, 1) !== 0) {
    $r1 = $this->input[$this->currPos++];
  } else {
    $r1 = self::$FAILED;
    if (!$silence) {$this->fail(42);}
  }
  $this->cache[$bucket][$key] = new GrammarCacheEntry(
    $this->currPos,
    $r1,
    self::$UNDEFINED,
    self::$UNDEFINED
  );
  return $r1;
}
private function parseinlineline_break_on_colon($silence, $boolParams, &$param_preproc, &$param_th) {
  $key = json_encode([444, $boolParams & 0x2fff, $param_preproc, $param_th]);
  $bucket = $this->currPos;
  $cached = $this->cache[$bucket][$key] ?? null;
  if ($cached) {
    $this->currPos = $cached->nextPos;
    if ($cached->preproc !== self::$UNDEFINED) { $param_preproc = $cached->preproc; }
    if ($cached->th !== self::$UNDEFINED) { $param_th = $cached->th; }
    return $cached->result;
  }
  $saved_preproc=$param_preproc;
  $saved_th=$param_th;
  $r1 = $this->parseinlineline($silence, $boolParams | 0x1000, $param_preproc, $param_th);
  $this->cache[$bucket][$key] = new GrammarCacheEntry(
    $this->currPos,
    $r1,
    $saved_preproc !== $param_preproc ? $param_preproc : self::$UNDEFINED,
    $saved_th !== $param_th ? $param_th : self::$UNDEFINED
  );
  return $r1;
}
private function discardeolf($silence) {
  $key = 523;
  $bucket = $this->currPos;
  $cached = $this->cache[$bucket][$key] ?? null;
  if ($cached) {
    $this->currPos = $cached->nextPos;

    return $cached->result;
  }

  // start choice_1
  $r1 = $this->discardnewline($silence);
  if ($r1!==self::$FAILED) {
    goto choice_1;
  }
  $r1 = $this->discardeof($silence);
  choice_1:
  $this->cache[$bucket][$key] = new GrammarCacheEntry(
    $this->currPos,
    $r1,
    self::$UNDEFINED,
    self::$UNDEFINED
  );
  return $r1;
}
private function parsetable_line($silence, $boolParams, &$param_preproc, &$param_th) {
  $key = json_encode([450, $boolParams & 0x3fff, $param_preproc, $param_th]);
  $bucket = $this->currPos;
  $cached = $this->cache[$bucket][$key] ?? null;
  if ($cached) {
    $this->currPos = $cached->nextPos;
    if ($cached->preproc !== self::$UNDEFINED) { $param_preproc = $cached->preproc; }
    if ($cached->th !== self::$UNDEFINED) { $param_th = $cached->th; }
    return $cached->result;
  }
  $saved_preproc=$param_preproc;
  $saved_th=$param_th;
  $p2 = $this->currPos;
  // start seq_1
  $p3 = $this->currPos;
  // start choice_1
  $p5 = $this->currPos;
  $r4 = $this->discardinline_breaks(true, $boolParams, $param_preproc, $param_th);
  if ($r4 === self::$FAILED) {
    $r4 = false;
    goto choice_1;
  } else {
    $r4 = self::$FAILED;
    $this->currPos = $p5;
  }
  // free $p5
  $p5 = $this->currPos;
  if ($this->currPos >= $this->inputLength ? false : substr_compare($this->input, "{{!}}", $this->currPos, 5, false) === 0) {
    $r4 = "{{!}}";
    $this->currPos += 5;
    $r4 = false;
    $this->currPos = $p5;
  } else {
    $r4 = self::$FAILED;
  }
  // free $p5
  choice_1:
  if ($r4===self::$FAILED) {
    $r1 = self::$FAILED;
    goto seq_1;
  }
  // start choice_2
  $r6 = $this->parsetable_start_tag($silence, $boolParams, $param_preproc, $param_th);
  if ($r6!==self::$FAILED) {
    goto choice_2;
  }
  $r6 = $this->parsetable_content_line($silence, $boolParams | 0x40, $param_preproc, $param_th);
  if ($r6!==self::$FAILED) {
    goto choice_2;
  }
  $r6 = $this->parsetable_end_tag($silence);
  choice_2:
  // tl <- $r6
  if ($r6===self::$FAILED) {
    $this->currPos = $p3;
    $r1 = self::$FAILED;
    goto seq_1;
  }
  $r1 = true;
  seq_1:
  if ($r1!==self::$FAILED) {
    $this->savedPos = $p2;
    $r1 = $this->a67($r6);
  }
  // free $p3
  $this->cache[$bucket][$key] = new GrammarCacheEntry(
    $this->currPos,
    $r1,
    $saved_preproc !== $param_preproc ? $param_preproc : self::$UNDEFINED,
    $saved_th !== $param_th ? $param_th : self::$UNDEFINED
  );
  return $r1;
}
private function parsesol($silence, $boolParams, &$param_preproc, &$param_th) {
  $key = json_encode([542, $boolParams & 0x3ebf, $param_preproc, $param_th]);
  $bucket = $this->currPos;
  $cached = $this->cache[$bucket][$key] ?? null;
  if ($cached) {
    $this->currPos = $cached->nextPos;
    if ($cached->preproc !== self::$UNDEFINED) { $param_preproc = $cached->preproc; }
    if ($cached->th !== self::$UNDEFINED) { $param_th = $cached->th; }
    return $cached->result;
  }
  $saved_preproc=$param_preproc;
  $saved_th=$param_th;
  // start seq_1
  $p1 = $this->currPos;
  // start choice_1
  $r3 = $this->parseempty_line_with_comments($silence);
  if ($r3!==self::$FAILED) {
    goto choice_1;
  }
  $r3 = $this->parsesol_prefix($silence);
  choice_1:
  if ($r3===self::$FAILED) {
    $r2 = self::$FAILED;
    goto seq_1;
  }
  $r4 = [];
  for (;;) {
    $r5 = $this->parsesol_transparent($silence, $boolParams, $param_preproc, $param_th);
    if ($r5!==self::$FAILED) {
      $r4[] = $r5;
    } else {
      break;
    }
  }
  // free $r5
  $r2 = [$r3,$r4];
  seq_1:
  // free $r2,$p1
  $this->cache[$bucket][$key] = new GrammarCacheEntry(
    $this->currPos,
    $r2,
    $saved_preproc !== $param_preproc ? $param_preproc : self::$UNDEFINED,
    $saved_th !== $param_th ? $param_th : self::$UNDEFINED
  );
  return $r2;
}
private function discardcomment($silence) {
  $key = 525;
  $bucket = $this->currPos;
  $cached = $this->cache[$bucket][$key] ?? null;
  if ($cached) {
    $this->currPos = $cached->nextPos;

    return $cached->result;
  }

  $p2 = $this->currPos;
  // start seq_1
  $p3 = $this->currPos;
  if ($this->currPos >= $this->inputLength ? false : substr_compare($this->input, "<!--", $this->currPos, 4, false) === 0) {
    $r4 = "<!--";
    $this->currPos += 4;
  } else {
    if (!$silence) {$this->fail(11);}
    $r4 = self::$FAILED;
    $r1 = self::$FAILED;
    goto seq_1;
  }
  $p6 = $this->currPos;
  for (;;) {
    // start seq_2
    $p8 = $this->currPos;
    $p9 = $this->currPos;
    if ($this->currPos >= $this->inputLength ? false : substr_compare($this->input, "-->", $this->currPos, 3, false) === 0) {
      $r10 = "-->";
      $this->currPos += 3;
    } else {
      $r10 = self::$FAILED;
    }
    if ($r10 === self::$FAILED) {
      $r10 = false;
    } else {
      $r10 = self::$FAILED;
      $this->currPos = $p9;
      $r7 = self::$FAILED;
      goto seq_2;
    }
    // free $p9
    if ($this->currPos < $this->inputLength) {
      $r11 = self::consumeChar($this->input, $this->currPos);;
    } else {
      $r11 = self::$FAILED;
      if (!$silence) {$this->fail(7);}
      $this->currPos = $p8;
      $r7 = self::$FAILED;
      goto seq_2;
    }
    $r7 = true;
    seq_2:
    if ($r7===self::$FAILED) {
      break;
    }
    // free $p8
  }
  // free $r7
  $r5 = true;
  // c <- $r5
  if ($r5!==self::$FAILED) {
    $r5 = substr($this->input, $p6, $this->currPos - $p6);
  } else {
    $r5 = self::$FAILED;
    $this->currPos = $p3;
    $r1 = self::$FAILED;
    goto seq_1;
  }
  // free $p6
  $p6 = $this->currPos;
  // start choice_1
  if ($this->currPos >= $this->inputLength ? false : substr_compare($this->input, "-->", $this->currPos, 3, false) === 0) {
    $r7 = "-->";
    $this->currPos += 3;
    goto choice_1;
  } else {
    if (!$silence) {$this->fail(12);}
    $r7 = self::$FAILED;
  }
  $r7 = $this->discardeof($silence);
  choice_1:
  // cEnd <- $r7
  if ($r7!==self::$FAILED) {
    $r7 = substr($this->input, $p6, $this->currPos - $p6);
  } else {
    $r7 = self::$FAILED;
    $this->currPos = $p3;
    $r1 = self::$FAILED;
    goto seq_1;
  }
  // free $p6
  $r1 = true;
  seq_1:
  if ($r1!==self::$FAILED) {
    $this->savedPos = $p2;
    $r1 = $this->a27($r5, $r7);
  }
  // free $p3
  $this->cache[$bucket][$key] = new GrammarCacheEntry(
    $this->currPos,
    $r1,
    self::$UNDEFINED,
    self::$UNDEFINED
  );
  return $r1;
}
private function discardsof($silence) {
  $key = 515;
  $bucket = $this->currPos;
  $cached = $this->cache[$bucket][$key] ?? null;
  if ($cached) {
    $this->currPos = $cached->nextPos;

    return $cached->result;
  }

  $this->savedPos = $this->currPos;
  $r1 = $this->a68();
  if ($r1) {
    $r1 = false;
  } else {
    $r1 = self::$FAILED;
  }
  $this->cache[$bucket][$key] = new GrammarCacheEntry(
    $this->currPos,
    $r1,
    self::$UNDEFINED,
    self::$UNDEFINED
  );
  return $r1;
}
private function parseredirect($silence, $boolParams, &$param_th, &$param_preproc) {
  $key = json_encode([284, $boolParams & 0x3fff, $param_th, $param_preproc]);
  $bucket = $this->currPos;
  $cached = $this->cache[$bucket][$key] ?? null;
  if ($cached) {
    $this->currPos = $cached->nextPos;
    if ($cached->th !== self::$UNDEFINED) { $param_th = $cached->th; }
    if ($cached->preproc !== self::$UNDEFINED) { $param_preproc = $cached->preproc; }
    return $cached->result;
  }
  $saved_th=$param_th;
  $saved_preproc=$param_preproc;
  $p2 = $this->currPos;
  // start seq_1
  $p3 = $this->currPos;
  $r4 = $this->parseredirect_word($silence);
  // rw <- $r4
  if ($r4===self::$FAILED) {
    $r1 = self::$FAILED;
    goto seq_1;
  }
  $p6 = $this->currPos;
  for (;;) {
    $r7 = $this->discardspace_or_newline($silence);
    if ($r7===self::$FAILED) {
      break;
    }
  }
  // free $r7
  $r5 = true;
  // sp <- $r5
  if ($r5!==self::$FAILED) {
    $r5 = substr($this->input, $p6, $this->currPos - $p6);
  } else {
    $r5 = self::$FAILED;
    $this->currPos = $p3;
    $r1 = self::$FAILED;
    goto seq_1;
  }
  // free $p6
  $p6 = $this->currPos;
  // start seq_2
  $p8 = $this->currPos;
  if (($this->input[$this->currPos] ?? null) === ":") {
    $this->currPos++;
    $r9 = ":";
  } else {
    if (!$silence) {$this->fail(18);}
    $r9 = self::$FAILED;
    $r7 = self::$FAILED;
    goto seq_2;
  }
  for (;;) {
    $r11 = $this->discardspace_or_newline($silence);
    if ($r11===self::$FAILED) {
      break;
    }
  }
  // free $r11
  $r10 = true;
  if ($r10===self::$FAILED) {
    $this->currPos = $p8;
    $r7 = self::$FAILED;
    goto seq_2;
  }
  // free $r10
  $r7 = true;
  seq_2:
  if ($r7===self::$FAILED) {
    $r7 = null;
  }
  // free $p8
  // c <- $r7
  $r7 = substr($this->input, $p6, $this->currPos - $p6);
  // free $p6
  $r10 = $this->parsewikilink($silence, $boolParams, $param_th, $param_preproc);
  // wl <- $r10
  if ($r10===self::$FAILED) {
    $this->currPos = $p3;
    $r1 = self::$FAILED;
    goto seq_1;
  }
  $this->savedPos = $this->currPos;
  $r11 = $this->a69($r4, $r5, $r7, $r10);
  if ($r11) {
    $r11 = false;
  } else {
    $r11 = self::$FAILED;
    $this->currPos = $p3;
    $r1 = self::$FAILED;
    goto seq_1;
  }
  $r1 = true;
  seq_1:
  if ($r1!==self::$FAILED) {
    $this->savedPos = $p2;
    $r1 = $this->a70($r4, $r5, $r7, $r10);
  }
  // free $p3
  $this->cache[$bucket][$key] = new GrammarCacheEntry(
    $this->currPos,
    $r1,
    $saved_preproc !== $param_preproc ? $param_preproc : self::$UNDEFINED,
    $saved_th !== $param_th ? $param_th : self::$UNDEFINED
  );
  return $r1;
}
private function parsesol_transparent($silence, $boolParams, &$param_preproc, &$param_th) {
  $key = json_encode([540, $boolParams & 0x3ebf, $param_preproc, $param_th]);
  $bucket = $this->currPos;
  $cached = $this->cache[$bucket][$key] ?? null;
  if ($cached) {
    $this->currPos = $cached->nextPos;
    if ($cached->preproc !== self::$UNDEFINED) { $param_preproc = $cached->preproc; }
    if ($cached->th !== self::$UNDEFINED) { $param_th = $cached->th; }
    return $cached->result;
  }
  $saved_preproc=$param_preproc;
  $saved_th=$param_th;
  // start choice_1
  $r1 = $this->parsecomment($silence);
  if ($r1!==self::$FAILED) {
    goto choice_1;
  }
  $r1 = $this->parseinclude_limits($silence, $boolParams | 0x2000, $param_preproc, $param_th);
  if ($r1!==self::$FAILED) {
    goto choice_1;
  }
  $r1 = $this->parseannotation_tag($silence, $boolParams, $param_preproc, $param_th);
  if ($r1!==self::$FAILED) {
    goto choice_1;
  }
  $r1 = $this->parsebehavior_switch($silence);
  choice_1:
  $this->cache[$bucket][$key] = new GrammarCacheEntry(
    $this->currPos,
    $r1,
    $saved_preproc !== $param_preproc ? $param_preproc : self::$UNDEFINED,
    $saved_th !== $param_th ? $param_th : self::$UNDEFINED
  );
  return $r1;
}
private function parseblock_line($silence, $boolParams, &$param_preproc, &$param_th) {
  $key = json_encode([298, $boolParams & 0x3fff, $param_preproc, $param_th]);
  $bucket = $this->currPos;
  $cached = $this->cache[$bucket][$key] ?? null;
  if ($cached) {
    $this->currPos = $cached->nextPos;
    if ($cached->preproc !== self::$UNDEFINED) { $param_preproc = $cached->preproc; }
    if ($cached->th !== self::$UNDEFINED) { $param_th = $cached->th; }
    return $cached->result;
  }
  $saved_preproc=$param_preproc;
  $saved_th=$param_th;
  // start choice_1
  $r1 = $this->parseheading($silence, $boolParams, $param_preproc, $param_th);
  if ($r1!==self::$FAILED) {
    goto choice_1;
  }
  $r1 = $this->parselist_item($silence, $boolParams, $param_preproc, $param_th);
  if ($r1!==self::$FAILED) {
    goto choice_1;
  }
  $r1 = $this->parsehr($silence, $boolParams, $param_preproc, $param_th);
  if ($r1!==self::$FAILED) {
    goto choice_1;
  }
  $p2 = $this->currPos;
  // start seq_1
  $p3 = $this->currPos;
  $r4 = $this->parseoptionalSpaceToken($silence);
  // st <- $r4
  if ($r4===self::$FAILED) {
    $r1 = self::$FAILED;
    goto seq_1;
  }
  $p5 = $this->currPos;
  if (strspn($this->input, " <{}|!", $this->currPos, 1) !== 0) {
    $r6 = $this->input[$this->currPos++];
    $r6 = false;
    $this->currPos = $p5;
  } else {
    $r6 = self::$FAILED;
    $this->currPos = $p3;
    $r1 = self::$FAILED;
    goto seq_1;
  }
  // free $p5
  $r7 = $this->parsetable_line($silence, $boolParams, $param_preproc, $param_th);
  // tl <- $r7
  if ($r7===self::$FAILED) {
    $this->currPos = $p3;
    $r1 = self::$FAILED;
    goto seq_1;
  }
  $r1 = true;
  seq_1:
  if ($r1!==self::$FAILED) {
    $this->savedPos = $p2;
    $r1 = $this->a71($r4, $r7);
  }
  // free $p3
  choice_1:
  $this->cache[$bucket][$key] = new GrammarCacheEntry(
    $this->currPos,
    $r1,
    $saved_preproc !== $param_preproc ? $param_preproc : self::$UNDEFINED,
    $saved_th !== $param_th ? $param_th : self::$UNDEFINED
  );
  return $r1;
}
private function parseblock_lines($silence, $boolParams, &$param_preproc, &$param_th) {
  $key = json_encode([294, $boolParams & 0x3fff, $param_preproc, $param_th]);
  $bucket = $this->currPos;
  $cached = $this->cache[$bucket][$key] ?? null;
  if ($cached) {
    $this->currPos = $cached->nextPos;
    if ($cached->preproc !== self::$UNDEFINED) { $param_preproc = $cached->preproc; }
    if ($cached->th !== self::$UNDEFINED) { $param_th = $cached->th; }
    return $cached->result;
  }
  $saved_preproc=$param_preproc;
  $saved_th=$param_th;
  $p2 = $this->currPos;
  // start seq_1
  $p3 = $this->currPos;
  $r4 = $this->parsesol($silence, $boolParams, $param_preproc, $param_th);
  // s <- $r4
  if ($r4===self::$FAILED) {
    $r1 = self::$FAILED;
    goto seq_1;
  }
  $p6 = $this->currPos;
  // start seq_2
  $p7 = $this->currPos;
  $r8 = $this->parseoptionalSpaceToken($silence);
  // os <- $r8
  if ($r8===self::$FAILED) {
    $r5 = self::$FAILED;
    goto seq_2;
  }
  $r9 = $this->parsesol($silence, $boolParams, $param_preproc, $param_th);
  // so <- $r9
  if ($r9===self::$FAILED) {
    $this->currPos = $p7;
    $r5 = self::$FAILED;
    goto seq_2;
  }
  $r5 = true;
  seq_2:
  if ($r5!==self::$FAILED) {
    $this->savedPos = $p6;
    $r5 = $this->a72($r4, $r8, $r9);
  } else {
    $r5 = null;
  }
  // free $p7
  // s2 <- $r5
  $r10 = $this->parseblock_line($silence, $boolParams, $param_preproc, $param_th);
  // bl <- $r10
  if ($r10===self::$FAILED) {
    $this->currPos = $p3;
    $r1 = self::$FAILED;
    goto seq_1;
  }
  $r1 = true;
  seq_1:
  if ($r1!==self::$FAILED) {
    $this->savedPos = $p2;
    $r1 = $this->a73($r4, $r5, $r10);
  }
  // free $p3
  $this->cache[$bucket][$key] = new GrammarCacheEntry(
    $this->currPos,
    $r1,
    $saved_preproc !== $param_preproc ? $param_preproc : self::$UNDEFINED,
    $saved_th !== $param_th ? $param_th : self::$UNDEFINED
  );
  return $r1;
}
private function discardtplarg($silence, $boolParams, &$param_th) {
  $key = json_encode([347, $boolParams & 0x3fff, $param_th]);
  $bucket = $this->currPos;
  $cached = $this->cache[$bucket][$key] ?? null;
  if ($cached) {
    $this->currPos = $cached->nextPos;
    if ($cached->th !== self::$UNDEFINED) { $param_th = $cached->th; }
    return $cached->result;
  }
  $saved_th=$param_th;
  $r1 = $this->discardtplarg_preproc($silence, $boolParams, self::newRef("}}"), $param_th);
  $this->cache[$bucket][$key] = new GrammarCacheEntry(
    $this->currPos,
    $r1,
    self::$UNDEFINED,
    $saved_th !== $param_th ? $param_th : self::$UNDEFINED
  );
  return $r1;
}
private function parsetemplate($silence, $boolParams, &$param_th) {
  $key = json_encode([340, $boolParams & 0x3fff, $param_th]);
  $bucket = $this->currPos;
  $cached = $this->cache[$bucket][$key] ?? null;
  if ($cached) {
    $this->currPos = $cached->nextPos;
    if ($cached->th !== self::$UNDEFINED) { $param_th = $cached->th; }
    return $cached->result;
  }
  $saved_th=$param_th;
  $r1 = $this->parsetemplate_preproc($silence, $boolParams, self::newRef("}}"), $param_th);
  $this->cache[$bucket][$key] = new GrammarCacheEntry(
    $this->currPos,
    $r1,
    self::$UNDEFINED,
    $saved_th !== $param_th ? $param_th : self::$UNDEFINED
  );
  return $r1;
}
private function parsebroken_template($silence, &$param_preproc) {
  $key = json_encode([342, $param_preproc]);
  $bucket = $this->currPos;
  $cached = $this->cache[$bucket][$key] ?? null;
  if ($cached) {
    $this->currPos = $cached->nextPos;
    if ($cached->preproc !== self::$UNDEFINED) { $param_preproc = $cached->preproc; }
    return $cached->result;
  }
  $saved_preproc=$param_preproc;
  $p2 = $this->currPos;
  // start seq_1
  $p3 = $this->currPos;
  // t <- $r4
  if ($this->currPos >= $this->inputLength ? false : substr_compare($this->input, "{{", $this->currPos, 2, false) === 0) {
    $r4 = "{{";
    $this->currPos += 2;
  } else {
    if (!$silence) {$this->fail(43);}
    $r4 = self::$FAILED;
    $this->currPos = $p3;
    $r1 = self::$FAILED;
    goto seq_1;
  }
  $r1 = true;
  seq_1:
  if ($r1!==self::$FAILED) {
    $this->savedPos = $p2;
    $r1 = $this->a74($param_preproc, $r4);
  }
  // free $p3
  $this->cache[$bucket][$key] = new GrammarCacheEntry(
    $this->currPos,
    $r1,
    $saved_preproc !== $param_preproc ? $param_preproc : self::$UNDEFINED,
    self::$UNDEFINED
  );
  return $r1;
}
private function parsetplarg($silence, $boolParams, &$param_th) {
  $key = json_encode([346, $boolParams & 0x3fff, $param_th]);
  $bucket = $this->currPos;
  $cached = $this->cache[$bucket][$key] ?? null;
  if ($cached) {
    $this->currPos = $cached->nextPos;
    if ($cached->th !== self::$UNDEFINED) { $param_th = $cached->th; }
    return $cached->result;
  }
  $saved_th=$param_th;
  $r1 = $this->parsetplarg_preproc($silence, $boolParams, self::newRef("}}"), $param_th);
  $this->cache[$bucket][$key] = new GrammarCacheEntry(
    $this->currPos,
    $r1,
    self::$UNDEFINED,
    $saved_th !== $param_th ? $param_th : self::$UNDEFINED
  );
  return $r1;
}
private function discardwikilink($silence, $boolParams, &$param_th, &$param_preproc) {
  $key = json_encode([391, $boolParams & 0x3fff, $param_th, $param_preproc]);
  $bucket = $this->currPos;
  $cached = $this->cache[$bucket][$key] ?? null;
  if ($cached) {
    $this->currPos = $cached->nextPos;
    if ($cached->th !== self::$UNDEFINED) { $param_th = $cached->th; }
    if ($cached->preproc !== self::$UNDEFINED) { $param_preproc = $cached->preproc; }
    return $cached->result;
  }
  $saved_th=$param_th;
  $saved_preproc=$param_preproc;
  // start choice_1
  $r1 = $this->discardwikilink_preproc($silence, $boolParams, self::newRef("]]"), $param_th);
  if ($r1!==self::$FAILED) {
    goto choice_1;
  }
  $r1 = $this->discardbroken_wikilink($silence, $boolParams, $param_preproc, $param_th);
  choice_1:
  $this->cache[$bucket][$key] = new GrammarCacheEntry(
    $this->currPos,
    $r1,
    $saved_preproc !== $param_preproc ? $param_preproc : self::$UNDEFINED,
    $saved_th !== $param_th ? $param_th : self::$UNDEFINED
  );
  return $r1;
}
private function parsedirective($silence, $boolParams, &$param_preproc, &$param_th) {
  $key = json_encode([486, $boolParams & 0x3fff, $param_preproc, $param_th]);
  $bucket = $this->currPos;
  $cached = $this->cache[$bucket][$key] ?? null;
  if ($cached) {
    $this->currPos = $cached->nextPos;
    if ($cached->preproc !== self::$UNDEFINED) { $param_preproc = $cached->preproc; }
    if ($cached->th !== self::$UNDEFINED) { $param_th = $cached->th; }
    return $cached->result;
  }
  $saved_preproc=$param_preproc;
  $saved_th=$param_th;
  // start choice_1
  $r1 = $this->parsecomment($silence);
  if ($r1!==self::$FAILED) {
    goto choice_1;
  }
  $r1 = $this->parseextension_annotation_tag($silence, $boolParams, $param_preproc, $param_th);
  if ($r1!==self::$FAILED) {
    goto choice_1;
  }
  $r1 = $this->parsetplarg_or_template($silence, $boolParams, $param_th, $param_preproc);
  if ($r1!==self::$FAILED) {
    goto choice_1;
  }
  $p2 = $this->currPos;
  // start seq_1
  $p3 = $this->currPos;
  $p4 = $this->currPos;
  if ($this->currPos >= $this->inputLength ? false : substr_compare($this->input, "-{", $this->currPos, 2, false) === 0) {
    $r5 = "-{";
    $this->currPos += 2;
    $r5 = false;
    $this->currPos = $p4;
  } else {
    $r5 = self::$FAILED;
    $r1 = self::$FAILED;
    goto seq_1;
  }
  // free $p4
  $r6 = $this->parselang_variant_or_tpl($silence, $boolParams, $param_th, $param_preproc);
  // v <- $r6
  if ($r6===self::$FAILED) {
    $this->currPos = $p3;
    $r1 = self::$FAILED;
    goto seq_1;
  }
  $r1 = true;
  seq_1:
  if ($r1!==self::$FAILED) {
    $this->savedPos = $p2;
    $r1 = $this->a75($r6);
    goto choice_1;
  }
  // free $p3
  $p3 = $this->currPos;
  // start seq_2
  $p4 = $this->currPos;
  $p7 = $this->currPos;
  if (($this->input[$this->currPos] ?? null) === "&") {
    $this->currPos++;
    $r8 = "&";
    $r8 = false;
    $this->currPos = $p7;
  } else {
    $r8 = self::$FAILED;
    $r1 = self::$FAILED;
    goto seq_2;
  }
  // free $p7
  $r9 = $this->parsehtmlentity($silence);
  // e <- $r9
  if ($r9===self::$FAILED) {
    $this->currPos = $p4;
    $r1 = self::$FAILED;
    goto seq_2;
  }
  $r1 = true;
  seq_2:
  if ($r1!==self::$FAILED) {
    $this->savedPos = $p3;
    $r1 = $this->a76($r9);
    goto choice_1;
  }
  // free $p4
  $r1 = $this->parseinclude_limits($silence, $boolParams, $param_preproc, $param_th);
  choice_1:
  $this->cache[$bucket][$key] = new GrammarCacheEntry(
    $this->currPos,
    $r1,
    $saved_preproc !== $param_preproc ? $param_preproc : self::$UNDEFINED,
    $saved_th !== $param_th ? $param_th : self::$UNDEFINED
  );
  return $r1;
}
private function parseinline_xmlish_tag($silence, $boolParams, &$param_preproc, &$param_th) {
  $key = json_encode([306, $boolParams & 0x3ebd, $param_preproc, $param_th]);
  $bucket = $this->currPos;
  $cached = $this->cache[$bucket][$key] ?? null;
  if ($cached) {
    $this->currPos = $cached->nextPos;
    if ($cached->preproc !== self::$UNDEFINED) { $param_preproc = $cached->preproc; }
    if ($cached->th !== self::$UNDEFINED) { $param_th = $cached->th; }
    return $cached->result;
  }
  $saved_preproc=$param_preproc;
  $saved_th=$param_th;
  $p2 = $this->currPos;
  // start seq_1
  $p3 = $this->currPos;
  if (($this->input[$this->currPos] ?? null) === "<") {
    $this->currPos++;
    $r4 = "<";
  } else {
    if (!$silence) {$this->fail(44);}
    $r4 = self::$FAILED;
    $r1 = self::$FAILED;
    goto seq_1;
  }
  // start choice_1
  $r5 = $this->parsexmlish_tag($silence, $boolParams | 0x2, $param_preproc, $param_th);
  if ($r5!==self::$FAILED) {
    goto choice_1;
  }
  $r5 = $this->parsexmlish_tag($silence, $boolParams & ~0x2, $param_preproc, $param_th);
  if ($r5!==self::$FAILED) {
    goto choice_1;
  }
  $r5 = $this->parsetvar_old_syntax_closing_HACK($silence);
  choice_1:
  // tag <- $r5
  if ($r5===self::$FAILED) {
    $this->currPos = $p3;
    $r1 = self::$FAILED;
    goto seq_1;
  }
  $r1 = true;
  seq_1:
  if ($r1!==self::$FAILED) {
    $this->savedPos = $p2;
    $r1 = $this->a77($r5);
  }
  // free $p3
  $this->cache[$bucket][$key] = new GrammarCacheEntry(
    $this->currPos,
    $r1,
    $saved_preproc !== $param_preproc ? $param_preproc : self::$UNDEFINED,
    $saved_th !== $param_th ? $param_th : self::$UNDEFINED
  );
  return $r1;
}
private function parsetable_attribute_preprocessor_text_single($silence, $boolParams, &$param_preproc, &$param_th) {
  $key = json_encode([502, $boolParams & 0x3fff, $param_preproc, $param_th]);
  $bucket = $this->currPos;
  $cached = $this->cache[$bucket][$key] ?? null;
  if ($cached) {
    $this->currPos = $cached->nextPos;
    if ($cached->preproc !== self::$UNDEFINED) { $param_preproc = $cached->preproc; }
    if ($cached->th !== self::$UNDEFINED) { $param_th = $cached->th; }
    return $cached->result;
  }
  $saved_preproc=$param_preproc;
  $saved_th=$param_th;
  $p2 = $this->currPos;
  $r3 = [];
  for (;;) {
    // start choice_1
    $p5 = $this->currPos;
    $r4 = self::$FAILED;
    for (;;) {
      if (strcspn($this->input, "{}&<-!['\x0d\x0a|", $this->currPos, 1) !== 0) {
        $r6 = self::consumeChar($this->input, $this->currPos);
        $r4 = true;
      } else {
        $r6 = self::$FAILED;
        if (!$silence) {$this->fail(45);}
        break;
      }
    }
    if ($r4!==self::$FAILED) {
      $r4 = substr($this->input, $p5, $this->currPos - $p5);
      goto choice_1;
    } else {
      $r4 = self::$FAILED;
    }
    // free $r6
    // free $p5
    $p5 = $this->currPos;
    // start seq_1
    $p7 = $this->currPos;
    $p8 = $this->currPos;
    $r6 = $this->discardinline_breaks(true, $boolParams, $param_preproc, $param_th);
    if ($r6 === self::$FAILED) {
      $r6 = false;
    } else {
      $r6 = self::$FAILED;
      $this->currPos = $p8;
      $r4 = self::$FAILED;
      goto seq_1;
    }
    // free $p8
    // start choice_2
    $r9 = $this->parsedirective($silence, $boolParams, $param_preproc, $param_th);
    if ($r9!==self::$FAILED) {
      goto choice_2;
    }
    if (strspn($this->input, "{}&<-![", $this->currPos, 1) !== 0) {
      $r9 = $this->input[$this->currPos++];
    } else {
      $r9 = self::$FAILED;
      if (!$silence) {$this->fail(46);}
    }
    choice_2:
    // s <- $r9
    if ($r9===self::$FAILED) {
      $this->currPos = $p7;
      $r4 = self::$FAILED;
      goto seq_1;
    }
    $r4 = true;
    seq_1:
    if ($r4!==self::$FAILED) {
      $this->savedPos = $p5;
      $r4 = $this->a42($r9);
    }
    // free $p7
    choice_1:
    if ($r4!==self::$FAILED) {
      $r3[] = $r4;
    } else {
      break;
    }
  }
  // r <- $r3
  // free $r4
  $r1 = $r3;
  if ($r1!==self::$FAILED) {
    $this->savedPos = $p2;
    $r1 = $this->a60($r3);
  }
  $this->cache[$bucket][$key] = new GrammarCacheEntry(
    $this->currPos,
    $r1,
    $saved_preproc !== $param_preproc ? $param_preproc : self::$UNDEFINED,
    $saved_th !== $param_th ? $param_th : self::$UNDEFINED
  );
  return $r1;
}
private function parsetable_attribute_preprocessor_text_double($silence, $boolParams, &$param_preproc, &$param_th) {
  $key = json_encode([504, $boolParams & 0x3fff, $param_preproc, $param_th]);
  $bucket = $this->currPos;
  $cached = $this->cache[$bucket][$key] ?? null;
  if ($cached) {
    $this->currPos = $cached->nextPos;
    if ($cached->preproc !== self::$UNDEFINED) { $param_preproc = $cached->preproc; }
    if ($cached->th !== self::$UNDEFINED) { $param_th = $cached->th; }
    return $cached->result;
  }
  $saved_preproc=$param_preproc;
  $saved_th=$param_th;
  $p2 = $this->currPos;
  $r3 = [];
  for (;;) {
    // start choice_1
    $p5 = $this->currPos;
    $r4 = self::$FAILED;
    for (;;) {
      if (strcspn($this->input, "{}&<-![\"\x0d\x0a|", $this->currPos, 1) !== 0) {
        $r6 = self::consumeChar($this->input, $this->currPos);
        $r4 = true;
      } else {
        $r6 = self::$FAILED;
        if (!$silence) {$this->fail(47);}
        break;
      }
    }
    if ($r4!==self::$FAILED) {
      $r4 = substr($this->input, $p5, $this->currPos - $p5);
      goto choice_1;
    } else {
      $r4 = self::$FAILED;
    }
    // free $r6
    // free $p5
    $p5 = $this->currPos;
    // start seq_1
    $p7 = $this->currPos;
    $p8 = $this->currPos;
    $r6 = $this->discardinline_breaks(true, $boolParams, $param_preproc, $param_th);
    if ($r6 === self::$FAILED) {
      $r6 = false;
    } else {
      $r6 = self::$FAILED;
      $this->currPos = $p8;
      $r4 = self::$FAILED;
      goto seq_1;
    }
    // free $p8
    // start choice_2
    $r9 = $this->parsedirective($silence, $boolParams, $param_preproc, $param_th);
    if ($r9!==self::$FAILED) {
      goto choice_2;
    }
    if (strspn($this->input, "{}&<-![", $this->currPos, 1) !== 0) {
      $r9 = $this->input[$this->currPos++];
    } else {
      $r9 = self::$FAILED;
      if (!$silence) {$this->fail(46);}
    }
    choice_2:
    // s <- $r9
    if ($r9===self::$FAILED) {
      $this->currPos = $p7;
      $r4 = self::$FAILED;
      goto seq_1;
    }
    $r4 = true;
    seq_1:
    if ($r4!==self::$FAILED) {
      $this->savedPos = $p5;
      $r4 = $this->a42($r9);
    }
    // free $p7
    choice_1:
    if ($r4!==self::$FAILED) {
      $r3[] = $r4;
    } else {
      break;
    }
  }
  // r <- $r3
  // free $r4
  $r1 = $r3;
  if ($r1!==self::$FAILED) {
    $this->savedPos = $p2;
    $r1 = $this->a60($r3);
  }
  $this->cache[$bucket][$key] = new GrammarCacheEntry(
    $this->currPos,
    $r1,
    $saved_preproc !== $param_preproc ? $param_preproc : self::$UNDEFINED,
    $saved_th !== $param_th ? $param_th : self::$UNDEFINED
  );
  return $r1;
}
private function parsetable_attribute_preprocessor_text($silence, $boolParams, &$param_preproc, &$param_th) {
  $key = json_encode([500, $boolParams & 0x3fff, $param_preproc, $param_th]);
  $bucket = $this->currPos;
  $cached = $this->cache[$bucket][$key] ?? null;
  if ($cached) {
    $this->currPos = $cached->nextPos;
    if ($cached->preproc !== self::$UNDEFINED) { $param_preproc = $cached->preproc; }
    if ($cached->th !== self::$UNDEFINED) { $param_th = $cached->th; }
    return $cached->result;
  }
  $saved_preproc=$param_preproc;
  $saved_th=$param_th;
  $p2 = $this->currPos;
  $r3 = [];
  for (;;) {
    // start choice_1
    $p5 = $this->currPos;
    $r4 = self::$FAILED;
    for (;;) {
      if (strcspn($this->input, "{}&<-![ \x09\x0a\x0d\x0c|", $this->currPos, 1) !== 0) {
        $r6 = self::consumeChar($this->input, $this->currPos);
        $r4 = true;
      } else {
        $r6 = self::$FAILED;
        if (!$silence) {$this->fail(48);}
        break;
      }
    }
    if ($r4!==self::$FAILED) {
      $r4 = substr($this->input, $p5, $this->currPos - $p5);
      goto choice_1;
    } else {
      $r4 = self::$FAILED;
    }
    // free $r6
    // free $p5
    $p5 = $this->currPos;
    // start seq_1
    $p7 = $this->currPos;
    $p8 = $this->currPos;
    $r6 = $this->discardinline_breaks(true, $boolParams, $param_preproc, $param_th);
    if ($r6 === self::$FAILED) {
      $r6 = false;
    } else {
      $r6 = self::$FAILED;
      $this->currPos = $p8;
      $r4 = self::$FAILED;
      goto seq_1;
    }
    // free $p8
    // start choice_2
    $r9 = $this->parsedirective($silence, $boolParams, $param_preproc, $param_th);
    if ($r9!==self::$FAILED) {
      goto choice_2;
    }
    if (strspn($this->input, "{}&<-![", $this->currPos, 1) !== 0) {
      $r9 = $this->input[$this->currPos++];
    } else {
      $r9 = self::$FAILED;
      if (!$silence) {$this->fail(46);}
    }
    choice_2:
    // s <- $r9
    if ($r9===self::$FAILED) {
      $this->currPos = $p7;
      $r4 = self::$FAILED;
      goto seq_1;
    }
    $r4 = true;
    seq_1:
    if ($r4!==self::$FAILED) {
      $this->savedPos = $p5;
      $r4 = $this->a42($r9);
    }
    // free $p7
    choice_1:
    if ($r4!==self::$FAILED) {
      $r3[] = $r4;
    } else {
      break;
    }
  }
  if (count($r3) === 0) {
    $r3 = self::$FAILED;
  }
  // r <- $r3
  // free $r4
  $r1 = $r3;
  if ($r1!==self::$FAILED) {
    $this->savedPos = $p2;
    $r1 = $this->a60($r3);
  }
  $this->cache[$bucket][$key] = new GrammarCacheEntry(
    $this->currPos,
    $r1,
    $saved_preproc !== $param_preproc ? $param_preproc : self::$UNDEFINED,
    $saved_th !== $param_th ? $param_th : self::$UNDEFINED
  );
  return $r1;
}
private function parseless_than($silence, $boolParams) {
  $key = json_encode([422, $boolParams & 0x2]);
  $bucket = $this->currPos;
  $cached = $this->cache[$bucket][$key] ?? null;
  if ($cached) {
    $this->currPos = $cached->nextPos;

    return $cached->result;
  }

  $p1 = $this->currPos;
  // start seq_1
  $p3 = $this->currPos;
  if (/*annOrExtTag*/($boolParams & 0x2) !== 0) {
    $r4 = false;
  } else {
    $r4 = self::$FAILED;
    $r2 = self::$FAILED;
    goto seq_1;
  }
  if (($this->input[$this->currPos] ?? null) === "<") {
    $this->currPos++;
    $r5 = "<";
  } else {
    if (!$silence) {$this->fail(44);}
    $r5 = self::$FAILED;
    $this->currPos = $p3;
    $r2 = self::$FAILED;
    goto seq_1;
  }
  $r2 = true;
  seq_1:
  if ($r2!==self::$FAILED) {
    $r2 = substr($this->input, $p1, $this->currPos - $p1);
  } else {
    $r2 = self::$FAILED;
  }
  // free $p3
  // free $p1
  $this->cache[$bucket][$key] = new GrammarCacheEntry(
    $this->currPos,
    $r2,
    self::$UNDEFINED,
    self::$UNDEFINED
  );
  return $r2;
}
private function parseattribute_preprocessor_text_single($silence, $boolParams, &$param_preproc, &$param_th) {
  $key = json_encode([496, $boolParams & 0x3fff, $param_preproc, $param_th]);
  $bucket = $this->currPos;
  $cached = $this->cache[$bucket][$key] ?? null;
  if ($cached) {
    $this->currPos = $cached->nextPos;
    if ($cached->preproc !== self::$UNDEFINED) { $param_preproc = $cached->preproc; }
    if ($cached->th !== self::$UNDEFINED) { $param_th = $cached->th; }
    return $cached->result;
  }
  $saved_preproc=$param_preproc;
  $saved_th=$param_th;
  $p2 = $this->currPos;
  $r3 = [];
  for (;;) {
    // start choice_1
    $p5 = $this->currPos;
    $r4 = self::$FAILED;
    for (;;) {
      if (strcspn($this->input, "{}&<-|/'>", $this->currPos, 1) !== 0) {
        $r6 = self::consumeChar($this->input, $this->currPos);
        $r4 = true;
      } else {
        $r6 = self::$FAILED;
        if (!$silence) {$this->fail(49);}
        break;
      }
    }
    if ($r4!==self::$FAILED) {
      $r4 = substr($this->input, $p5, $this->currPos - $p5);
      goto choice_1;
    } else {
      $r4 = self::$FAILED;
    }
    // free $r6
    // free $p5
    $p5 = $this->currPos;
    // start seq_1
    $p7 = $this->currPos;
    $p8 = $this->currPos;
    $r6 = $this->discardinline_breaks(true, $boolParams, $param_preproc, $param_th);
    if ($r6 === self::$FAILED) {
      $r6 = false;
    } else {
      $r6 = self::$FAILED;
      $this->currPos = $p8;
      $r4 = self::$FAILED;
      goto seq_1;
    }
    // free $p8
    $p8 = $this->currPos;
    if ($this->currPos >= $this->inputLength ? false : substr_compare($this->input, "/>", $this->currPos, 2, false) === 0) {
      $r9 = "/>";
      $this->currPos += 2;
    } else {
      $r9 = self::$FAILED;
    }
    if ($r9 === self::$FAILED) {
      $r9 = false;
    } else {
      $r9 = self::$FAILED;
      $this->currPos = $p8;
      $this->currPos = $p7;
      $r4 = self::$FAILED;
      goto seq_1;
    }
    // free $p8
    // start choice_2
    $r10 = $this->parsedirective($silence, $boolParams, $param_preproc, $param_th);
    if ($r10!==self::$FAILED) {
      goto choice_2;
    }
    $r10 = $this->parseless_than($silence, $boolParams);
    if ($r10!==self::$FAILED) {
      goto choice_2;
    }
    if (strspn($this->input, "{}&-|/", $this->currPos, 1) !== 0) {
      $r10 = $this->input[$this->currPos++];
    } else {
      $r10 = self::$FAILED;
      if (!$silence) {$this->fail(50);}
    }
    choice_2:
    // s <- $r10
    if ($r10===self::$FAILED) {
      $this->currPos = $p7;
      $r4 = self::$FAILED;
      goto seq_1;
    }
    $r4 = true;
    seq_1:
    if ($r4!==self::$FAILED) {
      $this->savedPos = $p5;
      $r4 = $this->a42($r10);
    }
    // free $p7
    choice_1:
    if ($r4!==self::$FAILED) {
      $r3[] = $r4;
    } else {
      break;
    }
  }
  // r <- $r3
  // free $r4
  $r1 = $r3;
  if ($r1!==self::$FAILED) {
    $this->savedPos = $p2;
    $r1 = $this->a60($r3);
  }
  $this->cache[$bucket][$key] = new GrammarCacheEntry(
    $this->currPos,
    $r1,
    $saved_preproc !== $param_preproc ? $param_preproc : self::$UNDEFINED,
    $saved_th !== $param_th ? $param_th : self::$UNDEFINED
  );
  return $r1;
}
private function parseattribute_preprocessor_text_double($silence, $boolParams, &$param_preproc, &$param_th) {
  $key = json_encode([498, $boolParams & 0x3fff, $param_preproc, $param_th]);
  $bucket = $this->currPos;
  $cached = $this->cache[$bucket][$key] ?? null;
  if ($cached) {
    $this->currPos = $cached->nextPos;
    if ($cached->preproc !== self::$UNDEFINED) { $param_preproc = $cached->preproc; }
    if ($cached->th !== self::$UNDEFINED) { $param_th = $cached->th; }
    return $cached->result;
  }
  $saved_preproc=$param_preproc;
  $saved_th=$param_th;
  $p2 = $this->currPos;
  $r3 = [];
  for (;;) {
    // start choice_1
    $p5 = $this->currPos;
    $r4 = self::$FAILED;
    for (;;) {
      if (strcspn($this->input, "{}&<-|/\">", $this->currPos, 1) !== 0) {
        $r6 = self::consumeChar($this->input, $this->currPos);
        $r4 = true;
      } else {
        $r6 = self::$FAILED;
        if (!$silence) {$this->fail(51);}
        break;
      }
    }
    if ($r4!==self::$FAILED) {
      $r4 = substr($this->input, $p5, $this->currPos - $p5);
      goto choice_1;
    } else {
      $r4 = self::$FAILED;
    }
    // free $r6
    // free $p5
    $p5 = $this->currPos;
    // start seq_1
    $p7 = $this->currPos;
    $p8 = $this->currPos;
    $r6 = $this->discardinline_breaks(true, $boolParams, $param_preproc, $param_th);
    if ($r6 === self::$FAILED) {
      $r6 = false;
    } else {
      $r6 = self::$FAILED;
      $this->currPos = $p8;
      $r4 = self::$FAILED;
      goto seq_1;
    }
    // free $p8
    $p8 = $this->currPos;
    if ($this->currPos >= $this->inputLength ? false : substr_compare($this->input, "/>", $this->currPos, 2, false) === 0) {
      $r9 = "/>";
      $this->currPos += 2;
    } else {
      $r9 = self::$FAILED;
    }
    if ($r9 === self::$FAILED) {
      $r9 = false;
    } else {
      $r9 = self::$FAILED;
      $this->currPos = $p8;
      $this->currPos = $p7;
      $r4 = self::$FAILED;
      goto seq_1;
    }
    // free $p8
    // start choice_2
    $r10 = $this->parsedirective($silence, $boolParams, $param_preproc, $param_th);
    if ($r10!==self::$FAILED) {
      goto choice_2;
    }
    $r10 = $this->parseless_than($silence, $boolParams);
    if ($r10!==self::$FAILED) {
      goto choice_2;
    }
    if (strspn($this->input, "{}&-|/", $this->currPos, 1) !== 0) {
      $r10 = $this->input[$this->currPos++];
    } else {
      $r10 = self::$FAILED;
      if (!$silence) {$this->fail(50);}
    }
    choice_2:
    // s <- $r10
    if ($r10===self::$FAILED) {
      $this->currPos = $p7;
      $r4 = self::$FAILED;
      goto seq_1;
    }
    $r4 = true;
    seq_1:
    if ($r4!==self::$FAILED) {
      $this->savedPos = $p5;
      $r4 = $this->a42($r10);
    }
    // free $p7
    choice_1:
    if ($r4!==self::$FAILED) {
      $r3[] = $r4;
    } else {
      break;
    }
  }
  // r <- $r3
  // free $r4
  $r1 = $r3;
  if ($r1!==self::$FAILED) {
    $this->savedPos = $p2;
    $r1 = $this->a60($r3);
  }
  $this->cache[$bucket][$key] = new GrammarCacheEntry(
    $this->currPos,
    $r1,
    $saved_preproc !== $param_preproc ? $param_preproc : self::$UNDEFINED,
    $saved_th !== $param_th ? $param_th : self::$UNDEFINED
  );
  return $r1;
}
private function parseattribute_preprocessor_text($silence, $boolParams, &$param_preproc, &$param_th) {
  $key = json_encode([494, $boolParams & 0x3fff, $param_preproc, $param_th]);
  $bucket = $this->currPos;
  $cached = $this->cache[$bucket][$key] ?? null;
  if ($cached) {
    $this->currPos = $cached->nextPos;
    if ($cached->preproc !== self::$UNDEFINED) { $param_preproc = $cached->preproc; }
    if ($cached->th !== self::$UNDEFINED) { $param_th = $cached->th; }
    return $cached->result;
  }
  $saved_preproc=$param_preproc;
  $saved_th=$param_th;
  $p2 = $this->currPos;
  $r3 = [];
  for (;;) {
    // start choice_1
    $p5 = $this->currPos;
    $r4 = self::$FAILED;
    for (;;) {
      if (strcspn($this->input, "{}&<-|/ \x09\x0a\x0d\x0c>", $this->currPos, 1) !== 0) {
        $r6 = self::consumeChar($this->input, $this->currPos);
        $r4 = true;
      } else {
        $r6 = self::$FAILED;
        if (!$silence) {$this->fail(52);}
        break;
      }
    }
    if ($r4!==self::$FAILED) {
      $r4 = substr($this->input, $p5, $this->currPos - $p5);
      goto choice_1;
    } else {
      $r4 = self::$FAILED;
    }
    // free $r6
    // free $p5
    $p5 = $this->currPos;
    // start seq_1
    $p7 = $this->currPos;
    $p8 = $this->currPos;
    $r6 = $this->discardinline_breaks(true, $boolParams, $param_preproc, $param_th);
    if ($r6 === self::$FAILED) {
      $r6 = false;
    } else {
      $r6 = self::$FAILED;
      $this->currPos = $p8;
      $r4 = self::$FAILED;
      goto seq_1;
    }
    // free $p8
    $p8 = $this->currPos;
    if ($this->currPos >= $this->inputLength ? false : substr_compare($this->input, "/>", $this->currPos, 2, false) === 0) {
      $r9 = "/>";
      $this->currPos += 2;
    } else {
      $r9 = self::$FAILED;
    }
    if ($r9 === self::$FAILED) {
      $r9 = false;
    } else {
      $r9 = self::$FAILED;
      $this->currPos = $p8;
      $this->currPos = $p7;
      $r4 = self::$FAILED;
      goto seq_1;
    }
    // free $p8
    // start choice_2
    $r10 = $this->parsedirective($silence, $boolParams, $param_preproc, $param_th);
    if ($r10!==self::$FAILED) {
      goto choice_2;
    }
    $r10 = $this->parseless_than($silence, $boolParams);
    if ($r10!==self::$FAILED) {
      goto choice_2;
    }
    if (strspn($this->input, "{}&-|/", $this->currPos, 1) !== 0) {
      $r10 = $this->input[$this->currPos++];
    } else {
      $r10 = self::$FAILED;
      if (!$silence) {$this->fail(50);}
    }
    choice_2:
    // s <- $r10
    if ($r10===self::$FAILED) {
      $this->currPos = $p7;
      $r4 = self::$FAILED;
      goto seq_1;
    }
    $r4 = true;
    seq_1:
    if ($r4!==self::$FAILED) {
      $this->savedPos = $p5;
      $r4 = $this->a42($r10);
    }
    // free $p7
    choice_1:
    if ($r4!==self::$FAILED) {
      $r3[] = $r4;
    } else {
      break;
    }
  }
  if (count($r3) === 0) {
    $r3 = self::$FAILED;
  }
  // r <- $r3
  // free $r4
  $r1 = $r3;
  if ($r1!==self::$FAILED) {
    $this->savedPos = $p2;
    $r1 = $this->a60($r3);
  }
  $this->cache[$bucket][$key] = new GrammarCacheEntry(
    $this->currPos,
    $r1,
    $saved_preproc !== $param_preproc ? $param_preproc : self::$UNDEFINED,
    $saved_th !== $param_th ? $param_th : self::$UNDEFINED
  );
  return $r1;
}
private function parseautolink($silence, $boolParams, &$param_preproc, &$param_th) {
  $key = json_encode([316, $boolParams & 0x3fff, $param_preproc, $param_th]);
  $bucket = $this->currPos;
  $cached = $this->cache[$bucket][$key] ?? null;
  if ($cached) {
    $this->currPos = $cached->nextPos;
    if ($cached->preproc !== self::$UNDEFINED) { $param_preproc = $cached->preproc; }
    if ($cached->th !== self::$UNDEFINED) { $param_th = $cached->th; }
    return $cached->result;
  }
  $saved_preproc=$param_preproc;
  $saved_th=$param_th;
  $p2 = $this->currPos;
  // start seq_1
  $p3 = $this->currPos;
  if (!(/*extlink*/($boolParams & 0x8) !== 0)) {
    $r4 = false;
  } else {
    $r4 = self::$FAILED;
    $r1 = self::$FAILED;
    goto seq_1;
  }
  $this->savedPos = $this->currPos;
  $r5 = $this->a78();
  if (!$r5) {
    $r5 = false;
  } else {
    $r5 = self::$FAILED;
    $this->currPos = $p3;
    $r1 = self::$FAILED;
    goto seq_1;
  }
  // start choice_1
  $r6 = $this->parseautourl($silence, $boolParams, $param_preproc, $param_th);
  if ($r6!==self::$FAILED) {
    goto choice_1;
  }
  $r6 = $this->parseautoref($silence);
  if ($r6!==self::$FAILED) {
    goto choice_1;
  }
  $r6 = $this->parseisbn($silence);
  choice_1:
  // r <- $r6
  if ($r6===self::$FAILED) {
    $this->currPos = $p3;
    $r1 = self::$FAILED;
    goto seq_1;
  }
  $r1 = true;
  seq_1:
  if ($r1!==self::$FAILED) {
    $this->savedPos = $p2;
    $r1 = $this->a24($r6);
  }
  // free $p3
  $this->cache[$bucket][$key] = new GrammarCacheEntry(
    $this->currPos,
    $r1,
    $saved_preproc !== $param_preproc ? $param_preproc : self::$UNDEFINED,
    $saved_th !== $param_th ? $param_th : self::$UNDEFINED
  );
  return $r1;
}
private function parsebehavior_switch($silence) {
  $key = 312;
  $bucket = $this->currPos;
  $cached = $this->cache[$bucket][$key] ?? null;
  if ($cached) {
    $this->currPos = $cached->nextPos;

    return $cached->result;
  }

  $p2 = $this->currPos;
  $p4 = $this->currPos;
  // start seq_1
  $p5 = $this->currPos;
  if ($this->currPos >= $this->inputLength ? false : substr_compare($this->input, "__", $this->currPos, 2, false) === 0) {
    $r6 = "__";
    $this->currPos += 2;
  } else {
    if (!$silence) {$this->fail(53);}
    $r6 = self::$FAILED;
    $r3 = self::$FAILED;
    goto seq_1;
  }
  $r7 = $this->discardbehavior_text($silence);
  if ($r7===self::$FAILED) {
    $this->currPos = $p5;
    $r3 = self::$FAILED;
    goto seq_1;
  }
  if ($this->currPos >= $this->inputLength ? false : substr_compare($this->input, "__", $this->currPos, 2, false) === 0) {
    $r8 = "__";
    $this->currPos += 2;
  } else {
    if (!$silence) {$this->fail(53);}
    $r8 = self::$FAILED;
    $this->currPos = $p5;
    $r3 = self::$FAILED;
    goto seq_1;
  }
  $r3 = true;
  seq_1:
  // bs <- $r3
  if ($r3!==self::$FAILED) {
    $r3 = substr($this->input, $p4, $this->currPos - $p4);
  } else {
    $r3 = self::$FAILED;
  }
  // free $p5
  // free $p4
  $r1 = $r3;
  if ($r1!==self::$FAILED) {
    $this->savedPos = $p2;
    $r1 = $this->a79($r3);
  }
  $this->cache[$bucket][$key] = new GrammarCacheEntry(
    $this->currPos,
    $r1,
    self::$UNDEFINED,
    self::$UNDEFINED
  );
  return $r1;
}
private function parsetext_char($silence) {
  $key = 476;
  $bucket = $this->currPos;
  $cached = $this->cache[$bucket][$key] ?? null;
  if ($cached) {
    $this->currPos = $cached->nextPos;

    return $cached->result;
  }

  if (strcspn($this->input, "-'<[{\x0a\x0d:;]}|!=", $this->currPos, 1) !== 0) {
    $r1 = self::consumeChar($this->input, $this->currPos);
  } else {
    $r1 = self::$FAILED;
    if (!$silence) {$this->fail(54);}
  }
  $this->cache[$bucket][$key] = new GrammarCacheEntry(
    $this->currPos,
    $r1,
    self::$UNDEFINED,
    self::$UNDEFINED
  );
  return $r1;
}
private function parselang_variant_or_tpl($silence, $boolParams, &$param_th, &$param_preproc) {
  $key = json_encode([358, $boolParams & 0x3fff, $param_th, $param_preproc]);
  $bucket = $this->currPos;
  $cached = $this->cache[$bucket][$key] ?? null;
  if ($cached) {
    $this->currPos = $cached->nextPos;
    if ($cached->th !== self::$UNDEFINED) { $param_th = $cached->th; }
    if ($cached->preproc !== self::$UNDEFINED) { $param_preproc = $cached->preproc; }
    return $cached->result;
  }
  $saved_th=$param_th;
  $saved_preproc=$param_preproc;
  // start choice_1
  $p2 = $this->currPos;
  // start seq_1
  $p3 = $this->currPos;
  $p4 = $this->currPos;
  // start seq_2
  $p6 = $this->currPos;
  if ($this->currPos >= $this->inputLength ? false : substr_compare($this->input, "-{", $this->currPos, 2, false) === 0) {
    $r7 = "-{";
    $this->currPos += 2;
  } else {
    $r7 = self::$FAILED;
    $r5 = self::$FAILED;
    goto seq_2;
  }
  $p8 = $this->currPos;
  // start seq_3
  $p10 = $this->currPos;
  $r11 = self::$FAILED;
  for (;;) {
    if ($this->currPos >= $this->inputLength ? false : substr_compare($this->input, "{{{", $this->currPos, 3, false) === 0) {
      $r12 = "{{{";
      $this->currPos += 3;
      $r11 = true;
    } else {
      $r12 = self::$FAILED;
      break;
    }
  }
  if ($r11===self::$FAILED) {
    $r9 = self::$FAILED;
    goto seq_3;
  }
  // free $r12
  $p13 = $this->currPos;
  if (($this->input[$this->currPos] ?? null) === "{") {
    $this->currPos++;
    $r12 = "{";
  } else {
    $r12 = self::$FAILED;
  }
  if ($r12 === self::$FAILED) {
    $r12 = false;
  } else {
    $r12 = self::$FAILED;
    $this->currPos = $p13;
    $this->currPos = $p10;
    $r9 = self::$FAILED;
    goto seq_3;
  }
  // free $p13
  $r9 = true;
  seq_3:
  if ($r9!==self::$FAILED) {
    $r9 = false;
    $this->currPos = $p8;
  } else {
    $this->currPos = $p6;
    $r5 = self::$FAILED;
    goto seq_2;
  }
  // free $p10
  // free $p8
  $r14 = $this->discardtplarg(true, $boolParams, $param_th);
  if ($r14===self::$FAILED) {
    $this->currPos = $p6;
    $r5 = self::$FAILED;
    goto seq_2;
  }
  $r5 = true;
  seq_2:
  if ($r5!==self::$FAILED) {
    $r5 = false;
    $this->currPos = $p4;
  } else {
    $r1 = self::$FAILED;
    goto seq_1;
  }
  // free $p6
  // free $p4
  $r15 = $this->parselang_variant($silence, $boolParams, $param_th, $param_preproc);
  // a <- $r15
  if ($r15===self::$FAILED) {
    $this->currPos = $p3;
    $r1 = self::$FAILED;
    goto seq_1;
  }
  $r1 = true;
  seq_1:
  if ($r1!==self::$FAILED) {
    $this->savedPos = $p2;
    $r1 = $this->a51($r15);
    goto choice_1;
  }
  // free $p3
  $p3 = $this->currPos;
  // start seq_4
  $p4 = $this->currPos;
  $p6 = $this->currPos;
  // start seq_5
  $p8 = $this->currPos;
  if (($this->input[$this->currPos] ?? null) === "-") {
    $this->currPos++;
    $r17 = "-";
  } else {
    if (!$silence) {$this->fail(55);}
    $r17 = self::$FAILED;
    $r16 = self::$FAILED;
    goto seq_5;
  }
  $p10 = $this->currPos;
  // start seq_6
  $p13 = $this->currPos;
  $r19 = self::$FAILED;
  for (;;) {
    if ($this->currPos >= $this->inputLength ? false : substr_compare($this->input, "{{{", $this->currPos, 3, false) === 0) {
      $r20 = "{{{";
      $this->currPos += 3;
      $r19 = true;
    } else {
      $r20 = self::$FAILED;
      break;
    }
  }
  if ($r19===self::$FAILED) {
    $r18 = self::$FAILED;
    goto seq_6;
  }
  // free $r20
  $p21 = $this->currPos;
  if (($this->input[$this->currPos] ?? null) === "{") {
    $this->currPos++;
    $r20 = "{";
  } else {
    $r20 = self::$FAILED;
  }
  if ($r20 === self::$FAILED) {
    $r20 = false;
  } else {
    $r20 = self::$FAILED;
    $this->currPos = $p21;
    $this->currPos = $p13;
    $r18 = self::$FAILED;
    goto seq_6;
  }
  // free $p21
  $r18 = true;
  seq_6:
  if ($r18!==self::$FAILED) {
    $r18 = false;
    $this->currPos = $p10;
  } else {
    $this->currPos = $p8;
    $r16 = self::$FAILED;
    goto seq_5;
  }
  // free $p13
  // free $p10
  $r16 = true;
  seq_5:
  // a <- $r16
  if ($r16!==self::$FAILED) {
    $r16 = substr($this->input, $p6, $this->currPos - $p6);
  } else {
    $r16 = self::$FAILED;
    $r1 = self::$FAILED;
    goto seq_4;
  }
  // free $p8
  // free $p6
  $r22 = $this->parsetplarg($silence, $boolParams, $param_th);
  // b <- $r22
  if ($r22===self::$FAILED) {
    $this->currPos = $p4;
    $r1 = self::$FAILED;
    goto seq_4;
  }
  $r1 = true;
  seq_4:
  if ($r1!==self::$FAILED) {
    $this->savedPos = $p3;
    $r1 = $this->a52($r16, $r22);
    goto choice_1;
  }
  // free $p4
  $p4 = $this->currPos;
  // start seq_7
  $p6 = $this->currPos;
  $p8 = $this->currPos;
  // start seq_8
  $p10 = $this->currPos;
  if (($this->input[$this->currPos] ?? null) === "-") {
    $this->currPos++;
    $r24 = "-";
  } else {
    if (!$silence) {$this->fail(55);}
    $r24 = self::$FAILED;
    $r23 = self::$FAILED;
    goto seq_8;
  }
  $p13 = $this->currPos;
  // start seq_9
  $p21 = $this->currPos;
  if ($this->currPos >= $this->inputLength ? false : substr_compare($this->input, "{{", $this->currPos, 2, false) === 0) {
    $r26 = "{{";
    $this->currPos += 2;
  } else {
    $r26 = self::$FAILED;
    $r25 = self::$FAILED;
    goto seq_9;
  }
  for (;;) {
    if ($this->currPos >= $this->inputLength ? false : substr_compare($this->input, "{{{", $this->currPos, 3, false) === 0) {
      $r28 = "{{{";
      $this->currPos += 3;
    } else {
      $r28 = self::$FAILED;
      break;
    }
  }
  // free $r28
  $r27 = true;
  if ($r27===self::$FAILED) {
    $this->currPos = $p21;
    $r25 = self::$FAILED;
    goto seq_9;
  }
  // free $r27
  $p29 = $this->currPos;
  if (($this->input[$this->currPos] ?? null) === "{") {
    $this->currPos++;
    $r27 = "{";
  } else {
    $r27 = self::$FAILED;
  }
  if ($r27 === self::$FAILED) {
    $r27 = false;
  } else {
    $r27 = self::$FAILED;
    $this->currPos = $p29;
    $this->currPos = $p21;
    $r25 = self::$FAILED;
    goto seq_9;
  }
  // free $p29
  $r25 = true;
  seq_9:
  if ($r25!==self::$FAILED) {
    $r25 = false;
    $this->currPos = $p13;
  } else {
    $this->currPos = $p10;
    $r23 = self::$FAILED;
    goto seq_8;
  }
  // free $p21
  // free $p13
  $r23 = true;
  seq_8:
  // a <- $r23
  if ($r23!==self::$FAILED) {
    $r23 = substr($this->input, $p8, $this->currPos - $p8);
  } else {
    $r23 = self::$FAILED;
    $r1 = self::$FAILED;
    goto seq_7;
  }
  // free $p10
  // free $p8
  $r28 = $this->parsetemplate($silence, $boolParams, $param_th);
  // b <- $r28
  if ($r28===self::$FAILED) {
    $this->currPos = $p6;
    $r1 = self::$FAILED;
    goto seq_7;
  }
  $r1 = true;
  seq_7:
  if ($r1!==self::$FAILED) {
    $this->savedPos = $p4;
    $r1 = $this->a52($r23, $r28);
    goto choice_1;
  }
  // free $p6
  $p6 = $this->currPos;
  // start seq_10
  $p8 = $this->currPos;
  $p10 = $this->currPos;
  if ($this->currPos >= $this->inputLength ? false : substr_compare($this->input, "-{", $this->currPos, 2, false) === 0) {
    $r30 = "-{";
    $this->currPos += 2;
    $r30 = false;
    $this->currPos = $p10;
  } else {
    $r30 = self::$FAILED;
    $r1 = self::$FAILED;
    goto seq_10;
  }
  // free $p10
  $r31 = $this->parselang_variant($silence, $boolParams, $param_th, $param_preproc);
  // a <- $r31
  if ($r31===self::$FAILED) {
    $this->currPos = $p8;
    $r1 = self::$FAILED;
    goto seq_10;
  }
  $r1 = true;
  seq_10:
  if ($r1!==self::$FAILED) {
    $this->savedPos = $p6;
    $r1 = $this->a51($r31);
  }
  // free $p8
  choice_1:
  $this->cache[$bucket][$key] = new GrammarCacheEntry(
    $this->currPos,
    $r1,
    $saved_preproc !== $param_preproc ? $param_preproc : self::$UNDEFINED,
    $saved_th !== $param_th ? $param_th : self::$UNDEFINED
  );
  return $r1;
}
private function parsewikilink($silence, $boolParams, &$param_th, &$param_preproc) {
  $key = json_encode([390, $boolParams & 0x3fff, $param_th, $param_preproc]);
  $bucket = $this->currPos;
  $cached = $this->cache[$bucket][$key] ?? null;
  if ($cached) {
    $this->currPos = $cached->nextPos;
    if ($cached->th !== self::$UNDEFINED) { $param_th = $cached->th; }
    if ($cached->preproc !== self::$UNDEFINED) { $param_preproc = $cached->preproc; }
    return $cached->result;
  }
  $saved_th=$param_th;
  $saved_preproc=$param_preproc;
  // start choice_1
  $r1 = $this->parsewikilink_preproc($silence, $boolParams, self::newRef("]]"), $param_th);
  if ($r1!==self::$FAILED) {
    goto choice_1;
  }
  $r1 = $this->parsebroken_wikilink($silence, $boolParams, $param_preproc, $param_th);
  choice_1:
  $this->cache[$bucket][$key] = new GrammarCacheEntry(
    $this->currPos,
    $r1,
    $saved_preproc !== $param_preproc ? $param_preproc : self::$UNDEFINED,
    $saved_th !== $param_th ? $param_th : self::$UNDEFINED
  );
  return $r1;
}
private function parsequote($silence) {
  $key = 400;
  $bucket = $this->currPos;
  $cached = $this->cache[$bucket][$key] ?? null;
  if ($cached) {
    $this->currPos = $cached->nextPos;

    return $cached->result;
  }

  $p2 = $this->currPos;
  $p4 = $this->currPos;
  // start seq_1
  $p5 = $this->currPos;
  if ($this->currPos >= $this->inputLength ? false : substr_compare($this->input, "''", $this->currPos, 2, false) === 0) {
    $r6 = "''";
    $this->currPos += 2;
  } else {
    if (!$silence) {$this->fail(56);}
    $r6 = self::$FAILED;
    $r3 = self::$FAILED;
    goto seq_1;
  }
  for (;;) {
    if (($this->input[$this->currPos] ?? null) === "'") {
      $this->currPos++;
      $r8 = "'";
    } else {
      if (!$silence) {$this->fail(33);}
      $r8 = self::$FAILED;
      break;
    }
  }
  // free $r8
  $r7 = true;
  if ($r7===self::$FAILED) {
    $this->currPos = $p5;
    $r3 = self::$FAILED;
    goto seq_1;
  }
  // free $r7
  $r3 = true;
  seq_1:
  // quotes <- $r3
  if ($r3!==self::$FAILED) {
    $r3 = substr($this->input, $p4, $this->currPos - $p4);
  } else {
    $r3 = self::$FAILED;
  }
  // free $p5
  // free $p4
  $r1 = $r3;
  if ($r1!==self::$FAILED) {
    $this->savedPos = $p2;
    $r1 = $this->a80($r3);
  }
  $this->cache[$bucket][$key] = new GrammarCacheEntry(
    $this->currPos,
    $r1,
    self::$UNDEFINED,
    self::$UNDEFINED
  );
  return $r1;
}
private function parsetable_content_line($silence, $boolParams, &$param_preproc, &$param_th) {
  $key = json_encode([452, $boolParams & 0x3fff, $param_preproc, $param_th]);
  $bucket = $this->currPos;
  $cached = $this->cache[$bucket][$key] ?? null;
  if ($cached) {
    $this->currPos = $cached->nextPos;
    if ($cached->preproc !== self::$UNDEFINED) { $param_preproc = $cached->preproc; }
    if ($cached->th !== self::$UNDEFINED) { $param_th = $cached->th; }
    return $cached->result;
  }
  $saved_preproc=$param_preproc;
  $saved_th=$param_th;
  // start seq_1
  $p1 = $this->currPos;
  $r3 = [];
  for (;;) {
    // start choice_1
    $r4 = $this->parsespace($silence);
    if ($r4!==self::$FAILED) {
      goto choice_1;
    }
    $r4 = $this->parsecomment($silence);
    choice_1:
    if ($r4!==self::$FAILED) {
      $r3[] = $r4;
    } else {
      break;
    }
  }
  // free $r4
  // start choice_2
  $r4 = $this->parsetable_heading_tags($silence, $boolParams, $param_preproc);
  if ($r4!==self::$FAILED) {
    goto choice_2;
  }
  $r4 = $this->parsetable_row_tag($silence, $boolParams, $param_preproc, $param_th);
  if ($r4!==self::$FAILED) {
    goto choice_2;
  }
  $r4 = $this->parsetable_data_tags($silence, $boolParams, $param_preproc, $param_th);
  if ($r4!==self::$FAILED) {
    goto choice_2;
  }
  $r4 = $this->parsetable_caption_tag($silence, $boolParams, $param_preproc, $param_th);
  choice_2:
  if ($r4===self::$FAILED) {
    $this->currPos = $p1;
    $r2 = self::$FAILED;
    goto seq_1;
  }
  $r2 = [$r3,$r4];
  seq_1:
  // free $r2,$p1
  $this->cache[$bucket][$key] = new GrammarCacheEntry(
    $this->currPos,
    $r2,
    $saved_preproc !== $param_preproc ? $param_preproc : self::$UNDEFINED,
    $saved_th !== $param_th ? $param_th : self::$UNDEFINED
  );
  return $r2;
}
private function parsetable_end_tag($silence) {
  $key = 472;
  $bucket = $this->currPos;
  $cached = $this->cache[$bucket][$key] ?? null;
  if ($cached) {
    $this->currPos = $cached->nextPos;

    return $cached->result;
  }

  $p2 = $this->currPos;
  // start seq_1
  $p3 = $this->currPos;
  $r4 = [];
  for (;;) {
    // start choice_1
    $r5 = $this->parsespace($silence);
    if ($r5!==self::$FAILED) {
      goto choice_1;
    }
    $r5 = $this->parsecomment($silence);
    choice_1:
    if ($r5!==self::$FAILED) {
      $r4[] = $r5;
    } else {
      break;
    }
  }
  // sc <- $r4
  // free $r5
  $p6 = $this->currPos;
  $r5 = '';
  // startPos <- $r5
  if ($r5!==self::$FAILED) {
    $this->savedPos = $p6;
    $r5 = $this->a5($r4);
  } else {
    $this->currPos = $p3;
    $r1 = self::$FAILED;
    goto seq_1;
  }
  $r7 = $this->parsepipe($silence);
  // p <- $r7
  if ($r7===self::$FAILED) {
    $this->currPos = $p3;
    $r1 = self::$FAILED;
    goto seq_1;
  }
  // b <- $r8
  if (($this->input[$this->currPos] ?? null) === "}") {
    $this->currPos++;
    $r8 = "}";
  } else {
    if (!$silence) {$this->fail(57);}
    $r8 = self::$FAILED;
    $this->currPos = $p3;
    $r1 = self::$FAILED;
    goto seq_1;
  }
  $r1 = true;
  seq_1:
  if ($r1!==self::$FAILED) {
    $this->savedPos = $p2;
    $r1 = $this->a81($r4, $r5, $r7, $r8);
  }
  // free $p3
  $this->cache[$bucket][$key] = new GrammarCacheEntry(
    $this->currPos,
    $r1,
    self::$UNDEFINED,
    self::$UNDEFINED
  );
  return $r1;
}
private function parseempty_line_with_comments($silence) {
  $key = 546;
  $bucket = $this->currPos;
  $cached = $this->cache[$bucket][$key] ?? null;
  if ($cached) {
    $this->currPos = $cached->nextPos;

    return $cached->result;
  }

  $p2 = $this->currPos;
  // start seq_1
  $p3 = $this->currPos;
  $r4 = $this->parsesol_prefix($silence);
  // sp <- $r4
  if ($r4===self::$FAILED) {
    $r1 = self::$FAILED;
    goto seq_1;
  }
  $p6 = $this->currPos;
  $r5 = '';
  // p <- $r5
  if ($r5!==self::$FAILED) {
    $this->savedPos = $p6;
    $r5 = $this->a82($r4);
  } else {
    $this->currPos = $p3;
    $r1 = self::$FAILED;
    goto seq_1;
  }
  $r7 = [];
  for (;;) {
    // start seq_2
    $p9 = $this->currPos;
    $r10 = [];
    for (;;) {
      $r11 = $this->parsespace($silence);
      if ($r11!==self::$FAILED) {
        $r10[] = $r11;
      } else {
        break;
      }
    }
    // free $r11
    $r11 = $this->parsecomment($silence);
    if ($r11===self::$FAILED) {
      $this->currPos = $p9;
      $r8 = self::$FAILED;
      goto seq_2;
    }
    $r12 = [];
    for (;;) {
      // start choice_1
      $r13 = $this->parsespace($silence);
      if ($r13!==self::$FAILED) {
        goto choice_1;
      }
      $r13 = $this->parsecomment($silence);
      choice_1:
      if ($r13!==self::$FAILED) {
        $r12[] = $r13;
      } else {
        break;
      }
    }
    // free $r13
    $r13 = $this->parsenewline($silence);
    if ($r13===self::$FAILED) {
      $this->currPos = $p9;
      $r8 = self::$FAILED;
      goto seq_2;
    }
    $r8 = [$r10,$r11,$r12,$r13];
    seq_2:
    if ($r8!==self::$FAILED) {
      $r7[] = $r8;
    } else {
      break;
    }
    // free $p9
  }
  if (count($r7) === 0) {
    $r7 = self::$FAILED;
  }
  // c <- $r7
  if ($r7===self::$FAILED) {
    $this->currPos = $p3;
    $r1 = self::$FAILED;
    goto seq_1;
  }
  // free $r8
  $r1 = true;
  seq_1:
  if ($r1!==self::$FAILED) {
    $this->savedPos = $p2;
    $r1 = $this->a83($r4, $r5, $r7);
  }
  // free $p3
  $this->cache[$bucket][$key] = new GrammarCacheEntry(
    $this->currPos,
    $r1,
    self::$UNDEFINED,
    self::$UNDEFINED
  );
  return $r1;
}
private function parsesol_prefix($silence) {
  $key = 544;
  $bucket = $this->currPos;
  $cached = $this->cache[$bucket][$key] ?? null;
  if ($cached) {
    $this->currPos = $cached->nextPos;

    return $cached->result;
  }

  // start choice_1
  $r1 = $this->parsenewlineToken($silence);
  if ($r1!==self::$FAILED) {
    goto choice_1;
  }
  $p2 = $this->currPos;
  $this->savedPos = $this->currPos;
  $r1 = $this->a84();
  if ($r1) {
    $r1 = false;
    $this->savedPos = $p2;
    $r1 = $this->a85();
  } else {
    $r1 = self::$FAILED;
  }
  choice_1:
  $this->cache[$bucket][$key] = new GrammarCacheEntry(
    $this->currPos,
    $r1,
    self::$UNDEFINED,
    self::$UNDEFINED
  );
  return $r1;
}
private function parseredirect_word($silence) {
  $key = 286;
  $bucket = $this->currPos;
  $cached = $this->cache[$bucket][$key] ?? null;
  if ($cached) {
    $this->currPos = $cached->nextPos;

    return $cached->result;
  }

  $p1 = $this->currPos;
  // start seq_1
  $p3 = $this->currPos;
  for (;;) {
    if (strspn($this->input, " \x09\x0a\x0d\x00\x0b", $this->currPos, 1) !== 0) {
      $r5 = $this->input[$this->currPos++];
    } else {
      $r5 = self::$FAILED;
      if (!$silence) {$this->fail(58);}
      break;
    }
  }
  // free $r5
  $r4 = true;
  if ($r4===self::$FAILED) {
    $r2 = self::$FAILED;
    goto seq_1;
  }
  // free $r4
  $p6 = $this->currPos;
  $r4 = self::$FAILED;
  for (;;) {
    // start seq_2
    $p7 = $this->currPos;
    $p8 = $this->currPos;
    $r9 = $this->discardspace_or_newline(true);
    if ($r9 === self::$FAILED) {
      $r9 = false;
    } else {
      $r9 = self::$FAILED;
      $this->currPos = $p8;
      $r5 = self::$FAILED;
      goto seq_2;
    }
    // free $p8
    $p8 = $this->currPos;
    $r10 = $this->input[$this->currPos] ?? '';
    if ($r10 === ":" || $r10 === "[") {
      $this->currPos++;
    } else {
      $r10 = self::$FAILED;
    }
    if ($r10 === self::$FAILED) {
      $r10 = false;
    } else {
      $r10 = self::$FAILED;
      $this->currPos = $p8;
      $this->currPos = $p7;
      $r5 = self::$FAILED;
      goto seq_2;
    }
    // free $p8
    if ($this->currPos < $this->inputLength) {
      $r11 = self::consumeChar($this->input, $this->currPos);;
    } else {
      $r11 = self::$FAILED;
      if (!$silence) {$this->fail(7);}
      $this->currPos = $p7;
      $r5 = self::$FAILED;
      goto seq_2;
    }
    $r5 = true;
    seq_2:
    if ($r5!==self::$FAILED) {
      $r4 = true;
    } else {
      break;
    }
    // free $p7
  }
  // rw <- $r4
  if ($r4!==self::$FAILED) {
    $r4 = substr($this->input, $p6, $this->currPos - $p6);
  } else {
    $r4 = self::$FAILED;
    $this->currPos = $p3;
    $r2 = self::$FAILED;
    goto seq_1;
  }
  // free $r5
  // free $p6
  $this->savedPos = $this->currPos;
  $r5 = $this->a86($r4);
  if ($r5) {
    $r5 = false;
  } else {
    $r5 = self::$FAILED;
    $this->currPos = $p3;
    $r2 = self::$FAILED;
    goto seq_1;
  }
  $r2 = true;
  seq_1:
  if ($r2!==self::$FAILED) {
    $r2 = substr($this->input, $p1, $this->currPos - $p1);
  } else {
    $r2 = self::$FAILED;
  }
  // free $p3
  // free $p1
  $this->cache[$bucket][$key] = new GrammarCacheEntry(
    $this->currPos,
    $r2,
    self::$UNDEFINED,
    self::$UNDEFINED
  );
  return $r2;
}
private function parseinclude_limits($silence, $boolParams, &$param_preproc, &$param_th) {
  $key = json_encode([484, $boolParams & 0x3ebd, $param_preproc, $param_th]);
  $bucket = $this->currPos;
  $cached = $this->cache[$bucket][$key] ?? null;
  if ($cached) {
    $this->currPos = $cached->nextPos;
    if ($cached->preproc !== self::$UNDEFINED) { $param_preproc = $cached->preproc; }
    if ($cached->th !== self::$UNDEFINED) { $param_th = $cached->th; }
    return $cached->result;
  }
  $saved_preproc=$param_preproc;
  $saved_th=$param_th;
  $p2 = $this->currPos;
  // start seq_1
  $p3 = $this->currPos;
  $p4 = $this->currPos;
  // start seq_2
  $p6 = $this->currPos;
  if (($this->input[$this->currPos] ?? null) === "<") {
    $this->currPos++;
    $r7 = "<";
  } else {
    $r7 = self::$FAILED;
    $r5 = self::$FAILED;
    goto seq_2;
  }
  if (($this->input[$this->currPos] ?? null) === "/") {
    $this->currPos++;
    $r8 = "/";
  } else {
    $r8 = self::$FAILED;
    $r8 = null;
  }
  // start choice_1
  if ($this->currPos >= $this->inputLength ? false : substr_compare($this->input, "includeonly", $this->currPos, 11, true) === 0) {
    $r9 = substr($this->input, $this->currPos, 11);
    $this->currPos += 11;
    goto choice_1;
  } else {
    $r9 = self::$FAILED;
  }
  if ($this->currPos >= $this->inputLength ? false : substr_compare($this->input, "noinclude", $this->currPos, 9, true) === 0) {
    $r9 = substr($this->input, $this->currPos, 9);
    $this->currPos += 9;
    goto choice_1;
  } else {
    $r9 = self::$FAILED;
  }
  if ($this->currPos >= $this->inputLength ? false : substr_compare($this->input, "onlyinclude", $this->currPos, 11, true) === 0) {
    $r9 = substr($this->input, $this->currPos, 11);
    $this->currPos += 11;
  } else {
    $r9 = self::$FAILED;
  }
  choice_1:
  if ($r9===self::$FAILED) {
    $this->currPos = $p6;
    $r5 = self::$FAILED;
    goto seq_2;
  }
  $r5 = true;
  seq_2:
  if ($r5!==self::$FAILED) {
    $r5 = false;
    $this->currPos = $p4;
  } else {
    $r1 = self::$FAILED;
    goto seq_1;
  }
  // free $p6
  // free $p4
  if (($this->input[$this->currPos] ?? null) === "<") {
    $this->currPos++;
    $r10 = "<";
  } else {
    if (!$silence) {$this->fail(44);}
    $r10 = self::$FAILED;
    $this->currPos = $p3;
    $r1 = self::$FAILED;
    goto seq_1;
  }
  $r11 = $this->parsexmlish_tag($silence, $boolParams | 0x2, $param_preproc, $param_th);
  // il <- $r11
  if ($r11===self::$FAILED) {
    $this->currPos = $p3;
    $r1 = self::$FAILED;
    goto seq_1;
  }
  $this->savedPos = $this->currPos;
  $r12 = $this->a87($r11, /*sol_il*/($boolParams & 0x2000) !== 0);
  if ($r12) {
    $r12 = false;
  } else {
    $r12 = self::$FAILED;
    $this->currPos = $p3;
    $r1 = self::$FAILED;
    goto seq_1;
  }
  $r1 = true;
  seq_1:
  if ($r1!==self::$FAILED) {
    $this->savedPos = $p2;
    $r1 = $this->a88($r11, /*sol_il*/($boolParams & 0x2000) !== 0);
  }
  // free $p3
  $this->cache[$bucket][$key] = new GrammarCacheEntry(
    $this->currPos,
    $r1,
    $saved_preproc !== $param_preproc ? $param_preproc : self::$UNDEFINED,
    $saved_th !== $param_th ? $param_th : self::$UNDEFINED
  );
  return $r1;
}
private function parseannotation_tag($silence, $boolParams, &$param_preproc, &$param_th) {
  $key = json_encode([404, $boolParams & 0x3ebf, $param_preproc, $param_th]);
  $bucket = $this->currPos;
  $cached = $this->cache[$bucket][$key] ?? null;
  if ($cached) {
    $this->currPos = $cached->nextPos;
    if ($cached->preproc !== self::$UNDEFINED) { $param_preproc = $cached->preproc; }
    if ($cached->th !== self::$UNDEFINED) { $param_th = $cached->th; }
    return $cached->result;
  }
  $saved_preproc=$param_preproc;
  $saved_th=$param_th;
  $p2 = $this->currPos;
  // start seq_1
  $p3 = $this->currPos;
  $r4 = $this->parseextension_annotation_tag($silence, $boolParams, $param_preproc, $param_th);
  // annToken <- $r4
  if ($r4===self::$FAILED) {
    $r1 = self::$FAILED;
    goto seq_1;
  }
  $this->savedPos = $this->currPos;
  $r5 = $this->a89($r4);
  if ($r5) {
    $r5 = false;
  } else {
    $r5 = self::$FAILED;
    $this->currPos = $p3;
    $r1 = self::$FAILED;
    goto seq_1;
  }
  $r1 = true;
  seq_1:
  if ($r1!==self::$FAILED) {
    $this->savedPos = $p2;
    $r1 = $this->a90($r4);
  }
  // free $p3
  $this->cache[$bucket][$key] = new GrammarCacheEntry(
    $this->currPos,
    $r1,
    $saved_preproc !== $param_preproc ? $param_preproc : self::$UNDEFINED,
    $saved_th !== $param_th ? $param_th : self::$UNDEFINED
  );
  return $r1;
}
private function parseheading($silence, $boolParams, &$param_preproc, &$param_th) {
  $key = json_encode([310, $boolParams & 0x3fff, $param_preproc, $param_th]);
  $bucket = $this->currPos;
  $cached = $this->cache[$bucket][$key] ?? null;
  if ($cached) {
    $this->currPos = $cached->nextPos;
    if ($cached->preproc !== self::$UNDEFINED) { $param_preproc = $cached->preproc; }
    if ($cached->th !== self::$UNDEFINED) { $param_th = $cached->th; }
    return $cached->result;
  }
  $saved_preproc=$param_preproc;
  $saved_th=$param_th;
  $p2 = $this->currPos;
  // start seq_1
  $p3 = $this->currPos;
  $p4 = $this->currPos;
  if (($this->input[$this->currPos] ?? null) === "=") {
    $this->currPos++;
    $r5 = "=";
    $r5 = false;
    $this->currPos = $p4;
  } else {
    $r5 = self::$FAILED;
    $r1 = self::$FAILED;
    goto seq_1;
  }
  // free $p4
  $p4 = $this->currPos;
  // start seq_2
  $p7 = $this->currPos;
  $p9 = $this->currPos;
  $r8 = self::$FAILED;
  for (;;) {
    if (($this->input[$this->currPos] ?? null) === "=") {
      $this->currPos++;
      $r10 = "=";
      $r8 = true;
    } else {
      if (!$silence) {$this->fail(23);}
      $r10 = self::$FAILED;
      break;
    }
  }
  // s <- $r8
  if ($r8!==self::$FAILED) {
    $r8 = substr($this->input, $p9, $this->currPos - $p9);
  } else {
    $r8 = self::$FAILED;
    $r6 = self::$FAILED;
    goto seq_2;
  }
  // free $r10
  // free $p9
  // start seq_3
  $p9 = $this->currPos;
  $p12 = $this->currPos;
  $r13 = $this->parseinlineline($silence, $boolParams | 0x4, $param_preproc, $param_th);
  if ($r13===self::$FAILED) {
    $r13 = null;
  }
  // ill <- $r13
  $r11 = $r13;
  if ($r11!==self::$FAILED) {
    $this->savedPos = $p12;
    $r11 = $this->a91($r8, $r13);
  } else {
    $r10 = self::$FAILED;
    goto seq_3;
  }
  $p14 = $this->currPos;
  $r15 = self::$FAILED;
  for (;;) {
    if (($this->input[$this->currPos] ?? null) === "=") {
      $this->currPos++;
      $r16 = "=";
      $r15 = true;
    } else {
      if (!$silence) {$this->fail(23);}
      $r16 = self::$FAILED;
      break;
    }
  }
  if ($r15!==self::$FAILED) {
    $r15 = substr($this->input, $p14, $this->currPos - $p14);
  } else {
    $r15 = self::$FAILED;
    $this->currPos = $p9;
    $r10 = self::$FAILED;
    goto seq_3;
  }
  // free $r16
  // free $p14
  $r10 = [$r11,$r15];
  seq_3:
  if ($r10===self::$FAILED) {
    $r10 = null;
  }
  // free $p9
  // ce <- $r10
  $this->savedPos = $this->currPos;
  $r16 = $this->a92($r8, $r10);
  if ($r16) {
    $r16 = false;
  } else {
    $r16 = self::$FAILED;
    $this->currPos = $p7;
    $r6 = self::$FAILED;
    goto seq_2;
  }
  $p9 = $this->currPos;
  $r17 = '';
  // endTPos <- $r17
  if ($r17!==self::$FAILED) {
    $this->savedPos = $p9;
    $r17 = $this->a93($r8, $r10);
  } else {
    $this->currPos = $p7;
    $r6 = self::$FAILED;
    goto seq_2;
  }
  $r18 = [];
  for (;;) {
    // start choice_1
    $r19 = $this->parsespace($silence);
    if ($r19!==self::$FAILED) {
      goto choice_1;
    }
    $r19 = $this->parsesol_transparent($silence, $boolParams, $param_preproc, $param_th);
    choice_1:
    if ($r19!==self::$FAILED) {
      $r18[] = $r19;
    } else {
      break;
    }
  }
  // spc <- $r18
  // free $r19
  $p14 = $this->currPos;
  $r19 = $this->discardeolf(true);
  if ($r19!==self::$FAILED) {
    $r19 = false;
    $this->currPos = $p14;
  } else {
    $this->currPos = $p7;
    $r6 = self::$FAILED;
    goto seq_2;
  }
  // free $p14
  $r6 = true;
  seq_2:
  // r <- $r6
  if ($r6!==self::$FAILED) {
    $this->savedPos = $p4;
    $r6 = $this->a94($r8, $r10, $r17, $r18);
  } else {
    $this->currPos = $p3;
    $r1 = self::$FAILED;
    goto seq_1;
  }
  // free $p7
  $r1 = true;
  seq_1:
  if ($r1!==self::$FAILED) {
    $this->savedPos = $p2;
    $r1 = $this->a24($r6);
  }
  // free $p3
  $this->cache[$bucket][$key] = new GrammarCacheEntry(
    $this->currPos,
    $r1,
    $saved_preproc !== $param_preproc ? $param_preproc : self::$UNDEFINED,
    $saved_th !== $param_th ? $param_th : self::$UNDEFINED
  );
  return $r1;
}
private function parsehr($silence, $boolParams, &$param_preproc, &$param_th) {
  $key = json_encode([296, $boolParams & 0x3ebf, $param_preproc, $param_th]);
  $bucket = $this->currPos;
  $cached = $this->cache[$bucket][$key] ?? null;
  if ($cached) {
    $this->currPos = $cached->nextPos;
    if ($cached->preproc !== self::$UNDEFINED) { $param_preproc = $cached->preproc; }
    if ($cached->th !== self::$UNDEFINED) { $param_th = $cached->th; }
    return $cached->result;
  }
  $saved_preproc=$param_preproc;
  $saved_th=$param_th;
  $p2 = $this->currPos;
  // start seq_1
  $p3 = $this->currPos;
  if ($this->currPos >= $this->inputLength ? false : substr_compare($this->input, "----", $this->currPos, 4, false) === 0) {
    $r4 = "----";
    $this->currPos += 4;
  } else {
    if (!$silence) {$this->fail(59);}
    $r4 = self::$FAILED;
    $r1 = self::$FAILED;
    goto seq_1;
  }
  $p6 = $this->currPos;
  for (;;) {
    if (($this->input[$this->currPos] ?? null) === "-") {
      $this->currPos++;
      $r7 = "-";
    } else {
      if (!$silence) {$this->fail(55);}
      $r7 = self::$FAILED;
      break;
    }
  }
  // free $r7
  $r5 = true;
  // d <- $r5
  if ($r5!==self::$FAILED) {
    $r5 = substr($this->input, $p6, $this->currPos - $p6);
  } else {
    $r5 = self::$FAILED;
    $this->currPos = $p3;
    $r1 = self::$FAILED;
    goto seq_1;
  }
  // free $p6
  // start choice_1
  $p6 = $this->currPos;
  // start seq_2
  $p8 = $this->currPos;
  $p9 = $this->currPos;
  $r10 = $this->discardsol(true, $boolParams, $param_preproc, $param_th);
  if ($r10!==self::$FAILED) {
    $r10 = false;
    $this->currPos = $p9;
  } else {
    $r7 = self::$FAILED;
    goto seq_2;
  }
  // free $p9
  $r7 = true;
  seq_2:
  if ($r7!==self::$FAILED) {
    $this->savedPos = $p6;
    $r7 = $this->a95($r5);
    goto choice_1;
  }
  // free $p8
  $p8 = $this->currPos;
  $r7 = '';
  if ($r7!==self::$FAILED) {
    $this->savedPos = $p8;
    $r7 = $this->a96($r5);
  }
  choice_1:
  // lineContent <- $r7
  if ($r7===self::$FAILED) {
    $this->currPos = $p3;
    $r1 = self::$FAILED;
    goto seq_1;
  }
  $r1 = true;
  seq_1:
  if ($r1!==self::$FAILED) {
    $this->savedPos = $p2;
    $r1 = $this->a97($r5, $r7);
  }
  // free $p3
  $this->cache[$bucket][$key] = new GrammarCacheEntry(
    $this->currPos,
    $r1,
    $saved_preproc !== $param_preproc ? $param_preproc : self::$UNDEFINED,
    $saved_th !== $param_th ? $param_th : self::$UNDEFINED
  );
  return $r1;
}
private function discardtplarg_preproc($silence, $boolParams, &$param_preproc, &$param_th) {
  $key = json_encode([349, $boolParams & 0x3fff, $param_preproc, $param_th]);
  $bucket = $this->currPos;
  $cached = $this->cache[$bucket][$key] ?? null;
  if ($cached) {
    $this->currPos = $cached->nextPos;
    if ($cached->preproc !== self::$UNDEFINED) { $param_preproc = $cached->preproc; }
    if ($cached->th !== self::$UNDEFINED) { $param_th = $cached->th; }
    return $cached->result;
  }
  $saved_preproc=$param_preproc;
  $saved_th=$param_th;
  $p2 = $this->currPos;
  // start seq_1
  $p3 = $this->currPos;
  if ($this->currPos >= $this->inputLength ? false : substr_compare($this->input, "{{{", $this->currPos, 3, false) === 0) {
    $r4 = "{{{";
    $this->currPos += 3;
  } else {
    if (!$silence) {$this->fail(60);}
    $r4 = self::$FAILED;
    $r1 = self::$FAILED;
    goto seq_1;
  }
  $p6 = $this->currPos;
  $r5 = '';
  // p <- $r5
  if ($r5!==self::$FAILED) {
    $this->savedPos = $p6;
    $r5 = $this->a17();
  } else {
    $this->currPos = $p3;
    $r1 = self::$FAILED;
    goto seq_1;
  }
  $r7 = $this->parsetemplate_param_value($silence, $boolParams, $param_preproc, $param_th);
  if ($r7===self::$FAILED) {
    $r7 = null;
  }
  // target <- $r7
  $r8 = [];
  for (;;) {
    $p10 = $this->currPos;
    // start seq_2
    $p11 = $this->currPos;
    for (;;) {
      $r13 = $this->discardnl_comment_space($silence);
      if ($r13===self::$FAILED) {
        break;
      }
    }
    // free $r13
    $r12 = true;
    if ($r12===self::$FAILED) {
      $r9 = self::$FAILED;
      goto seq_2;
    }
    // free $r12
    if (($this->input[$this->currPos] ?? null) === "|") {
      $this->currPos++;
      $r12 = "|";
    } else {
      if (!$silence) {$this->fail(13);}
      $r12 = self::$FAILED;
      $this->currPos = $p11;
      $r9 = self::$FAILED;
      goto seq_2;
    }
    // start choice_1
    $p14 = $this->currPos;
    // start seq_3
    $p15 = $this->currPos;
    $p17 = $this->currPos;
    $r16 = '';
    // p0 <- $r16
    if ($r16!==self::$FAILED) {
      $this->savedPos = $p17;
      $r16 = $this->a98($r5, $r7);
    } else {
      $r13 = self::$FAILED;
      goto seq_3;
    }
    $r18 = [];
    for (;;) {
      $r19 = $this->parsenl_comment_space($silence);
      if ($r19!==self::$FAILED) {
        $r18[] = $r19;
      } else {
        break;
      }
    }
    // v <- $r18
    // free $r19
    $p20 = $this->currPos;
    $r19 = '';
    // p1 <- $r19
    if ($r19!==self::$FAILED) {
      $this->savedPos = $p20;
      $r19 = $this->a99($r5, $r7, $r16, $r18);
    } else {
      $this->currPos = $p15;
      $r13 = self::$FAILED;
      goto seq_3;
    }
    $p21 = $this->currPos;
    // start choice_2
    if (($this->input[$this->currPos] ?? null) === "|") {
      $this->currPos++;
      $r22 = "|";
      goto choice_2;
    } else {
      $r22 = self::$FAILED;
    }
    if ($this->currPos >= $this->inputLength ? false : substr_compare($this->input, "}}}", $this->currPos, 3, false) === 0) {
      $r22 = "}}}";
      $this->currPos += 3;
    } else {
      $r22 = self::$FAILED;
    }
    choice_2:
    if ($r22!==self::$FAILED) {
      $r22 = false;
      $this->currPos = $p21;
    } else {
      $this->currPos = $p15;
      $r13 = self::$FAILED;
      goto seq_3;
    }
    // free $p21
    $r13 = true;
    seq_3:
    if ($r13!==self::$FAILED) {
      $this->savedPos = $p14;
      $r13 = $this->a100($r5, $r7, $r16, $r18, $r19);
      goto choice_1;
    }
    // free $p15
    $r13 = $this->parsetemplate_param_value($silence, $boolParams, $param_preproc, $param_th);
    choice_1:
    // r <- $r13
    if ($r13===self::$FAILED) {
      $this->currPos = $p11;
      $r9 = self::$FAILED;
      goto seq_2;
    }
    $r9 = true;
    seq_2:
    if ($r9!==self::$FAILED) {
      $this->savedPos = $p10;
      $r9 = $this->a101($r5, $r7, $r13);
      $r8[] = $r9;
    } else {
      break;
    }
    // free $p11
  }
  // params <- $r8
  // free $r9
  for (;;) {
    $r23 = $this->discardnl_comment_space($silence);
    if ($r23===self::$FAILED) {
      break;
    }
  }
  // free $r23
  $r9 = true;
  if ($r9===self::$FAILED) {
    $this->currPos = $p3;
    $r1 = self::$FAILED;
    goto seq_1;
  }
  // free $r9
  $r9 = $this->discardinline_breaks($silence, $boolParams, $param_preproc, $param_th);
  if ($r9===self::$FAILED) {
    $this->currPos = $p3;
    $r1 = self::$FAILED;
    goto seq_1;
  }
  if ($this->currPos >= $this->inputLength ? false : substr_compare($this->input, "}}}", $this->currPos, 3, false) === 0) {
    $r23 = "}}}";
    $this->currPos += 3;
  } else {
    if (!$silence) {$this->fail(61);}
    $r23 = self::$FAILED;
    $this->currPos = $p3;
    $r1 = self::$FAILED;
    goto seq_1;
  }
  $r1 = true;
  seq_1:
  if ($r1!==self::$FAILED) {
    $this->savedPos = $p2;
    $r1 = $this->a102($r5, $r7, $r8);
  }
  // free $p3
  $this->cache[$bucket][$key] = new GrammarCacheEntry(
    $this->currPos,
    $r1,
    $saved_preproc !== $param_preproc ? $param_preproc : self::$UNDEFINED,
    $saved_th !== $param_th ? $param_th : self::$UNDEFINED
  );
  return $r1;
}
private function parsetemplate_preproc($silence, $boolParams, &$param_preproc, &$param_th) {
  $key = json_encode([344, $boolParams & 0x3fff, $param_preproc, $param_th]);
  $bucket = $this->currPos;
  $cached = $this->cache[$bucket][$key] ?? null;
  if ($cached) {
    $this->currPos = $cached->nextPos;
    if ($cached->preproc !== self::$UNDEFINED) { $param_preproc = $cached->preproc; }
    if ($cached->th !== self::$UNDEFINED) { $param_th = $cached->th; }
    return $cached->result;
  }
  $saved_preproc=$param_preproc;
  $saved_th=$param_th;
  // start choice_1
  $p2 = $this->currPos;
  // start seq_1
  $p3 = $this->currPos;
  if ($this->currPos >= $this->inputLength ? false : substr_compare($this->input, "{{", $this->currPos, 2, false) === 0) {
    $r4 = "{{";
    $this->currPos += 2;
  } else {
    if (!$silence) {$this->fail(43);}
    $r4 = self::$FAILED;
    $r1 = self::$FAILED;
    goto seq_1;
  }
  $p6 = $this->currPos;
  for (;;) {
    $r7 = $this->discardnl_comment_space($silence);
    if ($r7===self::$FAILED) {
      break;
    }
  }
  // free $r7
  $r5 = true;
  // leadWS <- $r5
  if ($r5!==self::$FAILED) {
    $r5 = substr($this->input, $p6, $this->currPos - $p6);
  } else {
    $r5 = self::$FAILED;
    $this->currPos = $p3;
    $r1 = self::$FAILED;
    goto seq_1;
  }
  // free $p6
  $r7 = $this->parsetemplate_param_value($silence, $boolParams, $param_preproc, $param_th);
  // target <- $r7
  if ($r7===self::$FAILED) {
    $this->currPos = $p3;
    $r1 = self::$FAILED;
    goto seq_1;
  }
  $r8 = [];
  for (;;) {
    $p6 = $this->currPos;
    // start seq_2
    $p10 = $this->currPos;
    for (;;) {
      $r12 = $this->discardnl_comment_space($silence);
      if ($r12===self::$FAILED) {
        break;
      }
    }
    // free $r12
    $r11 = true;
    if ($r11===self::$FAILED) {
      $r9 = self::$FAILED;
      goto seq_2;
    }
    // free $r11
    if (($this->input[$this->currPos] ?? null) === "|") {
      $this->currPos++;
      $r11 = "|";
    } else {
      if (!$silence) {$this->fail(13);}
      $r11 = self::$FAILED;
      $this->currPos = $p10;
      $r9 = self::$FAILED;
      goto seq_2;
    }
    // start choice_2
    $p13 = $this->currPos;
    // start seq_3
    $p14 = $this->currPos;
    $p16 = $this->currPos;
    $r15 = '';
    // p0 <- $r15
    if ($r15!==self::$FAILED) {
      $this->savedPos = $p16;
      $r15 = $this->a103($r5, $r7);
    } else {
      $r12 = self::$FAILED;
      goto seq_3;
    }
    $r17 = [];
    for (;;) {
      $r18 = $this->parsenl_comment_space($silence);
      if ($r18!==self::$FAILED) {
        $r17[] = $r18;
      } else {
        break;
      }
    }
    // v <- $r17
    // free $r18
    $p19 = $this->currPos;
    $r18 = '';
    // p <- $r18
    if ($r18!==self::$FAILED) {
      $this->savedPos = $p19;
      $r18 = $this->a104($r5, $r7, $r15, $r17);
    } else {
      $this->currPos = $p14;
      $r12 = self::$FAILED;
      goto seq_3;
    }
    $p20 = $this->currPos;
    // start choice_3
    if (($this->input[$this->currPos] ?? null) === "|") {
      $this->currPos++;
      $r21 = "|";
      goto choice_3;
    } else {
      $r21 = self::$FAILED;
    }
    if ($this->currPos >= $this->inputLength ? false : substr_compare($this->input, "}}", $this->currPos, 2, false) === 0) {
      $r21 = "}}";
      $this->currPos += 2;
    } else {
      $r21 = self::$FAILED;
    }
    choice_3:
    if ($r21!==self::$FAILED) {
      $r21 = false;
      $this->currPos = $p20;
    } else {
      $this->currPos = $p14;
      $r12 = self::$FAILED;
      goto seq_3;
    }
    // free $p20
    $r12 = true;
    seq_3:
    if ($r12!==self::$FAILED) {
      $this->savedPos = $p13;
      $r12 = $this->a105($r5, $r7, $r15, $r17, $r18);
      goto choice_2;
    }
    // free $p14
    $r12 = $this->parsetemplate_param($silence, $boolParams, $param_preproc, $param_th);
    choice_2:
    // r <- $r12
    if ($r12===self::$FAILED) {
      $this->currPos = $p10;
      $r9 = self::$FAILED;
      goto seq_2;
    }
    $r9 = true;
    seq_2:
    if ($r9!==self::$FAILED) {
      $this->savedPos = $p6;
      $r9 = $this->a106($r5, $r7, $r12);
      $r8[] = $r9;
    } else {
      break;
    }
    // free $p10
  }
  // params <- $r8
  // free $r9
  $p10 = $this->currPos;
  for (;;) {
    $r22 = $this->discardnl_comment_space($silence);
    if ($r22===self::$FAILED) {
      break;
    }
  }
  // free $r22
  $r9 = true;
  // trailWS <- $r9
  if ($r9!==self::$FAILED) {
    $r9 = substr($this->input, $p10, $this->currPos - $p10);
  } else {
    $r9 = self::$FAILED;
    $this->currPos = $p3;
    $r1 = self::$FAILED;
    goto seq_1;
  }
  // free $p10
  $r22 = $this->discardinline_breaks($silence, $boolParams, $param_preproc, $param_th);
  if ($r22===self::$FAILED) {
    $this->currPos = $p3;
    $r1 = self::$FAILED;
    goto seq_1;
  }
  if ($this->currPos >= $this->inputLength ? false : substr_compare($this->input, "}}", $this->currPos, 2, false) === 0) {
    $r23 = "}}";
    $this->currPos += 2;
  } else {
    if (!$silence) {$this->fail(62);}
    $r23 = self::$FAILED;
    $this->currPos = $p3;
    $r1 = self::$FAILED;
    goto seq_1;
  }
  $r1 = true;
  seq_1:
  if ($r1!==self::$FAILED) {
    $this->savedPos = $p2;
    $r1 = $this->a107($r5, $r7, $r8, $r9);
    goto choice_1;
  }
  // free $p3
  $p3 = $this->currPos;
  // start seq_4
  $p10 = $this->currPos;
  if ($this->currPos >= $this->inputLength ? false : substr_compare($this->input, "{{", $this->currPos, 2, false) === 0) {
    $r24 = "{{";
    $this->currPos += 2;
  } else {
    if (!$silence) {$this->fail(43);}
    $r24 = self::$FAILED;
    $r1 = self::$FAILED;
    goto seq_4;
  }
  for (;;) {
    $r26 = $this->discardspace_or_newline($silence);
    if ($r26===self::$FAILED) {
      break;
    }
  }
  // free $r26
  $r25 = true;
  if ($r25===self::$FAILED) {
    $this->currPos = $p10;
    $r1 = self::$FAILED;
    goto seq_4;
  }
  // free $r25
  if ($this->currPos >= $this->inputLength ? false : substr_compare($this->input, "}}", $this->currPos, 2, false) === 0) {
    $r25 = "}}";
    $this->currPos += 2;
  } else {
    if (!$silence) {$this->fail(62);}
    $r25 = self::$FAILED;
    $this->currPos = $p10;
    $r1 = self::$FAILED;
    goto seq_4;
  }
  $r1 = true;
  seq_4:
  if ($r1!==self::$FAILED) {
    $r1 = substr($this->input, $p3, $this->currPos - $p3);
  } else {
    $r1 = self::$FAILED;
  }
  // free $p10
  // free $p3
  choice_1:
  $this->cache[$bucket][$key] = new GrammarCacheEntry(
    $this->currPos,
    $r1,
    $saved_preproc !== $param_preproc ? $param_preproc : self::$UNDEFINED,
    $saved_th !== $param_th ? $param_th : self::$UNDEFINED
  );
  return $r1;
}
private function parsetplarg_preproc($silence, $boolParams, &$param_preproc, &$param_th) {
  $key = json_encode([348, $boolParams & 0x3fff, $param_preproc, $param_th]);
  $bucket = $this->currPos;
  $cached = $this->cache[$bucket][$key] ?? null;
  if ($cached) {
    $this->currPos = $cached->nextPos;
    if ($cached->preproc !== self::$UNDEFINED) { $param_preproc = $cached->preproc; }
    if ($cached->th !== self::$UNDEFINED) { $param_th = $cached->th; }
    return $cached->result;
  }
  $saved_preproc=$param_preproc;
  $saved_th=$param_th;
  $p2 = $this->currPos;
  // start seq_1
  $p3 = $this->currPos;
  if ($this->currPos >= $this->inputLength ? false : substr_compare($this->input, "{{{", $this->currPos, 3, false) === 0) {
    $r4 = "{{{";
    $this->currPos += 3;
  } else {
    if (!$silence) {$this->fail(60);}
    $r4 = self::$FAILED;
    $r1 = self::$FAILED;
    goto seq_1;
  }
  $p6 = $this->currPos;
  $r5 = '';
  // p <- $r5
  if ($r5!==self::$FAILED) {
    $this->savedPos = $p6;
    $r5 = $this->a17();
  } else {
    $this->currPos = $p3;
    $r1 = self::$FAILED;
    goto seq_1;
  }
  $r7 = $this->parsetemplate_param_value($silence, $boolParams, $param_preproc, $param_th);
  if ($r7===self::$FAILED) {
    $r7 = null;
  }
  // target <- $r7
  $r8 = [];
  for (;;) {
    $p10 = $this->currPos;
    // start seq_2
    $p11 = $this->currPos;
    for (;;) {
      $r13 = $this->discardnl_comment_space($silence);
      if ($r13===self::$FAILED) {
        break;
      }
    }
    // free $r13
    $r12 = true;
    if ($r12===self::$FAILED) {
      $r9 = self::$FAILED;
      goto seq_2;
    }
    // free $r12
    if (($this->input[$this->currPos] ?? null) === "|") {
      $this->currPos++;
      $r12 = "|";
    } else {
      if (!$silence) {$this->fail(13);}
      $r12 = self::$FAILED;
      $this->currPos = $p11;
      $r9 = self::$FAILED;
      goto seq_2;
    }
    // start choice_1
    $p14 = $this->currPos;
    // start seq_3
    $p15 = $this->currPos;
    $p17 = $this->currPos;
    $r16 = '';
    // p0 <- $r16
    if ($r16!==self::$FAILED) {
      $this->savedPos = $p17;
      $r16 = $this->a98($r5, $r7);
    } else {
      $r13 = self::$FAILED;
      goto seq_3;
    }
    $r18 = [];
    for (;;) {
      $r19 = $this->parsenl_comment_space($silence);
      if ($r19!==self::$FAILED) {
        $r18[] = $r19;
      } else {
        break;
      }
    }
    // v <- $r18
    // free $r19
    $p20 = $this->currPos;
    $r19 = '';
    // p1 <- $r19
    if ($r19!==self::$FAILED) {
      $this->savedPos = $p20;
      $r19 = $this->a99($r5, $r7, $r16, $r18);
    } else {
      $this->currPos = $p15;
      $r13 = self::$FAILED;
      goto seq_3;
    }
    $p21 = $this->currPos;
    // start choice_2
    if (($this->input[$this->currPos] ?? null) === "|") {
      $this->currPos++;
      $r22 = "|";
      goto choice_2;
    } else {
      $r22 = self::$FAILED;
    }
    if ($this->currPos >= $this->inputLength ? false : substr_compare($this->input, "}}}", $this->currPos, 3, false) === 0) {
      $r22 = "}}}";
      $this->currPos += 3;
    } else {
      $r22 = self::$FAILED;
    }
    choice_2:
    if ($r22!==self::$FAILED) {
      $r22 = false;
      $this->currPos = $p21;
    } else {
      $this->currPos = $p15;
      $r13 = self::$FAILED;
      goto seq_3;
    }
    // free $p21
    $r13 = true;
    seq_3:
    if ($r13!==self::$FAILED) {
      $this->savedPos = $p14;
      $r13 = $this->a100($r5, $r7, $r16, $r18, $r19);
      goto choice_1;
    }
    // free $p15
    $r13 = $this->parsetemplate_param_value($silence, $boolParams, $param_preproc, $param_th);
    choice_1:
    // r <- $r13
    if ($r13===self::$FAILED) {
      $this->currPos = $p11;
      $r9 = self::$FAILED;
      goto seq_2;
    }
    $r9 = true;
    seq_2:
    if ($r9!==self::$FAILED) {
      $this->savedPos = $p10;
      $r9 = $this->a101($r5, $r7, $r13);
      $r8[] = $r9;
    } else {
      break;
    }
    // free $p11
  }
  // params <- $r8
  // free $r9
  for (;;) {
    $r23 = $this->discardnl_comment_space($silence);
    if ($r23===self::$FAILED) {
      break;
    }
  }
  // free $r23
  $r9 = true;
  if ($r9===self::$FAILED) {
    $this->currPos = $p3;
    $r1 = self::$FAILED;
    goto seq_1;
  }
  // free $r9
  $r9 = $this->discardinline_breaks($silence, $boolParams, $param_preproc, $param_th);
  if ($r9===self::$FAILED) {
    $this->currPos = $p3;
    $r1 = self::$FAILED;
    goto seq_1;
  }
  if ($this->currPos >= $this->inputLength ? false : substr_compare($this->input, "}}}", $this->currPos, 3, false) === 0) {
    $r23 = "}}}";
    $this->currPos += 3;
  } else {
    if (!$silence) {$this->fail(61);}
    $r23 = self::$FAILED;
    $this->currPos = $p3;
    $r1 = self::$FAILED;
    goto seq_1;
  }
  $r1 = true;
  seq_1:
  if ($r1!==self::$FAILED) {
    $this->savedPos = $p2;
    $r1 = $this->a102($r5, $r7, $r8);
  }
  // free $p3
  $this->cache[$bucket][$key] = new GrammarCacheEntry(
    $this->currPos,
    $r1,
    $saved_preproc !== $param_preproc ? $param_preproc : self::$UNDEFINED,
    $saved_th !== $param_th ? $param_th : self::$UNDEFINED
  );
  return $r1;
}
private function discardwikilink_preproc($silence, $boolParams, &$param_preproc, &$param_th) {
  $key = json_encode([395, $boolParams & 0x3fff, $param_preproc, $param_th]);
  $bucket = $this->currPos;
  $cached = $this->cache[$bucket][$key] ?? null;
  if ($cached) {
    $this->currPos = $cached->nextPos;
    if ($cached->preproc !== self::$UNDEFINED) { $param_preproc = $cached->preproc; }
    if ($cached->th !== self::$UNDEFINED) { $param_th = $cached->th; }
    return $cached->result;
  }
  $saved_preproc=$param_preproc;
  $saved_th=$param_th;
  $p2 = $this->currPos;
  // start seq_1
  $p3 = $this->currPos;
  if ($this->currPos >= $this->inputLength ? false : substr_compare($this->input, "[[", $this->currPos, 2, false) === 0) {
    $r4 = "[[";
    $this->currPos += 2;
  } else {
    if (!$silence) {$this->fail(41);}
    $r4 = self::$FAILED;
    $r1 = self::$FAILED;
    goto seq_1;
  }
  $p6 = $this->currPos;
  $r5 = '';
  // spos <- $r5
  if ($r5!==self::$FAILED) {
    $this->savedPos = $p6;
    $r5 = $this->a17();
  } else {
    $this->currPos = $p3;
    $r1 = self::$FAILED;
    goto seq_1;
  }
  $r7 = $this->parsewikilink_preprocessor_text($silence, $boolParams, $param_preproc, $param_th);
  if ($r7===self::$FAILED) {
    $r7 = null;
  }
  // target <- $r7
  $p9 = $this->currPos;
  $r8 = '';
  // tpos <- $r8
  if ($r8!==self::$FAILED) {
    $this->savedPos = $p9;
    $r8 = $this->a108($r5, $r7);
  } else {
    $this->currPos = $p3;
    $r1 = self::$FAILED;
    goto seq_1;
  }
  $r10 = $this->parsewikilink_content($silence, $boolParams, $param_preproc, $param_th);
  // lcs <- $r10
  if ($r10===self::$FAILED) {
    $this->currPos = $p3;
    $r1 = self::$FAILED;
    goto seq_1;
  }
  $r11 = $this->discardinline_breaks($silence, $boolParams, $param_preproc, $param_th);
  if ($r11===self::$FAILED) {
    $this->currPos = $p3;
    $r1 = self::$FAILED;
    goto seq_1;
  }
  if ($this->currPos >= $this->inputLength ? false : substr_compare($this->input, "]]", $this->currPos, 2, false) === 0) {
    $r12 = "]]";
    $this->currPos += 2;
  } else {
    if (!$silence) {$this->fail(63);}
    $r12 = self::$FAILED;
    $this->currPos = $p3;
    $r1 = self::$FAILED;
    goto seq_1;
  }
  $r1 = true;
  seq_1:
  if ($r1!==self::$FAILED) {
    $this->savedPos = $p2;
    $r1 = $this->a109($r5, $r7, $r8, $r10);
  }
  // free $p3
  $this->cache[$bucket][$key] = new GrammarCacheEntry(
    $this->currPos,
    $r1,
    $saved_preproc !== $param_preproc ? $param_preproc : self::$UNDEFINED,
    $saved_th !== $param_th ? $param_th : self::$UNDEFINED
  );
  return $r1;
}
private function discardbroken_wikilink($silence, $boolParams, &$param_preproc, &$param_th) {
  $key = json_encode([393, $boolParams & 0x3fff, $param_preproc, $param_th]);
  $bucket = $this->currPos;
  $cached = $this->cache[$bucket][$key] ?? null;
  if ($cached) {
    $this->currPos = $cached->nextPos;
    if ($cached->preproc !== self::$UNDEFINED) { $param_preproc = $cached->preproc; }
    if ($cached->th !== self::$UNDEFINED) { $param_th = $cached->th; }
    return $cached->result;
  }
  $saved_preproc=$param_preproc;
  $saved_th=$param_th;
  $p2 = $this->currPos;
  // start seq_1
  $p3 = $this->currPos;
  $p4 = $this->currPos;
  if ($this->currPos >= $this->inputLength ? false : substr_compare($this->input, "[[", $this->currPos, 2, false) === 0) {
    $r5 = "[[";
    $this->currPos += 2;
    $r5 = false;
    $this->currPos = $p4;
  } else {
    $r5 = self::$FAILED;
    $r1 = self::$FAILED;
    goto seq_1;
  }
  // free $p4
  $this->savedPos = $this->currPos;
  $r6 = $this->a110($param_preproc);
  if ($r6) {
    $r6 = false;
  } else {
    $r6 = self::$FAILED;
    $this->currPos = $p3;
    $r1 = self::$FAILED;
    goto seq_1;
  }
  // start seq_2
  $p4 = $this->currPos;
  if (($this->input[$this->currPos] ?? null) === "[") {
    $this->currPos++;
    $r8 = "[";
  } else {
    if (!$silence) {$this->fail(19);}
    $r8 = self::$FAILED;
    $r7 = self::$FAILED;
    goto seq_2;
  }
  // start choice_1
  $r9 = $this->parseextlink($silence, $boolParams, $param_preproc, $param_th);
  if ($r9!==self::$FAILED) {
    goto choice_1;
  }
  if (($this->input[$this->currPos] ?? null) === "[") {
    $this->currPos++;
    $r9 = "[";
  } else {
    if (!$silence) {$this->fail(19);}
    $r9 = self::$FAILED;
  }
  choice_1:
  if ($r9===self::$FAILED) {
    $this->currPos = $p4;
    $r7 = self::$FAILED;
    goto seq_2;
  }
  $r7 = [$r8,$r9];
  seq_2:
  // a <- $r7
  if ($r7===self::$FAILED) {
    $this->currPos = $p3;
    $r1 = self::$FAILED;
    goto seq_1;
  }
  // free $p4
  $r1 = true;
  seq_1:
  if ($r1!==self::$FAILED) {
    $this->savedPos = $p2;
    $r1 = $this->a111($param_preproc, $r7);
  }
  // free $p3
  $this->cache[$bucket][$key] = new GrammarCacheEntry(
    $this->currPos,
    $r1,
    $saved_preproc !== $param_preproc ? $param_preproc : self::$UNDEFINED,
    $saved_th !== $param_th ? $param_th : self::$UNDEFINED
  );
  return $r1;
}
private function parseextension_annotation_tag($silence, $boolParams, &$param_preproc, &$param_th) {
  $key = json_encode([406, $boolParams & 0x3ebf, $param_preproc, $param_th]);
  $bucket = $this->currPos;
  $cached = $this->cache[$bucket][$key] ?? null;
  if ($cached) {
    $this->currPos = $cached->nextPos;
    if ($cached->preproc !== self::$UNDEFINED) { $param_preproc = $cached->preproc; }
    if ($cached->th !== self::$UNDEFINED) { $param_th = $cached->th; }
    return $cached->result;
  }
  $saved_preproc=$param_preproc;
  $saved_th=$param_th;
  $p2 = $this->currPos;
  // start seq_1
  $p3 = $this->currPos;
  if (!(/*annOrExtTag*/($boolParams & 0x2) !== 0)) {
    $r4 = false;
  } else {
    $r4 = self::$FAILED;
    $r1 = self::$FAILED;
    goto seq_1;
  }
  if (($this->input[$this->currPos] ?? null) === "<") {
    $this->currPos++;
    $r5 = "<";
  } else {
    if (!$silence) {$this->fail(44);}
    $r5 = self::$FAILED;
    $this->currPos = $p3;
    $r1 = self::$FAILED;
    goto seq_1;
  }
  // start choice_1
  $p7 = $this->currPos;
  // start seq_2
  $p8 = $this->currPos;
  $r9 = $this->parsexmlish_tag($silence, $boolParams | 0x2, $param_preproc, $param_th);
  // extToken <- $r9
  if ($r9===self::$FAILED) {
    $r6 = self::$FAILED;
    goto seq_2;
  }
  $this->savedPos = $this->currPos;
  $r10 = $this->a112($r9);
  if ($r10) {
    $r10 = false;
  } else {
    $r10 = self::$FAILED;
    $this->currPos = $p8;
    $r6 = self::$FAILED;
    goto seq_2;
  }
  $r6 = true;
  seq_2:
  if ($r6!==self::$FAILED) {
    $this->savedPos = $p7;
    $r6 = $this->a113($r9);
    goto choice_1;
  }
  // free $p8
  $r6 = $this->parsetvar_old_syntax_closing_HACK($silence);
  choice_1:
  // tag <- $r6
  if ($r6===self::$FAILED) {
    $this->currPos = $p3;
    $r1 = self::$FAILED;
    goto seq_1;
  }
  $r1 = true;
  seq_1:
  if ($r1!==self::$FAILED) {
    $this->savedPos = $p2;
    $r1 = $this->a77($r6);
  }
  // free $p3
  $this->cache[$bucket][$key] = new GrammarCacheEntry(
    $this->currPos,
    $r1,
    $saved_preproc !== $param_preproc ? $param_preproc : self::$UNDEFINED,
    $saved_th !== $param_th ? $param_th : self::$UNDEFINED
  );
  return $r1;
}
private function parsexmlish_tag($silence, $boolParams, &$param_preproc, &$param_th) {
  $key = json_encode([416, $boolParams & 0x3ebf, $param_preproc, $param_th]);
  $bucket = $this->currPos;
  $cached = $this->cache[$bucket][$key] ?? null;
  if ($cached) {
    $this->currPos = $cached->nextPos;
    if ($cached->preproc !== self::$UNDEFINED) { $param_preproc = $cached->preproc; }
    if ($cached->th !== self::$UNDEFINED) { $param_th = $cached->th; }
    return $cached->result;
  }
  $saved_preproc=$param_preproc;
  $saved_th=$param_th;
  $p2 = $this->currPos;
  // start seq_1
  $p3 = $this->currPos;
  $this->savedPos = $this->currPos;
  $r4 = $this->a114();
  if ($r4) {
    $r4 = false;
  } else {
    $r4 = self::$FAILED;
    $r1 = self::$FAILED;
    goto seq_1;
  }
  if (($this->input[$this->currPos] ?? null) === "/") {
    $this->currPos++;
    $r5 = "/";
  } else {
    if (!$silence) {$this->fail(35);}
    $r5 = self::$FAILED;
    $r5 = null;
  }
  // end <- $r5
  $r6 = $this->parsetag_name($silence);
  // name <- $r6
  if ($r6===self::$FAILED) {
    $this->currPos = $p3;
    $r1 = self::$FAILED;
    goto seq_1;
  }
  $this->savedPos = $this->currPos;
  $r7 = $this->a115($r5, $r6, /*annOrExtTag*/($boolParams & 0x2) !== 0);
  if ($r7) {
    $r7 = false;
  } else {
    $r7 = self::$FAILED;
    $this->currPos = $p3;
    $r1 = self::$FAILED;
    goto seq_1;
  }
  $r8 = $this->parsegeneric_newline_attributes($silence, $boolParams & ~0x140, $param_preproc, $param_th);
  // attribs <- $r8
  if ($r8===self::$FAILED) {
    $this->currPos = $p3;
    $r1 = self::$FAILED;
    goto seq_1;
  }
  for (;;) {
    $r10 = $this->discardspace_or_newline_or_solidus($silence);
    if ($r10===self::$FAILED) {
      break;
    }
  }
  // free $r10
  $r9 = true;
  if ($r9===self::$FAILED) {
    $this->currPos = $p3;
    $r1 = self::$FAILED;
    goto seq_1;
  }
  // free $r9
  if (($this->input[$this->currPos] ?? null) === "/") {
    $this->currPos++;
    $r9 = "/";
  } else {
    if (!$silence) {$this->fail(35);}
    $r9 = self::$FAILED;
    $r9 = null;
  }
  // selfclose <- $r9
  for (;;) {
    $r11 = $this->discardspace($silence);
    if ($r11===self::$FAILED) {
      break;
    }
  }
  // free $r11
  $r10 = true;
  if ($r10===self::$FAILED) {
    $this->currPos = $p3;
    $r1 = self::$FAILED;
    goto seq_1;
  }
  // free $r10
  if (($this->input[$this->currPos] ?? null) === ">") {
    $this->currPos++;
    $r10 = ">";
  } else {
    if (!$silence) {$this->fail(64);}
    $r10 = self::$FAILED;
    $this->currPos = $p3;
    $r1 = self::$FAILED;
    goto seq_1;
  }
  $r1 = true;
  seq_1:
  if ($r1!==self::$FAILED) {
    $this->savedPos = $p2;
    $r1 = $this->a116($r5, $r6, /*annOrExtTag*/($boolParams & 0x2) !== 0, $r8, $r9);
  }
  // free $p3
  $this->cache[$bucket][$key] = new GrammarCacheEntry(
    $this->currPos,
    $r1,
    $saved_preproc !== $param_preproc ? $param_preproc : self::$UNDEFINED,
    $saved_th !== $param_th ? $param_th : self::$UNDEFINED
  );
  return $r1;
}
private function parsetvar_old_syntax_closing_HACK($silence) {
  $key = 402;
  $bucket = $this->currPos;
  $cached = $this->cache[$bucket][$key] ?? null;
  if ($cached) {
    $this->currPos = $cached->nextPos;

    return $cached->result;
  }

  $p2 = $this->currPos;
  // start seq_1
  $p3 = $this->currPos;
  if ($this->currPos >= $this->inputLength ? false : substr_compare($this->input, "/>", $this->currPos, 2, false) === 0) {
    $r4 = "/>";
    $this->currPos += 2;
  } else {
    if (!$silence) {$this->fail(65);}
    $r4 = self::$FAILED;
    $r1 = self::$FAILED;
    goto seq_1;
  }
  $this->savedPos = $this->currPos;
  $r5 = $this->a117();
  if ($r5) {
    $r5 = false;
  } else {
    $r5 = self::$FAILED;
    $this->currPos = $p3;
    $r1 = self::$FAILED;
    goto seq_1;
  }
  $r1 = true;
  seq_1:
  if ($r1!==self::$FAILED) {
    $this->savedPos = $p2;
    $r1 = $this->a118();
  }
  // free $p3
  $this->cache[$bucket][$key] = new GrammarCacheEntry(
    $this->currPos,
    $r1,
    self::$UNDEFINED,
    self::$UNDEFINED
  );
  return $r1;
}
private function parseautourl($silence, $boolParams, &$param_preproc, &$param_th) {
  $key = json_encode([330, $boolParams & 0x3fff, $param_preproc, $param_th]);
  $bucket = $this->currPos;
  $cached = $this->cache[$bucket][$key] ?? null;
  if ($cached) {
    $this->currPos = $cached->nextPos;
    if ($cached->preproc !== self::$UNDEFINED) { $param_preproc = $cached->preproc; }
    if ($cached->th !== self::$UNDEFINED) { $param_th = $cached->th; }
    return $cached->result;
  }
  $saved_preproc=$param_preproc;
  $saved_th=$param_th;
  $p2 = $this->currPos;
  // start seq_1
  $p3 = $this->currPos;
  $p4 = $this->currPos;
  if ($this->currPos >= $this->inputLength ? false : substr_compare($this->input, "//", $this->currPos, 2, false) === 0) {
    $r5 = "//";
    $this->currPos += 2;
  } else {
    $r5 = self::$FAILED;
  }
  if ($r5 === self::$FAILED) {
    $r5 = false;
  } else {
    $r5 = self::$FAILED;
    $this->currPos = $p4;
    $r1 = self::$FAILED;
    goto seq_1;
  }
  // free $p4
  $p4 = $this->currPos;
  // start seq_2
  $p7 = $this->currPos;
  $r8 = $this->parseurl_protocol($silence);
  // proto <- $r8
  if ($r8===self::$FAILED) {
    $r6 = self::$FAILED;
    goto seq_2;
  }
  // start choice_1
  $r9 = $this->parseipv6urladdr($silence);
  if ($r9!==self::$FAILED) {
    goto choice_1;
  }
  $r9 = '';
  choice_1:
  // addr <- $r9
  if ($r9===self::$FAILED) {
    $this->currPos = $p7;
    $r6 = self::$FAILED;
    goto seq_2;
  }
  $r10 = [];
  for (;;) {
    $p12 = $this->currPos;
    // start seq_3
    $p13 = $this->currPos;
    $p14 = $this->currPos;
    $r15 = $this->discardinline_breaks(true, $boolParams, $param_preproc, $param_th);
    if ($r15 === self::$FAILED) {
      $r15 = false;
    } else {
      $r15 = self::$FAILED;
      $this->currPos = $p14;
      $r11 = self::$FAILED;
      goto seq_3;
    }
    // free $p14
    // start choice_2
    $r16 = $this->parseno_punctuation_char($silence);
    if ($r16!==self::$FAILED) {
      goto choice_2;
    }
    $r16 = $this->parsecomment($silence);
    if ($r16!==self::$FAILED) {
      goto choice_2;
    }
    $r16 = $this->parsetplarg_or_template($silence, $boolParams, $param_th, $param_preproc);
    if ($r16!==self::$FAILED) {
      goto choice_2;
    }
    $p14 = $this->currPos;
    // start seq_4
    $p17 = $this->currPos;
    if (($this->input[$this->currPos] ?? null) === "'") {
      $this->currPos++;
      $r18 = "'";
    } else {
      if (!$silence) {$this->fail(33);}
      $r18 = self::$FAILED;
      $r16 = self::$FAILED;
      goto seq_4;
    }
    $p19 = $this->currPos;
    if (($this->input[$this->currPos] ?? null) === "'") {
      $this->currPos++;
      $r20 = "'";
    } else {
      $r20 = self::$FAILED;
    }
    if ($r20 === self::$FAILED) {
      $r20 = false;
    } else {
      $r20 = self::$FAILED;
      $this->currPos = $p19;
      $this->currPos = $p17;
      $r16 = self::$FAILED;
      goto seq_4;
    }
    // free $p19
    $r16 = true;
    seq_4:
    if ($r16!==self::$FAILED) {
      $r16 = substr($this->input, $p14, $this->currPos - $p14);
      goto choice_2;
    } else {
      $r16 = self::$FAILED;
    }
    // free $p17
    // free $p14
    if (($this->input[$this->currPos] ?? null) === "{") {
      $this->currPos++;
      $r16 = "{";
      goto choice_2;
    } else {
      if (!$silence) {$this->fail(29);}
      $r16 = self::$FAILED;
    }
    $p14 = $this->currPos;
    // start seq_5
    $p17 = $this->currPos;
    $p19 = $this->currPos;
    // start seq_6
    $p22 = $this->currPos;
    $r23 = $this->parseraw_htmlentity(true);
    // rhe <- $r23
    if ($r23===self::$FAILED) {
      $r21 = self::$FAILED;
      goto seq_6;
    }
    $this->savedPos = $this->currPos;
    $r24 = $this->a119($r8, $r9, $r23);
    if ($r24) {
      $r24 = false;
    } else {
      $r24 = self::$FAILED;
      $this->currPos = $p22;
      $r21 = self::$FAILED;
      goto seq_6;
    }
    $r21 = true;
    seq_6:
    // free $p22
    if ($r21 === self::$FAILED) {
      $r21 = false;
    } else {
      $r21 = self::$FAILED;
      $this->currPos = $p19;
      $r16 = self::$FAILED;
      goto seq_5;
    }
    // free $p19
    // start choice_3
    $p19 = $this->currPos;
    // start seq_7
    $p22 = $this->currPos;
    $p26 = $this->currPos;
    if (($this->input[$this->currPos] ?? null) === "&") {
      $this->currPos++;
      $r27 = "&";
      $r27 = false;
      $this->currPos = $p26;
    } else {
      $r27 = self::$FAILED;
      $r25 = self::$FAILED;
      goto seq_7;
    }
    // free $p26
    $r28 = $this->parsehtmlentity($silence);
    // he <- $r28
    if ($r28===self::$FAILED) {
      $this->currPos = $p22;
      $r25 = self::$FAILED;
      goto seq_7;
    }
    $r25 = true;
    seq_7:
    if ($r25!==self::$FAILED) {
      $this->savedPos = $p19;
      $r25 = $this->a9($r8, $r9, $r28);
      goto choice_3;
    }
    // free $p22
    if (($this->input[$this->currPos] ?? null) === "&") {
      $this->currPos++;
      $r25 = "&";
    } else {
      if (!$silence) {$this->fail(4);}
      $r25 = self::$FAILED;
    }
    choice_3:
    // r <- $r25
    if ($r25===self::$FAILED) {
      $this->currPos = $p17;
      $r16 = self::$FAILED;
      goto seq_5;
    }
    $r16 = true;
    seq_5:
    if ($r16!==self::$FAILED) {
      $this->savedPos = $p14;
      $r16 = $this->a10($r8, $r9, $r25);
    }
    // free $p17
    choice_2:
    // c <- $r16
    if ($r16===self::$FAILED) {
      $this->currPos = $p13;
      $r11 = self::$FAILED;
      goto seq_3;
    }
    $r11 = true;
    seq_3:
    if ($r11!==self::$FAILED) {
      $this->savedPos = $p12;
      $r11 = $this->a11($r8, $r9, $r16);
      $r10[] = $r11;
    } else {
      break;
    }
    // free $p13
  }
  // path <- $r10
  // free $r11
  $r6 = true;
  seq_2:
  // r <- $r6
  if ($r6!==self::$FAILED) {
    $this->savedPos = $p4;
    $r6 = $this->a120($r8, $r9, $r10);
  } else {
    $this->currPos = $p3;
    $r1 = self::$FAILED;
    goto seq_1;
  }
  // free $p7
  $this->savedPos = $this->currPos;
  $r11 = $this->a121($r6);
  if ($r11) {
    $r11 = false;
  } else {
    $r11 = self::$FAILED;
    $this->currPos = $p3;
    $r1 = self::$FAILED;
    goto seq_1;
  }
  $r1 = true;
  seq_1:
  if ($r1!==self::$FAILED) {
    $this->savedPos = $p2;
    $r1 = $this->a122($r6);
  }
  // free $p3
  $this->cache[$bucket][$key] = new GrammarCacheEntry(
    $this->currPos,
    $r1,
    $saved_preproc !== $param_preproc ? $param_preproc : self::$UNDEFINED,
    $saved_th !== $param_th ? $param_th : self::$UNDEFINED
  );
  return $r1;
}
private function parseautoref($silence) {
  $key = 320;
  $bucket = $this->currPos;
  $cached = $this->cache[$bucket][$key] ?? null;
  if ($cached) {
    $this->currPos = $cached->nextPos;

    return $cached->result;
  }

  $p2 = $this->currPos;
  // start seq_1
  $p3 = $this->currPos;
  // start choice_1
  if ($this->currPos >= $this->inputLength ? false : substr_compare($this->input, "RFC", $this->currPos, 3, false) === 0) {
    $r4 = "RFC";
    $this->currPos += 3;
    goto choice_1;
  } else {
    if (!$silence) {$this->fail(66);}
    $r4 = self::$FAILED;
  }
  if ($this->currPos >= $this->inputLength ? false : substr_compare($this->input, "PMID", $this->currPos, 4, false) === 0) {
    $r4 = "PMID";
    $this->currPos += 4;
  } else {
    if (!$silence) {$this->fail(67);}
    $r4 = self::$FAILED;
  }
  choice_1:
  // ref <- $r4
  if ($r4===self::$FAILED) {
    $r1 = self::$FAILED;
    goto seq_1;
  }
  $r5 = [];
  for (;;) {
    $r6 = $this->parsespace_or_nbsp($silence);
    if ($r6!==self::$FAILED) {
      $r5[] = $r6;
    } else {
      break;
    }
  }
  if (count($r5) === 0) {
    $r5 = self::$FAILED;
  }
  // sp <- $r5
  if ($r5===self::$FAILED) {
    $this->currPos = $p3;
    $r1 = self::$FAILED;
    goto seq_1;
  }
  // free $r6
  $p7 = $this->currPos;
  $r6 = self::$FAILED;
  for (;;) {
    $r8 = $this->input[$this->currPos] ?? '';
    if (preg_match("/^[0-9]/", $r8)) {
      $this->currPos++;
      $r6 = true;
    } else {
      $r8 = self::$FAILED;
      if (!$silence) {$this->fail(68);}
      break;
    }
  }
  // identifier <- $r6
  if ($r6!==self::$FAILED) {
    $r6 = substr($this->input, $p7, $this->currPos - $p7);
  } else {
    $r6 = self::$FAILED;
    $this->currPos = $p3;
    $r1 = self::$FAILED;
    goto seq_1;
  }
  // free $r8
  // free $p7
  $r8 = $this->discardend_of_word($silence);
  if ($r8===self::$FAILED) {
    $this->currPos = $p3;
    $r1 = self::$FAILED;
    goto seq_1;
  }
  $r1 = true;
  seq_1:
  if ($r1!==self::$FAILED) {
    $this->savedPos = $p2;
    $r1 = $this->a123($r4, $r5, $r6);
  }
  // free $p3
  $this->cache[$bucket][$key] = new GrammarCacheEntry(
    $this->currPos,
    $r1,
    self::$UNDEFINED,
    self::$UNDEFINED
  );
  return $r1;
}
private function parseisbn($silence) {
  $key = 322;
  $bucket = $this->currPos;
  $cached = $this->cache[$bucket][$key] ?? null;
  if ($cached) {
    $this->currPos = $cached->nextPos;

    return $cached->result;
  }

  $p2 = $this->currPos;
  // start seq_1
  $p3 = $this->currPos;
  if ($this->currPos >= $this->inputLength ? false : substr_compare($this->input, "ISBN", $this->currPos, 4, false) === 0) {
    $r4 = "ISBN";
    $this->currPos += 4;
  } else {
    if (!$silence) {$this->fail(69);}
    $r4 = self::$FAILED;
    $r1 = self::$FAILED;
    goto seq_1;
  }
  $r5 = [];
  for (;;) {
    $r6 = $this->parsespace_or_nbsp($silence);
    if ($r6!==self::$FAILED) {
      $r5[] = $r6;
    } else {
      break;
    }
  }
  if (count($r5) === 0) {
    $r5 = self::$FAILED;
  }
  // sp <- $r5
  if ($r5===self::$FAILED) {
    $this->currPos = $p3;
    $r1 = self::$FAILED;
    goto seq_1;
  }
  // free $r6
  // start seq_2
  $p7 = $this->currPos;
  $r8 = $this->input[$this->currPos] ?? '';
  if (preg_match("/^[0-9]/", $r8)) {
    $this->currPos++;
  } else {
    $r8 = self::$FAILED;
    if (!$silence) {$this->fail(68);}
    $r6 = self::$FAILED;
    goto seq_2;
  }
  $r9 = [];
  for (;;) {
    // start seq_3
    $p11 = $this->currPos;
    // start choice_1
    $r12 = $this->parsespace_or_nbsp_or_dash($silence);
    if ($r12!==self::$FAILED) {
      goto choice_1;
    }
    $r12 = '';
    choice_1:
    if ($r12===self::$FAILED) {
      $r10 = self::$FAILED;
      goto seq_3;
    }
    $r13 = $this->input[$this->currPos] ?? '';
    if (preg_match("/^[0-9]/", $r13)) {
      $this->currPos++;
    } else {
      $r13 = self::$FAILED;
      if (!$silence) {$this->fail(68);}
      $this->currPos = $p11;
      $r10 = self::$FAILED;
      goto seq_3;
    }
    $r10 = [$r12,$r13];
    seq_3:
    if ($r10!==self::$FAILED) {
      $r9[] = $r10;
    } else {
      break;
    }
    // free $p11
  }
  if (count($r9) === 0) {
    $r9 = self::$FAILED;
  }
  if ($r9===self::$FAILED) {
    $this->currPos = $p7;
    $r6 = self::$FAILED;
    goto seq_2;
  }
  // free $r10
  // start choice_2
  // start seq_4
  $p11 = $this->currPos;
  // start choice_3
  $r14 = $this->parsespace_or_nbsp_or_dash($silence);
  if ($r14!==self::$FAILED) {
    goto choice_3;
  }
  $r14 = '';
  choice_3:
  if ($r14===self::$FAILED) {
    $r10 = self::$FAILED;
    goto seq_4;
  }
  $r15 = $this->input[$this->currPos] ?? '';
  if ($r15 === "x" || $r15 === "X") {
    $this->currPos++;
  } else {
    $r15 = self::$FAILED;
    if (!$silence) {$this->fail(70);}
    $this->currPos = $p11;
    $r10 = self::$FAILED;
    goto seq_4;
  }
  $r10 = [$r14,$r15];
  seq_4:
  if ($r10!==self::$FAILED) {
    goto choice_2;
  }
  // free $p11
  $r10 = '';
  choice_2:
  if ($r10===self::$FAILED) {
    $this->currPos = $p7;
    $r6 = self::$FAILED;
    goto seq_2;
  }
  $r6 = [$r8,$r9,$r10];
  seq_2:
  // isbn <- $r6
  if ($r6===self::$FAILED) {
    $this->currPos = $p3;
    $r1 = self::$FAILED;
    goto seq_1;
  }
  // free $p7
  $p7 = $this->currPos;
  $r16 = $this->discardend_of_word($silence);
  // isbncode <- $r16
  if ($r16!==self::$FAILED) {
    $this->savedPos = $p7;
    $r16 = $this->a124($r5, $r6);
  } else {
    $this->currPos = $p3;
    $r1 = self::$FAILED;
    goto seq_1;
  }
  $this->savedPos = $this->currPos;
  $r17 = $this->a125($r5, $r6, $r16);
  if ($r17) {
    $r17 = false;
  } else {
    $r17 = self::$FAILED;
    $this->currPos = $p3;
    $r1 = self::$FAILED;
    goto seq_1;
  }
  $r1 = true;
  seq_1:
  if ($r1!==self::$FAILED) {
    $this->savedPos = $p2;
    $r1 = $this->a126($r5, $r6, $r16);
  }
  // free $p3
  $this->cache[$bucket][$key] = new GrammarCacheEntry(
    $this->currPos,
    $r1,
    self::$UNDEFINED,
    self::$UNDEFINED
  );
  return $r1;
}
private function discardbehavior_text($silence) {
  $key = 315;
  $bucket = $this->currPos;
  $cached = $this->cache[$bucket][$key] ?? null;
  if ($cached) {
    $this->currPos = $cached->nextPos;

    return $cached->result;
  }

  $p1 = $this->currPos;
  $r2 = self::$FAILED;
  for (;;) {
    // start seq_1
    $p4 = $this->currPos;
    $p5 = $this->currPos;
    if ($this->currPos >= $this->inputLength ? false : substr_compare($this->input, "__", $this->currPos, 2, false) === 0) {
      $r6 = "__";
      $this->currPos += 2;
    } else {
      $r6 = self::$FAILED;
    }
    if ($r6 === self::$FAILED) {
      $r6 = false;
    } else {
      $r6 = self::$FAILED;
      $this->currPos = $p5;
      $r3 = self::$FAILED;
      goto seq_1;
    }
    // free $p5
    // start choice_1
    $r7 = $this->discardtext_char($silence);
    if ($r7!==self::$FAILED) {
      goto choice_1;
    }
    if (($this->input[$this->currPos] ?? null) === "-") {
      $this->currPos++;
      $r7 = "-";
    } else {
      if (!$silence) {$this->fail(55);}
      $r7 = self::$FAILED;
    }
    choice_1:
    if ($r7===self::$FAILED) {
      $this->currPos = $p4;
      $r3 = self::$FAILED;
      goto seq_1;
    }
    $r3 = true;
    seq_1:
    if ($r3!==self::$FAILED) {
      $r2 = true;
    } else {
      break;
    }
    // free $p4
  }
  if ($r2!==self::$FAILED) {
    $r2 = substr($this->input, $p1, $this->currPos - $p1);
  } else {
    $r2 = self::$FAILED;
  }
  // free $r3
  // free $p1
  $this->cache[$bucket][$key] = new GrammarCacheEntry(
    $this->currPos,
    $r2,
    self::$UNDEFINED,
    self::$UNDEFINED
  );
  return $r2;
}
private function parselang_variant($silence, $boolParams, &$param_th, &$param_preproc) {
  $key = json_encode([362, $boolParams & 0x3ff7, $param_th, $param_preproc]);
  $bucket = $this->currPos;
  $cached = $this->cache[$bucket][$key] ?? null;
  if ($cached) {
    $this->currPos = $cached->nextPos;
    if ($cached->th !== self::$UNDEFINED) { $param_th = $cached->th; }
    if ($cached->preproc !== self::$UNDEFINED) { $param_preproc = $cached->preproc; }
    return $cached->result;
  }
  $saved_th=$param_th;
  $saved_preproc=$param_preproc;
  // start choice_1
  $r1 = $this->parselang_variant_preproc($silence, $boolParams & ~0x8, self::newRef("}-"), $param_th);
  if ($r1!==self::$FAILED) {
    goto choice_1;
  }
  $r1 = $this->parsebroken_lang_variant($silence, $param_preproc);
  choice_1:
  $this->cache[$bucket][$key] = new GrammarCacheEntry(
    $this->currPos,
    $r1,
    $saved_preproc !== $param_preproc ? $param_preproc : self::$UNDEFINED,
    $saved_th !== $param_th ? $param_th : self::$UNDEFINED
  );
  return $r1;
}
private function parsewikilink_preproc($silence, $boolParams, &$param_preproc, &$param_th) {
  $key = json_encode([394, $boolParams & 0x3fff, $param_preproc, $param_th]);
  $bucket = $this->currPos;
  $cached = $this->cache[$bucket][$key] ?? null;
  if ($cached) {
    $this->currPos = $cached->nextPos;
    if ($cached->preproc !== self::$UNDEFINED) { $param_preproc = $cached->preproc; }
    if ($cached->th !== self::$UNDEFINED) { $param_th = $cached->th; }
    return $cached->result;
  }
  $saved_preproc=$param_preproc;
  $saved_th=$param_th;
  $p2 = $this->currPos;
  // start seq_1
  $p3 = $this->currPos;
  if ($this->currPos >= $this->inputLength ? false : substr_compare($this->input, "[[", $this->currPos, 2, false) === 0) {
    $r4 = "[[";
    $this->currPos += 2;
  } else {
    if (!$silence) {$this->fail(41);}
    $r4 = self::$FAILED;
    $r1 = self::$FAILED;
    goto seq_1;
  }
  $p6 = $this->currPos;
  $r5 = '';
  // spos <- $r5
  if ($r5!==self::$FAILED) {
    $this->savedPos = $p6;
    $r5 = $this->a17();
  } else {
    $this->currPos = $p3;
    $r1 = self::$FAILED;
    goto seq_1;
  }
  $r7 = $this->parsewikilink_preprocessor_text($silence, $boolParams, $param_preproc, $param_th);
  if ($r7===self::$FAILED) {
    $r7 = null;
  }
  // target <- $r7
  $p9 = $this->currPos;
  $r8 = '';
  // tpos <- $r8
  if ($r8!==self::$FAILED) {
    $this->savedPos = $p9;
    $r8 = $this->a108($r5, $r7);
  } else {
    $this->currPos = $p3;
    $r1 = self::$FAILED;
    goto seq_1;
  }
  $r10 = $this->parsewikilink_content($silence, $boolParams, $param_preproc, $param_th);
  // lcs <- $r10
  if ($r10===self::$FAILED) {
    $this->currPos = $p3;
    $r1 = self::$FAILED;
    goto seq_1;
  }
  $r11 = $this->discardinline_breaks($silence, $boolParams, $param_preproc, $param_th);
  if ($r11===self::$FAILED) {
    $this->currPos = $p3;
    $r1 = self::$FAILED;
    goto seq_1;
  }
  if ($this->currPos >= $this->inputLength ? false : substr_compare($this->input, "]]", $this->currPos, 2, false) === 0) {
    $r12 = "]]";
    $this->currPos += 2;
  } else {
    if (!$silence) {$this->fail(63);}
    $r12 = self::$FAILED;
    $this->currPos = $p3;
    $r1 = self::$FAILED;
    goto seq_1;
  }
  $r1 = true;
  seq_1:
  if ($r1!==self::$FAILED) {
    $this->savedPos = $p2;
    $r1 = $this->a109($r5, $r7, $r8, $r10);
  }
  // free $p3
  $this->cache[$bucket][$key] = new GrammarCacheEntry(
    $this->currPos,
    $r1,
    $saved_preproc !== $param_preproc ? $param_preproc : self::$UNDEFINED,
    $saved_th !== $param_th ? $param_th : self::$UNDEFINED
  );
  return $r1;
}
private function parsebroken_wikilink($silence, $boolParams, &$param_preproc, &$param_th) {
  $key = json_encode([392, $boolParams & 0x3fff, $param_preproc, $param_th]);
  $bucket = $this->currPos;
  $cached = $this->cache[$bucket][$key] ?? null;
  if ($cached) {
    $this->currPos = $cached->nextPos;
    if ($cached->preproc !== self::$UNDEFINED) { $param_preproc = $cached->preproc; }
    if ($cached->th !== self::$UNDEFINED) { $param_th = $cached->th; }
    return $cached->result;
  }
  $saved_preproc=$param_preproc;
  $saved_th=$param_th;
  $p2 = $this->currPos;
  // start seq_1
  $p3 = $this->currPos;
  $p4 = $this->currPos;
  if ($this->currPos >= $this->inputLength ? false : substr_compare($this->input, "[[", $this->currPos, 2, false) === 0) {
    $r5 = "[[";
    $this->currPos += 2;
    $r5 = false;
    $this->currPos = $p4;
  } else {
    $r5 = self::$FAILED;
    $r1 = self::$FAILED;
    goto seq_1;
  }
  // free $p4
  $this->savedPos = $this->currPos;
  $r6 = $this->a110($param_preproc);
  if ($r6) {
    $r6 = false;
  } else {
    $r6 = self::$FAILED;
    $this->currPos = $p3;
    $r1 = self::$FAILED;
    goto seq_1;
  }
  // start seq_2
  $p4 = $this->currPos;
  if (($this->input[$this->currPos] ?? null) === "[") {
    $this->currPos++;
    $r8 = "[";
  } else {
    if (!$silence) {$this->fail(19);}
    $r8 = self::$FAILED;
    $r7 = self::$FAILED;
    goto seq_2;
  }
  // start choice_1
  $r9 = $this->parseextlink($silence, $boolParams, $param_preproc, $param_th);
  if ($r9!==self::$FAILED) {
    goto choice_1;
  }
  if (($this->input[$this->currPos] ?? null) === "[") {
    $this->currPos++;
    $r9 = "[";
  } else {
    if (!$silence) {$this->fail(19);}
    $r9 = self::$FAILED;
  }
  choice_1:
  if ($r9===self::$FAILED) {
    $this->currPos = $p4;
    $r7 = self::$FAILED;
    goto seq_2;
  }
  $r7 = [$r8,$r9];
  seq_2:
  // a <- $r7
  if ($r7===self::$FAILED) {
    $this->currPos = $p3;
    $r1 = self::$FAILED;
    goto seq_1;
  }
  // free $p4
  $r1 = true;
  seq_1:
  if ($r1!==self::$FAILED) {
    $this->savedPos = $p2;
    $r1 = $this->a111($param_preproc, $r7);
  }
  // free $p3
  $this->cache[$bucket][$key] = new GrammarCacheEntry(
    $this->currPos,
    $r1,
    $saved_preproc !== $param_preproc ? $param_preproc : self::$UNDEFINED,
    $saved_th !== $param_th ? $param_th : self::$UNDEFINED
  );
  return $r1;
}
private function parsetable_heading_tags($silence, $boolParams, &$param_preproc) {
  $key = json_encode([466, $boolParams & 0x3fff, $param_preproc]);
  $bucket = $this->currPos;
  $cached = $this->cache[$bucket][$key] ?? null;
  if ($cached) {
    $this->currPos = $cached->nextPos;
    if ($cached->preproc !== self::$UNDEFINED) { $param_preproc = $cached->preproc; }
    return $cached->result;
  }
  $saved_preproc=$param_preproc;
  $r1 = $this->parsetable_heading_tags_parameterized($silence, $boolParams, $param_preproc, self::newRef(true));
  $this->cache[$bucket][$key] = new GrammarCacheEntry(
    $this->currPos,
    $r1,
    $saved_preproc !== $param_preproc ? $param_preproc : self::$UNDEFINED,
    self::$UNDEFINED
  );
  return $r1;
}
private function parsetable_row_tag($silence, $boolParams, &$param_preproc, &$param_th) {
  $key = json_encode([458, $boolParams & 0x3fbf, $param_preproc, $param_th]);
  $bucket = $this->currPos;
  $cached = $this->cache[$bucket][$key] ?? null;
  if ($cached) {
    $this->currPos = $cached->nextPos;
    if ($cached->preproc !== self::$UNDEFINED) { $param_preproc = $cached->preproc; }
    if ($cached->th !== self::$UNDEFINED) { $param_th = $cached->th; }
    return $cached->result;
  }
  $saved_preproc=$param_preproc;
  $saved_th=$param_th;
  $p2 = $this->currPos;
  // start seq_1
  $p3 = $this->currPos;
  if (!(/*tableDataBlock*/($boolParams & 0x1) !== 0)) {
    $r4 = false;
  } else {
    $r4 = self::$FAILED;
    $r1 = self::$FAILED;
    goto seq_1;
  }
  $r5 = $this->parsepipe($silence);
  // p <- $r5
  if ($r5===self::$FAILED) {
    $this->currPos = $p3;
    $r1 = self::$FAILED;
    goto seq_1;
  }
  $p7 = $this->currPos;
  $r6 = self::$FAILED;
  for (;;) {
    if (($this->input[$this->currPos] ?? null) === "-") {
      $this->currPos++;
      $r8 = "-";
      $r6 = true;
    } else {
      if (!$silence) {$this->fail(55);}
      $r8 = self::$FAILED;
      break;
    }
  }
  // dashes <- $r6
  if ($r6!==self::$FAILED) {
    $r6 = substr($this->input, $p7, $this->currPos - $p7);
  } else {
    $r6 = self::$FAILED;
    $this->currPos = $p3;
    $r1 = self::$FAILED;
    goto seq_1;
  }
  // free $r8
  // free $p7
  // start choice_1
  $r8 = $this->parsetable_attributes($silence, $boolParams & ~0x40, $param_preproc, $param_th);
  if ($r8!==self::$FAILED) {
    goto choice_1;
  }
  $this->savedPos = $this->currPos;
  $r8 = $this->a127($r5, $r6);
  if ($r8) {
    $r8 = false;
  } else {
    $r8 = self::$FAILED;
  }
  choice_1:
  // a <- $r8
  if ($r8===self::$FAILED) {
    $this->currPos = $p3;
    $r1 = self::$FAILED;
    goto seq_1;
  }
  $p7 = $this->currPos;
  $r9 = '';
  // tagEndPos <- $r9
  if ($r9!==self::$FAILED) {
    $this->savedPos = $p7;
    $r9 = $this->a128($r5, $r6, $r8);
  } else {
    $this->currPos = $p3;
    $r1 = self::$FAILED;
    goto seq_1;
  }
  $r10 = [];
  for (;;) {
    $r11 = $this->parsespace($silence);
    if ($r11!==self::$FAILED) {
      $r10[] = $r11;
    } else {
      break;
    }
  }
  // s2 <- $r10
  // free $r11
  $r1 = true;
  seq_1:
  if ($r1!==self::$FAILED) {
    $this->savedPos = $p2;
    $r1 = $this->a129($r5, $r6, $r8, $r9, $r10);
  }
  // free $p3
  $this->cache[$bucket][$key] = new GrammarCacheEntry(
    $this->currPos,
    $r1,
    $saved_preproc !== $param_preproc ? $param_preproc : self::$UNDEFINED,
    $saved_th !== $param_th ? $param_th : self::$UNDEFINED
  );
  return $r1;
}
private function parsetable_data_tags($silence, $boolParams, &$param_preproc, &$param_th) {
  $key = json_encode([462, $boolParams & 0x3fff, $param_preproc, $param_th]);
  $bucket = $this->currPos;
  $cached = $this->cache[$bucket][$key] ?? null;
  if ($cached) {
    $this->currPos = $cached->nextPos;
    if ($cached->preproc !== self::$UNDEFINED) { $param_preproc = $cached->preproc; }
    if ($cached->th !== self::$UNDEFINED) { $param_th = $cached->th; }
    return $cached->result;
  }
  $saved_preproc=$param_preproc;
  $saved_th=$param_th;
  $p2 = $this->currPos;
  // start seq_1
  $p3 = $this->currPos;
  if (!(/*tableDataBlock*/($boolParams & 0x1) !== 0)) {
    $r4 = false;
  } else {
    $r4 = self::$FAILED;
    $r1 = self::$FAILED;
    goto seq_1;
  }
  $r5 = $this->parsepipe($silence);
  // p <- $r5
  if ($r5===self::$FAILED) {
    $this->currPos = $p3;
    $r1 = self::$FAILED;
    goto seq_1;
  }
  $p6 = $this->currPos;
  $r7 = $this->input[$this->currPos] ?? '';
  if ($r7 === "+" || $r7 === "-") {
    $this->currPos++;
  } else {
    $r7 = self::$FAILED;
  }
  if ($r7 === self::$FAILED) {
    $r7 = false;
  } else {
    $r7 = self::$FAILED;
    $this->currPos = $p6;
    $this->currPos = $p3;
    $r1 = self::$FAILED;
    goto seq_1;
  }
  // free $p6
  $r8 = $this->parsetable_data_tag($silence, $boolParams, $param_preproc, $param_th);
  // td <- $r8
  if ($r8===self::$FAILED) {
    $this->currPos = $p3;
    $r1 = self::$FAILED;
    goto seq_1;
  }
  $p6 = $this->currPos;
  $r9 = '';
  // tagEndPos <- $r9
  if ($r9!==self::$FAILED) {
    $this->savedPos = $p6;
    $r9 = $this->a130($r5, $r8);
  } else {
    $this->currPos = $p3;
    $r1 = self::$FAILED;
    goto seq_1;
  }
  $r10 = $this->parsetds($silence, $boolParams, $param_preproc, $param_th);
  // tds <- $r10
  if ($r10===self::$FAILED) {
    $this->currPos = $p3;
    $r1 = self::$FAILED;
    goto seq_1;
  }
  $r1 = true;
  seq_1:
  if ($r1!==self::$FAILED) {
    $this->savedPos = $p2;
    $r1 = $this->a131($r5, $r8, $r9, $r10);
  }
  // free $p3
  $this->cache[$bucket][$key] = new GrammarCacheEntry(
    $this->currPos,
    $r1,
    $saved_preproc !== $param_preproc ? $param_preproc : self::$UNDEFINED,
    $saved_th !== $param_th ? $param_th : self::$UNDEFINED
  );
  return $r1;
}
private function parsetable_caption_tag($silence, $boolParams, &$param_preproc, &$param_th) {
  $key = json_encode([456, $boolParams & 0x3fff, $param_preproc, $param_th]);
  $bucket = $this->currPos;
  $cached = $this->cache[$bucket][$key] ?? null;
  if ($cached) {
    $this->currPos = $cached->nextPos;
    if ($cached->preproc !== self::$UNDEFINED) { $param_preproc = $cached->preproc; }
    if ($cached->th !== self::$UNDEFINED) { $param_th = $cached->th; }
    return $cached->result;
  }
  $saved_preproc=$param_preproc;
  $saved_th=$param_th;
  $p2 = $this->currPos;
  // start seq_1
  $p3 = $this->currPos;
  if (!(/*tableDataBlock*/($boolParams & 0x1) !== 0)) {
    $r4 = false;
  } else {
    $r4 = self::$FAILED;
    $r1 = self::$FAILED;
    goto seq_1;
  }
  $r5 = $this->parsepipe($silence);
  // p <- $r5
  if ($r5===self::$FAILED) {
    $this->currPos = $p3;
    $r1 = self::$FAILED;
    goto seq_1;
  }
  if (($this->input[$this->currPos] ?? null) === "+") {
    $this->currPos++;
    $r6 = "+";
  } else {
    if (!$silence) {$this->fail(71);}
    $r6 = self::$FAILED;
    $this->currPos = $p3;
    $r1 = self::$FAILED;
    goto seq_1;
  }
  $r7 = $this->parserow_syntax_table_args($silence, $boolParams, $param_preproc, $param_th);
  if ($r7===self::$FAILED) {
    $r7 = null;
  }
  // args <- $r7
  $p9 = $this->currPos;
  $r8 = '';
  // tagEndPos <- $r8
  if ($r8!==self::$FAILED) {
    $this->savedPos = $p9;
    $r8 = $this->a132($r5, $r7);
  } else {
    $this->currPos = $p3;
    $r1 = self::$FAILED;
    goto seq_1;
  }
  $r10 = [];
  for (;;) {
    $r11 = $this->parsenested_block_in_table($silence, $boolParams, $param_preproc, $param_th);
    if ($r11!==self::$FAILED) {
      $r10[] = $r11;
    } else {
      break;
    }
  }
  // c <- $r10
  // free $r11
  $r1 = true;
  seq_1:
  if ($r1!==self::$FAILED) {
    $this->savedPos = $p2;
    $r1 = $this->a133($r5, $r7, $r8, $r10);
  }
  // free $p3
  $this->cache[$bucket][$key] = new GrammarCacheEntry(
    $this->currPos,
    $r1,
    $saved_preproc !== $param_preproc ? $param_preproc : self::$UNDEFINED,
    $saved_th !== $param_th ? $param_th : self::$UNDEFINED
  );
  return $r1;
}
private function parsenewline($silence) {
  $key = 518;
  $bucket = $this->currPos;
  $cached = $this->cache[$bucket][$key] ?? null;
  if ($cached) {
    $this->currPos = $cached->nextPos;

    return $cached->result;
  }

  // start choice_1
  if (($this->input[$this->currPos] ?? null) === "\x0a") {
    $this->currPos++;
    $r1 = "\x0a";
    goto choice_1;
  } else {
    if (!$silence) {$this->fail(27);}
    $r1 = self::$FAILED;
  }
  if ($this->currPos >= $this->inputLength ? false : substr_compare($this->input, "\x0d\x0a", $this->currPos, 2, false) === 0) {
    $r1 = "\x0d\x0a";
    $this->currPos += 2;
  } else {
    if (!$silence) {$this->fail(28);}
    $r1 = self::$FAILED;
  }
  choice_1:
  $this->cache[$bucket][$key] = new GrammarCacheEntry(
    $this->currPos,
    $r1,
    self::$UNDEFINED,
    self::$UNDEFINED
  );
  return $r1;
}
private function discardsol($silence, $boolParams, &$param_preproc, &$param_th) {
  $key = json_encode([543, $boolParams & 0x3ebf, $param_preproc, $param_th]);
  $bucket = $this->currPos;
  $cached = $this->cache[$bucket][$key] ?? null;
  if ($cached) {
    $this->currPos = $cached->nextPos;
    if ($cached->preproc !== self::$UNDEFINED) { $param_preproc = $cached->preproc; }
    if ($cached->th !== self::$UNDEFINED) { $param_th = $cached->th; }
    return $cached->result;
  }
  $saved_preproc=$param_preproc;
  $saved_th=$param_th;
  // start seq_1
  $p1 = $this->currPos;
  // start choice_1
  $r3 = $this->discardempty_line_with_comments($silence);
  if ($r3!==self::$FAILED) {
    goto choice_1;
  }
  $r3 = $this->discardsol_prefix($silence);
  choice_1:
  if ($r3===self::$FAILED) {
    $r2 = self::$FAILED;
    goto seq_1;
  }
  for (;;) {
    $r5 = $this->discardsol_transparent($silence, $boolParams, $param_preproc, $param_th);
    if ($r5===self::$FAILED) {
      break;
    }
  }
  // free $r5
  $r4 = true;
  if ($r4===self::$FAILED) {
    $this->currPos = $p1;
    $r2 = self::$FAILED;
    goto seq_1;
  }
  // free $r4
  $r2 = true;
  seq_1:
  // free $r2,$p1
  $this->cache[$bucket][$key] = new GrammarCacheEntry(
    $this->currPos,
    $r2,
    $saved_preproc !== $param_preproc ? $param_preproc : self::$UNDEFINED,
    $saved_th !== $param_th ? $param_th : self::$UNDEFINED
  );
  return $r2;
}
private function parsetemplate_param_value($silence, $boolParams, &$param_preproc, &$param_th) {
  $key = json_encode([354, $boolParams & 0x3e17, $param_preproc, $param_th]);
  $bucket = $this->currPos;
  $cached = $this->cache[$bucket][$key] ?? null;
  if ($cached) {
    $this->currPos = $cached->nextPos;
    if ($cached->preproc !== self::$UNDEFINED) { $param_preproc = $cached->preproc; }
    if ($cached->th !== self::$UNDEFINED) { $param_th = $cached->th; }
    return $cached->result;
  }
  $saved_preproc=$param_preproc;
  $saved_th=$param_th;
  $p2 = $this->currPos;
  $r3 = $this->parsetemplate_param_text($silence, $boolParams & ~0x20, $param_preproc, $param_th);
  // tpt <- $r3
  $r1 = $r3;
  if ($r1!==self::$FAILED) {
    $this->savedPos = $p2;
    $r1 = $this->a134($r3);
  }
  $this->cache[$bucket][$key] = new GrammarCacheEntry(
    $this->currPos,
    $r1,
    $saved_preproc !== $param_preproc ? $param_preproc : self::$UNDEFINED,
    $saved_th !== $param_th ? $param_th : self::$UNDEFINED
  );
  return $r1;
}
private function discardnl_comment_space($silence) {
  $key = 527;
  $bucket = $this->currPos;
  $cached = $this->cache[$bucket][$key] ?? null;
  if ($cached) {
    $this->currPos = $cached->nextPos;

    return $cached->result;
  }

  // start choice_1
  $r1 = $this->discardnewlineToken($silence);
  if ($r1!==self::$FAILED) {
    goto choice_1;
  }
  $r1 = $this->discardspace($silence);
  if ($r1!==self::$FAILED) {
    goto choice_1;
  }
  $r1 = $this->discardcomment($silence);
  choice_1:
  $this->cache[$bucket][$key] = new GrammarCacheEntry(
    $this->currPos,
    $r1,
    self::$UNDEFINED,
    self::$UNDEFINED
  );
  return $r1;
}
private function parsenl_comment_space($silence) {
  $key = 526;
  $bucket = $this->currPos;
  $cached = $this->cache[$bucket][$key] ?? null;
  if ($cached) {
    $this->currPos = $cached->nextPos;

    return $cached->result;
  }

  // start choice_1
  $r1 = $this->parsenewlineToken($silence);
  if ($r1!==self::$FAILED) {
    goto choice_1;
  }
  $r1 = $this->parsespace($silence);
  if ($r1!==self::$FAILED) {
    goto choice_1;
  }
  $r1 = $this->parsecomment($silence);
  choice_1:
  $this->cache[$bucket][$key] = new GrammarCacheEntry(
    $this->currPos,
    $r1,
    self::$UNDEFINED,
    self::$UNDEFINED
  );
  return $r1;
}
private function parsetemplate_param($silence, $boolParams, &$param_preproc, &$param_th) {
  $key = json_encode([350, $boolParams & 0x3e17, $param_preproc, $param_th]);
  $bucket = $this->currPos;
  $cached = $this->cache[$bucket][$key] ?? null;
  if ($cached) {
    $this->currPos = $cached->nextPos;
    if ($cached->preproc !== self::$UNDEFINED) { $param_preproc = $cached->preproc; }
    if ($cached->th !== self::$UNDEFINED) { $param_th = $cached->th; }
    return $cached->result;
  }
  $saved_preproc=$param_preproc;
  $saved_th=$param_th;
  // start choice_1
  $p2 = $this->currPos;
  // start seq_1
  $p3 = $this->currPos;
  $r4 = $this->parsetemplate_param_name($silence, $boolParams, $param_preproc, $param_th);
  // name <- $r4
  if ($r4===self::$FAILED) {
    $r1 = self::$FAILED;
    goto seq_1;
  }
  $p6 = $this->currPos;
  // start seq_2
  $p7 = $this->currPos;
  $p9 = $this->currPos;
  $r8 = '';
  // kEndPos <- $r8
  if ($r8!==self::$FAILED) {
    $this->savedPos = $p9;
    $r8 = $this->a135($r4);
  } else {
    $r5 = self::$FAILED;
    goto seq_2;
  }
  if (($this->input[$this->currPos] ?? null) === "=") {
    $this->currPos++;
    $r10 = "=";
  } else {
    if (!$silence) {$this->fail(23);}
    $r10 = self::$FAILED;
    $this->currPos = $p7;
    $r5 = self::$FAILED;
    goto seq_2;
  }
  $p12 = $this->currPos;
  $r11 = '';
  // vStartPos <- $r11
  if ($r11!==self::$FAILED) {
    $this->savedPos = $p12;
    $r11 = $this->a136($r4, $r8);
  } else {
    $this->currPos = $p7;
    $r5 = self::$FAILED;
    goto seq_2;
  }
  $r13 = $this->parseoptionalSpaceToken($silence);
  // optSp <- $r13
  if ($r13===self::$FAILED) {
    $this->currPos = $p7;
    $r5 = self::$FAILED;
    goto seq_2;
  }
  $r14 = $this->parsetemplate_param_value($silence, $boolParams, $param_preproc, $param_th);
  if ($r14===self::$FAILED) {
    $r14 = null;
  }
  // tpv <- $r14
  $r5 = true;
  seq_2:
  if ($r5!==self::$FAILED) {
    $this->savedPos = $p6;
    $r5 = $this->a137($r4, $r8, $r11, $r13, $r14);
  } else {
    $r5 = null;
  }
  // free $p7
  // val <- $r5
  $r1 = true;
  seq_1:
  if ($r1!==self::$FAILED) {
    $this->savedPos = $p2;
    $r1 = $this->a138($r4, $r5);
    goto choice_1;
  }
  // free $p3
  $p3 = $this->currPos;
  $p7 = $this->currPos;
  $r1 = $this->input[$this->currPos] ?? '';
  if ($r1 === "|" || $r1 === "}") {
    $this->currPos++;
    $r1 = false;
    $this->currPos = $p7;
    $this->savedPos = $p3;
    $r1 = $this->a139();
  } else {
    $r1 = self::$FAILED;
  }
  // free $p7
  choice_1:
  $this->cache[$bucket][$key] = new GrammarCacheEntry(
    $this->currPos,
    $r1,
    $saved_preproc !== $param_preproc ? $param_preproc : self::$UNDEFINED,
    $saved_th !== $param_th ? $param_th : self::$UNDEFINED
  );
  return $r1;
}
private function parsewikilink_preprocessor_text($silence, $boolParams, &$param_preproc, &$param_th) {
  $key = json_encode([488, $boolParams & 0x3fff, $param_preproc, $param_th]);
  $bucket = $this->currPos;
  $cached = $this->cache[$bucket][$key] ?? null;
  if ($cached) {
    $this->currPos = $cached->nextPos;
    if ($cached->preproc !== self::$UNDEFINED) { $param_preproc = $cached->preproc; }
    if ($cached->th !== self::$UNDEFINED) { $param_th = $cached->th; }
    return $cached->result;
  }
  $saved_preproc=$param_preproc;
  $saved_th=$param_th;
  $p2 = $this->currPos;
  $r3 = [];
  for (;;) {
    // start choice_1
    $p6 = $this->currPos;
    $r5 = self::$FAILED;
    for (;;) {
      if (strcspn($this->input, "<[{\x0a\x0d\x09|!]}{ &-", $this->currPos, 1) !== 0) {
        $r7 = self::consumeChar($this->input, $this->currPos);
        $r5 = true;
      } else {
        $r7 = self::$FAILED;
        if (!$silence) {$this->fail(72);}
        break;
      }
    }
    // t <- $r5
    if ($r5!==self::$FAILED) {
      $r5 = substr($this->input, $p6, $this->currPos - $p6);
    } else {
      $r5 = self::$FAILED;
    }
    // free $r7
    // free $p6
    $r4 = $r5;
    if ($r4!==self::$FAILED) {
      goto choice_1;
    }
    $p6 = $this->currPos;
    // start seq_1
    $p8 = $this->currPos;
    $p9 = $this->currPos;
    $r7 = $this->discardinline_breaks(true, $boolParams, $param_preproc, $param_th);
    if ($r7 === self::$FAILED) {
      $r7 = false;
    } else {
      $r7 = self::$FAILED;
      $this->currPos = $p9;
      $r4 = self::$FAILED;
      goto seq_1;
    }
    // free $p9
    // start choice_2
    $r10 = $this->parsedirective($silence, $boolParams, $param_preproc, $param_th);
    if ($r10!==self::$FAILED) {
      goto choice_2;
    }
    $p9 = $this->currPos;
    // start seq_2
    $p11 = $this->currPos;
    $p12 = $this->currPos;
    if ($this->currPos >= $this->inputLength ? false : substr_compare($this->input, "]]", $this->currPos, 2, false) === 0) {
      $r13 = "]]";
      $this->currPos += 2;
    } else {
      $r13 = self::$FAILED;
    }
    if ($r13 === self::$FAILED) {
      $r13 = false;
    } else {
      $r13 = self::$FAILED;
      $this->currPos = $p12;
      $r10 = self::$FAILED;
      goto seq_2;
    }
    // free $p12
    // start choice_3
    $r14 = $this->discardtext_char($silence);
    if ($r14!==self::$FAILED) {
      goto choice_3;
    }
    if (strspn($this->input, "!<-}]\x0a\x0d", $this->currPos, 1) !== 0) {
      $r14 = $this->input[$this->currPos++];
    } else {
      $r14 = self::$FAILED;
      if (!$silence) {$this->fail(73);}
    }
    choice_3:
    if ($r14===self::$FAILED) {
      $this->currPos = $p11;
      $r10 = self::$FAILED;
      goto seq_2;
    }
    $r10 = true;
    seq_2:
    if ($r10!==self::$FAILED) {
      $r10 = substr($this->input, $p9, $this->currPos - $p9);
    } else {
      $r10 = self::$FAILED;
    }
    // free $p11
    // free $p9
    choice_2:
    // wr <- $r10
    if ($r10===self::$FAILED) {
      $this->currPos = $p8;
      $r4 = self::$FAILED;
      goto seq_1;
    }
    $r4 = true;
    seq_1:
    if ($r4!==self::$FAILED) {
      $this->savedPos = $p6;
      $r4 = $this->a140($r5, $r10);
    }
    // free $p8
    choice_1:
    if ($r4!==self::$FAILED) {
      $r3[] = $r4;
    } else {
      break;
    }
  }
  if (count($r3) === 0) {
    $r3 = self::$FAILED;
  }
  // r <- $r3
  // free $r4
  $r1 = $r3;
  if ($r1!==self::$FAILED) {
    $this->savedPos = $p2;
    $r1 = $this->a141($r3);
  }
  $this->cache[$bucket][$key] = new GrammarCacheEntry(
    $this->currPos,
    $r1,
    $saved_preproc !== $param_preproc ? $param_preproc : self::$UNDEFINED,
    $saved_th !== $param_th ? $param_th : self::$UNDEFINED
  );
  return $r1;
}
private function parsewikilink_content($silence, $boolParams, &$param_preproc, &$param_th) {
  $key = json_encode([388, $boolParams & 0x37df, $param_preproc, $param_th]);
  $bucket = $this->currPos;
  $cached = $this->cache[$bucket][$key] ?? null;
  if ($cached) {
    $this->currPos = $cached->nextPos;
    if ($cached->preproc !== self::$UNDEFINED) { $param_preproc = $cached->preproc; }
    if ($cached->th !== self::$UNDEFINED) { $param_th = $cached->th; }
    return $cached->result;
  }
  $saved_preproc=$param_preproc;
  $saved_th=$param_th;
  $r1 = [];
  for (;;) {
    $p3 = $this->currPos;
    // start seq_1
    $p4 = $this->currPos;
    $r5 = $this->discardpipe($silence);
    if ($r5===self::$FAILED) {
      $r2 = self::$FAILED;
      goto seq_1;
    }
    $p7 = $this->currPos;
    $r6 = '';
    // startPos <- $r6
    if ($r6!==self::$FAILED) {
      $this->savedPos = $p7;
      $r6 = $this->a17();
    } else {
      $this->currPos = $p4;
      $r2 = self::$FAILED;
      goto seq_1;
    }
    $r8 = $this->parselink_text($silence, $boolParams, $param_preproc, $param_th);
    if ($r8===self::$FAILED) {
      $r8 = null;
    }
    // lt <- $r8
    $r2 = true;
    seq_1:
    if ($r2!==self::$FAILED) {
      $this->savedPos = $p3;
      $r2 = $this->a142($r6, $r8);
      $r1[] = $r2;
    } else {
      break;
    }
    // free $p4
  }
  // free $r2
  $this->cache[$bucket][$key] = new GrammarCacheEntry(
    $this->currPos,
    $r1,
    $saved_preproc !== $param_preproc ? $param_preproc : self::$UNDEFINED,
    $saved_th !== $param_th ? $param_th : self::$UNDEFINED
  );
  return $r1;
}
private function parsetag_name($silence) {
  $key = 412;
  $bucket = $this->currPos;
  $cached = $this->cache[$bucket][$key] ?? null;
  if ($cached) {
    $this->currPos = $cached->nextPos;

    return $cached->result;
  }

  $p1 = $this->currPos;
  $r2 = self::$FAILED;
  for (;;) {
    if (strcspn($this->input, "\x09\x0a\x0b />\x00", $this->currPos, 1) !== 0) {
      $r3 = self::consumeChar($this->input, $this->currPos);
      $r2 = true;
    } else {
      $r3 = self::$FAILED;
      if (!$silence) {$this->fail(74);}
      break;
    }
  }
  if ($r2!==self::$FAILED) {
    $r2 = substr($this->input, $p1, $this->currPos - $p1);
  } else {
    $r2 = self::$FAILED;
  }
  // free $r3
  // free $p1
  $this->cache[$bucket][$key] = new GrammarCacheEntry(
    $this->currPos,
    $r2,
    self::$UNDEFINED,
    self::$UNDEFINED
  );
  return $r2;
}
private function parsespace_or_nbsp($silence) {
  $key = 536;
  $bucket = $this->currPos;
  $cached = $this->cache[$bucket][$key] ?? null;
  if ($cached) {
    $this->currPos = $cached->nextPos;

    return $cached->result;
  }

  // start choice_1
  $r1 = $this->parsespace($silence);
  if ($r1!==self::$FAILED) {
    goto choice_1;
  }
  $r1 = $this->parseunispace($silence);
  if ($r1!==self::$FAILED) {
    goto choice_1;
  }
  $p2 = $this->currPos;
  // start seq_1
  $p3 = $this->currPos;
  $p4 = $this->currPos;
  if (($this->input[$this->currPos] ?? null) === "&") {
    $this->currPos++;
    $r5 = "&";
    $r5 = false;
    $this->currPos = $p4;
  } else {
    $r5 = self::$FAILED;
    $r1 = self::$FAILED;
    goto seq_1;
  }
  // free $p4
  $r6 = $this->parsehtmlentity($silence);
  // he <- $r6
  if ($r6===self::$FAILED) {
    $this->currPos = $p3;
    $r1 = self::$FAILED;
    goto seq_1;
  }
  $this->savedPos = $this->currPos;
  $r7 = $this->a143($r6);
  if ($r7) {
    $r7 = false;
  } else {
    $r7 = self::$FAILED;
    $this->currPos = $p3;
    $r1 = self::$FAILED;
    goto seq_1;
  }
  $r1 = true;
  seq_1:
  if ($r1!==self::$FAILED) {
    $this->savedPos = $p2;
    $r1 = $this->a65($r6);
  }
  // free $p3
  choice_1:
  $this->cache[$bucket][$key] = new GrammarCacheEntry(
    $this->currPos,
    $r1,
    self::$UNDEFINED,
    self::$UNDEFINED
  );
  return $r1;
}
private function discardend_of_word($silence) {
  $key = 533;
  $bucket = $this->currPos;
  $cached = $this->cache[$bucket][$key] ?? null;
  if ($cached) {
    $this->currPos = $cached->nextPos;

    return $cached->result;
  }

  // start choice_1
  $r1 = $this->discardeof($silence);
  if ($r1!==self::$FAILED) {
    goto choice_1;
  }
  $p2 = $this->currPos;
  $r1 = $this->input[$this->currPos] ?? '';
  if (preg_match("/^[A-Za-z0-9_]/", $r1)) {
    $this->currPos++;
  } else {
    $r1 = self::$FAILED;
  }
  if ($r1 === self::$FAILED) {
    $r1 = false;
  } else {
    $r1 = self::$FAILED;
    $this->currPos = $p2;
  }
  // free $p2
  choice_1:
  $this->cache[$bucket][$key] = new GrammarCacheEntry(
    $this->currPos,
    $r1,
    self::$UNDEFINED,
    self::$UNDEFINED
  );
  return $r1;
}
private function parsespace_or_nbsp_or_dash($silence) {
  $key = 538;
  $bucket = $this->currPos;
  $cached = $this->cache[$bucket][$key] ?? null;
  if ($cached) {
    $this->currPos = $cached->nextPos;

    return $cached->result;
  }

  // start choice_1
  $r1 = $this->parsespace_or_nbsp($silence);
  if ($r1!==self::$FAILED) {
    goto choice_1;
  }
  if (($this->input[$this->currPos] ?? null) === "-") {
    $this->currPos++;
    $r1 = "-";
  } else {
    if (!$silence) {$this->fail(55);}
    $r1 = self::$FAILED;
  }
  choice_1:
  $this->cache[$bucket][$key] = new GrammarCacheEntry(
    $this->currPos,
    $r1,
    self::$UNDEFINED,
    self::$UNDEFINED
  );
  return $r1;
}
private function discardtext_char($silence) {
  $key = 477;
  $bucket = $this->currPos;
  $cached = $this->cache[$bucket][$key] ?? null;
  if ($cached) {
    $this->currPos = $cached->nextPos;

    return $cached->result;
  }

  if (strcspn($this->input, "-'<[{\x0a\x0d:;]}|!=", $this->currPos, 1) !== 0) {
    $r1 = self::consumeChar($this->input, $this->currPos);
  } else {
    $r1 = self::$FAILED;
    if (!$silence) {$this->fail(54);}
  }
  $this->cache[$bucket][$key] = new GrammarCacheEntry(
    $this->currPos,
    $r1,
    self::$UNDEFINED,
    self::$UNDEFINED
  );
  return $r1;
}
private function parselang_variant_preproc($silence, $boolParams, &$param_preproc, &$param_th) {
  $key = json_encode([364, $boolParams & 0x3fff, $param_preproc, $param_th]);
  $bucket = $this->currPos;
  $cached = $this->cache[$bucket][$key] ?? null;
  if ($cached) {
    $this->currPos = $cached->nextPos;
    if ($cached->preproc !== self::$UNDEFINED) { $param_preproc = $cached->preproc; }
    if ($cached->th !== self::$UNDEFINED) { $param_th = $cached->th; }
    return $cached->result;
  }
  $saved_preproc=$param_preproc;
  $saved_th=$param_th;
  $p2 = $this->currPos;
  // start seq_1
  $p3 = $this->currPos;
  $p5 = $this->currPos;
  // lv0 <- $r4
  if ($this->currPos >= $this->inputLength ? false : substr_compare($this->input, "-{", $this->currPos, 2, false) === 0) {
    $r4 = "-{";
    $this->currPos += 2;
    $this->savedPos = $p5;
    $r4 = $this->a144();
  } else {
    if (!$silence) {$this->fail(75);}
    $r4 = self::$FAILED;
    $r1 = self::$FAILED;
    goto seq_1;
  }
  // start choice_1
  $p7 = $this->currPos;
  // start seq_2
  $p8 = $this->currPos;
  $this->savedPos = $this->currPos;
  $r9 = $this->a145($r4);
  if ($r9) {
    $r9 = false;
  } else {
    $r9 = self::$FAILED;
    $r6 = self::$FAILED;
    goto seq_2;
  }
  $r10 = $this->parseopt_lang_variant_flags($silence, $boolParams, $param_preproc, $param_th);
  // ff <- $r10
  if ($r10===self::$FAILED) {
    $this->currPos = $p8;
    $r6 = self::$FAILED;
    goto seq_2;
  }
  $r6 = true;
  seq_2:
  if ($r6!==self::$FAILED) {
    $this->savedPos = $p7;
    $r6 = $this->a146($r4, $r10);
    goto choice_1;
  }
  // free $p8
  $p8 = $this->currPos;
  // start seq_3
  $p11 = $this->currPos;
  $this->savedPos = $this->currPos;
  $r12 = $this->a147($r4);
  if ($r12) {
    $r12 = false;
  } else {
    $r12 = self::$FAILED;
    $r6 = self::$FAILED;
    goto seq_3;
  }
  $r6 = true;
  seq_3:
  if ($r6!==self::$FAILED) {
    $this->savedPos = $p8;
    $r6 = $this->a148($r4);
  }
  // free $p11
  choice_1:
  // f <- $r6
  if ($r6===self::$FAILED) {
    $this->currPos = $p3;
    $r1 = self::$FAILED;
    goto seq_1;
  }
  // start choice_2
  $p11 = $this->currPos;
  // start seq_4
  $p14 = $this->currPos;
  $this->savedPos = $this->currPos;
  $r15 = $this->a149($r4, $r6);
  if ($r15) {
    $r15 = false;
  } else {
    $r15 = self::$FAILED;
    $r13 = self::$FAILED;
    goto seq_4;
  }
  $r16 = $this->parselang_variant_text($silence, $boolParams, $param_preproc, $param_th);
  // lv <- $r16
  if ($r16===self::$FAILED) {
    $this->currPos = $p14;
    $r13 = self::$FAILED;
    goto seq_4;
  }
  $r13 = true;
  seq_4:
  if ($r13!==self::$FAILED) {
    $this->savedPos = $p11;
    $r13 = $this->a150($r4, $r6, $r16);
    goto choice_2;
  }
  // free $p14
  $p14 = $this->currPos;
  // start seq_5
  $p17 = $this->currPos;
  $this->savedPos = $this->currPos;
  $r18 = $this->a151($r4, $r6);
  if ($r18) {
    $r18 = false;
  } else {
    $r18 = self::$FAILED;
    $r13 = self::$FAILED;
    goto seq_5;
  }
  $r19 = $this->parselang_variant_option_list($silence, $boolParams, $param_preproc, $param_th);
  // lv <- $r19
  if ($r19===self::$FAILED) {
    $this->currPos = $p17;
    $r13 = self::$FAILED;
    goto seq_5;
  }
  $r13 = true;
  seq_5:
  if ($r13!==self::$FAILED) {
    $this->savedPos = $p14;
    $r13 = $this->a152($r4, $r6, $r19);
  }
  // free $p17
  choice_2:
  // ts <- $r13
  if ($r13===self::$FAILED) {
    $this->currPos = $p3;
    $r1 = self::$FAILED;
    goto seq_1;
  }
  $r20 = $this->discardinline_breaks($silence, $boolParams, $param_preproc, $param_th);
  if ($r20===self::$FAILED) {
    $this->currPos = $p3;
    $r1 = self::$FAILED;
    goto seq_1;
  }
  $p17 = $this->currPos;
  // lv1 <- $r21
  if ($this->currPos >= $this->inputLength ? false : substr_compare($this->input, "}-", $this->currPos, 2, false) === 0) {
    $r21 = "}-";
    $this->currPos += 2;
    $this->savedPos = $p17;
    $r21 = $this->a153($r4, $r6, $r13);
  } else {
    if (!$silence) {$this->fail(76);}
    $r21 = self::$FAILED;
    $this->currPos = $p3;
    $r1 = self::$FAILED;
    goto seq_1;
  }
  $r1 = true;
  seq_1:
  if ($r1!==self::$FAILED) {
    $this->savedPos = $p2;
    $r1 = $this->a154($r4, $r6, $r13, $r21);
  }
  // free $p3
  $this->cache[$bucket][$key] = new GrammarCacheEntry(
    $this->currPos,
    $r1,
    $saved_preproc !== $param_preproc ? $param_preproc : self::$UNDEFINED,
    $saved_th !== $param_th ? $param_th : self::$UNDEFINED
  );
  return $r1;
}
private function parsebroken_lang_variant($silence, &$param_preproc) {
  $key = json_encode([360, $param_preproc]);
  $bucket = $this->currPos;
  $cached = $this->cache[$bucket][$key] ?? null;
  if ($cached) {
    $this->currPos = $cached->nextPos;
    if ($cached->preproc !== self::$UNDEFINED) { $param_preproc = $cached->preproc; }
    return $cached->result;
  }
  $saved_preproc=$param_preproc;
  $p2 = $this->currPos;
  // start seq_1
  $p3 = $this->currPos;
  // r <- $r4
  if ($this->currPos >= $this->inputLength ? false : substr_compare($this->input, "-{", $this->currPos, 2, false) === 0) {
    $r4 = "-{";
    $this->currPos += 2;
  } else {
    if (!$silence) {$this->fail(75);}
    $r4 = self::$FAILED;
    $r1 = self::$FAILED;
    goto seq_1;
  }
  $r1 = true;
  seq_1:
  if ($r1!==self::$FAILED) {
    $this->savedPos = $p2;
    $r1 = $this->a155($r4, $param_preproc);
  }
  // free $p3
  $this->cache[$bucket][$key] = new GrammarCacheEntry(
    $this->currPos,
    $r1,
    $saved_preproc !== $param_preproc ? $param_preproc : self::$UNDEFINED,
    self::$UNDEFINED
  );
  return $r1;
}
private function parsetable_heading_tags_parameterized($silence, $boolParams, &$param_preproc, &$param_th) {
  $key = json_encode([468, $boolParams & 0x3fff, $param_preproc, $param_th]);
  $bucket = $this->currPos;
  $cached = $this->cache[$bucket][$key] ?? null;
  if ($cached) {
    $this->currPos = $cached->nextPos;
    if ($cached->preproc !== self::$UNDEFINED) { $param_preproc = $cached->preproc; }
    if ($cached->th !== self::$UNDEFINED) { $param_th = $cached->th; }
    return $cached->result;
  }
  $saved_preproc=$param_preproc;
  $saved_th=$param_th;
  $p2 = $this->currPos;
  // start seq_1
  $p3 = $this->currPos;
  if (($this->input[$this->currPos] ?? null) === "!") {
    $this->currPos++;
    $r4 = "!";
  } else {
    if (!$silence) {$this->fail(77);}
    $r4 = self::$FAILED;
    $r1 = self::$FAILED;
    goto seq_1;
  }
  $r5 = $this->parsetable_heading_tag($silence, $boolParams, $param_preproc, $param_th);
  // thTag <- $r5
  if ($r5===self::$FAILED) {
    $this->currPos = $p3;
    $r1 = self::$FAILED;
    goto seq_1;
  }
  $r6 = [];
  for (;;) {
    $p8 = $this->currPos;
    // start seq_2
    $p9 = $this->currPos;
    // start choice_1
    if ($this->currPos >= $this->inputLength ? false : substr_compare($this->input, "!!", $this->currPos, 2, false) === 0) {
      $r10 = "!!";
      $this->currPos += 2;
      goto choice_1;
    } else {
      if (!$silence) {$this->fail(78);}
      $r10 = self::$FAILED;
    }
    $r10 = $this->parsepipe_pipe($silence);
    choice_1:
    // pp <- $r10
    if ($r10===self::$FAILED) {
      $r7 = self::$FAILED;
      goto seq_2;
    }
    $r11 = $this->parsetable_heading_tag($silence, $boolParams, $param_preproc, $param_th);
    // tht <- $r11
    if ($r11===self::$FAILED) {
      $this->currPos = $p9;
      $r7 = self::$FAILED;
      goto seq_2;
    }
    $r7 = true;
    seq_2:
    if ($r7!==self::$FAILED) {
      $this->savedPos = $p8;
      $r7 = $this->a156($r5, $r10, $r11);
      $r6[] = $r7;
    } else {
      break;
    }
    // free $p9
  }
  // thTags <- $r6
  // free $r7
  $r1 = true;
  seq_1:
  if ($r1!==self::$FAILED) {
    $this->savedPos = $p2;
    $r1 = $this->a157($r5, $r6);
  }
  // free $p3
  $this->cache[$bucket][$key] = new GrammarCacheEntry(
    $this->currPos,
    $r1,
    $saved_preproc !== $param_preproc ? $param_preproc : self::$UNDEFINED,
    $saved_th !== $param_th ? $param_th : self::$UNDEFINED
  );
  return $r1;
}
private function parsetable_data_tag($silence, $boolParams, &$param_preproc, &$param_th) {
  $key = json_encode([464, $boolParams & 0x3fff, $param_preproc, $param_th]);
  $bucket = $this->currPos;
  $cached = $this->cache[$bucket][$key] ?? null;
  if ($cached) {
    $this->currPos = $cached->nextPos;
    if ($cached->preproc !== self::$UNDEFINED) { $param_preproc = $cached->preproc; }
    if ($cached->th !== self::$UNDEFINED) { $param_th = $cached->th; }
    return $cached->result;
  }
  $saved_preproc=$param_preproc;
  $saved_th=$param_th;
  $p2 = $this->currPos;
  // start seq_1
  $p3 = $this->currPos;
  $p4 = $this->currPos;
  if (($this->input[$this->currPos] ?? null) === "}") {
    $this->currPos++;
    $r5 = "}";
  } else {
    $r5 = self::$FAILED;
  }
  if ($r5 === self::$FAILED) {
    $r5 = false;
  } else {
    $r5 = self::$FAILED;
    $this->currPos = $p4;
    $r1 = self::$FAILED;
    goto seq_1;
  }
  // free $p4
  $r6 = $this->parserow_syntax_table_args($silence, $boolParams, $param_preproc, $param_th);
  if ($r6===self::$FAILED) {
    $r6 = null;
  }
  // arg <- $r6
  $p4 = $this->currPos;
  $r7 = '';
  // tagEndPos <- $r7
  if ($r7!==self::$FAILED) {
    $this->savedPos = $p4;
    $r7 = $this->a158($r6);
  } else {
    $this->currPos = $p3;
    $r1 = self::$FAILED;
    goto seq_1;
  }
  $r8 = [];
  for (;;) {
    $r9 = $this->parsenested_block_in_table($silence, $boolParams, $param_preproc, $param_th);
    if ($r9!==self::$FAILED) {
      $r8[] = $r9;
    } else {
      break;
    }
  }
  // td <- $r8
  // free $r9
  $r1 = true;
  seq_1:
  if ($r1!==self::$FAILED) {
    $this->savedPos = $p2;
    $r1 = $this->a159($r6, $r7, $r8);
  }
  // free $p3
  $this->cache[$bucket][$key] = new GrammarCacheEntry(
    $this->currPos,
    $r1,
    $saved_preproc !== $param_preproc ? $param_preproc : self::$UNDEFINED,
    $saved_th !== $param_th ? $param_th : self::$UNDEFINED
  );
  return $r1;
}
private function parsetds($silence, $boolParams, &$param_preproc, &$param_th) {
  $key = json_encode([460, $boolParams & 0x3fff, $param_preproc, $param_th]);
  $bucket = $this->currPos;
  $cached = $this->cache[$bucket][$key] ?? null;
  if ($cached) {
    $this->currPos = $cached->nextPos;
    if ($cached->preproc !== self::$UNDEFINED) { $param_preproc = $cached->preproc; }
    if ($cached->th !== self::$UNDEFINED) { $param_th = $cached->th; }
    return $cached->result;
  }
  $saved_preproc=$param_preproc;
  $saved_th=$param_th;
  $r1 = [];
  for (;;) {
    $p3 = $this->currPos;
    // start seq_1
    $p4 = $this->currPos;
    // start choice_1
    $r5 = $this->parsepipe_pipe($silence);
    if ($r5!==self::$FAILED) {
      goto choice_1;
    }
    $p6 = $this->currPos;
    // start seq_2
    $p7 = $this->currPos;
    $r8 = $this->parsepipe($silence);
    // p <- $r8
    if ($r8===self::$FAILED) {
      $r5 = self::$FAILED;
      goto seq_2;
    }
    $p9 = $this->currPos;
    $r10 = $this->discardrow_syntax_table_args(true, $boolParams, $param_preproc, $param_th);
    if ($r10!==self::$FAILED) {
      $r10 = false;
      $this->currPos = $p9;
    } else {
      $this->currPos = $p7;
      $r5 = self::$FAILED;
      goto seq_2;
    }
    // free $p9
    $r5 = true;
    seq_2:
    if ($r5!==self::$FAILED) {
      $this->savedPos = $p6;
      $r5 = $this->a29($r8);
    }
    // free $p7
    choice_1:
    // pp <- $r5
    if ($r5===self::$FAILED) {
      $r2 = self::$FAILED;
      goto seq_1;
    }
    $r11 = $this->parsetable_data_tag($silence, $boolParams, $param_preproc, $param_th);
    // tdt <- $r11
    if ($r11===self::$FAILED) {
      $this->currPos = $p4;
      $r2 = self::$FAILED;
      goto seq_1;
    }
    $r2 = true;
    seq_1:
    if ($r2!==self::$FAILED) {
      $this->savedPos = $p3;
      $r2 = $this->a160($r5, $r11);
      $r1[] = $r2;
    } else {
      break;
    }
    // free $p4
  }
  // free $r2
  $this->cache[$bucket][$key] = new GrammarCacheEntry(
    $this->currPos,
    $r1,
    $saved_preproc !== $param_preproc ? $param_preproc : self::$UNDEFINED,
    $saved_th !== $param_th ? $param_th : self::$UNDEFINED
  );
  return $r1;
}
private function parsenested_block_in_table($silence, $boolParams, &$param_preproc, &$param_th) {
  $key = json_encode([292, $boolParams & 0x3fff, $param_preproc, $param_th]);
  $bucket = $this->currPos;
  $cached = $this->cache[$bucket][$key] ?? null;
  if ($cached) {
    $this->currPos = $cached->nextPos;
    if ($cached->preproc !== self::$UNDEFINED) { $param_preproc = $cached->preproc; }
    if ($cached->th !== self::$UNDEFINED) { $param_th = $cached->th; }
    return $cached->result;
  }
  $saved_preproc=$param_preproc;
  $saved_th=$param_th;
  $p2 = $this->currPos;
  // start seq_1
  $p3 = $this->currPos;
  $p4 = $this->currPos;
  // start seq_2
  $p6 = $this->currPos;
  $r7 = $this->discardsol(true, $boolParams, $param_preproc, $param_th);
  if ($r7===self::$FAILED) {
    $r5 = self::$FAILED;
    goto seq_2;
  }
  // start seq_3
  $p9 = $this->currPos;
  for (;;) {
    $r11 = $this->discardspace(true);
    if ($r11===self::$FAILED) {
      break;
    }
  }
  // free $r11
  $r10 = true;
  if ($r10===self::$FAILED) {
    $r8 = self::$FAILED;
    goto seq_3;
  }
  // free $r10
  $r10 = $this->discardsol(true, $boolParams, $param_preproc, $param_th);
  if ($r10===self::$FAILED) {
    $this->currPos = $p9;
    $r8 = self::$FAILED;
    goto seq_3;
  }
  $r8 = true;
  seq_3:
  if ($r8===self::$FAILED) {
    $r8 = null;
  }
  // free $p9
  for (;;) {
    $r12 = $this->discardspace(true);
    if ($r12===self::$FAILED) {
      break;
    }
  }
  // free $r12
  $r11 = true;
  if ($r11===self::$FAILED) {
    $this->currPos = $p6;
    $r5 = self::$FAILED;
    goto seq_2;
  }
  // free $r11
  // start choice_1
  $r11 = $this->discardpipe(true);
  if ($r11!==self::$FAILED) {
    goto choice_1;
  }
  if (($this->input[$this->currPos] ?? null) === "!") {
    $this->currPos++;
    $r11 = "!";
  } else {
    $r11 = self::$FAILED;
  }
  choice_1:
  if ($r11===self::$FAILED) {
    $this->currPos = $p6;
    $r5 = self::$FAILED;
    goto seq_2;
  }
  $r5 = true;
  seq_2:
  // free $p6
  if ($r5 === self::$FAILED) {
    $r5 = false;
  } else {
    $r5 = self::$FAILED;
    $this->currPos = $p4;
    $r1 = self::$FAILED;
    goto seq_1;
  }
  // free $p4
  $r12 = $this->parsenested_block($silence, $boolParams | 0x1, $param_preproc, $param_th);
  // b <- $r12
  if ($r12===self::$FAILED) {
    $this->currPos = $p3;
    $r1 = self::$FAILED;
    goto seq_1;
  }
  $r1 = true;
  seq_1:
  if ($r1!==self::$FAILED) {
    $this->savedPos = $p2;
    $r1 = $this->a161($r12);
  }
  // free $p3
  $this->cache[$bucket][$key] = new GrammarCacheEntry(
    $this->currPos,
    $r1,
    $saved_preproc !== $param_preproc ? $param_preproc : self::$UNDEFINED,
    $saved_th !== $param_th ? $param_th : self::$UNDEFINED
  );
  return $r1;
}
private function discardempty_line_with_comments($silence) {
  $key = 547;
  $bucket = $this->currPos;
  $cached = $this->cache[$bucket][$key] ?? null;
  if ($cached) {
    $this->currPos = $cached->nextPos;

    return $cached->result;
  }

  $p2 = $this->currPos;
  // start seq_1
  $p3 = $this->currPos;
  $r4 = $this->parsesol_prefix($silence);
  // sp <- $r4
  if ($r4===self::$FAILED) {
    $r1 = self::$FAILED;
    goto seq_1;
  }
  $p6 = $this->currPos;
  $r5 = '';
  // p <- $r5
  if ($r5!==self::$FAILED) {
    $this->savedPos = $p6;
    $r5 = $this->a82($r4);
  } else {
    $this->currPos = $p3;
    $r1 = self::$FAILED;
    goto seq_1;
  }
  $r7 = [];
  for (;;) {
    // start seq_2
    $p9 = $this->currPos;
    $r10 = [];
    for (;;) {
      $r11 = $this->parsespace($silence);
      if ($r11!==self::$FAILED) {
        $r10[] = $r11;
      } else {
        break;
      }
    }
    // free $r11
    $r11 = $this->parsecomment($silence);
    if ($r11===self::$FAILED) {
      $this->currPos = $p9;
      $r8 = self::$FAILED;
      goto seq_2;
    }
    $r12 = [];
    for (;;) {
      // start choice_1
      $r13 = $this->parsespace($silence);
      if ($r13!==self::$FAILED) {
        goto choice_1;
      }
      $r13 = $this->parsecomment($silence);
      choice_1:
      if ($r13!==self::$FAILED) {
        $r12[] = $r13;
      } else {
        break;
      }
    }
    // free $r13
    $r13 = $this->parsenewline($silence);
    if ($r13===self::$FAILED) {
      $this->currPos = $p9;
      $r8 = self::$FAILED;
      goto seq_2;
    }
    $r8 = [$r10,$r11,$r12,$r13];
    seq_2:
    if ($r8!==self::$FAILED) {
      $r7[] = $r8;
    } else {
      break;
    }
    // free $p9
  }
  if (count($r7) === 0) {
    $r7 = self::$FAILED;
  }
  // c <- $r7
  if ($r7===self::$FAILED) {
    $this->currPos = $p3;
    $r1 = self::$FAILED;
    goto seq_1;
  }
  // free $r8
  $r1 = true;
  seq_1:
  if ($r1!==self::$FAILED) {
    $this->savedPos = $p2;
    $r1 = $this->a83($r4, $r5, $r7);
  }
  // free $p3
  $this->cache[$bucket][$key] = new GrammarCacheEntry(
    $this->currPos,
    $r1,
    self::$UNDEFINED,
    self::$UNDEFINED
  );
  return $r1;
}
private function discardsol_prefix($silence) {
  $key = 545;
  $bucket = $this->currPos;
  $cached = $this->cache[$bucket][$key] ?? null;
  if ($cached) {
    $this->currPos = $cached->nextPos;

    return $cached->result;
  }

  // start choice_1
  $r1 = $this->discardnewlineToken($silence);
  if ($r1!==self::$FAILED) {
    goto choice_1;
  }
  $p2 = $this->currPos;
  $this->savedPos = $this->currPos;
  $r1 = $this->a84();
  if ($r1) {
    $r1 = false;
    $this->savedPos = $p2;
    $r1 = $this->a85();
  } else {
    $r1 = self::$FAILED;
  }
  choice_1:
  $this->cache[$bucket][$key] = new GrammarCacheEntry(
    $this->currPos,
    $r1,
    self::$UNDEFINED,
    self::$UNDEFINED
  );
  return $r1;
}
private function discardsol_transparent($silence, $boolParams, &$param_preproc, &$param_th) {
  $key = json_encode([541, $boolParams & 0x3ebf, $param_preproc, $param_th]);
  $bucket = $this->currPos;
  $cached = $this->cache[$bucket][$key] ?? null;
  if ($cached) {
    $this->currPos = $cached->nextPos;
    if ($cached->preproc !== self::$UNDEFINED) { $param_preproc = $cached->preproc; }
    if ($cached->th !== self::$UNDEFINED) { $param_th = $cached->th; }
    return $cached->result;
  }
  $saved_preproc=$param_preproc;
  $saved_th=$param_th;
  // start choice_1
  $r1 = $this->discardcomment($silence);
  if ($r1!==self::$FAILED) {
    goto choice_1;
  }
  $r1 = $this->discardinclude_limits($silence, $boolParams | 0x2000, $param_preproc, $param_th);
  if ($r1!==self::$FAILED) {
    goto choice_1;
  }
  $r1 = $this->discardannotation_tag($silence, $boolParams, $param_preproc, $param_th);
  if ($r1!==self::$FAILED) {
    goto choice_1;
  }
  $r1 = $this->discardbehavior_switch($silence);
  choice_1:
  $this->cache[$bucket][$key] = new GrammarCacheEntry(
    $this->currPos,
    $r1,
    $saved_preproc !== $param_preproc ? $param_preproc : self::$UNDEFINED,
    $saved_th !== $param_th ? $param_th : self::$UNDEFINED
  );
  return $r1;
}
private function parsetemplate_param_text($silence, $boolParams, &$param_preproc, &$param_th) {
  $key = json_encode([356, $boolParams & 0x3e37, $param_preproc, $param_th]);
  $bucket = $this->currPos;
  $cached = $this->cache[$bucket][$key] ?? null;
  if ($cached) {
    $this->currPos = $cached->nextPos;
    if ($cached->preproc !== self::$UNDEFINED) { $param_preproc = $cached->preproc; }
    if ($cached->th !== self::$UNDEFINED) { $param_th = $cached->th; }
    return $cached->result;
  }
  $saved_preproc=$param_preproc;
  $saved_th=$param_th;
  $p2 = $this->currPos;
  $r3 = [];
  for (;;) {
    // start choice_1
    $r4 = $this->parsenested_block($silence, ($boolParams & ~0x148) | 0x80, $param_preproc, $param_th);
    if ($r4!==self::$FAILED) {
      goto choice_1;
    }
    $r4 = $this->parsenewlineToken($silence);
    choice_1:
    if ($r4!==self::$FAILED) {
      $r3[] = $r4;
    } else {
      break;
    }
  }
  if (count($r3) === 0) {
    $r3 = self::$FAILED;
  }
  // il <- $r3
  // free $r4
  $r1 = $r3;
  if ($r1!==self::$FAILED) {
    $this->savedPos = $p2;
    $r1 = $this->a162($r3);
  }
  $this->cache[$bucket][$key] = new GrammarCacheEntry(
    $this->currPos,
    $r1,
    $saved_preproc !== $param_preproc ? $param_preproc : self::$UNDEFINED,
    $saved_th !== $param_th ? $param_th : self::$UNDEFINED
  );
  return $r1;
}
private function discardnewlineToken($silence) {
  $key = 521;
  $bucket = $this->currPos;
  $cached = $this->cache[$bucket][$key] ?? null;
  if ($cached) {
    $this->currPos = $cached->nextPos;

    return $cached->result;
  }

  $p2 = $this->currPos;
  $r1 = $this->discardnewline($silence);
  if ($r1!==self::$FAILED) {
    $this->savedPos = $p2;
    $r1 = $this->a26();
  }
  $this->cache[$bucket][$key] = new GrammarCacheEntry(
    $this->currPos,
    $r1,
    self::$UNDEFINED,
    self::$UNDEFINED
  );
  return $r1;
}
private function parsetemplate_param_name($silence, $boolParams, &$param_preproc, &$param_th) {
  $key = json_encode([352, $boolParams & 0x3e17, $param_preproc, $param_th]);
  $bucket = $this->currPos;
  $cached = $this->cache[$bucket][$key] ?? null;
  if ($cached) {
    $this->currPos = $cached->nextPos;
    if ($cached->preproc !== self::$UNDEFINED) { $param_preproc = $cached->preproc; }
    if ($cached->th !== self::$UNDEFINED) { $param_th = $cached->th; }
    return $cached->result;
  }
  $saved_preproc=$param_preproc;
  $saved_th=$param_th;
  // start choice_1
  $r1 = $this->parsetemplate_param_text($silence, $boolParams | 0x20, $param_preproc, $param_th);
  if ($r1!==self::$FAILED) {
    goto choice_1;
  }
  $p2 = $this->currPos;
  $p3 = $this->currPos;
  if (($this->input[$this->currPos] ?? null) === "=") {
    $this->currPos++;
    $r1 = "=";
    $r1 = false;
    $this->currPos = $p3;
    $this->savedPos = $p2;
    $r1 = $this->a163();
  } else {
    $r1 = self::$FAILED;
  }
  // free $p3
  choice_1:
  $this->cache[$bucket][$key] = new GrammarCacheEntry(
    $this->currPos,
    $r1,
    $saved_preproc !== $param_preproc ? $param_preproc : self::$UNDEFINED,
    $saved_th !== $param_th ? $param_th : self::$UNDEFINED
  );
  return $r1;
}
private function parselink_text($silence, $boolParams, &$param_preproc, &$param_th) {
  $key = json_encode([396, $boolParams & 0x37df, $param_preproc, $param_th]);
  $bucket = $this->currPos;
  $cached = $this->cache[$bucket][$key] ?? null;
  if ($cached) {
    $this->currPos = $cached->nextPos;
    if ($cached->preproc !== self::$UNDEFINED) { $param_preproc = $cached->preproc; }
    if ($cached->th !== self::$UNDEFINED) { $param_th = $cached->th; }
    return $cached->result;
  }
  $saved_preproc=$param_preproc;
  $saved_th=$param_th;
  $r1 = $this->parselink_text_parameterized($silence, ($boolParams & ~0x20) | 0x800, $param_preproc, $param_th);
  $this->cache[$bucket][$key] = new GrammarCacheEntry(
    $this->currPos,
    $r1,
    $saved_preproc !== $param_preproc ? $param_preproc : self::$UNDEFINED,
    $saved_th !== $param_th ? $param_th : self::$UNDEFINED
  );
  return $r1;
}
private function parseunispace($silence) {
  $key = 534;
  $bucket = $this->currPos;
  $cached = $this->cache[$bucket][$key] ?? null;
  if ($cached) {
    $this->currPos = $cached->nextPos;

    return $cached->result;
  }

  $r1 = self::charAt($this->input, $this->currPos);
  if (preg_match("/^[ \\x{a0}\\x{1680}\\x{2000}-\\x{200a}\\x{202f}\\x{205f}\\x{3000}]/u", $r1)) {
    $this->currPos += strlen($r1);
  } else {
    $r1 = self::$FAILED;
    if (!$silence) {$this->fail(25);}
  }
  $this->cache[$bucket][$key] = new GrammarCacheEntry(
    $this->currPos,
    $r1,
    self::$UNDEFINED,
    self::$UNDEFINED
  );
  return $r1;
}
private function parseopt_lang_variant_flags($silence, $boolParams, &$param_preproc, &$param_th) {
  $key = json_encode([366, $boolParams & 0x3ebf, $param_preproc, $param_th]);
  $bucket = $this->currPos;
  $cached = $this->cache[$bucket][$key] ?? null;
  if ($cached) {
    $this->currPos = $cached->nextPos;
    if ($cached->preproc !== self::$UNDEFINED) { $param_preproc = $cached->preproc; }
    if ($cached->th !== self::$UNDEFINED) { $param_th = $cached->th; }
    return $cached->result;
  }
  $saved_preproc=$param_preproc;
  $saved_th=$param_th;
  $p2 = $this->currPos;
  $p4 = $this->currPos;
  // start seq_1
  $p5 = $this->currPos;
  $r6 = $this->parselang_variant_flags($silence, $boolParams, $param_preproc, $param_th);
  // ff <- $r6
  if ($r6===self::$FAILED) {
    $r3 = self::$FAILED;
    goto seq_1;
  }
  if (($this->input[$this->currPos] ?? null) === "|") {
    $this->currPos++;
    $r7 = "|";
  } else {
    if (!$silence) {$this->fail(13);}
    $r7 = self::$FAILED;
    $this->currPos = $p5;
    $r3 = self::$FAILED;
    goto seq_1;
  }
  $r3 = true;
  seq_1:
  if ($r3!==self::$FAILED) {
    $this->savedPos = $p4;
    $r3 = $this->a164($r6);
  } else {
    $r3 = null;
  }
  // free $p5
  // f <- $r3
  $r1 = $r3;
  if ($r1!==self::$FAILED) {
    $this->savedPos = $p2;
    $r1 = $this->a165($r3);
  }
  $this->cache[$bucket][$key] = new GrammarCacheEntry(
    $this->currPos,
    $r1,
    $saved_preproc !== $param_preproc ? $param_preproc : self::$UNDEFINED,
    $saved_th !== $param_th ? $param_th : self::$UNDEFINED
  );
  return $r1;
}
private function parselang_variant_text($silence, $boolParams, &$param_preproc, &$param_th) {
  $key = json_encode([382, $boolParams & 0x3fff, $param_preproc, $param_th]);
  $bucket = $this->currPos;
  $cached = $this->cache[$bucket][$key] ?? null;
  if ($cached) {
    $this->currPos = $cached->nextPos;
    if ($cached->preproc !== self::$UNDEFINED) { $param_preproc = $cached->preproc; }
    if ($cached->th !== self::$UNDEFINED) { $param_th = $cached->th; }
    return $cached->result;
  }
  $saved_preproc=$param_preproc;
  $saved_th=$param_th;
  $p2 = $this->currPos;
  $r3 = [];
  for (;;) {
    // start choice_1
    $r4 = $this->parseinlineline($silence, $boolParams, $param_preproc, $param_th);
    if ($r4!==self::$FAILED) {
      goto choice_1;
    }
    if (($this->input[$this->currPos] ?? null) === "|") {
      $this->currPos++;
      $r4 = "|";
    } else {
      if (!$silence) {$this->fail(13);}
      $r4 = self::$FAILED;
    }
    choice_1:
    if ($r4!==self::$FAILED) {
      $r3[] = $r4;
    } else {
      break;
    }
  }
  // tokens <- $r3
  // free $r4
  $r1 = $r3;
  if ($r1!==self::$FAILED) {
    $this->savedPos = $p2;
    $r1 = $this->a166($r3);
  }
  $this->cache[$bucket][$key] = new GrammarCacheEntry(
    $this->currPos,
    $r1,
    $saved_preproc !== $param_preproc ? $param_preproc : self::$UNDEFINED,
    $saved_th !== $param_th ? $param_th : self::$UNDEFINED
  );
  return $r1;
}
private function parselang_variant_option_list($silence, $boolParams, &$param_preproc, &$param_th) {
  $key = json_encode([374, $boolParams & 0x3fff, $param_preproc, $param_th]);
  $bucket = $this->currPos;
  $cached = $this->cache[$bucket][$key] ?? null;
  if ($cached) {
    $this->currPos = $cached->nextPos;
    if ($cached->preproc !== self::$UNDEFINED) { $param_preproc = $cached->preproc; }
    if ($cached->th !== self::$UNDEFINED) { $param_th = $cached->th; }
    return $cached->result;
  }
  $saved_preproc=$param_preproc;
  $saved_th=$param_th;
  // start choice_1
  $p2 = $this->currPos;
  // start seq_1
  $p3 = $this->currPos;
  $r4 = $this->parselang_variant_option($silence, $boolParams, $param_preproc, $param_th);
  // o <- $r4
  if ($r4===self::$FAILED) {
    $r1 = self::$FAILED;
    goto seq_1;
  }
  $r5 = [];
  for (;;) {
    $p7 = $this->currPos;
    // start seq_2
    $p8 = $this->currPos;
    if (($this->input[$this->currPos] ?? null) === ";") {
      $this->currPos++;
      $r9 = ";";
    } else {
      if (!$silence) {$this->fail(26);}
      $r9 = self::$FAILED;
      $r6 = self::$FAILED;
      goto seq_2;
    }
    $r10 = $this->parselang_variant_option($silence, $boolParams, $param_preproc, $param_th);
    // oo <- $r10
    if ($r10===self::$FAILED) {
      $this->currPos = $p8;
      $r6 = self::$FAILED;
      goto seq_2;
    }
    $r6 = true;
    seq_2:
    if ($r6!==self::$FAILED) {
      $this->savedPos = $p7;
      $r6 = $this->a167($r4, $r10);
      $r5[] = $r6;
    } else {
      break;
    }
    // free $p8
  }
  // rest <- $r5
  // free $r6
  $r6 = [];
  for (;;) {
    // start seq_3
    $p8 = $this->currPos;
    if (($this->input[$this->currPos] ?? null) === ";") {
      $this->currPos++;
      $r12 = ";";
    } else {
      if (!$silence) {$this->fail(26);}
      $r12 = self::$FAILED;
      $r11 = self::$FAILED;
      goto seq_3;
    }
    $p13 = $this->currPos;
    $r14 = $this->discardbogus_lang_variant_option($silence, $boolParams, $param_preproc, $param_th);
    if ($r14!==self::$FAILED) {
      $r14 = substr($this->input, $p13, $this->currPos - $p13);
    } else {
      $r14 = self::$FAILED;
      $this->currPos = $p8;
      $r11 = self::$FAILED;
      goto seq_3;
    }
    // free $p13
    $r11 = [$r12,$r14];
    seq_3:
    if ($r11!==self::$FAILED) {
      $r6[] = $r11;
    } else {
      break;
    }
    // free $p8
  }
  // tr <- $r6
  // free $r11
  $r1 = true;
  seq_1:
  if ($r1!==self::$FAILED) {
    $this->savedPos = $p2;
    $r1 = $this->a168($r4, $r5, $r6);
    goto choice_1;
  }
  // free $p3
  $p3 = $this->currPos;
  $r11 = $this->parselang_variant_text($silence, $boolParams, $param_preproc, $param_th);
  // lvtext <- $r11
  $r1 = $r11;
  if ($r1!==self::$FAILED) {
    $this->savedPos = $p3;
    $r1 = $this->a169($r11);
  }
  choice_1:
  $this->cache[$bucket][$key] = new GrammarCacheEntry(
    $this->currPos,
    $r1,
    $saved_preproc !== $param_preproc ? $param_preproc : self::$UNDEFINED,
    $saved_th !== $param_th ? $param_th : self::$UNDEFINED
  );
  return $r1;
}
private function parsetable_heading_tag($silence, $boolParams, &$param_preproc, &$param_th) {
  $key = json_encode([470, $boolParams & 0x3fff, $param_preproc, $param_th]);
  $bucket = $this->currPos;
  $cached = $this->cache[$bucket][$key] ?? null;
  if ($cached) {
    $this->currPos = $cached->nextPos;
    if ($cached->preproc !== self::$UNDEFINED) { $param_preproc = $cached->preproc; }
    if ($cached->th !== self::$UNDEFINED) { $param_th = $cached->th; }
    return $cached->result;
  }
  $saved_preproc=$param_preproc;
  $saved_th=$param_th;
  $p2 = $this->currPos;
  // start seq_1
  $p3 = $this->currPos;
  $r4 = $this->parserow_syntax_table_args($silence, $boolParams, $param_preproc, $param_th);
  if ($r4===self::$FAILED) {
    $r4 = null;
  }
  // arg <- $r4
  $p6 = $this->currPos;
  $r5 = '';
  // tagEndPos <- $r5
  if ($r5!==self::$FAILED) {
    $this->savedPos = $p6;
    $r5 = $this->a158($r4);
  } else {
    $this->currPos = $p3;
    $r1 = self::$FAILED;
    goto seq_1;
  }
  $r7 = [];
  for (;;) {
    $p9 = $this->currPos;
    // start seq_2
    $p10 = $this->currPos;
    $r11 = $this->parsenested_block_in_table($silence, $boolParams, $param_preproc, $param_th);
    // d <- $r11
    if ($r11===self::$FAILED) {
      $this->currPos = $p10;
      $r8 = self::$FAILED;
      goto seq_2;
    }
    $r8 = true;
    seq_2:
    if ($r8!==self::$FAILED) {
      $this->savedPos = $p9;
      $r8 = $this->a170($r4, $r5, $param_th, $r11);
      $r7[] = $r8;
    } else {
      break;
    }
    // free $p10
  }
  // c <- $r7
  // free $r8
  $r1 = true;
  seq_1:
  if ($r1!==self::$FAILED) {
    $this->savedPos = $p2;
    $r1 = $this->a171($r4, $r5, $r7);
  }
  // free $p3
  $this->cache[$bucket][$key] = new GrammarCacheEntry(
    $this->currPos,
    $r1,
    $saved_preproc !== $param_preproc ? $param_preproc : self::$UNDEFINED,
    $saved_th !== $param_th ? $param_th : self::$UNDEFINED
  );
  return $r1;
}
private function parsepipe_pipe($silence) {
  $key = 508;
  $bucket = $this->currPos;
  $cached = $this->cache[$bucket][$key] ?? null;
  if ($cached) {
    $this->currPos = $cached->nextPos;

    return $cached->result;
  }

  // start choice_1
  if ($this->currPos >= $this->inputLength ? false : substr_compare($this->input, "||", $this->currPos, 2, false) === 0) {
    $r1 = "||";
    $this->currPos += 2;
    goto choice_1;
  } else {
    if (!$silence) {$this->fail(79);}
    $r1 = self::$FAILED;
  }
  if ($this->currPos >= $this->inputLength ? false : substr_compare($this->input, "{{!}}{{!}}", $this->currPos, 10, false) === 0) {
    $r1 = "{{!}}{{!}}";
    $this->currPos += 10;
  } else {
    if (!$silence) {$this->fail(80);}
    $r1 = self::$FAILED;
  }
  choice_1:
  $this->cache[$bucket][$key] = new GrammarCacheEntry(
    $this->currPos,
    $r1,
    self::$UNDEFINED,
    self::$UNDEFINED
  );
  return $r1;
}
private function discardrow_syntax_table_args($silence, $boolParams, &$param_preproc, &$param_th) {
  $key = json_encode([475, $boolParams & 0x3eff, $param_preproc, $param_th]);
  $bucket = $this->currPos;
  $cached = $this->cache[$bucket][$key] ?? null;
  if ($cached) {
    $this->currPos = $cached->nextPos;
    if ($cached->preproc !== self::$UNDEFINED) { $param_preproc = $cached->preproc; }
    if ($cached->th !== self::$UNDEFINED) { $param_th = $cached->th; }
    return $cached->result;
  }
  $saved_preproc=$param_preproc;
  $saved_th=$param_th;
  $p2 = $this->currPos;
  // start seq_1
  $p3 = $this->currPos;
  $r4 = $this->parsetable_attributes($silence, $boolParams | 0x100, $param_preproc, $param_th);
  // as <- $r4
  if ($r4===self::$FAILED) {
    $r1 = self::$FAILED;
    goto seq_1;
  }
  $r5 = $this->parseoptional_spaces($silence);
  // s <- $r5
  if ($r5===self::$FAILED) {
    $this->currPos = $p3;
    $r1 = self::$FAILED;
    goto seq_1;
  }
  $r6 = $this->parsepipe($silence);
  // p <- $r6
  if ($r6===self::$FAILED) {
    $this->currPos = $p3;
    $r1 = self::$FAILED;
    goto seq_1;
  }
  $p7 = $this->currPos;
  $r8 = $this->discardpipe(true);
  if ($r8 === self::$FAILED) {
    $r8 = false;
  } else {
    $r8 = self::$FAILED;
    $this->currPos = $p7;
    $this->currPos = $p3;
    $r1 = self::$FAILED;
    goto seq_1;
  }
  // free $p7
  $r1 = true;
  seq_1:
  if ($r1!==self::$FAILED) {
    $this->savedPos = $p2;
    $r1 = $this->a14($r4, $r5, $r6);
  }
  // free $p3
  $this->cache[$bucket][$key] = new GrammarCacheEntry(
    $this->currPos,
    $r1,
    $saved_preproc !== $param_preproc ? $param_preproc : self::$UNDEFINED,
    $saved_th !== $param_th ? $param_th : self::$UNDEFINED
  );
  return $r1;
}
private function parsenested_block($silence, $boolParams, &$param_preproc, &$param_th) {
  $key = json_encode([290, $boolParams & 0x3fff, $param_preproc, $param_th]);
  $bucket = $this->currPos;
  $cached = $this->cache[$bucket][$key] ?? null;
  if ($cached) {
    $this->currPos = $cached->nextPos;
    if ($cached->preproc !== self::$UNDEFINED) { $param_preproc = $cached->preproc; }
    if ($cached->th !== self::$UNDEFINED) { $param_th = $cached->th; }
    return $cached->result;
  }
  $saved_preproc=$param_preproc;
  $saved_th=$param_th;
  $p2 = $this->currPos;
  // start seq_1
  $p3 = $this->currPos;
  $p4 = $this->currPos;
  $r5 = $this->discardinline_breaks(true, $boolParams, $param_preproc, $param_th);
  if ($r5 === self::$FAILED) {
    $r5 = false;
  } else {
    $r5 = self::$FAILED;
    $this->currPos = $p4;
    $r1 = self::$FAILED;
    goto seq_1;
  }
  // free $p4
  $r6 = $this->parseblock($silence, $boolParams, $param_th, $param_preproc);
  // b <- $r6
  if ($r6===self::$FAILED) {
    $this->currPos = $p3;
    $r1 = self::$FAILED;
    goto seq_1;
  }
  $r1 = true;
  seq_1:
  if ($r1!==self::$FAILED) {
    $this->savedPos = $p2;
    $r1 = $this->a15($r6);
  }
  // free $p3
  $this->cache[$bucket][$key] = new GrammarCacheEntry(
    $this->currPos,
    $r1,
    $saved_preproc !== $param_preproc ? $param_preproc : self::$UNDEFINED,
    $saved_th !== $param_th ? $param_th : self::$UNDEFINED
  );
  return $r1;
}
private function discardinclude_limits($silence, $boolParams, &$param_preproc, &$param_th) {
  $key = json_encode([485, $boolParams & 0x3ebd, $param_preproc, $param_th]);
  $bucket = $this->currPos;
  $cached = $this->cache[$bucket][$key] ?? null;
  if ($cached) {
    $this->currPos = $cached->nextPos;
    if ($cached->preproc !== self::$UNDEFINED) { $param_preproc = $cached->preproc; }
    if ($cached->th !== self::$UNDEFINED) { $param_th = $cached->th; }
    return $cached->result;
  }
  $saved_preproc=$param_preproc;
  $saved_th=$param_th;
  $p2 = $this->currPos;
  // start seq_1
  $p3 = $this->currPos;
  $p4 = $this->currPos;
  // start seq_2
  $p6 = $this->currPos;
  if (($this->input[$this->currPos] ?? null) === "<") {
    $this->currPos++;
    $r7 = "<";
  } else {
    $r7 = self::$FAILED;
    $r5 = self::$FAILED;
    goto seq_2;
  }
  if (($this->input[$this->currPos] ?? null) === "/") {
    $this->currPos++;
    $r8 = "/";
  } else {
    $r8 = self::$FAILED;
    $r8 = null;
  }
  // start choice_1
  if ($this->currPos >= $this->inputLength ? false : substr_compare($this->input, "includeonly", $this->currPos, 11, true) === 0) {
    $r9 = substr($this->input, $this->currPos, 11);
    $this->currPos += 11;
    goto choice_1;
  } else {
    $r9 = self::$FAILED;
  }
  if ($this->currPos >= $this->inputLength ? false : substr_compare($this->input, "noinclude", $this->currPos, 9, true) === 0) {
    $r9 = substr($this->input, $this->currPos, 9);
    $this->currPos += 9;
    goto choice_1;
  } else {
    $r9 = self::$FAILED;
  }
  if ($this->currPos >= $this->inputLength ? false : substr_compare($this->input, "onlyinclude", $this->currPos, 11, true) === 0) {
    $r9 = substr($this->input, $this->currPos, 11);
    $this->currPos += 11;
  } else {
    $r9 = self::$FAILED;
  }
  choice_1:
  if ($r9===self::$FAILED) {
    $this->currPos = $p6;
    $r5 = self::$FAILED;
    goto seq_2;
  }
  $r5 = true;
  seq_2:
  if ($r5!==self::$FAILED) {
    $r5 = false;
    $this->currPos = $p4;
  } else {
    $r1 = self::$FAILED;
    goto seq_1;
  }
  // free $p6
  // free $p4
  if (($this->input[$this->currPos] ?? null) === "<") {
    $this->currPos++;
    $r10 = "<";
  } else {
    if (!$silence) {$this->fail(44);}
    $r10 = self::$FAILED;
    $this->currPos = $p3;
    $r1 = self::$FAILED;
    goto seq_1;
  }
  $r11 = $this->parsexmlish_tag($silence, $boolParams | 0x2, $param_preproc, $param_th);
  // il <- $r11
  if ($r11===self::$FAILED) {
    $this->currPos = $p3;
    $r1 = self::$FAILED;
    goto seq_1;
  }
  $this->savedPos = $this->currPos;
  $r12 = $this->a87($r11, /*sol_il*/($boolParams & 0x2000) !== 0);
  if ($r12) {
    $r12 = false;
  } else {
    $r12 = self::$FAILED;
    $this->currPos = $p3;
    $r1 = self::$FAILED;
    goto seq_1;
  }
  $r1 = true;
  seq_1:
  if ($r1!==self::$FAILED) {
    $this->savedPos = $p2;
    $r1 = $this->a88($r11, /*sol_il*/($boolParams & 0x2000) !== 0);
  }
  // free $p3
  $this->cache[$bucket][$key] = new GrammarCacheEntry(
    $this->currPos,
    $r1,
    $saved_preproc !== $param_preproc ? $param_preproc : self::$UNDEFINED,
    $saved_th !== $param_th ? $param_th : self::$UNDEFINED
  );
  return $r1;
}
private function discardannotation_tag($silence, $boolParams, &$param_preproc, &$param_th) {
  $key = json_encode([405, $boolParams & 0x3ebf, $param_preproc, $param_th]);
  $bucket = $this->currPos;
  $cached = $this->cache[$bucket][$key] ?? null;
  if ($cached) {
    $this->currPos = $cached->nextPos;
    if ($cached->preproc !== self::$UNDEFINED) { $param_preproc = $cached->preproc; }
    if ($cached->th !== self::$UNDEFINED) { $param_th = $cached->th; }
    return $cached->result;
  }
  $saved_preproc=$param_preproc;
  $saved_th=$param_th;
  $p2 = $this->currPos;
  // start seq_1
  $p3 = $this->currPos;
  $r4 = $this->parseextension_annotation_tag($silence, $boolParams, $param_preproc, $param_th);
  // annToken <- $r4
  if ($r4===self::$FAILED) {
    $r1 = self::$FAILED;
    goto seq_1;
  }
  $this->savedPos = $this->currPos;
  $r5 = $this->a89($r4);
  if ($r5) {
    $r5 = false;
  } else {
    $r5 = self::$FAILED;
    $this->currPos = $p3;
    $r1 = self::$FAILED;
    goto seq_1;
  }
  $r1 = true;
  seq_1:
  if ($r1!==self::$FAILED) {
    $this->savedPos = $p2;
    $r1 = $this->a90($r4);
  }
  // free $p3
  $this->cache[$bucket][$key] = new GrammarCacheEntry(
    $this->currPos,
    $r1,
    $saved_preproc !== $param_preproc ? $param_preproc : self::$UNDEFINED,
    $saved_th !== $param_th ? $param_th : self::$UNDEFINED
  );
  return $r1;
}
private function discardbehavior_switch($silence) {
  $key = 313;
  $bucket = $this->currPos;
  $cached = $this->cache[$bucket][$key] ?? null;
  if ($cached) {
    $this->currPos = $cached->nextPos;

    return $cached->result;
  }

  $p2 = $this->currPos;
  $p4 = $this->currPos;
  // start seq_1
  $p5 = $this->currPos;
  if ($this->currPos >= $this->inputLength ? false : substr_compare($this->input, "__", $this->currPos, 2, false) === 0) {
    $r6 = "__";
    $this->currPos += 2;
  } else {
    if (!$silence) {$this->fail(53);}
    $r6 = self::$FAILED;
    $r3 = self::$FAILED;
    goto seq_1;
  }
  $r7 = $this->discardbehavior_text($silence);
  if ($r7===self::$FAILED) {
    $this->currPos = $p5;
    $r3 = self::$FAILED;
    goto seq_1;
  }
  if ($this->currPos >= $this->inputLength ? false : substr_compare($this->input, "__", $this->currPos, 2, false) === 0) {
    $r8 = "__";
    $this->currPos += 2;
  } else {
    if (!$silence) {$this->fail(53);}
    $r8 = self::$FAILED;
    $this->currPos = $p5;
    $r3 = self::$FAILED;
    goto seq_1;
  }
  $r3 = true;
  seq_1:
  // bs <- $r3
  if ($r3!==self::$FAILED) {
    $r3 = substr($this->input, $p4, $this->currPos - $p4);
  } else {
    $r3 = self::$FAILED;
  }
  // free $p5
  // free $p4
  $r1 = $r3;
  if ($r1!==self::$FAILED) {
    $this->savedPos = $p2;
    $r1 = $this->a79($r3);
  }
  $this->cache[$bucket][$key] = new GrammarCacheEntry(
    $this->currPos,
    $r1,
    self::$UNDEFINED,
    self::$UNDEFINED
  );
  return $r1;
}
private function parselink_text_parameterized($silence, $boolParams, &$param_preproc, &$param_th) {
  $key = json_encode([398, $boolParams & 0x3fff, $param_preproc, $param_th]);
  $bucket = $this->currPos;
  $cached = $this->cache[$bucket][$key] ?? null;
  if ($cached) {
    $this->currPos = $cached->nextPos;
    if ($cached->preproc !== self::$UNDEFINED) { $param_preproc = $cached->preproc; }
    if ($cached->th !== self::$UNDEFINED) { $param_th = $cached->th; }
    return $cached->result;
  }
  $saved_preproc=$param_preproc;
  $saved_th=$param_th;
  $p2 = $this->currPos;
  $r3 = [];
  for (;;) {
    // start choice_1
    // start seq_1
    $p5 = $this->currPos;
    $r6 = $this->parsesol($silence, $boolParams, $param_preproc, $param_th);
    if ($r6===self::$FAILED) {
      $r4 = self::$FAILED;
      goto seq_1;
    }
    // start choice_2
    $r7 = $this->parseheading($silence, $boolParams, $param_preproc, $param_th);
    if ($r7!==self::$FAILED) {
      goto choice_2;
    }
    $r7 = $this->parsehr($silence, $boolParams, $param_preproc, $param_th);
    if ($r7!==self::$FAILED) {
      goto choice_2;
    }
    $r7 = $this->parsefull_table_in_link_caption($silence, $boolParams, $param_preproc, $param_th);
    choice_2:
    if ($r7===self::$FAILED) {
      $this->currPos = $p5;
      $r4 = self::$FAILED;
      goto seq_1;
    }
    $r4 = [$r6,$r7];
    seq_1:
    if ($r4!==self::$FAILED) {
      goto choice_1;
    }
    // free $p5
    $r4 = $this->parseurltext($silence, $boolParams, $param_preproc, $param_th);
    if ($r4!==self::$FAILED) {
      goto choice_1;
    }
    $p5 = $this->currPos;
    // start seq_2
    $p8 = $this->currPos;
    $p9 = $this->currPos;
    $r10 = $this->discardinline_breaks(true, $boolParams, $param_preproc, $param_th);
    if ($r10 === self::$FAILED) {
      $r10 = false;
    } else {
      $r10 = self::$FAILED;
      $this->currPos = $p9;
      $r4 = self::$FAILED;
      goto seq_2;
    }
    // free $p9
    // start choice_3
    $r11 = $this->parseinline_element($silence, $boolParams, $param_preproc, $param_th);
    if ($r11!==self::$FAILED) {
      goto choice_3;
    }
    // start seq_3
    $p9 = $this->currPos;
    if (($this->input[$this->currPos] ?? null) === "[") {
      $this->currPos++;
      $r12 = "[";
    } else {
      if (!$silence) {$this->fail(19);}
      $r12 = self::$FAILED;
      $r11 = self::$FAILED;
      goto seq_3;
    }
    $r13 = [];
    for (;;) {
      $r14 = $this->parsetext_char($silence);
      if ($r14!==self::$FAILED) {
        $r13[] = $r14;
      } else {
        break;
      }
    }
    if (count($r13) === 0) {
      $r13 = self::$FAILED;
    }
    if ($r13===self::$FAILED) {
      $this->currPos = $p9;
      $r11 = self::$FAILED;
      goto seq_3;
    }
    // free $r14
    if (($this->input[$this->currPos] ?? null) === "]") {
      $this->currPos++;
      $r14 = "]";
    } else {
      if (!$silence) {$this->fail(21);}
      $r14 = self::$FAILED;
      $this->currPos = $p9;
      $r11 = self::$FAILED;
      goto seq_3;
    }
    $p15 = $this->currPos;
    $p17 = $this->currPos;
    // start choice_4
    $p18 = $this->currPos;
    if (($this->input[$this->currPos] ?? null) === "]") {
      $this->currPos++;
      $r16 = "]";
    } else {
      $r16 = self::$FAILED;
    }
    if ($r16 === self::$FAILED) {
      $r16 = false;
      goto choice_4;
    } else {
      $r16 = self::$FAILED;
      $this->currPos = $p18;
    }
    // free $p18
    if ($this->currPos >= $this->inputLength ? false : substr_compare($this->input, "]]", $this->currPos, 2, false) === 0) {
      $r16 = "]]";
      $this->currPos += 2;
    } else {
      $r16 = self::$FAILED;
    }
    choice_4:
    if ($r16!==self::$FAILED) {
      $r16 = false;
      $this->currPos = $p17;
      $r16 = substr($this->input, $p15, $this->currPos - $p15);
    } else {
      $r16 = self::$FAILED;
      $this->currPos = $p9;
      $r11 = self::$FAILED;
      goto seq_3;
    }
    // free $p17
    // free $p15
    $r11 = [$r12,$r13,$r14,$r16];
    seq_3:
    if ($r11!==self::$FAILED) {
      goto choice_3;
    }
    // free $p9
    if ($this->currPos < $this->inputLength) {
      $r11 = self::consumeChar($this->input, $this->currPos);;
    } else {
      $r11 = self::$FAILED;
      if (!$silence) {$this->fail(7);}
    }
    choice_3:
    // r <- $r11
    if ($r11===self::$FAILED) {
      $this->currPos = $p8;
      $r4 = self::$FAILED;
      goto seq_2;
    }
    $r4 = true;
    seq_2:
    if ($r4!==self::$FAILED) {
      $this->savedPos = $p5;
      $r4 = $this->a24($r11);
    }
    // free $p8
    choice_1:
    if ($r4!==self::$FAILED) {
      $r3[] = $r4;
    } else {
      break;
    }
  }
  if (count($r3) === 0) {
    $r3 = self::$FAILED;
  }
  // c <- $r3
  // free $r4
  $r1 = $r3;
  if ($r1!==self::$FAILED) {
    $this->savedPos = $p2;
    $r1 = $this->a43($r3);
  }
  $this->cache[$bucket][$key] = new GrammarCacheEntry(
    $this->currPos,
    $r1,
    $saved_preproc !== $param_preproc ? $param_preproc : self::$UNDEFINED,
    $saved_th !== $param_th ? $param_th : self::$UNDEFINED
  );
  return $r1;
}
private function parselang_variant_flags($silence, $boolParams, &$param_preproc, &$param_th) {
  $key = json_encode([368, $boolParams & 0x3ebf, $param_preproc, $param_th]);
  $bucket = $this->currPos;
  $cached = $this->cache[$bucket][$key] ?? null;
  if ($cached) {
    $this->currPos = $cached->nextPos;
    if ($cached->preproc !== self::$UNDEFINED) { $param_preproc = $cached->preproc; }
    if ($cached->th !== self::$UNDEFINED) { $param_th = $cached->th; }
    return $cached->result;
  }
  $saved_preproc=$param_preproc;
  $saved_th=$param_th;
  // start choice_1
  $p2 = $this->currPos;
  // start seq_1
  $p3 = $this->currPos;
  $p5 = $this->currPos;
  for (;;) {
    $r6 = $this->discardspace_or_newline($silence);
    if ($r6===self::$FAILED) {
      break;
    }
  }
  // free $r6
  $r4 = true;
  // sp1 <- $r4
  if ($r4!==self::$FAILED) {
    $r4 = substr($this->input, $p5, $this->currPos - $p5);
  } else {
    $r4 = self::$FAILED;
    $r1 = self::$FAILED;
    goto seq_1;
  }
  // free $p5
  $r6 = $this->parselang_variant_flag($silence, $boolParams, $param_preproc, $param_th);
  // f <- $r6
  if ($r6===self::$FAILED) {
    $this->currPos = $p3;
    $r1 = self::$FAILED;
    goto seq_1;
  }
  $p5 = $this->currPos;
  for (;;) {
    $r8 = $this->discardspace_or_newline($silence);
    if ($r8===self::$FAILED) {
      break;
    }
  }
  // free $r8
  $r7 = true;
  // sp2 <- $r7
  if ($r7!==self::$FAILED) {
    $r7 = substr($this->input, $p5, $this->currPos - $p5);
  } else {
    $r7 = self::$FAILED;
    $this->currPos = $p3;
    $r1 = self::$FAILED;
    goto seq_1;
  }
  // free $p5
  // start seq_2
  $p5 = $this->currPos;
  if (($this->input[$this->currPos] ?? null) === ";") {
    $this->currPos++;
    $r9 = ";";
  } else {
    if (!$silence) {$this->fail(26);}
    $r9 = self::$FAILED;
    $r8 = self::$FAILED;
    goto seq_2;
  }
  $r10 = $this->parselang_variant_flags($silence, $boolParams, $param_preproc, $param_th);
  if ($r10===self::$FAILED) {
    $r10 = null;
  }
  $r8 = [$r9,$r10];
  seq_2:
  if ($r8===self::$FAILED) {
    $r8 = null;
  }
  // free $p5
  // more <- $r8
  $r1 = true;
  seq_1:
  if ($r1!==self::$FAILED) {
    $this->savedPos = $p2;
    $r1 = $this->a172($r4, $r6, $r7, $r8);
    goto choice_1;
  }
  // free $p3
  $p3 = $this->currPos;
  $p5 = $this->currPos;
  for (;;) {
    $r12 = $this->discardspace_or_newline($silence);
    if ($r12===self::$FAILED) {
      break;
    }
  }
  // free $r12
  $r11 = true;
  // sp <- $r11
  if ($r11!==self::$FAILED) {
    $r11 = substr($this->input, $p5, $this->currPos - $p5);
  } else {
    $r11 = self::$FAILED;
  }
  // free $p5
  $r1 = $r11;
  if ($r1!==self::$FAILED) {
    $this->savedPos = $p3;
    $r1 = $this->a173($r11);
  }
  choice_1:
  $this->cache[$bucket][$key] = new GrammarCacheEntry(
    $this->currPos,
    $r1,
    $saved_preproc !== $param_preproc ? $param_preproc : self::$UNDEFINED,
    $saved_th !== $param_th ? $param_th : self::$UNDEFINED
  );
  return $r1;
}
private function parselang_variant_option($silence, $boolParams, &$param_preproc, &$param_th) {
  $key = json_encode([378, $boolParams & 0x3fff, $param_preproc, $param_th]);
  $bucket = $this->currPos;
  $cached = $this->cache[$bucket][$key] ?? null;
  if ($cached) {
    $this->currPos = $cached->nextPos;
    if ($cached->preproc !== self::$UNDEFINED) { $param_preproc = $cached->preproc; }
    if ($cached->th !== self::$UNDEFINED) { $param_th = $cached->th; }
    return $cached->result;
  }
  $saved_preproc=$param_preproc;
  $saved_th=$param_th;
  // start choice_1
  $p2 = $this->currPos;
  // start seq_1
  $p3 = $this->currPos;
  $p5 = $this->currPos;
  for (;;) {
    $r6 = $this->discardspace_or_newline($silence);
    if ($r6===self::$FAILED) {
      break;
    }
  }
  // free $r6
  $r4 = true;
  // sp1 <- $r4
  if ($r4!==self::$FAILED) {
    $r4 = substr($this->input, $p5, $this->currPos - $p5);
  } else {
    $r4 = self::$FAILED;
    $r1 = self::$FAILED;
    goto seq_1;
  }
  // free $p5
  $r6 = $this->parselang_variant_name($silence, $boolParams, $param_preproc, $param_th);
  // lang <- $r6
  if ($r6===self::$FAILED) {
    $this->currPos = $p3;
    $r1 = self::$FAILED;
    goto seq_1;
  }
  $p5 = $this->currPos;
  for (;;) {
    $r8 = $this->discardspace_or_newline($silence);
    if ($r8===self::$FAILED) {
      break;
    }
  }
  // free $r8
  $r7 = true;
  // sp2 <- $r7
  if ($r7!==self::$FAILED) {
    $r7 = substr($this->input, $p5, $this->currPos - $p5);
  } else {
    $r7 = self::$FAILED;
    $this->currPos = $p3;
    $r1 = self::$FAILED;
    goto seq_1;
  }
  // free $p5
  if (($this->input[$this->currPos] ?? null) === ":") {
    $this->currPos++;
    $r8 = ":";
  } else {
    if (!$silence) {$this->fail(18);}
    $r8 = self::$FAILED;
    $this->currPos = $p3;
    $r1 = self::$FAILED;
    goto seq_1;
  }
  $p5 = $this->currPos;
  for (;;) {
    $r10 = $this->discardspace_or_newline($silence);
    if ($r10===self::$FAILED) {
      break;
    }
  }
  // free $r10
  $r9 = true;
  // sp3 <- $r9
  if ($r9!==self::$FAILED) {
    $r9 = substr($this->input, $p5, $this->currPos - $p5);
  } else {
    $r9 = self::$FAILED;
    $this->currPos = $p3;
    $r1 = self::$FAILED;
    goto seq_1;
  }
  // free $p5
  // start choice_2
  $r10 = $this->parselang_variant_nowiki($silence, $boolParams, $param_preproc, $param_th);
  if ($r10!==self::$FAILED) {
    goto choice_2;
  }
  $r10 = $this->parselang_variant_text_no_semi($silence, $boolParams, $param_preproc, $param_th);
  choice_2:
  // lvtext <- $r10
  if ($r10===self::$FAILED) {
    $this->currPos = $p3;
    $r1 = self::$FAILED;
    goto seq_1;
  }
  $r1 = true;
  seq_1:
  if ($r1!==self::$FAILED) {
    $this->savedPos = $p2;
    $r1 = $this->a174($r4, $r6, $r7, $r9, $r10);
    goto choice_1;
  }
  // free $p3
  $p3 = $this->currPos;
  // start seq_2
  $p5 = $this->currPos;
  $p12 = $this->currPos;
  for (;;) {
    $r13 = $this->discardspace_or_newline($silence);
    if ($r13===self::$FAILED) {
      break;
    }
  }
  // free $r13
  $r11 = true;
  // sp1 <- $r11
  if ($r11!==self::$FAILED) {
    $r11 = substr($this->input, $p12, $this->currPos - $p12);
  } else {
    $r11 = self::$FAILED;
    $r1 = self::$FAILED;
    goto seq_2;
  }
  // free $p12
  // start choice_3
  $r13 = $this->parselang_variant_nowiki($silence, $boolParams, $param_preproc, $param_th);
  if ($r13!==self::$FAILED) {
    goto choice_3;
  }
  $r13 = $this->parselang_variant_text_no_semi_or_arrow($silence, $boolParams, $param_preproc, $param_th);
  choice_3:
  // from <- $r13
  if ($r13===self::$FAILED) {
    $this->currPos = $p5;
    $r1 = self::$FAILED;
    goto seq_2;
  }
  if ($this->currPos >= $this->inputLength ? false : substr_compare($this->input, "=>", $this->currPos, 2, false) === 0) {
    $r14 = "=>";
    $this->currPos += 2;
  } else {
    if (!$silence) {$this->fail(81);}
    $r14 = self::$FAILED;
    $this->currPos = $p5;
    $r1 = self::$FAILED;
    goto seq_2;
  }
  $p12 = $this->currPos;
  for (;;) {
    $r16 = $this->discardspace_or_newline($silence);
    if ($r16===self::$FAILED) {
      break;
    }
  }
  // free $r16
  $r15 = true;
  // sp2 <- $r15
  if ($r15!==self::$FAILED) {
    $r15 = substr($this->input, $p12, $this->currPos - $p12);
  } else {
    $r15 = self::$FAILED;
    $this->currPos = $p5;
    $r1 = self::$FAILED;
    goto seq_2;
  }
  // free $p12
  $r16 = $this->parselang_variant_name($silence, $boolParams, $param_preproc, $param_th);
  // lang <- $r16
  if ($r16===self::$FAILED) {
    $this->currPos = $p5;
    $r1 = self::$FAILED;
    goto seq_2;
  }
  $p12 = $this->currPos;
  for (;;) {
    $r18 = $this->discardspace_or_newline($silence);
    if ($r18===self::$FAILED) {
      break;
    }
  }
  // free $r18
  $r17 = true;
  // sp3 <- $r17
  if ($r17!==self::$FAILED) {
    $r17 = substr($this->input, $p12, $this->currPos - $p12);
  } else {
    $r17 = self::$FAILED;
    $this->currPos = $p5;
    $r1 = self::$FAILED;
    goto seq_2;
  }
  // free $p12
  if (($this->input[$this->currPos] ?? null) === ":") {
    $this->currPos++;
    $r18 = ":";
  } else {
    if (!$silence) {$this->fail(18);}
    $r18 = self::$FAILED;
    $this->currPos = $p5;
    $r1 = self::$FAILED;
    goto seq_2;
  }
  $p12 = $this->currPos;
  for (;;) {
    $r20 = $this->discardspace_or_newline($silence);
    if ($r20===self::$FAILED) {
      break;
    }
  }
  // free $r20
  $r19 = true;
  // sp4 <- $r19
  if ($r19!==self::$FAILED) {
    $r19 = substr($this->input, $p12, $this->currPos - $p12);
  } else {
    $r19 = self::$FAILED;
    $this->currPos = $p5;
    $r1 = self::$FAILED;
    goto seq_2;
  }
  // free $p12
  // start choice_4
  $r20 = $this->parselang_variant_nowiki($silence, $boolParams, $param_preproc, $param_th);
  if ($r20!==self::$FAILED) {
    goto choice_4;
  }
  $r20 = $this->parselang_variant_text_no_semi($silence, $boolParams, $param_preproc, $param_th);
  choice_4:
  // to <- $r20
  if ($r20===self::$FAILED) {
    $this->currPos = $p5;
    $r1 = self::$FAILED;
    goto seq_2;
  }
  $r1 = true;
  seq_2:
  if ($r1!==self::$FAILED) {
    $this->savedPos = $p3;
    $r1 = $this->a175($r11, $r13, $r15, $r16, $r17, $r19, $r20);
  }
  // free $p5
  choice_1:
  $this->cache[$bucket][$key] = new GrammarCacheEntry(
    $this->currPos,
    $r1,
    $saved_preproc !== $param_preproc ? $param_preproc : self::$UNDEFINED,
    $saved_th !== $param_th ? $param_th : self::$UNDEFINED
  );
  return $r1;
}
private function discardbogus_lang_variant_option($silence, $boolParams, &$param_preproc, &$param_th) {
  $key = json_encode([377, $boolParams & 0x3fff, $param_preproc, $param_th]);
  $bucket = $this->currPos;
  $cached = $this->cache[$bucket][$key] ?? null;
  if ($cached) {
    $this->currPos = $cached->nextPos;
    if ($cached->preproc !== self::$UNDEFINED) { $param_preproc = $cached->preproc; }
    if ($cached->th !== self::$UNDEFINED) { $param_th = $cached->th; }
    return $cached->result;
  }
  $saved_preproc=$param_preproc;
  $saved_th=$param_th;
  $r1 = $this->discardlang_variant_text($silence, $boolParams, $param_preproc, $param_th);
  if ($r1===self::$FAILED) {
    $r1 = null;
  }
  $this->cache[$bucket][$key] = new GrammarCacheEntry(
    $this->currPos,
    $r1,
    $saved_preproc !== $param_preproc ? $param_preproc : self::$UNDEFINED,
    $saved_th !== $param_th ? $param_th : self::$UNDEFINED
  );
  return $r1;
}
private function parsefull_table_in_link_caption($silence, $boolParams, &$param_preproc, &$param_th) {
  $key = json_encode([446, $boolParams & 0x3ffe, $param_preproc, $param_th]);
  $bucket = $this->currPos;
  $cached = $this->cache[$bucket][$key] ?? null;
  if ($cached) {
    $this->currPos = $cached->nextPos;
    if ($cached->preproc !== self::$UNDEFINED) { $param_preproc = $cached->preproc; }
    if ($cached->th !== self::$UNDEFINED) { $param_th = $cached->th; }
    return $cached->result;
  }
  $saved_preproc=$param_preproc;
  $saved_th=$param_th;
  $p2 = $this->currPos;
  // start seq_1
  $p3 = $this->currPos;
  $p4 = $this->currPos;
  $r5 = $this->discardinline_breaks(true, $boolParams, $param_preproc, $param_th);
  if ($r5 === self::$FAILED) {
    $r5 = false;
  } else {
    $r5 = self::$FAILED;
    $this->currPos = $p4;
    $r1 = self::$FAILED;
    goto seq_1;
  }
  // free $p4
  $r6 = $this->parsefull_table_in_link_caption_parameterized($silence, ($boolParams & ~0x801) | 0x40, $param_preproc, $param_th);
  // r <- $r6
  if ($r6===self::$FAILED) {
    $this->currPos = $p3;
    $r1 = self::$FAILED;
    goto seq_1;
  }
  $r1 = true;
  seq_1:
  if ($r1!==self::$FAILED) {
    $this->savedPos = $p2;
    $r1 = $this->a176($r6);
  }
  // free $p3
  $this->cache[$bucket][$key] = new GrammarCacheEntry(
    $this->currPos,
    $r1,
    $saved_preproc !== $param_preproc ? $param_preproc : self::$UNDEFINED,
    $saved_th !== $param_th ? $param_th : self::$UNDEFINED
  );
  return $r1;
}
private function parselang_variant_flag($silence, $boolParams, &$param_preproc, &$param_th) {
  $key = json_encode([370, $boolParams & 0x3ebf, $param_preproc, $param_th]);
  $bucket = $this->currPos;
  $cached = $this->cache[$bucket][$key] ?? null;
  if ($cached) {
    $this->currPos = $cached->nextPos;
    if ($cached->preproc !== self::$UNDEFINED) { $param_preproc = $cached->preproc; }
    if ($cached->th !== self::$UNDEFINED) { $param_th = $cached->th; }
    return $cached->result;
  }
  $saved_preproc=$param_preproc;
  $saved_th=$param_th;
  // start choice_1
  $p2 = $this->currPos;
  $r3 = $this->input[$this->currPos] ?? '';
  // f <- $r3
  if (preg_match("/^[\\-+A-Z]/", $r3)) {
    $this->currPos++;
  } else {
    $r3 = self::$FAILED;
    if (!$silence) {$this->fail(82);}
  }
  $r1 = $r3;
  if ($r1!==self::$FAILED) {
    $this->savedPos = $p2;
    $r1 = $this->a177($r3);
    goto choice_1;
  }
  $p4 = $this->currPos;
  $r5 = $this->parselang_variant_name($silence, $boolParams, $param_preproc, $param_th);
  // v <- $r5
  $r1 = $r5;
  if ($r1!==self::$FAILED) {
    $this->savedPos = $p4;
    $r1 = $this->a178($r5);
    goto choice_1;
  }
  $p6 = $this->currPos;
  $p8 = $this->currPos;
  $r7 = self::$FAILED;
  for (;;) {
    // start seq_1
    $p10 = $this->currPos;
    $p11 = $this->currPos;
    $r12 = $this->discardspace_or_newline(true);
    if ($r12 === self::$FAILED) {
      $r12 = false;
    } else {
      $r12 = self::$FAILED;
      $this->currPos = $p11;
      $r9 = self::$FAILED;
      goto seq_1;
    }
    // free $p11
    $p11 = $this->currPos;
    $r13 = $this->discardnowiki(true, $boolParams, $param_preproc, $param_th);
    if ($r13 === self::$FAILED) {
      $r13 = false;
    } else {
      $r13 = self::$FAILED;
      $this->currPos = $p11;
      $this->currPos = $p10;
      $r9 = self::$FAILED;
      goto seq_1;
    }
    // free $p11
    if (strcspn($this->input, "{}|;", $this->currPos, 1) !== 0) {
      $r14 = self::consumeChar($this->input, $this->currPos);
    } else {
      $r14 = self::$FAILED;
      if (!$silence) {$this->fail(83);}
      $this->currPos = $p10;
      $r9 = self::$FAILED;
      goto seq_1;
    }
    $r9 = true;
    seq_1:
    if ($r9!==self::$FAILED) {
      $r7 = true;
    } else {
      break;
    }
    // free $p10
  }
  // b <- $r7
  if ($r7!==self::$FAILED) {
    $r7 = substr($this->input, $p8, $this->currPos - $p8);
  } else {
    $r7 = self::$FAILED;
  }
  // free $r9
  // free $p8
  $r1 = $r7;
  if ($r1!==self::$FAILED) {
    $this->savedPos = $p6;
    $r1 = $this->a179($r7);
  }
  choice_1:
  $this->cache[$bucket][$key] = new GrammarCacheEntry(
    $this->currPos,
    $r1,
    $saved_preproc !== $param_preproc ? $param_preproc : self::$UNDEFINED,
    $saved_th !== $param_th ? $param_th : self::$UNDEFINED
  );
  return $r1;
}
private function parselang_variant_name($silence, $boolParams, &$param_preproc, &$param_th) {
  $key = json_encode([372, $boolParams & 0x3ebf, $param_preproc, $param_th]);
  $bucket = $this->currPos;
  $cached = $this->cache[$bucket][$key] ?? null;
  if ($cached) {
    $this->currPos = $cached->nextPos;
    if ($cached->preproc !== self::$UNDEFINED) { $param_preproc = $cached->preproc; }
    if ($cached->th !== self::$UNDEFINED) { $param_th = $cached->th; }
    return $cached->result;
  }
  $saved_preproc=$param_preproc;
  $saved_th=$param_th;
  // start choice_1
  $p2 = $this->currPos;
  // start seq_1
  $p3 = $this->currPos;
  $r4 = $this->input[$this->currPos] ?? '';
  if (preg_match("/^[a-z]/", $r4)) {
    $this->currPos++;
  } else {
    $r4 = self::$FAILED;
    if (!$silence) {$this->fail(84);}
    $r1 = self::$FAILED;
    goto seq_1;
  }
  $r5 = self::$FAILED;
  for (;;) {
    $r6 = $this->input[$this->currPos] ?? '';
    if (preg_match("/^[\\-a-zA-Z]/", $r6)) {
      $this->currPos++;
      $r5 = true;
    } else {
      $r6 = self::$FAILED;
      if (!$silence) {$this->fail(85);}
      break;
    }
  }
  if ($r5===self::$FAILED) {
    $this->currPos = $p3;
    $r1 = self::$FAILED;
    goto seq_1;
  }
  // free $r6
  $r1 = true;
  seq_1:
  if ($r1!==self::$FAILED) {
    $r1 = substr($this->input, $p2, $this->currPos - $p2);
    goto choice_1;
  } else {
    $r1 = self::$FAILED;
  }
  // free $p3
  // free $p2
  $r1 = $this->parsenowiki_text($silence, $boolParams, $param_preproc, $param_th);
  choice_1:
  $this->cache[$bucket][$key] = new GrammarCacheEntry(
    $this->currPos,
    $r1,
    $saved_preproc !== $param_preproc ? $param_preproc : self::$UNDEFINED,
    $saved_th !== $param_th ? $param_th : self::$UNDEFINED
  );
  return $r1;
}
private function parselang_variant_nowiki($silence, $boolParams, &$param_preproc, &$param_th) {
  $key = json_encode([380, $boolParams & 0x3ebf, $param_preproc, $param_th]);
  $bucket = $this->currPos;
  $cached = $this->cache[$bucket][$key] ?? null;
  if ($cached) {
    $this->currPos = $cached->nextPos;
    if ($cached->preproc !== self::$UNDEFINED) { $param_preproc = $cached->preproc; }
    if ($cached->th !== self::$UNDEFINED) { $param_th = $cached->th; }
    return $cached->result;
  }
  $saved_preproc=$param_preproc;
  $saved_th=$param_th;
  $p2 = $this->currPos;
  // start seq_1
  $p3 = $this->currPos;
  $r4 = $this->parsenowiki_text($silence, $boolParams, $param_preproc, $param_th);
  // n <- $r4
  if ($r4===self::$FAILED) {
    $r1 = self::$FAILED;
    goto seq_1;
  }
  $p6 = $this->currPos;
  for (;;) {
    $r7 = $this->discardspace_or_newline($silence);
    if ($r7===self::$FAILED) {
      break;
    }
  }
  // free $r7
  $r5 = true;
  // sp <- $r5
  if ($r5!==self::$FAILED) {
    $r5 = substr($this->input, $p6, $this->currPos - $p6);
  } else {
    $r5 = self::$FAILED;
    $this->currPos = $p3;
    $r1 = self::$FAILED;
    goto seq_1;
  }
  // free $p6
  $r1 = true;
  seq_1:
  if ($r1!==self::$FAILED) {
    $this->savedPos = $p2;
    $r1 = $this->a180($r4, $r5);
  }
  // free $p3
  $this->cache[$bucket][$key] = new GrammarCacheEntry(
    $this->currPos,
    $r1,
    $saved_preproc !== $param_preproc ? $param_preproc : self::$UNDEFINED,
    $saved_th !== $param_th ? $param_th : self::$UNDEFINED
  );
  return $r1;
}
private function parselang_variant_text_no_semi($silence, $boolParams, &$param_preproc, &$param_th) {
  $key = json_encode([384, $boolParams & 0x3dff, $param_preproc, $param_th]);
  $bucket = $this->currPos;
  $cached = $this->cache[$bucket][$key] ?? null;
  if ($cached) {
    $this->currPos = $cached->nextPos;
    if ($cached->preproc !== self::$UNDEFINED) { $param_preproc = $cached->preproc; }
    if ($cached->th !== self::$UNDEFINED) { $param_th = $cached->th; }
    return $cached->result;
  }
  $saved_preproc=$param_preproc;
  $saved_th=$param_th;
  $r1 = $this->parselang_variant_text($silence, $boolParams | 0x200, $param_preproc, $param_th);
  $this->cache[$bucket][$key] = new GrammarCacheEntry(
    $this->currPos,
    $r1,
    $saved_preproc !== $param_preproc ? $param_preproc : self::$UNDEFINED,
    $saved_th !== $param_th ? $param_th : self::$UNDEFINED
  );
  return $r1;
}
private function parselang_variant_text_no_semi_or_arrow($silence, $boolParams, &$param_preproc, &$param_th) {
  $key = json_encode([386, $boolParams & 0x39ff, $param_preproc, $param_th]);
  $bucket = $this->currPos;
  $cached = $this->cache[$bucket][$key] ?? null;
  if ($cached) {
    $this->currPos = $cached->nextPos;
    if ($cached->preproc !== self::$UNDEFINED) { $param_preproc = $cached->preproc; }
    if ($cached->th !== self::$UNDEFINED) { $param_th = $cached->th; }
    return $cached->result;
  }
  $saved_preproc=$param_preproc;
  $saved_th=$param_th;
  $r1 = $this->parselang_variant_text_no_semi($silence, $boolParams | 0x400, $param_preproc, $param_th);
  $this->cache[$bucket][$key] = new GrammarCacheEntry(
    $this->currPos,
    $r1,
    $saved_preproc !== $param_preproc ? $param_preproc : self::$UNDEFINED,
    $saved_th !== $param_th ? $param_th : self::$UNDEFINED
  );
  return $r1;
}
private function discardlang_variant_text($silence, $boolParams, &$param_preproc, &$param_th) {
  $key = json_encode([383, $boolParams & 0x3fff, $param_preproc, $param_th]);
  $bucket = $this->currPos;
  $cached = $this->cache[$bucket][$key] ?? null;
  if ($cached) {
    $this->currPos = $cached->nextPos;
    if ($cached->preproc !== self::$UNDEFINED) { $param_preproc = $cached->preproc; }
    if ($cached->th !== self::$UNDEFINED) { $param_th = $cached->th; }
    return $cached->result;
  }
  $saved_preproc=$param_preproc;
  $saved_th=$param_th;
  $p2 = $this->currPos;
  $r3 = [];
  for (;;) {
    // start choice_1
    $r4 = $this->parseinlineline($silence, $boolParams, $param_preproc, $param_th);
    if ($r4!==self::$FAILED) {
      goto choice_1;
    }
    if (($this->input[$this->currPos] ?? null) === "|") {
      $this->currPos++;
      $r4 = "|";
    } else {
      if (!$silence) {$this->fail(13);}
      $r4 = self::$FAILED;
    }
    choice_1:
    if ($r4!==self::$FAILED) {
      $r3[] = $r4;
    } else {
      break;
    }
  }
  // tokens <- $r3
  // free $r4
  $r1 = $r3;
  if ($r1!==self::$FAILED) {
    $this->savedPos = $p2;
    $r1 = $this->a166($r3);
  }
  $this->cache[$bucket][$key] = new GrammarCacheEntry(
    $this->currPos,
    $r1,
    $saved_preproc !== $param_preproc ? $param_preproc : self::$UNDEFINED,
    $saved_th !== $param_th ? $param_th : self::$UNDEFINED
  );
  return $r1;
}
private function parsefull_table_in_link_caption_parameterized($silence, $boolParams, &$param_preproc, &$param_th) {
  $key = json_encode([448, $boolParams & 0x3fff, $param_preproc, $param_th]);
  $bucket = $this->currPos;
  $cached = $this->cache[$bucket][$key] ?? null;
  if ($cached) {
    $this->currPos = $cached->nextPos;
    if ($cached->preproc !== self::$UNDEFINED) { $param_preproc = $cached->preproc; }
    if ($cached->th !== self::$UNDEFINED) { $param_th = $cached->th; }
    return $cached->result;
  }
  $saved_preproc=$param_preproc;
  $saved_th=$param_th;
  // start seq_1
  $p1 = $this->currPos;
  $r3 = $this->parsetable_start_tag($silence, $boolParams, $param_preproc, $param_th);
  if ($r3===self::$FAILED) {
    $r2 = self::$FAILED;
    goto seq_1;
  }
  $r4 = [];
  for (;;) {
    // start seq_2
    $p6 = $this->currPos;
    $r7 = [];
    for (;;) {
      // start seq_3
      $p9 = $this->currPos;
      $r10 = [];
      for (;;) {
        $r11 = $this->parsesol($silence, $boolParams, $param_preproc, $param_th);
        if ($r11!==self::$FAILED) {
          $r10[] = $r11;
        } else {
          break;
        }
      }
      if (count($r10) === 0) {
        $r10 = self::$FAILED;
      }
      if ($r10===self::$FAILED) {
        $r8 = self::$FAILED;
        goto seq_3;
      }
      // free $r11
      // start choice_1
      $r11 = $this->parsetable_content_line($silence, $boolParams, $param_preproc, $param_th);
      if ($r11!==self::$FAILED) {
        goto choice_1;
      }
      $r11 = $this->parsetplarg_or_template($silence, $boolParams, $param_th, $param_preproc);
      choice_1:
      if ($r11===self::$FAILED) {
        $this->currPos = $p9;
        $r8 = self::$FAILED;
        goto seq_3;
      }
      $r8 = [$r10,$r11];
      seq_3:
      if ($r8!==self::$FAILED) {
        $r7[] = $r8;
      } else {
        break;
      }
      // free $p9
    }
    // free $r8
    $r8 = [];
    for (;;) {
      $r12 = $this->parsesol($silence, $boolParams, $param_preproc, $param_th);
      if ($r12!==self::$FAILED) {
        $r8[] = $r12;
      } else {
        break;
      }
    }
    if (count($r8) === 0) {
      $r8 = self::$FAILED;
    }
    if ($r8===self::$FAILED) {
      $this->currPos = $p6;
      $r5 = self::$FAILED;
      goto seq_2;
    }
    // free $r12
    $r12 = $this->parsetable_end_tag($silence);
    if ($r12===self::$FAILED) {
      $this->currPos = $p6;
      $r5 = self::$FAILED;
      goto seq_2;
    }
    $r5 = [$r7,$r8,$r12];
    seq_2:
    if ($r5!==self::$FAILED) {
      $r4[] = $r5;
    } else {
      break;
    }
    // free $p6
  }
  if (count($r4) === 0) {
    $r4 = self::$FAILED;
  }
  if ($r4===self::$FAILED) {
    $this->currPos = $p1;
    $r2 = self::$FAILED;
    goto seq_1;
  }
  // free $r5
  $r2 = [$r3,$r4];
  seq_1:
  // free $r2,$p1
  $this->cache[$bucket][$key] = new GrammarCacheEntry(
    $this->currPos,
    $r2,
    $saved_preproc !== $param_preproc ? $param_preproc : self::$UNDEFINED,
    $saved_th !== $param_th ? $param_th : self::$UNDEFINED
  );
  return $r2;
}
private function discardnowiki($silence, $boolParams, &$param_preproc, &$param_th) {
  $key = json_encode([409, $boolParams & 0x3ebf, $param_preproc, $param_th]);
  $bucket = $this->currPos;
  $cached = $this->cache[$bucket][$key] ?? null;
  if ($cached) {
    $this->currPos = $cached->nextPos;
    if ($cached->preproc !== self::$UNDEFINED) { $param_preproc = $cached->preproc; }
    if ($cached->th !== self::$UNDEFINED) { $param_th = $cached->th; }
    return $cached->result;
  }
  $saved_preproc=$param_preproc;
  $saved_th=$param_th;
  $p2 = $this->currPos;
  // start seq_1
  $p3 = $this->currPos;
  $p4 = $this->currPos;
  // start seq_2
  $p6 = $this->currPos;
  if (($this->input[$this->currPos] ?? null) === "<") {
    $this->currPos++;
    $r7 = "<";
  } else {
    $r7 = self::$FAILED;
    $r5 = self::$FAILED;
    goto seq_2;
  }
  if (($this->input[$this->currPos] ?? null) === "/") {
    $this->currPos++;
    $r8 = "/";
  } else {
    $r8 = self::$FAILED;
    $r8 = null;
  }
  if ($this->currPos >= $this->inputLength ? false : substr_compare($this->input, "nowiki", $this->currPos, 6, true) === 0) {
    $r9 = substr($this->input, $this->currPos, 6);
    $this->currPos += 6;
  } else {
    $r9 = self::$FAILED;
    $this->currPos = $p6;
    $r5 = self::$FAILED;
    goto seq_2;
  }
  $r5 = true;
  seq_2:
  if ($r5!==self::$FAILED) {
    $r5 = false;
    $this->currPos = $p4;
  } else {
    $r1 = self::$FAILED;
    goto seq_1;
  }
  // free $p6
  // free $p4
  $r10 = $this->parseextension_annotation_tag($silence, $boolParams, $param_preproc, $param_th);
  // extToken <- $r10
  if ($r10===self::$FAILED) {
    $this->currPos = $p3;
    $r1 = self::$FAILED;
    goto seq_1;
  }
  $r1 = true;
  seq_1:
  if ($r1!==self::$FAILED) {
    $this->savedPos = $p2;
    $r1 = $this->a181($r10);
  }
  // free $p3
  $this->cache[$bucket][$key] = new GrammarCacheEntry(
    $this->currPos,
    $r1,
    $saved_preproc !== $param_preproc ? $param_preproc : self::$UNDEFINED,
    $saved_th !== $param_th ? $param_th : self::$UNDEFINED
  );
  return $r1;
}
private function parsenowiki_text($silence, $boolParams, &$param_preproc, &$param_th) {
  $key = json_encode([410, $boolParams & 0x3ebf, $param_preproc, $param_th]);
  $bucket = $this->currPos;
  $cached = $this->cache[$bucket][$key] ?? null;
  if ($cached) {
    $this->currPos = $cached->nextPos;
    if ($cached->preproc !== self::$UNDEFINED) { $param_preproc = $cached->preproc; }
    if ($cached->th !== self::$UNDEFINED) { $param_th = $cached->th; }
    return $cached->result;
  }
  $saved_preproc=$param_preproc;
  $saved_th=$param_th;
  $p2 = $this->currPos;
  $r3 = $this->parsenowiki($silence, $boolParams, $param_preproc, $param_th);
  // extToken <- $r3
  $r1 = $r3;
  if ($r1!==self::$FAILED) {
    $this->savedPos = $p2;
    $r1 = $this->a182($r3);
  }
  $this->cache[$bucket][$key] = new GrammarCacheEntry(
    $this->currPos,
    $r1,
    $saved_preproc !== $param_preproc ? $param_preproc : self::$UNDEFINED,
    $saved_th !== $param_th ? $param_th : self::$UNDEFINED
  );
  return $r1;
}
private function parsenowiki($silence, $boolParams, &$param_preproc, &$param_th) {
  $key = json_encode([408, $boolParams & 0x3ebf, $param_preproc, $param_th]);
  $bucket = $this->currPos;
  $cached = $this->cache[$bucket][$key] ?? null;
  if ($cached) {
    $this->currPos = $cached->nextPos;
    if ($cached->preproc !== self::$UNDEFINED) { $param_preproc = $cached->preproc; }
    if ($cached->th !== self::$UNDEFINED) { $param_th = $cached->th; }
    return $cached->result;
  }
  $saved_preproc=$param_preproc;
  $saved_th=$param_th;
  $p2 = $this->currPos;
  // start seq_1
  $p3 = $this->currPos;
  $p4 = $this->currPos;
  // start seq_2
  $p6 = $this->currPos;
  if (($this->input[$this->currPos] ?? null) === "<") {
    $this->currPos++;
    $r7 = "<";
  } else {
    $r7 = self::$FAILED;
    $r5 = self::$FAILED;
    goto seq_2;
  }
  if (($this->input[$this->currPos] ?? null) === "/") {
    $this->currPos++;
    $r8 = "/";
  } else {
    $r8 = self::$FAILED;
    $r8 = null;
  }
  if ($this->currPos >= $this->inputLength ? false : substr_compare($this->input, "nowiki", $this->currPos, 6, true) === 0) {
    $r9 = substr($this->input, $this->currPos, 6);
    $this->currPos += 6;
  } else {
    $r9 = self::$FAILED;
    $this->currPos = $p6;
    $r5 = self::$FAILED;
    goto seq_2;
  }
  $r5 = true;
  seq_2:
  if ($r5!==self::$FAILED) {
    $r5 = false;
    $this->currPos = $p4;
  } else {
    $r1 = self::$FAILED;
    goto seq_1;
  }
  // free $p6
  // free $p4
  $r10 = $this->parseextension_annotation_tag($silence, $boolParams, $param_preproc, $param_th);
  // extToken <- $r10
  if ($r10===self::$FAILED) {
    $this->currPos = $p3;
    $r1 = self::$FAILED;
    goto seq_1;
  }
  $r1 = true;
  seq_1:
  if ($r1!==self::$FAILED) {
    $this->savedPos = $p2;
    $r1 = $this->a181($r10);
  }
  // free $p3
  $this->cache[$bucket][$key] = new GrammarCacheEntry(
    $this->currPos,
    $r1,
    $saved_preproc !== $param_preproc ? $param_preproc : self::$UNDEFINED,
    $saved_th !== $param_th ? $param_th : self::$UNDEFINED
  );
  return $r1;
}

	public function parse( $input, $options = [] ) {
		$this->initInternal( $input, $options );
		$startRule = $options['startRule'] ?? '(DEFAULT)';
		$result = null;

		if ( !empty( $options['stream'] ) ) {
			switch ( $startRule ) {
				case '(DEFAULT)':
case "start_async":
  return $this->streamstart_async(false, self::newRef(null));
  break;
			default:
				throw new \Wikimedia\WikiPEG\InternalError( "Can't stream rule $startRule." );
			}
		} else {
			switch ( $startRule ) {
				case '(DEFAULT)':
case "start":
  $result = $this->parsestart(false, self::newRef(null));
  break;

case "table_start_tag":
  $result = $this->parsetable_start_tag(false, 0, self::newRef(null), self::newRef(null));
  break;

case "url":
  $result = $this->parseurl(false, self::newRef(null));
  break;

case "row_syntax_table_args":
  $result = $this->parserow_syntax_table_args(false, 0, self::newRef(null), self::newRef(null));
  break;

case "table_attributes":
  $result = $this->parsetable_attributes(false, 0, self::newRef(null), self::newRef(null));
  break;

case "generic_newline_attributes":
  $result = $this->parsegeneric_newline_attributes(false, 0, self::newRef(null), self::newRef(null));
  break;

case "tplarg_or_template_or_bust":
  $result = $this->parsetplarg_or_template_or_bust(false, self::newRef(null));
  break;

case "extlink":
  $result = $this->parseextlink(false, 0, self::newRef(null), self::newRef(null));
  break;

case "list_item":
  $result = $this->parselist_item(false, 0, self::newRef(null), self::newRef(null));
  break;
			default:
				throw new \Wikimedia\WikiPEG\InternalError( "Can't start parsing from rule $startRule." );
			}
		}

		if ( $result !== self::$FAILED && $this->currPos === $this->inputLength ) {
			return $result;
		} else {
			if ( $result !== self::$FAILED && $this->currPos < $this->inputLength ) {
				$this->fail( 0 );
			}
			throw $this->buildParseException();
		}
	}
}
