ConfirmEdit
=========

ConfirmEdit extension for MediaWiki

This extension provides various CAPTCHA tools for MediaWiki, to allow
for protection against spambots and other automated tools.

For more information, see the extension homepage at:
https://www.mediawiki.org/wiki/Extension:ConfirmEdit

### Overview

The following modules are included in ConfirmEdit:

* SimpleCaptcha - users have to solve an arithmetic math problem
* MathCaptcha - users have to solve a math problem that's displayed as
an image
* FancyCaptcha - users have to identify a series of characters, displayed
in a stylized way
* QuestyCaptcha - users have to answer a question, out of a series of
questions defined by the administrator(s)
* ReCaptchaNoCaptcha - users have to solve different types of visually or
audially tasks.
* hCaptcha - users have to solve visual tasks
* Turnstile - users check a box, which runs some client-side JS
heuristics

### License

ConfirmEdit is published under the GPL license.

### Authors

The main framework, and the SimpleCaptcha and FancyCaptcha modules, were
written by Brion Vibber.

The MathCaptcha module was written by Rob Church.

The QuestyCaptcha module was written by Benjamin Lees.

Additional maintenance work was done by Yaron Koren.

### Configuration comments
```php
/**
 * List of IP ranges to allow to skip the captcha, similar to the group setting:
 * "$wgGroupPermission[...]['skipcaptcha'] = true"
 *
 * Specific IP addresses or CIDR-style ranges may be used,
 * for instance:
 * $wgCaptchaWhitelistIP = array('192.168.1.0/24', '10.1.0.0/16');
 */
$wgCaptchaWhitelistIP = false;

/**
 * Actions which can trigger a captcha
 *
 * If the 'edit' trigger is on, *every* edit will trigger the captcha.
 * This may be useful for protecting against vandalbot attacks.
 *
 * If using the default 'addurl' trigger, the captcha will trigger on
 * edits that include URLs that aren't in the current version of the page.
 * This should catch automated linkspammers without annoying people when
 * they make more typical edits.
 *
 * The captcha code should not use $wgCaptchaTriggers, but CaptchaTriggers()
 * which also takes into account per namespace triggering.
 */
$wgCaptchaTriggers = [];
$wgCaptchaTriggers['edit']          = false; // Would check on every edit
$wgCaptchaTriggers['create']        = false; // Check on page creation.
$wgCaptchaTriggers['sendemail']     = false; // Special:Emailuser
$wgCaptchaTriggers['addurl']        = true;  // Check on edits that add URLs
$wgCaptchaTriggers['createaccount'] = true;  // Special:Userlogin&type=signup
$wgCaptchaTriggers['badlogin']      = true;  // Special:Userlogin after failure

/**
 * You may wish to apply special rules for captcha triggering on some namespaces.
 * $wgCaptchaTriggersOnNamespace[<namespace id>][<trigger>] forces an always on /
 * always off configuration with that trigger for the given namespace.
 * Leave unset to use the global options ($wgCaptchaTriggers).
 *
 * Shall not be used with 'createaccount' (it is not checked).
 */
$wgCaptchaTriggersOnNamespace = [];

# Example:
# $wgCaptchaTriggersOnNamespace[NS_TALK]['create'] = false; //Allow creation of talk pages without captchas.
# $wgCaptchaTriggersOnNamespace[NS_PROJECT]['edit'] = true; //Show captcha whenever editing Project pages.

/**
 * Indicate how to store per-session data required to match up the
 * internal captcha data with the editor.
 *
 * 'CaptchaSessionStore' uses PHP's session storage, which is cookie-based
 * and may fail for anons with cookies disabled.
 *
 * 'CaptchaCacheStore' uses MediaWiki core's MicroStash,
 * for storing captch data with a TTL eviction strategy.
 */
$wgCaptchaStorageClass = 'CaptchaSessionStore';

/**
 * Number of seconds a captcha session should last in the data cache
 * before expiring when managing through CaptchaCacheStore class.
 *
 * Default is a half hour.
 */
$wgCaptchaSessionExpiration = 30 * 60;

/**
 * Number of seconds after a bad login that a captcha will be shown to
 * that client on the login form to slow down password-guessing bots.
 *
 * Has no effect if 'badlogin' is disabled in $wgCaptchaTriggers or
 * if there is not a caching engine enabled.
 *
 * Default is five minutes.
 */
$wgCaptchaBadLoginExpiration = 5 * 60;

/**
 * Allow users who have confirmed their email addresses to post
 * URL links without being harassed by the captcha.
 *
 * @deprecated since 1.36
 * $wgGroupPermissions['emailconfirmed']['skipcaptcha'] = true; should be used instead.
 */
$wgAllowConfirmedEmail = false;

/**
 * Number of bad login attempts before triggering the captcha.  0 means the
 * captcha is presented on the first login.
 */
$wgCaptchaBadLoginAttempts = 3;

/**
 * Regex to whitelist URLs to known-good sites...
 * For instance:
 * $wgCaptchaWhitelist = '#^https?://([a-z0-9-]+\\.)?(wikimedia|wikipedia)\.org/#i';
 * Local admins can define a whitelist under [[MediaWiki:captcha-addurl-whitelist]]
 */
$wgCaptchaWhitelist = false;

/**
 * Additional regexes to check for. Use full regexes; can match things
 * other than URLs such as junk edits.
 *
 * If the new version matches one and the old version doesn't,
 * toss up the captcha screen.
 *
 * @fixme Add a message for local admins to add items as well.
 */
$wgCaptchaRegexes = [];
```
