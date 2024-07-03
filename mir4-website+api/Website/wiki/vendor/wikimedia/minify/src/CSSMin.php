<?php
/**
 * Copyright 2010 Trevor Parscal <tparscal@wikimedia.org>
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 *
 * @file
 * @license Apache-2.0
 */

namespace Wikimedia\Minify;

use Net_URL2;

/**
 * Transforms CSS data.
 *
 * This class provides minification, URL remapping, URL extracting, and data-URL embedding.
 */
class CSSMin {

	/**
	 * Strip marker for comments
	 */
	private const PLACEHOLDER = "\x7fPLACEHOLDER\x7f";

	/**
	 * Maximum file size to embed in a stylesheet.
	 *
	 * Use of `@embed` is generally discouraged (see T121730, T118514, and T120984).
	 *
	 * In a nut shell: Stylesheets, and thus any images we embed inside of them,
	 * are required to be fully downloaded by a browser before literally any content
	 * or interface component can be rendered on-screen. Embedding an image means
	 * you are saying it is more important to render this image than to render the
	 * page content (or that it is undesirable to render even the first paragraph
	 * of an article unless this image is also immediately there in the first paint).
	 *
	 * There are some cases where a small image can be worth embedding, for example
	 * if the image's file size is comparable or smaller than to size of the URL from
	 * which it would be lazy-loaded when CSS rules are applied. In that case, embedding
	 * the file content in place of the web address could be an uncompromising win for
	 * all users.
	 *
	 * To avoid mistakes and protect performance we put an arbitrary limit on this.
	 * If a file is larger than this limit, it should probably be loaded using the
	 * default CSS behaviour (by URL, without `@embed` instruction), or embedded as
	 * SVG directly in the HTML response if it is such a vital part of the page.
	 *
	 * See encodeImageAsDataURI().
	 */
	private const DATA_URI_SIZE_LIMIT = 32768;

	private const EMBED_REGEX = '\/\*\s*\@embed\s*\*\/';
	private const COMMENT_REGEX = '\/\*.*?\*\/';

	/**
	 * List of common image files extensions and MIME-types
	 */
	private const MIME_TYPES = [
		'gif' => 'image/gif',
		'jpe' => 'image/jpeg',
		'jpeg' => 'image/jpeg',
		'jpg' => 'image/jpeg',
		'png' => 'image/png',
		'tif' => 'image/tiff',
		'tiff' => 'image/tiff',
		'xbm' => 'image/x-xbitmap',
		'svg' => 'image/svg+xml',
	];

	/**
	 * Get a list of local files referenced in a stylesheet (includes non-existent files).
	 *
	 * @param string $source CSS stylesheet source to process
	 * @param string $path File path where the source was read from
	 * @return string[] List of local file references
	 */
	public static function getLocalFileReferences( $source, $path ) {
		$stripped = preg_replace( '/' . self::COMMENT_REGEX . '/s', '', $source );
		$path = rtrim( $path, '/' ) . '/';
		$files = [];

		$rFlags = PREG_OFFSET_CAPTURE | PREG_SET_ORDER;
		if ( preg_match_all( '/' . self::getUrlRegex() . '/J', $stripped, $matches, $rFlags ) ) {
			foreach ( $matches as $match ) {
				$url = $match['file'][0];

				// Skip fully-qualified and protocol-relative URLs and data URIs
				if (
					strpos( $url, '//' ) === 0 ||
					parse_url( $url, PHP_URL_SCHEME )
				) {
					break;
				}

				// Strip trailing anchors - T115436
				$anchor = strpos( $url, '#' );
				if ( $anchor !== false ) {
					$url = substr( $url, 0, $anchor );

					// '#some-anchors' is not a file
					if ( $url === '' ) {
						break;
					}
				}

				$files[] = $path . $url;
			}
		}
		return $files;
	}

	/**
	 * Encode an image file as a data URI.
	 *
	 * If the image file has a suitable MIME type and size, encode it as a data URI, base64-encoded
	 * for binary files or just percent-encoded otherwise. Return false if the image type is
	 * unfamiliar or file exceeds the size limit.
	 *
	 * @param string $file Image file to encode.
	 * @param string|null $type File's MIME type or null. If null, CSSMin will
	 *     try to autodetect the type.
	 * @return string|false Image contents encoded as a data URI or false.
	 */
	public static function encodeImageAsDataURI( $file, $type = null ) {
		// Fast-fail for files that exceed the maximum data URI length
		if ( filesize( $file ) >= self::DATA_URI_SIZE_LIMIT ) {
			trigger_error( "File exceeds limit for embedded files: $file", E_USER_WARNING );
			return false;
		}

		if ( $type === null ) {
			$type = self::getMimeType( $file );
		}
		if ( !$type ) {
			return false;
		}

		return self::encodeStringAsDataURI( file_get_contents( $file ), $type );
	}

	/**
	 * Encode file contents as a data URI with chosen MIME type.
	 *
	 * The URI will be base64-encoded for binary files or just percent-encoded otherwise.
	 *
	 * @since 2.0.0
	 * @param string $contents File contents to encode.
	 * @param string $type File's MIME type.
	 * @return string Image contents encoded as a data URI or false.
	 */
	public static function encodeStringAsDataURI( $contents, $type ) {
		// Try #1: Non-encoded data URI
		// Remove XML declaration, it's not needed with data URI usage
		$contents = preg_replace( "/<\\?xml.*?\\?>/", '', $contents );
		// The regular expression matches ASCII whitespace and printable characters.
		if ( preg_match( '/^[\r\n\t\x20-\x7e]+$/', $contents ) ) {
			// Do not base64-encode non-binary files (sensible SVGs).
			// (This often produces longer URLs, but they compress better, yielding a net smaller size.)
			$encoded = rawurlencode( $contents );
			// Unencode some things that don't need to be encoded, to make the encoding smaller
			$encoded = strtr( $encoded, [
				'%20' => ' ', // Unencode spaces
				'%2F' => '/', // Unencode slashes
				'%3A' => ':', // Unencode colons
				'%3D' => '=', // Unencode equals signs
				'%0A' => ' ', // Change newlines to spaces
				'%0D' => ' ', // Change carriage returns to spaces
				'%09' => ' ', // Change tabs to spaces
			] );
			// Consolidate runs of multiple spaces in a row
			$encoded = preg_replace( '/ {2,}/', ' ', $encoded );
			// Remove leading and trailing spaces
			$encoded = trim( $encoded, ' ' );
			return 'data:' . $type . ',' . $encoded;
		}

		// Try #2: Encoded data URI
		return 'data:' . $type . ';base64,' . base64_encode( $contents );
	}

	/**
	 * Serialize a string (escape and quote) for use as a CSS string value.
	 * https://drafts.csswg.org/cssom/#serialize-a-string
	 *
	 * @param string $value
	 * @return string
	 */
	public static function serializeStringValue( $value ) {
		$value = strtr( $value, [ "\0" => "\u{FFFD}", '\\' => '\\\\', '"' => '\\"' ] );
		$value = preg_replace_callback( '/[\x01-\x1f\x7f]/', static function ( $match ) {
			return '\\' . base_convert( (string)ord( $match[0] ), 10, 16 ) . ' ';
		}, $value );
		return '"' . $value . '"';
	}

	/**
	 * @param string $file
	 * @return bool|string
	 */
	public static function getMimeType( $file ) {
		// Infer the MIME-type from the file extension
		$ext = strtolower( pathinfo( $file, PATHINFO_EXTENSION ) );
		return self::MIME_TYPES[$ext] ?? mime_content_type( realpath( $file ) );
	}

	/**
	 * Build a CSS 'url()' value for the given URL, quoting parentheses (and other funny characters)
	 * and escaping quotes as necessary.
	 *
	 * See http://www.w3.org/TR/css-syntax-3/#consume-a-url-token
	 *
	 * @param string $url URL to process
	 * @return string 'url()' value, usually just `"url($url)"`, quoted/escaped if necessary
	 */
	public static function buildUrlValue( $url ) {
		// The list below has been crafted to match URLs such as:
		//   scheme://user@domain:port/~user/fi%20le.png?query=yes&really=y+s
		//   data:image/png;base64,R0lGODlh/+==
		if ( preg_match( '!^[\w:@/~.%+;,?&=-]+$!', $url ) ) {
			return "url($url)";
		} else {
			return 'url("' . strtr( $url, [ '\\' => '\\\\', '"' => '\\"' ] ) . '")';
		}
	}

	/**
	 * Remaps CSS URL paths and automatically embeds data URIs for CSS rules
	 * or url() values preceded by an `/ * @embed * /` comment.
	 *
	 * @param string $source CSS data to remap
	 * @param string $local File path where the source was read from
	 * @param string $remote Full URL to the file's directory (may be protocol-relative, trailing slash is optional)
	 * @param bool $embedData If false, never do any data URI embedding,
	 *   even if `/ * @embed * /` is found.
	 * @return string Remapped CSS data
	 */
	public static function remap( $source, $local, $remote, $embedData = true ) {
		// High-level overview:
		// * For each CSS rule in $source that includes at least one url() value:
		//   * Check for an @embed comment at the start indicating that all URIs should be embedded
		//   * For each url() value:
		//     * Check for an @embed comment directly preceding the value
		//     * If either @embed comment exists:
		//       * Embedding the URL as data: URI, if it's possible / allowed
		//       * Otherwise remap the URL to work in generated stylesheets

		// Guard against trailing slashes, because "some/remote/../foo.png"
		// resolves to "some/remote/foo.png" on (some?) clients (T29052).
		// But, don't turn "/" into "" since that is invalid as base URL (T282280).
		$remote = $remote === '/' ? $remote : rtrim( $remote, '/' );

		// Disallow U+007F DELETE, which is illegal anyway, and which
		// we use for comment placeholders.
		$source = strtr( $source, "\x7f", "?" );

		// Replace all comments by a placeholder so they will not interfere with the remapping.
		// Warning: This will also catch on anything looking like the start of a comment between
		// quotation marks (e.g. "foo /* bar").
		$comments = [];

		$pattern = '/(?!' . self::EMBED_REGEX . ')(' . self::COMMENT_REGEX . ')/s';

		$source = preg_replace_callback(
			$pattern,
			static function ( $match ) use ( &$comments ) {
				$comments[] = $match[ 0 ];
				return self::PLACEHOLDER . ( count( $comments ) - 1 ) . 'x';
			},
			$source
		);

		// Note: This will not correctly handle cases where ';', '{' or '}'
		// appears in the rule itself, e.g. in a quoted string. You are advised
		// not to use such characters in file names. We also match start/end of
		// the string to be consistent in edge-cases ('@import url(…)').
		$pattern = '/(?:^|[;{])\K[^;{}]*' . self::getUrlRegex() . '[^;}]*(?=[;}]|$)/J';

		$source = preg_replace_callback(
			$pattern,
			static function ( $matchOuter ) use ( $local, $remote, $embedData ) {
				$rule = $matchOuter[0];

				// Check for global @embed comment and remove it. Allow other comments to be present
				// before @embed (they have been replaced with placeholders at this point).
				$embedAll = false;
				$rule = preg_replace(
					'/^((?:\s+|' .
						self::PLACEHOLDER .
						'(\d+)x)*)' .
						self::EMBED_REGEX .
						'\s*/',
					'$1',
					$rule,
					1,
					$embedAll
				);

				// Build two versions of current rule: with remapped URLs
				// and with embedded data: URIs (where possible).
				$pattern = '/(?P<embed>' . self::EMBED_REGEX . '\s*|)' . self::getUrlRegex() . '/J';

				return preg_replace_callback(
					$pattern,
					static function ( $match ) use ( $local, $remote, $embedData, $embedAll ) {
						// For each url reference in the CSS, call remapOne()
						// to insert either a remapped URL or an embedded data URI.
						// We enable use of data URIs if both of these are true:
						// 1. The PHP caller enabled the option,
						// 2. and, the CSS declared `@embed` either above the rule
						//    for all URLs inside that block, or above the individual
						//    property for that one property.
						$embedOne = $embedData && ( $embedAll || $match['embed'] );
						$remapped = self::remapOne(
							$match['file'],
							$match['query'],
							$local,
							$remote,
							$embedOne
						);
						return self::buildUrlValue( $remapped );
					},
					$rule
				);
			}, $source );

		// Re-insert comments
		$pattern = '/' . self::PLACEHOLDER . '(\d+)x/';
		return preg_replace_callback( $pattern, static function ( $match ) use ( &$comments ) {
			return $comments[ $match[1] ];
		}, $source );
	}

	/**
	 * Is this CSS rule referencing a remote URL?
	 *
	 * @param string $maybeUrl
	 * @return bool
	 */
	protected static function isRemoteUrl( $maybeUrl ) {
		return strpos( $maybeUrl, '//' ) === 0 || parse_url( $maybeUrl, PHP_URL_SCHEME );
	}

	/**
	 * Is this CSS rule referencing a local URL?
	 *
	 * @param string $maybeUrl
	 * @return bool
	 */
	protected static function isLocalUrl( $maybeUrl ) {
		// Accept "/" (known local)
		// Accept "/anything" (known local)
		// Reject "//anything" (known remote)
		// Reject "" (invalid/uncertain)
		return $maybeUrl === '/' || ( isset( $maybeUrl[1] ) && $maybeUrl[0] === '/' && $maybeUrl[1] !== '/' );
	}

	/**
	 * @codeCoverageIgnore
	 * @return string
	 */
	private static function getUrlRegex() {
		static $urlRegex;
		if ( $urlRegex === null ) {
			$urlRegex = '(' .
				// Unquoted url
				'url\(\s*(?P<file>[^\s\'"][^\?\)]+?)(?P<query>\?[^\)]*?|)\s*\)' .
				// Single quoted url
				'|url\(\s*\'(?P<file>[^\?\']+?)(?P<query>\?[^\']*?|)\'\s*\)' .
				// Double quoted url
				'|url\(\s*"(?P<file>[^\?"]+?)(?P<query>\?[^"]*?|)"\s*\)' .
				')';
		}
		return $urlRegex;
	}

	/**
	 * Resolve a possibly-relative URL against a base URL.
	 *
	 * @param string $base
	 * @param string $url
	 * @return string
	 */
	private static function resolveUrl( string $base, string $url ): string {
		// Net_URL2::resolve() doesn't allow for resolving against server-less URLs.
		// We need this as for MediaWiki/ResourceLoader, the remote base path may either
		// be separate (e.g. a separate domain), or simply local (like "/w"). In the
		// local case, we don't want to needlessly include the server in the output.
		$isServerless = self::isLocalUrl( $base );
		if ( $isServerless ) {
			$base = "https://placeholder.invalid$base";
		}
		// Net_URL2::resolve() doesn't allow for protocol-relative URLs, but we want to.
		$isProtoRelative = strpos( $base, '//' ) === 0;
		if ( $isProtoRelative ) {
			$base = "https:$base";
		}

		$baseUrl = new Net_URL2( $base );
		$ret = $baseUrl->resolve( $url );
		if ( $isProtoRelative ) {
			$ret->setScheme( false );
		}
		if ( $isServerless ) {
			$ret->setScheme( false );
			$ret->setHost( false );
		}
		return $ret->getURL();
	}

	/**
	 * Remap or embed a CSS URL path.
	 *
	 * @param string $file URL to remap/embed
	 * @param string $query
	 * @param string $local File path where the source was read from
	 * @param string $remote Full URL to the file's directory (may be protocol-relative, trailing slash is optional)
	 * @param bool $embed Whether to do any data URI embedding
	 * @return string Remapped/embedded URL data
	 */
	public static function remapOne( $file, $query, $local, $remote, $embed ) {
		// The full URL possibly with query, as passed to the 'url()' value in CSS
		$url = $file . $query;

		// Expand local URLs with absolute paths to a full URL (possibly protocol-relative).
		if ( self::isLocalUrl( $url ) ) {
			return self::resolveUrl( $remote, $url );
		}

		// Pass through fully-qualified and protocol-relative URLs and data URIs, as well as local URLs if
		// we can't expand them.
		// Also skips anchors or the rare `behavior` property specifying application's default behavior
		if (
			self::isRemoteUrl( $url ) ||
			( $url[0] ?? '' ) === '#'
		) {
			return $url;
		}

		// The $remote must have a trailing slash beyond this point for correct path resolution.
		if ( ( $remote[-1] ?? '' ) !== '/' ) {
			$remote .= '/';
		}

		if ( $local === false ) {
			// CSS specifies a path that is neither a local file path, nor a local URL.
			// It is probably already a fully-qualitied URL or data URI, but try to expand
			// it just in case.
			$url = self::resolveUrl( $remote, $url );
		} else {
			// We drop the query part here and instead make the path relative to $remote
			$url = self::resolveUrl( $remote, $file );
			// Path to the actual file on the filesystem
			$localFile = "{$local}/{$file}";
			if ( file_exists( $localFile ) ) {
				if ( $embed ) {
					$data = self::encodeImageAsDataURI( $localFile );
					if ( $data !== false ) {
						return $data;
					}
				}
				// Add version parameter as the first five hex digits
				// of the MD5 hash of the file's contents.
				$url .= '?' . substr( md5_file( $localFile ), 0, 5 );
			}
			// If any of these conditions failed (file missing, we don't want to embed it
			// or it's not embeddable), return the URL (possibly with ?timestamp part)
		}
		return $url;
	}

	/**
	 * Removes whitespace from CSS data
	 *
	 * @param string $css CSS data to minify
	 * @return string Minified CSS data
	 */
	public static function minify( $css ) {
		return trim(
			str_replace(
				[ '; ', ': ', ' {', '{ ', ', ', '} ', ';}', '( ', ' )', '[ ', ' ]' ],
				[ ';', ':', '{', '{', ',', '}', '}', '(', ')', '[', ']' ],
				preg_replace( [ '/\s+/', '/\/\*.*?\*\//s' ], [ ' ', '' ], $css )
			)
		);
	}
}

/**
 * @deprecated since 2.1.0 Use Wikimedia\Minify\CSSMin instead
 */
class_alias( CSSMin::class, 'CSSMin' );
