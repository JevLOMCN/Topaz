<?php
declare( strict_types = 1 );

namespace Wikimedia\Parsoid\Ext\Pre;

use Wikimedia\Parsoid\Core\Sanitizer;
use Wikimedia\Parsoid\DOM\DocumentFragment;
use Wikimedia\Parsoid\Ext\DOMDataUtils;
use Wikimedia\Parsoid\Ext\ExtensionModule;
use Wikimedia\Parsoid\Ext\ExtensionTagHandler;
use Wikimedia\Parsoid\Ext\ParsoidExtensionAPI;
use Wikimedia\Parsoid\Ext\PHPUtils;
use Wikimedia\Parsoid\Ext\Utils;

/**
 * The `<pre>` extension tag shadows the html pre tag, but has different
 * semantics.  It treats anything inside it as plaintext.
 */
class Pre extends ExtensionTagHandler implements ExtensionModule {

	/** @inheritDoc */
	public function getConfig(): array {
		return [
			'name' => '<pre>',
			'tags' => [
				[
					'name' => 'pre',
					'handler' => self::class,
				]
			]
		];
	}

	/** @inheritDoc */
	public function sourceToDom(
		ParsoidExtensionAPI $extApi, string $txt, array $extArgs
	): DocumentFragment {
		$domFragment = $extApi->htmlToDom( '' );
		$doc = $domFragment->ownerDocument;
		$pre = $doc->createElement( 'pre' );

		Sanitizer::applySanitizedArgs( $extApi->getSiteConfig(), $pre, $extArgs );
		DOMDataUtils::getDataParsoid( $pre )->stx = 'html';

		// Support nowikis in pre.  Do this before stripping newlines, see test,
		// "<pre> with <nowiki> inside (compatibility with 1.6 and earlier)"
		$txt = preg_replace( '/<nowiki\s*>(.*?)<\/nowiki\s*>/s', '$1', $txt );

		// Strip leading newline to match legacy php parser.  This is probably because
		// it doesn't do xml serialization accounting for `newlineStrippingElements`
		// Of course, this leads to indistinguishability between n=0 and n=1
		// newlines, but that only seems to affect parserTests output.  Rendering
		// is the same, and the newline is preserved for rt in the `extSrc`.
		$txt = PHPUtils::stripPrefix( $txt, "\n" );

		// `extSrc` will take care of rt'ing these
		$txt = Utils::decodeWtEntities( $txt );

		$pre->appendChild( $doc->createTextNode( $txt ) );
		$domFragment->appendChild( $pre );

		return $domFragment;
	}

}
