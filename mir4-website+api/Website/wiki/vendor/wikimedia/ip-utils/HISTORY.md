# Release History

## 5.0.0
* Require PHP 7.4 or later (Timo Tijhof)
* The IPSet class is now part of the IPUtils library (Timo Tijhof)

## 4.0.1
* Allow wikimedia/ip-set ^4.0.0 (Timo Tijhof)

## 4.0.0
* Several IPUtils constants are now explicitly private (Zabe)

## 3.0.2
* Remove redundant strpos/substr code from canonicalize() (Reedy)
* Allow wikimedia/ip-set ^3.0.0 (Reedy)

## 3.0.1
* Revert "Stop allowing invalid /0 subnet" (Reedy)
* Update return type for IPUtils::parseCIDR6 (Reedy)
* Allow spaces around hyphenated IP ranges (Reedy)

## 3.0.0
* Add method to retrieve all IPs in a given range (Ammar Abdulhamid)
* Cast isValidIPv[46] return value to bool (Reedy)

## 2.0.0
* Add isValidIPv4 and isValidIPv6 (Reedy)
* Allow explicit ranges in IPUtils::isValidRange() (Reedy)
* Check for IPv4 before IPv6 in IPUtils::isValidRange (Reedy)
* Combine hexToOctet and hexToQuad tests to cover formatHex (Reedy)
* Drop PHP 7.0/7.1 and HHVM support (James D. Forrester)
* Fix return values where function doesn't always return [string, string] (Reedy)
* Make getSubnet actually not accept invalid IPv4 addresses (Reedy)
* Remove unnecessary temporary variables (Reedy)
* Stop allowing invalid /0 subnet (Reedy)

## 1.0.0
* Initial import from MediaWiki core (Kunal Mehta)
