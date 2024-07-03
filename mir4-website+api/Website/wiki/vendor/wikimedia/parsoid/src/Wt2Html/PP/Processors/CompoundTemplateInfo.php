<?php
declare( strict_types = 1 );

namespace Wikimedia\Parsoid\Wt2Html\PP\Processors;

use Wikimedia\Parsoid\Core\DomSourceRange;
use Wikimedia\Parsoid\NodeData\TemplateInfo;

class CompoundTemplateInfo {
	/** @var DomSourceRange */
	public $dsr;

	/** @var TemplateInfo */
	public $info;

	/** @var bool */
	public $isParam;

	public function __construct( DomSourceRange $dsr, TemplateInfo $info, bool $isParam ) {
		$this->dsr = $dsr;
		$this->info = $info;
		$this->isParam = $isParam;
	}
}
