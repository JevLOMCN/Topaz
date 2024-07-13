<?php
/**
 * Copyright 2011 Paul Copperman <paul.copperman@gmail.com>
 * Copyright 2018 Timo Tijhof
 * Copyright 2021 Roan Kattouw <roan.kattouw@gmail.com>
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 *
 * @file
 * @license Apache-2.0
 * @license MIT
 * @license GPL-2.0-or-later
 * @license LGPL-2.1-or-later
 */

namespace Wikimedia\Minify;

/**
 * JavaScript Minifier
 *
 * This class is meant to safely minify JavaScript code, while leaving syntactically correct
 * programs intact. Other libraries, such as JSMin require a certain coding style to work
 * correctly. OTOH, libraries like jsminplus, that do parse the code correctly are rather
 * slow, because they construct a complete parse tree before outputting the code minified.
 * So this class is meant to allow arbitrary (but syntactically correct) input, while being
 * fast enough to be used for on-the-fly minifying.
 *
 * This class was written with ECMA-262 7th Edition in mind ("ECMAScript 2016"). Parsing features
 * new to later editions of ECMAScript might not be supported. It's assumed that the input is
 * syntactically correct; if it's not, this class may not detect that, and may produce incorrect
 * output.
 *
 * This class has limited support for 8.0 spec ("ECMAScript 2017"), specifically, the await
 * keyword and most kinds of async functions are implemented. Other new parsing features of ES2017
 * are not yet supported.
 *
 * See also:
 * - <https://262.ecma-international.org/8.0/>
 * - <https://262.ecma-international.org/7.0/>
 * - <https://262.ecma-international.org/6.0/>
 */
class JavaScriptMinifier {

	/* Parsing states.
	 * The state machine is necessary to decide whether to parse a slash as division
	 * operator or as regexp literal, and to know where semicolon insertion is possible.
	 * States are generally named after the next expected item. We only distinguish states when the
	 * distinction is relevant for our purpose. The meaning of these states is documented
	 * in $model below.
	 *
	 * Negative numbers are used to indicate that the state is inside a generator function,
	 * which changes the behavior of 'yield'
	 */
	private const STATEMENT                     = 1;
	private const CONDITION                     = 2;
	private const FUNC                          = 3;
	private const GENFUNC                       = 4;
	private const PROPERTY_ASSIGNMENT           = 5;
	private const EXPRESSION                    = 6;
	private const EXPRESSION_NO_NL              = 7;
	private const EXPRESSION_OP                 = 8;
	private const EXPRESSION_DOT                = 9;
	private const EXPRESSION_END                = 10;
	private const EXPRESSION_ARROWFUNC          = 11;
	private const EXPRESSION_TERNARY            = 12;
	private const EXPRESSION_TERNARY_OP         = 13;
	private const EXPRESSION_TERNARY_DOT        = 14;
	private const EXPRESSION_TERNARY_ARROWFUNC  = 15;
	private const PAREN_EXPRESSION              = 16;
	private const PAREN_EXPRESSION_OP           = 17;
	private const PAREN_EXPRESSION_DOT          = 18;
	private const PAREN_EXPRESSION_ARROWFUNC    = 19;
	private const PROPERTY_EXPRESSION           = 20;
	private const PROPERTY_EXPRESSION_OP        = 21;
	private const PROPERTY_EXPRESSION_DOT       = 22;
	private const PROPERTY_EXPRESSION_ARROWFUNC = 23;
	private const CLASS_DEF                     = 24;
	private const IMPORT_EXPORT                 = 25;
	private const TEMPLATE_STRING_HEAD          = 26;
	private const TEMPLATE_STRING_TAIL          = 27;
	private const PAREN_EXPRESSION_OP_NO_NL     = 28;

	/* Token types */
	private const TYPE_UN_OP         = 101; // unary operators
	private const TYPE_INCR_OP       = 102; // ++ and --
	private const TYPE_BIN_OP        = 103; // binary operators (except .)
	private const TYPE_ADD_OP        = 104; // + and - which can be either unary or binary ops
	private const TYPE_DOT           = 105; // .
	private const TYPE_HOOK          = 106; // ?
	private const TYPE_COLON         = 107; // :
	private const TYPE_COMMA         = 108; // ,
	private const TYPE_SEMICOLON     = 109; // ;
	private const TYPE_BRACE_OPEN    = 110; // {
	private const TYPE_BRACE_CLOSE   = 111; // }
	private const TYPE_PAREN_OPEN    = 112; // ( and [
	private const TYPE_PAREN_CLOSE   = 113; // ) and ]
	private const TYPE_ARROW         = 114; // =>
	private const TYPE_RETURN        = 115; // keywords: break, continue, return, throw
	private const TYPE_IF            = 116; // keywords: catch, for, with, switch, while, if
	private const TYPE_DO            = 117; // keywords: case, finally, else, do, try
	private const TYPE_VAR           = 118; // keywords: var, let, const
	private const TYPE_YIELD         = 119; // keywords: yield
	private const TYPE_FUNC          = 120; // keywords: function
	private const TYPE_CLASS         = 121; // keywords: class
	private const TYPE_LITERAL       = 122; // all literals, identifiers, unrecognised tokens, and other keywords
	private const TYPE_SPECIAL       = 123; // For special treatment of tokens that usually mean something else
	private const TYPE_ASYNC         = 124; // keywords: async
	private const TYPE_AWAIT         = 125; // keywords: await

	private const ACTION_GOTO = 201; // Go to another state
	private const ACTION_PUSH = 202; // Push a state to the stack
	private const ACTION_POP = 203; // Pop the state from the top of the stack, and go to that state

	// Limit to avoid excessive memory usage
	private const STACK_LIMIT = 1000;

	// Length of the longest token in $tokenTypes made of punctuation characters,
	// as defined in $opChars. Update this if you add longer tokens to $tokenTypes.
	//
	// Currently the longest punctuation token is `>>>=`, which is 4 characters.
	private const LONGEST_PUNCTUATION_TOKEN = 4;

	/**
	 * @var int $maxLineLength
	 *
	 * Maximum line length
	 *
	 * This is not a strict maximum, but a guideline. Longer lines will be
	 * produced when literals (e.g. quoted strings) longer than this are
	 * encountered, or when required to guard against semicolon insertion.
	 *
	 * This is a private member (instead of constant) to allow tests to
	 * set it to 1, to verify ASI and line-breaking behaviour.
	 */
	private static $maxLineLength = 1000;

	private static $expandedStates = false;

	/**
	 * @var array $opChars
	 *
	 * Characters which can be combined without whitespace between them.
	 */
	private static $opChars = [
		// ECMAScript 6.0 § 11.7 Punctuators
		// Unlike the spec, these are individual symbols, not sequences.
		'{' => true,
		'}' => true,
		'(' => true,
		')' => true,
		'[' => true,
		']' => true,
		// Dots have a special case after $dotlessNum which require whitespace
		'.' => true,
		';' => true,
		',' => true,
		'<' => true,
		'>' => true,
		'=' => true,
		'!' => true,
		'+' => true,
		'-' => true,
		'*' => true,
		'%' => true,
		'&' => true,
		'|' => true,
		'^' => true,
		'~' => true,
		'?' => true,
		':' => true,
		'/' => true,
		// ECMAScript 6.0 § 11.8.4 String Literals
		'"' => true,
		"'" => true,
		// ECMAScript 6.0 § 11.8.6 Template Literal Lexical Components
		'`' => true,
	];

	/**
	 * @var array $tokenTypes
	 *
	 * Tokens and their types.
	 */
	private static $tokenTypes = [
		// ECMAScript 6.0 § 12.5 Unary Operators
		// UnaryExpression includes PostfixExpression, which includes 'new'.
		'new'        => self::TYPE_UN_OP,
		'delete'     => self::TYPE_UN_OP,
		'void'       => self::TYPE_UN_OP,
		'typeof'     => self::TYPE_UN_OP,
		'~'          => self::TYPE_UN_OP,
		'!'          => self::TYPE_UN_OP,
		// ECMAScript 8.0 § 14.6 AwaitExpression
		//
		//    await UnaryExpression
		//
		'await'      => self::TYPE_AWAIT,
		// ECMAScript 6.0 § 12.2 Primary Expression, among others
		'...'        => self::TYPE_UN_OP,
		// ECMAScript 6.0 § 12.7 Additive Operators
		'++'         => self::TYPE_INCR_OP,
		'--'         => self::TYPE_INCR_OP,
		'+'          => self::TYPE_ADD_OP,
		'-'          => self::TYPE_ADD_OP,
		// ECMAScript 6.0 § 12.6 Multiplicative Operators
		'*'          => self::TYPE_BIN_OP,
		'/'          => self::TYPE_BIN_OP,
		'%'          => self::TYPE_BIN_OP,
		// ECMAScript 7.0 § 12.6 Exponentiation Operator
		'**'         => self::TYPE_BIN_OP,
		// ECMAScript 6.0 § 12.8 Bitwise Shift Operators
		'<<'         => self::TYPE_BIN_OP,
		'>>'         => self::TYPE_BIN_OP,
		'>>>'        => self::TYPE_BIN_OP,
		// ECMAScript 6.0 § 12.9 Relational Operators
		'<'          => self::TYPE_BIN_OP,
		'>'          => self::TYPE_BIN_OP,
		'<='         => self::TYPE_BIN_OP,
		'>='         => self::TYPE_BIN_OP,
		'instanceof' => self::TYPE_BIN_OP,
		'in'         => self::TYPE_BIN_OP,
		// ECMAScript 6.0 § 12.10 Equality Operators
		'=='         => self::TYPE_BIN_OP,
		'!='         => self::TYPE_BIN_OP,
		'==='        => self::TYPE_BIN_OP,
		'!=='        => self::TYPE_BIN_OP,
		// ECMAScript 6.0 § 12.11 Binary Bitwise Operators
		'&'          => self::TYPE_BIN_OP,
		'^'          => self::TYPE_BIN_OP,
		'|'          => self::TYPE_BIN_OP,
		// ECMAScript 6.0 § 12.12 Binary Logical Operators
		'&&'         => self::TYPE_BIN_OP,
		'||'         => self::TYPE_BIN_OP,
		// ECMAScript 6.0 § 12.13 Conditional Operator
		// Also known as ternary.
		'?'          => self::TYPE_HOOK,
		':'          => self::TYPE_COLON,
		// ECMAScript 6.0 § 12.14 Assignment Operators
		'='          => self::TYPE_BIN_OP,
		'*='         => self::TYPE_BIN_OP,
		'/='         => self::TYPE_BIN_OP,
		'%='         => self::TYPE_BIN_OP,
		'+='         => self::TYPE_BIN_OP,
		'-='         => self::TYPE_BIN_OP,
		'<<='        => self::TYPE_BIN_OP,
		'>>='        => self::TYPE_BIN_OP,
		'>>>='       => self::TYPE_BIN_OP,
		'&='         => self::TYPE_BIN_OP,
		'^='         => self::TYPE_BIN_OP,
		'|='         => self::TYPE_BIN_OP,
		// ECMAScript 6.0 § 12.15 Comma Operator
		','          => self::TYPE_COMMA,

		// The keywords that disallow LineTerminator before their
		// (sometimes optional) Expression or Identifier.
		//
		//    keyword ;
		//    keyword [no LineTerminator here] Identifier ;
		//    keyword [no LineTerminator here] Expression ;
		//
		// See also ECMAScript 6.0 § 11.9.1 Rules of Automatic Semicolon Insertion
		'continue'   => self::TYPE_RETURN,
		'break'      => self::TYPE_RETURN,
		'return'     => self::TYPE_RETURN,
		'throw'      => self::TYPE_RETURN,
		// yield is only a keyword inside generator functions, otherwise it's an identifier
		// This is handled with the negative states hack: if the state is negative, TYPE_YIELD
		// is treated as TYPE_RETURN, if it's positive it's treated as TYPE_LITERAL
		'yield'      => self::TYPE_YIELD,

		// The keywords require a parenthesised Expression or Identifier
		// before the next Statement.
		//
		//     keyword ( Expression ) Statement
		//     keyword ( Identifier ) Statement
		//
		// See also ECMAScript 6.0:
		// - § 13.6 The if Statement
		// - § 13.7 Iteration Statements (do, while, for)
		// - § 12.10 The with Statement
		// - § 12.11 The switch Statement
		// - § 12.13 The throw Statement
		'if'         => self::TYPE_IF,
		'catch'      => self::TYPE_IF,
		'while'      => self::TYPE_IF,
		'for'        => self::TYPE_IF,
		'switch'     => self::TYPE_IF,
		'with'       => self::TYPE_IF,

		// The keywords followed by a Statement, Expression, or Block.
		//
		//     else Statement
		//     do Statement
		//     case Expression
		//     try Block
		//     finally Block
		//
		// See also ECMAScript 6.0:
		// - § 13.6 The if Statement (else)
		// - § 13.7 Iteration Statements (do, while, for)
		// - § 13.12 The switch Statement (case)
		// - § 13.15 The try Statement
		'else'       => self::TYPE_DO,
		'do'         => self::TYPE_DO,
		'case'       => self::TYPE_DO,
		'try'        => self::TYPE_DO,
		'finally'    => self::TYPE_DO,

		// Keywords followed by a variable declaration
		// This is different from the group above, because a { begins
		// object destructuring, rather than a block
		'var'        => self::TYPE_VAR,
		'let'        => self::TYPE_VAR,
		'const'      => self::TYPE_VAR,

		// ECMAScript 6.0 § 14.1 Function Definitions
		'function'   => self::TYPE_FUNC,
		// ECMAScript 6.0 § 14.2 Arrow Function Definitions
		'=>'         => self::TYPE_ARROW,

		// Class declaration or expression:
		//     class Identifier { ClassBody }
		//     class { ClassBody }
		//     class Identifier extends Expression { ClassBody }
		//     class extends Expression { ClassBody }
		'class'      => self::TYPE_CLASS,

		// ECMAScript 6.0 § 12.3 Left-Hand-Side Expressions (MemberExpression)
		// A dot can also be part of a DecimalLiteral, but in that case we handle the entire
		// DecimalLiteral as one token. A separate '.' token is always part of a MemberExpression.
		'.'          => self::TYPE_DOT,

		// Can be one of:
		// - Block (ECMAScript 6.0 § 13.2 Block)
		// - ObjectLiteral (ECMAScript 6.0 § 12.2 Primary Expression)
		'{'          => self::TYPE_BRACE_OPEN,
		'}'          => self::TYPE_BRACE_CLOSE,

		// Can be one of:
		// - Parenthesised Identifier or Expression after a
		//   TYPE_IF or TYPE_FUNC keyword.
		// - PrimaryExpression (ECMAScript 6.0 § 12.2 Primary Expression)
		// - CallExpression (ECMAScript 6.0 § 12.3 Left-Hand-Side Expressions)
		// - Beginning of an ArrowFunction (ECMAScript 6.0 § 14.2 Arrow Function Definitions)
		'('          => self::TYPE_PAREN_OPEN,
		')'          => self::TYPE_PAREN_CLOSE,

		// Can be one of:
		// - ArrayLiteral (ECMAScript 6.0 § 12.2 Primary Expressions)
		// - ComputedPropertyName (ECMAScript 6.0 § 12.2.6 Object Initializer)
		'['          => self::TYPE_PAREN_OPEN,
		']'          => self::TYPE_PAREN_CLOSE,

		// Can be one of:
		// - End of any statement
		// - EmptyStatement (ECMAScript 6.0 § 13.4 Empty Statement)
		';'          => self::TYPE_SEMICOLON,

		// ECMAScript 8.0 § 14.6 Async Function Definitions
		// async [no LineTerminator here] function ...
		// async [no LineTerminator here] propertyName() ...
		'async'      => self::TYPE_ASYNC,
	];

	/**
	 * @var array $model
	 *
	 * The main table for the state machine. Defines the desired action for every state/token pair.
	 *
	 * The state pushed onto the stack by ACTION_PUSH will be returned to by ACTION_POP.
	 * A state/token pair may not specify both ACTION_POP and ACTION_GOTO. If that does happen,
	 * ACTION_POP takes precedence.
	 *
	 * This table is augmented by self::ensureExpandedStates().
	 */
	private static $model = [
		// Statement - This is the initial state.
		self::STATEMENT => [
			self::TYPE_UN_OP => [
				self::ACTION_GOTO => self::EXPRESSION,
			],
			self::TYPE_INCR_OP => [
				self::ACTION_GOTO => self::EXPRESSION,
			],
			self::TYPE_ADD_OP => [
				self::ACTION_GOTO => self::EXPRESSION,
			],
			self::TYPE_BRACE_OPEN => [
				// Use of '{' in statement context, creates a Block.
				self::ACTION_PUSH => self::STATEMENT,
			],
			self::TYPE_BRACE_CLOSE => [
				// Ends a Block
				self::ACTION_POP => true,
			],
			self::TYPE_PAREN_OPEN => [
				self::ACTION_PUSH => self::EXPRESSION_OP,
				self::ACTION_GOTO => self::PAREN_EXPRESSION,
			],
			self::TYPE_RETURN => [
				self::ACTION_GOTO => self::EXPRESSION_NO_NL,
			],
			self::TYPE_IF => [
				self::ACTION_GOTO => self::CONDITION,
			],
			self::TYPE_VAR => [
				self::ACTION_GOTO => self::EXPRESSION,
			],
			self::TYPE_FUNC => [
				self::ACTION_PUSH => self::STATEMENT,
				self::ACTION_GOTO => self::FUNC,
			],
			self::TYPE_CLASS => [
				self::ACTION_PUSH => self::STATEMENT,
				self::ACTION_GOTO => self::CLASS_DEF,
			],
			self::TYPE_SPECIAL => [
				'import' => [
					self::ACTION_GOTO => self::IMPORT_EXPORT,
				],
				'export' => [
					self::ACTION_GOTO => self::IMPORT_EXPORT,
				],
			],
			self::TYPE_LITERAL => [
				self::ACTION_GOTO => self::EXPRESSION_OP,
			],
			self::TYPE_ASYNC => [
				self::ACTION_GOTO => self::EXPRESSION_OP,
			],
			self::TYPE_AWAIT => [
				self::ACTION_GOTO => self::EXPRESSION,
			],
		],
		// The state after if/catch/while/for/switch/with
		// Waits for an expression in parentheses, then goes to STATEMENT
		self::CONDITION => [
			self::TYPE_PAREN_OPEN => [
				self::ACTION_PUSH => self::STATEMENT,
				self::ACTION_GOTO => self::PAREN_EXPRESSION,
			],
		],
		// The state after the function keyword. Waits for {, then goes to STATEMENT.
		// The function body's closing } will pop the stack, so the state to return to
		// after the function should be pushed to the stack first
		self::FUNC => [
			// Needed to prevent * in an expression in the argument list from improperly
			// triggering GENFUNC
			self::TYPE_PAREN_OPEN => [
				self::ACTION_PUSH => self::FUNC,
				self::ACTION_GOTO => self::PAREN_EXPRESSION,
			],
			self::TYPE_BRACE_OPEN => [
				self::ACTION_GOTO => self::STATEMENT,
			],
			self::TYPE_SPECIAL => [
				'*' => [
					self::ACTION_GOTO => self::GENFUNC,
				],
			],
		],
		// After function*. Waits for { , then goes to a generator function statement.
		self::GENFUNC => [
			self::TYPE_BRACE_OPEN => [
				// Note negative value: generator function states are negative
				self::ACTION_GOTO => -self::STATEMENT
			],
		],
		// Property assignment - This is an object literal declaration.
		// For example: `{ key: value, key2, [computedKey3]: value3, method4() { ... } }`
		self::PROPERTY_ASSIGNMENT => [
			// Note that keywords like if, class, var, delete, instanceof etc. can be used as keys,
			// and should be treated as literals here, as they are in EXPRESSION_DOT. In this state,
			// that is implicitly true because TYPE_LITERAL has no action, so it stays in this state.
			// If we later add a state transition for TYPE_LITERAL, that same transition should
			// also be applied to TYPE_RETURN, TYPE_IF, TYPE_DO, TYPE_VAR, TYPE_FUNC and TYPE_CLASS.
			self::TYPE_COLON => [
				self::ACTION_GOTO => self::PROPERTY_EXPRESSION,
			],
			// For {, which begins a method
			self::TYPE_BRACE_OPEN => [
				self::ACTION_PUSH => self::PROPERTY_ASSIGNMENT,
				// This is not flipped, see "Special cases" below
				self::ACTION_GOTO => self::STATEMENT,
			],
			self::TYPE_BRACE_CLOSE => [
				self::ACTION_POP => true,
			],
			// For [, which begins a computed key
			self::TYPE_PAREN_OPEN => [
				self::ACTION_PUSH => self::PROPERTY_ASSIGNMENT,
				self::ACTION_GOTO => self::PAREN_EXPRESSION,
			],
			self::TYPE_SPECIAL => [
				'*' => [
					self::ACTION_PUSH => self::PROPERTY_ASSIGNMENT,
					self::ACTION_GOTO => self::GENFUNC,
				],
			],
		],
		// Place in an expression where we expect an operand or a unary operator: the start
		// of an expression or after an operator. Note that unary operators (including INCR_OP
		// and ADD_OP) cause us to stay in this state, while operands take us to EXPRESSION_OP
		self::EXPRESSION => [
			self::TYPE_SEMICOLON => [
				self::ACTION_GOTO => self::STATEMENT,
			],
			self::TYPE_BRACE_OPEN => [
				self::ACTION_PUSH => self::EXPRESSION_OP,
				self::ACTION_GOTO => self::PROPERTY_ASSIGNMENT,
			],
			self::TYPE_BRACE_CLOSE => [
				self::ACTION_POP => true,
			],
			self::TYPE_PAREN_OPEN => [
				self::ACTION_PUSH => self::EXPRESSION_OP,
				self::ACTION_GOTO => self::PAREN_EXPRESSION,
			],
			self::TYPE_FUNC => [
				self::ACTION_PUSH => self::EXPRESSION_OP,
				self::ACTION_GOTO => self::FUNC,
			],
			self::TYPE_CLASS => [
				self::ACTION_PUSH => self::EXPRESSION_OP,
				self::ACTION_GOTO => self::CLASS_DEF,
			],
			self::TYPE_LITERAL => [
				self::ACTION_GOTO => self::EXPRESSION_OP,
			],
			self::TYPE_ASYNC => [
				self::ACTION_GOTO => self::EXPRESSION_OP,
			],
		],
		// An expression immediately after return/throw/break/continue, where a newline
		// is not allowed. This state is identical to EXPRESSION, except that semicolon
		// insertion can happen here, and we never stay here: in cases where EXPRESSION would
		// do nothing, we go to EXPRESSION.
		self::EXPRESSION_NO_NL => [
			self::TYPE_UN_OP => [
				self::ACTION_GOTO => self::EXPRESSION,
			],
			self::TYPE_INCR_OP => [
				self::ACTION_GOTO => self::EXPRESSION,
			],
			// BIN_OP seems impossible at the start of an expression, but it can happen in
			// yield *foo
			self::TYPE_BIN_OP => [
				self::ACTION_GOTO => self::EXPRESSION,
			],
			self::TYPE_ADD_OP => [
				self::ACTION_GOTO => self::EXPRESSION,
			],
			self::TYPE_SEMICOLON => [
				self::ACTION_GOTO => self::STATEMENT,
			],
			self::TYPE_BRACE_OPEN => [
				self::ACTION_PUSH => self::EXPRESSION_OP,
				self::ACTION_GOTO => self::PROPERTY_ASSIGNMENT,
			],
			self::TYPE_BRACE_CLOSE => [
				self::ACTION_POP => true,
			],
			self::TYPE_PAREN_OPEN => [
				self::ACTION_PUSH => self::EXPRESSION_OP,
				self::ACTION_GOTO => self::PAREN_EXPRESSION,
			],
			self::TYPE_FUNC => [
				self::ACTION_PUSH => self::EXPRESSION_OP,
				self::ACTION_GOTO => self::FUNC,
			],
			self::TYPE_CLASS => [
				self::ACTION_PUSH => self::EXPRESSION_OP,
				self::ACTION_GOTO => self::CLASS_DEF,
			],
			self::TYPE_LITERAL => [
				self::ACTION_GOTO => self::EXPRESSION_OP,
			],
			self::TYPE_ASYNC => [
				self::ACTION_GOTO => self::EXPRESSION_OP,
			],
			self::TYPE_AWAIT => [
				self::ACTION_GOTO => self::EXPRESSION,
			],
		],
		// Place in an expression after an operand, where we expect an operator
		self::EXPRESSION_OP => [
			self::TYPE_BIN_OP => [
				self::ACTION_GOTO => self::EXPRESSION,
			],
			self::TYPE_ADD_OP => [
				self::ACTION_GOTO => self::EXPRESSION,
			],
			self::TYPE_DOT => [
				self::ACTION_GOTO => self::EXPRESSION_DOT,
			],
			self::TYPE_HOOK => [
				self::ACTION_PUSH => self::EXPRESSION,
				self::ACTION_GOTO => self::EXPRESSION_TERNARY,
			],
			self::TYPE_COLON => [
				self::ACTION_GOTO => self::STATEMENT,
			],
			self::TYPE_COMMA => [
				self::ACTION_GOTO => self::EXPRESSION,
			],
			self::TYPE_SEMICOLON => [
				self::ACTION_GOTO => self::STATEMENT,
			],
			self::TYPE_ARROW => [
				self::ACTION_GOTO => self::EXPRESSION_ARROWFUNC,
			],
			self::TYPE_PAREN_OPEN => [
				self::ACTION_PUSH => self::EXPRESSION_OP,
				self::ACTION_GOTO => self::PAREN_EXPRESSION,
			],
			self::TYPE_BRACE_CLOSE => [
				self::ACTION_POP => true,
			],
			self::TYPE_FUNC => [
				self::ACTION_PUSH => self::EXPRESSION_OP,
				self::ACTION_GOTO => self::FUNC,
			],
		],
		// State after a dot (.). Like EXPRESSION, except that many keywords behave like literals
		// (e.g. class, if, else, var, function) because they're not valid as identifiers but are
		// valid as property names.
		self::EXPRESSION_DOT => [
			self::TYPE_LITERAL => [
				self::ACTION_GOTO => self::EXPRESSION_OP,
			],
			// The following are keywords behaving as literals
			self::TYPE_RETURN => [
				self::ACTION_GOTO => self::EXPRESSION_OP,
			],
			self::TYPE_IF => [
				self::ACTION_GOTO => self::EXPRESSION_OP,
			],
			self::TYPE_DO => [
				self::ACTION_GOTO => self::EXPRESSION_OP,
			],
			self::TYPE_VAR => [
				self::ACTION_GOTO => self::EXPRESSION_OP,
			],
			self::TYPE_FUNC => [
				self::ACTION_GOTO => self::EXPRESSION_OP,
			],
			self::TYPE_CLASS => [
				self::ACTION_GOTO => self::EXPRESSION_OP,
			],
			// We don't expect real unary/binary operators here, but some keywords
			// (new, delete, void, typeof, instanceof, in) are classified as such, and they can be
			// used as property names
			self::TYPE_UN_OP => [
				self::ACTION_GOTO => self::EXPRESSION_OP,
			],
			self::TYPE_BIN_OP => [
				self::ACTION_GOTO => self::EXPRESSION_OP,
			],
		],
		// State after the } closing an arrow function body: like STATEMENT except
		// that it has semicolon insertion, COMMA can continue the expression, and after
		// a function we go to STATEMENT instead of EXPRESSION_OP
		self::EXPRESSION_END => [
			self::TYPE_UN_OP => [
				self::ACTION_GOTO => self::EXPRESSION,
			],
			self::TYPE_INCR_OP => [
				self::ACTION_GOTO => self::EXPRESSION,
			],
			self::TYPE_ADD_OP => [
				self::ACTION_GOTO => self::EXPRESSION,
			],
			self::TYPE_COMMA => [
				self::ACTION_GOTO => self::EXPRESSION,
			],
			self::TYPE_SEMICOLON => [
				self::ACTION_GOTO => self::STATEMENT,
			],
			self::TYPE_BRACE_OPEN => [
				self::ACTION_PUSH => self::STATEMENT,
				self::ACTION_GOTO => self::STATEMENT,
			],
			self::TYPE_BRACE_CLOSE => [
				self::ACTION_POP => true,
			],
			self::TYPE_PAREN_OPEN => [
				self::ACTION_PUSH => self::EXPRESSION_OP,
				self::ACTION_GOTO => self::PAREN_EXPRESSION,
			],
			self::TYPE_RETURN => [
				self::ACTION_GOTO => self::EXPRESSION_NO_NL,
			],
			self::TYPE_IF => [
				self::ACTION_GOTO => self::CONDITION,
			],
			self::TYPE_VAR => [
				self::ACTION_GOTO => self::EXPRESSION,
			],
			self::TYPE_FUNC => [
				self::ACTION_PUSH => self::STATEMENT,
				self::ACTION_GOTO => self::FUNC,
			],
			self::TYPE_CLASS => [
				self::ACTION_PUSH => self::STATEMENT,
				self::ACTION_GOTO => self::CLASS_DEF,
			],
			self::TYPE_LITERAL => [
				self::ACTION_GOTO => self::EXPRESSION_OP,
			],
			self::TYPE_ASYNC => [
				self::ACTION_GOTO => self::EXPRESSION_OP,
			],
		],
		// State after =>. Like EXPRESSION, except that { begins an arrow function body
		// rather than an object literal.
		self::EXPRESSION_ARROWFUNC => [
			self::TYPE_UN_OP => [
				self::ACTION_GOTO => self::EXPRESSION,
			],
			self::TYPE_INCR_OP => [
				self::ACTION_GOTO => self::EXPRESSION,
			],
			self::TYPE_ADD_OP => [
				self::ACTION_GOTO => self::EXPRESSION,
			],
			self::TYPE_BRACE_OPEN => [
				self::ACTION_PUSH => self::EXPRESSION_END,
				self::ACTION_GOTO => self::STATEMENT,
			],
			self::TYPE_PAREN_OPEN => [
				self::ACTION_PUSH => self::EXPRESSION_OP,
				self::ACTION_GOTO => self::PAREN_EXPRESSION,
			],
			self::TYPE_FUNC => [
				self::ACTION_PUSH => self::EXPRESSION_OP,
				self::ACTION_GOTO => self::FUNC,
			],
			self::TYPE_CLASS => [
				self::ACTION_PUSH => self::EXPRESSION_OP,
				self::ACTION_GOTO => self::CLASS_DEF,
			],
			self::TYPE_LITERAL => [
				self::ACTION_GOTO => self::EXPRESSION_OP,
			],
		],
		// Expression after a ? . This differs from EXPRESSION because a : ends the ternary
		// rather than starting STATEMENT (outside a ternary, : comes after a goto label)
		// The actual rule for : ending the ternary is in EXPRESSION_TERNARY_OP.
		self::EXPRESSION_TERNARY => [
			self::TYPE_BRACE_OPEN => [
				self::ACTION_PUSH => self::EXPRESSION_TERNARY_OP,
				self::ACTION_GOTO => self::PROPERTY_ASSIGNMENT,
			],
			self::TYPE_PAREN_OPEN => [
				self::ACTION_PUSH => self::EXPRESSION_TERNARY_OP,
				self::ACTION_GOTO => self::PAREN_EXPRESSION,
			],
			self::TYPE_FUNC => [
				self::ACTION_PUSH => self::EXPRESSION_TERNARY_OP,
				self::ACTION_GOTO => self::FUNC,
			],
			self::TYPE_CLASS => [
				self::ACTION_PUSH => self::EXPRESSION_TERNARY_OP,
				self::ACTION_GOTO => self::CLASS_DEF,
			],
			self::TYPE_LITERAL => [
				self::ACTION_GOTO => self::EXPRESSION_TERNARY_OP,
			],
		],
		// Like EXPRESSION_OP, but for ternaries, see EXPRESSION_TERNARY
		self::EXPRESSION_TERNARY_OP => [
			self::TYPE_BIN_OP => [
				self::ACTION_GOTO => self::EXPRESSION_TERNARY,
			],
			self::TYPE_ADD_OP => [
				self::ACTION_GOTO => self::EXPRESSION_TERNARY,
			],
			self::TYPE_DOT => [
				self::ACTION_GOTO => self::EXPRESSION_TERNARY_DOT,
			],
			self::TYPE_HOOK => [
				self::ACTION_PUSH => self::EXPRESSION_TERNARY,
				self::ACTION_GOTO => self::EXPRESSION_TERNARY,
			],
			self::TYPE_COMMA => [
				self::ACTION_GOTO => self::EXPRESSION_TERNARY,
			],
			self::TYPE_ARROW => [
				self::ACTION_GOTO => self::EXPRESSION_TERNARY_ARROWFUNC,
			],
			self::TYPE_PAREN_OPEN => [
				self::ACTION_PUSH => self::EXPRESSION_TERNARY_OP,
				self::ACTION_GOTO => self::PAREN_EXPRESSION,
			],
			self::TYPE_COLON => [
				self::ACTION_POP => true,
			],
		],
		// Like EXPRESSION_DOT, but for ternaries, see EXPRESSION_TERNARY
		self::EXPRESSION_TERNARY_DOT => [
			self::TYPE_LITERAL => [
				self::ACTION_GOTO => self::EXPRESSION_TERNARY_OP,
			],
			// The following are keywords behaving as literals
			self::TYPE_RETURN => [
				self::ACTION_GOTO => self::EXPRESSION_TERNARY_OP,
			],
			self::TYPE_IF => [
				self::ACTION_GOTO => self::EXPRESSION_TERNARY_OP,
			],
			self::TYPE_DO => [
				self::ACTION_GOTO => self::EXPRESSION_TERNARY_OP,
			],
			self::TYPE_VAR => [
				self::ACTION_GOTO => self::EXPRESSION_TERNARY_OP,
			],
			self::TYPE_FUNC => [
				self::ACTION_GOTO => self::EXPRESSION_TERNARY_OP,
			],
			self::TYPE_CLASS => [
				self::ACTION_GOTO => self::EXPRESSION_TERNARY_OP,
			],
			// We don't expect real unary/binary operators here, but some keywords
			// (new, delete, void, typeof, instanceof, in) are classified as such, and they can be
			// used as property names
			self::TYPE_UN_OP => [
				self::ACTION_GOTO => self::EXPRESSION_TERNARY_OP,
			],
			self::TYPE_BIN_OP => [
				self::ACTION_GOTO => self::EXPRESSION_TERNARY_OP,
			],
		],
		// Like EXPRESSION_ARROWFUNC, but for ternaries, see EXPRESSION_TERNARY
		self::EXPRESSION_TERNARY_ARROWFUNC => [
			self::TYPE_UN_OP => [
				self::ACTION_GOTO => self::EXPRESSION_TERNARY,
			],
			self::TYPE_INCR_OP => [
				self::ACTION_GOTO => self::EXPRESSION_TERNARY,
			],
			self::TYPE_ADD_OP => [
				self::ACTION_GOTO => self::EXPRESSION_TERNARY,
			],
			self::TYPE_BRACE_OPEN => [
				self::ACTION_PUSH => self::EXPRESSION_TERNARY_OP,
				self::ACTION_GOTO => self::STATEMENT,
			],
			self::TYPE_PAREN_OPEN => [
				self::ACTION_PUSH => self::EXPRESSION_TERNARY_OP,
				self::ACTION_GOTO => self::PAREN_EXPRESSION,
			],
			self::TYPE_FUNC => [
				self::ACTION_PUSH => self::EXPRESSION_TERNARY_OP,
				self::ACTION_GOTO => self::FUNC,
			],
			self::TYPE_CLASS => [
				self::ACTION_PUSH => self::EXPRESSION_TERNARY_OP,
				self::ACTION_GOTO => self::CLASS_DEF,
			],
			self::TYPE_LITERAL => [
				self::ACTION_GOTO => self::EXPRESSION_TERNARY_OP,
			],
		],
		// Expression inside parentheses. Like EXPRESSION, except that ) ends this state
		// This differs from EXPRESSION because semicolon insertion can't happen here
		self::PAREN_EXPRESSION => [
			self::TYPE_BRACE_OPEN => [
				self::ACTION_PUSH => self::PAREN_EXPRESSION_OP,
				self::ACTION_GOTO => self::PROPERTY_ASSIGNMENT,
			],
			self::TYPE_PAREN_OPEN => [
				self::ACTION_PUSH => self::PAREN_EXPRESSION_OP,
				self::ACTION_GOTO => self::PAREN_EXPRESSION,
			],
			self::TYPE_PAREN_CLOSE => [
				self::ACTION_POP => true,
			],
			self::TYPE_FUNC => [
				self::ACTION_PUSH => self::PAREN_EXPRESSION_OP,
				self::ACTION_GOTO => self::FUNC,
			],
			self::TYPE_CLASS => [
				self::ACTION_PUSH => self::PAREN_EXPRESSION_OP,
				self::ACTION_GOTO => self::CLASS_DEF,
			],
			self::TYPE_LITERAL => [
				self::ACTION_GOTO => self::PAREN_EXPRESSION_OP,
			],
			self::TYPE_ASYNC => [
				self::ACTION_GOTO => self::PAREN_EXPRESSION_OP_NO_NL,
			],
		],
		// Like EXPRESSION_OP, but in parentheses, see PAREN_EXPRESSION
		self::PAREN_EXPRESSION_OP => [
			self::TYPE_BIN_OP => [
				self::ACTION_GOTO => self::PAREN_EXPRESSION,
			],
			self::TYPE_ADD_OP => [
				self::ACTION_GOTO => self::PAREN_EXPRESSION,
			],
			self::TYPE_DOT => [
				self::ACTION_GOTO => self::PAREN_EXPRESSION_DOT,
			],
			self::TYPE_HOOK => [
				self::ACTION_GOTO => self::PAREN_EXPRESSION,
			],
			self::TYPE_COLON => [
				self::ACTION_GOTO => self::PAREN_EXPRESSION,
			],
			self::TYPE_COMMA => [
				self::ACTION_GOTO => self::PAREN_EXPRESSION,
			],
			self::TYPE_SEMICOLON => [
				self::ACTION_GOTO => self::PAREN_EXPRESSION,
			],
			self::TYPE_ARROW => [
				self::ACTION_GOTO => self::PAREN_EXPRESSION_ARROWFUNC,
			],
			self::TYPE_PAREN_OPEN => [
				self::ACTION_PUSH => self::PAREN_EXPRESSION_OP,
				self::ACTION_GOTO => self::PAREN_EXPRESSION,
			],
			self::TYPE_PAREN_CLOSE => [
				self::ACTION_POP => true,
			],
		],
		// Like EXPRESSION_DOT, but in parentheses, see PAREN_EXPRESSION
		self::PAREN_EXPRESSION_DOT => [
			self::TYPE_LITERAL => [
				self::ACTION_GOTO => self::PAREN_EXPRESSION_OP,
			],
			// The following are keywords behaving as literals
			self::TYPE_RETURN => [
				self::ACTION_GOTO => self::PAREN_EXPRESSION_OP,
			],
			self::TYPE_IF => [
				self::ACTION_GOTO => self::PAREN_EXPRESSION_OP,
			],
			self::TYPE_DO => [
				self::ACTION_GOTO => self::PAREN_EXPRESSION_OP,
			],
			self::TYPE_VAR => [
				self::ACTION_GOTO => self::PAREN_EXPRESSION_OP,
			],
			self::TYPE_FUNC => [
				self::ACTION_GOTO => self::PAREN_EXPRESSION_OP,
			],
			self::TYPE_CLASS => [
				self::ACTION_GOTO => self::PAREN_EXPRESSION_OP,
			],
			// We don't expect real unary/binary operators here, but some keywords
			// (new, delete, void, typeof, instanceof, in) are classified as such, and they can be
			// used as property names
			self::TYPE_UN_OP => [
				self::ACTION_GOTO => self::PAREN_EXPRESSION_OP,
			],
			self::TYPE_BIN_OP => [
				self::ACTION_GOTO => self::PAREN_EXPRESSION_OP,
			],
		],
		// Like EXPRESSION_ARROWFUNC, but in parentheses, see PAREN_EXPRESSION
		self::PAREN_EXPRESSION_ARROWFUNC => [
			self::TYPE_UN_OP => [
				self::ACTION_GOTO => self::PAREN_EXPRESSION,
			],
			self::TYPE_INCR_OP => [
				self::ACTION_GOTO => self::PAREN_EXPRESSION,
			],
			self::TYPE_ADD_OP => [
				self::ACTION_GOTO => self::PAREN_EXPRESSION,
			],
			self::TYPE_BRACE_OPEN => [
				self::ACTION_PUSH => self::PAREN_EXPRESSION_OP,
				self::ACTION_GOTO => self::STATEMENT,
			],
			self::TYPE_PAREN_OPEN => [
				self::ACTION_PUSH => self::PAREN_EXPRESSION_OP,
				self::ACTION_GOTO => self::PAREN_EXPRESSION,
			],
			self::TYPE_FUNC => [
				self::ACTION_PUSH => self::PAREN_EXPRESSION_OP,
				self::ACTION_GOTO => self::FUNC,
			],
			self::TYPE_CLASS => [
				self::ACTION_PUSH => self::PAREN_EXPRESSION_OP,
				self::ACTION_GOTO => self::CLASS_DEF,
			],
			self::TYPE_LITERAL => [
				self::ACTION_GOTO => self::PAREN_EXPRESSION_OP,
			],
		],

		// Like PAREN_EXPRESSION_OP, for the state after "async" in a PAREN_EXPRESSION,
		// for use by the $semicolon model.
		self::PAREN_EXPRESSION_OP_NO_NL => [
			self::TYPE_BIN_OP => [
				self::ACTION_GOTO => self::PAREN_EXPRESSION,
			],
			self::TYPE_ADD_OP => [
				self::ACTION_GOTO => self::PAREN_EXPRESSION,
			],
			self::TYPE_DOT => [
				self::ACTION_GOTO => self::PAREN_EXPRESSION_DOT,
			],
			self::TYPE_HOOK => [
				self::ACTION_GOTO => self::PAREN_EXPRESSION,
			],
			self::TYPE_COLON => [
				self::ACTION_GOTO => self::PAREN_EXPRESSION,
			],
			self::TYPE_COMMA => [
				self::ACTION_GOTO => self::PAREN_EXPRESSION,
			],
			self::TYPE_SEMICOLON => [
				self::ACTION_GOTO => self::PAREN_EXPRESSION,
			],
			self::TYPE_ARROW => [
				self::ACTION_GOTO => self::PAREN_EXPRESSION_ARROWFUNC,
			],
			self::TYPE_PAREN_OPEN => [
				self::ACTION_PUSH => self::PAREN_EXPRESSION_OP,
				self::ACTION_GOTO => self::PAREN_EXPRESSION,
			],
			self::TYPE_PAREN_CLOSE => [
				self::ACTION_POP => true,
			],
		],
		// Expression as the value of a key in an object literal. Like EXPRESSION, except that
		// a comma (in PROPERTY_EXPRESSION_OP) goes to PROPERTY_ASSIGNMENT instead
		self::PROPERTY_EXPRESSION => [
			self::TYPE_BRACE_OPEN => [
				self::ACTION_PUSH => self::PROPERTY_EXPRESSION_OP,
				self::ACTION_GOTO => self::PROPERTY_ASSIGNMENT,
			],
			self::TYPE_BRACE_CLOSE => [
				self::ACTION_POP => true,
			],
			self::TYPE_PAREN_OPEN => [
				self::ACTION_PUSH => self::PROPERTY_EXPRESSION_OP,
				self::ACTION_GOTO => self::PAREN_EXPRESSION,
			],
			self::TYPE_FUNC => [
				self::ACTION_PUSH => self::PROPERTY_EXPRESSION_OP,
				self::ACTION_GOTO => self::FUNC,
			],
			self::TYPE_CLASS => [
				self::ACTION_PUSH => self::PROPERTY_EXPRESSION_OP,
				self::ACTION_GOTO => self::CLASS_DEF,
			],
			self::TYPE_LITERAL => [
				self::ACTION_GOTO => self::PROPERTY_EXPRESSION_OP,
			],
		],
		// Like EXPRESSION_OP, but in a property expression, see PROPERTY_EXPRESSION
		self::PROPERTY_EXPRESSION_OP => [
			self::TYPE_BIN_OP => [
				self::ACTION_GOTO => self::PROPERTY_EXPRESSION,
			],
			self::TYPE_ADD_OP => [
				self::ACTION_GOTO => self::PROPERTY_EXPRESSION,
			],
			self::TYPE_DOT => [
				self::ACTION_GOTO => self::PROPERTY_EXPRESSION_DOT,
			],
			self::TYPE_HOOK => [
				self::ACTION_PUSH => self::PROPERTY_EXPRESSION,
				self::ACTION_GOTO => self::EXPRESSION_TERNARY,
			],
			self::TYPE_COMMA => [
				self::ACTION_GOTO => self::PROPERTY_ASSIGNMENT,
			],
			self::TYPE_ARROW => [
				self::ACTION_GOTO => self::PROPERTY_EXPRESSION_ARROWFUNC,
			],
			self::TYPE_BRACE_OPEN => [
				self::ACTION_PUSH => self::PROPERTY_EXPRESSION_OP,
			],
			self::TYPE_BRACE_CLOSE => [
				self::ACTION_POP => true,
			],
			self::TYPE_PAREN_OPEN => [
				self::ACTION_PUSH => self::PROPERTY_EXPRESSION_OP,
				self::ACTION_GOTO => self::PAREN_EXPRESSION,
			],
		],
		// Like EXPRESSION_DOT, but in a property expression, see PROPERTY_EXPRESSION
		self::PROPERTY_EXPRESSION_DOT => [
			self::TYPE_LITERAL => [
				self::ACTION_GOTO => self::PROPERTY_EXPRESSION_OP,
			],
			// The following are keywords behaving as literals
			self::TYPE_RETURN => [
				self::ACTION_GOTO => self::PROPERTY_EXPRESSION_OP,
			],
			self::TYPE_IF => [
				self::ACTION_GOTO => self::PROPERTY_EXPRESSION_OP,
			],
			self::TYPE_DO => [
				self::ACTION_GOTO => self::PROPERTY_EXPRESSION_OP,
			],
			self::TYPE_VAR => [
				self::ACTION_GOTO => self::PROPERTY_EXPRESSION_OP,
			],
			self::TYPE_FUNC => [
				self::ACTION_GOTO => self::PROPERTY_EXPRESSION_OP,
			],
			self::TYPE_CLASS => [
				self::ACTION_GOTO => self::PROPERTY_EXPRESSION_OP,
			],
			// We don't expect real unary/binary operators here, but some keywords
			// (new, delete, void, typeof, instanceof, in) are classified as such, and they can be
			// used as property names
			self::TYPE_UN_OP => [
				self::ACTION_GOTO => self::PROPERTY_EXPRESSION_OP,
			],
			self::TYPE_BIN_OP => [
				self::ACTION_GOTO => self::PROPERTY_EXPRESSION_OP,
			],
		],
		// Like EXPRESSION_ARROWFUNC, but in a property expression, see PROPERTY_EXPRESSION
		self::PROPERTY_EXPRESSION_ARROWFUNC => [
			self::TYPE_UN_OP => [
				self::ACTION_GOTO => self::PROPERTY_EXPRESSION,
			],
			self::TYPE_INCR_OP => [
				self::ACTION_GOTO => self::PROPERTY_EXPRESSION,
			],
			self::TYPE_ADD_OP => [
				self::ACTION_GOTO => self::PROPERTY_EXPRESSION,
			],
			self::TYPE_BRACE_OPEN => [
				self::ACTION_PUSH => self::PROPERTY_EXPRESSION_OP,
				self::ACTION_GOTO => self::STATEMENT,
			],
			self::TYPE_PAREN_OPEN => [
				self::ACTION_PUSH => self::PROPERTY_EXPRESSION_OP,
				self::ACTION_GOTO => self::PAREN_EXPRESSION,
			],
			self::TYPE_FUNC => [
				self::ACTION_PUSH => self::PROPERTY_EXPRESSION_OP,
				self::ACTION_GOTO => self::FUNC,
			],
			self::TYPE_CLASS => [
				self::ACTION_PUSH => self::PROPERTY_EXPRESSION_OP,
				self::ACTION_GOTO => self::CLASS_DEF,
			],
			self::TYPE_LITERAL => [
				self::ACTION_GOTO => self::PROPERTY_EXPRESSION_OP,
			],
		],
		// Class definition (after the class keyword). Expects an identifier, or the extends
		// keyword followed by an expression (or both), followed by {, which starts an object
		// literal. The object literal's closing } will pop the stack, so the state to return
		// to after the class definition should be pushed to the stack first.
		self::CLASS_DEF => [
			self::TYPE_BRACE_OPEN => [
				self::ACTION_GOTO => self::PROPERTY_ASSIGNMENT,
			],
			self::TYPE_PAREN_OPEN => [
				self::ACTION_PUSH => self::CLASS_DEF,
				self::ACTION_GOTO => self::PAREN_EXPRESSION,
			],
			self::TYPE_FUNC => [
				self::ACTION_PUSH => self::CLASS_DEF,
				self::ACTION_GOTO => self::FUNC,
			],
		],
		// Import or export declaration
		self::IMPORT_EXPORT => [
			self::TYPE_SEMICOLON => [
				self::ACTION_GOTO => self::STATEMENT,
			],
			self::TYPE_VAR => [
				self::ACTION_GOTO => self::EXPRESSION,
			],
			self::TYPE_FUNC => [
				self::ACTION_PUSH => self::EXPRESSION_OP,
				self::ACTION_GOTO => self::FUNC,
			],
			self::TYPE_CLASS => [
				self::ACTION_PUSH => self::EXPRESSION_OP,
				self::ACTION_GOTO => self::CLASS_DEF,
			],
			self::TYPE_SPECIAL => [
				'default' => [
					self::ACTION_GOTO => self::EXPRESSION,
				],
				// Stay in this state for *, as, from
				'*' => [],
				'as' => [],
				'from' => [],
			],
		],
		// Used in template string-specific code below
		self::TEMPLATE_STRING_HEAD => [
			self::TYPE_LITERAL => [
				self::ACTION_PUSH => self::TEMPLATE_STRING_TAIL,
				self::ACTION_GOTO => self::EXPRESSION,
			],
		],
	];

	/**
	 * @var array $semicolon
	 *
	 * Rules for when semicolon insertion is appropriate. Semicolon insertion happens if we are
	 * in one of these states, and encounter one of these tokens preceded by a newline.
	 *
	 * This array is augmented by ensureExpandedStates().
	 */
	private static $semicolon = [
		self::EXPRESSION_NO_NL => [
			self::TYPE_UN_OP => true,
			// BIN_OP seems impossible at the start of an expression, but it can happen in
			// yield *foo
			self::TYPE_BIN_OP => true,
			self::TYPE_INCR_OP => true,
			self::TYPE_ADD_OP => true,
			self::TYPE_BRACE_OPEN => true,
			self::TYPE_PAREN_OPEN => true,
			self::TYPE_RETURN => true,
			self::TYPE_IF => true,
			self::TYPE_DO => true,
			self::TYPE_VAR => true,
			self::TYPE_FUNC => true,
			self::TYPE_CLASS => true,
			self::TYPE_LITERAL => true,
			self::TYPE_ASYNC => true,
		],
		self::EXPRESSION_OP => [
			self::TYPE_UN_OP => true,
			self::TYPE_INCR_OP => true,
			self::TYPE_BRACE_OPEN => true,
			self::TYPE_RETURN => true,
			self::TYPE_IF => true,
			self::TYPE_DO => true,
			self::TYPE_VAR => true,
			self::TYPE_FUNC => true,
			self::TYPE_CLASS => true,
			self::TYPE_LITERAL => true,
			self::TYPE_ASYNC => true,
		],
		self::EXPRESSION_END => [
			self::TYPE_UN_OP => true,
			self::TYPE_INCR_OP => true,
			self::TYPE_ADD_OP => true,
			self::TYPE_BRACE_OPEN => true,
			self::TYPE_PAREN_OPEN => true,
			self::TYPE_RETURN => true,
			self::TYPE_IF => true,
			self::TYPE_DO => true,
			self::TYPE_VAR => true,
			self::TYPE_FUNC => true,
			self::TYPE_CLASS => true,
			self::TYPE_LITERAL => true,
			self::TYPE_ASYNC => true,
		],
		self::PAREN_EXPRESSION_OP_NO_NL => [
			self::TYPE_FUNC => true,
		]
	];

	/**
	 * @var array $divStates
	 *
	 * States in which a / is a division operator. In all other states, it's the start of a regex.
	 *
	 * This array is augmented by self::ensureExpandedStates().
	 */
	private static $divStates = [
		self::EXPRESSION_OP          => true,
		self::EXPRESSION_TERNARY_OP  => true,
		self::PAREN_EXPRESSION_OP    => true,
		self::PROPERTY_EXPRESSION_OP => true
	];

	/**
	 * Add copies of all states but with negative numbers to self::$model (if not already present),
	 * to represent generator function states.
	 */
	private static function ensureExpandedStates() {
		// Already done?
		if ( self::$expandedStates ) {
			return;
		}
		self::$expandedStates = true;

		// Add copies of all states (except FUNC and GENFUNC) with negative numbers.
		// These negative states represent states inside generator functions. When in these states,
		// TYPE_YIELD is treated as TYPE_RETURN, otherwise as TYPE_LITERAL
		foreach ( self::$model as $state => $transitions ) {
			if ( $state !== self::FUNC && $state !== self::GENFUNC ) {
				foreach ( $transitions as $tokenType => $actions ) {
					foreach ( $actions as $action => $target ) {
						if ( is_array( $target ) ) {
							foreach ( $target as $subaction => $subtarget ) {
								self::$model[-$state][$tokenType][$action][$subaction] =
									$subtarget === self::FUNC || $subtarget === true || $subtarget === self::GENFUNC
									? $subtarget : -$subtarget;
							}
						} else {
							self::$model[-$state][$tokenType][$action] =
								$target === self::FUNC || $target === true || $target === self::GENFUNC
								? $target : -$target;
						}
					}
				}
			}
		}
		// Special cases:
		// '{' in a property assignment starts a method, so it shouldn't be flipped
		self::$model[-self::PROPERTY_ASSIGNMENT][self::TYPE_BRACE_OPEN][self::ACTION_GOTO] = self::STATEMENT;

		// Also add negative versions of states to the other arrays
		foreach ( self::$semicolon as $state => $value ) {
			self::$semicolon[-$state] = $value;
		}
		foreach ( self::$divStates as $state => $value ) {
			self::$divStates[-$state] = $value;
		}
	}

	/**
	 * Returns minified JavaScript code.
	 *
	 * @param string $s JavaScript code to minify
	 * @return string|bool Minified code or false on failure
	 */
	public static function minify( $s ) {
		return self::minifyInternal( $s );
	}

	/**
	 * Create a minifier state object without source map capabilities
	 *
	 * Example:
	 *
	 *   JavaScriptMinifier::createMinifier()
	 *     ->addSourceFile( 'file.js', $source )
	 *     ->getMinifiedOutput();
	 *
	 * @return JavaScriptMinifierState
	 */
	public static function createMinifier() {
		return new JavaScriptMinifierState;
	}

	/**
	 * Create a minifier state object with source map capabilities
	 *
	 * Example:
	 *
	 *   $mapper = JavaScriptMinifier::createSourceMapState()
	 *     ->addSourceFile( 'file1.js', $source1 )
	 *     ->addOutput( "\n\n" )
	 *     ->addSourceFile( 'file2.js', $source2 );
	 *   $out = $mapper->getMinifiedOutput();
	 *   $map = $mapper->getSourceMap()
	 *
	 * @return JavaScriptMapperState
	 */
	public static function createSourceMapState() {
		return new JavaScriptMapperState;
	}

	/**
	 * Create a MinifierState that doesn't actually minify
	 *
	 * @return IdentityMinifierState
	 */
	public static function createIdentityMinifier() {
		return new IdentityMinifierState;
	}

	/**
	 * Minify with optional source map.
	 *
	 * @internal
	 *
	 * @param string $s
	 * @param MappingsGenerator|null $mapGenerator
	 * @return bool|string
	 */
	public static function minifyInternal( $s, $mapGenerator = null ) {
		self::ensureExpandedStates();

		// Here's where the minifying takes place: Loop through the input, looking for tokens
		// and output them to $out, taking actions to the above defined rules when appropriate.
		$out = '';
		$pos = 0;
		$length = strlen( $s );
		$lineLength = 0;
		$dotlessNum = false;
		$lastDotlessNum = false;
		$newlineFound = true;
		$state = self::STATEMENT;
		$stack = [];
		$topOfStack = null; // Optimization: calling end( $stack ) repeatedly is expensive
		$last = ';'; // Pretend that we have seen a semicolon yet
		while ( $pos < $length ) {
			// First, skip over any whitespace and multiline comments, recording whether we
			// found any newline character
			$skip = strspn( $s, " \t\n\r\xb\xc", $pos );
			if ( !$skip ) {
				$ch = $s[$pos];
				if ( $ch === '/' && substr( $s, $pos, 2 ) === '/*' ) {
					// Multiline comment. Search for the end token or EOT.
					$end = strpos( $s, '*/', $pos + 2 );
					$skip = $end === false ? $length - $pos : $end - $pos + 2;
				}
			}
			if ( $skip ) {
				// The semicolon insertion mechanism needs to know whether there was a newline
				// between two tokens, so record it now.
				if ( !$newlineFound && strcspn( $s, "\r\n", $pos, $skip ) !== $skip ) {
					$newlineFound = true;
				}
				if ( $mapGenerator ) {
					$mapGenerator->consumeSource( $skip );
				}
				$pos += $skip;
				continue;
			}
			// Handle C++-style comments and html comments, which are treated as single line
			// comments by the browser, regardless of whether the end tag is on the same line.
			// Handle --> the same way, but only if it's at the beginning of the line
			// @phan-suppress-next-line PhanPossiblyUndeclaredVariable
			if ( ( $ch === '/' && substr( $s, $pos, 2 ) === '//' )
				|| ( $ch === '<' && substr( $s, $pos, 4 ) === '<!--' )
				|| ( $ch === '-' && $newlineFound && substr( $s, $pos, 3 ) === '-->' )
			) {
				$skip = strcspn( $s, "\r\n", $pos );
				if ( $mapGenerator ) {
					$mapGenerator->consumeSource( $skip );
				}
				$pos += $skip;
				continue;
			}

			// Find out which kind of token we're handling.
			// Note: $end must point past the end of the current token
			// so that `substr($s, $pos, $end - $pos)` would be the entire token.
			// In order words, $end will be the offset of the last relevant character
			// in the stream + 1, or simply put: The offset of the first character
			// of any next token in the stream.
			$end = $pos + 1;
			// Handle string literals
			if ( $ch === "'" || $ch === '"' ) {
				// Search to the end of the string literal, skipping over backslash escapes
				$search = $ch . '\\';
				do {
					// Speculatively add 2 to the end so that if we see a backslash,
					// the next iteration will start 2 characters further (one for the
					// backslash, one for the escaped character).
					// We'll correct this outside the loop.
					$end += strcspn( $s, $search, $end ) + 2;
					// If the last character in our search for a quote or a backlash
					// matched a backslash and we haven't reached the end, keep searching..
				} while ( $end - 2 < $length && $s[$end - 2] === '\\' );
				// Correction (1): Undo speculative add, keep only one (end of string literal)
				$end--;
				if ( $end > $length ) {
					// Correction (2): Loop wrongly assumed an end quote ended the search,
					// but search ended because we've reached the end. Correct $end.
					// TODO: This is invalid and should throw.
					$end--;
				}

			// Handle template strings, either from "`" to begin a new string,
			// or continuation after the "}" that ends a "${"-expression.
			} elseif ( $ch === '`' || ( $ch === '}' && $topOfStack === self::TEMPLATE_STRING_TAIL ) ) {
				if ( $ch === '}' ) {
					// Pop the TEMPLATE_STRING_TAIL state off the stack
					// We don't let it get popped off the stack the normal way, to avoid the newline
					// and comment stripping code above running on the continuation of the literal
					array_pop( $stack );
					// Also pop the previous state off the stack
					$state = array_pop( $stack );
					$topOfStack = end( $stack );
				}
				// Search until we reach either a closing ` or a ${, skipping over backslash escapes
				// and $ characters followed by something other than { or `
				do {
					$end += strcspn( $s, '`$\\', $end ) + 1;
					if ( $end - 1 < $length && $s[$end - 1] === '`' ) {
						// End of the string, stop
						// We don't do this in the while() condition because the $end++ in the
						// backslash escape branch makes it difficult to do so without incorrectly
						// considering an escaped backtick (\`) the end of the string
						break;
					}
					if ( $end - 1 < $length && $s[$end - 1] === '\\' ) {
						// Backslash escape. Skip the next character, and keep going
						$end++;
						continue;
					}
					if ( $end < $length && $s[$end - 1] === '$' && $s[$end] === '{' ) {
						// Beginning of an expression in ${ ... }. Skip the {, and stop
						$end++;
						// Push the current state to the stack. We'll pop this off later when hitting
						// the end of this template string
						$stack[] = $state;
						$topOfStack = $state;
						// Change the state to TEMPLATE_STRING_HEAD. The token type will be detected
						// as TYPE_LITERAL, and this will cause the state machine to expect an
						// expression, then go to the TEMPLATE_STRING_TAIL state when it hits the }
						$state = self::TEMPLATE_STRING_HEAD;
						break;
					}
				} while ( $end - 1 < $length );
				if ( $end > $length ) {
					// Loop wrongly assumed an end quote or ${ ended the search,
					// but search ended because we've reached the end. Correct $end.
					// TODO: This is invalid and should throw.
					$end--;
				}

			// We have to distinguish between regexp literals and division operators
			// A division operator is only possible in certain states
			} elseif ( $ch === '/' && !isset( self::$divStates[$state] ) ) {
				// Regexp literal
				for ( ; ; ) {
					// Search until we find "/" (end of regexp), "\" (backslash escapes),
					// or "[" (start of character classes).
					do {
						// Speculatively add 2 to ensure next iteration skips
						// over backslash and escaped character.
						// We'll correct this outside the loop.
						$end += strcspn( $s, '/[\\', $end ) + 2;
						// If backslash escape, keep searching...
					} while ( $end - 2 < $length && $s[$end - 2] === '\\' );
					// Correction (1): Undo speculative add, keep only one (end of regexp)
					$end--;
					if ( $end > $length ) {
						// Correction (2): Loop wrongly assumed end slash was seen
						// String ended without end of regexp. Correct $end.
						// TODO: This is invalid and should throw.
						$end--;
						break;
					}
					if ( $s[$end - 1] === '/' ) {
						break;
					}
					// (Implicit else), we must've found the start of a char class,
					// skip until we find "]" (end of char class), or "\" (backslash escape)
					do {
						// Speculatively add 2 for backslash escape.
						// We'll substract one outside the loop.
						$end += strcspn( $s, ']\\', $end ) + 2;
						// If backslash escape, keep searching...
					} while ( $end - 2 < $length && $s[$end - 2] === '\\' );
					// Correction (1): Undo speculative add, keep only one (end of regexp)
					$end--;
					if ( $end > $length ) {
						// Correction (2): Loop wrongly assumed "]" was seen
						// String ended without ending char class or regexp. Correct $end.
						// TODO: This is invalid and should throw.
						$end--;
						break;
					}
				}
				// Search past the regexp modifiers (gi)
				while ( $end < $length && ctype_alpha( $s[$end] ) ) {
					$end++;
				}
			} elseif (
				$ch === '0'
				&& ( $pos + 1 < $length ) && ( $s[$pos + 1] === 'x' || $s[$pos + 1] === 'X' )
			) {
				// Hex numeric literal
				$end++; // x or X
				$len = strspn( $s, '0123456789ABCDEFabcdef', $end );
				if ( !$len ) {
					return self::parseError(
						$s,
						$pos,
						'Expected a hexadecimal number but found ' . substr( $s, $pos, 5 ) . '...'
					);
				}
				$end += $len;
			} elseif (
				// Optimisation: This check must accept only ASCII digits 0-9.
				// Avoid ctype_digit() because it is slower and also accepts locale-specific digits.
				// Using is_numeric() might seem wrong also as it accepts negative numbers, decimal
				// numbers, and exponents (e.g. strings like "+012.34e6"). But, it is fine here
				// because we know $ch is a single character, and we believe the only single
				// characters that is_numeric() accepts are ASCII digits 0-9.
				is_numeric( $ch )
				|| ( $ch === '.' && $pos + 1 < $length && is_numeric( $s[$pos + 1] ) )
			) {
				$end += strspn( $s, '0123456789', $end );
				$decimal = strspn( $s, '.', $end );
				if ( $decimal ) {
					if ( $decimal > 2 ) {
						return self::parseError( $s, $end, 'The number has too many decimal points' );
					}
					$end += strspn( $s, '0123456789', $end + 1 ) + $decimal;
				} else {
					$dotlessNum = true;
				}
				$exponent = strspn( $s, 'eE', $end );
				if ( $exponent ) {
					if ( $exponent > 1 ) {
						return self::parseError( $s, $end, 'Number with several E' );
					}
					$end++;

					// + sign is optional; - sign is required.
					$end += strspn( $s, '-+', $end );
					$len = strspn( $s, '0123456789', $end );
					if ( !$len ) {
						return self::parseError(
							$s,
							$pos,
							'No decimal digits after e, how many zeroes should be added?'
						);
					}
					$end += $len;
				}
			} elseif ( isset( self::$opChars[$ch] ) ) {
				// Punctuation character. Search for the longest matching operator.
				for ( $tokenLength = self::LONGEST_PUNCTUATION_TOKEN; $tokenLength > 1; $tokenLength-- ) {
					if (
						$pos + $tokenLength <= $length &&
						isset( self::$tokenTypes[ substr( $s, $pos, $tokenLength ) ] )
					) {
						$end = $pos + $tokenLength;
						break;
					}
				}
			} else {
				// Identifier or reserved word. Search for the end by excluding whitespace and
				// punctuation.
				$end += strcspn( $s, " \t\n.;,=<>+-{}()[]?:*/%'\"`!&|^~\xb\xc\r", $end );
			}

			// Now get the token type from our type array
			$token = substr( $s, $pos, $end - $pos ); // so $end - $pos == strlen( $token )
			$type = isset( self::$model[$state][self::TYPE_SPECIAL][$token] )
				? self::TYPE_SPECIAL
				: self::$tokenTypes[$token] ?? self::TYPE_LITERAL;
			if ( $type === self::TYPE_YIELD ) {
				// yield is treated as TYPE_RETURN inside a generator function (negative state)
				// but as TYPE_LITERAL when not in a generator function (positive state)
				$type = $state < 0 ? self::TYPE_RETURN : self::TYPE_LITERAL;
			}

			$pad = '';
			if ( $newlineFound && isset( self::$semicolon[$state][$type] ) ) {
				// This token triggers the semicolon insertion mechanism of javascript. While we
				// could add the ; token here ourselves, keeping the newline has a few advantages.
				$pad = "\n";
				$state = $state < 0 ? -self::STATEMENT : self::STATEMENT;
				$lineLength = 0;
			} elseif ( $lineLength + $end - $pos > self::$maxLineLength &&
				!isset( self::$semicolon[$state][$type] ) &&
				$type !== self::TYPE_INCR_OP &&
				$type !== self::TYPE_ARROW
			) {
				// This line would get too long if we added $token, so add a newline first.
				// Only do this if it won't trigger semicolon insertion and if it won't
				// put a postfix increment operator or an arrow on its own line,
				// which is illegal in js.
				$pad = "\n";
				$lineLength = 0;
			// Check, whether we have to separate the token from the last one with whitespace
			} elseif ( !isset( self::$opChars[$last] ) && !isset( self::$opChars[$ch] ) ) {
				$pad = ' ';
				$lineLength++;
			// Don't accidentally create ++, -- or // tokens
			} elseif ( $last === $ch && ( $ch === '+' || $ch === '-' || $ch === '/' ) ) {
				$pad = ' ';
				$lineLength++;
			// Don't create invalid dot notation after number literal (T303827).
			// Keep whitespace in "42. foo".
			// But keep minifying "foo.bar", "42..foo", and "42.0.foo" per $opChars.
			} elseif ( $lastDotlessNum && $type === self::TYPE_DOT ) {
				$pad = ' ';
				$lineLength++;
			}

			// self::debug( $topOfStack, $last, $state, $ch, $token, $type );

			if ( $mapGenerator ) {
				$mapGenerator->outputSpace( $pad );
				$mapGenerator->outputToken( $token );
				$mapGenerator->consumeSource( $end - $pos );
			}
			$out .= $pad;
			$out .= $token;
			$lineLength += $end - $pos; // += strlen( $token )
			$last = $s[$end - 1];
			$pos = $end;
			$newlineFound = false;
			$lastDotlessNum = $dotlessNum;
			$dotlessNum = false;

			// Now that we have output our token, transition into the new state.
			$actions = $type === self::TYPE_SPECIAL ?
				self::$model[$state][$type][$token] :
				self::$model[$state][$type] ?? [];
			if ( isset( $actions[self::ACTION_PUSH] ) &&
				count( $stack ) < self::STACK_LIMIT
			) {
				$topOfStack = $actions[self::ACTION_PUSH];
				$stack[] = $topOfStack;
			}
			if ( $stack && isset( $actions[self::ACTION_POP] ) ) {
				$state = array_pop( $stack );
				$topOfStack = end( $stack );
			} elseif ( isset( $actions[self::ACTION_GOTO] ) ) {
				$state = $actions[self::ACTION_GOTO];
			}
		}
		return $out;
	}

	/**
	 * @param string $fullJavascript
	 * @param int $position
	 * @param string $errorMsg
	 * @return bool
	 */
	public static function parseError( $fullJavascript, $position, $errorMsg ) {
		// TODO: Handle the error: trigger_error, throw exception, return false...
		return false;
	}

	/**
	 * @param null|false|int $top
	 * @param string $last
	 * @param int $state
	 * @param string $ch
	 * @param string $token
	 * @param int $type
	 */
	private static function debug(
		$top, string $last,
		int $state, string $ch, string $token, int $type
	) {
		static $first = true;
		$self = new \ReflectionClass( self::class );
		$constants = $self->getConstants();

		foreach ( $self->getConstants() as $name => $value ) {
			if ( $value === $top ) {
				$top = $name;
			}
			if ( $value === $state ) {
				$state = $name;
			}
			if ( $value === $type ) {
				$type = $name;
			}
		}

		if ( $first ) {
			print sprintf( "| %-29s | %-4s | %-29s | %-2s | %-10s | %-29s\n",
				'topOfStack', 'last', 'state', 'ch', 'token', 'type' );
			print sprintf( "| %'-29s | %'-4s | %'-29s | %'-2s | %'-10s | %'-29s\n",
				'', '', '', '', '', '' );
			$first = false;
		}
		print sprintf( "| %-29s | %-4s | %-29s | %-2s | %-10s | %-29s\n",
			(string)$top, $last, $state, $ch, $token, $type );
	}
}
