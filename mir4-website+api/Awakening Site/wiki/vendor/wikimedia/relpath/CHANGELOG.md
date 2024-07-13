# Changelog

## 4.0.0

The library now requires PHP 7.4 or later.

Changed:

* Switch to PSR-4 autoloader.

Removed:

* PHP 7.2, and 7.3 are no longer supported.

## 3.0.0

The library now requires PHP 7.2 or later.

Changed:

* Add strict type hints to `RelPath::joinPath()` and other RelPath methods.

Removed:

* The `RelPath` namespace, deprecated in 2.1.0, has been removed.
  Use `Wikimedia\RelPath` instead.
* PHP 5.5, 5.6, 7.0, and 7.1 are no longer supported.

## 2.1.1

Fixed:

* The `RelPath\joinPath` function was calling an undefined
  function `oinPath`. This has been fixed.

## 2.1.0

Changed:

* The `RelPath` namespace functions are now static methods on a new
  `RelPath` class in the `Wikimedia` namespace.
  For example, change `RelPath\joinPath` to `Wikimedia\RelPath::joinPath`.
  The old functions are kept for backward compatibility.

## 2.0.0

The library now requires PHP 5.5 or later.

Removed:

* PHP 5.3 and PHP 5.4 are no longer supported.

## 1.0.4

Fixed:

* Normalize root directory slash on Windows to forward slash.
  The leading slash could sometimes change from a forward slash
  to a backslash due to how `dirname()` behaves on Windows.

## 1.0.3

Changed:

* Collapse redundant path segments in `RelPath\joinPath()`.
  The method could previously return `/foo/./bar` or `/foo/baz/../bar`,
  but would now return `/foo/bar` instead.

## 1.0.1

Added:

* New `RelPath\joinPath()` method. This is analogous to Python's `os.path.join()`.

## 1.0.0

Initial release
