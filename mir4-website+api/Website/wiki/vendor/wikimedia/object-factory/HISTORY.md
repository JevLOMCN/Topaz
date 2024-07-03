# Release History

## v5.0.1
* .gitattributes: Updates (Reedy)

## v5.0.0
* BREAKING CHANGE: drop PHP 7.2/7.3 support (Reedy)
* BREAKING CHANGE: drop `specIsArg` option (DannyS712)
* Updates now we can use PHP 7.4 (Reedy)
* composer.json: Allow psr/container 2 (Reedy)

## v4.0.0
* BREAKING CHANGE: Drop Wikimedia\ObjectFactory class alias (Reedy)

## v3.0.2
* Fix PSR-4 namespace loading issues (Reedy)

## v3.0.1
* Move under new Wikimedia\ObjectFactory namespace (DannyS712)
* Bump psr/container from 1.0.0 to 1.1.1 (Reedy)

## v3.0.0
* BREAKING CHANGE: Drop PHP 7.0/7.1 and HHVM support (James D. Forrester)
* BREAKING CHANGE: Remove deprecated ObjectFactory::constructClassInstance method (DannyS712)
* Allow null values for 'services' spec field (Gergő Tisza)
* Add support for optional services (DannyS712)
* Add phan annotations for better analysis (Daimona Eaytoy)

## v2.1.0
* Implement spec 'services' for DI (Brad Jorsch)

## v2.0.0
* BREAKING CHANGE: Bump required PHP version to 5.6.99 (PHP 7 or HHVM) (Reedy)
* Enhance to be able to replace most/all MediaWiki object instantiation (Brad Jorsch)
* Deprecate ObjectFactory::constructClassInstance method (Brad Jorsch)

## v1.0.0
* Initial commit (Bryan Davis, Kunal Mehta)
