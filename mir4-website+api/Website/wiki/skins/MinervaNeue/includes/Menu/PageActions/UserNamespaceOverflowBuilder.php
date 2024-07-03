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

use MediaWiki\Context\IContextSource;
use MediaWiki\Minerva\LanguagesHelper;
use MediaWiki\Minerva\Menu\Entries\LanguageSelectorEntry;
use MediaWiki\Minerva\Menu\Group;
use MediaWiki\Minerva\Permissions\IMinervaPagePermissions;
use MediaWiki\Title\Title;

class UserNamespaceOverflowBuilder extends DefaultOverflowBuilder {

	/**
	 * @var IContextSource
	 */
	private $context;

	/**
	 * @var LanguagesHelper
	 */
	private $languagesHelper;

	/**
	 * Initialize the overflow menu visible on the User namespace
	 * @param Title $title
	 * @param IContextSource $context
	 * @param IMinervaPagePermissions $permissions
	 * @param LanguagesHelper $languagesHelper
	 */
	public function __construct(
		Title $title,
		IContextSource $context,
		IMinervaPagePermissions $permissions,
		LanguagesHelper $languagesHelper
	) {
		$this->context = $context;
		$this->languagesHelper = $languagesHelper;
		parent::__construct( $title, $context, $permissions );
	}

	/**
	 * @inheritDoc
	 */
	public function getGroup( array $toolbox, array $actions ): Group {
		$group = parent::getGroup( $toolbox, $actions );

		if ( $this->isAllowed( IMinervaPagePermissions::SWITCH_LANGUAGE ) ) {
			$group->prependEntry( new LanguageSelectorEntry(
				$this->getTitle(),
				$this->languagesHelper->doesTitleHasLanguagesOrVariants(
					$this->context->getOutput(),
					$this->getTitle()
				),
				$this->getMessageLocalizer(),
				false,
				// no additional classes
				'',
				'minerva-page-actions-language-switcher'
			) );
		}

		return $group;
	}
}
