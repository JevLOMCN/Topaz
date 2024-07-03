/** PEGjs lexer/parser */
/**
*
* This implements the intent grammar from W3C (as of 15.08.23) from:
* https://www.w3.org/TR/mathml4/#mixing_intent_grammar
*
* intent             := concept-or-literal | number | reference | application | structure
* concept-or-literal := NCName
* number             := '-'? digit+ ( '.' digit+ )?
* reference          := '$' NCName
* structure          := ':' ('common' | 'structure' | 'chemistry' | 'equations' )
* application        := intent hint? '(' arguments? ')'
* arguments          := intent ( ',' intent )*
* hint               := '@' ( 'prefix' | 'infix' | 'postfix' | 'function' | 'silent' )
*
* Added 'structure'-macros intents like in W3C Examples to the grammar
* https://github.com/w3c/mathml-docs/blob/main/intent-examples/examples.html
*
* The applications condition is implemented a bit differently, repetitive arguments in parenthesis are
* allowed i.e f(a)(b), this enables checking for applications condition without infinite loop
* warning by PEG interpreter.
*
* Also added hints like "decimal-comma", "thousands-comma" for number
*
* author: Johannes Stegmüller
**/

start =  application / intent
intent = concept_or_literal / number / reference / structure
concept_or_literal = NCName {
    return $this->text();
}
number = sign:("-"?)
        intPart:digits
        fracPart:("." digits)?
        hint:hint?
        {
            $intPartS = implode($intPart ?? []);
            $fracPartS = implode($fracPart[1] ?? []);
            $value = $sign ?? "";
            $value .= $intPartS;
            $value .= isset($fracPart) ? ("." . $fracPartS ) : "";
            return ["type" => "number", "value" => $value, "hint"=>$hint];
        }
digits = digit+
digit = [0-9]
reference = "$" NCName {
	return substr($this->text(),1);
}
structure = ":" ("common" / "structure" / "chemistry" /"matrix" / "equations" / "chemical-element" / "chemical-formula" / "chemical-equation")
{
	return substr($this->text(),1);
}
application = intent:intent hint:hint? args:("(" WS? arguments:arguments? WS? ")")+
{
    $argsF = [];
    foreach ($args as $arg) {
       $argsF[] = $arg[2];
    }
   $returnObj =  [
         "type" => "application",
         "intent" => $intent,
         "hint" => isset($hint) ? $hint : null,
         "arguments" => $argsF
     ];
   return $returnObj;
}
arguments = WS? first:start WS? rest:(","  WS? start WS?)*
{
      $args = [$first];
      foreach ($rest as $arg) {
          $args[] = $arg[2];
      }
      return $args;
}
hint = ("@"/":") hintType:("prefix" / "infix" / "postfix" / "function" / "silent" / "decimal-comma" /"thousands-comma")
{
    return $hintType;
}
NCName = !":" [a-zA-Z_] [a-zA-Z0-9._-]* { return $this->text(); }
WS = " " {return " "; }