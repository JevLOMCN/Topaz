<?php
declare( strict_types = 1 );

namespace Wikimedia\Parsoid\Utils;

use Composer\Semver\Semver;
use stdClass;
use Wikimedia\Assert\Assert;
use Wikimedia\Assert\UnreachableException;
use Wikimedia\Parsoid\Config\Env;
use Wikimedia\Parsoid\Core\DomSourceRange;
use Wikimedia\Parsoid\Core\PageBundle;
use Wikimedia\Parsoid\DOM\Document;
use Wikimedia\Parsoid\DOM\Element;
use Wikimedia\Parsoid\DOM\Node;
use Wikimedia\Parsoid\NodeData\DataBag;
use Wikimedia\Parsoid\NodeData\DataMw;
use Wikimedia\Parsoid\NodeData\DataMwI18n;
use Wikimedia\Parsoid\NodeData\DataParsoid;
use Wikimedia\Parsoid\NodeData\I18nInfo;
use Wikimedia\Parsoid\NodeData\NodeData;
use Wikimedia\Parsoid\NodeData\ParamInfo;
use Wikimedia\Parsoid\NodeData\TempData;
use Wikimedia\Parsoid\Tokens\SourceRange;

/**
 * These helpers pertain to HTML and data attributes of a node.
 */
class DOMDataUtils {
	public const DATA_OBJECT_ATTR_NAME = 'data-object-id';

	/**
	 * Return the dynamic "bag" property of a Document.
	 * @param Document $doc
	 * @return DataBag
	 */
	public static function getBag( Document $doc ): DataBag {
		// This is a dynamic property; it is not declared.
		// All references go through here so we can suppress phan's complaint.
		// @phan-suppress-next-line PhanUndeclaredProperty
		return $doc->bag;
	}

	public static function prepareDoc( Document $doc ): void {
		// `bag` is a deliberate dynamic property; see DOMDataUtils::getBag()
		// @phan-suppress-next-line PhanUndeclaredProperty dynamic property
		$doc->bag = new DataBag();

		// Cache the head and body.
		DOMCompat::getHead( $doc );
		DOMCompat::getBody( $doc );
	}

	/**
	 * @param Document $topLevelDoc
	 * @param Document $childDoc
	 */
	public static function prepareChildDoc( Document $topLevelDoc, Document $childDoc ) {
		// @phan-suppress-next-line PhanUndeclaredProperty dynamic property
		Assert::invariant( $topLevelDoc->bag instanceof DataBag, 'doc bag not set' );
		// @phan-suppress-next-line PhanUndeclaredProperty dynamic property
		$childDoc->bag = $topLevelDoc->bag;
	}

	/**
	 * Stash $obj in $doc and return an id for later retrieval
	 * @param Document $doc
	 * @param NodeData $obj
	 * @return int
	 */
	public static function stashObjectInDoc( Document $doc, NodeData $obj ): int {
		return self::getBag( $doc )->stashObject( $obj );
	}

	/**
	 * Does this node have any attributes?
	 * @param Element $node
	 * @return bool
	 */
	public static function noAttrs( Element $node ): bool {
		// The 'xmlns' attribute is "invisible" T235295
		if ( $node->hasAttribute( 'xmlns' ) ) {
			return false;
		}
		$numAttrs = count( $node->attributes );
		return $numAttrs === 0 ||
			( $numAttrs === 1 && $node->hasAttribute( self::DATA_OBJECT_ATTR_NAME ) );
	}

	/**
	 * Get data object from a node.
	 *
	 * @param Element $node node
	 * @return NodeData
	 */
	public static function getNodeData( Element $node ): NodeData {
		if ( !$node->hasAttribute( self::DATA_OBJECT_ATTR_NAME ) ) {
			// Initialized on first request
			$dataObject = new NodeData;
			self::setNodeData( $node, $dataObject );
			return $dataObject;
		}

		$nodeId = DOMCompat::getAttribute( $node, self::DATA_OBJECT_ATTR_NAME );
		if ( $nodeId !== null ) {
			$dataObject = self::getBag( $node->ownerDocument )->getObject( (int)$nodeId );
		} else {
			$dataObject = null; // Make phan happy
		}
		Assert::invariant( isset( $dataObject ), 'Bogus nodeId given!' );
		if ( isset( $dataObject->storedId ) ) {
			throw new UnreachableException(
				'Trying to fetch node data without loading!' .
				// If this node's data-object id is different from storedId,
				// it will indicate that the data-parsoid object was shared
				// between nodes without getting cloned. Useful for debugging.
				'Node id: ' . $nodeId .
				'Stored data: ' . PHPUtils::jsonEncode( $dataObject )
			);
		}
		return $dataObject;
	}

	/**
	 * Set node data.
	 *
	 * @param Element $node node
	 * @param NodeData $data data
	 */
	public static function setNodeData( Element $node, NodeData $data ): void {
		$nodeId = self::stashObjectInDoc( $node->ownerDocument, $data );
		$node->setAttribute( self::DATA_OBJECT_ATTR_NAME, (string)$nodeId );
	}

	/**
	 * Get data parsoid info from a node.
	 *
	 * @param Element $node node
	 * @return DataParsoid
	 */
	public static function getDataParsoid( Element $node ): DataParsoid {
		$data = self::getNodeData( $node );
		$data->parsoid ??= new DataParsoid;
		return $data->parsoid;
	}

	/**
	 * Set data parsoid info on a node.
	 *
	 * @param Element $node node
	 * @param DataParsoid $dp data-parsoid
	 */
	public static function setDataParsoid( Element $node, DataParsoid $dp ): void {
		$data = self::getNodeData( $node );
		$data->parsoid = $dp;
	}

	/**
	 * Returns the i18n information of a node. This is in private access because it shouldn't
	 * typically be used directly; instead getDataNodeI18n and getDataAttrI18n should be used.
	 * @param Element $node
	 * @return DataMwI18n|null
	 */
	private static function getDataMwI18n( Element $node ): ?DataMwI18n {
		$data = self::getNodeData( $node );
		// We won't set a default value for this property
		return $data->i18n ?? null;
	}

	/**
	 * Sets the i18n information of a node. This is in private access because it shouldn't
	 * typically be used directly; instead setDataNodeI18n and setDataAttrI18n should be used.
	 */
	private static function setDataMwI18n( Element $node, DataMwI18n $i18n ) {
		$data = self::getNodeData( $node );
		$data->i18n = $i18n;
	}

	/**
	 * Retrieves internationalization (i18n) information of a node (typically for localization)
	 * @param Element $node
	 * @return ?I18nInfo
	 */
	public static function getDataNodeI18n( Element $node ): ?I18nInfo {
		$data = self::getNodeData( $node );
		// We won't set a default value for this property
		if ( !isset( $data->i18n ) ) {
			return null;
		}
		return $data->i18n->getSpanInfo();
	}

	/**
	 * Sets internationalization (i18n) information of a node, used for later localization
	 */
	public static function setDataNodeI18n( Element $node, I18nInfo $i18n ) {
		$data = self::getNodeData( $node );
		$data->i18n ??= new DataMwI18n();
		$data->i18n->setSpanInfo( $i18n );
	}

	/**
	 * Retrieves internationalization (i18n) information of an attribute value (typically for
	 * localization)
	 * @param Element $node
	 * @param string $name
	 * @return ?I18nInfo
	 */
	public static function getDataAttrI18n( Element $node, string $name ): ?I18nInfo {
		$data = self::getNodeData( $node );
		// We won't set a default value for this property
		if ( !isset( $data->i18n ) ) {
			return null;
		}
		return $data->i18n->getAttributeInfo( $name );
	}

	/**
	 * Sets internationalization (i18n) information of a attribute value, used for later
	 * localization
	 * @param Element $node
	 * @param string $name
	 * @param I18nInfo $i18n
	 */
	public static function setDataAttrI18n( Element $node, string $name, I18nInfo $i18n ) {
		$data = self::getNodeData( $node );
		$data->i18n ??= new DataMwI18n();
		$data->i18n->setAttributeInfo( $name, $i18n );
	}

	/**
	 * @param Element $node
	 * @return array
	 */
	public static function getDataAttrI18nNames( Element $node ): array {
		$data = self::getNodeData( $node );
		// We won't set a default value for this property
		if ( !isset( $data->i18n ) ) {
			return [];
		}
		return $data->i18n->getAttributeNames();
	}

	/**
	 * Get data diff info from a node.
	 *
	 * @param Element $node node
	 * @return ?stdClass
	 */
	public static function getDataParsoidDiff( Element $node ): ?stdClass {
		$data = self::getNodeData( $node );
		// We won't set a default value for this property
		return $data->parsoid_diff ?? null;
	}

	/**
	 * Set data diff info on a node.
	 *
	 * @param Element $node node
	 * @param ?stdClass $diffObj data-parsoid-diff object
	 */
	public static function setDataParsoidDiff( Element $node, ?stdClass $diffObj ): void {
		$data = self::getNodeData( $node );
		$data->parsoid_diff = $diffObj;
	}

	/**
	 * Get data meta wiki info from a node.
	 *
	 * @param Element $node node
	 * @return DataMw
	 */
	public static function getDataMw( Element $node ): DataMw {
		$data = self::getNodeData( $node );
		$data->mw ??= new DataMw;
		return $data->mw;
	}

	/**
	 * Set data meta wiki info from a node.
	 *
	 * @param Element $node node
	 * @param ?DataMw $dmw data-mw
	 */
	public static function setDataMw( Element $node, ?DataMw $dmw ): void {
		$data = self::getNodeData( $node );
		$data->mw = $dmw;
	}

	/**
	 * Check if there is meta wiki info in a node.
	 *
	 * @param Element $node node
	 * @return bool
	 */
	public static function validDataMw( Element $node ): bool {
		return (array)self::getDataMw( $node ) !== [];
	}

	/**
	 * Check if there is i18n info on a node (for the node or its attributes)
	 * @param Element $node
	 * @return bool
	 */
	public static function validDataMwI18n( Element $node ): bool {
		return self::getDataMwI18n( $node ) !== null;
	}

	/**
	 * Get an object from a JSON-encoded XML attribute on a node.
	 *
	 * @param Element $node node
	 * @param string $name name
	 * @param mixed $defaultVal
	 * @return mixed
	 */
	public static function getJSONAttribute( Element $node, string $name, $defaultVal ) {
		$attVal = DOMCompat::getAttribute( $node, $name );
		if ( $attVal === null ) {
			return $defaultVal;
		}
		$decoded = PHPUtils::jsonDecode( $attVal, false );
		if ( $decoded !== null ) {
			return $decoded;
		} else {
			error_log( 'ERROR: Could not decode attribute-val ' . $attVal .
				' for ' . $name . ' on node ' . DOMCompat::nodeName( $node ) );
			return $defaultVal;
		}
	}

	/**
	 * Set a attribute on a node with a JSON-encoded object.
	 *
	 * @param Element $node node
	 * @param string $name Name of the attribute.
	 * @param mixed $obj value of the attribute to
	 */
	public static function setJSONAttribute( Element $node, string $name, $obj ): void {
		$val = $obj === [] ? '{}' : PHPUtils::jsonEncode( $obj );
		$node->setAttribute( $name, $val );
	}

	/**
	 * Set shadow info on a node; similar to the method on tokens.
	 * Records a key = value pair in data-parsoid['a'] property.
	 *
	 * This is effectively a call of 'setShadowInfoIfModified' except
	 * there is no original value, so by definition, $val is modified.
	 *
	 * @param Element $node node
	 * @param string $name Name of the attribute.
	 * @param mixed $val val
	 */
	public static function setShadowInfo( Element $node, string $name, $val ): void {
		$dp = self::getDataParsoid( $node );
		$dp->a ??= [];
		$dp->sa ??= [];
		$dp->a[$name] = $val;
	}

	/**
	 * Set shadow info on a node; similar to the method on tokens.
	 *
	 * If the new value ($val) for the key ($name) is different from the
	 * original value ($origVal):
	 * - the new value is recorded in data-parsoid->a and
	 * - the original value is recorded in data-parsoid->sa
	 *
	 * @param Element $node node
	 * @param string $name Name of the attribute.
	 * @param mixed $val val
	 * @param mixed $origVal original value (null is a valid value)
	 * @param bool $skipOrig
	 */
	public static function setShadowInfoIfModified(
		Element $node, string $name, $val, $origVal, bool $skipOrig = false
	): void {
		if ( !$skipOrig && ( $val === $origVal || $origVal === null ) ) {
			return;
		}
		$dp = self::getDataParsoid( $node );
		$dp->a ??= [];
		$dp->sa ??= [];
		// FIXME: This is a hack to not overwrite already shadowed info.
		// We should either fix the call site that depends on this
		// behaviour to do an explicit check, or double down on this
		// by porting it to the token method as well.
		if ( !$skipOrig && !array_key_exists( $name, $dp->a ) ) {
			$dp->sa[$name] = $origVal;
		}
		$dp->a[$name] = $val;
	}

	/**
	 * Set an attribute and shadow info to a node.
	 * Similar to the method on tokens
	 *
	 * @param Element $node node
	 * @param string $name Name of the attribute.
	 * @param mixed $val value
	 * @param mixed $origVal original value
	 * @param bool $skipOrig
	 */
	public static function addNormalizedAttribute(
		Element $node, string $name, $val, $origVal, bool $skipOrig = false
	): void {
		if ( $name === 'id' ) {
			DOMCompat::setIdAttribute( $node, $val );
		} else {
			$node->setAttribute( $name, $val );
		}
		self::setShadowInfoIfModified( $node, $name, $val, $origVal, $skipOrig );
	}

	/**
	 * Get this document's pagebundle object
	 * @param Document $doc
	 * @return PageBundle
	 */
	public static function getPageBundle( Document $doc ): PageBundle {
		return self::getBag( $doc )->getPageBundle();
	}

	/**
	 * Removes the `data-*` attribute from a node, and migrates the data to the
	 * document's JSON store. Generates a unique id with the following format:
	 * ```
	 * mw<base64-encoded counter>
	 * ```
	 * but attempts to keep user defined ids.
	 *
	 * TODO: Note that $data is effective a partial PageBundle containing
	 * only the 'parsoid' and 'mw' properties.
	 *
	 * @param Element $node node
	 * @param Env $env environment
	 * @param stdClass $data data
	 * @param array $idIndex Index of used id attributes in the DOM
	 */
	public static function storeInPageBundle(
		Element $node, Env $env, stdClass $data, array $idIndex
	): void {
		$uid = DOMCompat::getAttribute( $node, 'id' );
		$document = $node->ownerDocument;
		$pb = self::getPageBundle( $document );
		$docDp = &$pb->parsoid;
		$origId = $uid;
		if ( $uid !== null && array_key_exists( $uid, $docDp['ids'] ) ) {
			$uid = null;
			$env->log( 'info', 'Wikitext for this page has duplicate ids: ' . $origId );
		}
		if ( $uid === '' ) {
			$uid = null;
			$env->log( 'info', 'Bogus empty id' );
		}
		if ( $uid === null ) {
			do {
				$docDp['counter'] += 1;
				// PORT-FIXME: NOTE that we aren't updating the idIndex here because
				// we are generating unique ids that will not conflict. In any case,
				// the idIndex is a workaround for the PHP DOM's issues and we might
				// switch out of this in the future anyway.
				$uid = 'mw' . PHPUtils::counterToBase64( $docDp['counter'] );
			} while ( isset( $idIndex[$uid] ) );
			self::addNormalizedAttribute( $node, 'id', $uid, $origId );
		}
		$docDp['ids'][$uid] = $data->parsoid;
		if ( isset( $data->mw ) ) {
			$pb->mw['ids'][$uid] = $data->mw;
		}
	}

	/**
	 * @param Document $doc doc
	 * @param PageBundle $pb object
	 */
	public static function injectPageBundle( Document $doc, PageBundle $pb ): void {
		$script = DOMUtils::appendToHead( $doc, 'script', [
			'id' => 'mw-pagebundle',
			'type' => 'application/x-mw-pagebundle',
		] );
		$script->appendChild( $doc->createTextNode( $pb->encodeForHeadElement() ) );
	}

	/**
	 * @param Document $doc doc
	 * @return stdClass|null
	 */
	public static function extractPageBundle( Document $doc ): ?stdClass {
		$pb = null;
		$dpScriptElt = DOMCompat::getElementById( $doc, 'mw-pagebundle' );
		if ( $dpScriptElt ) {
			$dpScriptElt->parentNode->removeChild( $dpScriptElt );
			// we actually want arrays in the page bundle rather than stdClasses; but we still
			// want to access the object properties
			$pb = (object)PHPUtils::jsonDecode( $dpScriptElt->textContent );
		}
		return $pb;
	}

	/**
	 * Walk DOM from node downward calling loadDataAttribs
	 *
	 * @param Node $node node
	 * @param array $options options
	 */
	public static function visitAndLoadDataAttribs( Node $node, array $options = [] ): void {
		DOMUtils::visitDOM( $node, [ self::class, 'loadDataAttribs' ], $options );
	}

	/**
	 * Massage the data parsoid object loaded from a node attribute
	 * into expected shape.
	 *
	 * @param stdClass $stdDP
	 * @param array $options
	 * @param ?Element $node
	 * @return DataParsoid
	 */
	private static function massageLoadedDataParsoid(
		stdClass $stdDP, array $options = [], ?Element $node = null
	): DataParsoid {
		$dp = new DataParsoid;
		foreach ( $stdDP as $key => $value ) {
			switch ( $key ) {
				case 'a':
				case 'sa':
					$dp->$key = (array)$value;
					break;

				case 'dsr':
				case 'extTagOffsets':
					if ( $value !== null ) {
						$dp->$key = DomSourceRange::fromArray( $value );
					}
					break;

				case 'tsr':
				case 'extLinkContentOffsets':
					if ( $value !== null ) {
						$dp->$key = SourceRange::fromArray( $value );
					}
					break;

				case 'optList':
					$optList = [];
					foreach ( $value as $item ) {
						$optList[] = (array)$item;
					}
					$dp->optList = $optList;
					break;

				case 'pi':
					$pi = [];
					foreach ( $value as $item ) {
						$pi2 = [];
						foreach ( $item as $item2 ) {
							$pi2[] = ParamInfo::newFromJson( $item2 );
						}
						$pi[] = $pi2;
					}
					$dp->pi = $pi;
					break;

				case 'tmp':
					// $tmp in DataParsoid.php is lazy-initialized and can be empty
					if ( $value ) {
						$tmp = new TempData;
						foreach ( $value as $key2 => $value2 ) {
							$tmp->$key2 = $value2;
						}
						$dp->tmp = $tmp;
					}
					break;

				default:
					$dp->$key = $value;
			}
		}
		if ( !empty( $options['markNew'] ) ) {
			$dp->setTempFlag( TempData::IS_NEW, !$node->hasAttribute( 'data-parsoid' ) );
		}
		return $dp;
	}

	/**
	 * These are intended be used on a document after post-processing, so that
	 * the underlying .dataobject is transparently applied (in the store case)
	 * and reloaded (in the load case), rather than worrying about keeping
	 * the attributes up-to-date throughout that phase.  For the most part,
	 * using this.ppTo* should be sufficient and using these directly should be
	 * avoided.
	 *
	 * @param Node $node node
	 * @param array $options options
	 */
	public static function loadDataAttribs( Node $node, array $options ): void {
		if ( !( $node instanceof Element ) ) {
			return;
		}
		// Reset the node data object's stored state, since we're reloading it
		self::setNodeData( $node, new NodeData );
		$dp = self::massageLoadedDataParsoid(
			self::getJSONAttribute( $node, 'data-parsoid', new stdClass ),
			$options, $node );
		self::setDataParsoid( $node, $dp );
		$node->removeAttribute( 'data-parsoid' );
		$dmw = self::getJSONAttribute( $node, 'data-mw', null );
		self::setDataMw( $node, $dmw !== null ? new DataMw( (array)$dmw ) : null );
		$node->removeAttribute( 'data-mw' );
		$dpd = self::getJSONAttribute( $node, 'data-parsoid-diff', null );
		self::setDataParsoidDiff( $node, $dpd );
		$node->removeAttribute( 'data-parsoid-diff' );
		$dataI18n = DOMCompat::getAttribute( $node, 'data-mw-i18n' );
		if ( $dataI18n !== null ) {
			$i18n = DataMwI18n::fromJson( PHPUtils::jsonDecode( $dataI18n, true ) );
			self::setDataMwI18n( $node, $i18n );
			$node->removeAttribute( 'data-mw-i18n' );
		}
	}

	/**
	 * Builds an index of id attributes seen in the DOM
	 * @param Node $node
	 * @return array
	 */
	public static function usedIdIndex( Node $node ): array {
		$index = [];
		DOMUtils::visitDOM( DOMCompat::getBody( $node->ownerDocument ),
			static function ( Node $n, ?array $options = null ) use ( &$index ) {
				if ( $n instanceof Element ) {
					$id = DOMCompat::getAttribute( $n, 'id' );
					if ( $id !== null ) {
						$index[$id] = true;
					}
				}
			},
			[]
		);
		return $index;
	}

	/**
	 * Walk DOM from node downward calling storeDataAttribs
	 *
	 * @param Node $node node
	 * @param array $options options
	 */
	public static function visitAndStoreDataAttribs( Node $node, array $options = [] ): void {
		// PORT-FIXME: storeDataAttribs calls storeInPageBundle which calls getElementById.
		// PHP's `getElementById` implementation is broken, and we work around that by
		// using Zest which uses XPath. So, getElementById call can be O(n) and calling it
		// on on every element of the DOM via vistDOM here makes it O(n^2) instead of O(n).
		// So, we work around that by building an index and avoiding getElementById entirely
		// in storeInPageBundle.
		if ( !empty( $options['storeInPageBundle'] ) ) {
			$options['idIndex'] = self::usedIdIndex( $node );
		}
		DOMUtils::visitDOM( $node, [ self::class, 'storeDataAttribs' ], $options );
	}

	/**
	 * Copy data attributes from the bag to either JSON-encoded attributes on
	 * each node, or the page bundle, erasing the data-object-id attributes.
	 *
	 * @param Node $node node
	 * @param ?array $options options
	 *   - discardDataParsoid: Discard DataParsoid objects instead of storing them
	 *   - keepTmp: Preserve DataParsoid::$tmp
	 *   - storeInPageBundle: If true, data will be stored in the page bundle
	 *     instead of data-parsoid and data-mw.
	 *   - env: The Env object required for various features
	 *   - idIndex: Array of used ID attributes
	 */
	public static function storeDataAttribs( Node $node, ?array $options = null ): void {
		$options ??= [];
		if ( !( $node instanceof Element ) ) {
			return;
		}
		Assert::invariant( empty( $options['discardDataParsoid'] ) || empty( $options['keepTmp'] ),
			'Conflicting options: discardDataParsoid and keepTmp are both enabled.' );
		$dp = self::getDataParsoid( $node );
		$discardDataParsoid = !empty( $options['discardDataParsoid'] );
		if ( $dp->getTempFlag( TempData::IS_NEW ) ) {
			// Only necessary to support the cite extension's getById,
			// that's already been loaded once.
			//
			// This is basically a hack to ensure that DOMUtils.isNewElt
			// continues to work since we effectively rely on the absence
			// of data-parsoid to identify new elements. But, loadDataAttribs
			// creates an empty {} if one doesn't exist. So, this hack
			// ensures that a loadDataAttribs + storeDataAttribs pair don't
			// dirty the node by introducing an empty data-parsoid attribute
			// where one didn't exist before.
			//
			// Ideally, we'll find a better solution for this edge case later.
			$discardDataParsoid = true;
		}
		$data = null;
		if ( !$discardDataParsoid ) {
			if ( empty( $options['keepTmp'] ) ) {
				// @phan-suppress-next-line PhanTypeObjectUnsetDeclaredProperty
				unset( $dp->tmp );
			}

			if ( !empty( $options['storeInPageBundle'] ) ) {
				$data = (object)[ 'parsoid' => $dp ];
			} else {
				self::setJSONAttribute( $node, 'data-parsoid', $dp );
			}
		}

		// Strip invalid data-mw attributes
		if ( self::validDataMw( $node ) ) {
			if (
				!empty( $options['storeInPageBundle'] ) && isset( $options['env'] ) &&
				// The pagebundle didn't have data-mw before 999.x
				Semver::satisfies( $options['env']->getOutputContentVersion(), '^999.0.0' )
			) {
				$data = $data ?: new stdClass;
				$data->mw = self::getDataMw( $node );
			} else {
				self::setJSONAttribute( $node, 'data-mw', self::getDataMw( $node ) );
			}
		}

		if ( self::validDataMwI18n( $node ) ) {
			self::setJSONAttribute( $node, 'data-mw-i18n', self::getDataMwI18n( $node ) );
		}

		// Store pagebundle
		if ( $data !== null ) {
			self::storeInPageBundle( $node, $options['env'], $data, $options['idIndex'] );
		}

		// Indicate that this node's data has been stored so that if we try
		// to access it after the fact we're aware and remove the attribute
		// since it's no longer needed.
		$nd = self::getNodeData( $node );
		$id = DOMCompat::getAttribute( $node, self::DATA_OBJECT_ATTR_NAME );
		$nd->storedId = $id !== null ? intval( $id ) : null;
		$node->removeAttribute( self::DATA_OBJECT_ATTR_NAME );
	}

	/**
	 * Clones a node and its data bag
	 * @param Element $elt
	 * @param bool $deep
	 * @return Element
	 */
	public static function cloneNode( Element $elt, bool $deep ): Element {
		$clone = $elt->cloneNode( $deep );
		'@phan-var Element $clone'; // @var Element $clone
		// We do not need to worry about $deep because a shallow clone does not have child nodes,
		// so it's always cloning data on the cloned tree (which may be empty).
		self::fixClonedData( $clone );
		return $clone;
	}

	/**
	 * Recursively fixes cloned data from $elt: to avoid conflicts of element IDs, we clone the
	 * data and set it in the node with a new element ID (which setNodeData does).
	 * @param Element $elt
	 */
	private static function fixClonedData( Element $elt ) {
		if ( $elt->hasAttribute( self::DATA_OBJECT_ATTR_NAME ) ) {
			self::setNodeData( $elt, self::getNodeData( $elt )->cloneNodeData() );
		}
		foreach ( $elt->childNodes as $child ) {
			if ( $child instanceof Element ) {
				self::fixClonedData( $child );
			}
		}
	}
}
