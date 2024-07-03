<?php
/**
 * This file is part of the Peast package
 *
 * (c) Marco Marchiò <marco.mm89@gmail.com>
 *
 * For the full copyright and license information refer to the LICENSE file
 * distributed with this source code
 */
namespace Peast\Syntax;

/**
 * Base class for scanners.
 * 
 * @author Marco Marchiò <marco.mm89@gmail.com>
 */
class Scanner
{
    use JSX\Scanner;

    /**
     * Scanner features
     * 
     * @var Features
     */
    protected $features;

    /**
     * Current column
     * 
     * @var int
     */
    protected $column = 0;
    
    /**
     * Current line
     * 
     * @var int
     */
    protected $line = 1;
    
    /**
     * Current index
     * 
     * @var int
     */
    protected $index = 0;
    
    /**
     * Source length
     * 
     * @var int
     */
    protected $length;
    
    /**
     * Source characters
     * 
     * @var array
     */
    protected $source;
    
    /**
     * Consumed position
     * 
     * @var Position
     */
    protected $position;
    
    /**
     * Current token
     * 
     * @var Token 
     */
    protected $currentToken;
    
    /**
     * Next token
     * 
     * @var Token 
     */
    protected $nextToken;
    
    /**
     * Strict mode flag
     * 
     * @var bool 
     */
    protected $strictMode = false;
    
    /**
     * True to register tokens in the tokens array
     * 
     * @var bool 
     */
    protected $registerTokens = false;
    
    /**
     * Module mode
     * 
     * @var bool 
     */
    protected $isModule = false;
    
    /**
     * Comments handling
     * 
     * @var bool 
     */
    protected $comments = false;
    
    /**
     * Internal JSX scan flag
     * 
     * @var bool
     */
    protected $jsx = false;
    
    /**
     * Registered tokens array
     * 
     * @var array 
     */
    protected $tokens = array();
    
    /**
     * Comments to tokens map
     * 
     * @var array 
     */
    protected $commentsMap = array();
    
    /**
     * Events emitter
     *
     * @var EventsEmitter
     */
    protected $eventsEmitter;
    
    /**
     * Regex to match identifiers starts
     * 
     * @var string 
     */
    protected $idStartRegex = "/[\p{Lu}\p{Ll}\p{Lt}\p{Lm}\p{Lo}\p{Nl}\x{1885}\x{1886}\x{2118}\x{212E}\x{309B}\x{309C}]/u";
    
    /**
     * Regex to match identifiers parts
     * 
     * @var string 
     */
    protected $idPartRegex = "/[\p{Lu}\p{Ll}\p{Lt}\p{Lm}\p{Lo}\p{Nl}\x{1885}\x{1886}\x{2118}\x{212E}\x{309B}\x{309C}\p{Mn}\p{Mc}\p{Nd}\p{Pc}\x{00B7}\x{0387}\x{1369}\x{136A}\x{136B}\x{136C}\x{136D}\x{136E}\x{136F}\x{1370}\x{1371}\x{19DA}\x{200C}\x{200D}]/u";
    
    /**
     * Keywords array
     * 
     * @var array 
     */
    protected $keywords = array(
        "break", "do", "in", "typeof", "case", "else", "instanceof", "var",
        "catch", "export", "new", "void", "class", "extends", "return", "while",
        "const", "finally", "super", "with", "continue", "for", "switch",
        "debugger", "function", "this", "default", "if", "throw",
        "delete", "import", "try", "enum", "await"
    );
    
    /**
     * Array of words that are keywords only in strict mode
     * 
     * @var array 
     */
    protected $strictModeKeywords = array(
        "implements", "interface", "package", "private", "protected", "public",
        "static", "let", "yield"
    );

    /**
     * Punctuators array
     * 
     * @var array 
     */
    protected $punctuators = array(
        ".", ";", ",", "<", ">", "<=", ">=", "==", "!=", "===", "!==", "+",
        "-", "*", "%", "++", "--", "<<", ">>", ">>>", "&", "|", "^", "!", "~",
        "&&", "||", "?", ":", "=", "+=", "-=", "*=", "%=", "<<=", ">>=", ">>>=",
        "&=", "|=", "^=", "=>", "...", "/", "/=", "**", "**=", "??", "?.",
        "&&=", "||=", "??="
    );
    
    /**
     * Punctuators LSM
     * 
     * @var LSM 
     */
    protected $punctuatorsLSM;
    
    /**
     * Strings stops LSM
     * 
     * @var LSM 
     */
    protected $stringsStopsLSM;
    
    /**
     * Brackets array
     * 
     * @var array 
     */
    protected $brackets = array(
        "(" => "", "[" => "", "{" => "", ")" => "(", "]" => "[", "}" => "{"
    );
    
    /**
     * Open brackets array
     * 
     * @var array 
     */
    protected $openBrackets = array();
    
    /**
     * Open templates array
     * 
     * @var array 
     */
    protected $openTemplates = array();
    
    /**
     * Whitespaces array
     * 
     * @var array 
     */
    protected $whitespaces = array(
        " ", "\t", "\n", "\r", "\f", "\v", 0x00A0, 0xFEFF, 0x00A0,
        0x1680, 0x2000, 0x2001, 0x2002, 0x2003, 0x2004, 0x2005, 0x2006,
        0x2007, 0x2008, 0x2009, 0x200A, 0x202F, 0x205F, 0x3000, 0x2028,
        0x2029
    );
    
    /**
     * Line terminators characters array
     * 
     * @var array 
     * 
     * @static
     */
    public static $lineTerminatorsChars = array("\n", "\r", 0x2028, 0x2029);
    
    /**
     * Line terminators sequences array
     * 
     * @var array
     * 
     * @static
     */
    public static $lineTerminatorsSequences = array("\r\n");
    
    /**
     * Regex to split texts using valid ES line terminators
     * 
     * @var array 
     */
    protected $linesSplitter;
    
    /**
     * Concatenation of line terminators characters and line terminators
     * sequences
     * 
     * @var array 
     */
    protected $lineTerminators;
    
    /**
     * Properties to copy when getting the scanner state
     * 
     * @var array
     */
    protected $stateProps = array("position", "index", "column", "line",
                                  "currentToken", "nextToken", "strictMode",
                                  "openBrackets", "openTemplates",
                                  "commentsMap");
    
    /**
     * Decimal numbers
     * 
     * @var array
     */
    protected $numbers = array("0", "1", "2", "3", "4", "5", "6", "7", "8",
                               "9");
    
    /**
     * Hexadecimal numbers
     * 
     * @var array
     */
    protected $xnumbers = array("0", "1", "2", "3", "4", "5", "6", "7", "8",
                                "9", "a", "b", "c", "d", "e", "f", "A", "B",
                                "C", "D", "E", "F");

    /**
     * Octal numbers
     *
     * @var array
     */
    protected $onumbers = array("0", "1", "2", "3", "4", "5", "6", "7");

    /**
     * Binary numbers
     *
     * @var array
     */
    protected $bnumbers = array("0", "1");

    /**
     * Class constructor
     * 
     * @param string   $source   Source code
     * @param Features $features Scanner features
     * @param array    $options  Parsing options
     */
    function __construct(
        $source, Features $features, $options
    ) {
        $this->features = $features;

        $encoding = isset($options["sourceEncoding"]) ?
                    $options["sourceEncoding"] :
                    null;
        
        //Strip BOM characters from the source
        $this->stripBOM($source, $encoding);
        
        //Convert to UTF8 if needed
        if ($encoding && !preg_match("/UTF-?8/i", $encoding)) {
            $source = mb_convert_encoding($source, "UTF-8", $encoding);
        }
        
        //Instead of using mb_substr for each character, split the source
        //into an array of UTF8 characters for performance reasons
        $this->source = Utils::stringToUTF8Array(
            $source,
            !isset($options["strictEncoding"]) || $options["strictEncoding"]
        );
        $this->length = count($this->source);
        
        //Convert character codes to UTF8 characters in whitespaces and line
        //terminators
        $this->lineTerminators = array_merge(
            self::$lineTerminatorsSequences, self::$lineTerminatorsChars
        );
        foreach (array("whitespaces", "lineTerminators") as $key) {
            foreach ($this->$key as $i => $char) {
                if (is_int($char)) {
                    $this->{$key}[$i] = Utils::unicodeToUtf8($char);
                }
            }
        }

        //Remove exponentiation operator if the feature
        //is not enabled
        if (!$this->features->exponentiationOperator) {
            Utils::removeArrayValue($this->punctuators, "**");
            Utils::removeArrayValue($this->punctuators, "**=");
        }

        if (!$this->features->optionalChaining) {
            Utils::removeArrayValue($this->punctuators, "?.");
        }

        //Remove logical assignment operators if the feature
        //is not enabled
        if (!$this->features->logicalAssignmentOperators) {
            Utils::removeArrayValue($this->punctuators, "&&=");
            Utils::removeArrayValue($this->punctuators, "||=");
            Utils::removeArrayValue($this->punctuators, "??=");
        }
        
        //Create a LSM for punctuators array
        $this->punctuatorsLSM = new LSM($this->punctuators);
        
        //Create a LSM for strings stops
        $this->stringsStopsLSM = new LSM($this->lineTerminators, true);
        
        //Allow paragraph and line separators in strings
        if ($this->features->paragraphLineSepInStrings) {
            $this->stringsStopsLSM->remove(Utils::unicodeToUtf8(0x2028));
            $this->stringsStopsLSM->remove(Utils::unicodeToUtf8(0x2029));
        }

        //Remove await as keyword if async/await is enabled
        if ($this->features->asyncAwait) {
            Utils::removeArrayValue($this->keywords, "await");
        }
        
        $this->linesSplitter = "/" .
                               implode("|", $this->lineTerminators) .
                               "/uS";
        $this->position = new Position(0, 0, 0);
    }
    
    /**
     * Strips BOM characters from the source and detects source encoding if not
     * given by the user
     * 
     * @param string $source   Source
     * @param string $encoding User specified encoding
     */
    public function stripBOM(&$source, &$encoding)
    {
        $boms = array(
            "\xEF" => array(array("\xBB", "\xBF"), "UTF-8"),
            "\xFE" => array(array("\xFF"), "UTF-16BE"),
            "\xFF" => array(array("\xFE"), "UTF-16LE"),
        );
        if (!isset($source[0]) || !isset($boms[$source[0]])) {
            return;
        }
        $bom = $boms[$source[0]];
        $l = count($bom[0]);
        for ($i = 0; $i < $l; $i++) {
            if (!isset($source[$i + 1]) || $source[$i + 1] !== $bom[0][$i]) {
                return;
            }
        }
        $source = substr($source, $l + 1);
        if (!$encoding) {
            $encoding = $bom[1];
        }
    }
    
    /**
     * Enables or disables module scanning mode
     * 
     * @param bool $enable True to enable module scanning mode, false to disable it
     * 
     * @return $this
     */
    public function enableModuleMode($enable = true)
    {
        $this->isModule = $enable;
        return $this;
    }
    
    /**
     * Enables or disables comments handling
     * 
     * @param bool $enable True to enable comments handling, false to disable it
     * 
     * @return $this
     */
    public function enableComments($enable = true)
    {
        $this->comments = $enable;
        return $this;
    }
    
    /**
     * Enables or disables tokens registration in the token array
     * 
     * @param bool $enable True to enable token registration, false to disable it
     * 
     * @return $this
     */
    public function enableTokenRegistration($enable = true)
    {
        $this->registerTokens = $enable;
        return $this;
    }
    
    /**
     * Return registered tokens
     * 
     * @return array
     */
    public function getTokens()
    {
        return $this->tokens;
    }
    
    /**
     * Returns the scanner's event emitter
     * 
     * @return EventsEmitter
     */
    public function getEventsEmitter()
    {
        if (!$this->eventsEmitter) {
            //The event emitter is created here so that it won't exist if not
            //necessary
            $this->eventsEmitter = new EventsEmitter;
        }
        return $this->eventsEmitter;
    }
    
    /**
     * Enables or disables strict mode
     * 
     * @param bool $strictMode Strict mode state
     * 
     * @return $this
     */
    public function setStrictMode($strictMode)
    {
        $this->strictMode = $strictMode;
        return $this;
    }
    
    /**
     * Return strict mode state
     * 
     * @return bool
     */
    public function getStrictMode()
    {
        return $this->strictMode;
    }
    
    /**
     * Checks if the given token is a keyword in the current strict mode state
     * 
     * @param Token $token Token to checks
     * 
     * @return bool
     */
    public function isStrictModeKeyword($token)
    {
        return $token->type === Token::TYPE_KEYWORD &&
               (in_array($token->value, $this->keywords) || (
                $this->strictMode &&
                in_array($token->value, $this->strictModeKeywords)));
    }
    
    /**
     * Returns the current scanner state
     * 
     * @return array
     */
    public function getState()
    {
        //Consume current and next tokens so that they wont' be parsed again
        //if the state is restored. If the current token is a slash the next
        //token isn't parsed, this prevents some edge cases where a regexp
        //that contains something that can be interpreted as a comment causes
        //the content to be parsed as a real comment too
        $token = $this->currentToken ?: $this->getToken();
        if ($token && $token->value !== "/") {
            $this->getNextToken();
        }
        $state = array();
        foreach ($this->stateProps as $prop) {
            $state[$prop] = $this->$prop;
        }
        if ($this->registerTokens) {
            $state["tokensNum"] = count($this->tokens);
        }
        //Emit the FreezeState event and pass the given state so that listeners
        //attached to this event can add data
        $this->eventsEmitter && $this->eventsEmitter->fire(
            "FreezeState", array(&$state)
        );
        return $state;
    }
    
    /**
     * Sets the current scanner state
     * 
     * @param array $state State
     * 
     * @return $this
     */
    public function setState($state)
    {
        if ($this->registerTokens) {
            //Check if tokens have been added
            if (isset($this->tokens[$state["tokensNum"]])) {
                //Remove all added tokens
                for ($i = count($this->tokens) - 1; $i >= $state["tokensNum"]; $i--) {
                    array_pop($this->tokens);
                }
            }
            unset($state["tokensNum"]);
        }
        //Emit the ResetState event and pass the given state
        $this->eventsEmitter && $this->eventsEmitter->fire(
            "ResetState", array(&$state)
        );
        foreach ($state as $key => $value) {
            $this->$key = $value;
        }
        return $this;
    }
    
    /**
     * Returns current scanner state
     * 
     * @param bool $scanPosition By default this method returns the scanner
     *                           consumed position, if this parameter is true
     *                           the scanned position will be returned
     * 
     * @return Position
     */
    public function getPosition($scanPosition = false)
    {
        if ($scanPosition) {
            return new Position($this->line, $this->column, $this->index);
        } else {
            return $this->position;
        }
    }
    
    /**
     * Sets the current scan position at the given one
     * 
     * @param Position $position Position at which the scan position will be set
     * 
     * @return $this
     */
    public function setScanPosition(Position $position = null)
    {
        $this->line = $position->getLine();
        $this->column = $position->getColumn();
        $this->index = $position->getIndex();
        return $this;
    }
    
    /**
     * Return the character at the given index in the source code or null if the
     * end is reached.
     * 
     * @param int $index Index, if not given it will use the current index
     * 
     * @return string|null
     */
    public function charAt($index = null)
    {
        if ($index === null) {
            $index = $this->index;
        }
        return $index < $this->length ? $this->source[$index] : null;
    }
    
    /**
     * Throws a syntax error
     * 
     * @param string $message Error message
     * 
     * @return void
     * 
     * @throws Exception
     */
    protected function error($message = null)
    {
        if (!$message) {
            $message = "Unexpected " . $this->charAt();
        }
        throw new Exception($message, $this->getPosition(true));
    }
    
    /**
     * Consumes the current token
     * 
     * @return $this
     */
    public function consumeToken()
    {
        //Move the scanner position to the end of the current position
        $this->position = $this->currentToken->location->end;
        
        //Before consume the token, consume comments associated with it
        if ($this->comments) {
            $this->consumeCommentsForCurrentToken();
        }
        
        //Register the token if required
        if ($this->registerTokens) {
            $this->tokens[] = $this->currentToken;
        }
        
        //Emit the TokenConsumed event for the consumed token
        $this->eventsEmitter && $this->eventsEmitter->fire(
            "TokenConsumed", array($this->currentToken)
        );
        
        $this->currentToken = $this->nextToken;
        $this->nextToken = null;
        return $this;
    }
    
    /**
     * Checks if the given string is matched, if so it consumes the token
     * 
     * @param string $expected String to check
     * 
     * @return Token|null
     */
    public function consume($expected)
    {
        //Do not call getToken if there's already a pending token for
        //performance reasons
        $token = $this->currentToken ?: $this->getToken();
        if ($token && $token->value === $expected) {
            $this->consumeToken();
            return $token;
        }
        return null;
    }
    
    /**
     * Checks if one of the given strings is matched, if so it consumes the
     * token
     * 
     * @param array $expected Strings to check
     * 
     * @return Token|null
     */
    public function consumeOneOf($expected)
    {
        //Do not call getToken if there's already a pending token for
        //performance reasons
        $token = $this->currentToken ?: $this->getToken();
        if ($token && in_array($token->value, $expected)) {
            $this->consumeToken();
            return $token;
        }
        return null;
    }
    
    /**
     * Checks that there are not line terminators following the current scan
     * position before next token
     * 
     * @param bool $nextToken By default it checks the current token position
     *                        relative to the current position, if this
     *                        parameter is true the check will be done relative
     *                        to the next token
     * 
     * @return bool
     */
    public function noLineTerminators($nextToken = false)
    {
        if ($nextToken) {
            $nextToken = $this->getNextToken();
            $refLine = !$nextToken ? null :
                        $nextToken->location->end->getLine();
        } else {
            $refLine = $this->getPosition()->getLine();
        }
        $token = $this->currentToken ?: $this->getToken();
        return $token &&
               $token->location->start->getLine() === $refLine;
    }
    
    /**
     * Checks if one of the given strings follows the current scan position
     * 
     * @param string|array $expected  String or array of strings to check
     * @param bool         $nextToken This parameter must be true if the first
     *                                parameter is an array so that it will
     *                                check also next tokens
     * 
     * @return bool
     */
    public function isBefore($expected, $nextToken = false)
    {
        $token = $this->currentToken ?: $this->getToken();
        if (!$token) {
            return false;
        } elseif (in_array($token->value, $expected)) {
            return true;
        } elseif (!$nextToken) {
            return false;
        }
        if (!$this->getNextToken()) {
            return false;
        }
        foreach ($expected as $val) {
            if (!is_array($val) || $val[0] !== $token->value) {
                continue;
            }
            //If the second value in the array is true check that the current
            //token is not followed by line terminators, otherwise compare its
            //value to the next token
            if (($val[1] === true && $this->noLineTerminators(true)) ||
                ($val[1] !== true && $val[1] === $this->nextToken->value)) {
                return true;
            }
        }
        return false;
    }
    
    /**
     * Returns the next token
     * 
     * @return Token|null
     */
    public function getNextToken()
    {
        if (!$this->nextToken) {
            $token = $this->currentToken ?: $this->getToken();
            $this->currentToken = null;
            $this->nextToken = $this->getToken(true);
            $this->currentToken = $token;
        }
        return $this->nextToken;
    }
    
    /**
     * Returns the current token
     *
     * @param bool $skipEOFChecks  True to skip end of file checks
     *                             even if the end is reached
     * 
     * @return Token|null
     */
    public function getToken($skipEOFChecks = false)
    {
        //The current token is returned until consumed
        if ($this->currentToken) {
            return $this->currentToken;
        }
        
        $comments = $this->skipWhitespacesAndComments();
        
        //Emit the TokenCreated event for all the comments found
        if ($comments) {
            foreach ($comments as $comment) {
                $this->eventsEmitter && $this->eventsEmitter->fire(
                    "TokenCreated", array($comment)
                );
            }
        }

        //When the end of the source is reached
        if ($this->index >= $this->length) {
            //Check if there are open brackets
            if (!$skipEOFChecks) {
                foreach ($this->openBrackets as $bracket => $num) {
                    if ($num) {
                        $this->error("Unclosed $bracket");
                    }
                }

                //Check if there are open templates
                if (count($this->openTemplates)) {
                    $this->error("Unterminated template");
                }
            }
            
            //Register comments and consume them
            if ($this->comments && $comments) {
                $this->commentsForCurrentToken($comments);
            }
            
            //Emit the EndReached event when at the end of the source
            $this->eventsEmitter && $this->eventsEmitter->fire(
                "EndReached"
            );
            
            return null;
        }
        
        $startPosition = $this->getPosition(true);
        $origException = null;
        try {
            
            //Try to match a token
            if (
                ($this->jsx && ($token = $this->scanJSXIdentifier())) ||
                ($token = $this->scanTemplate()) ||
                ($token = $this->scanNumber()) ||
                ($this->jsx && ($token = $this->scanJSXPunctuator())) ||
                ($token = $this->scanPunctuator()) ||
                ($token = $this->scanKeywordOrIdentifier()) ||
                ($this->jsx && ($token = $this->scanJSXString())) ||
                ($token = $this->scanString())
            ) {
                //Set the token start and end positions
                $token->location->start = $startPosition;
                $token->location->end = $this->getPosition(true);
                $this->currentToken = $token;
                                            
                //Register comments if required
                if ($this->comments && $comments) {
                    $this->commentsForCurrentToken($comments);
                }
                
                //Emit the TokenCreated event for the token just created
                $this->eventsEmitter && $this->eventsEmitter->fire(
                    "TokenCreated", array($this->currentToken)
                );
                
                return $this->currentToken;
            }
            
        } catch (Exception $e) {
            $origException = $e;
        }
        
        //If last token was "/" do not throw an error if the token has not be
        //recognized since it can be the first character in a regexp and it will
        //be consumed when the current token will be reconsumed as a regexp
        if ($this->isAfterSlash($startPosition)) {
            $this->setScanPosition($startPosition);
            return null;
        }
        
        //No valid token found. If there was a scan error, throw the same
        //exception again, otherwise throw a new error
        if ($origException) {
            throw $origException;
        }
        $this->error();
    }
    
    /**
     * Executes the operations to handle the end of the source scanning
     * 
     * @return $this
     */
    public function consumeEnd()
    {
        //Consume final comments
        if ($this->comments) {
            $this->consumeCommentsForCurrentToken();
        }
        
        //Emit the EndReached event when at the end of the source
        $this->eventsEmitter && $this->eventsEmitter->fire(
            "EndReached"
        );
        
        return $this;
    }
    
    /**
     * Gets or sets comments for the current token. If the parameter is an
     * array it associates the given comments array to the current node,
     * otherwise comments for the current token are returned
     * 
     * @param array $comments  Comments array
     * 
     * @return array
     */
    protected function commentsForCurrentToken($comments = null)
    {
        $id = $this->currentToken ? spl_object_hash($this->currentToken) : "";
        if ($comments !== null) {
            $this->commentsMap[$id] = $comments;
        } elseif (isset($this->commentsMap[$id])) {
            $comments = $this->commentsMap[$id];
            unset($this->commentsMap[$id]);
        }
        return $comments;
    }
    
    /**
     * Consumes comment tokens associated with the current token
     * 
     * @return $this
     */
    protected function consumeCommentsForCurrentToken()
    {
        $comments = $this->commentsForCurrentToken();
        if ($comments && ($this->registerTokens || $this->eventsEmitter)) {
            foreach ($comments as $comment) {
                //Register the token if required
                if ($this->registerTokens) {
                    $this->tokens[] = $comment;
                }
                //Emit the TokenConsumed event for the comment
                $this->eventsEmitter && $this->eventsEmitter->fire(
                    "TokenConsumed", array($comment)
                );
            }
        }
        return $this;
    }
    
    /**
     * Checks if the given position follows a slash.
     * 
     * @param Position $position  Position to check
     * 
     * @return bool
     */
    protected function isAfterSlash($position)
    {
        //Start from the previous index and loop until the begin of the file is reached
        $idx = $position->getIndex() - 1;
        while ($idx >= 0) {
            //Get the char at the index to check
            $char = $this->charAt($idx);
            //If the char is actually a slash check that it's not a multiline comment closing slash
            if ($char === "/") {
                return $idx === 0 || $this->charAt($idx - 1) !== "*";
            }
            //Skip whitespaces but not line terminators since regexps can't be multiline
            elseif (in_array($char, $this->whitespaces) && !in_array($char, $this->lineTerminators)) {
                $idx--;
            }
            //If "/=" is found, return true, this is the only operator that needs a special treatment
            //since it can be also the start of a regex
            elseif ($char === "=" && $this->charAt($idx - 1) === "/") {
                return true;
            }
            //Different character, return
            else {
                break;
            }
        }
        return false;
    }
    
    /**
     * Tries to reconsume the current token as a regexp if possible
     * 
     * @return Token|null
     */
    public function reconsumeCurrentTokenAsRegexp()
    {
        $token = $this->currentToken ?: $this->getToken();
        $value = $token ? $token->value : null;
        
        //Check if the token starts with "/"
        if (!$value || $value[0] !== "/") {
            return null;
        }
        
        //Reset the scanner position to the token's start position
        $startPosition = $token->location->start;
        $this->setScanPosition($startPosition);
        
        $buffer = "/";
        $this->index++;
        $this->column++;
        $inClass = false;
        while (true) {
            //In a characters class the delimiter "/" is allowed without escape,
            //so the characters class must be closed before closing the regexp
            $stops = $inClass ? array("]") : array("/", "[");
            $tempBuffer = $this->consumeUntil($stops);
            if ($tempBuffer === null) {
                if ($inClass) {
                    $this->error(
                        "Unterminated character class in regexp"
                    );
                } else {
                    $this->error("Unterminated regexp");
                }
            }
            $buffer .= $tempBuffer[0];
            if ($tempBuffer[1] === "/") {
                break;
            } else {
                $inClass = $tempBuffer[1] === "[";
            }
        }
        
        //Flags
        while (($char = $this->charAt()) !== null) {
            $lower = strtolower($char);
            if ($lower >= "a" && $lower <= "z") {
                $buffer .= $char;
                $this->index++;
                $this->column++;
            } else {
                break;
            }
        }
        
        //If next token has already been parsed and it's a bracket exclude it
        //from the count of open brackets
        if ($this->nextToken) {
            $nextVal = $this->nextToken->value;
            if (isset($this->brackets[$nextVal]) &&
                isset($this->openBrackets[$nextVal])
            ) {
                if ($this->brackets[$nextVal]) {
                    $this->openBrackets[$nextVal]++;
                } else {
                    $this->openBrackets[$nextVal]--;
                }
            }
            $this->nextToken = null;
        }
        
        //If comments handling is enabled, get the comments associated with the
        //current token
        $comments = $this->comments ? $this->commentsForCurrentToken() : null;
            
        //Replace the current token with a regexp token
        $token = new Token(Token::TYPE_REGULAR_EXPRESSION, $buffer);
        $token->location->start = $startPosition;
        $token->location->end = $this->getPosition(true);
        $this->currentToken = $token;
                                    
        if ($comments) {
            //Attach the comments to the new current token
            $this->commentsForCurrentToken($comments);
        }
        
        return $this->currentToken;
    }
    
    /**
     * Skips whitespaces and comments from the current scan position. If
     * comments handling is enabled, the array of parsed comments
     * 
     * @return array
     */
    protected function skipWhitespacesAndComments()
    {
        $comments = [];
        $content = "";
        $secStartIdx = $this->index;
        while (($char = $this->charAt()) !== null) {
            //Whitespace
            if (in_array($char, $this->whitespaces)) {
                
                $content .= $char;
                $this->index++;
                
            } elseif ($char === "/" || $char === "#") {

                $nextChar = $this->charAt($this->index + 1);

                if ($char === "#") {
                    //Hashbang comment. This will be parsed only if hashbang comments are enabled
                    //and if it appears at the beginning of the code
                    $valid = $nextChar === "!" && $this->features->hashbangComments && !$this->index;
                } else {
                    $valid = $nextChar === "/" || $nextChar === "*";
                }

                //Comment
                if ($valid) {
                    
                    //If comments must be handled, empty the current content too
                    //and get the comment start position
                    if ($this->comments) {
                        if ($content !== "") {
                            $this->adjustColumnAndLine($content);
                            $content = "";
                        }
                        $start = $this->getPosition(true);
                    }
                    
                    $inline = $nextChar !== "*";
                    $this->index += 2;
                    $content .= $char . $nextChar;
                    
                    while (true) {
                        $char = $this->charAt();
                        
                        if ($char === null) {
                            if (!$inline) {
                                //If the end of the source has been reached and
                                //a multiline comment is still open, it's an
                                //error
                                $this->error("Unterminated comment");
                            }
                            $isEnd = true;
                        } else {
                            $content .= $char;
                            $this->index++;
                            $isEnd = $inline ?
                                     //Inline comment
                                     in_array($char, $this->lineTerminators) :
                                     //Multiline comment
                                     $char === "*" && $this->charAt() === "/";
                        }
                        
                        if ($isEnd) {
                            if (!$inline) {
                                $content .= "/";
                                $this->index++;
                            }
                            if ($this->comments) {
                                //For inline comments the closing line
                                //terminator must be excluded from comment text
                                if ($inline && $char !== null) {
                                    $this->index--;
                                    $content = substr($content, 0, -strlen($char));
                                }
                                $this->adjustColumnAndLine($content);
                                $token = new Token(Token::TYPE_COMMENT, $content);
                                $token->location->start = $start;
                                $token->location->end = $this->getPosition(true);
                                $comments[] = $token;
                                //For inline comments the new content contains
                                //the closing line terminator since the char has
                                //already been processed
                                $content = "";
                                if ($inline && $char !== null) {
                                    $content = $char;
                                    $this->index++;
                                }
                            }
                            break;
                        }
                    }
                    
                } else {
                    break;
                }
                
            } elseif (!$this->isModule && $char === "<" &&
                $this->charAt($this->index + 1) === "!" &&
                $this->charAt($this->index + 2) === "-" &&
                $this->charAt($this->index + 3) === "-"
            ) {
                
                //If comments must be handled, empty the current content too
                //and get the comment start position
                if ($this->comments) {
                    if ($content !== "") {
                        $this->adjustColumnAndLine($content);
                        $content = "";
                    }
                    $start = $this->getPosition(true);
                }
                
                //Open html comment
                $this->index += 4;
                $content .= "<!--";
                while (true) {
                    $char = $this->charAt();
                    if ($char === null) {
                        $isEnd = true;
                    } else {
                        $content .= $char;
                        $this->index++;
                        $isEnd = in_array($char, $this->lineTerminators);
                    }
                    if ($isEnd) {
                        if ($this->comments) {
                            //Remove the closing line terminator from the
                            //comment text
                            if ($char !== null) {
                                $this->index--;
                                $content = substr($content, 0, -strlen($char));
                            }
                            $this->adjustColumnAndLine($content);
                            $token = new Token(Token::TYPE_COMMENT, $content);
                            $token->location->start = $start;
                            $token->location->end = $this->getPosition(true);
                            $comments[] = $token;
                            $content = "";
                            if ($char !== null) {
                                $content = $char;
                                $this->index++;
                            }
                        }
                        break;
                    }
                }
                
            } elseif (!$this->isModule && $char === "-" &&
                $this->charAt($this->index + 1) === "-" &&
                $this->charAt($this->index + 2) === ">"
            ) {
                
                //Close html comment
                //Check if it is on it's own line
                $allow = false;
                if (!$secStartIdx) {
                    $allow = true;
                } else {
                    for ($index = $this->index - 1; $index >= $secStartIdx; $index--) {
                        if (in_array($this->charAt($index), $this->lineTerminators)) {
                            $allow = true;
                            break;
                        }
                    }
                }
                if ($allow) {
                    
                    //If comments must be handled, empty the current content too
                    //and get the comment start position
                    if ($this->comments) {
                        if ($content !== "") {
                            $this->adjustColumnAndLine($content);
                            $content = "";
                        }
                        $start = $this->getPosition(true);
                    }
                    
                    $this->index += 3;
                    $content .= "-->";
                    while (true) {
                        $char = $this->charAt();
                        
                        if ($char === null) {
                            $isEnd = true;
                        } else {
                            $content .= $char;
                            $this->index++;
                            $isEnd = in_array($char, $this->lineTerminators);
                        }
                        
                        if ($isEnd) {
                            if ($this->comments) {
                                //Remove the closing line terminator from the
                                //comment text
                                if ($char !== null) {
                                    $this->index--;
                                    $content = substr($content, 0, -strlen($char));
                                }
                                $this->adjustColumnAndLine($content);
                                $token = new Token(Token::TYPE_COMMENT, $content);
                                $token->location->start = $start;
                                $token->location->end = $this->getPosition(true);
                                $comments[] = $token;
                                $content = "";
                                if ($char !== null) {
                                    $content = $char;
                                    $this->index++;
                                }
                            }
                            break;
                        }
                    }
                } else {
                    break;
                }
                
            } else {
                break;
            }
        }
        
        if ($content !== "") {
            $this->adjustColumnAndLine($content);
        }
        
        return $comments;
    }
    
    /**
     * String scanning method
     * 
     * @param bool $handleEscape True to handle escaping
     * 
     * @return Token|null
     */
    protected function scanString($handleEscape = true)
    {
        $char = $this->charAt();
        if ($char === "'" || $char === '"') {
            $this->index++;
            $this->column++;
            //Add the quote to the LSM and then remove it after consuming
            $this->stringsStopsLSM->add($char);
            $buffer = $this->consumeUntil($this->stringsStopsLSM, $handleEscape);
            $this->stringsStopsLSM->remove($char);
            if ($buffer === null || $buffer[1] !== $char) {
                $this->error("Unterminated string");
            }
            return new Token(Token::TYPE_STRING_LITERAL, $char . $buffer[0]);
        }
        
        return null;
    }
    
    /**
     * Template scanning method
     * 
     * @return Token|null
     */
    protected function scanTemplate()
    {
        $char = $this->charAt();
        
        //Get the current number of open curly brackets
        $openCurly = isset($this->openBrackets["{"]) ?
                     $this->openBrackets["{"] :
                     0;
        
        //If the character is a curly bracket check and the number of open
        //curly brackets matches the last number in the open templates stack,
        //then the bracket closes the open template expression
        $endExpression = false;
        if ($char === "}") {
            $len = count($this->openTemplates);
            if ($len && $this->openTemplates[$len - 1] === $openCurly) {
                $endExpression = true;
                array_pop($this->openTemplates);
            }
        }
        
        if ($char === "`" || $endExpression) {
            $this->index++;
            $this->column++;
            $buffer = $char;
            while (true) {
                $tempBuffer = $this->consumeUntil(array("`", "$"));
                if (!$tempBuffer) {
                    $this->error("Unterminated template");
                }
                $buffer .= $tempBuffer[0];
                if ($tempBuffer[1] !== "$" || $this->charAt() === "{") {
                    //If "${" is found it's a new template expression, register
                    //the current number of open curly brackets in the open
                    //templates stack
                    if ($tempBuffer[1] === "$") {
                        $this->index++;
                        $this->column++;
                        $buffer .= "{";
                        $this->openTemplates[] = $openCurly;
                    }
                    break;
                }
            }
            return new Token(Token::TYPE_TEMPLATE, $buffer);
        }
        
        return null;
    }
    
    /**
     * Number scanning method
     * 
     * @return Token|null
     */
    protected function scanNumber()
    {
        //Numbers can start with a decimal number or with a dot (.5)
        $char = $this->charAt();
        if (!(($char >= "0" && $char <= "9") || $char === ".")) {
            return null;
        }

        $buffer = "";
        $allowedDecimals = true;
        
        //Parse the integer part
        if ($char !== ".") {
            
            //Consume all decimal numbers
            $buffer = $this->consumeNumbers();
            $char = $this->charAt();
            
            if ($this->features->bigInt && $char === "n") {
                $this->index++;
                $this->column++;
                return new Token(Token::TYPE_BIGINT_LITERAL, $buffer . $char);
            }
            
            $lower = $char !== null ? strtolower($char) : null;
            
            //Handle hexadecimal (0x), octal (0o) and binary (0b) forms
            if ($buffer === "0" && $lower !== null &&
                isset($this->{$lower . "numbers"})
            ) {
                
                $this->index++;
                $this->column++;
                $tempBuffer = $this->consumeNumbers($lower);
                if ($tempBuffer === null) {
                    $this->error("Missing numbers after 0$char");
                }
                $buffer .= $char . $tempBuffer;
                
                //Check that there are not numbers left
                if ($this->consumeNumbers() !== null) {
                    $this->error();
                }

                if ($this->features->bigInt && $this->charAt() === "n") {
                    $this->index++;
                    $this->column++;
                    return new Token(Token::TYPE_BIGINT_LITERAL, $buffer . $char);
                }
                
                return new Token(Token::TYPE_NUMERIC_LITERAL, $buffer);
            }
            
            //Consume exponent part if present
            if ($tempBuffer = $this->consumeExponentPart()) {
                $buffer .= $tempBuffer;
                $allowedDecimals = false;
            }
        }
        
        //Parse the decimal part
        if ($allowedDecimals && $this->charAt() === ".") {
            
            //Consume the dot
            $this->index++;
            $this->column++;
            $buffer .= ".";
            
            //Consume all decimal numbers
            $tempBuffer = $this->consumeNumbers();
            $buffer .= $tempBuffer;
            
            //If the buffer contains only the dot it should be parsed as
            //punctuator
            if ($buffer === ".") {
                $this->index--;
                $this->column--;
                return null;
            }
            
            //Consume exponent part if present
            if (($tempBuffer = $this->consumeExponentPart()) !== null) {
                $buffer .= $tempBuffer;
            }
        }
        
        return new Token(Token::TYPE_NUMERIC_LITERAL, $buffer);
    }
    
    /**
     * Consumes the maximum number of digits
     * 
     * @param string $type Digits type (decimal, hexadecimal, etc...)
     * @param int    $max  Maximum number of digits to match
     * 
     * @return string|null
     */
    protected function consumeNumbers($type = "", $max = null)
    {
        $buffer = "";
        $char = $this->charAt();
        $count = 0;
        $extra = $this->features->numericLiteralSeparator ? "_" : "";
        while (
            in_array($char, $this->{$type . "numbers"}) ||
            ($count && $char === $extra)
        ) {
            $buffer .= $char;
            $this->index++;
            $this->column++;
            $count ++;
            if ($count === $max) {
                break;
            }
            $char = $this->charAt();
        }
        if ($count && substr($buffer, -1) === "_") {
            $this->error(
                "Numeric separators are not allowed at the end of a number"
            );
        }
        return $count ? $buffer : null;
    }
    
    /**
     * Consumes the exponent part of a number
     * 
     * @return string|null
     */
    protected function consumeExponentPart()
    {
        $buffer = "";
        $char = $this->charAt();
        if ($char !== null && strtolower($char) === "e") {
            $this->index++;
            $this->column++;
            $buffer .= $char;
            $char = $this->charAt();
            if ($char === "+" || $char === "-") {
                $this->index++;
                $this->column++;
                $buffer .= $char;
            }
            $tempBuffer = $this->consumeNumbers();
            if ($tempBuffer === null) {
                $this->error("Missing exponent");
            }
            $buffer .= $tempBuffer;
        }
        return $buffer;
    }
    
    /**
     * Punctuator scanning method
     * 
     * @return Token|null
     */
    protected function scanPunctuator()
    {
        $token = null;
        $char = $this->charAt();
        
        //Check if the next char is a bracket
        if (isset($this->brackets[$char])) {
            //Check if it is a closing bracket
            if ($this->brackets[$char]) {
                $openBracket = $this->brackets[$char];
                //Check if there is a corresponding open bracket
                if (!isset($this->openBrackets[$openBracket]) ||
                    !$this->openBrackets[$openBracket]
                ) {
                    if (!$this->isAfterSlash($this->getPosition(true))) {
                        $this->error();
                    }
                } else {
                    $this->openBrackets[$openBracket]--;
                }
            } else {
                if (!isset($this->openBrackets[$char])) {
                    $this->openBrackets[$char] = 0;
                }
                $this->openBrackets[$char]++;
            }
            $this->index++;
            $this->column++;
            $token = new Token(Token::TYPE_PUNCTUATOR, $char);
        } elseif (
            //Try to match the longest punctuator
            $match = $this->punctuatorsLSM->match($this, $this->index, $char)
        ) {
            //Optional chaining punctuator cannot appear before a number, in this
            //case only the question mark must be consumed
            if ($match[1] === "?." &&
                ($nextChar = $this->charAt($this->index + $match[0])) !== null &&
                $nextChar >= "0" && $nextChar <= "9"
            ) {
                $match = array(1, "?");
            }
            $this->index += $match[0];
            $this->column += $match[0];
            $token = new Token(Token::TYPE_PUNCTUATOR, $match[1]);
        }
        return $token;
    }
    
    /**
     * Keywords and identifiers scanning method
     * 
     * @return Token|null
     */
    protected function scanKeywordOrIdentifier()
    {
        //Check private identifier start character
        if ($private = $this->features->privateMethodsAndFields && $this->charAt() === "#") {
            $this->index++;
            $this->column++;
        }

        //Consume the maximum number of characters that are unicode escape
        //sequences or valid identifier starts (only the first character) or
        //parts
        $buffer = "";
        $start = true;
        while (($char = $this->charAt()) !== null) {
            if (
                ($char >= "a" && $char <= "z") ||
                ($char >= "A" && $char <= "Z") ||
                $char === "_" || $char === "$" ||
                (!$start && $char >= "0" && $char <= "9") ||
                $this->isIdentifierChar($char, $start)
            ) {
                $buffer .= $char;
                $this->index++;
                $this->column++;
            } elseif ($char === "\\" && ($seq = $this->consumeUnicodeEscapeSequence())) {
                //Verify that it's a valid character
                if (!$this->isIdentifierChar($seq[1], $start)) {
                    break;
                }
                $buffer .= $seq[0];
            } else {
                break;
            }
            $start = false;
        }
        
        //Identify token type
        if ($buffer === "") {
            //Unconsume the hash if nothing was found after that
            if ($private) {
                $this->index--;
                $this->column--;
            }
            return null;
        } elseif ($private) {
            $type = Token::TYPE_PRIVATE_IDENTIFIER;
            $buffer = "#" . $buffer;
        } elseif ($buffer === "null") {
            $type = Token::TYPE_NULL_LITERAL;
        } elseif ($buffer === "true" || $buffer === "false") {
            $type = Token::TYPE_BOOLEAN_LITERAL;
        } elseif (in_array($buffer, $this->keywords) ||
            in_array($buffer, $this->strictModeKeywords)
        ) {
            $type = Token::TYPE_KEYWORD;
        } else {
            $type = Token::TYPE_IDENTIFIER;
        }
        
        return new Token($type, $buffer);
    }
    
    /**
     * Consumes an unicode escape sequence
     * 
     * @return array|null
     */
    protected function consumeUnicodeEscapeSequence()
    {
        if ($this->charAt() !== "\\" ||
            $this->charAt($this->index + 1) !== "u"
        ) {
            return null;
        }
        
        $startIndex = $this->index;
        $startColumn = $this->column;
        $this->index += 2;
        $this->column += 2;
        $brackets = false;
        if ($this->charAt() === "{") {
            //\u{FFF}
            $brackets = true;
            $this->index++;
            $this->column++;
            $code = $this->consumeNumbers("x");
            if ($code && $this->charAt() !== "}") {
                $code = null;
            } else {
                $this->index++;
                $this->column++;
            }
        } else {
            //\uFFFF
            $code = $this->consumeNumbers("x", 4);
            if ($code && strlen($code) !== 4) {
                $code = null;
            }
        }
        
        //Unconsume everything if the format is invalid
        if ($code === null) {
            $this->index = $startIndex;
            $this->column = $startColumn;
            return null;
        }
        
        //Return an array where the first element is the matched sequence
        //and the second one is the decoded character
        return array(
            $brackets ? "\\u{" . $code . "}" : "\\u" . $code,
            Utils::unicodeToUtf8(hexdec($code))
        );
    }
    
    /**
     * Checks if the given character is valid for an identifier
     * 
     * @param string $char  Character to check
     * @param bool   $start If true it will check that the character is
     *                      valid to start an identifier
     * 
     * @return bool
     */
    protected function isIdentifierChar($char, $start = true)
    {
        return ($char >= "a" && $char <= "z") ||
               ($char >= "A" && $char <= "Z") ||
               $char === "_" || $char === "$" ||
               (!$start && $char >= "0" && $char <= "9") ||
               preg_match($start ? $this->idStartRegex : $this->idPartRegex, $char);
    }
    
    /**
     * Increases columns and lines count according to the given string
     * 
     * @param string $buffer String to analyze
     * 
     * @return void
     */
    protected function adjustColumnAndLine($buffer)
    {
        $lines = preg_split($this->linesSplitter, $buffer);
        $linesCount = count($lines) - 1;
        $this->line += $linesCount;
        $columns = mb_strlen($lines[$linesCount], "UTF-8");
        if ($linesCount) {
            $this->column = $columns;
        } else {
            $this->column += $columns;
        }
    }
    
    /**
     * Consumes characters until one of the given characters is found
     * 
     * @param array|LSM $stops          Characters to search
     * @param bool      $handleEscape   True to handle escaping
     * @param bool      $collectStop    True to include the stop character
     * 
     * @return array|null
     */
    protected function consumeUntil(
        $stops, $handleEscape = true, $collectStop = true
    ) {
        $isLSM = $stops instanceof LSM;
        $buffer = "";
        $escaped = false;
        while (($char = $this->charAt()) !== null) {
            $incrIndex = 1;
            $isStop = false;
            if ($isLSM) {
                $m = $stops->match($this, $this->index, $char);
                if ($m) {
                    $isStop = true;
                    $incrIndex = $m[0];
                    $char = $m[1];
                }
            } else {
                $isStop = in_array($char, $stops);
            }
            $validStop = $isStop && !$escaped;
            if (!$validStop || $collectStop) {
                $this->index += $incrIndex;
                $buffer .= $char;
            }
            if ($validStop) {
                if (!$collectStop && $buffer === "") {
                    return null;
                }
                $this->adjustColumnAndLine($buffer);
                return array($buffer, $char);
            } elseif (!$escaped && $char === "\\" && $handleEscape) {
                $escaped = true;
            } else {
                $escaped = false;
            }
        }
        return null;
    }
}