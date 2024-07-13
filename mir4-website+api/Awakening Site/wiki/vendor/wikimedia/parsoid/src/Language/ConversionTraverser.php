<?php
declare( strict_types = 1 );

namespace Wikimedia\Parsoid\Language;

use Wikimedia\Assert\Assert;
use Wikimedia\Bcp47Code\Bcp47Code;
use Wikimedia\LangConv\ReplacementMachine;
use Wikimedia\Parsoid\Config\Env;
use Wikimedia\Parsoid\DOM\DocumentFragment;
use Wikimedia\Parsoid\DOM\Element;
use Wikimedia\Parsoid\DOM\Node;
use Wikimedia\Parsoid\DOM\Text;
use Wikimedia\Parsoid\Utils\DOMCompat;
use Wikimedia\Parsoid\Utils\DOMDataUtils;
use Wikimedia\Parsoid\Utils\DOMTraverser;
use Wikimedia\Parsoid\Utils\DOMUtils;
use Wikimedia\Parsoid\Utils\Utils;

class ConversionTraverser extends DOMTraverser {

	/** @var Bcp47Code a language code */
	private $toLang;

	/** @var Bcp47Code a language code */
	private $fromLang;

	/** @var LanguageGuesser */
	private $guesser;

	/** @var ReplacementMachine (uses MW-internal codes) */
	private $machine;

	/**
	 * @param Env $env
	 * @param Bcp47Code $toLang target language for conversion
	 * @param LanguageGuesser $guesser oracle to determine "original language" for round-tripping
	 * @param ReplacementMachine $machine machine to do actual conversion
	 */
	public function __construct(
		Env $env, Bcp47Code $toLang, LanguageGuesser $guesser, ReplacementMachine $machine
	) {
		parent::__construct();
		$this->toLang = $toLang;
		$this->guesser = $guesser;
		$this->machine = $machine;

		// No conversion inside <code>, <script>, <pre>, <cite>
		// (See adhoc regexps inside LanguageConverter.php::autoConvert)
		// XXX: <cite> ought to probably be handled more generically
		// as extension output, not special-cased as a HTML tag.
		foreach ( [ 'code', 'script', 'pre', 'cite' ] as $el ) {
			$this->addHandler( $el, function ( Element $el ) {
				return $this->noConvertHandler( $el );
			} );
		}
		// Setting/saving the language context
		$this->addHandler( null, function ( Node $node ) {
			return $this->anyHandler( $node );
		} );
		$this->addHandler( 'p', function ( Element $el ) {
			return $this->langContextHandler( $el );
		} );
		$this->addHandler( 'body', function ( Element $el ) {
			return $this->langContextHandler( $el );
		} );
		// Converting #text, <a> nodes, and title/alt attributes
		$this->addHandler( '#text', function ( Node $node ) {
			return $this->textHandler( $node );
		} );
		$this->addHandler( 'a', function ( Element $el ) use ( $env ){
			return $this->aHandler( $el, $env );
		} );
		$this->addHandler( null, function ( Node $node ) {
			return $this->attrHandler( $node );
		} );
		// LanguageConverter markup
		foreach ( [ 'meta', 'div', 'span' ] as $el ) {
			$this->addHandler( $el, function ( Element $el ) {
				return $this->lcHandler( $el );
			} );
		}
	}

	/**
	 * @param Element $el
	 * @return ?Node|bool
	 */
	private function noConvertHandler( Element $el ) {
		// Don't touch the inside of this node!
		return $el->nextSibling;
	}

	/**
	 * @param Node $node
	 * @return ?Node|bool
	 */
	private function anyHandler( Node $node ) {
		/* Look for `lang` attributes */
		if ( $node instanceof Element ) {
			if ( $node->hasAttribute( 'lang' ) ) {
				$lang = DOMCompat::getAttribute( $node, 'lang' );
				// XXX validate lang! override fromLang?
				// $this->>fromLang = $lang;
			}
		}
		return true; // Continue with other handlers
	}

	/**
	 * @param Element $el
	 * @return ?Node|bool
	 */
	private function langContextHandler( Element $el ) {
		$this->fromLang = $this->guesser->guessLang( $el );
		// T320662: use internal MW language names for now :(
		$fromLangMw = Utils::bcp47ToMwCode( $this->fromLang );
		$el->setAttribute( 'data-mw-variant-lang', $fromLangMw );
		return true; // Continue with other handlers
	}

	/**
	 * @param Node $node
	 * @return ?Node|bool
	 */
	private function textHandler( Node $node ) {
		Assert::invariant( $this->fromLang !== null, 'Text w/o a context' );
		$toLangMw = Utils::bcp47ToMwCode( $this->toLang );
		$fromLangMw = Utils::bcp47ToMwCode( $this->fromLang );
		// @phan-suppress-next-line PhanTypeMismatchArgument,PhanTypeMismatchReturn both declared as DOMNode
		return $this->machine->replace( $node, $toLangMw, $fromLangMw );
	}

	/**
	 * @param Element $el
	 * @param Env $env
	 * @return ?Node|bool
	 */
	private function aHandler( Element $el, Env $env ) {
		// Is this a wikilink?  If so, extract title & convert it
		if ( DOMUtils::hasRel( $el, 'mw:WikiLink' ) ) {
			$href = preg_replace( '#^(\.\.?/)+#', '', DOMCompat::getAttribute( $el, 'href' ) ?? '', 1 );
			$fromPage = Utils::decodeURI( $href );
			$toPageFrag = $this->machine->convert(
				$el->ownerDocument, $fromPage,
				Utils::bcp47ToMwCode( $this->toLang ),
				Utils::bcp47ToMwCode( $this->fromLang )
			);
			'@phan-var DocumentFragment $toPageFrag'; // @var DocumentFragment $toPageFrag
			$toPage = $this->docFragToString( $toPageFrag );
			if ( $toPage === null ) {
				// Non-reversible transform (sigh); mark this for rt.
				$el->setAttribute( 'data-mw-variant-orig', $fromPage );
				$toPage = $this->docFragToString( $toPageFrag, true/* force */ );
			}
			if ( $el->hasAttribute( 'title' ) ) {
				$el->setAttribute( 'title', str_replace( '_', ' ', $toPage ) );
			}
			$el->setAttribute( 'href', "./{$toPage}" );
		} elseif ( DOMUtils::hasRel( $el, 'mw:WikiLink/Interwiki' ) ) {
			// Don't convert title or children of interwiki links
			return $el->nextSibling;
		} elseif ( DOMUtils::hasRel( $el, 'mw:ExtLink' ) ) {
			// WTUtils.usesURLLinkSyntax uses data-parsoid, so don't use it,
			// but syntactic free links should also have class="external free"
			if ( DOMUtils::hasClass( $el, 'free' ) ) {
				// Don't convert children of syntactic "free links"
				return $el->nextSibling;
			}
			// Other external link text is protected from conversion iff
			// (a) it doesn't starts/end with -{ ... }-
			if ( $el->firstChild &&
				DOMUtils::hasTypeOf( $el->firstChild, 'mw:LanguageVariant' ) ) {
				return true;
			}
			// (b) it looks like a URL (protocol-relative links excluded)
			$linkText = $el->textContent; // XXX: this could be expensive
			if ( Utils::isProtocolValid( $linkText, $env )
				 && substr( $linkText, 0, 2 ) !== '//'
			) {
				return $el->nextSibling;
			}
		}
		return true;
	}

	/**
	 * @param Node $node
	 * @return ?Node|bool
	 */
	private function attrHandler( Node $node ) {
		// Convert `alt` and `title` attributes on elements
		// (Called before aHandler, so the `title` might get overwritten there)
		if ( !( $node instanceof Element ) ) {
			return true;
		}
		DOMUtils::assertElt( $node );
		foreach ( [ 'title', 'alt' ] as $attr ) {
			$orig = DOMCompat::getAttribute( $node, $attr );
			if ( $orig === null ) {
				continue;
			}
			if ( $attr === 'title' && DOMUtils::hasRel( $node, 'mw:WikiLink' ) ) {
				// We've already converted the title in aHandler above.
				continue;
			}
			if ( str_contains( $orig, '://' ) ) {
				continue; /* Don't convert URLs */
			}
			$toFrag = $this->machine->convert(
				$node->ownerDocument, $orig,
				Utils::bcp47ToMwCode( $this->toLang ),
				Utils::bcp47ToMwCode( $this->fromLang )
			);
			'@phan-var DocumentFragment $toFrag'; // @var DocumentFragment $toFrag
			$to = $this->docFragToString( $toFrag );
			if ( $to === null ) {
				// Non-reversible transform (sigh); mark for rt.
				$node->setAttribute( "data-mw-variant-{$attr}", $orig );
				$to = $this->docFragToString( $toFrag, true/* force */ );
			}
			$node->setAttribute( $attr, $to );
		}
		return true;
	}

	/**
	 * Handler for LanguageConverter markup
	 *
	 * @param Element $el
	 * @return ?Node|bool
	 */
	private function lcHandler( Element $el ) {
		if ( !DOMUtils::hasTypeOf( $el, 'mw:LanguageVariant' ) ) {
			return true; /* not language converter markup */
		}
		$dmv = DOMDataUtils::getJSONAttribute( $el, 'data-mw-variant', [] );
		if ( isset( $dmv->disabled ) ) {
			DOMCompat::setInnerHTML( $el, $dmv->disabled->t );
			// XXX check handling of embedded data-parsoid
			// XXX check handling of nested constructs
			return $el->nextSibling;
		} elseif ( isset( $dmv->twoway ) ) {
			// FIXME
		} elseif ( isset( $dmv->oneway ) ) {
			// FIXME
		} elseif ( isset( $dmv->name ) ) {
			// FIXME
		} elseif ( isset( $dmv->filter ) ) {
			// FIXME
		} elseif ( isset( $dmv->describe ) ) {
			// FIXME
		}
		return true;
	}

	private function docFragToString(
		DocumentFragment $docFrag, bool $force = false
	): ?string {
		if ( !$force ) {
			for ( $child = $docFrag->firstChild; $child; $child = $child->nextSibling ) {
				if ( !( $child instanceof Text ) ) {
					return null; /* unsafe */
				}
			}
		}
		return $docFrag->textContent;
	}
}
