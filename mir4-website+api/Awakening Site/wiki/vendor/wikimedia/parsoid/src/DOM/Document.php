<?php

declare( strict_types = 1 );

namespace Wikimedia\Parsoid\DOM;

#[\AllowDynamicProperties]
class Document extends \DOMDocument implements Node {

	/**
	 * Inprocess cache used in DOMCompat::getBody()
	 *
	 * @var Element|null
	 */
	public ?Element $body = null;

	public function __construct() {
		parent::__construct();
		# Register our alias classes. Notes:
		#  - NodeList can't be passed to registerNodeClass, and so in
		#    "DOMDocument" mode we're always going to be using DOMNodeList,
		#    not the Wikimedia\Parsoid\DOM\Compat\NodeList class defined here.
		#  - Similarly, DOMException is always going to be \DOMException
		#    when we're in DOMDocument mode.
		#  - CharacterData and Node are abstract superclasses.  Due to the
		#    limitations of PHP multiple inheritance, we can't make them
		#    proper subclasses of DOMCharacterData/DOMNode.  Instead we make
		#    them marker interfaces, and ensure that all subclasses of
		#    DOMNode also implement our Node interface, and similarly all
		#    subclasses of DOMCharacterData implement our CharacterData marker
		#    interface.
		#  - PHP doesn't have a DOMParser equivalent in the dom extension
		foreach ( [
			'Document',
			'Attr',
			'Comment',
			'DocumentFragment',
			'DocumentType',
			'Element',
			'ProcessingInstruction',
			'Text',
		] as $cls ) {
			$this->registerNodeClass(
				"DOM$cls",
				"Wikimedia\\Parsoid\\DOM\\$cls"
			);
		}
	}
}
