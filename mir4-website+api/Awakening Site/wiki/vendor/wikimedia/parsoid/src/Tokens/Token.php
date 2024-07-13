<?php
declare( strict_types = 1 );

namespace Wikimedia\Parsoid\Tokens;

use stdClass;
use Wikimedia\Assert\Assert;
use Wikimedia\Parsoid\Core\DomSourceRange;
use Wikimedia\Parsoid\NodeData\DataParsoid;
use Wikimedia\Parsoid\Utils\PHPUtils;
use Wikimedia\Parsoid\Wt2Html\Frame;

/**
 * Catch-all class for all token types.
 */
abstract class Token implements \JsonSerializable {
	/** @var DataParsoid|null */
	public $dataParsoid;

	/** @var KV[] */
	public $attribs;

	/**
	 * @inheritDoc
	 */
	#[\ReturnTypeWillChange]
	abstract public function jsonSerialize();

	/**
	 * Get a name for the token.
	 * Derived classes can override this.
	 * @return string
	 */
	public function getName(): string {
		return $this->getType();
	}

	/**
	 * Returns a string key for this token
	 * @return string
	 */
	public function getType(): string {
		$classParts = explode( '\\', get_class( $this ) );
		return end( $classParts );
	}

	/**
	 * Generic set attribute method.
	 *
	 * @param string $name
	 *    Always a string when used this way.
	 *    The more complex form (where the key is a non-string) are found when
	 *    KV objects are constructed in the tokenizer.
	 * @param string|Token|array<Token|string> $value
	 * @param ?KVSourceRange $srcOffsets
	 */
	public function addAttribute(
		string $name, $value, ?KVSourceRange $srcOffsets = null
	): void {
		$this->attribs[] = new KV( $name, $value, $srcOffsets );
	}

	/**
	 * Generic set attribute method with support for change detection.
	 * Set a value and preserve the original wikitext that produced it.
	 *
	 * @param string $name
	 * @param string|Token|Token[] $value
	 * @param mixed $origValue
	 */
	public function addNormalizedAttribute( string $name, $value, $origValue ): void {
		$this->addAttribute( $name, $value );
		$this->setShadowInfo( $name, $value, $origValue );
	}

	/**
	 * Generic attribute accessor.
	 *
	 * @param string $name
	 * @return string|Token|Token[]|KV[]|null
	 */
	public function getAttributeV( string $name ) {
		return KV::lookup( $this->attribs, $name );
	}

	/**
	 * Generic attribute accessor.
	 *
	 * @param string $name
	 * @return KV|null
	 */
	public function getAttributeKV( string $name ) {
		return KV::lookupKV( $this->attribs, $name );
	}

	/**
	 * Generic attribute accessor.
	 *
	 * @param string $name
	 * @return bool
	 */
	public function hasAttribute( string $name ): bool {
		return $this->getAttributeKV( $name ) !== null;
	}

	/**
	 * Set an unshadowed attribute.
	 *
	 * @param string $name
	 * @param string|Token|Token[] $value
	 */
	public function setAttribute( string $name, $value ): void {
		// First look for the attribute and change the last match if found.
		for ( $i = count( $this->attribs ) - 1; $i >= 0; $i-- ) {
			$kv = $this->attribs[$i];
			$k = $kv->k;
			if ( is_string( $k ) && mb_strtolower( $k ) === $name ) {
				$kv->v = $value;
				$this->attribs[$i] = $kv;
				return;
			}
		}
		// Nothing found, just add the attribute
		$this->addAttribute( $name, $value );
	}

	/**
	 * Store the original value of an attribute in a token's dataParsoid.
	 *
	 * @param string $name
	 * @param mixed $value
	 * @param mixed $origValue
	 */
	public function setShadowInfo( string $name, $value, $origValue ): void {
		// Don't shadow if value is the same or the orig is null
		if ( $value !== $origValue && $origValue !== null ) {
			$this->dataParsoid->a ??= [];
			$this->dataParsoid->a[$name] = $value;
			$this->dataParsoid->sa ??= [];
			$this->dataParsoid->sa[$name] = $origValue;
		}
	}

	/**
	 * Attribute info accessor for the wikitext serializer. Performs change
	 * detection and uses unnormalized attribute values if set. Expects the
	 * context to be set to a token.
	 *
	 * @param string $name
	 * @return array Information about the shadow info attached to this attribute:
	 *   - value: (Token|Token[]|string)
	 *     When modified is false and fromsrc is true, this is always a string.
	 *   - modified: (bool)
	 *   - fromsrc: (bool)
	 */
	public function getAttributeShadowInfo( string $name ): array {
		$curVal = $this->getAttributeV( $name );

		// Not the case, continue regular round-trip information.
		if ( !property_exists( $this->dataParsoid, 'a' ) ||
			!array_key_exists( $name, $this->dataParsoid->a )
		) {
			return [
				"value" => $curVal,
				// Mark as modified if a new element
				"modified" => $this->dataParsoid->isModified(),
				"fromsrc" => false
			];
		} elseif ( $this->dataParsoid->a[$name] !== $curVal ) {
			return [
				"value" => $curVal,
				"modified" => true,
				"fromsrc" => false
			];
		} elseif ( !property_exists( $this->dataParsoid, 'sa' ) ||
			!array_key_exists( $name, $this->dataParsoid->sa )
		) {
			return [
				"value" => $curVal,
				"modified" => false,
				"fromsrc" => false
			];
		} else {
			return [
				"value" => $this->dataParsoid->sa[$name],
				"modified" => false,
				"fromsrc" => true
			];
		}
	}

	/**
	 * Completely remove all attributes with this name.
	 *
	 * @param string $name
	 */
	public function removeAttribute( string $name ): void {
		foreach ( $this->attribs as $i => $kv ) {
			if ( mb_strtolower( $kv->k ) === $name ) {
				unset( $this->attribs[$i] );
			}
		}
		$this->attribs = array_values( $this->attribs );
	}

	/**
	 * Add a space-separated property value.
	 * These are Parsoid-added attributes, not something present in source.
	 * So, only a regular ASCII space characters will be used here.
	 *
	 * @param string $name The attribute name
	 * @param string $value The value to add to the attribute
	 */
	public function addSpaceSeparatedAttribute( string $name, string $value ): void {
		$curVal = $this->getAttributeKV( $name );
		if ( $curVal !== null ) {
			if ( in_array( $value, explode( ' ', $curVal->v ), true ) ) {
				// value is already included, nothing to do.
				return;
			}

			// Value was not yet included in the existing attribute, just add
			// it separated with a space
			$this->setAttribute( $curVal->k, $curVal->v . ' ' . $value );
		} else {
			// the attribute did not exist at all, just add it
			$this->addAttribute( $name, $value );
		}
	}

	/**
	 * Get the wikitext source of a token.
	 *
	 * @param Frame $frame
	 * @return string
	 */
	public function getWTSource( Frame $frame ): string {
		$tsr = $this->dataParsoid->tsr ?? null;
		if ( !( $tsr instanceof SourceRange ) ) {
			throw new InvalidTokenException( 'Expected token to have tsr info.' );
		}
		$srcText = $frame->getSrcText();
		Assert::invariant( $tsr->end >= $tsr->start, 'Bad TSR' );
		return $tsr->substr( $srcText );
	}

	/**
	 * Create key value set from an array
	 *
	 * @param array $a
	 * @return array
	 */
	private static function kvsFromArray( array $a ): array {
		$kvs = [];
		foreach ( $a as $e ) {
			if ( is_array( $e["k"] ?? null ) ) {
				self::rebuildNestedTokens( $e["k"] );
			}
			$v = $e['v'] ?? null;
			if ( is_array( $v ) ) {
				// $v is either an array of Tokens or an array of KVs
				if ( count( $v ) > 0 ) {
					if ( is_array( $v[0] ) && array_key_exists( 'k', $v[0] ) ) {
						$v = self::kvsFromArray( $v );
					} else {
						self::rebuildNestedTokens( $v );
					}
				}
			}
			$so = $e["srcOffsets"] ?? null;
			if ( $so ) {
				$so = KVSourceRange::fromArray( $so );
			}
			$kvs[] = new KV(
				$e["k"] ?? null,
				$v,
				$so,
				$e["ksrc"] ?? null,
				$e["vsrc"] ?? null
			);
		}
		return $kvs;
	}

	/**
	 * @param iterable|stdClass|DataParsoid &$a
	 */
	private static function rebuildNestedTokens( &$a ): void {
		// objects do not count as iterables in PHP but can be iterated nevertheless
		// @phan-suppress-next-line PhanTypeSuspiciousNonTraversableForeach
		foreach ( $a as &$v ) {
			$v = self::getToken( $v );
		}
		unset( $v ); // Future-proof protection
	}

	/**
	 * Get a token from some PHP structure. Used by the PHPUnit tests.
	 *
	 * @param KV|Token|array|string|int|float|bool|null $input
	 * @return Token|string|int|float|bool|null|array<Token|string|int|float|bool|null>
	 */
	public static function getToken( $input ) {
		if ( !$input ) {
			return $input;
		}

		if ( is_array( $input ) && isset( $input['type'] ) ) {
			if ( isset( $input['dataParsoid'] ) ) {
				$da = new DataParsoid;
				foreach ( $input['dataParsoid'] as $key => $value ) {
					switch ( $key ) {
						case 'tmp':
							$tmp = $da->getTemp();
							foreach ( $value as $key2 => $value2 ) {
								$tmp->$key2 = $value2;
							}
							break;
						case 'dsr':
							// dsr is generally for DOM trees, not Tokens.
							$da->dsr = DomSourceRange::fromArray( $value );
							break;
						case 'tsr':
							$da->tsr = SourceRange::fromArray( $value );
							break;
						case 'extTagOffsets':
							$da->extTagOffsets = DomSourceRange::fromArray( $value );
							break;
						case 'extLinkContentOffsets':
							$da->extLinkContentOffsets =
								SourceRange::fromArray( $value );
							break;
						default:
							$da->$key = $value;
					}
				}
			} else {
				$da = null;
			}
			switch ( $input['type'] ) {
				case "SelfclosingTagTk":
					$token = new SelfclosingTagTk( $input['name'], self::kvsFromArray( $input['attribs'] ), $da );
					break;
				case "TagTk":
					$token = new TagTk( $input['name'], self::kvsFromArray( $input['attribs'] ), $da );
					break;
				case "EndTagTk":
					$token = new EndTagTk( $input['name'], self::kvsFromArray( $input['attribs'] ), $da );
					break;
				case "NlTk":
					$token = new NlTk( $da->tsr ?? null, $da );
					break;
				case "EOFTk":
					$token = new EOFTk();
					break;
				case "CommentTk":
					$token = new CommentTk( $input["value"], $da );
					break;
				default:
					// Looks like data-parsoid can have a 'type' property in some cases
					// We can change that usage and then throw an exception here
					$token = &$input;
			}
		} elseif ( is_array( $input ) ) {
			$token = &$input;
		} else {
			$token = $input;
		}

		if ( is_array( $token ) ) {
			self::rebuildNestedTokens( $token );
		} else {
			if ( !empty( $token->attribs ) ) {
				self::rebuildNestedTokens( $token->attribs );
			}
			if ( !empty( $token->dataParsoid ) ) {
				self::rebuildNestedTokens( $token->dataParsoid );
			}
		}

		return $token;
	}

	public function fetchExpandedAttrValue( string $key ): ?string {
		if ( preg_match(
			'/mw:ExpandedAttrs/', $this->getAttributeV( 'typeof' ) ?? ''
		) ) {
			$attribs = PHPUtils::jsonDecode(
				$this->getAttributeV( 'data-mw' ), false
			)->attribs;
			foreach ( $attribs as $attr ) {
				if ( $attr[0]->txt === $key ) {
					return $attr[1]->html;
				}
			}
		}
		return null;
	}

}
