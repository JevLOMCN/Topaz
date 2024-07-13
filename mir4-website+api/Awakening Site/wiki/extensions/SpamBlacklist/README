MediaWiki extension: SpamBlacklist
----------------------------------

SpamBlacklist is a simple edit filter extension. When someone tries to save the
page, it checks the text against a potentially very large list of "bad"
hostnames. If there is a match, it displays an error message to the user and
refuses to save the page.

To enable it, first download a copy of the SpamBlacklist directory and put it
into your extensions directory. Then put the following at the end of your
LocalSettings.php:

    wfLoadExtension ( 'SpamBlacklist' );

The list of bad URLs can be drawn from multiple sources. These sources are
configured with the $wgBlacklistSettings global variable. This global variable
can be set in LocalSettings.php.

$wgBlacklistSettings is an array, where first key is either spam or email and
their value containing either a URL, a filename or a database location.
Specifying a database location allows you to draw the
blacklist from a page on your wiki. The format of the database location
specifier is "DB: <db name> <title>".

Example:

    wfLoadExtension ( 'SpamBlacklist' );
    $wgBlacklistSettings = [
        'spam' => [
            "$IP/extensions/SpamBlacklist/wikimedia_blacklist", // Wikimedia's list
            "DB: wikidb My_spam_blacklist", // database (wikidb), title (My_spam_blacklist)
        ]
    ];

The local pages [[MediaWiki:Spam-blacklist]] and [[MediaWiki:Spam-whitelist]]
will always be used, whatever additional files are listed.

Compatibility
-----------

This extension is primarily maintained to run on the latest release version
of MediaWiki (1.33.x as of this writing) and development versions.

If you are using an older version of MediaWiki, you can checkout an
older release branch, for example MediaWiki 1.20 would use REL1_20.

File format
-----------

In simple terms:
   * Everything from a "#" character to the end of the line is a comment
   * Every non-blank line is a regex fragment which will only match inside URLs

Internally, a regex is formed which looks like this:

   !http://[a-z0-9\-.]*(line 1|line 2|line 3|....)!Si

A few notes about this format. It's not necessary to add www to the start of
hostnames, the regex is designed to match any subdomain. Don't add patterns
to your file which may run off the end of the URL, e.g. anything containing
".*". Unlike in some similar systems, the line-end metacharacter "$" will not
assert the end of the hostname, it'll assert the end of the page.

Performance
-----------

This extension uses a small "loader" file, to avoid loading all the code on
every page view. This means that page view performance will not be affected
even if you are not running a PHP bytecode cache such as Turck MMCache. Note
that a bytecode cache is strongly recommended for any MediaWiki installation.

The regex match itself generally adds an insignificant overhead to page saves,
on the order of 100ms in our experience. However loading the spam file from disk
or the database, and constructing the regex, may take a significant amount of
time depending on your hardware. If you find that enabling this extension slows
down saves excessively, try installing MemCached or another supported data
caching solution. The SpamBlacklist extension will cache the constructed regex
if such a system is present.

Caching behavior
----------------

Blacklist files loaded from remote web sites are cached locally, in the cache
subsystem used for MediaWiki's localization. (This usually means the objectcache
table on a default install.)

By default, the list is cached for 15 minutes (if successfully fetched) or
10 minutes (if the network fetch failed), after which point it will be fetched
again when next requested. This should be a decent balance between avoiding
too-frequent fetches if your site is frequently used and staying up to date.

Fully-processed blacklist data may be cached in memcached or another shared
memory cache if it's been configured in MediaWiki.

Stability
---------

This extension has not been widely tested outside Wikimedia. Although it has
been in production on Wikimedia websites since December 2004, it should be
considered experimental. Its design is simple, with little input validation, so
unexpected behavior due to incorrect regular expression input or non-standard
configuration is entirely possible.

Obtaining or making blacklists
------------------------------

The primary source for a MediaWiki-compatible blacklist file is the Wikimedia
spam blacklist on meta:

    https://meta.wikimedia.org/wiki/Spam_blacklist

In the default configuration, the extension loads this list from our site
once every 10-15 minutes.

The Wikimedia spam blacklist can only be edited by trusted administrators.
Wikimedia hosts large, diverse wikis with many thousands of external links,
hence the Wikimedia blacklist is comparatively conservative in the links it
blocks. You may want to add your own keyword blocks or even ccTLD blocks.
You may suggest modifications to the Wikimedia blacklist at:

    https://meta.wikimedia.org/wiki/Talk:Spam_blacklist

To make maintenance of local lists easier, you may wish to add a DB: source to
$wgBlacklistSettings and hence create a blacklist on your wiki. If you do this,
it is strongly recommended that you protect the page from general editing.
Besides the obvious danger that someone may add a regex that matches everything,
please note that an attacker with the ability to input arbitrary regular
expressions may be able to generate segfaults in the PCRE library.

Whitelisting
------------

You may sometimes find that a site listed in a centrally-maintained blacklist
contains something you nonetheless want to link to.

A local whitelist can be maintained by creating a [[MediaWiki:Spam-whitelist]]
page and listing hostnames in it, using the same format as the blacklists.
URLs matching the whitelist will be ignored locally.

Logging
-------

To aid with tracking which domains are being spammed, this extension has
multiple logging features. By default, hits are included in the standard
debug log (controlled by $wgDebugLogFile). You can grep for 'SpamBlacklistHit',
which includes the IP of the user and the URL they tried to submit. This
file is only availible for people with server access and includes private info.

You can also enable logging to [[Special:Log]] by setting $wgLogSpamBlacklistHits to
true. This will include the account which tripped the blacklist, the page title the
edit was attempted on, and the specific URL. By default this log is only viewable
to wiki administrators, and you can grant other groups access by giving them the
"spamblacklistlog" permission.

Copyright
---------
This extension and this documentation was written by Tim Starling (with later
contributions by others) and is available under GPLv2 or any later version.
