<?php

namespace Wikimedia\RemexHtml\TreeBuilder;

use Wikimedia\RemexHtml\HTMLData;
use Wikimedia\RemexHtml\Tokenizer\Attribute;
use Wikimedia\RemexHtml\Tokenizer\Attributes;

/**
 * An Attributes class for storing attributes on foreign elements, which may
 * have namespaces. Features lazy adjustment of attribute name case.
 */
class ForeignAttributes implements Attributes {
	/** @var Attributes */
	protected $unadjusted;

	/** @var array The map of lowercase attribute name to correct attribute name */
	protected $table;

	/** @var Attribute[]|null */
	protected $attrObjects;

	/**
	 * Adjustment tables for the case of attributes on MathML and SVG elements
	 * @var array
	 */
	private static $adjustmentTables = [
		'math' => [
			'definitionurl' => 'definitionURL',
		],
		'svg' => [
			'attributename' => 'attributeName',
			'attributetype' => 'attributeType',
			'basefrequency' => 'baseFrequency',
			'baseprofile' => 'baseProfile',
			'calcmode' => 'calcMode',
			'clippathunits' => 'clipPathUnits',
			'diffuseconstant' => 'diffuseConstant',
			'edgemode' => 'edgeMode',
			'filterunits' => 'filterUnits',
			'glyphref' => 'glyphRef',
			'gradienttransform' => 'gradientTransform',
			'gradientunits' => 'gradientUnits',
			'kernelmatrix' => 'kernelMatrix',
			'kernelunitlength' => 'kernelUnitLength',
			'keypoints' => 'keyPoints',
			'keysplines' => 'keySplines',
			'keytimes' => 'keyTimes',
			'lengthadjust' => 'lengthAdjust',
			'limitingconeangle' => 'limitingConeAngle',
			'markerheight' => 'markerHeight',
			'markerunits' => 'markerUnits',
			'markerwidth' => 'markerWidth',
			'maskcontentunits' => 'maskContentUnits',
			'maskunits' => 'maskUnits',
			'numoctaves' => 'numOctaves',
			'pathlength' => 'pathLength',
			'patterncontentunits' => 'patternContentUnits',
			'patterntransform' => 'patternTransform',
			'patternunits' => 'patternUnits',
			'pointsatx' => 'pointsAtX',
			'pointsaty' => 'pointsAtY',
			'pointsatz' => 'pointsAtZ',
			'preservealpha' => 'preserveAlpha',
			'preserveaspectratio' => 'preserveAspectRatio',
			'primitiveunits' => 'primitiveUnits',
			'refx' => 'refX',
			'refy' => 'refY',
			'repeatcount' => 'repeatCount',
			'repeatdur' => 'repeatDur',
			'requiredextensions' => 'requiredExtensions',
			'requiredfeatures' => 'requiredFeatures',
			'specularconstant' => 'specularConstant',
			'specularexponent' => 'specularExponent',
			'spreadmethod' => 'spreadMethod',
			'startoffset' => 'startOffset',
			'stddeviation' => 'stdDeviation',
			'stitchtiles' => 'stitchTiles',
			'surfacescale' => 'surfaceScale',
			'systemlanguage' => 'systemLanguage',
			'tablevalues' => 'tableValues',
			'targetx' => 'targetX',
			'targety' => 'targetY',
			'textlength' => 'textLength',
			'viewbox' => 'viewBox',
			'viewtarget' => 'viewTarget',
			'xchannelselector' => 'xChannelSelector',
			'ychannelselector' => 'yChannelSelector',
			'zoomandpan' => 'zoomAndPan',
		],
		'other' => [],
	];

	/**
	 * The potentially namespaced attributes, and the namespaces they belong to.
	 * Excepting xmlns since it is very special.
	 *
	 * @var array
	 */
	private static $namespaceMap = [
		'xlink:actuate' => HTMLData::NS_XLINK,
		'xlink:arcrole' => HTMLData::NS_XLINK,
		'xlink:href' => HTMLData::NS_XLINK,
		'xlink:role' => HTMLData::NS_XLINK,
		'xlink:show' => HTMLData::NS_XLINK,
		'xlink:title' => HTMLData::NS_XLINK,
		'xlink:type' => HTMLData::NS_XLINK,
		'xml:lang' => HTMLData::NS_XML,
		'xml:space' => HTMLData::NS_XML,
		'xmlns:xlink' => HTMLData::NS_XMLNS,
	];

	/**
	 * @param Attributes $unadjusted The unadjusted attributes from the Tokenizer
	 * @param string $type The element type, which may be "math", "svg" or "other".
	 */
	public function __construct( Attributes $unadjusted, $type ) {
		$this->unadjusted = $unadjusted;
		$this->table = self::$adjustmentTables[$type];
	}

	public function offsetExists( $offset ): bool {
		$offset = $this->table[$offset] ?? $offset;
		return $this->unadjusted->offsetExists( $offset );
	}

	public function &offsetGet( $offset ): string {
		$offset = $this->table[$offset] ?? $offset;
		$value = &$this->unadjusted->offsetGet( $offset );
		return $value;
	}

	public function offsetSet( $offset, $value ): void {
		// @phan-suppress-previous-line PhanPluginNeverReturnMethod
		throw new TreeBuilderError( "Setting foreign attributes is not supported" );
	}

	public function offsetUnset( $offset ): void {
		// @phan-suppress-previous-line PhanPluginNeverReturnMethod
		throw new TreeBuilderError( "Setting foreign attributes is not supported" );
	}

	public function getValues() {
		$result = [];
		foreach ( $this->unadjusted->getValues() as $name => $value ) {
			$name = $this->table[$name] ?? $name;
			$result[$name] = $value;
		}
		return $result;
	}

	public function count(): int {
		return $this->unadjusted->count();
	}

	public function getIterator(): \ArrayIterator {
		return new \ArrayIterator( $this->getValues() );
	}

	public function getObjects() {
		if ( $this->attrObjects === null ) {
			$result = [];
			foreach ( $this->unadjusted->getValues() as $name => $value ) {
				if ( isset( $this->table[$name] ) ) {
					$name = $this->table[$name];
				}
				if ( $name === 'xmlns' ) {
					$prefix = null;
					$namespace = HTMLData::NS_XMLNS;
					$localName = $name;
				} elseif ( isset( self::$namespaceMap[$name] ) ) {
					$namespace = self::$namespaceMap[$name];
					[ $prefix, $localName ] = explode( ':', $name, 2 );
				} else {
					$prefix = null;
					$namespace = null;
					$localName = $name;
				}
				$result[$name] = new Attribute( $name, $namespace, $prefix, $localName, $value );
			}
			$this->attrObjects = $result;
		}
		return $this->attrObjects;
	}

	public function merge( Attributes $other ) {
		// @phan-suppress-previous-line PhanPluginNeverReturnMethod
		throw new TreeBuilderError( __METHOD__ . ': unimplemented' );
	}

	public function clone() {
		return $this;
	}
}
