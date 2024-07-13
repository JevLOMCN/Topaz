<?php

namespace Wikimedia\Purtle;

/**
 * Helper class for turning non-ascii characters into Python-style unicode escape sequences.
 *
 * @author Daniel Kinzler
 *
 * Most of this class was copied from EasyRdf's Ntriples.php.
 * The following licensing terms apply to the copied code:
 *
 * Copyright (c) 2009-2013 Nicholas J Humfrey. All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 * 1. Redistributions of source code must retain the above copyright
 *    notice, this list of conditions and the following disclaimer.
 * 2. Redistributions in binary form must reproduce the above copyright notice,
 *    this list of conditions and the following disclaimer in the documentation
 *    and/or other materials provided with the distribution.
 * 3. The name of the author 'Nicholas J Humfrey" may be used to endorse or
 *    promote products derived from this software without specific prior
 *    written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS"
 * AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE
 * IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE
 * ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE
 * LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR
 * CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF
 * SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS
 * INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN
 * CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
 * ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
 * POSSIBILITY OF SUCH DAMAGE.
 *
 * @copyright Copyright (c) 2009-2013 Nicholas J Humfrey
 * @license http://www.opensource.org/licenses/bsd-license.php BSD-2-Clause
 */
class UnicodeEscaper {

	/**
	 * @var string[] Character encoding cache
	 */
	private $escChars = [];

	/**
	 * @param string $str
	 *
	 * @return string
	 */
	public function escapeString( $str ) {
		$result = '';
		$strLen = mb_strlen( $str, 'UTF-8' );
		for ( $i = 0; $i < $strLen; $i++ ) {
			$c = mb_substr( $str, $i, 1, 'UTF-8' );
			if ( !isset( $this->escChars[$c] ) ) {
				$this->escChars[$c] = $this->escapedChar( $c );
			}
			$result .= $this->escChars[$c];
		}
		return $result;
	}

	/**
	 * @param string $cUtf
	 *
	 * @return int
	 */
	private function unicodeCharNo( $cUtf ) {
		$bl = strlen( $cUtf ); /* binary length */
		$r = 0;
		switch ( $bl ) {
			case 1: /* 0####### (0-127) */
				$r = ord( $cUtf );
				break;
			case 2: /* 110##### 10###### = 192+x 128+x */
				$r = ( ( ord( $cUtf[0] ) - 192 ) * 64 ) +
					( ord( $cUtf[1] ) - 128 );
				break;
			case 3: /* 1110#### 10###### 10###### = 224+x 128+x 128+x */
				$r = ( ( ord( $cUtf[0] ) - 224 ) * 4096 ) +
					( ( ord( $cUtf[1] ) - 128 ) * 64 ) +
					( ord( $cUtf[2] ) - 128 );
				break;
			case 4: /* 1111#### 10###### 10###### 10###### = 240+x 128+x 128+x 128+x */
				$r = ( ( ord( $cUtf[0] ) - 240 ) * 262144 ) +
					( ( ord( $cUtf[1] ) - 128 ) * 4096 ) +
					( ( ord( $cUtf[2] ) - 128 ) * 64 ) +
					( ord( $cUtf[3] ) - 128 );
				break;
		}
		return $r;
	}

	/**
	 * @param string $c
	 *
	 * @return string
	 */
	private function escapedChar( $c ) {
		$no = $this->unicodeCharNo( $c );
		/* see http://www.w3.org/TR/rdf-testcases/#ntrip_strings */
		if ( $no < 9 ) {
			return '\u' . sprintf( '%04X', $no ); /* #x0-#x8 (0-8) */
		} elseif ( $no == 9 ) {
			return '\t'; /* #x9 (9) */
		} elseif ( $no == 10 ) {
			return '\n'; /* #xA (10) */
		} elseif ( $no < 13 ) {
			return '\u' . sprintf( '%04X', $no ); /* #xB-#xC (11-12) */
		} elseif ( $no == 13 ) {
			return '\r'; /* #xD (13) */
		} elseif ( $no < 32 ) {
			return '\u' . sprintf( '%04X', $no ); /* #xE-#x1F (14-31) */
		} elseif ( $no < 127 ) {
			return $c; /* #x20-#x7E (32-126) */
		} elseif ( $no < 65536 ) {
			return '\u' . sprintf( '%04X', $no ); /* #x7F-#xFFFF (128-65535) */
		} elseif ( $no < 1114112 ) {
			return '\U' . sprintf( '%08X', $no ); /* #x10000-#x10FFFF (65536-1114111) */
		} else {
			return ''; /* not defined => ignore */
		}
	}

}
