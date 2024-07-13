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

namespace MediaWiki\Extension\Gadgets;

use InvalidArgumentException;
use MediaWiki\Linker\LinkTarget;
use MediaWiki\Title\Title;

/**
 * Combine two gadget repos during migrations
 *
 * @copyright 2017 Kunal Mehta <legoktm@member.fsf.org>
 * @copyright 2023 Siddharth VP
 */
class MultiGadgetRepo extends GadgetRepo {

	/**
	 * @var GadgetRepo[]
	 */
	private array $repos;

	/**
	 * @param GadgetRepo[] $repos
	 */
	public function __construct( array $repos ) {
		$this->repos = $repos;
	}

	/**
	 * @inheritDoc
	 */
	public function getGadget( string $id ): Gadget {
		foreach ( $this->repos as $repo ) {
			try {
				return $repo->getGadget( $id );
			} catch ( InvalidArgumentException $e ) {
				// Try next repo
			}
		}

		throw new InvalidArgumentException( "No gadget registered for '$id'" );
	}

	/**
	 * @inheritDoc
	 */
	public function getGadgetIds(): array {
		$ids = [];
		foreach ( $this->repos as $repo ) {
			$ids = array_merge( $ids, $repo->getGadgetIds() );
		}
		return array_values( array_unique( $ids ) );
	}

	/**
	 * @inheritDoc
	 */
	public function handlePageUpdate( LinkTarget $target ): void {
		foreach ( $this->repos as $repo ) {
			$repo->handlePageUpdate( $target );
		}
	}

	private function getRepoForGadget( string $id ): GadgetRepo {
		foreach ( $this->repos as $repo ) {
			try {
				$repo->getGadget( $id );
				// return repo if it didn't throw
				return $repo;
			} catch ( InvalidArgumentException $e ) {
			}
		}
		throw new InvalidArgumentException( "No repo found for gadget $id" );
	}

	public function getGadgetDefinitionTitle( string $id ): ?Title {
		return $this->getRepoForGadget( $id )->getGadgetDefinitionTitle( $id );
	}

	public function titleWithoutPrefix( string $titleText, string $gadgetId ): string {
		return $this->getRepoForGadget( $gadgetId )->titleWithoutPrefix( $titleText, $gadgetId );
	}

	public function validationWarnings( Gadget $gadget ): array {
		$duplicateWarnings = $this->isDefinedTwice( $gadget->getName() ) ? [
			wfMessage( "gadgets-validate-duplicate", $gadget->getName() )
		] : [];
		return array_merge( $duplicateWarnings, parent::validationWarnings( $gadget ) );
	}

	/**
	 * Checks if a gadget is defined with the same name in two different repos.
	 * @param string $id Gadget name
	 * @return bool
	 */
	private function isDefinedTwice( string $id ) {
		$found = false;
		foreach ( $this->repos as $repo ) {
			try {
				$repo->getGadget( $id );
				if ( $found ) {
					// found it a second time
					return true;
				} else {
					$found = true;
				}
			} catch ( InvalidArgumentException $e ) {
			}
		}
		return false;
	}

}
