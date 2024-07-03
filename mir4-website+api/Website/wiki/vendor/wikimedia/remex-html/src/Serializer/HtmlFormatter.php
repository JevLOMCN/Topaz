<?php

namespace Wikimedia\RemexHtml\Serializer;

use Wikimedia\RemexHtml\DOM\DOMFormatter;
use Wikimedia\RemexHtml\DOM\DOMUtils;
use Wikimedia\RemexHtml\HTMLData;

/**
 * A formatter which follows the HTML 5 fragment serialization algorithm.
 */
class HtmlFormatter implements Formatter, DOMFormatter {
	/**
	 * The elements for which a closing tag is omitted.
	 *
	 * @var array<string,bool>
	 */
	protected $voidElements = [
		'area' => true,
		'base' => true,
		'basefont' => true,
		'bgsound' => true,
		'br' => true,
		'col' => true,
		'embed' => true,
		'frame' => true,
		'hr' => true,
		'img' => true,
		'input' => true,
		'keygen' => true,
		'link' => true,
		'menuitem' => true,
		'meta' => true,
		'param' => true,
		'source' => true,
		'track' => true,
		'wbr' => true,
	];

	/**
	 * The elements which need a leading newline in their contents to be
	 * duplicated, since the parser strips a leading newline.
	 *
	 * @var array<string,bool>
	 */
	protected $prefixLfElements = [
		'pre' => true,
		'textarea' => true,
		'listing' => true
	];

	/**
	 * The elements which have unescaped contents.
	 *
	 * @var array<string,bool>
	 */
	protected $rawTextElements = [
		'style' => true,
		'script' => true,
		'xmp' => true,
		'iframe' => true,
		'noembed' => true,
		'noframes' => true,
		'plaintext' => true,
	];

	/**
	 * The escape table for attribute values
	 *
	 * @var array<string,string>
	 */
	protected $attributeEscapes = [
		'&' => '&amp;',
		"\xc2\xa0" => '&nbsp;',
		'"' => '&quot;',
	];

	/**
	 * The escape table for text nodes
	 *
	 * @var array<string,string>
	 */
	protected $textEscapes = [
		'&' => '&amp;',
		"\xc2\xa0" => '&nbsp;',
		'<' => '&lt;',
		'>' => '&gt;',
	];

	/**
	 * Attribute namespaces which have unqualified local names
	 *
	 * @var array<string,bool>
	 */
	protected $unqualifiedNamespaces = [
		HTMLData::NS_HTML => true,
		HTMLData::NS_MATHML => true,
		HTMLData::NS_SVG => true,
	];

	protected $useSourceDoctype;
	protected $reverseCoercion;

	/**
	 * Constructor.
	 *
	 * @param array $options An associative array of options:
	 *   - scriptingFlag : Set this to false to disable scripting. True by default.
	 *   - useSourceDoctype : Emit the doctype used in the source. If this is
	 *     false or absent, an HTML doctype will be used.
	 *   - reverseCoercion : When formatting a DOM node, reverse the encoding
	 *     of invalid names. False by default.
	 */
	public function __construct( $options = [] ) {
		$options += [
			'scriptingFlag' => true,
			'useSourceDoctype' => false,
			'reverseCoercion' => false,
		];
		if ( $options['scriptingFlag'] ) {
			$this->rawTextElements['noscript'] = true;
		}
		$this->useSourceDoctype = $options['useSourceDoctype'];
		$this->reverseCoercion = $options['reverseCoercion'];
	}

	public function startDocument( $fragmentNamespace, $fragmentName ) {
		return "<!DOCTYPE html>";
	}

	public function characters( SerializerNode $parent, $text, $start, $length ) {
		$text = substr( $text, $start, $length );
		if ( $parent->namespace !== HTMLData::NS_HTML
			|| !isset( $this->rawTextElements[$parent->name] )
		) {
			$text = strtr( $text, $this->textEscapes );
		}
		return $text;
	}

	public function element( SerializerNode $parent, SerializerNode $node, $contents ) {
		$name = $node->name;
		$s = "<$name";
		foreach ( $node->attrs->getValues() as $attrName => $attrValue ) {
			$encValue = strtr( $attrValue, $this->attributeEscapes );
			$s .= " $attrName=\"$encValue\"";
		}
		$s .= '>';
		if ( $node->namespace === HTMLData::NS_HTML ) {
			if ( isset( $contents[0] ) && $contents[0] === "\n"
				&& isset( $this->prefixLfElements[$name] )
			) {
				$s .= "\n$contents</$name>";
			} elseif ( !isset( $this->voidElements[$name] ) ) {
				$s .= "$contents</$name>";
			}
		} else {
			$s .= "$contents</$name>";
		}
		return $s;
	}

	public function comment( SerializerNode $parent, $text ) {
		return "<!--$text-->";
	}

	public function doctype( $name, $public, $system ) {
		return '';
	}

	/**
	 * @param \DOMNode $node
	 * @return string
	 */
	public function formatDOMNode( $node ) {
		$contents = '';
		if ( $node->firstChild ) {
			foreach ( $node->childNodes as $child ) {
				$contents .= $this->formatDOMNode( $child );
			}
		}

		switch ( $node->nodeType ) {
			case XML_ELEMENT_NODE:
				'@phan-var \DOMElement $node'; /** @var \DOMElement $node */
				return $this->formatDOMElement( $node, $contents );

			case XML_DOCUMENT_NODE:
				if ( !$this->useSourceDoctype ) {
					return "<!DOCTYPE html>" . $contents;
				} else {
					return $contents;
				}

			case XML_DOCUMENT_FRAG_NODE:
				return $contents;

			case XML_TEXT_NODE:
				'@phan-var \DOMCharacterData $node'; /** @var \DOMCharacterData $node */
				$text = $node->data;
				$parent = $node->parentNode;
				if ( $parent->namespaceURI !== HTMLData::NS_HTML
					|| !isset( $this->rawTextElements[$parent->nodeName] )
				) {
					$text = strtr( $text, $this->textEscapes );
				}
				return $text;

			case XML_CDATA_SECTION_NODE:
				'@phan-var \DOMCdataSection $node'; /** @var \DOMCdataSection $node */
				$parent = $node->parentNode;
				if ( $parent->namespaceURI === HTMLData::NS_HTML ) {
					// CDATA is not allowed in HTML nodes
					return $node->data;
				} else {
					return "<![CDATA[{$node->data}]]>";
				}

			case XML_PI_NODE:
				'@phan-var \DOMProcessingInstruction $node'; /** @var \DOMProcessingInstruction $node */
				return "<?{$node->target} {$node->data}>";

			case XML_COMMENT_NODE:
				'@phan-var \DOMComment $node'; /** @var \DOMComment $node */
				return "<!--{$node->data}-->";

			case XML_DOCUMENT_TYPE_NODE:
				'@phan-var \DOMDocumentType $node'; /** @var \DOMDocumentType $node */
				if ( $this->useSourceDoctype ) {
					return "<!DOCTYPE {$node->name}>";
				} else {
					return '';
				}

			default:
				return '';
		}
	}

	/**
	 * @param \DOMElement $node
	 * @param string $contents
	 * @return string
	 */
	public function formatDOMElement( $node, $contents ) {
		$ns = $node->namespaceURI;
		if ( $ns === null
			|| isset( $this->unqualifiedNamespaces[$ns] )
			|| !( $node->prefix )
		) {
			$name = (string)$node->localName;
		} else {
			$name = $node->prefix . ':' . $node->localName;
		}
		if ( $this->reverseCoercion ) {
			$name = DOMUtils::uncoerceName( $name );
		}

		$s = '<' . $name;
		foreach ( $node->attributes as $attr ) {
			switch ( $attr->namespaceURI ) {
				case HTMLData::NS_XML:
					$attrName = 'xml:' . $attr->localName;
					break;
				case HTMLData::NS_XMLNS:
					if ( $attr->localName === 'xmlns' ) {
						$attrName = 'xmlns';
					} else {
						$attrName = 'xmlns:' . $attr->localName;
					}
					break;
				case HTMLData::NS_XLINK:
					$attrName = 'xlink:' . $attr->localName;
					break;
				default:
					if ( strlen( $attr->prefix ) ) {
						$attrName = $attr->prefix . ':' . $attr->localName;
					} else {
						$attrName = $attr->localName;
					}
			}
			if ( $this->reverseCoercion ) {
				$attrName = DOMUtils::uncoerceName( $attrName );
			}
			$encValue = strtr( $attr->value, $this->attributeEscapes );
			$s .= " $attrName=\"$encValue\"";
		}
		$s .= '>';
		if ( $ns === HTMLData::NS_HTML ) {
			if ( isset( $contents[0] ) && $contents[0] === "\n"
				&& isset( $this->prefixLfElements[$name] )
			) {
				$s .= "\n$contents</$name>";
			} elseif ( !isset( $this->voidElements[$name] ) ) {
				$s .= "$contents</$name>";
			}
		} else {
			$s .= "$contents</$name>";
		}
		return $s;
	}
}
