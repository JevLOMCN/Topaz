<?php
declare( strict_types = 1 );

namespace Wikimedia\Parsoid\ParserTests;

use Error;
use Psr\Log\LogLevel;
use Wikimedia\Alea\Alea;
use Wikimedia\Assert\Assert;
use Wikimedia\Parsoid\DOM\Document;
use Wikimedia\Parsoid\DOM\Element;
use Wikimedia\Parsoid\DOM\Node;
use Wikimedia\Parsoid\Utils\ContentUtils;
use Wikimedia\Parsoid\Utils\DOMCompat;
use Wikimedia\Parsoid\Utils\DOMUtils;
use Wikimedia\Parsoid\Utils\PHPUtils;
use Wikimedia\Parsoid\Utils\Utils;
use Wikimedia\Parsoid\Utils\WTUtils;

/**
 * Represents a parser test
 */
class Test extends Item {

	// 'testAllModes' and 'TestRunner::runTest' assume that test modes are added
	// in this order for caching to work properly (and even when test objects are cloned).
	// This ordering is enforced in computeTestModes.
	public const ALL_TEST_MODES = [ 'wt2html', 'wt2wt', 'html2html', 'html2wt', 'selser' ];

	/* --- These are test properties from the test file --- */

	/** @var ?string This is the test name, not page title for the test */
	public $testName = null;

	/** @var array */
	public $options = [];

	/** @var array */
	public $config = [];

	/** @var array */
	public $sections = [];

	/** @var array Known failures for this test, indexed by testing mode. */
	public $knownFailures = [];

	/* --- These next are computed based on an ordered list of preferred
	*      section keys --- */

	/** @var ?string */
	public $wikitext = null;

	/** @var ?string */
	public $parsoidHtml = null;

	/** @var ?string */
	public $legacyHtml = null;

	/* --- The rest below are computed by Parsoid while running tests -- */

	/** @var string */
	private $pageName;

	/** @var int */
	private $pageNs;

	/** @var array */
	public $selserChangeTrees = [];

	/** @var ?array */
	public $changetree = null;

	/** @var bool */
	public $duplicateChange = false;

	/** @var ?string */
	public $seed = null;

	/** @var ?string */
	public $resultWT = null;

	/** @var ?bool */
	public $wt2wtPassed = null;

	/** @var ?string */
	public $wt2wtResult = null;

	/** @var ?string */
	public $selser = null;

	/** @var ?string */
	public $changedHTMLStr = null;

	/** @var ?string */
	public $cachedBODYstr = null;

	/** @var ?string */
	public $cachedWTstr = null;

	/** @var ?string */
	public $cachedNormalizedHTML = null;

	/** @var array */
	public $time = [];

	private const DIRECT_KEYS = [
		'type',
		'filename',
		'lineNumStart',
		'lineNumEnd',
		'testName',
		'options',
		'config',
	];
	private const WIKITEXT_KEYS = [
		'wikitext',
		# deprecated
		'input',
	];
	private const LEGACY_HTML_KEYS = [
		'html/php', 'html/*', 'html',
		# deprecated
		'result',
		'html/php+tidy',
		'html/*+tidy',
		'html+tidy',
	];
	private const PARSOID_HTML_KEYS = [
		'html/parsoid', 'html/*', 'html',
		# deprecated
		'result',
		'html/*+tidy',
		'html+tidy',
	];
	private const WARN_DEPRECATED_KEYS = [
		'input',
		'result',
		'html/php+tidy',
		'html/*+tidy',
		'html+tidy',
		'html/php+untidy',
		'html+untidy',
	];

	/**
	 * @param array $testProperties key-value mapping of properties
	 * @param array $knownFailures Known failures for this test, indexed by testing mode
	 * @param ?string $comment Optional comment describing the test
	 * @param ?callable $warnFunc Optional callback used to emit
	 *   deprecation warnings.
	 */
	public function __construct(
		array $testProperties,
		array $knownFailures = [],
		?string $comment = null,
		?callable $warnFunc = null
	) {
		parent::__construct( $testProperties, $comment );
		$this->knownFailures = $knownFailures;

		foreach ( $testProperties as $key => $value ) {
			if ( in_array( $key, self::DIRECT_KEYS, true ) ) {
				$this->$key = $value;
			} else {
				if ( isset( $this->sections[$key] ) ) {
					$this->error( "Duplicate test section", $key );
				}
				$this->sections[$key] = $value;
			}
		}

		# Priority order for wikitext, legacyHtml, and parsoidHtml properties
		$cats = [
			'wikitext' => self::WIKITEXT_KEYS,
			'legacyHtml' => self::LEGACY_HTML_KEYS,
			'parsoidHtml' => self::PARSOID_HTML_KEYS,
		];
		foreach ( $cats as $prop => $keys ) {
			foreach ( $keys as $key ) {
				if ( isset( $this->sections[$key] ) ) {
					$this->$prop = $this->sections[$key];
					break;
				}
			}
		}

		# Deprecation warnings
		if ( $warnFunc ) {
			foreach ( self::WARN_DEPRECATED_KEYS as $key ) {
				if ( isset( $this->sections[$key] ) ) {
					$warnFunc( $this->errorMsg(
						"Parser test section $key is deprecated"
					) );
				}
			}
		}
	}

	/**
	 * @param array $testFilter Test Filter as set in TestRunner
	 * @return bool if test matches the filter
	 */
	public function matchesFilter( $testFilter ): bool {
		if ( !$testFilter ) {
			return true; // Trivial match
		}

		if ( !empty( $testFilter['regex'] ) ) {
			$regex = isset( $testFilter['raw'] ) ?
				   ( '/' . $testFilter['raw'] . '/' ) :
				   $testFilter['regex'];
			return (bool)preg_match( $regex, $this->testName );
		}

		if ( !empty( $testFilter['string'] ) ) {
			return strpos( $this->testName, $testFilter['raw'] ) !== false;
		}

		return true; // Trivial match because of a bad test filter
	}

	public function pageName(): string {
		if ( !$this->pageName ) {
			$this->pageName = $this->options['title'] ?? 'Parser test';
			if ( is_array( $this->pageName ) ) {
				$this->pageName = 'Parser test';
			}
		}

		return $this->pageName;
	}

	/**
	 * Given a test runner that runs in a specific set of test modes ($testRunnerModes)
	 * compute the list of valid test modes based on what modes have been enabled on the
	 * test itself.
	 *
	 * @param array $testRunnerModes What test modes is the test runner running with?
	 * @return array
	 */
	public function computeTestModes( array $testRunnerModes ): array {
		// Ensure we compute valid modes in the order specificed in ALL_TEST_MODES since
		// caching in the presence of test cloning rely on tests running in this order.
		$validModes = array_intersect( self::ALL_TEST_MODES, $testRunnerModes );

		// Filter for modes the test has opted in for
		$testModes = $this->options['parsoid']['modes'] ?? null;
		if ( $testModes ) {
			$selserEnabled = in_array( 'selser', $testRunnerModes, true );
			// Avoid filtering out the selser test
			if ( $selserEnabled &&
				!in_array( 'selser', $testModes, true ) &&
				in_array( 'wt2wt', $testModes, true )
			) {
				$testModes[] = 'selser';
			}

			$validModes = array_intersect( $validModes, $testModes );
		}

		return $validModes;
	}

	// Random string used as selser comment content
	public const STATIC_RANDOM_STRING = 'ahseeyooxooZ8Oon0boh';

	/**
	 * Apply manually-specified changes, which are provided in a pseudo-jQuery
	 * format.
	 *
	 * @param Document $doc
	 */
	public function applyManualChanges( Document $doc ) {
		$changes = $this->options['parsoid']['changes'];
		$err = null;
		// changes are specified using jquery methods.
		//  [x,y,z...] becomes $(x)[y](z....)
		// that is, ['fig', 'attr', 'width', '120'] is interpreted as
		//   $('fig').attr('width', '120')
		// See http://api.jquery.com/ for documentation of these methods.
		// "contents" as second argument calls the jquery .contents() method
		// on the results of the selector in the first argument, which is
		// a good way to get at the text and comment nodes
		$jquery = [
			'after' => static function ( Node $node, string $html ) {
				$div = null;
				$tbl = null;
				if ( DOMCompat::nodeName( $node->parentNode ) === 'tbody' ) {
					$tbl = $node->ownerDocument->createElement( 'table' );
					DOMCompat::setInnerHTML( $tbl, $html );
					// <tbody> is implicitly added when inner html is set to <tr>..</tr>
					DOMUtils::migrateChildren( $tbl->firstChild, $node->parentNode, $node->nextSibling );
				} elseif ( DOMCompat::nodeName( $node->parentNode ) === 'tr' ) {
					$tbl = $node->ownerDocument->createElement( 'table' );
					DOMCompat::setInnerHTML( $tbl, '<tbody><tr></tr></tbody>' );
					$tr = $tbl->firstChild->firstChild;
					'@phan-var Element $tr'; // @var Element $tr
					DOMCompat::setInnerHTML( $tr, $html );
					DOMUtils::migrateChildren( $tbl->firstChild->firstChild,
						$node->parentNode, $node->nextSibling );
				} else {
					$div = $node->ownerDocument->createElement( 'div' );
					DOMCompat::setInnerHTML( $div, $html );
					DOMUtils::migrateChildren( $div, $node->parentNode, $node->nextSibling );
				}
			},
			'append' => static function ( Node $node, string $html ) {
				if ( DOMCompat::nodeName( $node ) === 'tr' ) {
					$tbl = $node->ownerDocument->createElement( 'table' );
					DOMCompat::setInnerHTML( $tbl, $html );
					// <tbody> is implicitly added when inner html is set to <tr>..</tr>
					DOMUtils::migrateChildren( $tbl->firstChild, $node );
				} else {
					$div = $node->ownerDocument->createElement( 'div' );
					DOMCompat::setInnerHTML( $div, $html );
					DOMUtils::migrateChildren( $div, $node );
				}
			},
			'attr' => static function ( Node $node, string $name, string $val ) {
				'@phan-var Element $node'; // @var Element $node
				$node->setAttribute( $name, $val );
			},
			'before' => static function ( Node $node, string $html ) {
				$div = null;
				$tbl = null;
				if ( DOMCompat::nodeName( $node->parentNode ) === 'tbody' ) {
					$tbl = $node->ownerDocument->createElement( 'table' );
					DOMCompat::setInnerHTML( $tbl, $html );
					// <tbody> is implicitly added when inner html is set to <tr>..</tr>
					DOMUtils::migrateChildren( $tbl->firstChild, $node->parentNode, $node );
				} elseif ( DOMCompat::nodeName( $node->parentNode ) === 'tr' ) {
					$tbl = $node->ownerDocument->createElement( 'table' );
					DOMCompat::setInnerHTML( $tbl, '<tbody><tr></tr></tbody>' );
					$tr = $tbl->firstChild->firstChild;
					'@phan-var Element $tr'; // @var Element $tr
					DOMCompat::setInnerHTML( $tr, $html );
					DOMUtils::migrateChildren( $tbl->firstChild->firstChild, $node->parentNode, $node );
				} else {
					$div = $node->ownerDocument->createElement( 'div' );
					DOMCompat::setInnerHTML( $div, $html );
					DOMUtils::migrateChildren( $div, $node->parentNode, $node );
				}
			},
			'removeAttr' => static function ( Node $node, string $name ) {
				'@phan-var Element $node'; // @var Element $node
				$node->removeAttribute( $name );
			},
			'removeClass' => static function ( Node $node, string $c ) {
				'@phan-var Element $node'; // @var Element $node
				DOMCompat::getClassList( $node )->remove( $c );
			},
			'addClass' => static function ( Node $node, string $c ) {
				'@phan-var Element $node'; // @var Element $node
				DOMCompat::getClassList( $node )->add( $c );
			},
			'text' => static function ( Node $node, string $t ) {
				$node->textContent = $t;
			},
			'html' => static function ( Node $node, string $h ) {
				'@phan-var Element $node'; // @var Element $node
				DOMCompat::setInnerHTML( $node, $h );
			},
			'remove' => static function ( Node $node, string $optSelector = null ) {
				// jquery lets us specify an optional selector to further
				// restrict the removed elements.
				// text nodes don't have the "querySelectorAll" method, so
				// just include them by default (jquery excludes them, which
				// is less useful)
				if ( !$optSelector ) {
					$what = [ $node ];
				} elseif ( !( $node instanceof Element ) ) {
					$what = [ $node ];/* text node hack! */
				} else {
					'@phan-var Element $node'; // @var Element $node
					$what = DOMCompat::querySelectorAll( $node, $optSelector );
				}
				foreach ( $what as $node ) {
					if ( $node->parentNode ) {
						$node->parentNode->removeChild( $node );
					}
				}
			},
			'empty' => static function ( Node $node ) {
				'@phan-var Element $node'; // @var Element $node
				DOMCompat::replaceChildren( $node );
			},
			'wrap' => static function ( Node $node, string $w ) {
				$frag = $node->ownerDocument->createElement( 'div' );
				DOMCompat::setInnerHTML( $frag, $w );
				$first = $frag->firstChild;
				$node->parentNode->replaceChild( $first, $node );
				while ( $first->firstChild ) {
					$first = $first->firstChild;
				}
				$first->appendChild( $node );
			}
		];

		$body = DOMCompat::getBody( $doc );

		foreach ( $changes as $change ) {
			if ( $err ) {
				continue;
			}
			if ( count( $change ) < 2 ) {
				$err = new Error( 'bad change: ' . $change );
				continue;
			}
			// use document.querySelectorAll as a poor man's $(...)
			$els = PHPUtils::iterable_to_array(
				DOMCompat::querySelectorAll( $body, $change[0] )
			);
			if ( !count( $els ) ) {
				$err = new Error( $change[0] .
					' did not match any elements: ' . DOMCompat::getOuterHTML( $body ) );
				continue;
			}
			if ( $change[1] === 'contents' ) {
				$change = array_slice( $change, 1 );
				$acc = [];
				foreach ( $els as $el ) {
					PHPUtils::pushArray( $acc, iterator_to_array( $el->childNodes ) );
				}
				$els = $acc;
			}
			$fn = $jquery[$change[1]] ?? null;
			if ( !$fn ) {
				$err = new Error( 'bad mutator function: ' . $change[1] );
				continue;
			}
			foreach ( $els as $el ) {
				call_user_func_array( $fn, array_merge( [ $el ], array_slice( $change, 2 ) ) );
			}
		}

		if ( $err ) {
			print TestUtils::colorString( (string)$err, "red" ) . "\n";
			throw $err;
		}
	}

	/**
	 * Make changes to a DOM in order to run a selser test on it.
	 *
	 * @param array $dumpOpts
	 * @param Document $doc
	 * @param array $changelist
	 */
	public function applyChanges( array $dumpOpts, Document $doc, array $changelist ) {
		$logger = $dumpOpts['logger'] ?? null;
		// Seed the random-number generator based on the item title and changelist
		$alea = new Alea( ( json_encode( $changelist ) ) . ( $this->testName ?? '' ) );

		// Keep the changes in the test object
		// to check for duplicates while building tasks
		$this->changetree = $changelist;

		// Helper function for getting a random string
		$randomString = static function () use ( &$alea ): string {
			return base_convert( (string)$alea->uint32(), 10, 36 );
		};

		$insertNewNode = static function ( Node $n ) use ( $randomString ): void {
			// Insert a text node, if not in a fosterable position.
			// If in foster position, enter a comment.
			// In either case, dom-diff should register a new node
			$str = $randomString();
			$ownerDoc = $n->ownerDocument;
			$wrapperName = null;
			$newNode = null;

			// Don't separate legacy IDs from their H? node.
			if ( WTUtils::isFallbackIdSpan( $n ) ) {
				$n = $n->nextSibling ?? $n->parentNode;
			}

			// For these container nodes, it would be buggy
			// to insert text nodes as children
			switch ( DOMCompat::nodeName( $n->parentNode ) ) {
				case 'ol':
				case 'ul':
					$wrapperName = 'li';
					break;
				case 'dl':
					$wrapperName = 'dd';
					break;
				case 'tr':
					$prev = DOMCompat::getPreviousElementSibling( $n );
					if ( $prev ) {
						// TH or TD
						$wrapperName = DOMCompat::nodeName( $prev );
					} else {
						$next = DOMCompat::getNextElementSibling( $n );
						if ( $next ) {
							// TH or TD
							$wrapperName = DOMCompat::nodeName( $next );
						} else {
							$wrapperName = 'td';
						}
					}
					break;
				case 'body':
					$wrapperName = 'p';
					break;
				default:
					if ( WTUtils::isBlockNodeWithVisibleWT( $n ) ) {
						$wrapperName = 'p';
					}
					break;
			}

			if ( DOMUtils::isFosterablePosition( $n ) && DOMCompat::nodeName( $n->parentNode ) !== 'tr' ) {
				$newNode = $ownerDoc->createComment( $str );
			} elseif ( $wrapperName ) {
				$newNode = $ownerDoc->createElement( $wrapperName );
				$newNode->appendChild( $ownerDoc->createTextNode( $str ) );
			} else {
				$newNode = $ownerDoc->createTextNode( $str );
			}

			$n->parentNode->insertBefore( $newNode, $n );
		};

		$removeNode = static function ( Node $n ): void {
			$n->parentNode->removeChild( $n );
		};

		$applyChangesInternal = static function ( Node $node, array $changes ) use (
			&$applyChangesInternal, $removeNode, $insertNewNode,
			$randomString, $logger
		): void {
			if ( count( $node->childNodes ) < count( $changes ) ) {
				throw new Error( "Error: more changes than nodes to apply them to!" );
			}

			// Clone array since we are mutating the children in the changes loop below
			$nodeArray = [];
			foreach ( $node->childNodes as $n ) {
				$nodeArray[] = $n;
			}

			foreach ( $changes as $i => $change ) {
				$child = $nodeArray[$i];

				if ( is_array( $change ) ) {
					$applyChangesInternal( $child, $change );
				} else {
					switch ( $change ) {
						// No change
						case 0:
							break;

						// Change node wrapper
						// (sufficient to insert a random attr)
						case 1:
							if ( $child instanceof Element ) {
								$child->setAttribute( 'data-foobar', $randomString() );
							} elseif ( $logger ) {
								$logger->log(
									LogLevel::ERROR,
									'Buggy changetree. changetype 1 (modify attribute)' .
									' cannot be applied on text/comment nodes.'
								);
							}
							break;

						// Insert new node before child
						case 2:
							$insertNewNode( $child );
							break;

						// Delete tree rooted at child
						case 3:
							$removeNode( $child );
							break;

						// Change tree rooted at child
						case 4:
							$insertNewNode( $child );
							$removeNode( $child );
							break;
					}

				}
			}
		};

		$body = DOMCompat::getBody( $doc );

		if ( $logger && ( $dumpOpts['dom:post-changes'] ?? false ) ) {
			$logger->log( LogLevel::ERROR, "----- Original DOM -----" );
			$logger->log( LogLevel::ERROR, ContentUtils::dumpDOM( $body, '', [ 'quiet' => true ] ) );
		}

		if ( $this->changetree === [ 5 ] ) {
			// Hack so that we can work on the parent node rather than just the
			// children: Append a comment with known content. This is later
			// stripped from the output, and the result is compared to the
			// original wikitext rather than the non-selser wt2wt result.
			$body->appendChild( $doc->createComment( self::STATIC_RANDOM_STRING ) );
		} elseif ( $this->changetree !== [] ) {
			$applyChangesInternal( $body, $this->changetree );
		}

		if ( $logger && ( $dumpOpts['dom:post-changes'] ?? false ) ) {
			$logger->log( LogLevel::ERROR, "----- Change Tree -----" );
			$logger->log( LogLevel::ERROR, json_encode( $this->changetree ) );
			$logger->log( LogLevel::ERROR, "----- Edited DOM -----" );
			$logger->log( LogLevel::ERROR, ContentUtils::dumpDOM( $body, '', [ 'quiet' => true ] ) );
		}
	}

	/**
	 * For a selser test, check if a change we could make has already been
	 * tested in this round.
	 * Used for generating unique tests.
	 *
	 * @param array $change Candidate change.
	 * @return bool
	 */
	public function isDuplicateChangeTree( array $change ): bool {
		$allChanges = $this->selserChangeTrees;
		foreach ( $allChanges as $c ) {
			if ( $c == $change ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Generate a change object for a document, so we can apply it during a selser test.
	 *
	 * @param Document $doc
	 * @return array The list of changes.
	 */
	public function generateChanges( Document $doc ): array {
		$alea = new Alea( ( $this->seed ?? '' ) . ( $this->testName ?? '' ) );

		/**
		 * If no node in the DOM subtree rooted at 'node' is editable in the VE,
		 * this function should return false.
		 *
		 * Currently true for template and extension content, and for entities.
		 */
		$domSubtreeIsEditable = static function ( Node $node ): bool {
			return !( $node instanceof Element ) ||
				( !WTUtils::isEncapsulationWrapper( $node ) &&
					// These wrappers can only be edited in restricted ways.
					// Simpler to just block all editing on them.
					!DOMUtils::matchTypeOf( $node,
						'#^mw:(Entity|Placeholder|DisplaySpace|Annotation|ExtendedAnnRange)(/|$)#'
					) &&
					// Deleting these wrappers is tantamount to removing the
					// references-tag encapsulation wrappers, which results in errors.
					!DOMUtils::hasClass( $node, 'mw-references-wrap' )
				);
		};

		/**
		 * Even if a DOM subtree might be editable in the VE,
		 * certain nodes in the DOM might not be directly editable.
		 *
		 * Currently, this restriction is only applied to DOMs generated for images.
		 * Possibly, there are other candidates.
		 */
		$nodeIsUneditable = static function ( Node $node ) use ( &$nodeIsUneditable ): bool {
			// Text and comment nodes are always editable
			if ( !( $node instanceof Element ) ) {
				return false;
			}

			if ( WTUtils::isMarkerAnnotation( $node ) ) {
				return true;
			}

			// - File wrapper is an uneditable elt.
			// - Any node nested in a file wrapper that is not a figcaption
			//   is an uneditable elt.
			// - Entity spans are uneditable as well
			// - Placeholder is defined to be uneditable in the spec
			// - ExtendedAnnRange is an "unknown" type in the spec, and hence uneditable
			return DOMUtils::matchTypeOf( $node,
					'#^mw:(File|Entity|Placeholder|DisplaySpace|ExtendedAnnRange)(/|$)#' ) || (
				DOMCompat::nodeName( $node ) !== 'figcaption' &&
				$node->parentNode &&
				DOMCompat::nodeName( $node->parentNode ) !== 'body' &&
				$nodeIsUneditable( $node->parentNode )
			);
		};

		$defaultChangeType = 0;

		$hasChangeMarkers = static function ( array $list ) use (
			&$hasChangeMarkers, $defaultChangeType
		): bool {
			// If all recorded changes are 0, then nothing has been modified
			foreach ( $list as $c ) {
				if ( ( is_array( $c ) && $hasChangeMarkers( $c ) ) ||
					( !is_array( $c ) && $c !== $defaultChangeType )
				) {
					return true;
				}
			}
			return false;
		};

		$genChangesInternal = static function ( Node $node ) use (
			&$genChangesInternal, &$hasChangeMarkers,
			$domSubtreeIsEditable, $nodeIsUneditable, $alea,
			$defaultChangeType
		): array {
			// Seed the random-number generator based on the item title
			$changelist = [];
			$children = $node->childNodes ? iterator_to_array( $node->childNodes ) : [];
			foreach ( $children as $child ) {
				$changeType = $defaultChangeType;
				if ( $domSubtreeIsEditable( $child ) ) {
					if ( $nodeIsUneditable( $child ) || $alea->random() < 0.5 ) {
						// This call to random is a hack to preserve the current
						// determined state of our knownFailures entries after a
						// refactor.
						$alea->uint32();
						$changeType = $genChangesInternal( $child );
						// `$genChangesInternal` returns an array, which can be
						// empty.  Revert to the `$defaultChangeType` if that's
						// the case.
						if ( count( $changeType ) === 0 ) {
							$changeType = $defaultChangeType;
						}
					} else {
						if ( !( $child instanceof Element ) ) {
							// Text or comment node -- valid changes: 2, 3, 4
							// since we cannot set attributes on these
							$changeType = floor( $alea->random() * 3 ) + 2;
						} else {
							$changeType = floor( $alea->random() * 4 ) + 1;
						}
					}
				}

				$changelist[] = $changeType;

			}

			return $hasChangeMarkers( $changelist ) ? $changelist : [];
		};

		$body = DOMCompat::getBody( $doc );

		$changetree = null;
		$numAttempts = 0;
		do {
			$numAttempts++;
			$changetree = $genChangesInternal( $body );
		} while (
			$numAttempts < 1000 &&
			( count( $changetree ) === 0 ||
				$this->isDuplicateChangeTree( $changetree ) )
		);

		if ( $numAttempts === 1000 ) {
			// couldn't generate a change ... marking as such
			$this->duplicateChange = true;
		}

		return $changetree;
	}

	/**
	 * FIXME: clean up this mess!
	 * - generate all changes at once (generateChanges should return a tree really)
	 *   rather than going to all these lengths of interleaving change
	 *   generation with tests
	 * - set up the changes in item directly rather than juggling around with
	 *   indexes etc
	 * - indicate whether to compare to wt2wt or the original input
	 * - maybe make a full selser test one method that uses others rather than the
	 *   current chain of methods that sometimes do something for selser
	 *
	 * @param array $targetModes
	 * @param array $runnerOpts
	 * @param callable $runTest
	 */
	public function testAllModes( // phpcs:ignore MediaWiki.Commenting.MissingCovers.MissingCovers
		array $targetModes, array $runnerOpts, callable $runTest
	): void {
		if ( !$this->testName ) {
			throw new Error( 'Missing title from test case.' );
		}
		$selserNoAuto = ( ( $runnerOpts['selser'] ?? false ) === 'noauto' );

		foreach ( $targetModes as $targetMode ) {
			if (
				$targetMode === 'selser' &&
				!( $selserNoAuto || isset( $runnerOpts['changetree'] ) )
			) {
				// Run selser tests in the following order:
				// 1. Manual changes (if provided)
				// 2. changetree 5 (oracle exists for verifying output)
				// 3. All other change trees (no oracle exists for verifying output)

				if ( isset( $this->options['parsoid']['changes'] ) ) {
					// Mutating the item here is necessary to output 'manual' in
					// the test's title and to differentiate it for knownFailures.
					$this->changetree = [ 'manual' ];
					$runTest( $this, 'selser', $runnerOpts );
				}

				// Skip the rest if the test doesn't want changetrees
				if ( ( $this->options['parsoid']['selser'] ?? '' ) === 'noauto' ) {
					continue;
				}

				// Changetree 5 (append a comment to the root node)
				$this->changetree = [ 5 ];
				$runTest( $this, 'selser', $runnerOpts );

				// Automatically generated changed trees
				$this->selserChangeTrees = [];
				for ( $j = 0; $j < $runnerOpts['numchanges']; $j++ ) {
					// Set changetree to null to ensure we don't assume [ 5 ] in $runTest
					$this->changetree = null;
					$this->seed = $j . '';
					$runTest( $this, 'selser', $runnerOpts );
					if ( $this->isDuplicateChangeTree( $this->changetree ) ) {
						// Once we get a duplicate change tree, we can no longer
						// generate and run new tests. So, be done now!
						break;
					} else {
						$this->selserChangeTrees[$j] = $this->changetree;
					}
				}
			} elseif ( $targetMode === 'selser' && $selserNoAuto ) {
				// Manual changes were requested on the command line,
				// check that the item does have them.
				if ( isset( $this->options['parsoid']['changes'] ) ) {
					$this->changetree = [ 'manual' ];
					$runTest( $this, 'selser', $runnerOpts );
				}
				continue;
			} else {
				if ( $targetMode === 'wt2html' && isset( $this->sections['html/parsoid+langconv'] ) ) {
					// Since we are clobbering options and parsoidHtml, clone the test object
					$testClone = Utils::clone( $this );
					$testClone->options['langconv'] = true;
					$testClone->parsoidHtml = $this->sections['html/parsoid+langconv'];
					$runTest( $testClone, $targetMode, $runnerOpts );
					if ( $this->parsoidHtml === null ) {
						// Don't run the same test in non-langconv mode
						// unless we have a non-langconv section
						continue;
					}
				}

				Assert::invariant(
					$targetMode !== 'selser' ||
					( isset( $runnerOpts['changetree'] ) && !$selserNoAuto ),
					"Unexpected target mode $targetMode" );

				$runTest( $this, $targetMode, $runnerOpts );
			}
		}
	}

	/**
	 * Normalize expected and actual HTML to suppress irrelevant differences.
	 * The normalization is determined by the HTML sections present in the test
	 * as well as other Parsoid-specific test options.
	 *
	 * @param Element|string $actual
	 * @param ?string $normExpected
	 * @param bool $standalone
	 * @return array
	 */
	public function normalizeHTML( $actual, ?string $normExpected, bool $standalone = true ): array {
		$opts = $this->options;
		$haveStandaloneHTML = $standalone && isset( $this->sections['html/parsoid+standalone'] );
		$haveIntegratedHTML = !$standalone && isset( $this->sections['html/parsoid+integrated'] );
		$parsoidOnly = isset( $this->sections['html/parsoid'] ) ||
			$haveStandaloneHTML ||
			$haveIntegratedHTML ||
			isset( $this->sections['html/parsoid+langconv'] ) ||
			( isset( $opts['parsoid'] ) && !isset( $opts['parsoid']['normalizePhp'] ) );
		$externalLinkTarget = ( $opts['externallinktarget'] ?? false ) ||
			isset( $this->config['wgExternalLinkTarget'] ) ||
			isset( $this->config['wgNoFollowLinks'] ) ||
			isset( $this->config['wgNoFollowDomainExceptions'] );
		$normOpts = [
			'parsoidOnly' => $parsoidOnly,
			'preserveIEW' => isset( $opts['parsoid']['preserveIEW'] ),
			'externallinktarget' => $externalLinkTarget,
		];

		if ( $normExpected === null ) {
			if ( $haveIntegratedHTML ) {
				$parsoidHTML = $this->sections['html/parsoid+integrated'];
			} elseif ( $haveStandaloneHTML ) {
				$parsoidHTML = $this->sections['html/parsoid+standalone'];
			} else {
				$parsoidHTML = $this->parsoidHtml;
			}
			if ( $parsoidOnly ) {
				$normExpected = TestUtils::normalizeOut( $parsoidHTML, $normOpts );
			} else {
				$normExpected = TestUtils::normalizeHTML( $parsoidHTML );
			}
			$this->cachedNormalizedHTML = $normExpected;
		}

		return [ TestUtils::normalizeOut( $actual, $normOpts ), $normExpected ];
	}

	/**
	 * Normalize expected and actual wikitext to suppress irrelevant differences.
	 *
	 * Because of selser as well as manual edit trees, expected wikitext isn't always
	 * found in the same section for all tests ending in WT (unlike normalizeHTML).
	 * Hence,
	 * (a) this code has a different structure than normalizeHTML
	 * (b) we cannot cache normalized wikitext
	 *
	 * @param string $actual
	 * @param string $expected
	 * @param bool $standalone
	 * @return array
	 */
	public function normalizeWT( string $actual, string $expected, bool $standalone = true ): array {
		// No other normalizations at this time
		$normalizedActual = rtrim( $actual, "\n" );
		$normalizedExpected = rtrim( $expected, "\n" );

		return [ $normalizedActual, $normalizedExpected ];
	}
}
