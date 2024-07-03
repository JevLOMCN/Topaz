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

namespace MediaWiki\Extension\Gadgets\Api;

use ApiBase;
use ApiQuery;
use ApiQueryBase;
use ApiResult;
use MediaWiki\Extension\Gadgets\Gadget;
use MediaWiki\Extension\Gadgets\GadgetRepo;
use Wikimedia\ParamValidator\ParamValidator;

class ApiQueryGadgets extends ApiQueryBase {
	private array $props;

	/**
	 * @var array|bool
	 */
	private $categories;

	/**
	 * @var array|bool
	 */
	private $neededIds;

	private bool $listAllowed;

	private bool $listEnabled;

	private GadgetRepo $gadgetRepo;

	public function __construct( ApiQuery $queryModule, $moduleName, GadgetRepo $gadgetRepo ) {
		parent::__construct( $queryModule, $moduleName, 'ga' );
		$this->gadgetRepo = $gadgetRepo;
	}

	public function execute() {
		$params = $this->extractRequestParams();
		$this->props = array_flip( $params['prop'] );
		$this->categories = isset( $params['categories'] )
			? array_flip( $params['categories'] )
			: false;
		$this->neededIds = isset( $params['ids'] )
			? array_flip( $params['ids'] )
			: false;
		$this->listAllowed = isset( $params['allowedonly'] ) && $params['allowedonly'];
		$this->listEnabled = isset( $params['enabledonly'] ) && $params['enabledonly'];

		$this->getMain()->setCacheMode( $this->listAllowed || $this->listEnabled
			? 'anon-public-user-private' : 'public' );

		$this->applyList( $this->getList() );
	}

	private function getList(): array {
		$gadgets = $this->gadgetRepo->getStructuredList();

		if ( !$gadgets ) {
			return [];
		}

		$result = [];
		foreach ( $gadgets as $category => $list ) {
			if ( $this->categories && !isset( $this->categories[$category] ) ) {
				continue;
			}

			foreach ( $list as $g ) {
				if ( $this->isNeeded( $g ) ) {
					$result[] = $g;
				}
			}
		}
		return $result;
	}

	private function applyList( array $gadgets ): void {
		$data = [];
		$result = $this->getResult();

		/**
		 * @var $g Gadget
		 */
		foreach ( $gadgets as $g ) {
			$row = [];
			if ( isset( $this->props['id'] ) ) {
				$row['id'] = $g->getName();
			}

			if ( isset( $this->props['metadata'] ) ) {
				$row['metadata'] = $this->fakeMetadata( $g );
				$this->setIndexedTagNameForMetadata( $row['metadata'] );
			}

			if ( isset( $this->props['desc'] ) ) {
				$row['desc'] = $g->getDescription();
			}

			$data[] = $row;
		}

		ApiResult::setIndexedTagName( $data, 'gadget' );
		$result->addValue( 'query', $this->getModuleName(), $data );
	}

	private function isNeeded( Gadget $gadget ): bool {
		$user = $this->getUser();

		return ( $this->neededIds === false || isset( $this->neededIds[$gadget->getName()] ) )
			&& ( !$this->listAllowed || $gadget->isAllowed( $user ) )
			&& ( !$this->listEnabled || $gadget->isEnabled( $user ) );
	}

	private function fakeMetadata( Gadget $g ): array {
		return [
			'settings' => [
				'actions' => $g->getRequiredActions(),
				'categories' => $g->getRequiredCategories(),
				'category' => $g->getCategory(),
				'contentModels' => $g->getRequiredContentModels(),
				'default' => $g->isOnByDefault(),
				'hidden' => $g->isHidden(),
				'legacyscripts' => (bool)$g->getLegacyScripts(),
				'namespaces' => $g->getRequiredNamespaces(),
				'package' => $g->isPackaged(),
				'requiresES6' => $g->requiresES6(),
				'rights' => $g->getRequiredRights(),
				'shared' => false,
				'skins' => $g->getRequiredSkins(),
				'supportsUrlLoad' => $g->supportsUrlLoad(),
			],
			'module' => [
				'datas' => $g->getJSONs(),
				'dependencies' => $g->getDependencies(),
				'messages' => $g->getMessages(),
				'peers' => $g->getPeers(),
				'scripts' => $g->getScripts(),
				'styles' => $g->getStyles(),
			]
		];
	}

	private function setIndexedTagNameForMetadata( array &$metadata ): void {
		static $tagNames = [
			'actions' => 'action',
			'categories' => 'category',
			'contentModels' => 'contentModel',
			'datas' => 'data',
			'dependencies' => 'dependency',
			'messages' => 'message',
			'namespaces' => 'namespace',
			'peers' => 'peer',
			'rights' => 'right',
			'scripts' => 'script',
			'skins' => 'skin',
			'styles' => 'style',
		];

		foreach ( $metadata as $data ) {
			foreach ( $data as $key => $value ) {
				if ( is_array( $value ) ) {
					$tag = $tagNames[$key] ?? $key;
					ApiResult::setIndexedTagName( $value, $tag );
				}
			}
		}
	}

	public function getAllowedParams() {
		return [
			'prop' => [
				ParamValidator::PARAM_DEFAULT => 'id|metadata',
				ParamValidator::PARAM_ISMULTI => true,
				ParamValidator::PARAM_TYPE => [
					'id',
					'metadata',
					'desc',
				],
				ApiBase::PARAM_HELP_MSG_PER_VALUE => [],
			],
			'categories' => [
				ParamValidator::PARAM_ISMULTI => true,
				ParamValidator::PARAM_TYPE => 'string',
			],
			'ids' => [
				ParamValidator::PARAM_TYPE => 'string',
				ParamValidator::PARAM_ISMULTI => true,
			],
			'allowedonly' => false,
			'enabledonly' => false,
		];
	}

	/**
	 * @see ApiBase::getExamplesMessages()
	 * @return array
	 */
	protected function getExamplesMessages() {
		$params = $this->getAllowedParams();
		$allProps = implode( '|', $params['prop'][ParamValidator::PARAM_TYPE] );
		return [
			'action=query&list=gadgets&gaprop=id|desc'
				=> 'apihelp-query+gadgets-example-1',
			"action=query&list=gadgets&gaprop=$allProps"
				=> 'apihelp-query+gadgets-example-2',
			'action=query&list=gadgets&gacategories=foo'
				=> 'apihelp-query+gadgets-example-3',
			'action=query&list=gadgets&gaids=foo|bar&gaprop=id|desc|metadata'
				=> 'apihelp-query+gadgets-example-4',
			'action=query&list=gadgets&gaenabledonly'
				=> 'apihelp-query+gadgets-example-5',
		];
	}
}
