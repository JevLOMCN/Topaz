# Equivset

A mapping of "equivalent" or similar-looking characters ([homoglyphs](https://en.wikipedia.org/wiki/Homoglyph)) to prevent spoofing. This is similar to the Unicode Consortium's [confusables.txt](http://www.unicode.org/Public/security/8.0.0/confusables.txt) with some signficant differences. Confusables.txt lists character pairs that are visually identical or nearly identical, for example, Latin "A" and Greek "Α" (alpha). This list is much broader, including pairs that merely look similar, for example, "S" and "$". Another difference is that this list only includes letters and punctuation. It does not include symbols, emoji, or graphical elements.

## Installation
Using composer:
Add the following to the composer.json file for your project:

```json
{
  "require": {
     "wikimedia/equivset": "^1.0.0"
  }
}
```

And then run 'composer update'.

## Usage

```php
use Wikimedia\Equivset\Equivset;

$equivset = new Equivset();

// Normalize a string
echo $equivset->normalize( 'sp00f' ); // SPOOF

// Get a single character.
if ( $equivset->has( 'ɑ' ) ) {
	$char = $equivset->get( 'ɑ' );
}
echo $char; // A

// Loop over entire set.
foreach ( $equivset as $char => $equiv ) {
	// Do something.
}

// Get the entire set.
$all = $equivset->all();
```

## Contributing

All changes should be made to `./data/equivset.in`. Then run
`bin/console generate-equivset` to generate the JSON, serialized, and plain
text versions of the equivset in `./dist`.
