[![Latest Stable Version]](https://packagist.org/packages/wikimedia/json-codec) [![License]](https://packagist.org/packages/wikimedia/json-codec)

JsonCodec
=====================

Interfaces to serialize and deserialize PHP objects to/from JSON.

Additional documentation about this library can be found on
[mediawiki.org](https://www.mediawiki.org/wiki/JsonCodec).


Usage
-----

To make an object serializable/deserializable to/from JSON, the
simplest way is to use the `JsonCodecableTrait` and implement two
methods in your class, `toJsonArray()` and the static method
`newFromJsonArray()`:
```php
use Wikimedia\JsonCodec\JsonCodecable;

class SampleObject implements JsonCodecable {
	use JsonCodecableTrait;

	/** @var string */
	public string $property;

	// ....

	// Implement JsonCodecable using the JsonCodecableTrait

	/** @inheritDoc */
	public function toJsonArray(): array {
		return [
			'property' => $this->property,
		];
	}

	/** @inheritDoc */
	public static function newFromJsonArray( array $json ): SampleObject {
		return new SampleObject( $json['property'] );
	}
}
```
A slightly more complicated version of this example can be found in
[`tests/SampleObject.php`](./tests/SampleObject.php).

If your class requires explicit management -- for example, object
instances need to be created using a factory service, you can
implement `JsonCodecable` directly:
```php
use Wikimedia\JsonCodec\JsonCodecable;

class ManagedObject implements JsonCodecable {
	public static function jsonClassCodec( ContainerInterface $serviceContainer ) {
		$factory = $serviceContainer->get( 'MyObjectFactory' );
		return new class( $factory ) implements JsonClassCodec {
			// ...
			public function toJsonArray( $obj ): array {
				// ...
			}
			public function newFromJsonArray( string $className, array $json ): ManagedObject {
				return $this->factory->create( $json[....] );
			}
		};
	}
}
```
A full example can be found in
[`tests/ManagedObject.php`](./tests/ManagedObject.php).

Note that array returned by `toJsonArray()` can include other
`JsonCodecable` objects, which will be recursively serialized.
When `newFromJsonArray` is called during deserialization, all
of these recursively included objects will already have been
deserialized back into objects.

To serialize an object to JSON, use [`JsonCodec`](./src/JsonCodec.php):
```php
use Wikimedia\JsonCodec\JsonCodec;

$services = ... your global services object, or null ...;
$codec = new JsonCodec( $services );

$string_result = $codec->toJsonString( $someComplexValue );
$someComplexValue = $codec->newFromJsonString( $string_result );
```

In some cases you want to embed this output into another context,
or to pretty-print the output using non-default `json_encode` options.
In these cases it can be useful to have access to methods which
return or accept the array form of the encoding, just before
json encoding/decoding:
```php
$array_result = $codec->toJsonArray( $someComplexValue );
var_export($array_result); // pretty-print
$request->jsonResponse( [ 'error': false, 'embedded': $array_result ] );

$someComplexValue = $codec->fromJsonArray( $data['embedded'] );
```

### Handling "non-codecable" objects

In some cases you want to be able to serialize/deserialize third-party
objects which don't implement JsonCodecable.  This can be done using
the JsonCodec method `::addCodecFor()` which allows the creator of
the `JsonCodec` instance to specify a `JsonClassCodec` to use for
an arbitrary class name.  For example:
```php
use Wikimedia\JsonCodec\JsonCodec;

$codec = new JsonCodec( ...optional services object... );
$codec->addCodecFor( \DocumentFragment::class, new MyDOMSerializer() );

$string_result = $codec->toJsonString( $someComplexValue );
```
This is done by default to provide a serializer for `stdClass` objects.

If adding class codecs one-by-one is not sufficient, for example if
you wish to add support for all objects implementing some
alternate serialization interface, you can subclass `JsonCodec` and
override the protected `JsonCodec::codecFor()` method to return
an appropriate codec.  Your code should look like this:
```php
class MyCustomJsonCodec extends JsonCodec {
   protected function codecFor( string $className ): ?JsonClassCodec {
      $codec = parent::codecFor( $className );
      if ($codec === null && is_a($className, MyOwnSerializationType::class, true)) {
         $codec = new MyCustomSerializer();
         // Cache this for future use
         $this->addCodecFor( $className, $codec );
      }
      return $codec;
  }
}
```
A full example can be found in
[`tests/AlternateCodec.php`](./tests/AlternateCodec.php).

### More concise output

By default JsonCodec embeds the class name of the appropriate object
type into the JSON output to enable reliable deserialization.  In some
applications, however, concise JSON output is desired.  By providing
an optional "class hint" to the top-level call to `::toJsonArray()` and
`newFromJsonArray()` and implementing the `::jsonClassHintFor()`
method in your class codec you can suppress unnecessary type
information in the JSON (when your provided hint matches what would
have been added).  For example:

```
class SampleContainerObject implements JsonCodecable {
	use JsonCodecableTrait;

	/** @var mixed */
	public $contents;

    // ...

	// Implement JsonCodecable using the JsonCodecableTrait

	/** @inheritDoc */
	public function toJsonArray(): array {
		return [ 'contents' => $this->contents ];
	}

	/** @inheritDoc */
	public static function newFromJsonArray( array $json ): SampleContainerObject {
		return new SampleContainerObject( $json['contents'] );
	}

	/** @inheritDoc */
	public static function jsonClassHintFor( string $keyName ): ?string {
		if ( $keyName === 'contents' ) {
			// Hint that the contained value is a SampleObject. It might be!
			return SampleObject::class;
		}
		return null;
	}
}
```

You can then generate concise output by providing the proper hints
when serializing and deserializing:
```
use Wikimedia\JsonCodec\JsonCodec;

$codec = new JsonCodec();

$value = new SampleContainerObject( new SampleObject( 'sample' ) );
$string_result = $codec->toJsonString( $value, SampleContainerObject::class );

// $string_result is now:
//    {"contents":{"property":"sample"}}'
// with no explicit type information.

// But we need to provide the same class hint when deserializing:
$value = $codec->newFromJsonString( $string_result, SampleContainerObject::class );
```

Note that the provided value is a *hint*.  If we were to put a value
other than a `SampleObject` into the `SampleContainerObject` the type
of that value would be embedded into the JSON output, but it would not
break serialization/deserialization.

The class hint you provide can be suffixed with `[]` to indicate a
homogenous list or array of the given type.

A full example can be found in
[`tests/SampleContainerObject.php`](./tests/SampleContainerObject.php).

In some cases, `::jsonClassHintFor()` may be inadequate to describe
the implicit typing of the JSON; for example tagged union values or
implicitly-typed objects nested deeply or inside non-homogeneous
arrays.  For those use cases a `JsonCodecInterface` parameter is
provided to the `::jsonClassCodec()` method.  This allows the
serialization/deserialization code to manually encode/decode portions
of its JSON array using an implicit type.  More details can be found
in the interface documentation for
[`src/JsonCodecInterface.php`](./src/JsonCodecInterface.php) and a
full example can be found in
[`tests/TaggedValue.php`](./tests/TaggedValue.php).



Running tests
-------------

```
composer install
composer test
```

History
-------
The JsonCodec concept was first introduced in MediaWiki 1.36.0 ([dbdc2a3cd33](https://gerrit.wikimedia.org/r/c/mediawiki/core/+/641575/)). It was
split out of the MediaWiki codebase and published as an independent library
during the MediaWiki 1.41 development cycle, with changes to the API.

---
[Latest Stable Version]: https://poser.pugx.org/wikimedia/json-codec/v/stable.svg
[License]: https://poser.pugx.org/wikimedia/json-codec/license.svg
