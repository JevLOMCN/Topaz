<?php

namespace MediaWiki\Extension\Math\WikiTexVC\MMLmappings\Util;

use MediaWiki\Extension\Math\WikiTexVC\XMLNode;

/**
 * Algorithm to make a simple, but customizable comparison of two MathML-Strings for automated testing.
 * It compares all element keys in the tree without order and calculates an F-score based on similarity.
 * Also, it gives information the elements which differ both trees.
 *
 * @author Johannes Stegmüller
 */
class MMLComparator {

	/**
	 * These keys for mathml-elements get not
	 * considered for similarity comparison.
	 */
	private const IGNOREDELEMENTKEYS = [
		"semantics",
		"annotation",
		"annotation-xml",
		"csymbol",
		"mrow",
		"mstyle"
	];

	/**
	 * Although the keys mentioned here may be on the ignore list.
	 * Keys containing one of the specific attribute get checked.
	 */
	private const CHECKEXPLICITLY = [
		"mstyle" => [ "mathcolor" ]
	];

	/**
	 * Attributes which are not considered for similarity checks.
	 * Attributes are always defined for a specific mathml key.
	 */
	private const IGNOREDATTRIBUTES = [
		"mrow" => [ "class", "data-mjx-texclass" ],
		"math" => [ "alttext", "display" ],
		"mi"   => [ "class", "data-mjx-variant", "mathvariant", "data-mjx-texclass", "data-mjx-alternate" ],
		"mo"   => [ "data-mjx-pseudoscript", "stretchy", "fence", "data-mjx-texclass", "texClass", "class",
			"data-mjx-alternate", "form", "accent", "lspace", "rspace", "xref", "id" ],
		"mtext" => [ "texClass", "class" ],
		"mspace" => [ "width" ]
	];

	public static function functionObtainTreeInBrackets( $mml ) {
		$xml = simplexml_load_string( $mml );
		$nodes = self::xmlToNode( $xml );
		return self::convertToBracketFormat( $nodes );
	}

	/**
	 * Compares the base to the comparison MathML.
	 * The base is considered the reference MathML. Calculates an F-Score for similarity of elements.
	 * MathML elements which are not considered for similarity checks can be specified  within this class.
	 * Gives back the differing elements relative to the base.
	 * If any issue with the input, the returned F-Score has value '-0.0'
	 *
	 * @param string $mmlBase MathML base element as string
	 * @param string $mmlComp MathML comparison element as string
	 * @return array with 'similarityF': Calculated F-Score for similarity, 'more/less': diffs relative to the base.
	 */
	public function compareMathML( $mmlBase, $mmlComp ): array {
		$compRes = [
			"similarityF" => -0.0,
			"more" => [],
			"less" => []
		];

		if ( !$mmlBase || !$mmlComp || trim( $mmlBase ) == "" || trim( $mmlComp ) == "" ) {
			return $compRes;
		}

		$xmlBase = simplexml_load_string( $mmlBase );
		$xmlComp = simplexml_load_string( $mmlComp );

		// Create flattened Arrays of MathML
		$mbase = $this->createComparisonArray( $xmlBase );
		$mcomp = $this->createComparisonArray( $xmlComp );

		// Compare base and comp arrays, tbd: eventually extract this function
		$countsBase = $this->countArraySize( $mbase );
		$countsComp = $this->countArraySize( $mcomp );
		$overallRelevantSum = $countsBase[0];
		$overallRetrievedSum = $countsComp[0];

		$ctrSame = $this->compareMathMLKeyArrays( $compRes, $mbase, $mcomp );

		$compRes['similarityF'] = $this->calculateFscore( $ctrSame, $overallRelevantSum, $overallRetrievedSum );
		return $compRes;
	}

	public static function xmlToNode( $xml ) {
		$node = new XMLNode( $xml->getName() );

		foreach ( $xml->children() as $child ) {
			$node->children[] = self::xmlToNode( $child );
		}

		return $node;
	}

	private function treeEditDistance( $root1, $root2 ) {
		$n = count( $root1->children );
		$m = count( $root2->children );

		$dp = [];
		for ( $i = 0; $i <= $n; $i++ ) {
			$dp[$i] = [];
			for ( $j = 0; $j <= $m; $j++ ) {
				$dp[$i][$j] = 0;
			}
		}

		for ( $i = 0; $i <= $n; $i++ ) {
			for ( $j = 0; $j <= $m; $j++ ) {
				if ( $i == 0 ) {
					$dp[$i][$j] = $j;
				} elseif ( $j == 0 ) {
					$dp[$i][$j] = $i;
				} elseif ( $root1->children[$i - 1]->value == $root2->children[$j - 1]->value ) {
					$dp[$i][$j] = $dp[$i - 1][$j - 1];
				} else {
					$dp[$i][$j] = 1 + min(
							$dp[$i][$j - 1], // Insert
							$dp[$i - 1][$j], // Remove
							$dp[$i - 1][$j - 1] // Replace
						);
				}
			}
		}

		$ted = $dp[$n][$m];
		$totalNodes = $n + $m; // Total nodes in both trees
		if ( $totalNodes != 0 ) {
			$normalizedTED = $ted / $totalNodes;
		} else {
			$normalizedTED = -1;
		}

		return [ "TED" => $ted, "normalizedTED" => $normalizedTED ];
	}

	public static function convertToBracketFormat( $root ) {
		if ( empty( $root->children ) ) {
			return "{" . $root->value . "}";
		}

		$result = $root->value . '{';

		foreach ( $root->children as $child ) {
			$result .= self::convertToBracketFormat( $child );
		}

		$result .= '}';
		return $result;
	}

	private function compareMathMLKeyArrays( &$compRes, $mbase, $mcomp ) {
		$intersections = 0;
		foreach ( $mbase as $key => $baseElement ) {
			$compElement = $mcomp[$key] ?? null;
			if ( $compElement == null ) {
				// The base has this mml element(s), but not the comparison
				continue;
			}
			$compRet = $this->compareArrays( $baseElement, $compElement );
			$intersections += $compRet['sameCtr'];
			if ( count( $compRet['more'] ) > 0 ) {
				$compRes['more'][$key] = $compRet['more'];
			}
			if ( count( $compRet['less'] ) > 0 ) {
				$compRes['less'][$key] = $compRet['less'];
			}
		}
		return $intersections;
	}

	private function calculateFscore( $intersection, $sumRelevant, $sumRetrieved ) {
		if ( $sumRelevant == 0 && $sumRetrieved == 0 ) {
			return 1.0;
		}

		if ( $sumRetrieved == 0 && $sumRelevant != 0 ) {
			return 0;
		}

		if ( $sumRelevant == 0 && $sumRetrieved != 0 ) {
			$recall = 1;
		} else {
			$recall = $intersection / $sumRelevant;
		}

		$prec = $intersection / $sumRetrieved;
		if ( ( $prec + $recall ) == 0 ) {
			return 0;
		}
		// Calculate F-Score
		return 2 * ( ( $prec * $recall ) / ( $prec + $recall ) );
	}

	private function countArraySize( $arr ): array {
		$overallSize = 0;
		$overallAttrs = 0;
		foreach ( $arr as $element ) {
			foreach ( $element as $attrs ) {
				$overallSize += 1;
				$overallAttrs += count( $attrs );
			}
		}

		return [ $overallSize, $overallAttrs ];
	}

	private function compareArrays( $base, $comp ): array {
		$sameCtr = 0;
		$compRet = [
			"sameCtr" => 0,
			"less" => [],
			"more" => [],
		];

		// Return score how many same or very similar items are in array
		foreach ( $base as $keyBase => $baseEl ) {
			foreach ( $comp as $keyComp => $compEl ) {
				if ( $compEl == -1 ) {
					continue;
				}
				if ( $this->compareTwoBaseElements( $compEl, $baseEl ) ) {
					$base[$keyBase] = -1;
					$comp[$keyComp] = -1;
					$sameCtr += 1;
					break;
				}
			}
		}
		$compRet['sameCtr'] = $sameCtr;
		$compRet = $this->addRemainingElements( $compRet, 'less', $base );
		return $this->addRemainingElements( $compRet, 'more', $comp );
	}

	private function addRemainingElements( $compRet, $key, $data ): array {
		foreach ( $data as $el ) {
			if ( $el !== null && $el != -1 ) {
				$compRet[$key][] = $el;
			}
		}
		return $compRet;
	}

	private function compareTwoBaseElements( $compEl, $baseEl ): bool {
		// Most basic comparison, this works for elements without attributes
		if ( $compEl === $baseEl ) {
			return true;
		}
		// The array and sub-elements are exactly the same
		if ( array_diff( $compEl, $baseEl ) == [] && array_diff( $baseEl, $compEl ) == [] ) {
			return true;
		}

		return false;
	}

	private function createComparisonArray( $xml ): array {
		$allKeys = [];
		$this->prepareMMLElements( $xml, $allKeys );
		$finalMMLKeys = $this->filterKeys( $allKeys );
		return $finalMMLKeys;
	}

	private function filterKeys( $allMMLElements ): array {
		$finalKeys = [];
		foreach ( $allMMLElements as $key => $element ) {
			if ( in_array( $key, self::IGNOREDELEMENTKEYS, true ) ) {
				$finalKeys = $this->checkExplicitKeys( $key, $element, $finalKeys );
				continue;
			}
			$finalKeys[$key] = $element;
		}
		return $finalKeys;
	}

	private function prepareMMLElements( $xml, &$finalArray, $alwaysSetVal = false ) {
		foreach ( $xml as $key => $element ) {
			if ( $element instanceof \SimpleXMLElement ) {
				$el = $this->filterAttributes( $key, $element->attributes() );
				$value = trim( (string)$element );
				if ( $alwaysSetVal || $value !== "" ) {
					$el["val"] = $value;
				}
				$finalArray[$key][] = $el ?? [];

			} else {
				$finalArray[$key][] = $element;
			}
			$this->prepareMMLElements( $element, $finalArray );
		}
	}

	private function filterAttributes( $elementKey, $attributes ): array {
		$ignoredAttrs = self::IGNOREDATTRIBUTES[$elementKey] ?? [];
		$finalAttributes = [];
		foreach ( $attributes as $akey => $aval ) {

			if ( in_array( $akey, $ignoredAttrs, true ) ) {
				continue;
			}
			$finalAttributes[$akey] = $aval[0];
		}
		return $finalAttributes;
	}

	private function checkExplicitKeys( $key, $element, array $finalKeys ): array {
		$check = self::CHECKEXPLICITLY[$key] ?? null;
		if ( $check ) {
			foreach ( $element as $added ) {
				foreach ( $check as $checkEl ) {
					if ( array_key_exists( $checkEl, $added ) ) {
						$finalKeys[$key] = $element;
					}
				}

			}
		}
		return $finalKeys;
	}
}
