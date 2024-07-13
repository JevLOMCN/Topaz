<?php

namespace Shellbox\ShellParser;


	use Wikimedia\WikiPEG\InternalError;
	// @phan-file-suppress PhanUnusedGotoLabel
	// @phan-file-suppress PhanNoopSwitchCases
	// @phan-file-suppress PhanPluginNeverReturnMethod
	// @phan-file-suppress PhanPluginUnreachableCode


class PEGParser extends \Wikimedia\WikiPEG\PEGParserBase {
  // initializer
  
  	/**
  	 * Overridable tree node constructor
  	 *
  	 * @stable to override
  	 * @param string $type
  	 * @param array|Node|string $contents
  	 * @return Node
  	 */
  	protected function node( $type, $contents ) {
  		return new Node( $type, $contents );
  	}
  
  	/**
  	 * Combine arrays and non-array items into a single flat array.
  	 *
  	 * @param array|Node|string|null ...$items
  	 * @return array
  	 * @phan-return array<Node|string>
  	 */
  	private function merge( ...$items ) {
  		if ( !$items ) {
  			return [];
  		}
  		$mergeArgs = [];
  		foreach ( $items as $item ) {
  			if ( $item !== null ) {
  				if ( !is_array( $item ) ) {
  					$mergeArgs[] = [ $item ];
  				} else {
  					$mergeArgs[] = $item;
  				}
  			}
  		}
  		return array_merge( ...$mergeArgs );
  	}
  

  // cache init
    protected $cache = [];

  // expectations
  protected $expectations = [
    0 => ["type" => "end", "description" => "end of input"],
    1 => ["type" => "literal", "value" => "\x0a", "description" => "\"\\n\""],
    2 => ["type" => "class", "value" => "[ \\t\\v\\r\\f]", "description" => "[ \\t\\v\\r\\f]"],
    3 => ["type" => "literal", "value" => "#", "description" => "\"#\""],
    4 => ["type" => "class", "value" => "[^\\n]", "description" => "[^\\n]"],
    5 => ["type" => "literal", "value" => "&&", "description" => "\"&&\""],
    6 => ["type" => "literal", "value" => "||", "description" => "\"||\""],
    7 => ["type" => "literal", "value" => "&", "description" => "\"&\""],
    8 => ["type" => "literal", "value" => ";", "description" => "\";\""],
    9 => ["type" => "literal", "value" => "!", "description" => "\"!\""],
    10 => ["type" => "literal", "value" => ";;", "description" => "\";;\""],
    11 => ["type" => "literal", "value" => "|", "description" => "\"|\""],
    12 => ["type" => "class", "value" => "[_a-zA-Z]", "description" => "[_a-zA-Z]"],
    13 => ["type" => "class", "value" => "[_a-zA-Z0-9]", "description" => "[_a-zA-Z0-9]"],
    14 => ["type" => "literal", "value" => "(", "description" => "\"(\""],
    15 => ["type" => "literal", "value" => ")", "description" => "\")\""],
    16 => ["type" => "literal", "value" => "=", "description" => "\"=\""],
    17 => ["type" => "literal", "value" => "{", "description" => "\"{\""],
    18 => ["type" => "literal", "value" => "}", "description" => "\"}\""],
    19 => ["type" => "literal", "value" => "for", "description" => "\"for\""],
    20 => ["type" => "literal", "value" => "case", "description" => "\"case\""],
    21 => ["type" => "literal", "value" => "esac", "description" => "\"esac\""],
    22 => ["type" => "literal", "value" => "if", "description" => "\"if\""],
    23 => ["type" => "literal", "value" => "then", "description" => "\"then\""],
    24 => ["type" => "literal", "value" => "fi", "description" => "\"fi\""],
    25 => ["type" => "literal", "value" => "while", "description" => "\"while\""],
    26 => ["type" => "literal", "value" => "until", "description" => "\"until\""],
    27 => ["type" => "class", "value" => "[0-9]", "description" => "[0-9]"],
    28 => ["type" => "literal", "value" => "'", "description" => "\"'\""],
    29 => ["type" => "class", "value" => "[^']", "description" => "[^']"],
    30 => ["type" => "literal", "value" => "\"", "description" => "\"\\\"\""],
    31 => ["type" => "literal", "value" => "\\", "description" => "\"\\\\\""],
    32 => ["type" => "class", "value" => "[^\"`\$\\\\]", "description" => "[^\"`\$\\\\]"],
    33 => ["type" => "literal", "value" => "`", "description" => "\"`\""],
    34 => ["type" => "literal", "value" => "\$", "description" => "\"\$\""],
    35 => ["type" => "class", "value" => "[^`\$\\\\]", "description" => "[^`\$\\\\]"],
    36 => ["type" => "class", "value" => "[^'\"\\\\`\$ \\t\\v\\r\\f\\n&|;<>(){}]", "description" => "[^'\"\\\\`\$ \\t\\v\\r\\f\\n&|;<>(){}]"],
    37 => ["type" => "class", "value" => "[^'\"\\\\`\$ \\t\\v\\r\\f\\n&|;<>()]", "description" => "[^'\"\\\\`\$ \\t\\v\\r\\f\\n&|;<>()]"],
    38 => ["type" => "literal", "value" => "else", "description" => "\"else\""],
    39 => ["type" => "literal", "value" => "elif", "description" => "\"elif\""],
    40 => ["type" => "literal", "value" => "do", "description" => "\"do\""],
    41 => ["type" => "literal", "value" => "done", "description" => "\"done\""],
    42 => ["type" => "literal", "value" => "in", "description" => "\"in\""],
    43 => ["type" => "literal", "value" => "<&", "description" => "\"<&\""],
    44 => ["type" => "literal", "value" => "<>", "description" => "\"<>\""],
    45 => ["type" => "literal", "value" => "<", "description" => "\"<\""],
    46 => ["type" => "literal", "value" => ">&", "description" => "\">&\""],
    47 => ["type" => "literal", "value" => ">>", "description" => "\">>\""],
    48 => ["type" => "literal", "value" => ">|", "description" => "\">|\""],
    49 => ["type" => "literal", "value" => ">", "description" => "\">\""],
    50 => ["type" => "literal", "value" => "<<-", "description" => "\"<<-\""],
    51 => ["type" => "literal", "value" => "<<", "description" => "\"<<\""],
    52 => ["type" => "class", "value" => "[\$`\"\\\\\\n]", "description" => "[\$`\"\\\\\\n]"],
    53 => ["type" => "class", "value" => "[\$\\\\]", "description" => "[\$\\\\]"],
    54 => ["type" => "literal", "value" => "\\`", "description" => "\"\\\\`\""],
    55 => ["type" => "class", "value" => "[@*#?\\-\$!0]", "description" => "[@*#?\\-\$!0]"],
    56 => ["type" => "class", "value" => "[1-9]", "description" => "[1-9]"],
    57 => ["type" => "literal", "value" => "((", "description" => "\"((\""],
    58 => ["type" => "literal", "value" => "))", "description" => "\"))\""],
    59 => ["type" => "literal", "value" => ":", "description" => "\":\""],
    60 => ["type" => "class", "value" => "[\\-=?+]", "description" => "[\\-=?+]"],
    61 => ["type" => "literal", "value" => "%%", "description" => "\"%%\""],
    62 => ["type" => "literal", "value" => "##", "description" => "\"##\""],
    63 => ["type" => "class", "value" => "[%#\\-=?+]", "description" => "[%#\\-=?+]"],
  ];

  // actions
  private function a0($commands) {
  
  		return $this->node( 'program', $commands );
  	
  }
  private function a1() {
  
  		return $this->node( 'program', [] );
  	
  }
  private function a2($c) {
  
  			return $c;
  		
  }
  private function a3($item, $separator) {
  
  				if ( $separator && $separator[0] === '&' ) {
  					return $this->node( 'background', $item );
  				} else {
  					return $item;
  				}
  			
  }
  private function a4($nodes, $last) {
  
  			if ( $last ) {
                  $nodes[] = $last;
              }
              if ( count( $nodes ) > 1 ) {
                  return $this->node( 'list', $nodes );
              } else {
                  return $nodes[0];
              }
  		
  }
  private function a5($list) {
  
  	return $this->node( 'complete_command', $list );
  
  }
  private function a6($first, $pipeline) {
  
  			return $this->node( 'and_if', $pipeline );
  		
  }
  private function a7($first, $pipeline) {
  
  			return $this->node( 'or_if', $pipeline );
  		
  }
  private function a8($first, $rest) {
  
  	return $this->merge( $first, $rest );
  
  }
  private function a9($bang, $pipeline) {
  
  	if ( $bang !== null ) {
  		return $this->node( 'bang', $pipeline );
  	} else {
  		return $pipeline;
  	}
  
  }
  private function a10($first, $command) {
  
  			return $command;
  		
  }
  private function a11($first, $rest) {
  
  	if ( count( $rest ) ) {
  		return $this->node( 'pipeline', $this->merge( $first, $rest ) );
  	} else {
  		return $first;
  	}
  
  }
  private function a12($c, $r) {
  
  		if ( $r !== null ) {
  			return $this->merge( $c, $r );
  		} else {
  			return $c;
  		}
  	
  }
  private function a13($fname, $body) {
  
  	return $this->node( 'function_definition',
  		$this->merge( $this->node( 'function_name', $fname ), $body ) );
  
  }
  private function a14($prefix, $word, $suffix) {
  
  		$contents = [ $prefix, $word ];
  		if ( $suffix !== null ) {
  			$contents = array_merge( $contents, $suffix );
  		}
  		return $this->node( 'simple_command', $contents );
  	
  }
  private function a15($prefix) {
  
  		return $this->node( 'simple_command', [ $prefix ] );
  	
  }
  private function a16($name, $suffix) {
  
  		$contents = [ $name ];
  		if ( $suffix ) {
  			$contents = array_merge( $contents, $suffix );
  		}
  		return $this->node( 'simple_command', $contents );
  	
  }
  private function a17($c, $r) {
  
  	if ( $r !== null ) {
  		return $this->merge( $c, $r );
  	} else {
  		return $c;
  	}
  
  }
  private function a18($contents) {
  
  	return $this->node( 'cmd_prefix', $contents );
  
  }
  private function a19($parts) {
  
  	return $this->node( 'word', $parts );
  
  }
  private function a20($word) {
  
  	return $word;
  
  }
  private function a21($list) {
  
  	return $this->node( 'brace_group', $list );
  
  }
  private function a22($list) {
  
  	return $this->node( 'subshell', $list );
  
  }
  private function a23($name, $wordlist, $do_group) {
  
  			return $this->node( 'for', [ $name, $this->node( 'in', $wordlist ?: [] ), $do_group ] );
  		
  }
  private function a24($name, $do_group) {
  
  			return $this->node( 'for', [ $name, $do_group ] );
  		
  }
  private function a25($word, $list_esac) {
  
  	if ( is_array( $list_esac ) ) {
  		$list = $list_esac[0];
  	} else {
  		$list = [];
  	}
  	return $this->node( 'case', [ $word, $this->node( 'in', $list ) ] );
  
  }
  private function a26($condition, $consequent, $else_part) {
  
  	$contents = [
  		$this->node( 'condition', $condition ),
  		$this->node( 'consequent', $consequent )
  	];
  	if ( $else_part !== null ) {
  		$contents = $this->merge( $contents, $else_part );
  	}
  	return $this->node( 'if', $contents );
  
  }
  private function a27($list, $body) {
  
  	return $this->node( 'while', [
  		$this->node( 'condition', $list ),
  		$body
  	] );
  
  }
  private function a28($list, $body) {
  
  	return $this->node( 'until', [
  		$this->node( 'condition', $list ),
  		$body
  	] );
  
  }
  private function a29($number, $file_or_here) {
  
  		$contents = [];
  		if ( $number !== null ) {
  			$contents[] = $this->node( 'io_subject', $number );
  		}
  		$contents[] = $file_or_here;
  		return $this->node( 'io_redirect', $contents );
  	
  }
  private function a30($name, $word) {
  
  	return $this->node( 'assignment', [ $this->node( 'name', $name ), $word ] );
  
  }
  private function a31($term, $separator) {
  
  			if ( $separator && $separator[0] === '&' ) {
  				return $this->node( 'background', $term );
  			} else {
  				return $term;
  			}
  		
  }
  private function a32($terms, $last) {
  
  		if ( $last ) {
  			$terms[] = $last;
  		}
  		return $terms;
  	
  }
  private function a33($term) {
  
  		if ( $term === null ) {
  			// Phan is convinced $term may be null, not sure how
  			return [];
  		} else {
  			return $term;
  		}
  	
  }
  private function a34($name) {
  
  	return $name;
  
  }
  private function a35($list) {
  
  	return $this->node( 'do', $list );
  
  }
  private function a36($list, $item) {
  
  		$list[] = $item;
  		return $list;
  	
  }
  private function a37($item) {
  
  		return [ $item ];
  	
  }
  private function a38($condition, $consequent, $else_part) {
  
  		$contents = [
  			$this->node( 'elif_condition', $condition ),
  			$this->node( 'elif_consequent', $consequent )
  		];
  		if ( $else_part !== null ) {
  			$contents = $this->merge( $contents, $else_part );
  		}
  		return $contents;
  	
  }
  private function a39($alternative) {
  
  		return [ $this->node( 'else', $alternative ) ];
  	
  }
  private function a40($filename) {
  
  		return $this->node( 'duplicate_input', $filename );
  	
  }
  private function a41($filename) {
  
  		return $this->node( 'read_and_write', $filename );
  	
  }
  private function a42($filename) {
  
  		return $this->node( 'input', $filename );
  	
  }
  private function a43($filename) {
  
  		return $this->node( 'duplicate_output', $filename );
  	
  }
  private function a44($filename) {
  
  		return $this->node( 'append_output', $filename );
  	
  }
  private function a45($filename) {
  
  		return $this->node( 'clobber', $filename );
  	
  }
  private function a46($filename) {
  
  		return $this->node( 'output', $filename );
  	
  }
  private function a47() {
   return 'io_here_strip'; 
  }
  private function a48() {
   return 'io_here'; 
  }
  private function a49($op, $end) {
  
  	// TODO: this is quite complicated to implement, especially given the way
  	// the parser is structured.
  	throw new UnimplementedError( 'heredoc is not implemented' );
  	// For phan
  	return $this->node( 'io_here', '' );
  
  }
  private function a50($contents) {
  
  	return $this->node( 'single_quote', $contents );
  
  }
  private function a51($contents) {
  
  	return $this->node( 'double_quote', $contents );
  
  }
  private function a52($contents) {
  
  	return $this->node( 'bare_escape', $contents );
  
  }
  private function a53($parts) {
  
  	return $this->node( 'backquote', $parts );
  
  }
  private function a54($contents) {
  
  	return $contents;
  
  }
  private function a55($plain) {
  
  	return $this->node( 'unquoted_literal', $plain );
  
  }
  private function a56($pattern, $list) {
  
  		return $this->node( 'case_item', [
  			$pattern,
  			$this->node( 'case_consequent', $list )
  		] );
  	
  }
  private function a57($pattern) {
  
  		return $this->node( 'case_item', $pattern );
  	
  }
  private function a58($pattern) {
  
  		return $this->node( 'case_item', [ $pattern ] );
  	
  }
  private function a59($contents) {
  
  	return $this->node( 'dquoted_escape', $contents );
  
  }
  private function a60($contents) {
  
  	return $this->node( 'backquoted_escape', $contents );
  
  }
  private function a61($parts) {
  
  	return $this->node( 'double_backquote', $parts );
  
  }
  private function a62($contents) {
  
  	return $this->node( 'special_parameter', $contents );
  
  }
  private function a63($contents) {
  
  	return $this->node( 'positional_parameter', $contents );
  
  }
  private function a64($words) {
  
  	return $this->node( 'arithmetic_expansion', $words );
  
  }
  private function a65($command) {
  
  	return $this->node( 'command_expansion', $command );
  
  }
  private function a66($name) {
  
  	return $this->node( 'named_parameter', $name );
  
  }
  private function a67($first, $rest) {
  
  	$patterns = [ $first ];
  	foreach ( $rest as $pattern ) {
  		$patterns[] = $pattern[1];
  	}
  	return $this->node( 'case_pattern', $patterns );
  
  }
  private function a68($parameter, $operator, $word) {
  
  	$names = [
  		':-' => 'use_default',
  		'-' => 'use_default_unset',
  		':=' => 'assign_default',
  		'=' => 'assign_default_unset',
  		':?' => 'indicate_error',
  		'?' => 'indicate_error_unset',
  		':+' => 'use_alternative',
  		'+' => 'use_alternative_unset',
  		'%' => 'remove_smallest_suffix',
  		'%%' => 'remove_largest_suffix',
  		'#' => 'remove_smallest_prefix',
  		'##' => 'remove_largest_prefix'
  	];
  	if ( !isset( $names[$operator] ) ) {
  		throw new InternalError( "Unable to find operator \"$operator\"" );
  	}
  	return $this->node( $names[$operator], [ $parameter, $word ?? '' ] );
  
  }
  private function a69($parameter) {
  
  	return $this->node( 'string_length', $parameter );
  
  }
  private function a70($parameter) {
  
  	return $this->node( 'braced_parameter_expansion', $parameter );
  
  }
  private function a71($parameter) {
  
  	return $this->node( 'positional_parameter', $parameter );
  
  }

  // generated
  private function parseprogram($silence) {
    $key = json_encode([208, $this->currPos]);
    $cached = $this->cache[$key] ?? null;
      if ($cached) {
        $this->currPos = $cached['nextPos'];
  
        return $cached['result'];
      }
  
    // start choice_1
    $p2 = $this->currPos;
    // start seq_1
    $p3 = $this->currPos;
    $r4 = $this->discardlinebreak($silence);
    if ($r4===self::$FAILED) {
      $r1 = self::$FAILED;
      goto seq_1;
    }
    $r5 = $this->parsecomplete_commands($silence);
    // commands <- $r5
    if ($r5===self::$FAILED) {
      $this->currPos = $p3;
      $r1 = self::$FAILED;
      goto seq_1;
    }
    $r6 = $this->discardlinebreak($silence);
    if ($r6===self::$FAILED) {
      $this->currPos = $p3;
      $r1 = self::$FAILED;
      goto seq_1;
    }
    $r1 = true;
    seq_1:
    if ($r1!==self::$FAILED) {
      $this->savedPos = $p2;
      $r1 = $this->a0($r5);
      goto choice_1;
    }
    // free $p3
    $p3 = $this->currPos;
    $r1 = $this->discardlinebreak($silence);
    if ($r1!==self::$FAILED) {
      $this->savedPos = $p3;
      $r1 = $this->a1();
    }
    choice_1:
    $cached = ['nextPos' => $this->currPos, 'result' => $r1];
  
    $this->cache[$key] = $cached;
    return $r1;
  }
  private function discardlinebreak($silence) {
    $key = json_encode([285, $this->currPos]);
    $cached = $this->cache[$key] ?? null;
      if ($cached) {
        $this->currPos = $cached['nextPos'];
  
        return $cached['result'];
      }
  
    $r1 = $this->discardnewline_list($silence);
    if ($r1===self::$FAILED) {
      $r1 = null;
    }
    $cached = ['nextPos' => $this->currPos, 'result' => $r1];
  
    $this->cache[$key] = $cached;
    return $r1;
  }
  private function parsecomplete_commands($silence) {
    $key = json_encode([210, $this->currPos]);
    $cached = $this->cache[$key] ?? null;
      if ($cached) {
        $this->currPos = $cached['nextPos'];
  
        return $cached['result'];
      }
  
    $r1 = [];
    for (;;) {
      // start choice_1
      $p3 = $this->currPos;
      // start seq_1
      $p4 = $this->currPos;
      $r5 = $this->parsecomplete_command($silence, 0x0);
      // c <- $r5
      if ($r5===self::$FAILED) {
        $r2 = self::$FAILED;
        goto seq_1;
      }
      $r6 = $this->discardnewline_list($silence);
      if ($r6===self::$FAILED) {
        $this->currPos = $p4;
        $r2 = self::$FAILED;
        goto seq_1;
      }
      $r2 = true;
      seq_1:
      if ($r2!==self::$FAILED) {
        $this->savedPos = $p3;
        $r2 = $this->a2($r5);
        goto choice_1;
      }
      // free $p4
      $r2 = $this->parsecomplete_command($silence, 0x0);
      choice_1:
      if ($r2!==self::$FAILED) {
        $r1[] = $r2;
      } else {
        break;
      }
    }
    // free $r2
    $cached = ['nextPos' => $this->currPos, 'result' => $r1];
  
    $this->cache[$key] = $cached;
    return $r1;
  }
  private function discardnewline_list($silence) {
    $key = json_encode([283, $this->currPos]);
    $cached = $this->cache[$key] ?? null;
      if ($cached) {
        $this->currPos = $cached['nextPos'];
  
        return $cached['result'];
      }
  
    $r1 = self::$FAILED;
    for (;;) {
      $r2 = $this->discardNEWLINE($silence);
      if ($r2!==self::$FAILED) {
        $r1 = true;
      } else {
        break;
      }
    }
    // free $r2
    $cached = ['nextPos' => $this->currPos, 'result' => $r1];
  
    $this->cache[$key] = $cached;
    return $r1;
  }
  private function parsecomplete_command($silence, $boolParams) {
    $key = json_encode([212, $this->currPos, $boolParams & 0x1]);
    $cached = $this->cache[$key] ?? null;
      if ($cached) {
        $this->currPos = $cached['nextPos'];
  
        return $cached['result'];
      }
  
    $p2 = $this->currPos;
    // start seq_1
    $p3 = $this->currPos;
    $r4 = $this->discardOWS($silence);
    if ($r4===self::$FAILED) {
      $r1 = self::$FAILED;
      goto seq_1;
    }
    // start choice_1
    $p6 = $this->currPos;
    // start seq_2
    $p7 = $this->currPos;
    $r8 = [];
    for (;;) {
      $p10 = $this->currPos;
      // start seq_3
      $p11 = $this->currPos;
      $r12 = $this->parseand_or($silence, $boolParams);
      // item <- $r12
      if ($r12===self::$FAILED) {
        $r9 = self::$FAILED;
        goto seq_3;
      }
      $p14 = $this->currPos;
      $r13 = $this->discardseparator_op($silence);
      // separator <- $r13
      if ($r13!==self::$FAILED) {
        $r13 = substr($this->input, $p14, $this->currPos - $p14);
      } else {
        $r13 = self::$FAILED;
        $this->currPos = $p11;
        $r9 = self::$FAILED;
        goto seq_3;
      }
      // free $p14
      $r9 = true;
      seq_3:
      if ($r9!==self::$FAILED) {
        $this->savedPos = $p10;
        $r9 = $this->a3($r12, $r13);
        $r8[] = $r9;
      } else {
        break;
      }
      // free $p11
    }
    if (count($r8) === 0) {
      $r8 = self::$FAILED;
    }
    // nodes <- $r8
    if ($r8===self::$FAILED) {
      $r5 = self::$FAILED;
      goto seq_2;
    }
    // free $r9
    $r9 = $this->parseand_or($silence, $boolParams);
    if ($r9===self::$FAILED) {
      $r9 = null;
    }
    // last <- $r9
    $r5 = true;
    seq_2:
    if ($r5!==self::$FAILED) {
      $this->savedPos = $p6;
      $r5 = $this->a4($r8, $r9);
      goto choice_1;
    }
    // free $p7
    $r5 = $this->parseand_or($silence, $boolParams);
    choice_1:
    // list <- $r5
    if ($r5===self::$FAILED) {
      $this->currPos = $p3;
      $r1 = self::$FAILED;
      goto seq_1;
    }
    $r1 = true;
    seq_1:
    if ($r1!==self::$FAILED) {
      $this->savedPos = $p2;
      $r1 = $this->a5($r5);
    }
    // free $p3
    $cached = ['nextPos' => $this->currPos, 'result' => $r1];
  
    $this->cache[$key] = $cached;
    return $r1;
  }
  private function discardNEWLINE($silence) {
    $key = json_encode([413, $this->currPos]);
    $cached = $this->cache[$key] ?? null;
      if ($cached) {
        $this->currPos = $cached['nextPos'];
  
        return $cached['result'];
      }
  
    // start seq_1
    $p1 = $this->currPos;
    if (($this->input[$this->currPos] ?? null) === "\x0a") {
      $this->currPos++;
      $r3 = "\x0a";
    } else {
      if (!$silence) {$this->fail(1);}
      $r3 = self::$FAILED;
      $r2 = self::$FAILED;
      goto seq_1;
    }
    $r4 = $this->discardOWS($silence);
    if ($r4===self::$FAILED) {
      $this->currPos = $p1;
      $r2 = self::$FAILED;
      goto seq_1;
    }
    $r2 = true;
    seq_1:
    // free $r2,$p1
    $cached = ['nextPos' => $this->currPos, 'result' => $r2];
  
    $this->cache[$key] = $cached;
    return $r2;
  }
  private function discardOWS($silence) {
    $key = json_encode([361, $this->currPos]);
    $cached = $this->cache[$key] ?? null;
      if ($cached) {
        $this->currPos = $cached['nextPos'];
  
        return $cached['result'];
      }
  
    // start seq_1
    $p1 = $this->currPos;
    for (;;) {
      if (strspn($this->input, " \x09\x0b\x0d\x0c", $this->currPos, 1) !== 0) {
        $r4 = $this->input[$this->currPos++];
      } else {
        $r4 = self::$FAILED;
        if (!$silence) {$this->fail(2);}
        break;
      }
    }
    // free $r4
    $r3 = true;
    if ($r3===self::$FAILED) {
      $r2 = self::$FAILED;
      goto seq_1;
    }
    // free $r3
    // start seq_2
    $p5 = $this->currPos;
    if (($this->input[$this->currPos] ?? null) === "#") {
      $this->currPos++;
      $r4 = "#";
    } else {
      if (!$silence) {$this->fail(3);}
      $r4 = self::$FAILED;
      $r3 = self::$FAILED;
      goto seq_2;
    }
    for (;;) {
      $r7 = self::charAt($this->input, $this->currPos);
      if ($r7 !== '' && !($r7 === "\x0a")) {
        $this->currPos += strlen($r7);
      } else {
        $r7 = self::$FAILED;
        if (!$silence) {$this->fail(4);}
        break;
      }
    }
    // free $r7
    $r6 = true;
    if ($r6===self::$FAILED) {
      $this->currPos = $p5;
      $r3 = self::$FAILED;
      goto seq_2;
    }
    // free $r6
    $r3 = true;
    seq_2:
    if ($r3===self::$FAILED) {
      $r3 = null;
    }
    // free $p5
    $r2 = true;
    seq_1:
    // free $r2,$p1
    $cached = ['nextPos' => $this->currPos, 'result' => $r2];
  
    $this->cache[$key] = $cached;
    return $r2;
  }
  private function parseand_or($silence, $boolParams) {
    $key = json_encode([214, $this->currPos, $boolParams & 0x1]);
    $cached = $this->cache[$key] ?? null;
      if ($cached) {
        $this->currPos = $cached['nextPos'];
  
        return $cached['result'];
      }
  
    $p2 = $this->currPos;
    // start seq_1
    $p3 = $this->currPos;
    $r4 = $this->parsepipeline($silence, $boolParams);
    // first <- $r4
    if ($r4===self::$FAILED) {
      $r1 = self::$FAILED;
      goto seq_1;
    }
    $r5 = [];
    for (;;) {
      // start choice_1
      $p7 = $this->currPos;
      // start seq_2
      $p8 = $this->currPos;
      $r9 = $this->discardAND_IF($silence);
      if ($r9===self::$FAILED) {
        $r6 = self::$FAILED;
        goto seq_2;
      }
      $r10 = $this->discardlinebreak($silence);
      if ($r10===self::$FAILED) {
        $this->currPos = $p8;
        $r6 = self::$FAILED;
        goto seq_2;
      }
      $r11 = $this->parsepipeline($silence, $boolParams);
      // pipeline <- $r11
      if ($r11===self::$FAILED) {
        $this->currPos = $p8;
        $r6 = self::$FAILED;
        goto seq_2;
      }
      $r6 = true;
      seq_2:
      if ($r6!==self::$FAILED) {
        $this->savedPos = $p7;
        $r6 = $this->a6($r4, $r11);
        goto choice_1;
      }
      // free $p8
      $p8 = $this->currPos;
      // start seq_3
      $p12 = $this->currPos;
      $r13 = $this->discardOR_IF($silence);
      if ($r13===self::$FAILED) {
        $r6 = self::$FAILED;
        goto seq_3;
      }
      $r14 = $this->discardlinebreak($silence);
      if ($r14===self::$FAILED) {
        $this->currPos = $p12;
        $r6 = self::$FAILED;
        goto seq_3;
      }
      $r15 = $this->parsepipeline($silence, $boolParams);
      // pipeline <- $r15
      if ($r15===self::$FAILED) {
        $this->currPos = $p12;
        $r6 = self::$FAILED;
        goto seq_3;
      }
      $r6 = true;
      seq_3:
      if ($r6!==self::$FAILED) {
        $this->savedPos = $p8;
        $r6 = $this->a7($r4, $r15);
        goto choice_1;
      }
      // free $p12
      $r6 = $this->parsepipeline($silence, $boolParams);
      choice_1:
      if ($r6!==self::$FAILED) {
        $r5[] = $r6;
      } else {
        break;
      }
    }
    // rest <- $r5
    // free $r6
    $r1 = true;
    seq_1:
    if ($r1!==self::$FAILED) {
      $this->savedPos = $p2;
      $r1 = $this->a8($r4, $r5);
    }
    // free $p3
    $cached = ['nextPos' => $this->currPos, 'result' => $r1];
  
    $this->cache[$key] = $cached;
    return $r1;
  }
  private function discardseparator_op($silence) {
    $key = json_encode([287, $this->currPos]);
    $cached = $this->cache[$key] ?? null;
      if ($cached) {
        $this->currPos = $cached['nextPos'];
  
        return $cached['result'];
      }
  
    // start choice_1
    $r1 = $this->discardAND($silence);
    if ($r1!==self::$FAILED) {
      goto choice_1;
    }
    $r1 = $this->discardSEMI($silence);
    choice_1:
    $cached = ['nextPos' => $this->currPos, 'result' => $r1];
  
    $this->cache[$key] = $cached;
    return $r1;
  }
  private function parsepipeline($silence, $boolParams) {
    $key = json_encode([216, $this->currPos, $boolParams & 0x1]);
    $cached = $this->cache[$key] ?? null;
      if ($cached) {
        $this->currPos = $cached['nextPos'];
  
        return $cached['result'];
      }
  
    $p2 = $this->currPos;
    // start seq_1
    $p3 = $this->currPos;
    $r4 = $this->parseBang($silence);
    if ($r4===self::$FAILED) {
      $r4 = null;
    }
    // bang <- $r4
    $r5 = $this->parsepipe_sequence($silence, $boolParams);
    // pipeline <- $r5
    if ($r5===self::$FAILED) {
      $this->currPos = $p3;
      $r1 = self::$FAILED;
      goto seq_1;
    }
    $r1 = true;
    seq_1:
    if ($r1!==self::$FAILED) {
      $this->savedPos = $p2;
      $r1 = $this->a9($r4, $r5);
    }
    // free $p3
    $cached = ['nextPos' => $this->currPos, 'result' => $r1];
  
    $this->cache[$key] = $cached;
    return $r1;
  }
  private function discardAND_IF($silence) {
    $key = json_encode([293, $this->currPos]);
    $cached = $this->cache[$key] ?? null;
      if ($cached) {
        $this->currPos = $cached['nextPos'];
  
        return $cached['result'];
      }
  
    // start seq_1
    $p1 = $this->currPos;
    if ($this->currPos >= $this->inputLength ? false : substr_compare($this->input, "&&", $this->currPos, 2, false) === 0) {
      $r3 = "&&";
      $this->currPos += 2;
    } else {
      if (!$silence) {$this->fail(5);}
      $r3 = self::$FAILED;
      $r2 = self::$FAILED;
      goto seq_1;
    }
    $r4 = $this->discardOWS($silence);
    if ($r4===self::$FAILED) {
      $this->currPos = $p1;
      $r2 = self::$FAILED;
      goto seq_1;
    }
    $r2 = true;
    seq_1:
    // free $r2,$p1
    $cached = ['nextPos' => $this->currPos, 'result' => $r2];
  
    $this->cache[$key] = $cached;
    return $r2;
  }
  private function discardOR_IF($silence) {
    $key = json_encode([295, $this->currPos]);
    $cached = $this->cache[$key] ?? null;
      if ($cached) {
        $this->currPos = $cached['nextPos'];
  
        return $cached['result'];
      }
  
    // start seq_1
    $p1 = $this->currPos;
    if ($this->currPos >= $this->inputLength ? false : substr_compare($this->input, "||", $this->currPos, 2, false) === 0) {
      $r3 = "||";
      $this->currPos += 2;
    } else {
      if (!$silence) {$this->fail(6);}
      $r3 = self::$FAILED;
      $r2 = self::$FAILED;
      goto seq_1;
    }
    $r4 = $this->discardOWS($silence);
    if ($r4===self::$FAILED) {
      $this->currPos = $p1;
      $r2 = self::$FAILED;
      goto seq_1;
    }
    $r2 = true;
    seq_1:
    // free $r2,$p1
    $cached = ['nextPos' => $this->currPos, 'result' => $r2];
  
    $this->cache[$key] = $cached;
    return $r2;
  }
  private function discardAND($silence) {
    $key = json_encode([347, $this->currPos]);
    $cached = $this->cache[$key] ?? null;
      if ($cached) {
        $this->currPos = $cached['nextPos'];
  
        return $cached['result'];
      }
  
    // start seq_1
    $p1 = $this->currPos;
    $p3 = $this->currPos;
    $r4 = $this->discardAND_IF(true);
    if ($r4 === self::$FAILED) {
      $r4 = false;
    } else {
      $r4 = self::$FAILED;
      $this->currPos = $p3;
      $r2 = self::$FAILED;
      goto seq_1;
    }
    // free $p3
    if (($this->input[$this->currPos] ?? null) === "&") {
      $this->currPos++;
      $r5 = "&";
    } else {
      if (!$silence) {$this->fail(7);}
      $r5 = self::$FAILED;
      $this->currPos = $p1;
      $r2 = self::$FAILED;
      goto seq_1;
    }
    $r6 = $this->discardOWS($silence);
    if ($r6===self::$FAILED) {
      $this->currPos = $p1;
      $r2 = self::$FAILED;
      goto seq_1;
    }
    $r2 = true;
    seq_1:
    // free $r2,$p1
    $cached = ['nextPos' => $this->currPos, 'result' => $r2];
  
    $this->cache[$key] = $cached;
    return $r2;
  }
  private function discardSEMI($silence) {
    $key = json_encode([349, $this->currPos]);
    $cached = $this->cache[$key] ?? null;
      if ($cached) {
        $this->currPos = $cached['nextPos'];
  
        return $cached['result'];
      }
  
    // start seq_1
    $p1 = $this->currPos;
    $p3 = $this->currPos;
    $r4 = $this->discardDSEMI(true);
    if ($r4 === self::$FAILED) {
      $r4 = false;
    } else {
      $r4 = self::$FAILED;
      $this->currPos = $p3;
      $r2 = self::$FAILED;
      goto seq_1;
    }
    // free $p3
    if (($this->input[$this->currPos] ?? null) === ";") {
      $this->currPos++;
      $r5 = ";";
    } else {
      if (!$silence) {$this->fail(8);}
      $r5 = self::$FAILED;
      $this->currPos = $p1;
      $r2 = self::$FAILED;
      goto seq_1;
    }
    $r6 = $this->discardOWS($silence);
    if ($r6===self::$FAILED) {
      $this->currPos = $p1;
      $r2 = self::$FAILED;
      goto seq_1;
    }
    $r2 = true;
    seq_1:
    // free $r2,$p1
    $cached = ['nextPos' => $this->currPos, 'result' => $r2];
  
    $this->cache[$key] = $cached;
    return $r2;
  }
  private function parseBang($silence) {
    $key = json_encode([340, $this->currPos]);
    $cached = $this->cache[$key] ?? null;
      if ($cached) {
        $this->currPos = $cached['nextPos'];
  
        return $cached['result'];
      }
  
    // start seq_1
    $p1 = $this->currPos;
    if (($this->input[$this->currPos] ?? null) === "!") {
      $this->currPos++;
      $r3 = "!";
    } else {
      if (!$silence) {$this->fail(9);}
      $r3 = self::$FAILED;
      $r2 = self::$FAILED;
      goto seq_1;
    }
    $r4 = $this->parseDELIM($silence);
    if ($r4===self::$FAILED) {
      $this->currPos = $p1;
      $r2 = self::$FAILED;
      goto seq_1;
    }
    $r2 = [$r3,$r4];
    seq_1:
    // free $r2,$p1
    $cached = ['nextPos' => $this->currPos, 'result' => $r2];
  
    $this->cache[$key] = $cached;
    return $r2;
  }
  private function parsepipe_sequence($silence, $boolParams) {
    $key = json_encode([218, $this->currPos, $boolParams & 0x1]);
    $cached = $this->cache[$key] ?? null;
      if ($cached) {
        $this->currPos = $cached['nextPos'];
  
        return $cached['result'];
      }
  
    $p2 = $this->currPos;
    // start seq_1
    $p3 = $this->currPos;
    $r4 = $this->parsecommand($silence, $boolParams);
    // first <- $r4
    if ($r4===self::$FAILED) {
      $r1 = self::$FAILED;
      goto seq_1;
    }
    $r5 = [];
    for (;;) {
      $p7 = $this->currPos;
      // start seq_2
      $p8 = $this->currPos;
      $r9 = $this->discardPIPE($silence);
      if ($r9===self::$FAILED) {
        $r6 = self::$FAILED;
        goto seq_2;
      }
      $r10 = $this->discardlinebreak($silence);
      if ($r10===self::$FAILED) {
        $this->currPos = $p8;
        $r6 = self::$FAILED;
        goto seq_2;
      }
      $r11 = $this->parsecommand($silence, $boolParams);
      // command <- $r11
      if ($r11===self::$FAILED) {
        $this->currPos = $p8;
        $r6 = self::$FAILED;
        goto seq_2;
      }
      $r6 = true;
      seq_2:
      if ($r6!==self::$FAILED) {
        $this->savedPos = $p7;
        $r6 = $this->a10($r4, $r11);
        $r5[] = $r6;
      } else {
        break;
      }
      // free $p8
    }
    // rest <- $r5
    // free $r6
    $r1 = true;
    seq_1:
    if ($r1!==self::$FAILED) {
      $this->savedPos = $p2;
      $r1 = $this->a11($r4, $r5);
    }
    // free $p3
    $cached = ['nextPos' => $this->currPos, 'result' => $r1];
  
    $this->cache[$key] = $cached;
    return $r1;
  }
  private function discardDSEMI($silence) {
    $key = json_encode([297, $this->currPos]);
    $cached = $this->cache[$key] ?? null;
      if ($cached) {
        $this->currPos = $cached['nextPos'];
  
        return $cached['result'];
      }
  
    // start seq_1
    $p1 = $this->currPos;
    if ($this->currPos >= $this->inputLength ? false : substr_compare($this->input, ";;", $this->currPos, 2, false) === 0) {
      $r3 = ";;";
      $this->currPos += 2;
    } else {
      if (!$silence) {$this->fail(10);}
      $r3 = self::$FAILED;
      $r2 = self::$FAILED;
      goto seq_1;
    }
    $r4 = $this->discardOWS($silence);
    if ($r4===self::$FAILED) {
      $this->currPos = $p1;
      $r2 = self::$FAILED;
      goto seq_1;
    }
    $r2 = true;
    seq_1:
    // free $r2,$p1
    $cached = ['nextPos' => $this->currPos, 'result' => $r2];
  
    $this->cache[$key] = $cached;
    return $r2;
  }
  private function parseDELIM($silence) {
    $key = json_encode([362, $this->currPos]);
    $cached = $this->cache[$key] ?? null;
      if ($cached) {
        $this->currPos = $cached['nextPos'];
  
        return $cached['result'];
      }
  
    // start choice_1
    $r1 = [];
    for (;;) {
      if (strspn($this->input, " \x09\x0b\x0d\x0c", $this->currPos, 1) !== 0) {
        $r2 = $this->input[$this->currPos++];
        $r1[] = $r2;
      } else {
        $r2 = self::$FAILED;
        if (!$silence) {$this->fail(2);}
        break;
      }
    }
    if (count($r1) === 0) {
      $r1 = self::$FAILED;
    }
    if ($r1!==self::$FAILED) {
      goto choice_1;
    }
    // free $r2
    $p3 = $this->currPos;
    if ($this->currPos < $this->inputLength) {
      $r1 = self::consumeChar($this->input, $this->currPos);;
    } else {
      $r1 = self::$FAILED;
    }
    if ($r1 === self::$FAILED) {
      $r1 = false;
      goto choice_1;
    } else {
      $r1 = self::$FAILED;
      $this->currPos = $p3;
    }
    // free $p3
    $p3 = $this->currPos;
    if (($this->input[$this->currPos] ?? null) === "\x0a") {
      $this->currPos++;
      $r1 = "\x0a";
      $r1 = false;
      $this->currPos = $p3;
    } else {
      $r1 = self::$FAILED;
    }
    // free $p3
    choice_1:
    $cached = ['nextPos' => $this->currPos, 'result' => $r1];
  
    $this->cache[$key] = $cached;
    return $r1;
  }
  private function parsecommand($silence, $boolParams) {
    $key = json_encode([220, $this->currPos, $boolParams & 0x1]);
    $cached = $this->cache[$key] ?? null;
      if ($cached) {
        $this->currPos = $cached['nextPos'];
  
        return $cached['result'];
      }
  
    // start choice_1
    $r1 = $this->parsefunction_definition($silence, $boolParams);
    if ($r1!==self::$FAILED) {
      goto choice_1;
    }
    $r1 = $this->parsesimple_command($silence, $boolParams);
    if ($r1!==self::$FAILED) {
      goto choice_1;
    }
    $p2 = $this->currPos;
    // start seq_1
    $p3 = $this->currPos;
    $r4 = $this->parsecompound_command($silence, $boolParams);
    // c <- $r4
    if ($r4===self::$FAILED) {
      $r1 = self::$FAILED;
      goto seq_1;
    }
    $r5 = $this->parseredirect_list($silence, $boolParams);
    if ($r5===self::$FAILED) {
      $r5 = null;
    }
    // r <- $r5
    $r1 = true;
    seq_1:
    if ($r1!==self::$FAILED) {
      $this->savedPos = $p2;
      $r1 = $this->a12($r4, $r5);
    }
    // free $p3
    choice_1:
    $cached = ['nextPos' => $this->currPos, 'result' => $r1];
  
    $this->cache[$key] = $cached;
    return $r1;
  }
  private function discardPIPE($silence) {
    $key = json_encode([355, $this->currPos]);
    $cached = $this->cache[$key] ?? null;
      if ($cached) {
        $this->currPos = $cached['nextPos'];
  
        return $cached['result'];
      }
  
    // start seq_1
    $p1 = $this->currPos;
    $p3 = $this->currPos;
    $r4 = $this->discardOR_IF(true);
    if ($r4 === self::$FAILED) {
      $r4 = false;
    } else {
      $r4 = self::$FAILED;
      $this->currPos = $p3;
      $r2 = self::$FAILED;
      goto seq_1;
    }
    // free $p3
    if (($this->input[$this->currPos] ?? null) === "|") {
      $this->currPos++;
      $r5 = "|";
    } else {
      if (!$silence) {$this->fail(11);}
      $r5 = self::$FAILED;
      $this->currPos = $p1;
      $r2 = self::$FAILED;
      goto seq_1;
    }
    $r6 = $this->discardOWS($silence);
    if ($r6===self::$FAILED) {
      $this->currPos = $p1;
      $r2 = self::$FAILED;
      goto seq_1;
    }
    $r2 = true;
    seq_1:
    // free $r2,$p1
    $cached = ['nextPos' => $this->currPos, 'result' => $r2];
  
    $this->cache[$key] = $cached;
    return $r2;
  }
  private function parsefunction_definition($silence, $boolParams) {
    $key = json_encode([256, $this->currPos, $boolParams & 0x1]);
    $cached = $this->cache[$key] ?? null;
      if ($cached) {
        $this->currPos = $cached['nextPos'];
  
        return $cached['result'];
      }
  
    $p2 = $this->currPos;
    // start seq_1
    $p3 = $this->currPos;
    $r4 = $this->parseNAME($silence);
    // fname <- $r4
    if ($r4===self::$FAILED) {
      $r1 = self::$FAILED;
      goto seq_1;
    }
    $r5 = $this->discardLPAREN($silence);
    if ($r5===self::$FAILED) {
      $this->currPos = $p3;
      $r1 = self::$FAILED;
      goto seq_1;
    }
    $r6 = $this->discardRPAREN($silence);
    if ($r6===self::$FAILED) {
      $this->currPos = $p3;
      $r1 = self::$FAILED;
      goto seq_1;
    }
    $r7 = $this->discardlinebreak($silence);
    if ($r7===self::$FAILED) {
      $this->currPos = $p3;
      $r1 = self::$FAILED;
      goto seq_1;
    }
    $r8 = $this->parsefunction_body($silence, $boolParams);
    // body <- $r8
    if ($r8===self::$FAILED) {
      $this->currPos = $p3;
      $r1 = self::$FAILED;
      goto seq_1;
    }
    $r1 = true;
    seq_1:
    if ($r1!==self::$FAILED) {
      $this->savedPos = $p2;
      $r1 = $this->a13($r4, $r8);
    }
    // free $p3
    $cached = ['nextPos' => $this->currPos, 'result' => $r1];
  
    $this->cache[$key] = $cached;
    return $r1;
  }
  private function parsesimple_command($silence, $boolParams) {
    $key = json_encode([264, $this->currPos, $boolParams & 0x1]);
    $cached = $this->cache[$key] ?? null;
      if ($cached) {
        $this->currPos = $cached['nextPos'];
  
        return $cached['result'];
      }
  
    // start choice_1
    $p2 = $this->currPos;
    // start seq_1
    $p3 = $this->currPos;
    $r4 = $this->parsecmd_prefix($silence, $boolParams);
    // prefix <- $r4
    if ($r4===self::$FAILED) {
      $r1 = self::$FAILED;
      goto seq_1;
    }
    $r5 = $this->parseWORD($silence, $boolParams);
    // word <- $r5
    if ($r5===self::$FAILED) {
      $this->currPos = $p3;
      $r1 = self::$FAILED;
      goto seq_1;
    }
    $r6 = $this->parsecmd_suffix($silence, $boolParams);
    if ($r6===self::$FAILED) {
      $r6 = null;
    }
    // suffix <- $r6
    $r1 = true;
    seq_1:
    if ($r1!==self::$FAILED) {
      $this->savedPos = $p2;
      $r1 = $this->a14($r4, $r5, $r6);
      goto choice_1;
    }
    // free $p3
    $p3 = $this->currPos;
    $r7 = $this->parsecmd_prefix($silence, $boolParams);
    // prefix <- $r7
    $r1 = $r7;
    if ($r1!==self::$FAILED) {
      $this->savedPos = $p3;
      $r1 = $this->a15($r7);
      goto choice_1;
    }
    $p8 = $this->currPos;
    // start seq_2
    $p9 = $this->currPos;
    $r10 = $this->parsecmd_name($silence, $boolParams);
    // name <- $r10
    if ($r10===self::$FAILED) {
      $r1 = self::$FAILED;
      goto seq_2;
    }
    $r11 = $this->parsecmd_suffix($silence, $boolParams);
    if ($r11===self::$FAILED) {
      $r11 = null;
    }
    // suffix <- $r11
    $r1 = true;
    seq_2:
    if ($r1!==self::$FAILED) {
      $this->savedPos = $p8;
      $r1 = $this->a16($r10, $r11);
    }
    // free $p9
    choice_1:
    $cached = ['nextPos' => $this->currPos, 'result' => $r1];
  
    $this->cache[$key] = $cached;
    return $r1;
  }
  private function parsecompound_command($silence, $boolParams) {
    $key = json_encode([222, $this->currPos, $boolParams & 0x1]);
    $cached = $this->cache[$key] ?? null;
      if ($cached) {
        $this->currPos = $cached['nextPos'];
  
        return $cached['result'];
      }
  
    // start choice_1
    $r1 = $this->parsebrace_group($silence);
    if ($r1!==self::$FAILED) {
      goto choice_1;
    }
    $r1 = $this->parsesubshell($silence);
    if ($r1!==self::$FAILED) {
      goto choice_1;
    }
    $r1 = $this->parsefor_clause($silence, $boolParams);
    if ($r1!==self::$FAILED) {
      goto choice_1;
    }
    $r1 = $this->parsecase_clause($silence, $boolParams);
    if ($r1!==self::$FAILED) {
      goto choice_1;
    }
    $r1 = $this->parseif_clause($silence);
    if ($r1!==self::$FAILED) {
      goto choice_1;
    }
    $r1 = $this->parsewhile_clause($silence);
    if ($r1!==self::$FAILED) {
      goto choice_1;
    }
    $r1 = $this->parseuntil_clause($silence);
    choice_1:
    $cached = ['nextPos' => $this->currPos, 'result' => $r1];
  
    $this->cache[$key] = $cached;
    return $r1;
  }
  private function parseredirect_list($silence, $boolParams) {
    $key = json_encode([272, $this->currPos, $boolParams & 0x1]);
    $cached = $this->cache[$key] ?? null;
      if ($cached) {
        $this->currPos = $cached['nextPos'];
  
        return $cached['result'];
      }
  
    $r1 = [];
    for (;;) {
      $r2 = $this->parseio_redirect($silence, $boolParams);
      if ($r2!==self::$FAILED) {
        $r1[] = $r2;
      } else {
        break;
      }
    }
    if (count($r1) === 0) {
      $r1 = self::$FAILED;
    }
    // free $r2
    $cached = ['nextPos' => $this->currPos, 'result' => $r1];
  
    $this->cache[$key] = $cached;
    return $r1;
  }
  private function parseNAME($silence) {
    $key = json_encode([410, $this->currPos]);
    $cached = $this->cache[$key] ?? null;
      if ($cached) {
        $this->currPos = $cached['nextPos'];
  
        return $cached['result'];
      }
  
    $p1 = $this->currPos;
    // start seq_1
    $p3 = $this->currPos;
    $r4 = $this->input[$this->currPos] ?? '';
    if (preg_match("/^[_a-zA-Z]/", $r4)) {
      $this->currPos++;
    } else {
      $r4 = self::$FAILED;
      if (!$silence) {$this->fail(12);}
      $r2 = self::$FAILED;
      goto seq_1;
    }
    for (;;) {
      $r6 = $this->input[$this->currPos] ?? '';
      if (preg_match("/^[_a-zA-Z0-9]/", $r6)) {
        $this->currPos++;
      } else {
        $r6 = self::$FAILED;
        if (!$silence) {$this->fail(13);}
        break;
      }
    }
    // free $r6
    $r5 = true;
    if ($r5===self::$FAILED) {
      $this->currPos = $p3;
      $r2 = self::$FAILED;
      goto seq_1;
    }
    // free $r5
    $r2 = true;
    seq_1:
    if ($r2!==self::$FAILED) {
      $r2 = substr($this->input, $p1, $this->currPos - $p1);
    } else {
      $r2 = self::$FAILED;
    }
    // free $p3
    // free $p1
    $cached = ['nextPos' => $this->currPos, 'result' => $r2];
  
    $this->cache[$key] = $cached;
    return $r2;
  }
  private function discardLPAREN($silence) {
    $key = json_encode([357, $this->currPos]);
    $cached = $this->cache[$key] ?? null;
      if ($cached) {
        $this->currPos = $cached['nextPos'];
  
        return $cached['result'];
      }
  
    // start seq_1
    $p1 = $this->currPos;
    if (($this->input[$this->currPos] ?? null) === "(") {
      $this->currPos++;
      $r3 = "(";
    } else {
      if (!$silence) {$this->fail(14);}
      $r3 = self::$FAILED;
      $r2 = self::$FAILED;
      goto seq_1;
    }
    $r4 = $this->discardOWS($silence);
    if ($r4===self::$FAILED) {
      $this->currPos = $p1;
      $r2 = self::$FAILED;
      goto seq_1;
    }
    $r2 = true;
    seq_1:
    // free $r2,$p1
    $cached = ['nextPos' => $this->currPos, 'result' => $r2];
  
    $this->cache[$key] = $cached;
    return $r2;
  }
  private function discardRPAREN($silence) {
    $key = json_encode([359, $this->currPos]);
    $cached = $this->cache[$key] ?? null;
      if ($cached) {
        $this->currPos = $cached['nextPos'];
  
        return $cached['result'];
      }
  
    // start seq_1
    $p1 = $this->currPos;
    if (($this->input[$this->currPos] ?? null) === ")") {
      $this->currPos++;
      $r3 = ")";
    } else {
      if (!$silence) {$this->fail(15);}
      $r3 = self::$FAILED;
      $r2 = self::$FAILED;
      goto seq_1;
    }
    $r4 = $this->discardOWS($silence);
    if ($r4===self::$FAILED) {
      $this->currPos = $p1;
      $r2 = self::$FAILED;
      goto seq_1;
    }
    $r2 = true;
    seq_1:
    // free $r2,$p1
    $cached = ['nextPos' => $this->currPos, 'result' => $r2];
  
    $this->cache[$key] = $cached;
    return $r2;
  }
  private function parsefunction_body($silence, $boolParams) {
    $key = json_encode([258, $this->currPos, $boolParams & 0x1]);
    $cached = $this->cache[$key] ?? null;
      if ($cached) {
        $this->currPos = $cached['nextPos'];
  
        return $cached['result'];
      }
  
    $p2 = $this->currPos;
    // start seq_1
    $p3 = $this->currPos;
    $r4 = $this->parsecompound_command($silence, $boolParams);
    // c <- $r4
    if ($r4===self::$FAILED) {
      $r1 = self::$FAILED;
      goto seq_1;
    }
    $r5 = $this->parseredirect_list($silence, $boolParams);
    if ($r5===self::$FAILED) {
      $r5 = null;
    }
    // r <- $r5
    $r1 = true;
    seq_1:
    if ($r1!==self::$FAILED) {
      $this->savedPos = $p2;
      $r1 = $this->a17($r4, $r5);
    }
    // free $p3
    $cached = ['nextPos' => $this->currPos, 'result' => $r1];
  
    $this->cache[$key] = $cached;
    return $r1;
  }
  private function parsecmd_prefix($silence, $boolParams) {
    $key = json_encode([268, $this->currPos, $boolParams & 0x1]);
    $cached = $this->cache[$key] ?? null;
      if ($cached) {
        $this->currPos = $cached['nextPos'];
  
        return $cached['result'];
      }
  
    $p2 = $this->currPos;
    $r3 = [];
    for (;;) {
      // start choice_1
      $r4 = $this->parseio_redirect($silence, $boolParams);
      if ($r4!==self::$FAILED) {
        goto choice_1;
      }
      $r4 = $this->parseASSIGNMENT_WORD($silence, $boolParams);
      choice_1:
      if ($r4!==self::$FAILED) {
        $r3[] = $r4;
      } else {
        break;
      }
    }
    if (count($r3) === 0) {
      $r3 = self::$FAILED;
    }
    // contents <- $r3
    // free $r4
    $r1 = $r3;
    if ($r1!==self::$FAILED) {
      $this->savedPos = $p2;
      $r1 = $this->a18($r3);
    }
    $cached = ['nextPos' => $this->currPos, 'result' => $r1];
  
    $this->cache[$key] = $cached;
    return $r1;
  }
  private function parseWORD($silence, $boolParams) {
    $key = json_encode([364, $this->currPos, $boolParams & 0x1]);
    $cached = $this->cache[$key] ?? null;
      if ($cached) {
        $this->currPos = $cached['nextPos'];
  
        return $cached['result'];
      }
  
    $p2 = $this->currPos;
    // start seq_1
    $p3 = $this->currPos;
    $r4 = [];
    for (;;) {
      $r5 = $this->parseword_part($silence, $boolParams);
      if ($r5!==self::$FAILED) {
        $r4[] = $r5;
      } else {
        break;
      }
    }
    if (count($r4) === 0) {
      $r4 = self::$FAILED;
    }
    // parts <- $r4
    if ($r4===self::$FAILED) {
      $r1 = self::$FAILED;
      goto seq_1;
    }
    // free $r5
    $r5 = $this->discardOWS($silence);
    if ($r5===self::$FAILED) {
      $this->currPos = $p3;
      $r1 = self::$FAILED;
      goto seq_1;
    }
    $r1 = true;
    seq_1:
    if ($r1!==self::$FAILED) {
      $this->savedPos = $p2;
      $r1 = $this->a19($r4);
    }
    // free $p3
    $cached = ['nextPos' => $this->currPos, 'result' => $r1];
  
    $this->cache[$key] = $cached;
    return $r1;
  }
  private function parsecmd_suffix($silence, $boolParams) {
    $key = json_encode([270, $this->currPos, $boolParams & 0x1]);
    $cached = $this->cache[$key] ?? null;
      if ($cached) {
        $this->currPos = $cached['nextPos'];
  
        return $cached['result'];
      }
  
    $r1 = [];
    for (;;) {
      // start choice_1
      $r2 = $this->parseio_redirect($silence, $boolParams);
      if ($r2!==self::$FAILED) {
        goto choice_1;
      }
      $r2 = $this->parseWORD($silence, $boolParams);
      choice_1:
      if ($r2!==self::$FAILED) {
        $r1[] = $r2;
      } else {
        break;
      }
    }
    if (count($r1) === 0) {
      $r1 = self::$FAILED;
    }
    // free $r2
    $cached = ['nextPos' => $this->currPos, 'result' => $r1];
  
    $this->cache[$key] = $cached;
    return $r1;
  }
  private function parsecmd_name($silence, $boolParams) {
    $key = json_encode([266, $this->currPos, $boolParams & 0x1]);
    $cached = $this->cache[$key] ?? null;
      if ($cached) {
        $this->currPos = $cached['nextPos'];
  
        return $cached['result'];
      }
  
    $p2 = $this->currPos;
    // start seq_1
    $p3 = $this->currPos;
    $p4 = $this->currPos;
    $r5 = $this->discardreserved(true);
    if ($r5 === self::$FAILED) {
      $r5 = false;
    } else {
      $r5 = self::$FAILED;
      $this->currPos = $p4;
      $r1 = self::$FAILED;
      goto seq_1;
    }
    // free $p4
    $r6 = $this->parseWORD($silence, $boolParams);
    // word <- $r6
    if ($r6===self::$FAILED) {
      $this->currPos = $p3;
      $r1 = self::$FAILED;
      goto seq_1;
    }
    $r1 = true;
    seq_1:
    if ($r1!==self::$FAILED) {
      $this->savedPos = $p2;
      $r1 = $this->a20($r6);
    }
    // free $p3
    $cached = ['nextPos' => $this->currPos, 'result' => $r1];
  
    $this->cache[$key] = $cached;
    return $r1;
  }
  private function parsebrace_group($silence) {
    $key = json_encode([260, $this->currPos]);
    $cached = $this->cache[$key] ?? null;
      if ($cached) {
        $this->currPos = $cached['nextPos'];
  
        return $cached['result'];
      }
  
    $p2 = $this->currPos;
    // start seq_1
    $p3 = $this->currPos;
    $r4 = $this->discardLbrace($silence);
    if ($r4===self::$FAILED) {
      $r1 = self::$FAILED;
      goto seq_1;
    }
    $r5 = $this->parsecompound_list($silence);
    // list <- $r5
    if ($r5===self::$FAILED) {
      $this->currPos = $p3;
      $r1 = self::$FAILED;
      goto seq_1;
    }
    $r6 = $this->discardRbrace($silence);
    if ($r6===self::$FAILED) {
      $this->currPos = $p3;
      $r1 = self::$FAILED;
      goto seq_1;
    }
    $r1 = true;
    seq_1:
    if ($r1!==self::$FAILED) {
      $this->savedPos = $p2;
      $r1 = $this->a21($r5);
    }
    // free $p3
    $cached = ['nextPos' => $this->currPos, 'result' => $r1];
  
    $this->cache[$key] = $cached;
    return $r1;
  }
  private function parsesubshell($silence) {
    $key = json_encode([224, $this->currPos]);
    $cached = $this->cache[$key] ?? null;
      if ($cached) {
        $this->currPos = $cached['nextPos'];
  
        return $cached['result'];
      }
  
    $p2 = $this->currPos;
    // start seq_1
    $p3 = $this->currPos;
    $r4 = $this->discardLPAREN($silence);
    if ($r4===self::$FAILED) {
      $r1 = self::$FAILED;
      goto seq_1;
    }
    $r5 = $this->parsecompound_list($silence);
    // list <- $r5
    if ($r5===self::$FAILED) {
      $this->currPos = $p3;
      $r1 = self::$FAILED;
      goto seq_1;
    }
    $r6 = $this->discardRPAREN($silence);
    if ($r6===self::$FAILED) {
      $this->currPos = $p3;
      $r1 = self::$FAILED;
      goto seq_1;
    }
    $r1 = true;
    seq_1:
    if ($r1!==self::$FAILED) {
      $this->savedPos = $p2;
      $r1 = $this->a22($r5);
    }
    // free $p3
    $cached = ['nextPos' => $this->currPos, 'result' => $r1];
  
    $this->cache[$key] = $cached;
    return $r1;
  }
  private function parsefor_clause($silence, $boolParams) {
    $key = json_encode([228, $this->currPos, $boolParams & 0x1]);
    $cached = $this->cache[$key] ?? null;
      if ($cached) {
        $this->currPos = $cached['nextPos'];
  
        return $cached['result'];
      }
  
    // start choice_1
    $p2 = $this->currPos;
    // start seq_1
    $p3 = $this->currPos;
    $r4 = $this->discardFor($silence);
    if ($r4===self::$FAILED) {
      $r1 = self::$FAILED;
      goto seq_1;
    }
    $r5 = $this->parsefor_name($silence);
    // name <- $r5
    if ($r5===self::$FAILED) {
      $this->currPos = $p3;
      $r1 = self::$FAILED;
      goto seq_1;
    }
    $r6 = $this->discardlinebreak($silence);
    if ($r6===self::$FAILED) {
      $this->currPos = $p3;
      $r1 = self::$FAILED;
      goto seq_1;
    }
    $r7 = $this->discardfor_case_in($silence);
    if ($r7===self::$FAILED) {
      $this->currPos = $p3;
      $r1 = self::$FAILED;
      goto seq_1;
    }
    $r8 = $this->parsewordlist($silence, $boolParams);
    if ($r8===self::$FAILED) {
      $r8 = null;
    }
    // wordlist <- $r8
    $r9 = $this->discardsequential_sep($silence);
    if ($r9===self::$FAILED) {
      $this->currPos = $p3;
      $r1 = self::$FAILED;
      goto seq_1;
    }
    $r10 = $this->parsedo_group($silence);
    // do_group <- $r10
    if ($r10===self::$FAILED) {
      $this->currPos = $p3;
      $r1 = self::$FAILED;
      goto seq_1;
    }
    $r1 = true;
    seq_1:
    if ($r1!==self::$FAILED) {
      $this->savedPos = $p2;
      $r1 = $this->a23($r5, $r8, $r10);
      goto choice_1;
    }
    // free $p3
    $p3 = $this->currPos;
    // start seq_2
    $p11 = $this->currPos;
    $r12 = $this->discardFor($silence);
    if ($r12===self::$FAILED) {
      $r1 = self::$FAILED;
      goto seq_2;
    }
    $r13 = $this->parsefor_name($silence);
    // name <- $r13
    if ($r13===self::$FAILED) {
      $this->currPos = $p11;
      $r1 = self::$FAILED;
      goto seq_2;
    }
    $r14 = $this->discardsequential_sep($silence);
    if ($r14===self::$FAILED) {
      $r14 = null;
    }
    $r15 = $this->parsedo_group($silence);
    // do_group <- $r15
    if ($r15===self::$FAILED) {
      $this->currPos = $p11;
      $r1 = self::$FAILED;
      goto seq_2;
    }
    $r1 = true;
    seq_2:
    if ($r1!==self::$FAILED) {
      $this->savedPos = $p3;
      $r1 = $this->a24($r13, $r15);
    }
    // free $p11
    choice_1:
    $cached = ['nextPos' => $this->currPos, 'result' => $r1];
  
    $this->cache[$key] = $cached;
    return $r1;
  }
  private function parsecase_clause($silence, $boolParams) {
    $key = json_encode([236, $this->currPos, $boolParams & 0x1]);
    $cached = $this->cache[$key] ?? null;
      if ($cached) {
        $this->currPos = $cached['nextPos'];
  
        return $cached['result'];
      }
  
    $p2 = $this->currPos;
    // start seq_1
    $p3 = $this->currPos;
    $r4 = $this->discardCase($silence);
    if ($r4===self::$FAILED) {
      $r1 = self::$FAILED;
      goto seq_1;
    }
    $r5 = $this->parseWORD($silence, $boolParams);
    // word <- $r5
    if ($r5===self::$FAILED) {
      $this->currPos = $p3;
      $r1 = self::$FAILED;
      goto seq_1;
    }
    $r6 = $this->discardlinebreak($silence);
    if ($r6===self::$FAILED) {
      $this->currPos = $p3;
      $r1 = self::$FAILED;
      goto seq_1;
    }
    $r7 = $this->discardfor_case_in($silence);
    if ($r7===self::$FAILED) {
      $this->currPos = $p3;
      $r1 = self::$FAILED;
      goto seq_1;
    }
    $r8 = $this->discardlinebreak($silence);
    if ($r8===self::$FAILED) {
      $this->currPos = $p3;
      $r1 = self::$FAILED;
      goto seq_1;
    }
    // start choice_1
    // start seq_2
    $p10 = $this->currPos;
    $r11 = $this->parsecase_list($silence, $boolParams);
    if ($r11===self::$FAILED) {
      $r9 = self::$FAILED;
      goto seq_2;
    }
    $p12 = $this->currPos;
    $r13 = $this->discardEsac($silence);
    if ($r13!==self::$FAILED) {
      $r13 = substr($this->input, $p12, $this->currPos - $p12);
    } else {
      $r13 = self::$FAILED;
      $this->currPos = $p10;
      $r9 = self::$FAILED;
      goto seq_2;
    }
    // free $p12
    $r9 = [$r11,$r13];
    seq_2:
    if ($r9!==self::$FAILED) {
      goto choice_1;
    }
    // free $p10
    // start seq_3
    $p10 = $this->currPos;
    $r14 = $this->parsecase_list_ns($silence, $boolParams);
    if ($r14===self::$FAILED) {
      $r9 = self::$FAILED;
      goto seq_3;
    }
    $p12 = $this->currPos;
    $r15 = $this->discardEsac($silence);
    if ($r15!==self::$FAILED) {
      $r15 = substr($this->input, $p12, $this->currPos - $p12);
    } else {
      $r15 = self::$FAILED;
      $this->currPos = $p10;
      $r9 = self::$FAILED;
      goto seq_3;
    }
    // free $p12
    $r9 = [$r14,$r15];
    seq_3:
    if ($r9!==self::$FAILED) {
      goto choice_1;
    }
    // free $p10
    $p10 = $this->currPos;
    $r9 = $this->discardEsac($silence);
    if ($r9!==self::$FAILED) {
      $r9 = substr($this->input, $p10, $this->currPos - $p10);
    } else {
      $r9 = self::$FAILED;
    }
    // free $p10
    choice_1:
    // list_esac <- $r9
    if ($r9===self::$FAILED) {
      $this->currPos = $p3;
      $r1 = self::$FAILED;
      goto seq_1;
    }
    $r1 = true;
    seq_1:
    if ($r1!==self::$FAILED) {
      $this->savedPos = $p2;
      $r1 = $this->a25($r5, $r9);
    }
    // free $p3
    $cached = ['nextPos' => $this->currPos, 'result' => $r1];
  
    $this->cache[$key] = $cached;
    return $r1;
  }
  private function parseif_clause($silence) {
    $key = json_encode([248, $this->currPos]);
    $cached = $this->cache[$key] ?? null;
      if ($cached) {
        $this->currPos = $cached['nextPos'];
  
        return $cached['result'];
      }
  
    $p2 = $this->currPos;
    // start seq_1
    $p3 = $this->currPos;
    $r4 = $this->discardIf($silence);
    if ($r4===self::$FAILED) {
      $r1 = self::$FAILED;
      goto seq_1;
    }
    $r5 = $this->parsecompound_list($silence);
    // condition <- $r5
    if ($r5===self::$FAILED) {
      $this->currPos = $p3;
      $r1 = self::$FAILED;
      goto seq_1;
    }
    $r6 = $this->discardThen($silence);
    if ($r6===self::$FAILED) {
      $this->currPos = $p3;
      $r1 = self::$FAILED;
      goto seq_1;
    }
    $r7 = $this->parsecompound_list($silence);
    // consequent <- $r7
    if ($r7===self::$FAILED) {
      $this->currPos = $p3;
      $r1 = self::$FAILED;
      goto seq_1;
    }
    $r8 = $this->parseelse_part($silence);
    if ($r8===self::$FAILED) {
      $r8 = null;
    }
    // else_part <- $r8
    $r9 = $this->discardFi($silence);
    if ($r9===self::$FAILED) {
      $this->currPos = $p3;
      $r1 = self::$FAILED;
      goto seq_1;
    }
    $r1 = true;
    seq_1:
    if ($r1!==self::$FAILED) {
      $this->savedPos = $p2;
      $r1 = $this->a26($r5, $r7, $r8);
    }
    // free $p3
    $cached = ['nextPos' => $this->currPos, 'result' => $r1];
  
    $this->cache[$key] = $cached;
    return $r1;
  }
  private function parsewhile_clause($silence) {
    $key = json_encode([252, $this->currPos]);
    $cached = $this->cache[$key] ?? null;
      if ($cached) {
        $this->currPos = $cached['nextPos'];
  
        return $cached['result'];
      }
  
    $p2 = $this->currPos;
    // start seq_1
    $p3 = $this->currPos;
    $r4 = $this->discardWhile($silence);
    if ($r4===self::$FAILED) {
      $r1 = self::$FAILED;
      goto seq_1;
    }
    $r5 = $this->parsecompound_list($silence);
    // list <- $r5
    if ($r5===self::$FAILED) {
      $this->currPos = $p3;
      $r1 = self::$FAILED;
      goto seq_1;
    }
    $r6 = $this->parsedo_group($silence);
    // body <- $r6
    if ($r6===self::$FAILED) {
      $this->currPos = $p3;
      $r1 = self::$FAILED;
      goto seq_1;
    }
    $r1 = true;
    seq_1:
    if ($r1!==self::$FAILED) {
      $this->savedPos = $p2;
      $r1 = $this->a27($r5, $r6);
    }
    // free $p3
    $cached = ['nextPos' => $this->currPos, 'result' => $r1];
  
    $this->cache[$key] = $cached;
    return $r1;
  }
  private function parseuntil_clause($silence) {
    $key = json_encode([254, $this->currPos]);
    $cached = $this->cache[$key] ?? null;
      if ($cached) {
        $this->currPos = $cached['nextPos'];
  
        return $cached['result'];
      }
  
    $p2 = $this->currPos;
    // start seq_1
    $p3 = $this->currPos;
    $r4 = $this->discardUntil($silence);
    if ($r4===self::$FAILED) {
      $r1 = self::$FAILED;
      goto seq_1;
    }
    $r5 = $this->parsecompound_list($silence);
    // list <- $r5
    if ($r5===self::$FAILED) {
      $this->currPos = $p3;
      $r1 = self::$FAILED;
      goto seq_1;
    }
    $r6 = $this->parsedo_group($silence);
    // body <- $r6
    if ($r6===self::$FAILED) {
      $this->currPos = $p3;
      $r1 = self::$FAILED;
      goto seq_1;
    }
    $r1 = true;
    seq_1:
    if ($r1!==self::$FAILED) {
      $this->savedPos = $p2;
      $r1 = $this->a28($r5, $r6);
    }
    // free $p3
    $cached = ['nextPos' => $this->currPos, 'result' => $r1];
  
    $this->cache[$key] = $cached;
    return $r1;
  }
  private function parseio_redirect($silence, $boolParams) {
    $key = json_encode([274, $this->currPos, $boolParams & 0x1]);
    $cached = $this->cache[$key] ?? null;
      if ($cached) {
        $this->currPos = $cached['nextPos'];
  
        return $cached['result'];
      }
  
    $p2 = $this->currPos;
    // start seq_1
    $p3 = $this->currPos;
    $r4 = $this->parseIO_NUMBER($silence);
    if ($r4===self::$FAILED) {
      $r4 = null;
    }
    // number <- $r4
    // start choice_1
    $r5 = $this->parseio_file($silence, $boolParams);
    if ($r5!==self::$FAILED) {
      goto choice_1;
    }
    $r5 = $this->parseio_here($silence, $boolParams);
    choice_1:
    // file_or_here <- $r5
    if ($r5===self::$FAILED) {
      $this->currPos = $p3;
      $r1 = self::$FAILED;
      goto seq_1;
    }
    $r1 = true;
    seq_1:
    if ($r1!==self::$FAILED) {
      $this->savedPos = $p2;
      $r1 = $this->a29($r4, $r5);
    }
    // free $p3
    $cached = ['nextPos' => $this->currPos, 'result' => $r1];
  
    $this->cache[$key] = $cached;
    return $r1;
  }
  private function parseASSIGNMENT_WORD($silence, $boolParams) {
    $key = json_encode([408, $this->currPos, $boolParams & 0x1]);
    $cached = $this->cache[$key] ?? null;
      if ($cached) {
        $this->currPos = $cached['nextPos'];
  
        return $cached['result'];
      }
  
    $p2 = $this->currPos;
    // start seq_1
    $p3 = $this->currPos;
    $r4 = $this->parseNAME($silence);
    // name <- $r4
    if ($r4===self::$FAILED) {
      $r1 = self::$FAILED;
      goto seq_1;
    }
    if (($this->input[$this->currPos] ?? null) === "=") {
      $this->currPos++;
      $r5 = "=";
    } else {
      if (!$silence) {$this->fail(16);}
      $r5 = self::$FAILED;
      $this->currPos = $p3;
      $r1 = self::$FAILED;
      goto seq_1;
    }
    $r6 = $this->parseWORD($silence, $boolParams);
    // word <- $r6
    if ($r6===self::$FAILED) {
      $this->currPos = $p3;
      $r1 = self::$FAILED;
      goto seq_1;
    }
    $r1 = true;
    seq_1:
    if ($r1!==self::$FAILED) {
      $this->savedPos = $p2;
      $r1 = $this->a30($r4, $r6);
    }
    // free $p3
    $cached = ['nextPos' => $this->currPos, 'result' => $r1];
  
    $this->cache[$key] = $cached;
    return $r1;
  }
  private function parseword_part($silence, $boolParams) {
    $key = json_encode([366, $this->currPos, $boolParams & 0x1]);
    $cached = $this->cache[$key] ?? null;
      if ($cached) {
        $this->currPos = $cached['nextPos'];
  
        return $cached['result'];
      }
  
    // start choice_1
    $r1 = $this->parsesingle_quoted_part($silence);
    if ($r1!==self::$FAILED) {
      goto choice_1;
    }
    $r1 = $this->parsedouble_quoted_part($silence);
    if ($r1!==self::$FAILED) {
      goto choice_1;
    }
    $r1 = $this->parsebare_escape_sequence($silence);
    if ($r1!==self::$FAILED) {
      goto choice_1;
    }
    $r1 = $this->parsebackquote_expansion($silence);
    if ($r1!==self::$FAILED) {
      goto choice_1;
    }
    $r1 = $this->parsedollar_expansion($silence);
    if ($r1!==self::$FAILED) {
      goto choice_1;
    }
    $r1 = $this->parseplain_part($silence, $boolParams);
    choice_1:
    $cached = ['nextPos' => $this->currPos, 'result' => $r1];
  
    $this->cache[$key] = $cached;
    return $r1;
  }
  private function discardreserved($silence) {
    $key = json_encode([345, $this->currPos]);
    $cached = $this->cache[$key] ?? null;
      if ($cached) {
        $this->currPos = $cached['nextPos'];
  
        return $cached['result'];
      }
  
    // start choice_1
    $r1 = $this->discardIf($silence);
    if ($r1!==self::$FAILED) {
      goto choice_1;
    }
    $r1 = $this->discardThen($silence);
    if ($r1!==self::$FAILED) {
      goto choice_1;
    }
    $r1 = $this->discardElse($silence);
    if ($r1!==self::$FAILED) {
      goto choice_1;
    }
    $r1 = $this->discardElif($silence);
    if ($r1!==self::$FAILED) {
      goto choice_1;
    }
    $r1 = $this->discardFi($silence);
    if ($r1!==self::$FAILED) {
      goto choice_1;
    }
    $r1 = $this->discardDo($silence);
    if ($r1!==self::$FAILED) {
      goto choice_1;
    }
    $r1 = $this->discardDone($silence);
    if ($r1!==self::$FAILED) {
      goto choice_1;
    }
    $r1 = $this->discardCase($silence);
    if ($r1!==self::$FAILED) {
      goto choice_1;
    }
    $r1 = $this->discardEsac($silence);
    if ($r1!==self::$FAILED) {
      goto choice_1;
    }
    $r1 = $this->discardWhile($silence);
    if ($r1!==self::$FAILED) {
      goto choice_1;
    }
    $r1 = $this->discardUntil($silence);
    if ($r1!==self::$FAILED) {
      goto choice_1;
    }
    $r1 = $this->discardFor($silence);
    if ($r1!==self::$FAILED) {
      goto choice_1;
    }
    $r1 = $this->discardLbrace($silence);
    if ($r1!==self::$FAILED) {
      goto choice_1;
    }
    $r1 = $this->discardRbrace($silence);
    if ($r1!==self::$FAILED) {
      goto choice_1;
    }
    $r1 = $this->discardBang($silence);
    if ($r1!==self::$FAILED) {
      goto choice_1;
    }
    $r1 = $this->discardIn($silence);
    choice_1:
    $cached = ['nextPos' => $this->currPos, 'result' => $r1];
  
    $this->cache[$key] = $cached;
    return $r1;
  }
  private function discardLbrace($silence) {
    $key = json_encode([337, $this->currPos]);
    $cached = $this->cache[$key] ?? null;
      if ($cached) {
        $this->currPos = $cached['nextPos'];
  
        return $cached['result'];
      }
  
    // start seq_1
    $p1 = $this->currPos;
    if (($this->input[$this->currPos] ?? null) === "{") {
      $this->currPos++;
      $r3 = "{";
    } else {
      if (!$silence) {$this->fail(17);}
      $r3 = self::$FAILED;
      $r2 = self::$FAILED;
      goto seq_1;
    }
    $r4 = $this->discardDELIM($silence);
    if ($r4===self::$FAILED) {
      $this->currPos = $p1;
      $r2 = self::$FAILED;
      goto seq_1;
    }
    $r2 = true;
    seq_1:
    // free $r2,$p1
    $cached = ['nextPos' => $this->currPos, 'result' => $r2];
  
    $this->cache[$key] = $cached;
    return $r2;
  }
  private function parsecompound_list($silence) {
    $key = json_encode([226, $this->currPos]);
    $cached = $this->cache[$key] ?? null;
      if ($cached) {
        $this->currPos = $cached['nextPos'];
  
        return $cached['result'];
      }
  
    // start choice_1
    $p2 = $this->currPos;
    // start seq_1
    $p3 = $this->currPos;
    $r4 = $this->discardlinebreak($silence);
    if ($r4===self::$FAILED) {
      $r1 = self::$FAILED;
      goto seq_1;
    }
    $r5 = [];
    for (;;) {
      $p7 = $this->currPos;
      // start seq_2
      $p8 = $this->currPos;
      $r9 = $this->parseand_or($silence, 0x0);
      // term <- $r9
      if ($r9===self::$FAILED) {
        $r6 = self::$FAILED;
        goto seq_2;
      }
      $p11 = $this->currPos;
      $r10 = $this->discardseparator($silence);
      // separator <- $r10
      if ($r10!==self::$FAILED) {
        $r10 = substr($this->input, $p11, $this->currPos - $p11);
      } else {
        $r10 = self::$FAILED;
        $this->currPos = $p8;
        $r6 = self::$FAILED;
        goto seq_2;
      }
      // free $p11
      $r6 = true;
      seq_2:
      if ($r6!==self::$FAILED) {
        $this->savedPos = $p7;
        $r6 = $this->a31($r9, $r10);
        $r5[] = $r6;
      } else {
        break;
      }
      // free $p8
    }
    if (count($r5) === 0) {
      $r5 = self::$FAILED;
    }
    // terms <- $r5
    if ($r5===self::$FAILED) {
      $this->currPos = $p3;
      $r1 = self::$FAILED;
      goto seq_1;
    }
    // free $r6
    $r6 = $this->parseand_or($silence, 0x0);
    if ($r6===self::$FAILED) {
      $r6 = null;
    }
    // last <- $r6
    $r1 = true;
    seq_1:
    if ($r1!==self::$FAILED) {
      $this->savedPos = $p2;
      $r1 = $this->a32($r5, $r6);
      goto choice_1;
    }
    // free $p3
    $p3 = $this->currPos;
    // start seq_3
    $p8 = $this->currPos;
    $r12 = $this->discardlinebreak($silence);
    if ($r12===self::$FAILED) {
      $r1 = self::$FAILED;
      goto seq_3;
    }
    $r13 = $this->parseand_or($silence, 0x0);
    // term <- $r13
    if ($r13===self::$FAILED) {
      $this->currPos = $p8;
      $r1 = self::$FAILED;
      goto seq_3;
    }
    $r1 = true;
    seq_3:
    if ($r1!==self::$FAILED) {
      $this->savedPos = $p3;
      $r1 = $this->a33($r13);
    }
    // free $p8
    choice_1:
    $cached = ['nextPos' => $this->currPos, 'result' => $r1];
  
    $this->cache[$key] = $cached;
    return $r1;
  }
  private function discardRbrace($silence) {
    $key = json_encode([339, $this->currPos]);
    $cached = $this->cache[$key] ?? null;
      if ($cached) {
        $this->currPos = $cached['nextPos'];
  
        return $cached['result'];
      }
  
    // start seq_1
    $p1 = $this->currPos;
    if (($this->input[$this->currPos] ?? null) === "}") {
      $this->currPos++;
      $r3 = "}";
    } else {
      if (!$silence) {$this->fail(18);}
      $r3 = self::$FAILED;
      $r2 = self::$FAILED;
      goto seq_1;
    }
    $r4 = $this->discardDELIM($silence);
    if ($r4===self::$FAILED) {
      $this->currPos = $p1;
      $r2 = self::$FAILED;
      goto seq_1;
    }
    $r2 = true;
    seq_1:
    // free $r2,$p1
    $cached = ['nextPos' => $this->currPos, 'result' => $r2];
  
    $this->cache[$key] = $cached;
    return $r2;
  }
  private function discardFor($silence) {
    $key = json_encode([335, $this->currPos]);
    $cached = $this->cache[$key] ?? null;
      if ($cached) {
        $this->currPos = $cached['nextPos'];
  
        return $cached['result'];
      }
  
    // start seq_1
    $p1 = $this->currPos;
    if ($this->currPos >= $this->inputLength ? false : substr_compare($this->input, "for", $this->currPos, 3, false) === 0) {
      $r3 = "for";
      $this->currPos += 3;
    } else {
      if (!$silence) {$this->fail(19);}
      $r3 = self::$FAILED;
      $r2 = self::$FAILED;
      goto seq_1;
    }
    $r4 = $this->discardDELIM($silence);
    if ($r4===self::$FAILED) {
      $this->currPos = $p1;
      $r2 = self::$FAILED;
      goto seq_1;
    }
    $r2 = true;
    seq_1:
    // free $r2,$p1
    $cached = ['nextPos' => $this->currPos, 'result' => $r2];
  
    $this->cache[$key] = $cached;
    return $r2;
  }
  private function parsefor_name($silence) {
    $key = json_encode([230, $this->currPos]);
    $cached = $this->cache[$key] ?? null;
      if ($cached) {
        $this->currPos = $cached['nextPos'];
  
        return $cached['result'];
      }
  
    $p2 = $this->currPos;
    // start seq_1
    $p3 = $this->currPos;
    $r4 = $this->parseNAME($silence);
    // name <- $r4
    if ($r4===self::$FAILED) {
      $r1 = self::$FAILED;
      goto seq_1;
    }
    $r5 = $this->discardOWS($silence);
    if ($r5===self::$FAILED) {
      $this->currPos = $p3;
      $r1 = self::$FAILED;
      goto seq_1;
    }
    $r1 = true;
    seq_1:
    if ($r1!==self::$FAILED) {
      $this->savedPos = $p2;
      $r1 = $this->a34($r4);
    }
    // free $p3
    $cached = ['nextPos' => $this->currPos, 'result' => $r1];
  
    $this->cache[$key] = $cached;
    return $r1;
  }
  private function discardfor_case_in($silence) {
    $key = json_encode([233, $this->currPos]);
    $cached = $this->cache[$key] ?? null;
      if ($cached) {
        $this->currPos = $cached['nextPos'];
  
        return $cached['result'];
      }
  
    // start seq_1
    $p1 = $this->currPos;
    $r3 = $this->discardIn($silence);
    if ($r3===self::$FAILED) {
      $r2 = self::$FAILED;
      goto seq_1;
    }
    $r4 = $this->discardOWS($silence);
    if ($r4===self::$FAILED) {
      $this->currPos = $p1;
      $r2 = self::$FAILED;
      goto seq_1;
    }
    $r2 = true;
    seq_1:
    // free $r2,$p1
    $cached = ['nextPos' => $this->currPos, 'result' => $r2];
  
    $this->cache[$key] = $cached;
    return $r2;
  }
  private function parsewordlist($silence, $boolParams) {
    $key = json_encode([234, $this->currPos, $boolParams & 0x1]);
    $cached = $this->cache[$key] ?? null;
      if ($cached) {
        $this->currPos = $cached['nextPos'];
  
        return $cached['result'];
      }
  
    $r1 = [];
    for (;;) {
      $r2 = $this->parseWORD($silence, $boolParams);
      if ($r2!==self::$FAILED) {
        $r1[] = $r2;
      } else {
        break;
      }
    }
    if (count($r1) === 0) {
      $r1 = self::$FAILED;
    }
    // free $r2
    $cached = ['nextPos' => $this->currPos, 'result' => $r1];
  
    $this->cache[$key] = $cached;
    return $r1;
  }
  private function discardsequential_sep($silence) {
    $key = json_encode([291, $this->currPos]);
    $cached = $this->cache[$key] ?? null;
      if ($cached) {
        $this->currPos = $cached['nextPos'];
  
        return $cached['result'];
      }
  
    // start choice_1
    // start seq_1
    $p2 = $this->currPos;
    $r3 = $this->discardSEMI($silence);
    if ($r3===self::$FAILED) {
      $r1 = self::$FAILED;
      goto seq_1;
    }
    $r4 = $this->discardlinebreak($silence);
    if ($r4===self::$FAILED) {
      $this->currPos = $p2;
      $r1 = self::$FAILED;
      goto seq_1;
    }
    $r1 = true;
    seq_1:
    if ($r1!==self::$FAILED) {
      goto choice_1;
    }
    // free $p2
    $r1 = $this->discardnewline_list($silence);
    choice_1:
    $cached = ['nextPos' => $this->currPos, 'result' => $r1];
  
    $this->cache[$key] = $cached;
    return $r1;
  }
  private function parsedo_group($silence) {
    $key = json_encode([262, $this->currPos]);
    $cached = $this->cache[$key] ?? null;
      if ($cached) {
        $this->currPos = $cached['nextPos'];
  
        return $cached['result'];
      }
  
    $p2 = $this->currPos;
    // start seq_1
    $p3 = $this->currPos;
    $r4 = $this->discardDo($silence);
    if ($r4===self::$FAILED) {
      $r1 = self::$FAILED;
      goto seq_1;
    }
    $r5 = $this->parsecompound_list($silence);
    // list <- $r5
    if ($r5===self::$FAILED) {
      $this->currPos = $p3;
      $r1 = self::$FAILED;
      goto seq_1;
    }
    $r6 = $this->discardDone($silence);
    if ($r6===self::$FAILED) {
      $this->currPos = $p3;
      $r1 = self::$FAILED;
      goto seq_1;
    }
    $r1 = true;
    seq_1:
    if ($r1!==self::$FAILED) {
      $this->savedPos = $p2;
      $r1 = $this->a35($r5);
    }
    // free $p3
    $cached = ['nextPos' => $this->currPos, 'result' => $r1];
  
    $this->cache[$key] = $cached;
    return $r1;
  }
  private function discardCase($silence) {
    $key = json_encode([327, $this->currPos]);
    $cached = $this->cache[$key] ?? null;
      if ($cached) {
        $this->currPos = $cached['nextPos'];
  
        return $cached['result'];
      }
  
    // start seq_1
    $p1 = $this->currPos;
    if ($this->currPos >= $this->inputLength ? false : substr_compare($this->input, "case", $this->currPos, 4, false) === 0) {
      $r3 = "case";
      $this->currPos += 4;
    } else {
      if (!$silence) {$this->fail(20);}
      $r3 = self::$FAILED;
      $r2 = self::$FAILED;
      goto seq_1;
    }
    $r4 = $this->discardDELIM($silence);
    if ($r4===self::$FAILED) {
      $this->currPos = $p1;
      $r2 = self::$FAILED;
      goto seq_1;
    }
    $r2 = true;
    seq_1:
    // free $r2,$p1
    $cached = ['nextPos' => $this->currPos, 'result' => $r2];
  
    $this->cache[$key] = $cached;
    return $r2;
  }
  private function parsecase_list($silence, $boolParams) {
    $key = json_encode([240, $this->currPos, $boolParams & 0x1]);
    $cached = $this->cache[$key] ?? null;
      if ($cached) {
        $this->currPos = $cached['nextPos'];
  
        return $cached['result'];
      }
  
    $r1 = [];
    for (;;) {
      $r2 = $this->parsecase_item($silence, $boolParams);
      if ($r2!==self::$FAILED) {
        $r1[] = $r2;
      } else {
        break;
      }
    }
    if (count($r1) === 0) {
      $r1 = self::$FAILED;
    }
    // free $r2
    $cached = ['nextPos' => $this->currPos, 'result' => $r1];
  
    $this->cache[$key] = $cached;
    return $r1;
  }
  private function discardEsac($silence) {
    $key = json_encode([329, $this->currPos]);
    $cached = $this->cache[$key] ?? null;
      if ($cached) {
        $this->currPos = $cached['nextPos'];
  
        return $cached['result'];
      }
  
    // start seq_1
    $p1 = $this->currPos;
    if ($this->currPos >= $this->inputLength ? false : substr_compare($this->input, "esac", $this->currPos, 4, false) === 0) {
      $r3 = "esac";
      $this->currPos += 4;
    } else {
      if (!$silence) {$this->fail(21);}
      $r3 = self::$FAILED;
      $r2 = self::$FAILED;
      goto seq_1;
    }
    $r4 = $this->discardDELIM($silence);
    if ($r4===self::$FAILED) {
      $this->currPos = $p1;
      $r2 = self::$FAILED;
      goto seq_1;
    }
    $r2 = true;
    seq_1:
    // free $r2,$p1
    $cached = ['nextPos' => $this->currPos, 'result' => $r2];
  
    $this->cache[$key] = $cached;
    return $r2;
  }
  private function parsecase_list_ns($silence, $boolParams) {
    $key = json_encode([238, $this->currPos, $boolParams & 0x1]);
    $cached = $this->cache[$key] ?? null;
      if ($cached) {
        $this->currPos = $cached['nextPos'];
  
        return $cached['result'];
      }
  
    // start choice_1
    $p2 = $this->currPos;
    // start seq_1
    $p3 = $this->currPos;
    $r4 = $this->parsecase_list($silence, $boolParams);
    // list <- $r4
    if ($r4===self::$FAILED) {
      $r1 = self::$FAILED;
      goto seq_1;
    }
    $r5 = $this->parsecase_item_ns($silence, $boolParams);
    // item <- $r5
    if ($r5===self::$FAILED) {
      $this->currPos = $p3;
      $r1 = self::$FAILED;
      goto seq_1;
    }
    $r1 = true;
    seq_1:
    if ($r1!==self::$FAILED) {
      $this->savedPos = $p2;
      $r1 = $this->a36($r4, $r5);
      goto choice_1;
    }
    // free $p3
    $p3 = $this->currPos;
    $r6 = $this->parsecase_item_ns($silence, $boolParams);
    // item <- $r6
    $r1 = $r6;
    if ($r1!==self::$FAILED) {
      $this->savedPos = $p3;
      $r1 = $this->a37($r6);
    }
    choice_1:
    $cached = ['nextPos' => $this->currPos, 'result' => $r1];
  
    $this->cache[$key] = $cached;
    return $r1;
  }
  private function discardIf($silence) {
    $key = json_encode([313, $this->currPos]);
    $cached = $this->cache[$key] ?? null;
      if ($cached) {
        $this->currPos = $cached['nextPos'];
  
        return $cached['result'];
      }
  
    // start seq_1
    $p1 = $this->currPos;
    if ($this->currPos >= $this->inputLength ? false : substr_compare($this->input, "if", $this->currPos, 2, false) === 0) {
      $r3 = "if";
      $this->currPos += 2;
    } else {
      if (!$silence) {$this->fail(22);}
      $r3 = self::$FAILED;
      $r2 = self::$FAILED;
      goto seq_1;
    }
    $r4 = $this->discardDELIM($silence);
    if ($r4===self::$FAILED) {
      $this->currPos = $p1;
      $r2 = self::$FAILED;
      goto seq_1;
    }
    $r2 = true;
    seq_1:
    // free $r2,$p1
    $cached = ['nextPos' => $this->currPos, 'result' => $r2];
  
    $this->cache[$key] = $cached;
    return $r2;
  }
  private function discardThen($silence) {
    $key = json_encode([315, $this->currPos]);
    $cached = $this->cache[$key] ?? null;
      if ($cached) {
        $this->currPos = $cached['nextPos'];
  
        return $cached['result'];
      }
  
    // start seq_1
    $p1 = $this->currPos;
    if ($this->currPos >= $this->inputLength ? false : substr_compare($this->input, "then", $this->currPos, 4, false) === 0) {
      $r3 = "then";
      $this->currPos += 4;
    } else {
      if (!$silence) {$this->fail(23);}
      $r3 = self::$FAILED;
      $r2 = self::$FAILED;
      goto seq_1;
    }
    $r4 = $this->discardDELIM($silence);
    if ($r4===self::$FAILED) {
      $this->currPos = $p1;
      $r2 = self::$FAILED;
      goto seq_1;
    }
    $r2 = true;
    seq_1:
    // free $r2,$p1
    $cached = ['nextPos' => $this->currPos, 'result' => $r2];
  
    $this->cache[$key] = $cached;
    return $r2;
  }
  private function parseelse_part($silence) {
    $key = json_encode([250, $this->currPos]);
    $cached = $this->cache[$key] ?? null;
      if ($cached) {
        $this->currPos = $cached['nextPos'];
  
        return $cached['result'];
      }
  
    // start choice_1
    $p2 = $this->currPos;
    // start seq_1
    $p3 = $this->currPos;
    $r4 = $this->discardElif($silence);
    if ($r4===self::$FAILED) {
      $r1 = self::$FAILED;
      goto seq_1;
    }
    $r5 = $this->parsecompound_list($silence);
    // condition <- $r5
    if ($r5===self::$FAILED) {
      $this->currPos = $p3;
      $r1 = self::$FAILED;
      goto seq_1;
    }
    $r6 = $this->discardThen($silence);
    if ($r6===self::$FAILED) {
      $this->currPos = $p3;
      $r1 = self::$FAILED;
      goto seq_1;
    }
    $r7 = $this->parsecompound_list($silence);
    // consequent <- $r7
    if ($r7===self::$FAILED) {
      $this->currPos = $p3;
      $r1 = self::$FAILED;
      goto seq_1;
    }
    $r8 = $this->parseelse_part($silence);
    if ($r8===self::$FAILED) {
      $r8 = null;
    }
    // else_part <- $r8
    $r1 = true;
    seq_1:
    if ($r1!==self::$FAILED) {
      $this->savedPos = $p2;
      $r1 = $this->a38($r5, $r7, $r8);
      goto choice_1;
    }
    // free $p3
    $p3 = $this->currPos;
    // start seq_2
    $p9 = $this->currPos;
    $r10 = $this->discardElse($silence);
    if ($r10===self::$FAILED) {
      $r1 = self::$FAILED;
      goto seq_2;
    }
    $r11 = $this->parsecompound_list($silence);
    // alternative <- $r11
    if ($r11===self::$FAILED) {
      $this->currPos = $p9;
      $r1 = self::$FAILED;
      goto seq_2;
    }
    $r1 = true;
    seq_2:
    if ($r1!==self::$FAILED) {
      $this->savedPos = $p3;
      $r1 = $this->a39($r11);
    }
    // free $p9
    choice_1:
    $cached = ['nextPos' => $this->currPos, 'result' => $r1];
  
    $this->cache[$key] = $cached;
    return $r1;
  }
  private function discardFi($silence) {
    $key = json_encode([321, $this->currPos]);
    $cached = $this->cache[$key] ?? null;
      if ($cached) {
        $this->currPos = $cached['nextPos'];
  
        return $cached['result'];
      }
  
    // start seq_1
    $p1 = $this->currPos;
    if ($this->currPos >= $this->inputLength ? false : substr_compare($this->input, "fi", $this->currPos, 2, false) === 0) {
      $r3 = "fi";
      $this->currPos += 2;
    } else {
      if (!$silence) {$this->fail(24);}
      $r3 = self::$FAILED;
      $r2 = self::$FAILED;
      goto seq_1;
    }
    $r4 = $this->discardDELIM($silence);
    if ($r4===self::$FAILED) {
      $this->currPos = $p1;
      $r2 = self::$FAILED;
      goto seq_1;
    }
    $r2 = true;
    seq_1:
    // free $r2,$p1
    $cached = ['nextPos' => $this->currPos, 'result' => $r2];
  
    $this->cache[$key] = $cached;
    return $r2;
  }
  private function discardWhile($silence) {
    $key = json_encode([331, $this->currPos]);
    $cached = $this->cache[$key] ?? null;
      if ($cached) {
        $this->currPos = $cached['nextPos'];
  
        return $cached['result'];
      }
  
    // start seq_1
    $p1 = $this->currPos;
    if ($this->currPos >= $this->inputLength ? false : substr_compare($this->input, "while", $this->currPos, 5, false) === 0) {
      $r3 = "while";
      $this->currPos += 5;
    } else {
      if (!$silence) {$this->fail(25);}
      $r3 = self::$FAILED;
      $r2 = self::$FAILED;
      goto seq_1;
    }
    $r4 = $this->discardDELIM($silence);
    if ($r4===self::$FAILED) {
      $this->currPos = $p1;
      $r2 = self::$FAILED;
      goto seq_1;
    }
    $r2 = true;
    seq_1:
    // free $r2,$p1
    $cached = ['nextPos' => $this->currPos, 'result' => $r2];
  
    $this->cache[$key] = $cached;
    return $r2;
  }
  private function discardUntil($silence) {
    $key = json_encode([333, $this->currPos]);
    $cached = $this->cache[$key] ?? null;
      if ($cached) {
        $this->currPos = $cached['nextPos'];
  
        return $cached['result'];
      }
  
    // start seq_1
    $p1 = $this->currPos;
    if ($this->currPos >= $this->inputLength ? false : substr_compare($this->input, "until", $this->currPos, 5, false) === 0) {
      $r3 = "until";
      $this->currPos += 5;
    } else {
      if (!$silence) {$this->fail(26);}
      $r3 = self::$FAILED;
      $r2 = self::$FAILED;
      goto seq_1;
    }
    $r4 = $this->discardDELIM($silence);
    if ($r4===self::$FAILED) {
      $this->currPos = $p1;
      $r2 = self::$FAILED;
      goto seq_1;
    }
    $r2 = true;
    seq_1:
    // free $r2,$p1
    $cached = ['nextPos' => $this->currPos, 'result' => $r2];
  
    $this->cache[$key] = $cached;
    return $r2;
  }
  private function parseIO_NUMBER($silence) {
    $key = json_encode([414, $this->currPos]);
    $cached = $this->cache[$key] ?? null;
      if ($cached) {
        $this->currPos = $cached['nextPos'];
  
        return $cached['result'];
      }
  
    $p1 = $this->currPos;
    $r2 = self::$FAILED;
    for (;;) {
      $r3 = $this->input[$this->currPos] ?? '';
      if (preg_match("/^[0-9]/", $r3)) {
        $this->currPos++;
        $r2 = true;
      } else {
        $r3 = self::$FAILED;
        if (!$silence) {$this->fail(27);}
        break;
      }
    }
    if ($r2!==self::$FAILED) {
      $r2 = substr($this->input, $p1, $this->currPos - $p1);
    } else {
      $r2 = self::$FAILED;
    }
    // free $r3
    // free $p1
    $cached = ['nextPos' => $this->currPos, 'result' => $r2];
  
    $this->cache[$key] = $cached;
    return $r2;
  }
  private function parseio_file($silence, $boolParams) {
    $key = json_encode([276, $this->currPos, $boolParams & 0x1]);
    $cached = $this->cache[$key] ?? null;
      if ($cached) {
        $this->currPos = $cached['nextPos'];
  
        return $cached['result'];
      }
  
    // start choice_1
    $p2 = $this->currPos;
    // start seq_1
    $p3 = $this->currPos;
    $r4 = $this->discardLESSAND($silence);
    if ($r4===self::$FAILED) {
      $r1 = self::$FAILED;
      goto seq_1;
    }
    $r5 = $this->parseWORD($silence, $boolParams);
    // filename <- $r5
    if ($r5===self::$FAILED) {
      $this->currPos = $p3;
      $r1 = self::$FAILED;
      goto seq_1;
    }
    $r1 = true;
    seq_1:
    if ($r1!==self::$FAILED) {
      $this->savedPos = $p2;
      $r1 = $this->a40($r5);
      goto choice_1;
    }
    // free $p3
    $p3 = $this->currPos;
    // start seq_2
    $p6 = $this->currPos;
    $r7 = $this->discardLESSGREAT($silence);
    if ($r7===self::$FAILED) {
      $r1 = self::$FAILED;
      goto seq_2;
    }
    $r8 = $this->parseWORD($silence, $boolParams);
    // filename <- $r8
    if ($r8===self::$FAILED) {
      $this->currPos = $p6;
      $r1 = self::$FAILED;
      goto seq_2;
    }
    $r1 = true;
    seq_2:
    if ($r1!==self::$FAILED) {
      $this->savedPos = $p3;
      $r1 = $this->a41($r8);
      goto choice_1;
    }
    // free $p6
    $p6 = $this->currPos;
    // start seq_3
    $p9 = $this->currPos;
    $r10 = $this->discardLESS($silence);
    if ($r10===self::$FAILED) {
      $r1 = self::$FAILED;
      goto seq_3;
    }
    $r11 = $this->parseWORD($silence, $boolParams);
    // filename <- $r11
    if ($r11===self::$FAILED) {
      $this->currPos = $p9;
      $r1 = self::$FAILED;
      goto seq_3;
    }
    $r1 = true;
    seq_3:
    if ($r1!==self::$FAILED) {
      $this->savedPos = $p6;
      $r1 = $this->a42($r11);
      goto choice_1;
    }
    // free $p9
    $p9 = $this->currPos;
    // start seq_4
    $p12 = $this->currPos;
    $r13 = $this->discardGREATAND($silence);
    if ($r13===self::$FAILED) {
      $r1 = self::$FAILED;
      goto seq_4;
    }
    $r14 = $this->parseWORD($silence, $boolParams);
    // filename <- $r14
    if ($r14===self::$FAILED) {
      $this->currPos = $p12;
      $r1 = self::$FAILED;
      goto seq_4;
    }
    $r1 = true;
    seq_4:
    if ($r1!==self::$FAILED) {
      $this->savedPos = $p9;
      $r1 = $this->a43($r14);
      goto choice_1;
    }
    // free $p12
    $p12 = $this->currPos;
    // start seq_5
    $p15 = $this->currPos;
    $r16 = $this->discardDGREAT($silence);
    if ($r16===self::$FAILED) {
      $r1 = self::$FAILED;
      goto seq_5;
    }
    $r17 = $this->parseWORD($silence, $boolParams);
    // filename <- $r17
    if ($r17===self::$FAILED) {
      $this->currPos = $p15;
      $r1 = self::$FAILED;
      goto seq_5;
    }
    $r1 = true;
    seq_5:
    if ($r1!==self::$FAILED) {
      $this->savedPos = $p12;
      $r1 = $this->a44($r17);
      goto choice_1;
    }
    // free $p15
    $p15 = $this->currPos;
    // start seq_6
    $p18 = $this->currPos;
    $r19 = $this->discardCLOBBER($silence);
    if ($r19===self::$FAILED) {
      $r1 = self::$FAILED;
      goto seq_6;
    }
    $r20 = $this->parseWORD($silence, $boolParams);
    // filename <- $r20
    if ($r20===self::$FAILED) {
      $this->currPos = $p18;
      $r1 = self::$FAILED;
      goto seq_6;
    }
    $r1 = true;
    seq_6:
    if ($r1!==self::$FAILED) {
      $this->savedPos = $p15;
      $r1 = $this->a45($r20);
      goto choice_1;
    }
    // free $p18
    $p18 = $this->currPos;
    // start seq_7
    $p21 = $this->currPos;
    $r22 = $this->discardGREAT($silence);
    if ($r22===self::$FAILED) {
      $r1 = self::$FAILED;
      goto seq_7;
    }
    $r23 = $this->parseWORD($silence, $boolParams);
    // filename <- $r23
    if ($r23===self::$FAILED) {
      $this->currPos = $p21;
      $r1 = self::$FAILED;
      goto seq_7;
    }
    $r1 = true;
    seq_7:
    if ($r1!==self::$FAILED) {
      $this->savedPos = $p18;
      $r1 = $this->a46($r23);
    }
    // free $p21
    choice_1:
    $cached = ['nextPos' => $this->currPos, 'result' => $r1];
  
    $this->cache[$key] = $cached;
    return $r1;
  }
  private function parseio_here($silence, $boolParams) {
    $key = json_encode([278, $this->currPos, $boolParams & 0x1]);
    $cached = $this->cache[$key] ?? null;
      if ($cached) {
        $this->currPos = $cached['nextPos'];
  
        return $cached['result'];
      }
  
    $p2 = $this->currPos;
    // start seq_1
    $p3 = $this->currPos;
    // start choice_1
    $p5 = $this->currPos;
    $r4 = $this->discardDLESSDASH($silence);
    if ($r4!==self::$FAILED) {
      $this->savedPos = $p5;
      $r4 = $this->a47();
      goto choice_1;
    }
    $p6 = $this->currPos;
    $r4 = $this->discardDLESS($silence);
    if ($r4!==self::$FAILED) {
      $this->savedPos = $p6;
      $r4 = $this->a48();
    }
    choice_1:
    // op <- $r4
    if ($r4===self::$FAILED) {
      $r1 = self::$FAILED;
      goto seq_1;
    }
    $r7 = $this->parsehere_end($silence, $boolParams);
    // end <- $r7
    if ($r7===self::$FAILED) {
      $this->currPos = $p3;
      $r1 = self::$FAILED;
      goto seq_1;
    }
    $r1 = true;
    seq_1:
    if ($r1!==self::$FAILED) {
      $this->savedPos = $p2;
      $r1 = $this->a49($r4, $r7);
    }
    // free $p3
    $cached = ['nextPos' => $this->currPos, 'result' => $r1];
  
    $this->cache[$key] = $cached;
    return $r1;
  }
  private function parsesingle_quoted_part($silence) {
    $key = json_encode([368, $this->currPos]);
    $cached = $this->cache[$key] ?? null;
      if ($cached) {
        $this->currPos = $cached['nextPos'];
  
        return $cached['result'];
      }
  
    $p2 = $this->currPos;
    // start seq_1
    $p3 = $this->currPos;
    if (($this->input[$this->currPos] ?? null) === "'") {
      $this->currPos++;
      $r4 = "'";
    } else {
      if (!$silence) {$this->fail(28);}
      $r4 = self::$FAILED;
      $r1 = self::$FAILED;
      goto seq_1;
    }
    $p6 = $this->currPos;
    for (;;) {
      $r7 = self::charAt($this->input, $this->currPos);
      if ($r7 !== '' && !($r7 === "'")) {
        $this->currPos += strlen($r7);
      } else {
        $r7 = self::$FAILED;
        if (!$silence) {$this->fail(29);}
        break;
      }
    }
    // free $r7
    $r5 = true;
    // contents <- $r5
    if ($r5!==self::$FAILED) {
      $r5 = substr($this->input, $p6, $this->currPos - $p6);
    } else {
      $r5 = self::$FAILED;
      $this->currPos = $p3;
      $r1 = self::$FAILED;
      goto seq_1;
    }
    // free $p6
    if (($this->input[$this->currPos] ?? null) === "'") {
      $this->currPos++;
      $r7 = "'";
    } else {
      if (!$silence) {$this->fail(28);}
      $r7 = self::$FAILED;
      $this->currPos = $p3;
      $r1 = self::$FAILED;
      goto seq_1;
    }
    $r1 = true;
    seq_1:
    if ($r1!==self::$FAILED) {
      $this->savedPos = $p2;
      $r1 = $this->a50($r5);
    }
    // free $p3
    $cached = ['nextPos' => $this->currPos, 'result' => $r1];
  
    $this->cache[$key] = $cached;
    return $r1;
  }
  private function parsedouble_quoted_part($silence) {
    $key = json_encode([370, $this->currPos]);
    $cached = $this->cache[$key] ?? null;
      if ($cached) {
        $this->currPos = $cached['nextPos'];
  
        return $cached['result'];
      }
  
    $p2 = $this->currPos;
    // start seq_1
    $p3 = $this->currPos;
    if (($this->input[$this->currPos] ?? null) === "\"") {
      $this->currPos++;
      $r4 = "\"";
    } else {
      if (!$silence) {$this->fail(30);}
      $r4 = self::$FAILED;
      $r1 = self::$FAILED;
      goto seq_1;
    }
    $r5 = [];
    for (;;) {
      // start choice_1
      $r6 = $this->parsedquoted_escape($silence);
      if ($r6!==self::$FAILED) {
        goto choice_1;
      }
      $r6 = $this->parsebackquote_expansion($silence);
      if ($r6!==self::$FAILED) {
        goto choice_1;
      }
      $r6 = $this->parsedollar_expansion($silence);
      if ($r6!==self::$FAILED) {
        goto choice_1;
      }
      if (($this->input[$this->currPos] ?? null) === "\\") {
        $this->currPos++;
        $r6 = "\\";
        goto choice_1;
      } else {
        if (!$silence) {$this->fail(31);}
        $r6 = self::$FAILED;
      }
      $p7 = $this->currPos;
      $r6 = self::$FAILED;
      for (;;) {
        if (strcspn($this->input, "\"`\$\\", $this->currPos, 1) !== 0) {
          $r8 = self::consumeChar($this->input, $this->currPos);
          $r6 = true;
        } else {
          $r8 = self::$FAILED;
          if (!$silence) {$this->fail(32);}
          break;
        }
      }
      if ($r6!==self::$FAILED) {
        $r6 = substr($this->input, $p7, $this->currPos - $p7);
      } else {
        $r6 = self::$FAILED;
      }
      // free $r8
      // free $p7
      choice_1:
      if ($r6!==self::$FAILED) {
        $r5[] = $r6;
      } else {
        break;
      }
    }
    // contents <- $r5
    // free $r6
    if (($this->input[$this->currPos] ?? null) === "\"") {
      $this->currPos++;
      $r6 = "\"";
    } else {
      if (!$silence) {$this->fail(30);}
      $r6 = self::$FAILED;
      $this->currPos = $p3;
      $r1 = self::$FAILED;
      goto seq_1;
    }
    $r1 = true;
    seq_1:
    if ($r1!==self::$FAILED) {
      $this->savedPos = $p2;
      $r1 = $this->a51($r5);
    }
    // free $p3
    $cached = ['nextPos' => $this->currPos, 'result' => $r1];
  
    $this->cache[$key] = $cached;
    return $r1;
  }
  private function parsebare_escape_sequence($silence) {
    $key = json_encode([374, $this->currPos]);
    $cached = $this->cache[$key] ?? null;
      if ($cached) {
        $this->currPos = $cached['nextPos'];
  
        return $cached['result'];
      }
  
    $p2 = $this->currPos;
    // start seq_1
    $p3 = $this->currPos;
    if (($this->input[$this->currPos] ?? null) === "\\") {
      $this->currPos++;
      $r4 = "\\";
    } else {
      if (!$silence) {$this->fail(31);}
      $r4 = self::$FAILED;
      $r1 = self::$FAILED;
      goto seq_1;
    }
    $r5 = self::charAt($this->input, $this->currPos);
    // contents <- $r5
    if ($r5 !== '' && !($r5 === "\x0a")) {
      $this->currPos += strlen($r5);
    } else {
      $r5 = self::$FAILED;
      if (!$silence) {$this->fail(4);}
      $this->currPos = $p3;
      $r1 = self::$FAILED;
      goto seq_1;
    }
    $r1 = true;
    seq_1:
    if ($r1!==self::$FAILED) {
      $this->savedPos = $p2;
      $r1 = $this->a52($r5);
    }
    // free $p3
    $cached = ['nextPos' => $this->currPos, 'result' => $r1];
  
    $this->cache[$key] = $cached;
    return $r1;
  }
  private function parsebackquote_expansion($silence) {
    $key = json_encode([376, $this->currPos]);
    $cached = $this->cache[$key] ?? null;
      if ($cached) {
        $this->currPos = $cached['nextPos'];
  
        return $cached['result'];
      }
  
    $p2 = $this->currPos;
    // start seq_1
    $p3 = $this->currPos;
    if (($this->input[$this->currPos] ?? null) === "`") {
      $this->currPos++;
      $r4 = "`";
    } else {
      if (!$silence) {$this->fail(33);}
      $r4 = self::$FAILED;
      $r1 = self::$FAILED;
      goto seq_1;
    }
    $r5 = [];
    for (;;) {
      // start choice_1
      $r6 = $this->parsebackquoted_escape($silence);
      if ($r6!==self::$FAILED) {
        goto choice_1;
      }
      $r6 = $this->parsedollar_expansion($silence);
      if ($r6!==self::$FAILED) {
        goto choice_1;
      }
      $r6 = $this->parsedouble_backquote_expansion($silence);
      if ($r6!==self::$FAILED) {
        goto choice_1;
      }
      if (($this->input[$this->currPos] ?? null) === "\$") {
        $this->currPos++;
        $r6 = "\$";
        goto choice_1;
      } else {
        if (!$silence) {$this->fail(34);}
        $r6 = self::$FAILED;
      }
      if (($this->input[$this->currPos] ?? null) === "\\") {
        $this->currPos++;
        $r6 = "\\";
        goto choice_1;
      } else {
        if (!$silence) {$this->fail(31);}
        $r6 = self::$FAILED;
      }
      $p7 = $this->currPos;
      $r6 = self::$FAILED;
      for (;;) {
        if (strcspn($this->input, "`\$\\", $this->currPos, 1) !== 0) {
          $r8 = self::consumeChar($this->input, $this->currPos);
          $r6 = true;
        } else {
          $r8 = self::$FAILED;
          if (!$silence) {$this->fail(35);}
          break;
        }
      }
      if ($r6!==self::$FAILED) {
        $r6 = substr($this->input, $p7, $this->currPos - $p7);
      } else {
        $r6 = self::$FAILED;
      }
      // free $r8
      // free $p7
      choice_1:
      if ($r6!==self::$FAILED) {
        $r5[] = $r6;
      } else {
        break;
      }
    }
    // parts <- $r5
    // free $r6
    if (($this->input[$this->currPos] ?? null) === "`") {
      $this->currPos++;
      $r6 = "`";
    } else {
      if (!$silence) {$this->fail(33);}
      $r6 = self::$FAILED;
      $this->currPos = $p3;
      $r1 = self::$FAILED;
      goto seq_1;
    }
    $r1 = true;
    seq_1:
    if ($r1!==self::$FAILED) {
      $this->savedPos = $p2;
      $r1 = $this->a53($r5);
    }
    // free $p3
    $cached = ['nextPos' => $this->currPos, 'result' => $r1];
  
    $this->cache[$key] = $cached;
    return $r1;
  }
  private function parsedollar_expansion($silence) {
    $key = json_encode([382, $this->currPos]);
    $cached = $this->cache[$key] ?? null;
      if ($cached) {
        $this->currPos = $cached['nextPos'];
  
        return $cached['result'];
      }
  
    $p2 = $this->currPos;
    // start seq_1
    $p3 = $this->currPos;
    if (($this->input[$this->currPos] ?? null) === "\$") {
      $this->currPos++;
      $r4 = "\$";
    } else {
      if (!$silence) {$this->fail(34);}
      $r4 = self::$FAILED;
      $r1 = self::$FAILED;
      goto seq_1;
    }
    // start choice_1
    $r5 = $this->parsespecial_parameter($silence);
    if ($r5!==self::$FAILED) {
      goto choice_1;
    }
    $r5 = $this->parseshort_positional_parameter($silence);
    if ($r5!==self::$FAILED) {
      goto choice_1;
    }
    $r5 = $this->parsebrace_expansion($silence);
    if ($r5!==self::$FAILED) {
      goto choice_1;
    }
    $r5 = $this->parsearithmetic_expansion($silence);
    if ($r5!==self::$FAILED) {
      goto choice_1;
    }
    $r5 = $this->parsecommand_expansion($silence);
    if ($r5!==self::$FAILED) {
      goto choice_1;
    }
    $r5 = $this->parsenamed_parameter($silence);
    choice_1:
    // contents <- $r5
    if ($r5===self::$FAILED) {
      $this->currPos = $p3;
      $r1 = self::$FAILED;
      goto seq_1;
    }
    $r1 = true;
    seq_1:
    if ($r1!==self::$FAILED) {
      $this->savedPos = $p2;
      $r1 = $this->a54($r5);
    }
    // free $p3
    $cached = ['nextPos' => $this->currPos, 'result' => $r1];
  
    $this->cache[$key] = $cached;
    return $r1;
  }
  private function parseplain_part($silence, $boolParams) {
    $key = json_encode([406, $this->currPos, $boolParams & 0x1]);
    $cached = $this->cache[$key] ?? null;
      if ($cached) {
        $this->currPos = $cached['nextPos'];
  
        return $cached['result'];
      }
  
    $p2 = $this->currPos;
    // start choice_1
    $p4 = $this->currPos;
    // start seq_1
    $p5 = $this->currPos;
    if (/*no_rbrace*/($boolParams & 0x1) !== 0) {
      $r6 = false;
    } else {
      $r6 = self::$FAILED;
      $r3 = self::$FAILED;
      goto seq_1;
    }
    $r7 = self::$FAILED;
    for (;;) {
      if (strcspn($this->input, "'\"\\`\$ \x09\x0b\x0d\x0c\x0a&|;<>(){}", $this->currPos, 1) !== 0) {
        $r8 = self::consumeChar($this->input, $this->currPos);
        $r7 = true;
      } else {
        $r8 = self::$FAILED;
        if (!$silence) {$this->fail(36);}
        break;
      }
    }
    if ($r7===self::$FAILED) {
      $this->currPos = $p5;
      $r3 = self::$FAILED;
      goto seq_1;
    }
    // free $r8
    $r3 = true;
    seq_1:
    if ($r3!==self::$FAILED) {
      $r3 = substr($this->input, $p4, $this->currPos - $p4);
      goto choice_1;
    } else {
      $r3 = self::$FAILED;
    }
    // free $p5
    // free $p4
    $p4 = $this->currPos;
    // start seq_2
    $p5 = $this->currPos;
    if (!(/*no_rbrace*/($boolParams & 0x1) !== 0)) {
      $r8 = false;
    } else {
      $r8 = self::$FAILED;
      $r3 = self::$FAILED;
      goto seq_2;
    }
    $r9 = self::$FAILED;
    for (;;) {
      if (strcspn($this->input, "'\"\\`\$ \x09\x0b\x0d\x0c\x0a&|;<>()", $this->currPos, 1) !== 0) {
        $r10 = self::consumeChar($this->input, $this->currPos);
        $r9 = true;
      } else {
        $r10 = self::$FAILED;
        if (!$silence) {$this->fail(37);}
        break;
      }
    }
    if ($r9===self::$FAILED) {
      $this->currPos = $p5;
      $r3 = self::$FAILED;
      goto seq_2;
    }
    // free $r10
    $r3 = true;
    seq_2:
    if ($r3!==self::$FAILED) {
      $r3 = substr($this->input, $p4, $this->currPos - $p4);
    } else {
      $r3 = self::$FAILED;
    }
    // free $p5
    // free $p4
    choice_1:
    // plain <- $r3
    $r1 = $r3;
    if ($r1!==self::$FAILED) {
      $this->savedPos = $p2;
      $r1 = $this->a55($r3);
    }
    $cached = ['nextPos' => $this->currPos, 'result' => $r1];
  
    $this->cache[$key] = $cached;
    return $r1;
  }
  private function discardElse($silence) {
    $key = json_encode([317, $this->currPos]);
    $cached = $this->cache[$key] ?? null;
      if ($cached) {
        $this->currPos = $cached['nextPos'];
  
        return $cached['result'];
      }
  
    // start seq_1
    $p1 = $this->currPos;
    if ($this->currPos >= $this->inputLength ? false : substr_compare($this->input, "else", $this->currPos, 4, false) === 0) {
      $r3 = "else";
      $this->currPos += 4;
    } else {
      if (!$silence) {$this->fail(38);}
      $r3 = self::$FAILED;
      $r2 = self::$FAILED;
      goto seq_1;
    }
    $r4 = $this->discardDELIM($silence);
    if ($r4===self::$FAILED) {
      $this->currPos = $p1;
      $r2 = self::$FAILED;
      goto seq_1;
    }
    $r2 = true;
    seq_1:
    // free $r2,$p1
    $cached = ['nextPos' => $this->currPos, 'result' => $r2];
  
    $this->cache[$key] = $cached;
    return $r2;
  }
  private function discardElif($silence) {
    $key = json_encode([319, $this->currPos]);
    $cached = $this->cache[$key] ?? null;
      if ($cached) {
        $this->currPos = $cached['nextPos'];
  
        return $cached['result'];
      }
  
    // start seq_1
    $p1 = $this->currPos;
    if ($this->currPos >= $this->inputLength ? false : substr_compare($this->input, "elif", $this->currPos, 4, false) === 0) {
      $r3 = "elif";
      $this->currPos += 4;
    } else {
      if (!$silence) {$this->fail(39);}
      $r3 = self::$FAILED;
      $r2 = self::$FAILED;
      goto seq_1;
    }
    $r4 = $this->discardDELIM($silence);
    if ($r4===self::$FAILED) {
      $this->currPos = $p1;
      $r2 = self::$FAILED;
      goto seq_1;
    }
    $r2 = true;
    seq_1:
    // free $r2,$p1
    $cached = ['nextPos' => $this->currPos, 'result' => $r2];
  
    $this->cache[$key] = $cached;
    return $r2;
  }
  private function discardDo($silence) {
    $key = json_encode([323, $this->currPos]);
    $cached = $this->cache[$key] ?? null;
      if ($cached) {
        $this->currPos = $cached['nextPos'];
  
        return $cached['result'];
      }
  
    // start seq_1
    $p1 = $this->currPos;
    if ($this->currPos >= $this->inputLength ? false : substr_compare($this->input, "do", $this->currPos, 2, false) === 0) {
      $r3 = "do";
      $this->currPos += 2;
    } else {
      if (!$silence) {$this->fail(40);}
      $r3 = self::$FAILED;
      $r2 = self::$FAILED;
      goto seq_1;
    }
    $r4 = $this->discardDELIM($silence);
    if ($r4===self::$FAILED) {
      $this->currPos = $p1;
      $r2 = self::$FAILED;
      goto seq_1;
    }
    $r2 = true;
    seq_1:
    // free $r2,$p1
    $cached = ['nextPos' => $this->currPos, 'result' => $r2];
  
    $this->cache[$key] = $cached;
    return $r2;
  }
  private function discardDone($silence) {
    $key = json_encode([325, $this->currPos]);
    $cached = $this->cache[$key] ?? null;
      if ($cached) {
        $this->currPos = $cached['nextPos'];
  
        return $cached['result'];
      }
  
    // start seq_1
    $p1 = $this->currPos;
    if ($this->currPos >= $this->inputLength ? false : substr_compare($this->input, "done", $this->currPos, 4, false) === 0) {
      $r3 = "done";
      $this->currPos += 4;
    } else {
      if (!$silence) {$this->fail(41);}
      $r3 = self::$FAILED;
      $r2 = self::$FAILED;
      goto seq_1;
    }
    $r4 = $this->discardDELIM($silence);
    if ($r4===self::$FAILED) {
      $this->currPos = $p1;
      $r2 = self::$FAILED;
      goto seq_1;
    }
    $r2 = true;
    seq_1:
    // free $r2,$p1
    $cached = ['nextPos' => $this->currPos, 'result' => $r2];
  
    $this->cache[$key] = $cached;
    return $r2;
  }
  private function discardBang($silence) {
    $key = json_encode([341, $this->currPos]);
    $cached = $this->cache[$key] ?? null;
      if ($cached) {
        $this->currPos = $cached['nextPos'];
  
        return $cached['result'];
      }
  
    // start seq_1
    $p1 = $this->currPos;
    if (($this->input[$this->currPos] ?? null) === "!") {
      $this->currPos++;
      $r3 = "!";
    } else {
      if (!$silence) {$this->fail(9);}
      $r3 = self::$FAILED;
      $r2 = self::$FAILED;
      goto seq_1;
    }
    $r4 = $this->discardDELIM($silence);
    if ($r4===self::$FAILED) {
      $this->currPos = $p1;
      $r2 = self::$FAILED;
      goto seq_1;
    }
    $r2 = true;
    seq_1:
    // free $r2,$p1
    $cached = ['nextPos' => $this->currPos, 'result' => $r2];
  
    $this->cache[$key] = $cached;
    return $r2;
  }
  private function discardIn($silence) {
    $key = json_encode([343, $this->currPos]);
    $cached = $this->cache[$key] ?? null;
      if ($cached) {
        $this->currPos = $cached['nextPos'];
  
        return $cached['result'];
      }
  
    // start seq_1
    $p1 = $this->currPos;
    if ($this->currPos >= $this->inputLength ? false : substr_compare($this->input, "in", $this->currPos, 2, false) === 0) {
      $r3 = "in";
      $this->currPos += 2;
    } else {
      if (!$silence) {$this->fail(42);}
      $r3 = self::$FAILED;
      $r2 = self::$FAILED;
      goto seq_1;
    }
    $r4 = $this->discardDELIM($silence);
    if ($r4===self::$FAILED) {
      $this->currPos = $p1;
      $r2 = self::$FAILED;
      goto seq_1;
    }
    $r2 = true;
    seq_1:
    // free $r2,$p1
    $cached = ['nextPos' => $this->currPos, 'result' => $r2];
  
    $this->cache[$key] = $cached;
    return $r2;
  }
  private function discardDELIM($silence) {
    $key = json_encode([363, $this->currPos]);
    $cached = $this->cache[$key] ?? null;
      if ($cached) {
        $this->currPos = $cached['nextPos'];
  
        return $cached['result'];
      }
  
    // start choice_1
    $r1 = self::$FAILED;
    for (;;) {
      if (strspn($this->input, " \x09\x0b\x0d\x0c", $this->currPos, 1) !== 0) {
        $r2 = $this->input[$this->currPos++];
        $r1 = true;
      } else {
        $r2 = self::$FAILED;
        if (!$silence) {$this->fail(2);}
        break;
      }
    }
    if ($r1!==self::$FAILED) {
      goto choice_1;
    }
    // free $r2
    $p3 = $this->currPos;
    if ($this->currPos < $this->inputLength) {
      $r1 = self::consumeChar($this->input, $this->currPos);;
    } else {
      $r1 = self::$FAILED;
    }
    if ($r1 === self::$FAILED) {
      $r1 = false;
      goto choice_1;
    } else {
      $r1 = self::$FAILED;
      $this->currPos = $p3;
    }
    // free $p3
    $p3 = $this->currPos;
    if (($this->input[$this->currPos] ?? null) === "\x0a") {
      $this->currPos++;
      $r1 = "\x0a";
      $r1 = false;
      $this->currPos = $p3;
    } else {
      $r1 = self::$FAILED;
    }
    // free $p3
    choice_1:
    $cached = ['nextPos' => $this->currPos, 'result' => $r1];
  
    $this->cache[$key] = $cached;
    return $r1;
  }
  private function discardseparator($silence) {
    $key = json_encode([289, $this->currPos]);
    $cached = $this->cache[$key] ?? null;
      if ($cached) {
        $this->currPos = $cached['nextPos'];
  
        return $cached['result'];
      }
  
    // start choice_1
    // start seq_1
    $p2 = $this->currPos;
    $r3 = $this->discardseparator_op($silence);
    if ($r3===self::$FAILED) {
      $r1 = self::$FAILED;
      goto seq_1;
    }
    $r4 = $this->discardlinebreak($silence);
    if ($r4===self::$FAILED) {
      $this->currPos = $p2;
      $r1 = self::$FAILED;
      goto seq_1;
    }
    $r1 = true;
    seq_1:
    if ($r1!==self::$FAILED) {
      goto choice_1;
    }
    // free $p2
    $r1 = $this->discardnewline_list($silence);
    choice_1:
    $cached = ['nextPos' => $this->currPos, 'result' => $r1];
  
    $this->cache[$key] = $cached;
    return $r1;
  }
  private function parsecase_item($silence, $boolParams) {
    $key = json_encode([244, $this->currPos, $boolParams & 0x1]);
    $cached = $this->cache[$key] ?? null;
      if ($cached) {
        $this->currPos = $cached['nextPos'];
  
        return $cached['result'];
      }
  
    // start choice_1
    $p2 = $this->currPos;
    // start seq_1
    $p3 = $this->currPos;
    $r4 = $this->discardLPAREN($silence);
    if ($r4===self::$FAILED) {
      $r4 = null;
    }
    $r5 = $this->parsepattern($silence, $boolParams);
    // pattern <- $r5
    if ($r5===self::$FAILED) {
      $this->currPos = $p3;
      $r1 = self::$FAILED;
      goto seq_1;
    }
    $r6 = $this->discardRPAREN($silence);
    if ($r6===self::$FAILED) {
      $this->currPos = $p3;
      $r1 = self::$FAILED;
      goto seq_1;
    }
    $r7 = $this->parsecompound_list($silence);
    // list <- $r7
    if ($r7===self::$FAILED) {
      $this->currPos = $p3;
      $r1 = self::$FAILED;
      goto seq_1;
    }
    $r8 = $this->discardDSEMI($silence);
    if ($r8===self::$FAILED) {
      $this->currPos = $p3;
      $r1 = self::$FAILED;
      goto seq_1;
    }
    $r9 = $this->discardlinebreak($silence);
    if ($r9===self::$FAILED) {
      $this->currPos = $p3;
      $r1 = self::$FAILED;
      goto seq_1;
    }
    $r1 = true;
    seq_1:
    if ($r1!==self::$FAILED) {
      $this->savedPos = $p2;
      $r1 = $this->a56($r5, $r7);
      goto choice_1;
    }
    // free $p3
    $p3 = $this->currPos;
    // start seq_2
    $p10 = $this->currPos;
    $r11 = $this->discardLPAREN($silence);
    if ($r11===self::$FAILED) {
      $r11 = null;
    }
    $r12 = $this->parsepattern($silence, $boolParams);
    // pattern <- $r12
    if ($r12===self::$FAILED) {
      $this->currPos = $p10;
      $r1 = self::$FAILED;
      goto seq_2;
    }
    $r13 = $this->discardRPAREN($silence);
    if ($r13===self::$FAILED) {
      $this->currPos = $p10;
      $r1 = self::$FAILED;
      goto seq_2;
    }
    $r14 = $this->discardlinebreak($silence);
    if ($r14===self::$FAILED) {
      $this->currPos = $p10;
      $r1 = self::$FAILED;
      goto seq_2;
    }
    $r15 = $this->discardDSEMI($silence);
    if ($r15===self::$FAILED) {
      $this->currPos = $p10;
      $r1 = self::$FAILED;
      goto seq_2;
    }
    $r16 = $this->discardlinebreak($silence);
    if ($r16===self::$FAILED) {
      $this->currPos = $p10;
      $r1 = self::$FAILED;
      goto seq_2;
    }
    $r1 = true;
    seq_2:
    if ($r1!==self::$FAILED) {
      $this->savedPos = $p3;
      $r1 = $this->a57($r12);
    }
    // free $p10
    choice_1:
    $cached = ['nextPos' => $this->currPos, 'result' => $r1];
  
    $this->cache[$key] = $cached;
    return $r1;
  }
  private function parsecase_item_ns($silence, $boolParams) {
    $key = json_encode([242, $this->currPos, $boolParams & 0x1]);
    $cached = $this->cache[$key] ?? null;
      if ($cached) {
        $this->currPos = $cached['nextPos'];
  
        return $cached['result'];
      }
  
    // start choice_1
    $p2 = $this->currPos;
    // start seq_1
    $p3 = $this->currPos;
    $r4 = $this->discardLPAREN($silence);
    if ($r4===self::$FAILED) {
      $r4 = null;
    }
    $r5 = $this->parsepattern($silence, $boolParams);
    // pattern <- $r5
    if ($r5===self::$FAILED) {
      $this->currPos = $p3;
      $r1 = self::$FAILED;
      goto seq_1;
    }
    $r6 = $this->discardRPAREN($silence);
    if ($r6===self::$FAILED) {
      $this->currPos = $p3;
      $r1 = self::$FAILED;
      goto seq_1;
    }
    $r7 = $this->parsecompound_list($silence);
    // list <- $r7
    if ($r7===self::$FAILED) {
      $this->currPos = $p3;
      $r1 = self::$FAILED;
      goto seq_1;
    }
    $r1 = true;
    seq_1:
    if ($r1!==self::$FAILED) {
      $this->savedPos = $p2;
      $r1 = $this->a56($r5, $r7);
      goto choice_1;
    }
    // free $p3
    $p3 = $this->currPos;
    // start seq_2
    $p8 = $this->currPos;
    $r9 = $this->discardLPAREN($silence);
    if ($r9===self::$FAILED) {
      $r9 = null;
    }
    $r10 = $this->parsepattern($silence, $boolParams);
    // pattern <- $r10
    if ($r10===self::$FAILED) {
      $this->currPos = $p8;
      $r1 = self::$FAILED;
      goto seq_2;
    }
    $r11 = $this->discardRPAREN($silence);
    if ($r11===self::$FAILED) {
      $this->currPos = $p8;
      $r1 = self::$FAILED;
      goto seq_2;
    }
    $r12 = $this->discardlinebreak($silence);
    if ($r12===self::$FAILED) {
      $this->currPos = $p8;
      $r1 = self::$FAILED;
      goto seq_2;
    }
    $r1 = true;
    seq_2:
    if ($r1!==self::$FAILED) {
      $this->savedPos = $p3;
      $r1 = $this->a58($r10);
    }
    // free $p8
    choice_1:
    $cached = ['nextPos' => $this->currPos, 'result' => $r1];
  
    $this->cache[$key] = $cached;
    return $r1;
  }
  private function discardLESSAND($silence) {
    $key = json_encode([303, $this->currPos]);
    $cached = $this->cache[$key] ?? null;
      if ($cached) {
        $this->currPos = $cached['nextPos'];
  
        return $cached['result'];
      }
  
    // start seq_1
    $p1 = $this->currPos;
    if ($this->currPos >= $this->inputLength ? false : substr_compare($this->input, "<&", $this->currPos, 2, false) === 0) {
      $r3 = "<&";
      $this->currPos += 2;
    } else {
      if (!$silence) {$this->fail(43);}
      $r3 = self::$FAILED;
      $r2 = self::$FAILED;
      goto seq_1;
    }
    $r4 = $this->discardOWS($silence);
    if ($r4===self::$FAILED) {
      $this->currPos = $p1;
      $r2 = self::$FAILED;
      goto seq_1;
    }
    $r2 = true;
    seq_1:
    // free $r2,$p1
    $cached = ['nextPos' => $this->currPos, 'result' => $r2];
  
    $this->cache[$key] = $cached;
    return $r2;
  }
  private function discardLESSGREAT($silence) {
    $key = json_encode([307, $this->currPos]);
    $cached = $this->cache[$key] ?? null;
      if ($cached) {
        $this->currPos = $cached['nextPos'];
  
        return $cached['result'];
      }
  
    // start seq_1
    $p1 = $this->currPos;
    if ($this->currPos >= $this->inputLength ? false : substr_compare($this->input, "<>", $this->currPos, 2, false) === 0) {
      $r3 = "<>";
      $this->currPos += 2;
    } else {
      if (!$silence) {$this->fail(44);}
      $r3 = self::$FAILED;
      $r2 = self::$FAILED;
      goto seq_1;
    }
    $r4 = $this->discardOWS($silence);
    if ($r4===self::$FAILED) {
      $this->currPos = $p1;
      $r2 = self::$FAILED;
      goto seq_1;
    }
    $r2 = true;
    seq_1:
    // free $r2,$p1
    $cached = ['nextPos' => $this->currPos, 'result' => $r2];
  
    $this->cache[$key] = $cached;
    return $r2;
  }
  private function discardLESS($silence) {
    $key = json_encode([351, $this->currPos]);
    $cached = $this->cache[$key] ?? null;
      if ($cached) {
        $this->currPos = $cached['nextPos'];
  
        return $cached['result'];
      }
  
    // start seq_1
    $p1 = $this->currPos;
    $p3 = $this->currPos;
    $r4 = $this->discardDLESS(true);
    if ($r4 === self::$FAILED) {
      $r4 = false;
    } else {
      $r4 = self::$FAILED;
      $this->currPos = $p3;
      $r2 = self::$FAILED;
      goto seq_1;
    }
    // free $p3
    $p3 = $this->currPos;
    $r5 = $this->discardLESSAND(true);
    if ($r5 === self::$FAILED) {
      $r5 = false;
    } else {
      $r5 = self::$FAILED;
      $this->currPos = $p3;
      $this->currPos = $p1;
      $r2 = self::$FAILED;
      goto seq_1;
    }
    // free $p3
    $p3 = $this->currPos;
    $r6 = $this->discardLESSGREAT(true);
    if ($r6 === self::$FAILED) {
      $r6 = false;
    } else {
      $r6 = self::$FAILED;
      $this->currPos = $p3;
      $this->currPos = $p1;
      $r2 = self::$FAILED;
      goto seq_1;
    }
    // free $p3
    $p3 = $this->currPos;
    $r7 = $this->discardDLESSDASH(true);
    if ($r7 === self::$FAILED) {
      $r7 = false;
    } else {
      $r7 = self::$FAILED;
      $this->currPos = $p3;
      $this->currPos = $p1;
      $r2 = self::$FAILED;
      goto seq_1;
    }
    // free $p3
    if (($this->input[$this->currPos] ?? null) === "<") {
      $this->currPos++;
      $r8 = "<";
    } else {
      if (!$silence) {$this->fail(45);}
      $r8 = self::$FAILED;
      $this->currPos = $p1;
      $r2 = self::$FAILED;
      goto seq_1;
    }
    $r9 = $this->discardOWS($silence);
    if ($r9===self::$FAILED) {
      $this->currPos = $p1;
      $r2 = self::$FAILED;
      goto seq_1;
    }
    $r2 = true;
    seq_1:
    // free $r2,$p1
    $cached = ['nextPos' => $this->currPos, 'result' => $r2];
  
    $this->cache[$key] = $cached;
    return $r2;
  }
  private function discardGREATAND($silence) {
    $key = json_encode([305, $this->currPos]);
    $cached = $this->cache[$key] ?? null;
      if ($cached) {
        $this->currPos = $cached['nextPos'];
  
        return $cached['result'];
      }
  
    // start seq_1
    $p1 = $this->currPos;
    if ($this->currPos >= $this->inputLength ? false : substr_compare($this->input, ">&", $this->currPos, 2, false) === 0) {
      $r3 = ">&";
      $this->currPos += 2;
    } else {
      if (!$silence) {$this->fail(46);}
      $r3 = self::$FAILED;
      $r2 = self::$FAILED;
      goto seq_1;
    }
    $r4 = $this->discardOWS($silence);
    if ($r4===self::$FAILED) {
      $this->currPos = $p1;
      $r2 = self::$FAILED;
      goto seq_1;
    }
    $r2 = true;
    seq_1:
    // free $r2,$p1
    $cached = ['nextPos' => $this->currPos, 'result' => $r2];
  
    $this->cache[$key] = $cached;
    return $r2;
  }
  private function discardDGREAT($silence) {
    $key = json_encode([301, $this->currPos]);
    $cached = $this->cache[$key] ?? null;
      if ($cached) {
        $this->currPos = $cached['nextPos'];
  
        return $cached['result'];
      }
  
    // start seq_1
    $p1 = $this->currPos;
    if ($this->currPos >= $this->inputLength ? false : substr_compare($this->input, ">>", $this->currPos, 2, false) === 0) {
      $r3 = ">>";
      $this->currPos += 2;
    } else {
      if (!$silence) {$this->fail(47);}
      $r3 = self::$FAILED;
      $r2 = self::$FAILED;
      goto seq_1;
    }
    $r4 = $this->discardOWS($silence);
    if ($r4===self::$FAILED) {
      $this->currPos = $p1;
      $r2 = self::$FAILED;
      goto seq_1;
    }
    $r2 = true;
    seq_1:
    // free $r2,$p1
    $cached = ['nextPos' => $this->currPos, 'result' => $r2];
  
    $this->cache[$key] = $cached;
    return $r2;
  }
  private function discardCLOBBER($silence) {
    $key = json_encode([311, $this->currPos]);
    $cached = $this->cache[$key] ?? null;
      if ($cached) {
        $this->currPos = $cached['nextPos'];
  
        return $cached['result'];
      }
  
    // start seq_1
    $p1 = $this->currPos;
    if ($this->currPos >= $this->inputLength ? false : substr_compare($this->input, ">|", $this->currPos, 2, false) === 0) {
      $r3 = ">|";
      $this->currPos += 2;
    } else {
      if (!$silence) {$this->fail(48);}
      $r3 = self::$FAILED;
      $r2 = self::$FAILED;
      goto seq_1;
    }
    $r4 = $this->discardOWS($silence);
    if ($r4===self::$FAILED) {
      $this->currPos = $p1;
      $r2 = self::$FAILED;
      goto seq_1;
    }
    $r2 = true;
    seq_1:
    // free $r2,$p1
    $cached = ['nextPos' => $this->currPos, 'result' => $r2];
  
    $this->cache[$key] = $cached;
    return $r2;
  }
  private function discardGREAT($silence) {
    $key = json_encode([353, $this->currPos]);
    $cached = $this->cache[$key] ?? null;
      if ($cached) {
        $this->currPos = $cached['nextPos'];
  
        return $cached['result'];
      }
  
    // start seq_1
    $p1 = $this->currPos;
    $p3 = $this->currPos;
    $r4 = $this->discardDGREAT(true);
    if ($r4 === self::$FAILED) {
      $r4 = false;
    } else {
      $r4 = self::$FAILED;
      $this->currPos = $p3;
      $r2 = self::$FAILED;
      goto seq_1;
    }
    // free $p3
    $p3 = $this->currPos;
    $r5 = $this->discardGREATAND(true);
    if ($r5 === self::$FAILED) {
      $r5 = false;
    } else {
      $r5 = self::$FAILED;
      $this->currPos = $p3;
      $this->currPos = $p1;
      $r2 = self::$FAILED;
      goto seq_1;
    }
    // free $p3
    $p3 = $this->currPos;
    $r6 = $this->discardCLOBBER(true);
    if ($r6 === self::$FAILED) {
      $r6 = false;
    } else {
      $r6 = self::$FAILED;
      $this->currPos = $p3;
      $this->currPos = $p1;
      $r2 = self::$FAILED;
      goto seq_1;
    }
    // free $p3
    if (($this->input[$this->currPos] ?? null) === ">") {
      $this->currPos++;
      $r7 = ">";
    } else {
      if (!$silence) {$this->fail(49);}
      $r7 = self::$FAILED;
      $this->currPos = $p1;
      $r2 = self::$FAILED;
      goto seq_1;
    }
    $r8 = $this->discardOWS($silence);
    if ($r8===self::$FAILED) {
      $this->currPos = $p1;
      $r2 = self::$FAILED;
      goto seq_1;
    }
    $r2 = true;
    seq_1:
    // free $r2,$p1
    $cached = ['nextPos' => $this->currPos, 'result' => $r2];
  
    $this->cache[$key] = $cached;
    return $r2;
  }
  private function discardDLESSDASH($silence) {
    $key = json_encode([309, $this->currPos]);
    $cached = $this->cache[$key] ?? null;
      if ($cached) {
        $this->currPos = $cached['nextPos'];
  
        return $cached['result'];
      }
  
    // start seq_1
    $p1 = $this->currPos;
    if ($this->currPos >= $this->inputLength ? false : substr_compare($this->input, "<<-", $this->currPos, 3, false) === 0) {
      $r3 = "<<-";
      $this->currPos += 3;
    } else {
      if (!$silence) {$this->fail(50);}
      $r3 = self::$FAILED;
      $r2 = self::$FAILED;
      goto seq_1;
    }
    $r4 = $this->discardOWS($silence);
    if ($r4===self::$FAILED) {
      $this->currPos = $p1;
      $r2 = self::$FAILED;
      goto seq_1;
    }
    $r2 = true;
    seq_1:
    // free $r2,$p1
    $cached = ['nextPos' => $this->currPos, 'result' => $r2];
  
    $this->cache[$key] = $cached;
    return $r2;
  }
  private function discardDLESS($silence) {
    $key = json_encode([299, $this->currPos]);
    $cached = $this->cache[$key] ?? null;
      if ($cached) {
        $this->currPos = $cached['nextPos'];
  
        return $cached['result'];
      }
  
    // start seq_1
    $p1 = $this->currPos;
    if ($this->currPos >= $this->inputLength ? false : substr_compare($this->input, "<<", $this->currPos, 2, false) === 0) {
      $r3 = "<<";
      $this->currPos += 2;
    } else {
      if (!$silence) {$this->fail(51);}
      $r3 = self::$FAILED;
      $r2 = self::$FAILED;
      goto seq_1;
    }
    $r4 = $this->discardOWS($silence);
    if ($r4===self::$FAILED) {
      $this->currPos = $p1;
      $r2 = self::$FAILED;
      goto seq_1;
    }
    $r2 = true;
    seq_1:
    // free $r2,$p1
    $cached = ['nextPos' => $this->currPos, 'result' => $r2];
  
    $this->cache[$key] = $cached;
    return $r2;
  }
  private function parsehere_end($silence, $boolParams) {
    $key = json_encode([280, $this->currPos, $boolParams & 0x1]);
    $cached = $this->cache[$key] ?? null;
      if ($cached) {
        $this->currPos = $cached['nextPos'];
  
        return $cached['result'];
      }
  
    $p1 = $this->currPos;
    $r2 = $this->discardWORD($silence, $boolParams);
    if ($r2!==self::$FAILED) {
      $r2 = substr($this->input, $p1, $this->currPos - $p1);
    } else {
      $r2 = self::$FAILED;
    }
    // free $p1
    $cached = ['nextPos' => $this->currPos, 'result' => $r2];
  
    $this->cache[$key] = $cached;
    return $r2;
  }
  private function parsedquoted_escape($silence) {
    $key = json_encode([372, $this->currPos]);
    $cached = $this->cache[$key] ?? null;
      if ($cached) {
        $this->currPos = $cached['nextPos'];
  
        return $cached['result'];
      }
  
    $p2 = $this->currPos;
    // start seq_1
    $p3 = $this->currPos;
    if (($this->input[$this->currPos] ?? null) === "\\") {
      $this->currPos++;
      $r4 = "\\";
    } else {
      if (!$silence) {$this->fail(31);}
      $r4 = self::$FAILED;
      $r1 = self::$FAILED;
      goto seq_1;
    }
    // contents <- $r5
    if (strspn($this->input, "\$`\"\\\x0a", $this->currPos, 1) !== 0) {
      $r5 = $this->input[$this->currPos++];
    } else {
      $r5 = self::$FAILED;
      if (!$silence) {$this->fail(52);}
      $this->currPos = $p3;
      $r1 = self::$FAILED;
      goto seq_1;
    }
    $r1 = true;
    seq_1:
    if ($r1!==self::$FAILED) {
      $this->savedPos = $p2;
      $r1 = $this->a59($r5);
    }
    // free $p3
    $cached = ['nextPos' => $this->currPos, 'result' => $r1];
  
    $this->cache[$key] = $cached;
    return $r1;
  }
  private function parsebackquoted_escape($silence) {
    $key = json_encode([378, $this->currPos]);
    $cached = $this->cache[$key] ?? null;
      if ($cached) {
        $this->currPos = $cached['nextPos'];
  
        return $cached['result'];
      }
  
    $p2 = $this->currPos;
    // start seq_1
    $p3 = $this->currPos;
    if (($this->input[$this->currPos] ?? null) === "\\") {
      $this->currPos++;
      $r4 = "\\";
    } else {
      if (!$silence) {$this->fail(31);}
      $r4 = self::$FAILED;
      $r1 = self::$FAILED;
      goto seq_1;
    }
    $r5 = $this->input[$this->currPos] ?? '';
    // contents <- $r5
    if ($r5 === "\$" || $r5 === "\\") {
      $this->currPos++;
    } else {
      $r5 = self::$FAILED;
      if (!$silence) {$this->fail(53);}
      $this->currPos = $p3;
      $r1 = self::$FAILED;
      goto seq_1;
    }
    $r1 = true;
    seq_1:
    if ($r1!==self::$FAILED) {
      $this->savedPos = $p2;
      $r1 = $this->a60($r5);
    }
    // free $p3
    $cached = ['nextPos' => $this->currPos, 'result' => $r1];
  
    $this->cache[$key] = $cached;
    return $r1;
  }
  private function parsedouble_backquote_expansion($silence) {
    $key = json_encode([380, $this->currPos]);
    $cached = $this->cache[$key] ?? null;
      if ($cached) {
        $this->currPos = $cached['nextPos'];
  
        return $cached['result'];
      }
  
    $p2 = $this->currPos;
    // start seq_1
    $p3 = $this->currPos;
    if ($this->currPos >= $this->inputLength ? false : substr_compare($this->input, "\\`", $this->currPos, 2, false) === 0) {
      $r4 = "\\`";
      $this->currPos += 2;
    } else {
      if (!$silence) {$this->fail(54);}
      $r4 = self::$FAILED;
      $r1 = self::$FAILED;
      goto seq_1;
    }
    // start choice_1
    $r5 = $this->parsebackquoted_escape($silence);
    if ($r5!==self::$FAILED) {
      goto choice_1;
    }
    $r5 = $this->parsedollar_expansion($silence);
    if ($r5!==self::$FAILED) {
      goto choice_1;
    }
    if (($this->input[$this->currPos] ?? null) === "\$") {
      $this->currPos++;
      $r5 = "\$";
      goto choice_1;
    } else {
      if (!$silence) {$this->fail(34);}
      $r5 = self::$FAILED;
    }
    // start seq_2
    $p6 = $this->currPos;
    if (($this->input[$this->currPos] ?? null) === "\\") {
      $this->currPos++;
      $r7 = "\\";
    } else {
      if (!$silence) {$this->fail(31);}
      $r7 = self::$FAILED;
      $r5 = self::$FAILED;
      goto seq_2;
    }
    $p8 = $this->currPos;
    if (($this->input[$this->currPos] ?? null) === "`") {
      $this->currPos++;
      $r9 = "`";
    } else {
      $r9 = self::$FAILED;
    }
    if ($r9 === self::$FAILED) {
      $r9 = false;
    } else {
      $r9 = self::$FAILED;
      $this->currPos = $p8;
      $this->currPos = $p6;
      $r5 = self::$FAILED;
      goto seq_2;
    }
    // free $p8
    $r5 = [$r7,$r9];
    seq_2:
    if ($r5!==self::$FAILED) {
      goto choice_1;
    }
    // free $p6
    $p6 = $this->currPos;
    $r5 = self::$FAILED;
    for (;;) {
      if (strcspn($this->input, "`\$\\", $this->currPos, 1) !== 0) {
        $r10 = self::consumeChar($this->input, $this->currPos);
        $r5 = true;
      } else {
        $r10 = self::$FAILED;
        if (!$silence) {$this->fail(35);}
        break;
      }
    }
    if ($r5!==self::$FAILED) {
      $r5 = substr($this->input, $p6, $this->currPos - $p6);
    } else {
      $r5 = self::$FAILED;
    }
    // free $r10
    // free $p6
    choice_1:
    // parts <- $r5
    if ($r5===self::$FAILED) {
      $this->currPos = $p3;
      $r1 = self::$FAILED;
      goto seq_1;
    }
    if ($this->currPos >= $this->inputLength ? false : substr_compare($this->input, "\\`", $this->currPos, 2, false) === 0) {
      $r10 = "\\`";
      $this->currPos += 2;
    } else {
      if (!$silence) {$this->fail(54);}
      $r10 = self::$FAILED;
      $this->currPos = $p3;
      $r1 = self::$FAILED;
      goto seq_1;
    }
    $r1 = true;
    seq_1:
    if ($r1!==self::$FAILED) {
      $this->savedPos = $p2;
      $r1 = $this->a61($r5);
    }
    // free $p3
    $cached = ['nextPos' => $this->currPos, 'result' => $r1];
  
    $this->cache[$key] = $cached;
    return $r1;
  }
  private function parsespecial_parameter($silence) {
    $key = json_encode([384, $this->currPos]);
    $cached = $this->cache[$key] ?? null;
      if ($cached) {
        $this->currPos = $cached['nextPos'];
  
        return $cached['result'];
      }
  
    $p2 = $this->currPos;
    // contents <- $r3
    if (strspn($this->input, "@*#?-\$!0", $this->currPos, 1) !== 0) {
      $r3 = $this->input[$this->currPos++];
    } else {
      $r3 = self::$FAILED;
      if (!$silence) {$this->fail(55);}
    }
    $r1 = $r3;
    if ($r1!==self::$FAILED) {
      $this->savedPos = $p2;
      $r1 = $this->a62($r3);
    }
    $cached = ['nextPos' => $this->currPos, 'result' => $r1];
  
    $this->cache[$key] = $cached;
    return $r1;
  }
  private function parseshort_positional_parameter($silence) {
    $key = json_encode([386, $this->currPos]);
    $cached = $this->cache[$key] ?? null;
      if ($cached) {
        $this->currPos = $cached['nextPos'];
  
        return $cached['result'];
      }
  
    $p2 = $this->currPos;
    $r3 = $this->input[$this->currPos] ?? '';
    // contents <- $r3
    if (preg_match("/^[1-9]/", $r3)) {
      $this->currPos++;
    } else {
      $r3 = self::$FAILED;
      if (!$silence) {$this->fail(56);}
    }
    $r1 = $r3;
    if ($r1!==self::$FAILED) {
      $this->savedPos = $p2;
      $r1 = $this->a63($r3);
    }
    $cached = ['nextPos' => $this->currPos, 'result' => $r1];
  
    $this->cache[$key] = $cached;
    return $r1;
  }
  private function parsebrace_expansion($silence) {
    $key = json_encode([388, $this->currPos]);
    $cached = $this->cache[$key] ?? null;
      if ($cached) {
        $this->currPos = $cached['nextPos'];
  
        return $cached['result'];
      }
  
    $p2 = $this->currPos;
    // start seq_1
    $p3 = $this->currPos;
    if (($this->input[$this->currPos] ?? null) === "{") {
      $this->currPos++;
      $r4 = "{";
    } else {
      if (!$silence) {$this->fail(17);}
      $r4 = self::$FAILED;
      $r1 = self::$FAILED;
      goto seq_1;
    }
    // start choice_1
    $r5 = $this->parsebinary_expansion($silence);
    if ($r5!==self::$FAILED) {
      goto choice_1;
    }
    $r5 = $this->parsestring_length($silence);
    if ($r5!==self::$FAILED) {
      goto choice_1;
    }
    $r5 = $this->parsebraced_parameter_expansion($silence);
    choice_1:
    // contents <- $r5
    if ($r5===self::$FAILED) {
      $this->currPos = $p3;
      $r1 = self::$FAILED;
      goto seq_1;
    }
    if (($this->input[$this->currPos] ?? null) === "}") {
      $this->currPos++;
      $r6 = "}";
    } else {
      if (!$silence) {$this->fail(18);}
      $r6 = self::$FAILED;
      $this->currPos = $p3;
      $r1 = self::$FAILED;
      goto seq_1;
    }
    $r1 = true;
    seq_1:
    if ($r1!==self::$FAILED) {
      $this->savedPos = $p2;
      $r1 = $this->a54($r5);
    }
    // free $p3
    $cached = ['nextPos' => $this->currPos, 'result' => $r1];
  
    $this->cache[$key] = $cached;
    return $r1;
  }
  private function parsearithmetic_expansion($silence) {
    $key = json_encode([394, $this->currPos]);
    $cached = $this->cache[$key] ?? null;
      if ($cached) {
        $this->currPos = $cached['nextPos'];
  
        return $cached['result'];
      }
  
    $p2 = $this->currPos;
    // start seq_1
    $p3 = $this->currPos;
    if ($this->currPos >= $this->inputLength ? false : substr_compare($this->input, "((", $this->currPos, 2, false) === 0) {
      $r4 = "((";
      $this->currPos += 2;
    } else {
      if (!$silence) {$this->fail(57);}
      $r4 = self::$FAILED;
      $r1 = self::$FAILED;
      goto seq_1;
    }
    $r5 = $this->discardOWS($silence);
    if ($r5===self::$FAILED) {
      $this->currPos = $p3;
      $r1 = self::$FAILED;
      goto seq_1;
    }
    $r6 = [];
    for (;;) {
      $r7 = $this->parseWORD($silence, 0x0);
      if ($r7!==self::$FAILED) {
        $r6[] = $r7;
      } else {
        break;
      }
    }
    if (count($r6) === 0) {
      $r6 = self::$FAILED;
    }
    // words <- $r6
    if ($r6===self::$FAILED) {
      $this->currPos = $p3;
      $r1 = self::$FAILED;
      goto seq_1;
    }
    // free $r7
    if ($this->currPos >= $this->inputLength ? false : substr_compare($this->input, "))", $this->currPos, 2, false) === 0) {
      $r7 = "))";
      $this->currPos += 2;
    } else {
      if (!$silence) {$this->fail(58);}
      $r7 = self::$FAILED;
      $this->currPos = $p3;
      $r1 = self::$FAILED;
      goto seq_1;
    }
    $r1 = true;
    seq_1:
    if ($r1!==self::$FAILED) {
      $this->savedPos = $p2;
      $r1 = $this->a64($r6);
    }
    // free $p3
    $cached = ['nextPos' => $this->currPos, 'result' => $r1];
  
    $this->cache[$key] = $cached;
    return $r1;
  }
  private function parsecommand_expansion($silence) {
    $key = json_encode([396, $this->currPos]);
    $cached = $this->cache[$key] ?? null;
      if ($cached) {
        $this->currPos = $cached['nextPos'];
  
        return $cached['result'];
      }
  
    $p2 = $this->currPos;
    // start seq_1
    $p3 = $this->currPos;
    if (($this->input[$this->currPos] ?? null) === "(") {
      $this->currPos++;
      $r4 = "(";
    } else {
      if (!$silence) {$this->fail(14);}
      $r4 = self::$FAILED;
      $r1 = self::$FAILED;
      goto seq_1;
    }
    $r5 = $this->parsecomplete_command($silence, 0x0);
    // command <- $r5
    if ($r5===self::$FAILED) {
      $this->currPos = $p3;
      $r1 = self::$FAILED;
      goto seq_1;
    }
    if (($this->input[$this->currPos] ?? null) === ")") {
      $this->currPos++;
      $r6 = ")";
    } else {
      if (!$silence) {$this->fail(15);}
      $r6 = self::$FAILED;
      $this->currPos = $p3;
      $r1 = self::$FAILED;
      goto seq_1;
    }
    $r1 = true;
    seq_1:
    if ($r1!==self::$FAILED) {
      $this->savedPos = $p2;
      $r1 = $this->a65($r5);
    }
    // free $p3
    $cached = ['nextPos' => $this->currPos, 'result' => $r1];
  
    $this->cache[$key] = $cached;
    return $r1;
  }
  private function parsenamed_parameter($silence) {
    $key = json_encode([404, $this->currPos]);
    $cached = $this->cache[$key] ?? null;
      if ($cached) {
        $this->currPos = $cached['nextPos'];
  
        return $cached['result'];
      }
  
    $p2 = $this->currPos;
    $r3 = $this->parseNAME($silence);
    // name <- $r3
    $r1 = $r3;
    if ($r1!==self::$FAILED) {
      $this->savedPos = $p2;
      $r1 = $this->a66($r3);
    }
    $cached = ['nextPos' => $this->currPos, 'result' => $r1];
  
    $this->cache[$key] = $cached;
    return $r1;
  }
  private function parsepattern($silence, $boolParams) {
    $key = json_encode([246, $this->currPos, $boolParams & 0x1]);
    $cached = $this->cache[$key] ?? null;
      if ($cached) {
        $this->currPos = $cached['nextPos'];
  
        return $cached['result'];
      }
  
    $p2 = $this->currPos;
    // start seq_1
    $p3 = $this->currPos;
    $r4 = $this->parseWORD($silence, $boolParams);
    // first <- $r4
    if ($r4===self::$FAILED) {
      $r1 = self::$FAILED;
      goto seq_1;
    }
    $r5 = [];
    for (;;) {
      // start seq_2
      $p7 = $this->currPos;
      $r8 = $this->parsePIPE($silence);
      if ($r8===self::$FAILED) {
        $r6 = self::$FAILED;
        goto seq_2;
      }
      $r9 = $this->parseWORD($silence, $boolParams);
      if ($r9===self::$FAILED) {
        $this->currPos = $p7;
        $r6 = self::$FAILED;
        goto seq_2;
      }
      $r6 = [$r8,$r9];
      seq_2:
      if ($r6!==self::$FAILED) {
        $r5[] = $r6;
      } else {
        break;
      }
      // free $p7
    }
    // rest <- $r5
    // free $r6
    $r1 = true;
    seq_1:
    if ($r1!==self::$FAILED) {
      $this->savedPos = $p2;
      $r1 = $this->a67($r4, $r5);
    }
    // free $p3
    $cached = ['nextPos' => $this->currPos, 'result' => $r1];
  
    $this->cache[$key] = $cached;
    return $r1;
  }
  private function discardWORD($silence, $boolParams) {
    $key = json_encode([365, $this->currPos, $boolParams & 0x1]);
    $cached = $this->cache[$key] ?? null;
      if ($cached) {
        $this->currPos = $cached['nextPos'];
  
        return $cached['result'];
      }
  
    $p2 = $this->currPos;
    // start seq_1
    $p3 = $this->currPos;
    $r4 = [];
    for (;;) {
      $r5 = $this->parseword_part($silence, $boolParams);
      if ($r5!==self::$FAILED) {
        $r4[] = $r5;
      } else {
        break;
      }
    }
    if (count($r4) === 0) {
      $r4 = self::$FAILED;
    }
    // parts <- $r4
    if ($r4===self::$FAILED) {
      $r1 = self::$FAILED;
      goto seq_1;
    }
    // free $r5
    $r5 = $this->discardOWS($silence);
    if ($r5===self::$FAILED) {
      $this->currPos = $p3;
      $r1 = self::$FAILED;
      goto seq_1;
    }
    $r1 = true;
    seq_1:
    if ($r1!==self::$FAILED) {
      $this->savedPos = $p2;
      $r1 = $this->a19($r4);
    }
    // free $p3
    $cached = ['nextPos' => $this->currPos, 'result' => $r1];
  
    $this->cache[$key] = $cached;
    return $r1;
  }
  private function parsebinary_expansion($silence) {
    $key = json_encode([390, $this->currPos]);
    $cached = $this->cache[$key] ?? null;
      if ($cached) {
        $this->currPos = $cached['nextPos'];
  
        return $cached['result'];
      }
  
    $p2 = $this->currPos;
    // start seq_1
    $p3 = $this->currPos;
    $r4 = $this->parseparameter($silence);
    // parameter <- $r4
    if ($r4===self::$FAILED) {
      $r1 = self::$FAILED;
      goto seq_1;
    }
    // start choice_1
    $p6 = $this->currPos;
    // start seq_2
    $p7 = $this->currPos;
    if (($this->input[$this->currPos] ?? null) === ":") {
      $this->currPos++;
      $r8 = ":";
    } else {
      if (!$silence) {$this->fail(59);}
      $r8 = self::$FAILED;
      $r5 = self::$FAILED;
      goto seq_2;
    }
    if (strspn($this->input, "-=?+", $this->currPos, 1) !== 0) {
      $r9 = $this->input[$this->currPos++];
    } else {
      $r9 = self::$FAILED;
      if (!$silence) {$this->fail(60);}
      $this->currPos = $p7;
      $r5 = self::$FAILED;
      goto seq_2;
    }
    $r5 = true;
    seq_2:
    if ($r5!==self::$FAILED) {
      $r5 = substr($this->input, $p6, $this->currPos - $p6);
      goto choice_1;
    } else {
      $r5 = self::$FAILED;
    }
    // free $p7
    // free $p6
    $p6 = $this->currPos;
    // start choice_2
    if ($this->currPos >= $this->inputLength ? false : substr_compare($this->input, "%%", $this->currPos, 2, false) === 0) {
      $r5 = "%%";
      $this->currPos += 2;
      goto choice_2;
    } else {
      if (!$silence) {$this->fail(61);}
      $r5 = self::$FAILED;
    }
    if ($this->currPos >= $this->inputLength ? false : substr_compare($this->input, "##", $this->currPos, 2, false) === 0) {
      $r5 = "##";
      $this->currPos += 2;
      goto choice_2;
    } else {
      if (!$silence) {$this->fail(62);}
      $r5 = self::$FAILED;
    }
    if (strspn($this->input, "%#-=?+", $this->currPos, 1) !== 0) {
      $r5 = $this->input[$this->currPos++];
    } else {
      $r5 = self::$FAILED;
      if (!$silence) {$this->fail(63);}
    }
    choice_2:
    if ($r5!==self::$FAILED) {
      $r5 = substr($this->input, $p6, $this->currPos - $p6);
    } else {
      $r5 = self::$FAILED;
    }
    // free $p6
    choice_1:
    // operator <- $r5
    if ($r5===self::$FAILED) {
      $this->currPos = $p3;
      $r1 = self::$FAILED;
      goto seq_1;
    }
    $r10 = $this->discardOWS($silence);
    if ($r10===self::$FAILED) {
      $this->currPos = $p3;
      $r1 = self::$FAILED;
      goto seq_1;
    }
    $r11 = $this->parseWORD($silence, 0x1);
    if ($r11===self::$FAILED) {
      $r11 = null;
    }
    // word <- $r11
    $r1 = true;
    seq_1:
    if ($r1!==self::$FAILED) {
      $this->savedPos = $p2;
      $r1 = $this->a68($r4, $r5, $r11);
    }
    // free $p3
    $cached = ['nextPos' => $this->currPos, 'result' => $r1];
  
    $this->cache[$key] = $cached;
    return $r1;
  }
  private function parsestring_length($silence) {
    $key = json_encode([392, $this->currPos]);
    $cached = $this->cache[$key] ?? null;
      if ($cached) {
        $this->currPos = $cached['nextPos'];
  
        return $cached['result'];
      }
  
    $p2 = $this->currPos;
    // start seq_1
    $p3 = $this->currPos;
    if (($this->input[$this->currPos] ?? null) === "#") {
      $this->currPos++;
      $r4 = "#";
    } else {
      if (!$silence) {$this->fail(3);}
      $r4 = self::$FAILED;
      $r1 = self::$FAILED;
      goto seq_1;
    }
    $r5 = $this->parseparameter($silence);
    // parameter <- $r5
    if ($r5===self::$FAILED) {
      $this->currPos = $p3;
      $r1 = self::$FAILED;
      goto seq_1;
    }
    $r1 = true;
    seq_1:
    if ($r1!==self::$FAILED) {
      $this->savedPos = $p2;
      $r1 = $this->a69($r5);
    }
    // free $p3
    $cached = ['nextPos' => $this->currPos, 'result' => $r1];
  
    $this->cache[$key] = $cached;
    return $r1;
  }
  private function parsebraced_parameter_expansion($silence) {
    $key = json_encode([398, $this->currPos]);
    $cached = $this->cache[$key] ?? null;
      if ($cached) {
        $this->currPos = $cached['nextPos'];
  
        return $cached['result'];
      }
  
    $p2 = $this->currPos;
    $r3 = $this->parseparameter($silence);
    // parameter <- $r3
    $r1 = $r3;
    if ($r1!==self::$FAILED) {
      $this->savedPos = $p2;
      $r1 = $this->a70($r3);
    }
    $cached = ['nextPos' => $this->currPos, 'result' => $r1];
  
    $this->cache[$key] = $cached;
    return $r1;
  }
  private function parsePIPE($silence) {
    $key = json_encode([354, $this->currPos]);
    $cached = $this->cache[$key] ?? null;
      if ($cached) {
        $this->currPos = $cached['nextPos'];
  
        return $cached['result'];
      }
  
    // start seq_1
    $p1 = $this->currPos;
    $p3 = $this->currPos;
    $r4 = $this->discardOR_IF(true);
    if ($r4 === self::$FAILED) {
      $r4 = false;
    } else {
      $r4 = self::$FAILED;
      $this->currPos = $p3;
      $r2 = self::$FAILED;
      goto seq_1;
    }
    // free $p3
    if (($this->input[$this->currPos] ?? null) === "|") {
      $this->currPos++;
      $r5 = "|";
    } else {
      if (!$silence) {$this->fail(11);}
      $r5 = self::$FAILED;
      $this->currPos = $p1;
      $r2 = self::$FAILED;
      goto seq_1;
    }
    $r6 = $this->parseOWS($silence);
    if ($r6===self::$FAILED) {
      $this->currPos = $p1;
      $r2 = self::$FAILED;
      goto seq_1;
    }
    $r2 = [$r4,$r5,$r6];
    seq_1:
    // free $r2,$p1
    $cached = ['nextPos' => $this->currPos, 'result' => $r2];
  
    $this->cache[$key] = $cached;
    return $r2;
  }
  private function parseparameter($silence) {
    $key = json_encode([400, $this->currPos]);
    $cached = $this->cache[$key] ?? null;
      if ($cached) {
        $this->currPos = $cached['nextPos'];
  
        return $cached['result'];
      }
  
    // start choice_1
    $r1 = $this->parsespecial_parameter($silence);
    if ($r1!==self::$FAILED) {
      goto choice_1;
    }
    $r1 = $this->parselong_positional_parameter($silence);
    if ($r1!==self::$FAILED) {
      goto choice_1;
    }
    $r1 = $this->parsenamed_parameter($silence);
    choice_1:
    $cached = ['nextPos' => $this->currPos, 'result' => $r1];
  
    $this->cache[$key] = $cached;
    return $r1;
  }
  private function parseOWS($silence) {
    $key = json_encode([360, $this->currPos]);
    $cached = $this->cache[$key] ?? null;
      if ($cached) {
        $this->currPos = $cached['nextPos'];
  
        return $cached['result'];
      }
  
    // start seq_1
    $p1 = $this->currPos;
    $r3 = [];
    for (;;) {
      if (strspn($this->input, " \x09\x0b\x0d\x0c", $this->currPos, 1) !== 0) {
        $r4 = $this->input[$this->currPos++];
        $r3[] = $r4;
      } else {
        $r4 = self::$FAILED;
        if (!$silence) {$this->fail(2);}
        break;
      }
    }
    // free $r4
    // start seq_2
    $p5 = $this->currPos;
    if (($this->input[$this->currPos] ?? null) === "#") {
      $this->currPos++;
      $r6 = "#";
    } else {
      if (!$silence) {$this->fail(3);}
      $r6 = self::$FAILED;
      $r4 = self::$FAILED;
      goto seq_2;
    }
    $r7 = [];
    for (;;) {
      $r8 = self::charAt($this->input, $this->currPos);
      if ($r8 !== '' && !($r8 === "\x0a")) {
        $this->currPos += strlen($r8);
        $r7[] = $r8;
      } else {
        $r8 = self::$FAILED;
        if (!$silence) {$this->fail(4);}
        break;
      }
    }
    // free $r8
    $r4 = [$r6,$r7];
    seq_2:
    if ($r4===self::$FAILED) {
      $r4 = null;
    }
    // free $p5
    $r2 = [$r3,$r4];
    seq_1:
    // free $r2,$p1
    $cached = ['nextPos' => $this->currPos, 'result' => $r2];
  
    $this->cache[$key] = $cached;
    return $r2;
  }
  private function parselong_positional_parameter($silence) {
    $key = json_encode([402, $this->currPos]);
    $cached = $this->cache[$key] ?? null;
      if ($cached) {
        $this->currPos = $cached['nextPos'];
  
        return $cached['result'];
      }
  
    $p2 = $this->currPos;
    $p4 = $this->currPos;
    $r3 = self::$FAILED;
    for (;;) {
      $r5 = $this->input[$this->currPos] ?? '';
      if (preg_match("/^[0-9]/", $r5)) {
        $this->currPos++;
        $r3 = true;
      } else {
        $r5 = self::$FAILED;
        if (!$silence) {$this->fail(27);}
        break;
      }
    }
    // parameter <- $r3
    if ($r3!==self::$FAILED) {
      $r3 = substr($this->input, $p4, $this->currPos - $p4);
    } else {
      $r3 = self::$FAILED;
    }
    // free $r5
    // free $p4
    $r1 = $r3;
    if ($r1!==self::$FAILED) {
      $this->savedPos = $p2;
      $r1 = $this->a71($r3);
    }
    $cached = ['nextPos' => $this->currPos, 'result' => $r1];
  
    $this->cache[$key] = $cached;
    return $r1;
  }

  public function parse($input, $options = []) {
    $this->initInternal($input, $options);
    $startRule = $options['startRule'] ?? '(DEFAULT)';
    $result = null;

    if (!empty($options['stream'])) {
      switch ($startRule) {
        
        default:
          throw new \Wikimedia\WikiPEG\InternalError("Can't stream rule $startRule.");
      }
    } else {
      switch ($startRule) {
        case '(DEFAULT)':
        case "program":
          $result = $this->parseprogram(false);
          break;
        default:
          throw new \Wikimedia\WikiPEG\InternalError("Can't start parsing from rule $startRule.");
      }
    }

    if ($result !== self::$FAILED && $this->currPos === $this->inputLength) {
      return $result;
    } else {
      if ($result !== self::$FAILED && $this->currPos < $this->inputLength) {
        $this->fail(0);
      }
      throw $this->buildParseException();
    }
  }
}

