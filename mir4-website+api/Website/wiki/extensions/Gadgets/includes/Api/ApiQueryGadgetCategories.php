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
use MediaWiki\Extension\Gadgets\GadgetRepo;
use Wikimedia\ParamValidator\ParamValidator;

/**
 * API for Gadgets extension
 */
class ApiQueryGadgetCategories extends ApiQueryBase {
	/**
	 * @var array
	 */
	private $props;

	/**
	 * @var array|bool
	 */
	private $neededNames;

	private GadgetRepo $gadgetRepo;

	public function __construct( ApiQuery $queryModule, $moduleName, GadgetRepo $gadgetRepo ) {
		parent::__construct( $queryModule, $moduleName, 'gc' );
		$this->gadgetRepo = $gadgetRepo;
	}

	public function execute() {
		$params = $this->extractRequestParams();
		$this->props = array_flip( $params['prop'] );
		$this->neededNames = isset( $params['names'] )
			? array_flip( $params['names'] )
			: false;

		$this->getMain()->setCacheMode( 'public' );

		$this->getList();
	}

	/**
	 * @return void
	 */
	private function getList() {
		$data = [];
		$result = $this->getResult();
		$gadgets = $this->gadgetRepo->getStructuredList();

		if ( $gadgets ) {
			foreach ( $gadgets as $category => $list ) {
				if ( $this->neededNames && !isset( $this->neededNames[$category] ) ) {
					continue;
				}
				$row = [];
				if ( isset( $this->props['name'] ) ) {
					$row['name'] = $category;
				}

				if ( ( $category !== "" ) && isset( $this->props['title'] ) ) {
					$row['desc'] = $this->msg( "gadget-section-$category" )->parse();
				}

				if ( isset( $this->props['members'] ) ) {
					$row['members'] = count( $list );
				}

				$data[] = $row;
			}
		}
		ApiResult::setIndexedTagName( $data, 'category' );
		$result->addValue( 'query', $this->getModuleName(), $data );
	}

	public function getAllowedParams() {
		return [
			'prop' => [
				ParamValidator::PARAM_DEFAULT => 'name',
				ParamValidator::PARAM_ISMULTI => true,
				ParamValidator::PARAM_TYPE => [
					'name',
					'title',
					'members',
				],
				ApiBase::PARAM_HELP_MSG_PER_VALUE => [],
			],
			'names' => [
				ParamValidator::PARAM_TYPE => 'string',
				ParamValidator::PARAM_ISMULTI => true,
			],
		];
	}

	/**
	 * @see ApiBase::getExamplesMessages()
	 * @return array
	 */
	protected function getExamplesMessages() {
		return [
			'action=query&list=gadgetcategories'
				=> 'apihelp-query+gadgetcategories-example-1',
			'action=query&list=gadgetcategories&gcnames=foo|bar&gcprop=name|title|members'
				=> 'apihelp-query+gadgetcategories-example-2',
		];
	}
}
