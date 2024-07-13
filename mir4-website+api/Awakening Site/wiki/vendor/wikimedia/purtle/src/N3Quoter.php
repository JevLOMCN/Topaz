<?php

namespace Wikimedia\Purtle;

/**
 * Helper class for quoting literals and URIs in N3 output.
 * Optionally supports shorthand and prefix resolution.
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 */
class N3Quoter {

	/**
	 * @var UnicodeEscaper
	 */
	private $escaper = null;

	/**
	 * @param bool $escapeUnicode
	 */
	public function setEscapeUnicode( $escapeUnicode ) {
		$this->escaper = $escapeUnicode ? new UnicodeEscaper() : null;
	}

	/**
	 * @param string $iri
	 *
	 * @return string
	 */
	public function escapeIRI( $iri ) {
		// FIXME: apply unicode escaping?!
		return strtr( $iri, [
				' ' => '%20',
				'"' => '%22',
				'<' => '%3C',
				'>' => '%3E',
				'\\' => '%5C',
				'`' => '%60',
				'^' => '%5E',
				'|' => '%7C',
				'{' => '%7B',
				'}' => '%7D',
		] );
	}

	/**
	 * @param string $s
	 *
	 * @return string
	 */
	public function escapeLiteral( $s ) {
		// Performance: If the entire string is just (a safe subset) of ASCII, let it through.
		// Ok are space (31), ! (32), # (35) - [ (91) and ] (93) to ~ (126), excludes " (34) and \ (92).
		if ( preg_match( '/^[ !#-[\]-~]*\z/', $s ) ) {
			return $s;
		}

		// String escapes. Note that the N3 spec is more restrictive than the Turtle and TR
		// specifications, see <https://www.w3.org/TeamSubmission/n3/#escaping>
		// and <https://www.w3.org/TR/turtle/#string>
		// and <https://www.w3.org/TR/n-triples/#grammar-production-literal>.
		// Allowed escapes according to the N3 spec are:
		// ECHAR	::=	'\' [tbnrf"'\]
		// The single quote however does not require escaping when used in double quotes.
		$escaped = strtr( $s, [
			"\x00" => '\u0000',
			"\x01" => '\u0001',
			"\x02" => '\u0002',
			"\x03" => '\u0003',
			"\x04" => '\u0004',
			"\x05" => '\u0005',
			"\x06" => '\u0006',
			"\x07" => '\u0007',
			"\x08" => '\b',
			"\x09" => '\t',
			"\x0A" => '\n',
			"\x0B" => '\u000B',
			"\x0C" => '\f',
			"\x0D" => '\r',
			"\x0E" => '\u000E',
			"\x0F" => '\u000F',
			"\x10" => '\u0010',
			"\x11" => '\u0011',
			"\x12" => '\u0012',
			"\x13" => '\u0013',
			"\x14" => '\u0014',
			"\x15" => '\u0015',
			"\x16" => '\u0016',
			"\x17" => '\u0017',
			"\x18" => '\u0018',
			"\x19" => '\u0019',
			"\x1A" => '\u001A',
			"\x1B" => '\u001B',
			"\x1C" => '\u001C',
			"\x1D" => '\u001D',
			"\x1E" => '\u001E',
			"\x1F" => '\u001F',
			'"' => '\"',
			'\\' => '\\\\',
		] );

		if ( $this->escaper !== null ) {
			$escaped = $this->escaper->escapeString( $escaped );
		}

		return $escaped;
	}

}
