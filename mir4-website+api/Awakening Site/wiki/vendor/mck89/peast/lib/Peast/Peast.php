<?php
/**
 * This file is part of the Peast package
 *
 * (c) Marco Marchiò <marco.mm89@gmail.com>
 *
 * For the full copyright and license information refer to the LICENSE file
 * distributed with this source code
 */
namespace Peast;

/**
 * Main class of Peast library.
 * Every function of this class takes two arguments:
 * - The source code to parse
 * - The options array that is an associative array of parser settings.
 *   Available options are:
 *      - "sourceType": one of the source type constants declared in this class.
 *        This option tells the parser to parse the source in script or module
 *        mode. If this option is not provided the parser will work in script
 *        mode.
 *      - "sourceEncoding": the encoding of the source. If not specified the
 *        parser will assume UTF-8.
 *      - "strictEncoding": if false the parser will handle invalid UTF8
 *        characters in the source code by replacing them with the character
 *        defined in the "mbstring.substitute_character" ini setting, otherwise
 *        it will throw an exception.
 *      - "comments": if true it enables comments parsing.
 *      - "jsx": if true it enables parsing of JSX syntax.
 * 
 * @method static Syntax\Parser ES2015(string $source, array $options = array())
 * Returns a parser instance with ES2015 features for the given source. See Peast
 * class documentation to understand the function arguments.
 * 
 * @method static Syntax\Parser ES6(string $source, array $options = array())
 * Returns a parser instance with ES2015 features for the given source. See Peast
 * class documentation to understand function arguments.
 * 
 * @method static Syntax\Parser ES2016(string $source, array $options = array())
 * Returns a parser instance with ES2016 features for the given source. See Peast
 * class documentation to understand function arguments.
 * 
 * @method static Syntax\Parser ES7(string $source, array $options = array())
 * Returns a parser instance with ES2016 features for the given source. See Peast
 * class documentation to understand function arguments.
 * 
 * @method static Syntax\Parser ES2017(string $source, array $options = array())
 * Returns a parser instance with ES2017 features for the given source. See Peast
 * class documentation to understand function arguments.
 * 
 * @method static Syntax\Parser ES8(string $source, array $options = array())
 * Returns a parser instance with ES2017 features for the given source. See Peast
 * class documentation to understand function arguments.
 * 
 * @method static Syntax\Parser ES2018(string $source, array $options = array())
 * Returns a parser instance with ES2018 features for the given source. See Peast
 * class documentation to understand function arguments.
 * 
 * @method static Syntax\Parser ES9(string $source, array $options = array())
 * Returns a parser instance with ES2018 features for the given source. See Peast
 * class documentation to understand function arguments.
 * 
 * @method static Syntax\Parser ES2019(string $source, array $options = array())
 * Returns a parser instance with ES2019 features for the given source. See Peast
 * class documentation to understand function arguments.
 * 
 * @method static Syntax\Parser ES10(string $source, array $options = array())
 * Returns a parser instance with ES2019 features for the given source. See Peast
 * class documentation to understand function arguments.
 *
 * @method static Syntax\Parser ES2020(string $source, array $options = array())
 * Returns a parser instance with ES2020 features for the given source. See Peast
 * class documentation to understand function arguments.
 *
 * @method static Syntax\Parser ES11(string $source, array $options = array())
 * Returns a parser instance with ES2020 features for the given source. See Peast
 * class documentation to understand function arguments.
 *
 * @method static Syntax\Parser ES2021(string $source, array $options = array())
 * Returns a parser instance with ES2021 features for the given source. See Peast
 * class documentation to understand function arguments.
 *
 * @method static Syntax\Parser ES12(string $source, array $options = array())
 * Returns a parser instance with ES2021 features for the given source. See Peast
 * class documentation to understand function arguments.
 *
 * @method static Syntax\Parser ES2022(string $source, array $options = array())
 * Returns a parser instance with ES2022 features for the given source. See Peast
 * class documentation to understand function arguments.
 *
 * @method static Syntax\Parser ES13(string $source, array $options = array())
 * Returns a parser instance with ES2022 features for the given source. See Peast
 * class documentation to understand function arguments.
 *
 * @method static Syntax\Parser ES2023(string $source, array $options = array())
 * Returns a parser instance with ES2023 features for the given source. See Peast
 * class documentation to understand function arguments.
 *
 * @method static Syntax\Parser ES14(string $source, array $options = array())
 * Returns a parser instance with ES2023 features for the given source. See Peast
 * class documentation to understand function arguments.
 *
 * @method static Syntax\Parser ES2024(string $source, array $options = array())
 * Returns a parser instance with ES2024 features for the given source. See Peast
 * class documentation to understand function arguments.
 *
 * @method static Syntax\Parser ES15(string $source, array $options = array())
 * Returns a parser instance with ES2024 features for the given source. See Peast
 * class documentation to understand function arguments.
 *
 * @method static Syntax\Parser latest(string $source, array $options = array())
 * Returns an instance of the latest parser version for the given source. See
 * Peast class documentation to understand function arguments.
 * 
 * @author Marco Marchiò <marco.mm89@gmail.com>
 */
class Peast
{
    //Source type constants
    /**
     * This source type indicates that the source is a script and import
     * and export keywords are not parsed.
     */
    const SOURCE_TYPE_SCRIPT = "script";
    
    /**
     * This source type indicates that the source is a module, this enables
     * the parsing of import and export keywords.
     */
    const SOURCE_TYPE_MODULE = "module";
    
    /**
     * Valid versions and aliases
     * 
     * @var array
     */
    static protected $versions = array(
        "ES6" => "ES2015",
        "ES7" => "ES2016",
        "ES8" => "ES2017",
        "ES9" => "ES2018",
        "ES10" => "ES2019",
        "ES11" => "ES2020",
        "ES12" => "ES2021",
        "ES13" => "ES2022",
        "ES14" => "ES2023",
        "ES15" => "ES2024"
    );
    
    /**
     * Magic method that exposes all the functions to access parser with
     * specific features
     * 
     * @param string    $version   Parser version
     * @param array     $args      Parser arguments
     * 
     * @return Syntax\Parser
     * 
     * @throws \Exception
     */
    public static function __callStatic($version, $args)
    {
        $source = $args[0];
        $options = isset($args[1]) ? $args[1] : array();
        
        if (!in_array($version, self::$versions)) {
            if ($version === "latest") {
                $version = end(self::$versions);
            } elseif (isset(self::$versions[$version])) {
                $version = self::$versions[$version];
            } else {
                throw new \Exception("Invalid version $version");
            }
        }
        
        $featuresClass = "\\Peast\\Syntax\\$version\\Features";
        return new Syntax\Parser(
            $source, new $featuresClass, $options
        );
    }
}