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

use MediaWiki\Output\OutputPage;
use MediaWiki\User\User;
use Skin;

/**
 * @author Siddharth VP
 */
class GadgetLoadConditions {
	/** @var User */
	private $user;
	/** @var Skin */
	private $skin;
	/** @var string */
	private $action;
	/** @var int */
	private $namespace;
	/** @var string[] */
	private $categories;
	/** @var string */
	private $contentModel;
	/** @var string|null */
	private $withGadgetParam;

	/**
	 * @param OutputPage $out
	 */
	public function __construct( OutputPage $out ) {
		$this->user = $out->getUser();
		$this->skin = $out->getSkin();
		$this->action = $out->getContext()->getActionName();
		$this->namespace = $out->getTitle()->getNamespace();
		$this->categories = $out->getCategories();
		$this->contentModel = $out->getTitle()->getContentModel();
		$this->withGadgetParam = $out->getRequest()->getRawVal( 'withgadget' );
	}

	public function check( Gadget $gadget ): bool {
		$urlLoad = $this->withGadgetParam === $gadget->getName() && $gadget->supportsUrlLoad();

		return ( $gadget->isEnabled( $this->user ) || $urlLoad )
			&& $gadget->isAllowed( $this->user )
			&& $gadget->isActionSupported( $this->action )
			&& $gadget->isSkinSupported( $this->skin )
			&& $gadget->isNamespaceSupported( $this->namespace )
			&& $gadget->isCategorySupported( $this->categories )
			&& $gadget->isContentModelSupported( $this->contentModel );
	}
}
