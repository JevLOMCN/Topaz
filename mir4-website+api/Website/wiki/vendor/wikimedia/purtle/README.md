# Purtle

**Purtle** is a fast, lightweight RDF generator. It provides a "fluent" interface for
generating RDF output in Turtle, JSON-LD, XML/RDF or N-Triples. The fluent interface allows the
resulting PHP code to be structured just like Turtle notation for RDF, hence the name: "Purtle"
is a contraction of "PHP Turtle".

The concrete classes implementing the common `RdfWriter` interface are:
* `TurtleRdfWriter` outputs Turtle
* `JsonLdRdfWriter` outputs JSON-LD
* `XmlRdfWriter` outputs XML/RDF
* `NTriplesRdfWriter` outputs N-Triples

The PHP code would look something like this:

```php
$writer = new TurtleRdfWriter();

$writer->prefix( 'acme', 'http://acme.test/terms/' );

$writer->about( 'http://quux.test/Something' )
  ->a( 'acme', 'Thing' )
  ->say( 'acme', 'name' )->text( 'Thingy' )->text( 'Dingsda', 'de' )
  ->say( 'acme', 'owner' )->is( 'http://quux.test/' );
```
