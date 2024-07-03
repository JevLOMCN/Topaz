<?php
/**
 * This file is part of the Peast package
 *
 * (c) Marco Marchiò <marco.mm89@gmail.com>
 *
 * For the full copyright and license information refer to the LICENSE file
 * distributed with this source code
 */
namespace Peast\Syntax\JSX;

use Peast\Syntax\Token;

/**
 * JSX scanner trait
 * 
 * @author Marco Marchiò <marco.mm89@gmail.com>
 */
trait Scanner
{
    /**
     * Tries to reconsume the current token as a jsx text if possible
     * 
     * @return Token|null
     */
    public function reconsumeCurrentTokenAsJSXText()
    {
        //Current and next tokens must be reset and the open brackets count must be correct
        //like they were never scanned
        foreach (array($this->currentToken, $this->nextToken) as $token) {
            if ($token && isset($this->brackets[$token->value])) {
                if ($refBracket = $this->brackets[$token->value]) {
                    $this->openBrackets[$refBracket]++;
                } else {
                    $this->openBrackets[$token->value]--;
                }
            }
        }
        $this->nextToken = null;
        $this->currentToken = null;

        $startPosition = $this->getPosition();
        $this->setScanPosition($startPosition);
        $result = $this->consumeUntil(array("{", "<"), false, false);
        if ($result) {
            $this->currentToken = new Token(Token::TYPE_JSX_TEXT, $result[0]);
            $this->currentToken->location->start = $startPosition;
            $this->currentToken->location->end = $this->getPosition(true);
        }
        return $this->currentToken;
    }
    
    
    /**
     * Reconsumes the current token in jsx mode
     * 
     * @return Token|null
     */
    public function reconsumeCurrentTokenInJSXMode()
    {
        $this->jsx = true;
        $this->nextToken = null;
        $this->currentToken = null;
        $startPosition = $this->getPosition();
        $this->setScanPosition($startPosition);
        $token = $this->getToken();
        $this->jsx = false;
        return $token;
    }
    
    /**
     * String scanning method in jsx mode
     * 
     * @return Token|null
     */
    public function scanJSXString()
    {
        return $this->scanString(false);
    }
    
    /**
     * String punctuator method in jsx mode
     * 
     * @return Token|null
     */
    public function scanJSXPunctuator()
    {
        //The ">" character in jsx mode must be emitted in its own token
        //without matching longer sequences like ">>"
        $char = $this->charAt();
        if ($char === ">") {
            $this->index++;
            $this->column++;
            return new Token(Token::TYPE_PUNCTUATOR, $char);
        }
        return $this->scanPunctuator();
    }
    
    /**
     * Identifier scanning method in jsx mode
     * 
     * @return Token|null
     */
    public function scanJSXIdentifier()
    {
        $buffer = "";
        $char = $this->charAt();
        if ($char !== null && $this->isIdentifierChar($char)) {
            
            do {
                $buffer .= $char;
                $this->index++;
                $this->column++;
                $char = $this->charAt();
            } while (
                $char !== null &&
                ($this->isIdentifierChar($char, false) || $char === "-")
            );
        }
        
        return $buffer === "" ? null : new Token(Token::TYPE_JSX_IDENTIFIER, $buffer);
    }
}
