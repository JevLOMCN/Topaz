Shellbox support for SyntaxHighlight
====================================

[SyntaxHighlight][] shells out to the `pygmentize` script from
[Pygments][]. The files in this directory are used in building a container
image containing a specific version of Pygments that is suitable for use as
a [Shellbox][] server for Extension:SyntaxHighlight.

Files
-----
### pygmentize

The `pygmentize` script is installed as `/srv/app/pygmentize` inside the
container image. Calling MediaWiki servers should be configured with:
```php
$wgPygmentizePath = '/srv/app/pygmentize';
```

### requirements.txt

The `requirements.txt` file is used to specify which version of Pygments to
install inside of the container image. To help ensure the integrity of the
installed package, pinning to a specific version and adding a hash to verify
the downloaded wheel is recommended. See [pip's documentation on secure
installs][pip-secure] for more information on hash-checking.


[SyntaxHighlight]: https://www.mediawiki.org/wiki/Extension:SyntaxHighlight
[Pygments]: https://pygments.org/
[Shellbox]: https://www.mediawiki.org/wiki/Shellbox
[pip-secure]: https://pip.pypa.io/en/stable/topics/secure-installs/
