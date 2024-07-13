<?php
declare( strict_types = 1 );

namespace Wikimedia\Parsoid\Ext;

use Wikimedia\Parsoid\Core\DomSourceRange;
use Wikimedia\Parsoid\NodeData\DataMw;
use Wikimedia\Parsoid\Tokens\Token;
use Wikimedia\Parsoid\Utils\Utils;

/**
 * Wrapper so that the internal token isn't exposed
 */
class ExtensionTag {

	/** @var Token */
	private $extToken;

	public function __construct( Token $extToken ) {
		$this->extToken = $extToken;
	}

	/**
	 * Return the name of the extension tag
	 */
	public function getName(): string {
		return $this->extToken->getAttributeV( 'name' );
	}

	/**
	 * Return the source offsets for this extension tag usage
	 */
	public function getOffsets(): ?DomSourceRange {
		return $this->extToken->dataParsoid->extTagOffsets ?? null;
	}

	/**
	 * Return the full extension source
	 */
	public function getSource(): ?string {
		return $this->extToken->getAttributeV( 'source' );
	}

	/**
	 * Is this extension tag self-closed?
	 */
	public function isSelfClosed(): bool {
		return !empty( $this->extToken->dataParsoid->selfClose );
	}

	public function getDefaultDataMw(): DataMw {
		return Utils::getExtArgInfo( $this->extToken );
	}
}
