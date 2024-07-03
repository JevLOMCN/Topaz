# VisualEditor

VisualEditor provides a visual editor for wiki pages. It is written in
JavaScript and runs in a web browser.

It uses the Parsoid parser to convert wikitext documents to annotated HTML
which the VisualEditor is able to load, modify and emit back to Parsoid at
which point it is converted back into wikitext.

For more information about these projects, check out the [VisualEditor][]
and [Parsoid][] pages on mediawiki.


## Developing and installing

For information on installing VisualEditor on a local wiki, please
see https://www.mediawiki.org/wiki/Extension:VisualEditor

For information about running tests and contributing code to VisualEditor,
see [CONTRIBUTING.md](./CONTRIBUTING.md).  Patch submissions are reviewed and managed with
[Gerrit][].  There is also [API documentation][] available for the
VisualEditor.


## Terminology

* Apex: See https://www.mediawiki.org/wiki/Skin:Apex
* CE: ContentEditable
* DM: Data model
* Invocation: Here the act of calling a template from a page, visible as e.g. `{{reflist}}` in the wikitext.
* MW: MediaWiki
* Page: See https://www.mediawiki.org/wiki/OOUI/Layouts/Booklets_and_Pages
* Parameter: A template parameter. Can be known (i.e. documented via TemplateData) or unknown.
* Part: A template-level entity in a transclusion, i.e. either a template, template placeholder, or wikitext snippet.
* SA: Standalone
* Template: See https://www.mediawiki.org/wiki/Help:Templates
* Transclusion: A sequence of one or more template invocations, possibly mixed with raw wikitext snippets.
* UI: User interface
* VE: VisualEditor
* WT: Wikitext

[VisualEditor]:      https://www.mediawiki.org/wiki/VisualEditor
[Parsoid]:           https://www.mediawiki.org/wiki/Parsoid
[API documentation]: https://doc.wikimedia.org/VisualEditor/master/
[Gerrit]:            https://www.mediawiki.org/wiki/Gerrit
