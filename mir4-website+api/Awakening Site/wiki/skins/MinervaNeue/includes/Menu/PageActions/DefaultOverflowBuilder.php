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

namespace MediaWiki\Minerva\Menu\PageActions;

use MediaWiki\Minerva\Menu\Entries\IMenuEntry;
use MediaWiki\Minerva\Menu\Entries\SingleMenuEntry;
use MediaWiki\Minerva\Menu\Group;
use MediaWiki\Minerva\Permissions\IMinervaPagePermissions;
use MediaWiki\Title\Title;
use MessageLocalizer;

class DefaultOverflowBuilder implements IOverflowBuilder {

	/**
	 * @var Title
	 */
	private $title;

	/**
	 * @var MessageLocalizer
	 */
	private $messageLocalizer;

	/**
	 * @var IMinervaPagePermissions
	 */
	private $permissions;

	/**
	 * Initialize Default overflow menu Group
	 *
	 * @param Title $title
	 * @param MessageLocalizer $messageLocalizer
	 * @param IMinervaPagePermissions $permissions Minerva permissions system
	 */
	public function __construct(
		Title $title,
		MessageLocalizer $messageLocalizer,
		IMinervaPagePermissions $permissions
	) {
		$this->title = $title;
		$this->messageLocalizer = $messageLocalizer;
		$this->permissions = $permissions;
	}

	public function getTitle(): Title {
		return $this->title;
	}

	public function getMessageLocalizer(): MessageLocalizer {
		return $this->messageLocalizer;
	}

	public function isAllowed( string $permission ): bool {
		return $this->permissions->isAllowed( $permission );
	}

	/**
	 * @inheritDoc
	 */
	public function getGroup( array $toolbox, array $actions ): Group {
		$group = new Group( 'p-tb' );

		$override = $this->isAllowed( IMinervaPagePermissions::EDIT_OR_CREATE ) ? [
			'editfull' => [
				'icon' => 'edit',
				'text' => $this->messageLocalizer->msg( 'minerva-page-actions-editfull' ),
				'href' => $this->title->getLocalURL( [ 'action' => 'edit', 'section' => 'all' ] ),
				'class' => 'edit-link',
			],
		] : [];
		// watch icon appears in page actions rather than here.
		$combinedMenu = array_merge( $toolbox, $override, $actions );
		unset( $combinedMenu[ 'watch' ] );
		unset( $combinedMenu[ 'unwatch' ] );
		foreach ( $combinedMenu as $key => $definition ) {
			$icon = $definition['icon'] ?? null;
			// Only menu items with icons can be displayed here.
			if ( $icon ) {
				$entry = $this->build( $key, $icon, $key, $combinedMenu );
				if ( $entry ) {
					$group->insertEntry( $entry );
				}
			}
		}
		return $group;
	}

	/**
	 * Build the single menu entry
	 *
	 * @param string $name
	 * @param string $icon WikimediaUI icon name.
	 * @param string $toolboxIdx
	 * @param array $toolbox An array of common toolbox items from the sidebar menu
	 * @return IMenuEntry|null
	 */
	private function build( $name, $icon, $toolboxIdx, array $toolbox ) {
		$href = $toolbox[$toolboxIdx]['href'] ?? null;
		$originalMsg = $toolbox[$toolboxIdx]['text'] ??
			$this->messageLocalizer->msg( $toolboxIdx )->text();

		$entry = new SingleMenuEntry(
			'page-actions-overflow-' . $name,
			$originalMsg,
			$href,
			$toolbox[$name]['class'] ?? false
		);

		return $href ? $entry->setIcon( $icon )->trackClicks( $name ) : null;
	}
}
