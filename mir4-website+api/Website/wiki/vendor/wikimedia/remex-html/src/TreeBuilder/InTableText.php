<?php

namespace Wikimedia\RemexHtml\TreeBuilder;

use Wikimedia\RemexHtml\Tokenizer\Attributes;

/**
 * The "in table text" insertion mode
 */
class InTableText extends InsertionMode {
	public function characters( $text, $start, $length, $sourceStart, $sourceLength ) {
		$handleNonNull = function ( $text, $start, $length, $sourceStart, $sourceLength ) {
			$this->builder->pendingTableCharacters[] = [
				$text, $start, $length, $sourceStart, $sourceLength ];
		};
		if ( !$this->builder->ignoreNulls ) {
			$this->stripNulls( $handleNonNull, $text, $start, $length,
				$sourceStart, $sourceLength );
		} else {
			$handleNonNull( $text, $start, $length, $sourceStart, $sourceLength );
		}
	}

	public function doctype( $name, $public, $system, $quirks, $sourceStart, $sourceLength ) {
		$this->processPendingCharacters();
		$this->dispatcher->restoreMode()
			->doctype( $name, $public, $system, $quirks, $sourceStart, $sourceLength );
	}

	public function comment( $text, $sourceStart, $sourceLength ) {
		$this->processPendingCharacters();
		$this->dispatcher->restoreMode()
			->comment( $text, $sourceStart, $sourceLength );
	}

	public function startTag( $name, Attributes $attrs, $selfClose, $sourceStart, $sourceLength ) {
		$this->processPendingCharacters();
		$this->dispatcher->restoreMode()
			->startTag( $name, $attrs, $selfClose, $sourceStart, $sourceLength );
	}

	public function endTag( $name, $sourceStart, $sourceLength ) {
		$this->processPendingCharacters();
		$this->dispatcher->restoreMode()
			->endTag( $name, $sourceStart, $sourceLength );
	}

	public function endDocument( $pos ) {
		$this->processPendingCharacters();
		$this->dispatcher->restoreMode()
			->endDocument( $pos );
	}

	/**
	 * Flush the pending table text and restore the mode
	 */
	public function flush() {
		$this->processPendingCharacters();
		$this->dispatcher->restoreMode();
	}

	/**
	 * Common code for the "anything else" case. Process the pending table
	 * character tokens.
	 */
	protected function processPendingCharacters() {
		$builder = $this->builder;
		$allSpace = true;
		foreach ( $builder->pendingTableCharacters as $token ) {
			[ $text, $start, $length, $sourceStart, $sourceLength ] = $token;
			if ( strspn( $text, "\t\n\f\r ", $start, $length ) !== $length ) {
				$allSpace = false;
			}
		}
		if ( $allSpace ) {
			foreach ( $builder->pendingTableCharacters as $token ) {
				[ $text, $start, $length, $sourceStart, $sourceLength ] = $token;
				$builder->insertCharacters( $text, $start, $length, $sourceStart, $sourceLength );
			}
		} else {
			$builder->fosterParenting = true;
			foreach ( $builder->pendingTableCharacters as $token ) {
				[ $text, $start, $length, $sourceStart, $sourceLength ] = $token;
				$builder->error( 'invalid characters in table text, fostering', $sourceStart );
				$this->dispatcher->inBody->characters( $text, $start, $length,
					$sourceStart, $sourceLength );
			}
			$builder->fosterParenting = false;
		}
	}
}
