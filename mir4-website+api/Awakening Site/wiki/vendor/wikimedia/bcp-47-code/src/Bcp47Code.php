<?php
/**
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
 */

namespace Wikimedia\Bcp47Code;

/**
 * This interface defines an opaque object representing a language.
 * The language can return a standardized IETF BCP 47 language tag
 * representing itself.
 *
 * It is recommended that the internal language class in your code
 * implement the Bcp47Code interface, and that you provide a mechanism
 * that will accept a Bcp47Code and return an appropriate instance of
 * your internal language code.
 *
 * For example:
 * <pre>
 * use Wikimedia\Bcp47Code\Bcp47Code;
 *
 * class MyLanguage implements Bcp47Code {
 *    public function toBcp47Code(): string {
 *      return $this->code;
 *    }
 *    public static function fromBcp47(Bcp47Code $code): MyLanguage {
 *      if ($code instanceof MyLanguage) {
 *         return $code;
 *      }
 *      return new MyLanguage($code->toBcp47Code());
 *    }
 *    public function isSameCodeAs( Bcp47Code $other ): bool {
 *      if ( $other instanceof MyLanguage ) {
 *         // implement optimized MyLanguage-specific comparison
 *      }
 *      return strcasecmp( $this->toBcp47Code(), $other->toBcp47Code() ) === 0;
 *    }
 * }
 * </pre>
 */
interface Bcp47Code {

	/**
	 * @return string a standardized IETF BCP 47 language tag
	 */
	public function toBcp47Code(): string;

	/**
	 * Compare two Bcp47Code objects.  Note that BCP 47 codes are case
	 * insensitive, so if this comparison is going to use ::toBcp47Code()
	 * ensure the comparison is case insensitive.
	 *
	 * @param Bcp47Code $other The language tag to compare to
	 * @return bool true if this language tag is the same as the given one
	 */
	public function isSameCodeAs( Bcp47Code $other ): bool;
}
