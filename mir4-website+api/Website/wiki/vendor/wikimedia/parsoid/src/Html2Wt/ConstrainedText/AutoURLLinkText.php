<?php
declare( strict_types = 1 );

namespace Wikimedia\Parsoid\Html2Wt\ConstrainedText;

use Wikimedia\Parsoid\Config\Env;
use Wikimedia\Parsoid\DOM\Element;
use Wikimedia\Parsoid\NodeData\DataParsoid;
use Wikimedia\Parsoid\Utils\DOMCompat;

/**
 * An autolink to an external resource, like `http://example.com`.
 */
class AutoURLLinkText extends RegExpConstrainedText {

	public function __construct( string $url, Element $node ) {
		parent::__construct( [
				'text' => $url,
				'node' => $node,
				// there's a \b boundary at start, and first char of url is a word char
				'badPrefix' => /* RegExp */ '/\w$/uD',
				'badSuffix' => self::badSuffix( $url )
			]
		);
	}

	// This regexp comes from the legacy parser's EXT_LINK_URL_CLASS regexp.
	private const EXT_LINK_URL_CLASS =
		'^\[\]<>"\x00-\x20\x7F\x{00A0}\x{1680}\x{180E}\x{2000}-\x{200A}\x{202F}\x{205F}\x{3000}';
	// This set of trailing punctuation comes from Parser.php::makeFreeExternalLink
	private const TRAILING_PUNCT = ',;\\\.:!?';
	private const NOT_LTGTNBSP = '(?!&(lt|gt|nbsp|#x0*(3[CcEe]|[Aa]0)|#0*(60|62|160));)';
	private const NOT_QQ = "(?!'')";
	// Trailing context for an autourl link
	private const PAREN_AUTOURL_BAD_SUFFIX =
		'/^' . self::NOT_LTGTNBSP . self::NOT_QQ .
		'[' . self::TRAILING_PUNCT . ']*' .
		'[' . self::EXT_LINK_URL_CLASS . self::TRAILING_PUNCT . ']/u';
	// If the URL has an doesn't have an open paren in it, TRAILING PUNCT will
	// include ')' as well.
	private const NOPAREN_AUTOURL_BAD_SUFFIX =
		'/^' . self::NOT_LTGTNBSP . self::NOT_QQ .
		'[' . self::TRAILING_PUNCT . '\)]*' .
		'[' . self::EXT_LINK_URL_CLASS . self::TRAILING_PUNCT . '\)]/u';

	private static function badSuffix( string $url ): string {
		return strpos( $url, '(' ) === false ?
			self::NOPAREN_AUTOURL_BAD_SUFFIX :
			self::PAREN_AUTOURL_BAD_SUFFIX;
	}

	protected static function fromSelSerImpl(
		string $text, Element $node, DataParsoid $dataParsoid,
		Env $env, array $opts
	): ?AutoURLLinkText {
		$stx = $dataParsoid->stx ?? null;
		$type = $dataParsoid->type ?? null;
		if (
			( DOMCompat::nodeName( $node ) === 'a' && $stx === 'url' ) ||
			( DOMCompat::nodeName( $node ) === 'img' && $type === 'extlink' )
		) {
			return new AutoURLLinkText( $text, $node );
		}
		return null;
	}

	/** @inheritDoc */
	public function escape( State $state ): Result {
		// Special case for entities which "leak off the end".
		$r = parent::escape( $state );
		// If the text ends with an incomplete entity, be careful of
		// suffix text which could complete it.
		if ( !$r->suffix &&
			preg_match( '/&[#0-9a-zA-Z]*$/D', $r->text ) &&
			preg_match( '/^[#0-9a-zA-Z]*;/', $state->rightContext )
		) {
			$r->suffix = $this->suffix;
		}
		return $r;
	}
}
