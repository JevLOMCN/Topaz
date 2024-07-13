Gadgets
=============

The Gadgets extension provides a way for users to pick JavaScript or CSS
based "gadgets" that other wiki users provide.

See https://www.mediawiki.org/wiki/Extension:Gadgets for more documentation.

The Gadgets extension was originally written by Daniel Kinzler in 2007
and is released under the GNU General Public Licence (GPL).

Prerequisites
-------------
This version of Gadgets requires MediaWiki 1.27 or later. To get a version
compatible with an earlier MediaWiki release, visit
[ExtensionDistributor/Gadgets](https://www.mediawiki.org/wiki/Special:ExtensionDistributor/Gadgets).

Installing
-------------
Copy the Gadgets directory into the extensions folder of your
MediaWiki installation. Then add the following lines to your
LocalSettings.php file (near the end):

	wfLoadExtension( 'Gadgets' );

Usage
-------------
See https://www.mediawiki.org/wiki/Extension:Gadgets#Usage

Caveats
-------------
* Gadgets do not apply to Special:Preferences, Special:UserLogin and
  Special:ResetPass so users can always disable any broken gadgets they
  may have enabled, and malicious gadgets will be unable to steal passwords.

Configuration
-------------
* `$wgGadgetsRepo`:  configures which GadgetRepo implementation will be used
  to source gadgets from. Currently, "definition" mode is the recommended
  setting and default. The "json" mode is not ready for production usage yet.
* `$wgSpecialGadgetUsageActiveUsers`:  configures whether or not to show active
  user stats on Special:GadgetUsage. True by default.
