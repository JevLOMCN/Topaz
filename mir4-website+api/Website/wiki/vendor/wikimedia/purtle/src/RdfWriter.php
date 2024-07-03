<?php

namespace Wikimedia\Purtle;

/**
 * Writer interface for RDF output. RdfWriter instances are generally stateful,
 * but should be implemented to operate in a stream-like manner with a minimum of state.
 *
 * This is intended to provide a "fluent interface" that allows programmers to use
 * a turtle-like structure when generating RDF output. E.g.:
 *
 * @code
 * $writer->prefix( 'acme', 'http://acme.test/terms/' );
 * $writer->about( 'http://quux.test/Something' )
 *   ->say( 'acme', 'name' )->text( 'Thingy' )->text( 'Dingsda', 'de' )
 *   ->say( 'acme', 'owner' )->is( 'http://quux.test/' );
 * @endcode
 *
 * To get the generated RDF output, use the drain() method.
 *
 * @note: The contract of this interface follows the GIGO principle, that is,
 * implementations are not required to ensure valid output or prompt failure on
 * invalid input. Speed should generally be favored over safety.
 *
 * Caveats:
 * - no relative iris
 * - predicates must be qnames
 * - no inline/nested blank nodes
 * - no comments
 * - no collections
 * - no automatic conversion of iris to qnames
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 */
interface RdfWriter {

	// TODO: split: generic RdfWriter class with shorthands, use RdfFormatters for output

	/**
	 * Returns the local name of a blank node, for use with the "_" prefix.
	 *
	 * @param string|null $label node label, will be generated if not given.
	 *
	 * @return string A local name for the blank node, for use with the '_' prefix.
	 */
	public function blank( $label = null );

	/**
	 * Start the document. May generate a header.
	 */
	public function start();

	/**
	 * Finish the document. May generate a footer.
	 *
	 * This will detach all sub-writers that had earlier been returned by sub().
	 */
	public function finish();

	/**
	 * Generates an RDF string from the current buffers state and returns it.
	 * The buffer is reset to the empty state.
	 * Before the result string is generated, implementations should close any
	 * pending syntactical structures (close tags, generate footers, etc).
	 *
	 * @return string The RDF output
	 */
	public function drain();

	/**
	 * Declare a prefix for later use. Prefixes should be declared before being used.
	 * Should not be called after start().
	 *
	 * @param string $prefix
	 * @param string $iri a IRI
	 */
	public function prefix( $prefix, $iri );

	/**
	 * Start an "about" (subject) clause, given a subject.
	 * Can occur at the beginning odf the output sequence, but can later only follow
	 * a call to is(), text(), or value().
	 * Should fail if called at an inappropriate time in the output sequence.
	 *
	 * @param string $base A QName prefix if $local is given, or an IRI if $local is null.
	 * @param string|null $local A QName suffix, or null if $base is an IRI.
	 *
	 * @return RdfWriter $this
	 */
	public function about( $base, $local = null );

	/**
	 * Start a predicate clause.
	 * Can only follow a call to about() or say().
	 * Should fail if called at an inappropriate time in the output sequence.
	 *
	 * @note Unlike about() and is(), say() cannot be called with a full IRI,
	 * but must always use qname form. This is required to cater to output
	 * formats that do not allow IRIs to be used as predicates directly,
	 * like RDF/XML.
	 *
	 * @param string $base A QName prefix if $local is given, or a shorthand. MUST NOT be an IRI.
	 * @param string|null $local A QName suffix, or null if $base is a shorthand.
	 *
	 * @return RdfWriter $this
	 */
	public function say( $base, $local = null );

	/**
	 * Produce a resource as the object of a statement.
	 * Can only follow a call to say() or a call to one of is(), text(), or value().
	 * Should fail if called at an inappropriate time in the output sequence.
	 *
	 * @param string $base A QName prefix if $local is given, or an IRI or shorthand if $local is null.
	 * @param string|null $local A QName suffix, or null if $base is an IRI or shorthand.
	 *
	 * @return RdfWriter $this
	 */
	public function is( $base, $local = null );

	/**
	 * Produce a text literal as the object of a statement.
	 * Can only follow a call to say() or a call to one of is(), text(), or value().
	 * Should fail if called at an inappropriate time in the output sequence.
	 *
	 * @param string $text the text to be placed in the output
	 * @param string|null $language the language the text is in
	 *
	 * @return RdfWriter $this
	 */
	public function text( $text, $language = null );

	/**
	 * Produce a typed or untyped literal as the object of a statement.
	 * Can only follow a call to say() or a call to one of is(), text(), or value().
	 * Should fail if called at an inappropriate time in the output sequence.
	 *
	 * @param string $value the value encoded as a string
	 * @param string|null $typeBase The data type's QName prefix if $typeLocal is given,
	 *        or an IRI or shorthand if $typeLocal is null.
	 * @param string|null $typeLocal The data type's  QName suffix,
	 *        or null if $typeBase is an IRI or shorthand.
	 *
	 * @return RdfWriter $this
	 */
	public function value( $value, $typeBase = null, $typeLocal = null );

	/**
	 * Shorthand for say( 'a' )->is( $type ).
	 *
	 * @param string $typeBase The data type's QName prefix if $typeLocal is given,
	 *        or an IRI or shorthand if $typeLocal is null.
	 * @param string|null $typeLocal The data type's  QName suffix,
	 *        or null if $typeBase is an IRI or shorthand.
	 *
	 * @return RdfWriter $this
	 */
	public function a( $typeBase, $typeLocal = null );

	/**
	 * Returns a document-level sub-writer.
	 * This can be used to generate parts statements out of sequence.
	 * Output generated by the sub-writer will be present in the
	 * return value of drain(), after any output generated by this
	 * writer itself.
	 *
	 * @note calling drain() on sub-writers results in undefined behavior!
	 * @note using sub-writers after finish() has been called on this writer
	 *        results in undefined behavior!
	 *
	 * @return RdfWriter
	 */
	public function sub();

	/**
	 * Returns the MIME type of the RDF serialization the writer produces.
	 *
	 * @return string a MIME type
	 */
	public function getMimeType();

}
