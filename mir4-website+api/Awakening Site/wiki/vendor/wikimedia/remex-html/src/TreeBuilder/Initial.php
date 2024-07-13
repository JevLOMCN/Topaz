<?php

namespace Wikimedia\RemexHtml\TreeBuilder;

use Wikimedia\RemexHtml\HTMLData;
use Wikimedia\RemexHtml\Tokenizer\Attributes;

/**
 * The "initial" insertion mode
 */
class Initial extends InsertionMode {
	/**
	 * The doctypes listed in the spec which are allowed without generating a
	 * parse error. A 2-d array where each row gives the doctype name, the
	 * public identifier and the system identifier.
	 *
	 * @var array
	 */
	private static $allowedDoctypes = [
		[ 'html', '-//W3C//DTD HTML 4.0//EN', null ],
		[ 'html', '-//W3C//DTD HTML 4.0//EN', 'http://www.w3.org/TR/REC-html40/strict.dtd' ],
		[ 'html', '-//W3C//DTD HTML 4.01//EN', null ],
		[ 'html', '-//W3C//DTD HTML 4.01//EN', 'http://www.w3.org/TR/html4/strict.dtd' ],
		[ 'html', '-//W3C//DTD XHTML 1.0 Strict//EN',
			'http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd' ],
		[ 'html', '-//W3C//DTD XHTML 1.1//EN', 'http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd' ]
	];

	public function characters( $text, $start, $length, $sourceStart, $sourceLength ) {
		// Ignore whitespace
		[ $part1, $part2 ] = $this->splitInitialMatch(
			true, "\t\n\f\r ", $text, $start, $length, $sourceStart, $sourceLength );
		[ $start, $length, $sourceStart, $sourceLength ] = $part2;
		if ( !$length ) {
			return;
		}
		if ( !$this->builder->isIframeSrcdoc ) {
			$this->error( 'missing doctype', $sourceStart );
			$this->builder->quirks = TreeBuilder::QUIRKS;
		}
		$this->dispatcher->switchMode( Dispatcher::BEFORE_HTML )
			->characters( $text, $start, $length, $sourceStart, $sourceLength );
	}

	public function startTag( $name, Attributes $attrs, $selfClose, $sourceStart, $sourceLength ) {
		if ( !$this->builder->isIframeSrcdoc ) {
			$this->error( 'missing doctype', $sourceStart );
			$this->builder->quirks = TreeBuilder::QUIRKS;
		}
		$this->dispatcher->switchMode( Dispatcher::BEFORE_HTML )
			->startTag( $name, $attrs, $selfClose, $sourceStart, $sourceLength );
	}

	public function endTag( $name, $sourceStart, $sourceLength ) {
		if ( !$this->builder->isIframeSrcdoc ) {
			$this->error( 'missing doctype', $sourceStart );
			$this->builder->quirks = TreeBuilder::QUIRKS;
		}
		$this->dispatcher->switchMode( Dispatcher::BEFORE_HTML )
			->endTag( $name, $sourceStart, $sourceLength );
	}

	public function doctype( $name, $public, $system, $quirks, $sourceStart, $sourceLength ) {
		if ( ( $name !== 'html' || $public !== null
				|| ( $system !== null && $system !== 'about:legacy-compat' )
			)
			&& !in_array( [ $name, $public, $system ], self::$allowedDoctypes, true )
		) {
			$this->error( 'invalid doctype', $sourceStart );
		}

		$quirks = $quirks ? TreeBuilder::QUIRKS : TreeBuilder::NO_QUIRKS;

		$quirksIfNoSystem = '~-//W3C//DTD HTML 4\.01 Frameset//|' .
			'-//W3C//DTD HTML 4\.01 Transitional//~Ai';
		$limitedQuirks = '~-//W3C//DTD XHTML 1\.0 Frameset//|' .
			'-//W3C//DTD XHTML 1\.0 Transitional//~Ai';

		if ( $name !== 'html'
			|| $public === '-//W3O//DTD W3 HTML Strict 3.0//EN//'
			|| $public === '-/W3C/DTD HTML 4.0 Transitional/EN'
			|| $public === 'HTML'
			|| $system === 'http://www.ibm.com/data/dtd/v11/ibmxhtml1-transitional.dtd'
			|| ( $system === null && preg_match( $quirksIfNoSystem, $public ?? '' ) )
			|| preg_match( HTMLData::$quirkyPrefixRegex, $public ?? '' )
		) {
			$quirks = TreeBuilder::QUIRKS;
		} elseif ( !$this->builder->isIframeSrcdoc
			&& (
				preg_match( $limitedQuirks, $public ?? '' )
				|| ( $system !== null && preg_match( $quirksIfNoSystem, $public ?? '' ) )
			)
		) {
			$quirks = TreeBuilder::LIMITED_QUIRKS;
		}

		$name ??= '';
		$public ??= '';
		$system ??= '';
		$this->builder->doctype( $name, $public, $system, $quirks,
			$sourceStart, $sourceLength );
		$this->dispatcher->switchMode( Dispatcher::BEFORE_HTML );
	}

	public function endDocument( $pos ) {
		if ( !$this->builder->isIframeSrcdoc ) {
			$this->error( 'missing doctype', $pos );
			$this->builder->quirks = TreeBuilder::QUIRKS;
		}
		$this->dispatcher->switchMode( Dispatcher::BEFORE_HTML )
			->endDocument( $pos );
	}
}
