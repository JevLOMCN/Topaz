<?php

namespace Wikimedia\Purtle;

use Closure;
use InvalidArgumentException;
use LogicException;

/**
 * Base class for RdfWriter implementations.
 *
 * Subclasses have to implement at least the writeXXX() methods to generate the desired output
 * for the respective RDF constructs. Subclasses may override the startXXX() and finishXXX()
 * methods to generate structural output, and override expandXXX() to transform identifiers.
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 */
abstract class RdfWriterBase implements RdfWriter {

	/**
	 * @var array An array of strings, RdfWriters, or closures.
	 */
	private $buffer = [];

	/**
	 * @var RdfWriter[] sub-writers.
	 */
	private $subs = [];

	protected const STATE_START = 0;
	protected const STATE_DOCUMENT = 5;
	protected const STATE_SUBJECT = 10;
	protected const STATE_PREDICATE = 11;
	protected const STATE_OBJECT = 12;
	protected const STATE_FINISH = 666;

	/**
	 * @var string the current state
	 */
	private $state = self::STATE_START;

	/**
	 * Shorthands that can be used in place of IRIs, e.g. ("a" to mean rdf:type).
	 *
	 * @var string[] a map of shorthand names to [ $base, $local ] pairs.
	 * @todo Handle "a" as a special case directly. Use for custom "variables" like %currentValue
	 *  instead.
	 */
	private $shorthands = [];

	/**
	 * @var string[] a map of prefixes to base IRIs
	 */
	protected $prefixes = [];

	/**
	 * @var array pair to store the current subject.
	 * Holds the $base and $local parameters passed to about().
	 */
	protected $currentSubject = [ null, null ];

	/**
	 * @var array pair to store the current predicate.
	 * Holds the $base and $local parameters passed to say().
	 */
	protected $currentPredicate = [ null, null ];

	/**
	 * @var BNodeLabeler
	 */
	private $labeler;

	/**
	 * Role ID for writers that will generate a full RDF document.
	 */
	public const DOCUMENT_ROLE = 'document';
	public const SUBDOCUMENT_ROLE = 'sub';

	/**
	 * Role ID for writers that will generate a single inline blank node.
	 */
	private const BNODE_ROLE = 'bnode';

	/**
	 * Role ID for writers that will generate a single inline RDR statement.
	 */
	private const STATEMENT_ROLE = 'statement';

	/**
	 * @var string The writer's role, see the XXX_ROLE constants.
	 */
	protected $role;

	/**
	 * Are prefixed locked against modification?
	 * @var bool
	 */
	private $prefixesLocked = false;

	/**
	 * @param string $role The writer's role, use the XXX_ROLE constants.
	 * @param BNodeLabeler|null $labeler
	 *
	 * @throws InvalidArgumentException
	 */
	public function __construct( $role, BNodeLabeler $labeler = null ) {
		if ( !is_string( $role ) ) {
			throw new InvalidArgumentException( '$role must be a string' );
		}

		$this->role = $role;
		$this->labeler = $labeler ?: new BNodeLabeler();

		$this->registerShorthand( 'a', 'rdf', 'type' );

		$this->prefix( 'rdf', 'http://www.w3.org/1999/02/22-rdf-syntax-ns#' );
		$this->prefix( 'xsd', 'http://www.w3.org/2001/XMLSchema#' );
	}

	/**
	 * @param string $role
	 * @param BNodeLabeler $labeler
	 *
	 * @return RdfWriterBase
	 */
	abstract protected function newSubWriter( $role, BNodeLabeler $labeler );

	/**
	 * Registers a shorthand that can be used instead of a qname,
	 * like 'a' can be used instead of 'rdf:type'.
	 *
	 * @param string $shorthand
	 * @param string $prefix
	 * @param string $local
	 */
	protected function registerShorthand( $shorthand, $prefix, $local ) {
		$this->shorthands[$shorthand] = [ $prefix, $local ];
	}

	/**
	 * Registers a prefix
	 *
	 * @param string $prefix
	 * @param string $iri The base IRI
	 *
	 * @throws LogicException
	 */
	public function prefix( $prefix, $iri ) {
		if ( $this->prefixesLocked ) {
			throw new LogicException( 'Prefixes can not be added after start()' );
		}

		$this->prefixes[$prefix] = $iri;
	}

	/**
	 * Determines whether $shorthand can be used as a shorthand.
	 *
	 * @param string $shorthand
	 *
	 * @return bool
	 */
	protected function isShorthand( $shorthand ) {
		return isset( $this->shorthands[$shorthand] );
	}

	/**
	 * Determines whether $shorthand can legally be used as a prefix.
	 *
	 * @param string $prefix
	 *
	 * @return bool
	 */
	protected function isPrefix( $prefix ) {
		return isset( $this->prefixes[$prefix] );
	}

	/**
	 * Returns the prefix map.
	 *
	 * @return string[] An associative array mapping prefixes to base IRIs.
	 */
	public function getPrefixes() {
		return $this->prefixes;
	}

	/**
	 * @param string|null $languageCode
	 *
	 * @return bool
	 */
	protected function isValidLanguageCode( $languageCode ) {
		// preg_match is somewhat (12%) slower than strspn but more readable
		return $languageCode !== null && preg_match( '/^[\da-z-]{2,}$/i', $languageCode );
	}

	/**
	 * @return RdfWriter
	 */
	final public function sub() {
		$writer = $this->newSubWriter( self::SUBDOCUMENT_ROLE, $this->labeler );
		$writer->state = self::STATE_DOCUMENT;

		// share registered prefixes
		$writer->prefixes =& $this->prefixes;

		$this->subs[] = $writer;
		return $writer;
	}

	/**
	 * Returns the writers role. The role determines the behavior of the writer with respect
	 * to which states and transitions are possible: a BNODE_ROLE writer would for instance
	 * not accept a call to about(), since it can only process triples about a single subject
	 * (the blank node it represents).
	 *
	 * @return string A string corresponding to one of the the XXX_ROLE constants.
	 */
	final public function getRole() {
		return $this->role;
	}

	/**
	 * Appends string to the output buffer.
	 * @param string $w
	 */
	final protected function write( $w ) {
		$this->buffer[] = $w;
	}

	/**
	 * If $base is a shorthand, $base and $local are updated to hold whatever qname
	 * the shorthand was associated with.
	 *
	 * Otherwise, $base and $local remain unchanged.
	 *
	 * @param string &$base
	 * @param string|null &$local
	 */
	protected function expandShorthand( &$base, &$local ) {
		if ( $local === null && isset( $this->shorthands[$base] ) ) {
			list( $base, $local ) = $this->shorthands[$base];
		}
	}

	/**
	 * If $base is a registered prefix, $base will be replaced by the base IRI associated with
	 * that prefix, with $local appended. $local will be set to null.
	 *
	 * Otherwise, $base and $local remain unchanged.
	 *
	 * @param string &$base
	 * @param string|null &$local
	 *
	 * @throws LogicException
	 */
	protected function expandQName( &$base, &$local ) {
		if ( $local !== null && $base !== '_' ) {
			if ( isset( $this->prefixes[$base] ) ) {
				$base = $this->prefixes[$base] . $local; // XXX: can we avoid this concat?
				$local = null;
			} else {
				throw new LogicException( 'Unknown prefix: ' . $base );
			}
		}
	}

	/**
	 * @see RdfWriter::blank()
	 *
	 * @param string|null $label node label, will be generated if not given.
	 *
	 * @return string
	 */
	final public function blank( $label = null ) {
		return $this->labeler->getLabel( $label );
	}

	/**
	 * @see RdfWriter::start()
	 */
	final public function start() {
		$this->state( self::STATE_DOCUMENT );
		$this->prefixesLocked = true;
	}

	/**
	 * @see RdfWriter::finish()
	 */
	final public function finish() {
		// close all unclosed states
		$this->state( self::STATE_DOCUMENT );

		// ...then insert output of sub-writers into the buffer,
		// so it gets placed before the footer...
		$this->drainSubs();

		// and then finalize
		$this->state( self::STATE_FINISH );

		// Detaches all subs.
		$this->subs = [];
	}

	/**
	 * @see RdfWriter::drain()
	 *
	 * @return string RDF
	 */
	final public function drain() {
		// we can drain after finish, but finish state is sticky
		if ( $this->state !== self::STATE_FINISH ) {
			$this->state( self::STATE_DOCUMENT );
		}

		$this->drainSubs();
		$this->flattenBuffer();

		$rdf = implode( '', $this->buffer );
		$this->buffer = [];

		return $rdf;
	}

	/**
	 * Calls drain() an any RdfWriter instances in $this->buffer, and replaces them
	 * in $this->buffer with the string returned by the drain() call. Any closures
	 * present in the $this->buffer will be called, and replaced by their return value.
	 */
	private function flattenBuffer() {
		foreach ( $this->buffer as &$b ) {
			if ( $b instanceof Closure ) {
				$b = $b();
			}
			if ( $b instanceof RdfWriter ) {
				$b = $b->drain();
			}
		}
	}

	/**
	 * Drains all subwriters, and appends their output to this writer's buffer.
	 * Subwriters remain usable.
	 */
	private function drainSubs() {
		foreach ( $this->subs as $sub ) {
			$rdf = $sub->drain();
			$this->write( $rdf );
		}
	}

	/**
	 * @see RdfWriter::about()
	 *
	 * @param string $base A QName prefix if $local is given, or an IRI if $local is null.
	 * @param string|null $local A QName suffix, or null if $base is an IRI.
	 *
	 * @return RdfWriter $this
	 */
	final public function about( $base, $local = null ) {
		$this->expandSubject( $base, $local );

		if ( $this->state === self::STATE_OBJECT
			&& $base === $this->currentSubject[0]
			&& $local === $this->currentSubject[1]
		) {
			return $this; // redundant about() call
		}

		$this->state( self::STATE_SUBJECT );

		$this->currentSubject[0] = $base;
		$this->currentSubject[1] = $local;
		$this->currentPredicate[0] = null;
		$this->currentPredicate[1] = null;

		$this->writeSubject( $base, $local );
		return $this;
	}

	/**
	 * @see RdfWriter::a()
	 * Shorthand for say( 'a' )->is( $type ).
	 *
	 * @param string $typeBase The data type's QName prefix if $typeLocal is given,
	 *        or an IRI or shorthand if $typeLocal is null.
	 * @param string|null $typeLocal The data type's  QName suffix,
	 *        or null if $typeBase is an IRI or shorthand.
	 *
	 * @return RdfWriter $this
	 */
	final public function a( $typeBase, $typeLocal = null ) {
		return $this->say( 'a' )->is( $typeBase, $typeLocal );
	}

	/**
	 * @see RdfWriter::say()
	 *
	 * @param string $base A QName prefix.
	 * @param string|null $local A QName suffix.
	 *
	 * @return RdfWriter $this
	 */
	final public function say( $base, $local = null ) {
		$this->expandPredicate( $base, $local );

		if ( $this->state === self::STATE_OBJECT
			&& $base === $this->currentPredicate[0]
			&& $local === $this->currentPredicate[1]
		) {
			return $this; // redundant about() call
		}

		$this->state( self::STATE_PREDICATE );

		$this->currentPredicate[0] = $base;
		$this->currentPredicate[1] = $local;

		$this->writePredicate( $base, $local );
		return $this;
	}

	/**
	 * @see RdfWriter::is()
	 *
	 * @param string $base A QName prefix if $local is given, or an IRI if $local is null.
	 * @param string|null $local A QName suffix, or null if $base is an IRI.
	 *
	 * @return RdfWriter $this
	 */
	final public function is( $base, $local = null ) {
		$this->state( self::STATE_OBJECT );

		$this->expandResource( $base, $local );
		$this->writeResource( $base, $local );
		return $this;
	}

	/**
	 * @see RdfWriter::text()
	 *
	 * @param string $text the text to be placed in the output
	 * @param string|null $language the language the text is in
	 *
	 * @return $this
	 */
	final public function text( $text, $language = null ) {
		$this->state( self::STATE_OBJECT );

		$this->writeText( $text, $language );
		return $this;
	}

	/**
	 * @see RdfWriter::value()
	 *
	 * @param string $value the value encoded as a string
	 * @param string|null $typeBase The data type's QName prefix if $typeLocal is given,
	 *        or an IRI or shorthand if $typeLocal is null.
	 * @param string|null $typeLocal The data type's  QName suffix,
	 *        or null if $typeBase is an IRI or shorthand.
	 *
	 * @return $this
	 */
	final public function value( $value, $typeBase = null, $typeLocal = null ) {
		$this->state( self::STATE_OBJECT );

		if ( $typeBase === null && !is_string( $value ) ) {
			$vtype = gettype( $value );
			switch ( $vtype ) {
				case 'integer':
					$typeBase = 'xsd';
					$typeLocal = 'integer';
					$value = "$value";
					break;

				case 'double':
					$typeBase = 'xsd';
					$typeLocal = 'double';
					$value = "$value";
					break;

				case 'boolean':
					$typeBase = 'xsd';
					$typeLocal = 'boolean';
					$value = $value ? 'true' : 'false';
					break;
			}
		}

		$this->expandType( $typeBase, $typeLocal );

		$this->writeValue( $value, $typeBase, $typeLocal );
		return $this;
	}

	/**
	 * State transition table
	 * First state is "from", second is "to"
	 * @var array
	 */
	protected $transitionTable = [
			self::STATE_START => [
					self::STATE_DOCUMENT => true,
			],
			self::STATE_DOCUMENT => [
					self::STATE_DOCUMENT => true,
					self::STATE_SUBJECT => true,
					self::STATE_FINISH => true,
			],
			self::STATE_SUBJECT => [
					self::STATE_PREDICATE => true,
			],
			self::STATE_PREDICATE => [
					self::STATE_OBJECT => true,
			],
			self::STATE_OBJECT => [
					self::STATE_DOCUMENT => true,
					self::STATE_SUBJECT => true,
					self::STATE_PREDICATE => true,
					self::STATE_OBJECT => true,
			],
	];

	/**
	 * Perform a state transition. Writer states roughly correspond to states in a naive
	 * regular parser for the respective syntax. State transitions may generate output,
	 * particularly of structural elements which correspond to terminals in a respective
	 * parser.
	 *
	 * @param int $newState one of the self::STATE_... constants
	 *
	 * @throws LogicException
	 */
	final protected function state( $newState ) {
		if ( !isset( $this->transitionTable[$this->state][$newState] ) ) {
			throw new LogicException( 'Bad transition: ' . $this->state . ' -> ' . $newState );
		}

		$action = $this->transitionTable[$this->state][$newState];
		if ( $action !== true ) {
			if ( is_string( $action ) ) {
				$this->write( $action );
			} else {
				$action();
			}
		}

		$this->state = $newState;
	}

	/**
	 * Must be implemented to generate output that starts a statement (or set of statements)
	 * about a subject. Depending on the requirements of the output format, the implementation
	 * may be empty.
	 *
	 * @note $base and $local are given as passed to about() and processed by expandSubject().
	 *
	 * @param string $base
	 * @param string|null $local
	 */
	abstract protected function writeSubject( $base, $local = null );

	/**
	 * Must be implemented to generate output that represents the association of a predicate
	 * with a subject that was previously defined by a call to writeSubject().
	 *
	 * @note $base and $local are given as passed to say() and processed by expandPredicate().
	 *
	 * @param string $base
	 * @param string|null $local
	 */
	abstract protected function writePredicate( $base, $local = null );

	/**
	 * Must be implemented to generate output that represents a resource used as the object
	 * of a statement.
	 *
	 * @note $base and $local are given as passed to is() and processed by expandObject().
	 *
	 * @param string $base
	 * @param string|null $local
	 */
	abstract protected function writeResource( $base, $local = null );

	/**
	 * Must be implemented to generate output that represents a text used as the object
	 * of a statement.
	 *
	 * @param string $text the text to be placed in the output
	 * @param string|null $language the language the text is in
	 */
	abstract protected function writeText( $text, $language );

	/**
	 * Must be implemented to generate output that represents a (typed) literal used as the object
	 * of a statement.
	 *
	 * @note $typeBase and $typeLocal are given as passed to value() and processed by expandType().
	 *
	 * @param string $value the value encoded as a string
	 * @param string|null $typeBase
	 * @param string|null $typeLocal
	 */
	abstract protected function writeValue( $value, $typeBase, $typeLocal = null );

	/**
	 * Perform any expansion (shorthand to qname, qname to IRI) desired
	 * for subject identifiers.
	 *
	 * @param string &$base
	 * @param string|null &$local
	 */
	protected function expandSubject( &$base, &$local ) {
	}

	/**
	 * Perform any expansion (shorthand to qname, qname to IRI) desired
	 * for predicate identifiers.
	 *
	 * @param string &$base
	 * @param string|null &$local
	 */
	protected function expandPredicate( &$base, &$local ) {
	}

	/**
	 * Perform any expansion (shorthand to qname, qname to IRI) desired
	 * for resource identifiers.
	 *
	 * @param string &$base
	 * @param string|null &$local
	 */
	protected function expandResource( &$base, &$local ) {
	}

	/**
	 * Perform any expansion (shorthand to qname, qname to IRI) desired
	 * for type identifiers.
	 *
	 * @param string|null &$base
	 * @param string|null &$local
	 */
	protected function expandType( &$base, &$local ) {
	}

}
