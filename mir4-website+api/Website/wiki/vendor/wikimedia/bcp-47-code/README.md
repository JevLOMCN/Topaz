[![Latest Stable Version]](https://packagist.org/packages/wikimedia/bcp-47-code) [![License]](https://packagist.org/packages/wikimedia/bcp-47-code)

Bcp47Code
=====================

Simple interface representing languages which have a BCP 47 code.

This abstracts the details of a "language" object in MediaWiki (or your
code) and exposes only the ability to convert this opaque object to
an interoperable [BCP 47] code, which is the standard used in [HTML]
and [HTTP].

The [BCP 47] standard was originally [RFC 4647],and is now
[maintained by the IETF based on the IANA language registry](https://en.wikipedia.org/wiki/IETF_language_tag).

Additional documentation about this library can be found on
[mediawiki.org](https://www.mediawiki.org/wiki/Bcp47Code).


Usage
-----

```php
use Wikimedia\Bcp47Code\Bcp47Code;

class YourCode {
   public function generateHtml(Bcp47Code $language, $content) {
      return "<html><body lang='" . $language->toBcp47() . "'>" .
          "$content</body></html>";
   }
}
```


Running tests
-------------

```
composer install
composer test
```

History
-------

This library was first introduced in MediaWiki 1.40 as a way to abstract
the internal MediaWiki Language class in a way that could be used by
external libraries such as [Parsoid], as well as making the interfaces
between BCP 47 users (such as HTML and HTTP handlers) and core strongly
typed and clear by using this opaque interface object.  This avoids
confusion between strings representing "MediaWiki internal language codes"
(case-sensitive, usually lower-case) and strings representing "BCP 47 codes"
(case-insensitive, often mixed-case).


---
[BCP 47]: https://www.rfc-editor.org/info/bcp47
[RFC 4647]: https://www.rfc-editor.org/info/rfc4647
[HTML]: https://www.w3.org/International/articles/language-tags/
[HTTP]: https://httpwg.org/specs/rfc9110.html#field.accept-language
[Latest Stable Version]: https://poser.pugx.org/wikimedia/bcp-47-code/v/stable.svg
[License]: https://poser.pugx.org/wikimedia/bcp-47-code/license.svg
