<?php

// AUTOMATICALLY GENERATED.  DO NOT EDIT.
// Use `composer build` to regenerate.

namespace Wikimedia\IDLeDOM;

/**
 * DOMImplementation
 *
 * @see https://dom.spec.whatwg.org/#interface-domimplementation
 *
 * @phan-forbid-undeclared-magic-properties
 */
interface DOMImplementation {
	/**
	 * @param string $qualifiedName
	 * @param string $publicId
	 * @param string $systemId
	 * @return DocumentType
	 */
	public function createDocumentType( string $qualifiedName, string $publicId = '', string $systemId = '' );

	/**
	 * @param ?string $namespace
	 * @param ?string $qualifiedName
	 * @param DocumentType|null $doctype
	 * @return XMLDocument
	 */
	public function createDocument( ?string $namespace, ?string $qualifiedName = '', /* ?DocumentType */ $doctype = null );

	/**
	 * @param ?string $title
	 * @return Document
	 */
	public function createHTMLDocument( ?string $title = null );

	/**
	 * @return bool
	 */
	public function hasFeature(): bool;

}
