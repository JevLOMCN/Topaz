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
 */

namespace Wikimedia\Equivset;

use ArrayIterator;
use IteratorAggregate;
use LogicException;
use Throwable;
use Wikimedia\Equivset\Exception\EquivsetException;

/**
 * Default Equivset
 */
class Equivset implements EquivsetInterface, IteratorAggregate {

	/**
	 * @var array<string,string>
	 */
	protected array $data;

	/**
	 * @var string
	 */
	protected string $serializedPath;

	/**
	 * Equivset
	 *
	 * @param array<string,string> $data Equivalent Set
	 * @param string $serializedPath Path of the serialized equivset array.
	 */
	public function __construct( array $data = [], string $serializedPath = '' ) {
		$this->data = $data;
		$this->serializedPath = $serializedPath ?: __DIR__ . '/../dist/equivset.php';
	}

	/**
	 * Get the equivset.
	 *
	 * @return array<string,string> An associative array of equivalent characters.
	 */
	public function all(): array {
		if ( !$this->data ) {
			$this->data = $this->load();
		}

		return $this->data;
	}

	/**
	 * {@inheritdoc}
	 *
	 * @param string $value The string to normalize against the equivset.
	 * @return string
	 */
	public function normalize( string $value ): string {
		$data = $this->all();

		return strtr( $value, $data );
	}

	/**
	 * {@inheritdoc}
	 *
	 * @param string $str1 The first string.
	 * @param string $str2 The second string.
	 *
	 * @return bool
	 */
	public function isEqual( string $str1, string $str2 ): bool {
		return $this->normalize( $str1 ) === $this->normalize( $str2 );
	}

	/**
	 * {@inheritdoc}
	 *
	 * @param string $key The character that was used.
	 * @return bool If the character has an equivalent.
	 */
	public function has( string $key ): bool {
		$data = $this->all();

		return array_key_exists( $key, $data );
	}

	/**
	 * {@inheritdoc}
	 *
	 * @param string $key The character that was used.
	 * @return string The equivalent character.
	 * @throws LogicException If character does not exist.
	 */
	public function get( string $key ): string {
		$data = $this->all();

		if ( !array_key_exists( $key, $data ) ) {
			throw new LogicException( 'Equivalent Character Not Found' );
		}

		return $data[$key];
	}

	/**
	 * {@inheritdoc}
	 *
	 * @return ArrayIterator The complete Equivset.
	 */
	public function getIterator(): ArrayIterator {
		return new ArrayIterator( $this->all() );
	}

	/**
	 * Get the equivset.
	 *
	 * @return array<string,string> An associative array of equivalent characters.
	 * @throws Throwable If the serialized equivset file is unreadable.
	 */
	protected function load(): array {
		if ( pathinfo( $this->serializedPath, PATHINFO_EXTENSION ) === 'php' ) {
			// This will naturally throw if the file does not exist, is not readable,
			// or can't be parsed.
			return require $this->serializedPath;
		}

		// file_get_contents() will not fail at this point since none of the
		// conditions that can cause a failure can happen at this point.
		// See http://php.net/manual/en/function.file-get-contents.php

		// phpcs:ignore Generic.PHP.NoSilencedErrors.Discouraged
		$contents = @file_get_contents( $this->serializedPath );
		if ( $contents === false ) {
			throw new EquivsetException( 'Serialized equivset file is unreadable' );
		}

		$data = unserialize( $contents );
		if ( $data === false ) {
			throw new EquivsetException( 'Unserializing serialized equivset failed' );
		}

		return $data;
	}
}
