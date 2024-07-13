This is a "virtual" namespace, used for the particular DOM implementation
Parsoid is choosing to use.

The default implementation here uses the subclassing mechanism built
into PHP's \DOMDocument classes.  It is intended that the implementation
in `Wikimedia\Dodo` (or some other DOM implementation) can also be
aliased into this namespace.  The overall goal is to provide a measure
of implementation independence by ensuring that direct references to
the built-in \DOM* classes don't appear in type hints and phan docs.
