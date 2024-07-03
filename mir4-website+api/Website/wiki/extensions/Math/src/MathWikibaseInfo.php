<?php

namespace MediaWiki\Extension\Math;

use DataValues\StringValue;
use MediaWiki\Html\Html;
use MediaWiki\MediaWikiServices;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\Lib\Formatters\SnakFormatter;

/**
 * This class stores information about mathematical Wikibase items.
 */
class MathWikibaseInfo {
	/**
	 * @var EntityId
	 */
	private $id;

	/**
	 * @var string the label of the item
	 */
	private $label;

	/**
	 * @var string description of the item
	 */
	private $description;

	/**
	 * @var StringValue a symbol representing the item
	 */
	private $symbol;

	/**
	 * @var MathWikibaseInfo[]
	 */
	private $hasParts = [];

	/**
	 * @var MathFormatter
	 */
	private $mathFormatter;

	/**
	 * @var string
	 */
	private $url;

	/**
	 * @param EntityId $entityId
	 * @param MathFormatter|null $mathFormatter to format math equations. Default format is HTML.
	 */
	public function __construct( EntityId $entityId, MathFormatter $mathFormatter = null ) {
		$this->id = $entityId;
		$this->mathFormatter = $mathFormatter ?: new MathFormatter( SnakFormatter::FORMAT_HTML );
	}

	/**
	 * @param string $label
	 */
	public function setLabel( $label ) {
		$this->label = $label;
	}

	/**
	 * @param string $description
	 */
	public function setDescription( $description ) {
		$this->description = $description;
	}

	/**
	 * @param StringValue $symbol
	 */
	public function setSymbol( $symbol ) {
		$this->symbol = $symbol;
	}

	/**
	 * @param MathWikibaseInfo $info
	 */
	public function addHasPartElement( MathWikibaseInfo $info ) {
		$this->hasParts[] = $info;
	}

	/**
	 * @param string $link
	 */
	public function setUrl( $link ) {
		$this->url = $link;
	}

	/**
	 * @param MathWikibaseInfo[] $infos
	 */
	public function addHasPartElements( $infos ) {
		array_push( $this->hasParts, ...$infos );
	}

	/**
	 * @return EntityId id
	 */
	public function getId() {
		return $this->id;
	}

	/**
	 * @return string label
	 */
	public function getLabel() {
		return $this->label;
	}

	/**
	 * @return string description
	 */
	public function getDescription() {
		return $this->description;
	}

	/**
	 * @return StringValue symbol
	 */
	public function getSymbol() {
		return $this->symbol;
	}

	/**
	 * @return string|null html formatted version of the symbol
	 */
	public function getFormattedSymbol() {
		if ( $this->symbol ) {
			return $this->mathFormatter->format( $this->getSymbol() );
		} else {
			return null;
		}
	}

	/**
	 * @return MathWikibaseInfo[] hasparts
	 */
	public function getParts() {
		return $this->hasParts;
	}

	/**
	 * @return string
	 */
	public function getUrl() {
		return $this->url;
	}

	/**
	 * @return MathFormatter
	 */
	public function getFormatter() {
		return $this->mathFormatter;
	}

	/**
	 * Does this info object has elements?
	 * @return bool true if there are elements otherwise false
	 */
	public function hasParts() {
		return $this->hasParts !== [];
	}

	/**
	 * Generates an HTML table representation of the has-parts elements
	 * @return string
	 */
	public function generateTableOfParts() {
		$lang = MediaWikiServices::getInstance()->getContentLanguage();
		$labelAlign = $lang->isRTL() ? 'left' : 'right';
		$labelAlignOpposite = !$lang->isRTL() ? 'left' : 'right';

		$output = Html::openElement( "table", [ "style" => "padding: 5px" ] );
		$output .= Html::openElement( "tbody" );

		foreach ( $this->hasParts as $part ) {
			$output .= Html::openElement( "tr" );

			$output .= Html::openElement(
				"td",
				[ "style" => "font-weight: bold; text-align:$labelAlign;" ]
			);

			if ( $part->url ) {
				$output .= Html::element(
					"a",
					[ "href" => $part->url ],
					$part->getLabel()
				);
			} else {
				$output .= $part->getLabel();
			}

			$output .= Html::closeElement( "td" );
			$output .= Html::openElement(
				"td",
				[ "style" => "text-align:center; padding: 2px; padding-left: 10px; padding-right: 10px;" ]
			);

			if ( $part->url ) {
				$output .= Html::rawElement(
					"a",
					[ "href" => $part->url ],
					$part->getFormattedSymbol()
				);
			} else {
				$output .= $part->getFormattedSymbol();
			}

			$output .= Html::closeElement( "td" );
			$output .= Html::element(
				"td",
				[ "style" => "font-style: italic; text-align:$labelAlignOpposite;" ],
				$part->getDescription()
			);
			$output .= Html::closeElement( "tr" );
		}

		$output .= Html::closeElement( "tbody" );
		$output .= Html::closeElement( "table" );

		return $output;
	}

	/**
	 * Generates a minimalized Html representation of the has-parts elements.
	 * @return string
	 */
	public function generateSmallTableOfParts() {
		$output = Html::openElement( "table" );
		$output .= Html::openElement( "tbody" );

		foreach ( $this->hasParts as $part ) {
			$output .= Html::openElement( "tr" );
			$output .= Html::rawElement(
				"td",
				[ "style" => "text-align: center; padding-right: 5px;" ],
				$part->getFormattedSymbol()
			);
			$output .= Html::element( "td", [ "style" => "text-align:left;" ], $part->getLabel() );
			$output .= Html::closeElement( "tr" );
		}

		$output .= Html::closeElement( "tbody" );
		$output .= Html::closeElement( "table" );
		return $output;
	}
}
