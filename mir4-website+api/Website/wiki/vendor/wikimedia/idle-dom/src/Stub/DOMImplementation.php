<?php

// AUTOMATICALLY GENERATED.  DO NOT EDIT.
// Use `composer build` to regenerate.

namespace Wikimedia\IDLeDOM\Stub;

use Exception;
use Wikimedia\IDLeDOM\Document;
use Wikimedia\IDLeDOM\DocumentType;
use Wikimedia\IDLeDOM\XMLDocument;

trait DOMImplementation {

	// Underscore is used to avoid conflicts with DOM-reserved names
	// phpcs:disable PSR2.Methods.MethodDeclaration.Underscore
	// phpcs:disable MediaWiki.NamingConventions.LowerCamelFunctionsName.FunctionName

	/**
	 * @return Exception
	 */
	abstract protected function _unimplemented(): Exception;

	// phpcs:enable

	/**
	 * @param string $qualifiedName
	 * @param string $publicId
	 * @param string $systemId
	 * @return DocumentType
	 */
	public function createDocumentType( string $qualifiedName, string $publicId = '', string $systemId = '' ) {
		throw self::_unimplemented();
	}

	/**
	 * @param ?string $namespace
	 * @param ?string $qualifiedName
	 * @param DocumentType|null $doctype
	 * @return XMLDocument
	 */
	public function createDocument( ?string $namespace, ?string $qualifiedName = '', /* ?DocumentType */ $doctype = null ) {
		throw self::_unimplemented();
	}

	/**
	 * @param ?string $title
	 * @return Document
	 */
	public function createHTMLDocument( ?string $title = null ) {
		throw self::_unimplemented();
	}

	/**
	 * @return bool
	 */
	public function hasFeature(): bool {
		throw self::_unimplemented();
	}

}
