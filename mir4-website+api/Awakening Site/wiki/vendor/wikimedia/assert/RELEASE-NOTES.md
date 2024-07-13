# Assert release notes

## Version 0.5.1 (2021-12-21)
* Added `UnreachableException` to indicate unreachable code paths, as the
  phan hints added in 0.5.0 prevent using a constant false `$condition` in
  `Assert::invariant()` for this purpose.
* When providing types, `'true'` or `'false'` can be specified rather than
  `'bool'` if only one of them should be allowed.

## Version 0.5.0 (2020-02-13)
* Raised required PHP version from 7.0 to 7.2
* Passing multiple types as an array (instead of pipe-separated strings) is now
  supported in `Assert::parameterElementType` as well.

## Version 0.4.0 (2019-01-21)
* Raised required PHP version from 5.3 to 7.0/HHVM
* Multiple types should be provided as arrays, e.g. `[ 'int', 'bool' ]`. The previous `'int|bool'`
  notation is still supported, but discouraged.

## Version 0.3.0 (2016-11-09)
* Added `Assert::parameterKeyType` and `ParameterKeyTypeException`
* Added `Assert::nonEmptyString`
* Added `Traversable` as a pseudo type that not only accept traversable objects but also arrays

## Version 0.2.2 (2015-04-29)
* Fixed a parameter type check in `ParameterAssertionException`

## Version 0.2.1 (2015-04-28)
* Fixed an import in `AssertTest`

## Version 0.2.0 (2015-04-13)
* Added `callable` as a pseudo type that not only accepts closures but everything `is_callable`
  accepts
* Added `AssertionException` interface
* All exception classes now implement `AssertionException`

## Version 0.1.0 (2014-07-30)

Initial release
