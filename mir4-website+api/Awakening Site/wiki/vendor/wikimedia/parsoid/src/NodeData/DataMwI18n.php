<?php
declare( strict_types = 1 );

namespace Wikimedia\Parsoid\NodeData;

/**
 * data-mw-i18n information, used for internationalization. This data is used to represent the
 * information necessary for later localization of messages (in spans) or element attributes values.
 */
class DataMwI18n implements \JsonSerializable {

	/** @var array */
	private $i18nInfo = [];

	/**
	 * Get the I18nInfo associated to a span (which will be used to fill in the span content) or
	 * null, if non-existent.
	 * @return I18nInfo|null
	 */
	public function getSpanInfo(): ?I18nInfo {
		return $this->i18nInfo['/'] ?? null;
	}

	/**
	 * Get the I18nInfo that will be used to localize an element attribute value with the name
	 * $name or null, if non-existent.
	 * @param string $name
	 * @return I18nInfo|null
	 */
	public function getAttributeInfo( string $name ): ?I18nInfo {
		return $this->i18nInfo[$name] ?? null;
	}

	/**
	 * Get the name of the localized attributes or an empty array if no localized attributes
	 * @return array
	 */
	public function getAttributeNames(): array {
		$res = [];
		foreach ( $this->i18nInfo as $k => $v ) {
			if ( $k !== '/' ) {
				$res[] = $k;
			}
		}
		return $res;
	}

	/**
	 * Defines the internationalization parameters of a string contained in a span.
	 */
	public function setSpanInfo( I18nInfo $info ) {
		$this->i18nInfo['/'] = $info;
	}

	/**
	 * Defines the internationalization parameters of the $name attribute's value.
	 */
	public function setAttributeInfo( string $name, I18nInfo $info ) {
		$this->i18nInfo[$name] = $info;
	}

	public function jsonSerialize(): array {
		return $this->i18nInfo;
	}

	public static function fromJson( array $json ): DataMwI18n {
		$i18n = new DataMwI18n();
		foreach ( $json as $k => $v ) {
			$i18n->i18nInfo[$k] = new I18nInfo( $v['lang'], $v['key'], $v['params'] );
		}
		return $i18n;
	}
}
