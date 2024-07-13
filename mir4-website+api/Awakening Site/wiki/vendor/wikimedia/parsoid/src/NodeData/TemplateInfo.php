<?php
declare( strict_types = 1 );

namespace Wikimedia\Parsoid\NodeData;

use stdClass;

class TemplateInfo {
	/**
	 * The target wikitext
	 * @var string|null
	 */
	public $targetWt;

	/**
	 * The parser function name
	 * @var string|null
	 */
	public $func;

	/**
	 * The URL of the target
	 * @var string|null
	 */
	public $href;

	/**
	 * Param infos indexed by key (ParamInfo->k)
	 * @var ParamInfo[]
	 */
	public $paramInfos;

	/**
	 * Get JSON-serializable data for data-mw.parts.template
	 *
	 * @param int $index The index into data-parsoid.pi
	 * @return stdClass
	 */
	public function getDataMw( int $index ): stdClass {
		$target = [ 'wt' => $this->targetWt ];
		if ( $this->func !== null ) {
			$target['function'] = $this->func;
		}
		if ( $this->href !== null ) {
			$target['href'] = $this->href;
		}
		$params = [];
		foreach ( $this->paramInfos as $info ) {
			$param = [
				'wt' => $info->valueWt,
			];
			if ( $info->html !== null ) {
				$param['html'] = $info->html;
			}
			if ( $info->keyWt !== null ) {
				$param['key']['wt'] = $info->keyWt;
			}
			$params[$info->k] = $param;
		}

		// Cast everything to object to satisfy pre-serialization consumers of data-mw
		return (object)[
			'target' => (object)$target,
			// params also needs to be cast to object in case all the keys are numeric
			'params' => (object)$params,
			'i' => $index
		];
	}
}
