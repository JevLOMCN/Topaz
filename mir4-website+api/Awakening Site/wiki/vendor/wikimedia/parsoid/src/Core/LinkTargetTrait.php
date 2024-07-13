<?php
declare( strict_types = 1 );

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
 * @author Addshore
 */
namespace Wikimedia\Parsoid\Core;

/**
 * Useful helpers for LinkTarget implementations.
 *
 * @see LinkTarget
 */
trait LinkTargetTrait {

	/**
	 * Convenience function to check if the target is in a given namespace.
	 *
	 * @param int $ns
	 * @return bool
	 */
	public function inNamespace( int $ns ): bool {
		'@phan-var LinkTarget $this';
		// Core may someday switch to an enumerated type, but this is
		// safe for now.
		return $this->getNamespace() === $ns;
	}

	/**
	 * Whether the link target has a fragment.
	 *
	 * @return bool
	 */
	public function hasFragment(): bool {
		'@phan-var LinkTarget $this';
		return $this->getFragment() !== '';
	}

	/**
	 * Get the main part of the link target, in text form.
	 *
	 * The main part is the link target without namespace prefix or hash fragment.
	 * The text form is used for display purposes.
	 *
	 * This is computed from the DB key by replacing any underscores with spaces.
	 *
	 * @note To get a title string that includes the namespace and/or fragment,
	 *       use a TitleFormatter.
	 *
	 * @return string
	 */
	public function getText(): string {
		'@phan-var LinkTarget $this';
		return strtr( $this->getDBKey(), '_', ' ' );
	}

	/**
	 * Whether this LinkTarget has an interwiki component.
	 *
	 * @return bool
	 */
	public function isExternal(): bool {
		'@phan-var LinkTarget $this';
		return $this->getInterwiki() !== '';
	}

	/**
	 * Check whether the given LinkTarget refers to the same target as this LinkTarget.
	 *
	 * Two link targets are considered the same if they have the same interwiki prefix,
	 * are in the same namespace, have the same main part, and the same fragment.
	 *
	 * @param LinkTarget $other
	 * @return bool
	 */
	public function isSameLinkAs( LinkTarget $other ): bool {
		'@phan-var LinkTarget $this';
		// NOTE: keep in sync with Title::isSameLinkAs()!
		// NOTE: keep in sync with TitleValue::isSameLinkAs()!
		// NOTE: === is needed for number-like titles
		return ( $other->getInterwiki() === $this->getInterwiki() )
			&& ( $other->getDBkey() === $this->getDBkey() )
			&& ( $other->getNamespace() === $this->getNamespace() )
			&& ( $other->getFragment() === $this->getFragment() );
	}

	/**
	 * Return an informative human-readable representation of the link target
	 * namespace, for use in logging and debugging.
	 *
	 * @return string
	 */
	public function getNamespaceName(): string {
		'@phan-var LinkTarget $this';
		if ( $this->getNamespace() === 0 ) {
			return '';  // 0 is NS_MAIN
		}
		// A nicer version of this would convert the namespace to a
		// human-readable string, but that's outside the bounds of the
		// limited LinkTarget interface.  As a fallback, just use a
		// numeric namespace.  Implementations can override this.
		return '<' . strval( $this->getNamespace() ) . '>';
	}

	/**
	 * Return an informative human-readable representation of the link target,
	 * for use in logging and debugging.
	 *
	 * @return string
	 */
	public function __toString(): string {
		'@phan-var LinkTarget $this';
		$result = '';
		if ( $this->isExternal() ) {
			$result .= $this->getInterwiki() . ':';
		}
		if ( $this->getNamespace() !== 0 ) { // 0 is NS_MAIN
			'@phan-var LinkTargetTrait $this';
			$result .= $this->getNamespaceName() . ':';
		}
		$result .= $this->getText();
		if ( $this->hasFragment() ) {
			$result .= '#' . $this->getFragment();
		}
		return $result;
	}

}
