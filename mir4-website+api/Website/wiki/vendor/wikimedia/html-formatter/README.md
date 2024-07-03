HtmlFormatter is a library spun off MediaWiki that allows you to load HTML into DomDocument, perform manipulations on it, and then return a HTML string.

Usage
-----

```php
use HtmlFormatter\HtmlFormatter;
// Load HTML that already has doctype and stuff
$formatter = new HtmlFormatter( $html );

// ...or one that doesn't have it
$formatter = new HtmlFormatter( HtmlFormatter::wrapHTML( $html ) );

// Add rules to remove some stuff
$formatter->remove( 'img' );
$formatter->remove( [ '.some_css_class', '#some_id', 'div.some_other_class' ] );
// Only the above syntax is supported, not full CSS/jQuery selectors

// These tags get replaced with their inner HTML,
// e.g. &lt;tag>foo&lt;/tag> --> foo
// Only tag names are supported here
$formatter->flatten( 'span' );
$formatter->flatten( [ 'code', 'pre' ] );

// Actually perform the removals
$formatter->filterContent();

// Direct DomDocument manipulations are possible
$formatter->getDoc()->createElement( 'p', 'Appended paragraph' );

// Get resulting HTML
$processedHtml = $formatter->getText();
```

License
-------
Copyright 2011-2024 MediaWiki contributors

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License along
with this program; if not, write to the Free Software Foundation, Inc.,
51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
<http://www.gnu.org/copyleft/gpl.html>
