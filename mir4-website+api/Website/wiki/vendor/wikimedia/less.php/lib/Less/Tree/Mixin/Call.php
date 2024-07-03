<?php
/**
 * @private
 */
class Less_Tree_Mixin_Call extends Less_Tree {

	public $selector;
	public $arguments;
	public $index;
	public $currentFileInfo;

	public $important;

	public function __construct( $elements, $args, $index, $currentFileInfo, $important = false ) {
		$this->selector = new Less_Tree_Selector( $elements );
		$this->arguments = $args;
		$this->index = $index;
		$this->currentFileInfo = $currentFileInfo;
		$this->important = $important;
	}

	/**
	 * @see less-2.5.3.js#MixinCall.prototype.eval
	 */
	public function compile( $env ) {
		$rules = [];
		$match = false;
		$isOneFound = false;
		$candidates = [];
		$conditionResult = [];

		$args = [];
		foreach ( $this->arguments as $a ) {
			$args[] = [ 'name' => $a['name'], 'value' => $a['value']->compile( $env ) ];
		}

		$defNone = 0;
		$defTrue = 1;
		$defFalse = 2;
		foreach ( $env->frames as $frame ) {
			$mixins = $frame->find( $this->selector );
			if ( !$mixins ) {
				continue;
			}

			$isOneFound = true;

			// To make `default()` function independent of definition order we have two "subpasses" here.
			// At first we evaluate each guard *twice* (with `default() == true` and `default() == false`),
			// and build candidate list with corresponding flags. Then, when we know all possible matches,
			// we make a final decision.

			$mixins_len = count( $mixins );
			for ( $m = 0; $m < $mixins_len; $m++ ) {
				$mixin = $mixins[$m];

				if ( $this->IsRecursive( $env, $mixin ) ) {
					continue;
				}

				if ( $mixin->matchArgs( $args, $env ) ) {

					$candidate = [ 'mixin' => $mixin, 'group' => $defNone ];

					if ( $mixin instanceof Less_Tree_Ruleset ) {
						for ( $f = 0; $f < 2; $f++ ) {
							Less_Tree_DefaultFunc::value( $f );
							$conditionResult[$f] = $mixin->matchCondition( $args, $env );
						}

						// PhanTypeInvalidDimOffset -- False positive
						'@phan-var array{0:bool,1:bool} $conditionResult';

						if ( $conditionResult[0] || $conditionResult[1] ) {
							if ( $conditionResult[0] != $conditionResult[1] ) {
								$candidate['group'] = $conditionResult[1] ? $defTrue : $defFalse;
							}

							$candidates[] = $candidate;
						}
					} else {
						$candidates[] = $candidate;
					}

					$match = true;
				}
			}

			Less_Tree_DefaultFunc::reset();

			$count = [ 0, 0, 0 ];
			for ( $m = 0; $m < count( $candidates ); $m++ ) {
				$count[ $candidates[$m]['group'] ]++;
			}

			if ( $count[$defNone] > 0 ) {
				$defaultResult = $defFalse;
			} else {
				$defaultResult = $defTrue;
				if ( ( $count[$defTrue] + $count[$defFalse] ) > 1 ) {
					throw new Exception( 'Ambiguous use of `default()` found when matching for `' . $this->format( $args ) . '`' );
				}
			}

			$candidates_length = count( $candidates );
			for ( $m = 0; $m < $candidates_length; $m++ ) {
				$candidate = $candidates[$m]['group'];
				if ( ( $candidate === $defNone ) || ( $candidate === $defaultResult ) ) {
					try {
						$mixin = $candidates[$m]['mixin'];
						if ( !( $mixin instanceof Less_Tree_Mixin_Definition ) ) {
							$originalRuleset = $mixin instanceof Less_Tree_Ruleset ? $mixin->originalRuleset : $mixin;
							$mixin = new Less_Tree_Mixin_Definition( '', [], $mixin->rules, null, false );
							$mixin->originalRuleset = $originalRuleset;
						}
						$rules = array_merge( $rules, $mixin->evalCall( $env, $args, $this->important )->rules );
					} catch ( Exception $e ) {
						throw new Less_Exception_Compiler( $e->getMessage(), null, null, $this->currentFileInfo );
					}
				}
			}

			if ( $match ) {
				if ( !$this->currentFileInfo || !isset( $this->currentFileInfo['reference'] ) || !$this->currentFileInfo['reference'] ) {
					Less_Tree::ReferencedArray( $rules );
				}

				return $rules;
			}
		}

		if ( $isOneFound ) {
			$selectorName = $this->selector->toCSS();
			throw new Less_Exception_Compiler( 'No matching definition was found for ' . $selectorName . ' with args `' . $this->Format( $args ) . '`', null, $this->index, $this->currentFileInfo );

		} else {
			throw new Less_Exception_Compiler( trim( $this->selector->toCSS() ) . " is undefined in " . $this->currentFileInfo['filename'], null, $this->index );
		}
	}

	/**
	 * Format the args for use in exception messages
	 */
	private function Format( $args ) {
		$message = [];
		if ( $args ) {
			foreach ( $args as $a ) {
				$argValue = '';
				if ( $a['name'] ) {
					$argValue .= $a['name'] . ':';
				}
				if ( is_object( $a['value'] ) ) {
					$argValue .= $a['value']->toCSS();
				} else {
					$argValue .= '???';
				}
				$message[] = $argValue;
			}
		}
		return implode( ', ', $message );
	}

	/**
	 * Are we in a recursive mixin call?
	 *
	 * @return bool
	 */
	private function IsRecursive( $env, $mixin ) {
		foreach ( $env->frames as $recur_frame ) {
			if ( !( $mixin instanceof Less_Tree_Mixin_Definition ) ) {

				if ( $mixin === $recur_frame ) {
					return true;
				}

				if ( isset( $recur_frame->originalRuleset ) && $mixin->ruleset_id === $recur_frame->originalRuleset ) {
					return true;
				}
			}
		}

		return false;
	}

}
