<?php
/**
 * © 2006-2007 Daniel Kinzler
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 * http://www.gnu.org/copyleft/gpl.html
 *
 * @file
 * @ingroup Extensions
 * @author Daniel Kinzler, brightbyte.de
 */

namespace MediaWiki\Extension\CategoryTree;

use FormatJson;
use InvalidArgumentException;
use MediaWiki\Config\Config;
use MediaWiki\MediaWikiServices;

/**
 * Core functions to handle the options
 */
class OptionManager {
	/** @var array */
	private $mOptions = [];

	/** @var Config */
	private $config;

	/**
	 * @param array $options
	 * @param Config $config
	 */
	public function __construct( array $options, Config $config ) {
		$this->config = $config;

		// ensure default values and order of options.
		// Order may become important, it may influence the cache key!
		foreach ( $config->get( 'CategoryTreeDefaultOptions' ) as $option => $default ) {
			$this->mOptions[$option] = $options[$option] ?? $default;
		}

		$this->mOptions['mode'] = $this->decodeMode( $this->mOptions['mode'] );

		if ( $this->mOptions['mode'] === CategoryTreeMode::PARENTS ) {
			// namespace filter makes no sense with CategoryTreeMode::PARENTS
			$this->mOptions['namespaces'] = false;
		}

		$this->mOptions['hideprefix'] = $this->decodeHidePrefix( $this->mOptions['hideprefix'] );
		$this->mOptions['showcount'] = self::decodeBoolean( $this->mOptions['showcount'] );
		$this->mOptions['namespaces'] = self::decodeNamespaces( $this->mOptions['namespaces'] );

		if ( $this->mOptions['namespaces'] ) {
			# automatically adjust mode to match namespace filter
			if ( count( $this->mOptions['namespaces'] ) === 1
				&& $this->mOptions['namespaces'][0] === NS_CATEGORY ) {
				$this->mOptions['mode'] = CategoryTreeMode::CATEGORIES;
			} elseif ( !in_array( NS_FILE, $this->mOptions['namespaces'] ) ) {
				$this->mOptions['mode'] = CategoryTreeMode::PAGES;
			} else {
				$this->mOptions['mode'] = CategoryTreeMode::ALL;
			}
		}
	}

	/**
	 * @return array
	 */
	public function getOptions() {
		return $this->mOptions;
	}

	/**
	 * @param string $name
	 * @return mixed
	 */
	public function getOption( $name ) {
		return $this->mOptions[$name];
	}

	/**
	 * @return bool
	 */
	public function isInverse() {
		return $this->getOption( 'mode' ) === CategoryTreeMode::PARENTS;
	}

	/**
	 * @param mixed $nn
	 * @return array|bool
	 */
	private static function decodeNamespaces( $nn ) {
		if ( $nn === false || $nn === null ) {
			return false;
		}

		if ( !is_array( $nn ) ) {
			$nn = preg_split( '![\s#:|]+!', $nn );
		}

		$namespaces = [];
		$contLang = MediaWikiServices::getInstance()->getContentLanguage();
		foreach ( $nn as $n ) {
			if ( is_int( $n ) ) {
				$ns = $n;
			} else {
				$n = trim( $n );
				if ( $n === '' ) {
					continue;
				}

				$lower = strtolower( $n );

				if ( is_numeric( $n ) ) {
					$ns = (int)$n;
				} elseif ( $n === '-' || $n === '_' || $n === '*' || $lower === 'main' ) {
					$ns = NS_MAIN;
				} else {
					$ns = $contLang->getNsIndex( $n );
				}
			}

			if ( is_int( $ns ) ) {
				$namespaces[] = $ns;
			}
		}

		# get elements into canonical order
		sort( $namespaces );
		return $namespaces;
	}

	/**
	 * @param mixed $mode
	 * @return int|string
	 */
	private function decodeMode( $mode ) {
		$defaultOptions = $this->config->get( 'CategoryTreeDefaultOptions' );

		if ( $mode === null ) {
			return $defaultOptions['mode'];
		}
		if ( is_int( $mode ) ) {
			return $mode;
		}

		$mode = trim( strtolower( $mode ) );

		if ( is_numeric( $mode ) ) {
			return (int)$mode;
		}

		if ( $mode === 'all' ) {
			$mode = CategoryTreeMode::ALL;
		} elseif ( $mode === 'pages' ) {
			$mode = CategoryTreeMode::PAGES;
		} elseif ( $mode === 'categories' || $mode === 'sub' ) {
			$mode = CategoryTreeMode::CATEGORIES;
		} elseif ( $mode === 'parents' || $mode === 'super' || $mode === 'inverse' ) {
			$mode = CategoryTreeMode::PARENTS;
		} elseif ( $mode === 'default' ) {
			$mode = $defaultOptions['mode'];
		}

		return (int)$mode;
	}

	/**
	 * Helper function to convert a string to a boolean value.
	 * Perhaps make this a global function in MediaWiki proper
	 * @param mixed $value
	 * @return bool|null|string
	 */
	public static function decodeBoolean( $value ) {
		if ( $value === null ) {
			return null;
		}
		if ( is_bool( $value ) ) {
			return $value;
		}
		if ( is_int( $value ) ) {
			return ( $value > 0 );
		}

		$value = trim( strtolower( $value ) );
		if ( is_numeric( $value ) ) {
			return ( (int)$value > 0 );
		}

		if ( $value === 'yes' || $value === 'y'
			|| $value === 'true' || $value === 't' || $value === 'on'
		) {
			return true;
		} elseif ( $value === 'no' || $value === 'n'
			|| $value === 'false' || $value === 'f' || $value === 'off'
		) {
			return false;
		} elseif ( $value === 'null' || $value === 'default' || $value === 'none' || $value === 'x' ) {
			return null;
		} else {
			return false;
		}
	}

	/**
	 * @param mixed $value
	 * @return int|string
	 */
	private function decodeHidePrefix( $value ) {
		$defaultOptions = $this->config->get( 'CategoryTreeDefaultOptions' );

		if ( $value === null ) {
			return $defaultOptions['hideprefix'];
		}
		if ( is_int( $value ) ) {
			return $value;
		}
		if ( $value === true ) {
			return CategoryTreeHidePrefix::ALWAYS;
		}
		if ( $value === false ) {
			return CategoryTreeHidePrefix::NEVER;
		}

		$value = trim( strtolower( $value ) );

		if ( $value === 'yes' || $value === 'y'
			|| $value === 'true' || $value === 't' || $value === 'on'
		) {
			return CategoryTreeHidePrefix::ALWAYS;
		} elseif ( $value === 'no' || $value === 'n'
			|| $value === 'false' || $value === 'f' || $value === 'off'
		) {
			return CategoryTreeHidePrefix::NEVER;
		} elseif ( $value === 'always' ) {
			return CategoryTreeHidePrefix::ALWAYS;
		} elseif ( $value === 'never' ) {
			return CategoryTreeHidePrefix::NEVER;
		} elseif ( $value === 'auto' ) {
			return CategoryTreeHidePrefix::AUTO;
		} elseif ( $value === 'categories' || $value === 'category' || $value === 'smart' ) {
			return CategoryTreeHidePrefix::CATEGORIES;
		} else {
			return $defaultOptions['hideprefix'];
		}
	}

	/**
	 * @param array $options
	 * @param string $enc
	 * @return mixed
	 */
	private static function encodeOptions( array $options, $enc ) {
		if ( $enc === 'mode' || $enc === '' ) {
			$opt = $options['mode'];
		} elseif ( $enc === 'json' ) {
			$opt = FormatJson::encode( $options );
		} else {
			throw new InvalidArgumentException( 'Unknown encoding for CategoryTree options: ' . $enc );
		}

		return $opt;
	}

	/**
	 * @param int|null $depth
	 * @return string
	 */
	public function getOptionsAsCacheKey( $depth = null ) {
		$key = '';

		foreach ( $this->mOptions as $k => $v ) {
			if ( is_array( $v ) ) {
				$v = implode( '|', $v );
			}
			$key .= $k . ':' . $v . ';';
		}

		if ( $depth !== null ) {
			$key .= ';depth=' . $depth;
		}
		return $key;
	}

	/**
	 * @param int|null $depth
	 * @return mixed
	 */
	public function getOptionsAsJsStructure( $depth = null ) {
		$opt = $this->mOptions;
		if ( $depth !== null ) {
			$opt['depth'] = $depth;
		}

		return self::encodeOptions( $opt, 'json' );
	}

	/**
	 * Internal function to cap depth
	 * @param int $depth
	 * @return int|mixed
	 */
	public function capDepth( $depth ) {
		if ( !is_numeric( $depth ) ) {
			return 1;
		}

		$mode = $this->getOption( 'mode' );
		$depth = intval( $depth );
		$maxDepth = $this->config->get( 'CategoryTreeMaxDepth' );

		if ( is_array( $maxDepth ) ) {
			$max = $maxDepth[$mode] ?? 1;
		} elseif ( is_numeric( $maxDepth ) ) {
			$max = $maxDepth;
		} else {
			wfDebug( __METHOD__ . ': $wgCategoryTreeMaxDepth is invalid.' );
			$max = 1;
		}

		return min( $depth, $max );
	}
}
