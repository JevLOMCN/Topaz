Workaround for browser bugs in Range.prototype.getClientRects and Range.prototype.getBoundingClientRect.

In particular:

* Chrome <= 54: Selections spanning multiple nodes return rectangles for all the parents of the endContainer. See https://code.google.com/p/chromium/issues/detail?id=324437.
* Chrome 55: Images get no rectangle when they are wrapped in a node and you select across them.
* Safari: Similar to the Chrome <= 54 bug, but only triggered near the edge of a block node, or programmatically near an inline node.
* Firefox: Similar to the Chrome <= 54 bug, but only triggered near the edge of a inline node
* IE <= 10: Rectangles are incorrectly scaled when using the browser's zoom feature.
* Chrome: Selection across a space which spans two lines results in a bounding rectangle which doesn't cover all the individual rectangles.
* Firefox: Selections across a space which spans two lines, and text on the next line results in a bounding rectangle which doesn't cover all the individual rectangles.

There are no known issues in Chrome >= 56, Edge and IE >= 11. In these browsers the library will fall through to native behaviour.

Install
=======

```bash
$ npm install rangefix
```

Usage
=====

**CommonJS**

```javascript
var RangeFix = require( 'rangefix' );
```

**AMD**

```javascript
define( [ 'rangefix' ], function ( Rangefix ) {
	// ...
} );
```

**Browser global**

```html
<script src="path/to/rangefix.js"></script>
```

**API**

Replace instances of `Range.prototype.getClientRects`/`getBoundingClientRect` with `RangeFix.getClientRects`/`getBoundingClientRect`:

```javascript
var range = document.getSelection().getRangeAt( 0 );

// Before
var rects = range.getClientRects();
var boundingRect = range.getBoundingClientRect();

// After
var rects = RangeFix.getClientRects( range );
var boundingRect = RangeFix.getBoundingClientRect( range );
```

Demo
====
http://edg2s.github.io/rangefix/
