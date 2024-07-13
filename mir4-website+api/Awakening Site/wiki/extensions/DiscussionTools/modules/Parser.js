'use strict';
/* global $:off */

var
	utils = require( './utils.js' ),
	charAt = require( 'mediawiki.String' ).charAt,
	codePointLength = require( 'mediawiki.String' ).codePointLength,
	trimByteLength = require( 'mediawiki.String' ).trimByteLength,
	CommentItem = require( './CommentItem.js' ),
	HeadingItem = require( './HeadingItem.js' ),
	ThreadItem = require( './ThreadItem.js' ),
	ThreadItemSet = require( './ThreadItemSet.js' ),
	moment = require( './lib/moment-timezone/moment-timezone-with-data-1970-2030.js' );

/**
 * Utilities for detecting and parsing components of discussion pages: signatures, timestamps,
 * comments and threads.
 *
 * @class mw.dt.Parser
 * @param {Array} data Language-specific data to be used for parsing
 * @constructor
 */
function Parser( data ) {
	this.data = data;
}

/**
 * How far backwards we look for a signature associated with a timestamp before giving up.
 * Note that this is not a hard limit on the length of signatures we detect.
 *
 * @constant {number}
 */
var SIGNATURE_SCAN_LIMIT = 100;

/**
 * Parse a discussion page.
 *
 * @param {HTMLElement} rootNode Root node of content to parse
 * @param {mw.Title} title Title of the page being parsed
 * @chainable
 * @return {Parser}
 */
Parser.prototype.parse = function ( rootNode, title ) {
	this.rootNode = rootNode;
	this.title = title;

	var result = this.buildThreadItems();
	this.buildThreads( result );
	this.computeIdsAndNames( result );

	return result;
};

OO.initClass( Parser );

/**
 * Get text of localisation messages in content language.
 *
 * @private
 * @param {string} contLangVariant Content language variant
 * @param {string[]} messages Message keys
 * @return {string[]} Message values
 */
Parser.prototype.getMessages = function ( contLangVariant, messages ) {
	var parser = this;
	return messages.map( function ( code ) {
		return parser.data.contLangMessages[ contLangVariant ][ code ];
	} );
};

/**
 * Get a regexp that matches timestamps generated using the given date format.
 *
 * This only supports format characters that are used by the default date format in any of
 * MediaWiki's languages, namely: D, d, F, G, H, i, j, l, M, n, Y, xg, xkY (and escape characters),
 * and only dates when MediaWiki existed, let's say 2000 onwards (Thai dates before 1941 are
 * complicated).
 *
 * @private
 * @param {string} contLangVariant Content language variant
 * @param {string} format Date format, as used by MediaWiki
 * @param {string} digitsRegexp Regular expression matching a single localised digit, e.g. `[0-9]`
 * @param {Object.<string,string>} tzAbbrs Map of localised timezone abbreviations to IANA abbreviations
 *   for the local timezone, e.g. `{EDT: "EDT", EST: "EST"}`
 * @return {string} Regular expression
 */
Parser.prototype.getTimestampRegexp = function ( contLangVariant, format, digitsRegexp, tzAbbrs ) {
	function regexpGroup( r ) {
		return '(' + r + ')';
	}

	function regexpAlternateGroup( array ) {
		return '(' + array.map( mw.util.escapeRegExp ).join( '|' ) + ')';
	}

	var parser = this;

	var s = '';
	var raw = false;
	// Adapted from Language::sprintfDate()
	for ( var p = 0; p < format.length; p++ ) {
		var num = false;
		var code = format[ p ];
		if ( code === 'x' && p < format.length - 1 ) {
			code += format[ ++p ];
		}
		if ( code === 'xk' && p < format.length - 1 ) {
			code += format[ ++p ];
		}

		switch ( code ) {
			case 'xx':
				s += 'x';
				break;
			case 'xg':
				s += regexpAlternateGroup( parser.getMessages( contLangVariant, [
					'january-gen', 'february-gen', 'march-gen', 'april-gen', 'may-gen', 'june-gen',
					'july-gen', 'august-gen', 'september-gen', 'october-gen', 'november-gen',
					'december-gen'
				] ) );
				break;
			case 'xn':
				raw = true;
				break;
			case 'd':
				num = '2';
				break;
			case 'D':
				s += regexpAlternateGroup( parser.getMessages( contLangVariant, [
					'sun', 'mon', 'tue', 'wed', 'thu', 'fri', 'sat'
				] ) );
				break;
			case 'j':
				num = '1,2';
				break;
			case 'l':
				s += regexpAlternateGroup( parser.getMessages( contLangVariant, [
					'sunday', 'monday', 'tuesday', 'wednesday', 'thursday',
					'friday', 'saturday'
				] ) );
				break;
			case 'F':
				s += regexpAlternateGroup( parser.getMessages( contLangVariant, [
					'january', 'february', 'march', 'april', 'may_long', 'june',
					'july', 'august', 'september', 'october', 'november',
					'december'
				] ) );
				break;
			case 'M':
				s += regexpAlternateGroup( parser.getMessages( contLangVariant, [
					'jan', 'feb', 'mar', 'apr', 'may', 'jun', 'jul', 'aug',
					'sep', 'oct', 'nov', 'dec'
				] ) );
				break;
			case 'm':
				num = '2';
				break;
			case 'n':
				num = '1,2';
				break;
			case 'Y':
				num = '4';
				break;
			case 'xkY':
				num = '4';
				break;
			case 'G':
				num = '1,2';
				break;
			case 'H':
				num = '2';
				break;
			case 'i':
				num = '2';
				break;
			case 's':
				num = '2';
				break;
			case '\\':
				// Backslash escaping
				if ( p < format.length - 1 ) {
					s += mw.util.escapeRegExp( format[ ++p ] );
				} else {
					s += mw.util.escapeRegExp( '\\' );
				}
				break;
			case '"':
				// Quoted literal
				if ( p < format.length - 1 ) {
					var endQuote = format.indexOf( '"', p + 1 );
					if ( endQuote === -1 ) {
						// No terminating quote, assume literal "
						s += '"';
					} else {
						s += mw.util.escapeRegExp( format.slice( p + 1, endQuote ) );
						p = endQuote;
					}
				} else {
					// Quote at end of string, assume literal "
					s += '"';
				}
				break;
			default:
				// Copy whole characters together, instead of single UTF-16 surrogates
				var char = charAt( format, p );
				s += mw.util.escapeRegExp( char );
				p += char.length - 1;
		}
		if ( num !== false ) {
			if ( raw ) {
				s += regexpGroup( '[0-9]{' + num + '}' );
				raw = false;
			} else {
				s += regexpGroup( digitsRegexp + '{' + num + '}' );
			}
		}
		// Ignore some invisible Unicode characters that often sneak into copy-pasted timestamps (T308448)
		s += '[\\u200E\\u200F]?';
	}

	var tzRegexp = regexpAlternateGroup( Object.keys( tzAbbrs ) );
	// Hard-coded parentheses and space like in Parser::pstPass2
	// Ignore some invisible Unicode characters that often sneak into copy-pasted timestamps (T245784)
	var regexp = s + ' [\\u200E\\u200F]?\\(' + tzRegexp + '\\)';

	return regexp;
};

/**
 * Get a function that parses timestamps generated using the given date format, based on the result
 * of matching the regexp returned by #getTimestampRegexp.
 *
 * @private
 * @param {string} contLangVariant Content language variant
 * @param {string} format Date format, as used by MediaWiki
 * @param {string[]|null} digits Localised digits from 0 to 9, e.g. `[ '0', '1', ..., '9' ]`
 * @param {string} localTimezone Local timezone IANA name, e.g. `America/New_York`
 * @param {Object.<string,string>} tzAbbrs Map of localised timezone abbreviations to IANA abbreviations
 *   for the local timezone, e.g. `{EDT: "EDT", EST: "EST"}`
 * @return {TimestampParser} Timestamp parser function
 */
Parser.prototype.getTimestampParser = function ( contLangVariant, format, digits, localTimezone, tzAbbrs ) {
	var matchingGroups = [];
	for ( var p = 0; p < format.length; p++ ) {
		var code = format[ p ];
		if ( code === 'x' && p < format.length - 1 ) {
			code += format[ ++p ];
		}
		if ( code === 'xk' && p < format.length - 1 ) {
			code += format[ ++p ];
		}

		switch ( code ) {
			case 'xx':
			case 'xn':
				break;
			case 'xg':
			case 'd':
			case 'j':
			case 'D':
			case 'l':
			case 'F':
			case 'M':
			case 'm':
			case 'n':
			case 'Y':
			case 'xkY':
			case 'G':
			case 'H':
			case 'i':
			case 's':
				matchingGroups.push( code );
				break;
			case '\\':
				// Backslash escaping
				if ( p < format.length - 1 ) {
					++p;
				}
				break;
			case '"':
				// Quoted literal
				if ( p < format.length - 1 ) {
					var endQuote = format.indexOf( '"', p + 1 );
					if ( endQuote !== -1 ) {
						p = endQuote;
					}
				}
				break;
			default:
				break;
		}
	}

	/**
	 * @param {string} text
	 * @return {number}
	 */
	function untransformDigits( text ) {
		return Number( digits ? text.replace(
			// digits list comes from site config so is trusted
			// eslint-disable-next-line security/detect-non-literal-regexp
			new RegExp( '[' + digits.join( '' ) + ']', 'g' ),
			( m ) => digits.indexOf( m )
		) : text );
	}

	var parser = this;

	/**
	 * @typedef {function(Array):moment} TimestampParser
	 */

	/**
	 * Timestamp parser
	 *
	 * @param {Array} match RegExp match data
	 * @return {Object} Result, an object with the following keys (or null if the date is invalid):
	 *  - {moment} date Moment date object
	 *  - {string|null} warning Warning message if the input wasn't correctly formed
	 */
	return function timestampParser( match ) {
		var
			year = 0,
			monthIdx = 0,
			day = 0,
			hour = 0,
			minute = 0;

		for ( var i = 0; i < matchingGroups.length; i++ ) {
			var code2 = matchingGroups[ i ];
			var text = match[ i + 1 ];

			switch ( code2 ) {
				case 'xg':
					monthIdx = parser.getMessages( contLangVariant, [
						'january-gen', 'february-gen', 'march-gen', 'april-gen', 'may-gen', 'june-gen',
						'july-gen', 'august-gen', 'september-gen', 'october-gen', 'november-gen',
						'december-gen'
					] ).indexOf( text );
					break;
				case 'd':
				case 'j':
					day = untransformDigits( text );
					break;
				case 'D':
				case 'l':
					// Day of the week - unused
					break;
				case 'F':
					monthIdx = parser.getMessages( contLangVariant, [
						'january', 'february', 'march', 'april', 'may_long', 'june',
						'july', 'august', 'september', 'october', 'november',
						'december'
					] ).indexOf( text );
					break;
				case 'M':
					monthIdx = parser.getMessages( contLangVariant, [
						'jan', 'feb', 'mar', 'apr', 'may', 'jun', 'jul', 'aug',
						'sep', 'oct', 'nov', 'dec'
					] ).indexOf( text );
					break;
				case 'm':
				case 'n':
					monthIdx = untransformDigits( text ) - 1;
					break;
				case 'Y':
					year = untransformDigits( text );
					break;
				case 'xkY':
					// Thai year
					year = untransformDigits( text ) - 543;
					break;
				case 'G':
				case 'H':
					hour = untransformDigits( text );
					break;
				case 'i':
					minute = untransformDigits( text );
					break;
				case 's':
					// Seconds - unused, because most timestamp formats omit them
					break;
				default:
					throw new Error( 'Not implemented' );
			}
		}
		// The last matching group is the timezone abbreviation
		var tzAbbr = tzAbbrs[ match[ match.length - 1 ] ];

		// Most of the time, the timezone abbreviation is not necessary to parse the date, since we
		// can assume all times are in the wiki's local timezone.
		var date = moment.tz( [ year, monthIdx, day, hour, minute ], localTimezone );

		// But during the "fall back" at the end of DST, some times will happen twice. Per the docs,
		// "Moment Timezone handles this by always using the earlier instance of a duplicated hour."
		// https://momentjs.com/timezone/docs/#/using-timezones/parsing-ambiguous-inputs/

		// Since the timezone abbreviation disambiguates the DST/non-DST times, we can detect when
		// that behavior was incorrect...
		var dateWarning = null;
		if ( date.zoneAbbr() !== tzAbbr ) {
			// ...and force the correct parsing. I can't find proper documentation for this feature,
			// but this pull request explains it: https://github.com/moment/moment-timezone/pull/101
			moment.tz.moveAmbiguousForward = true;
			date = moment.tz( [ year, monthIdx, day, hour, minute ], localTimezone );
			moment.tz.moveAmbiguousForward = false;
			if ( date.zoneAbbr() !== tzAbbr ) {
				// This should not be possible for "genuine" timestamps generated by MediaWiki.
				// But bots and humans get it wrong when marking up unsigned comments…
				// https://pl.wikipedia.org/w/index.php?title=Wikipedia:Kawiarenka/Artykuły&diff=prev&oldid=54772606
				dateWarning = 'Timestamp has timezone abbreviation for the wrong time';
			} else {
				dateWarning = 'Ambiguous time at DST switchover was parsed';
			}
		}

		// We require the date to be compatible with our libraries, for example zero or negative years (T352455)
		// In PHP we need to check with MWTimestamp.
		// In JS we need to check with Moment.
		if ( !date.isValid() ) {
			return null;
		}

		return {
			date: date,
			warning: dateWarning
		};
	};
};

/**
 * Get a regexp that matches timestamps in the local date format, for each language variant.
 *
 * This calls #getTimestampRegexp with predefined data for the current wiki.
 *
 * @private
 * @return {string[]} Regular expressions
 */
Parser.prototype.getLocalTimestampRegexps = function () {
	var parser = this;
	return Object.keys( this.data.dateFormat ).map( function ( contLangVariant ) {
		return parser.getTimestampRegexp(
			contLangVariant,
			parser.data.dateFormat[ contLangVariant ],
			'[' + parser.data.digits[ contLangVariant ].join( '' ) + ']',
			parser.data.timezones[ contLangVariant ]
		);
	} );
};

/**
 * Get a function that parses timestamps in the local date format, for each language variant,
 * based on the result of matching the regexps returned by #getLocalTimestampRegexps.
 *
 * This calls #getTimestampParser with predefined data for the current wiki.
 *
 * @private
 * @return {TimestampParser[]} Timestamp parser functions
 */
Parser.prototype.getLocalTimestampParsers = function () {
	var parser = this;
	return Object.keys( this.data.dateFormat ).map( function ( contLangVariant ) {
		return parser.getTimestampParser(
			contLangVariant,
			parser.data.dateFormat[ contLangVariant ],
			parser.data.digits[ contLangVariant ],
			parser.data.localTimezone,
			parser.data.timezones[ contLangVariant ]
		);
	} );
};

/**
 * Callback for document.createTreeWalker that will skip over nodes where we don't want to detect
 * comments (or section headings).
 *
 * @param {Node} node
 * @return {number} Appropriate NodeFilter constant
 */
function acceptOnlyNodesAllowingComments( node ) {
	if ( node instanceof HTMLElement ) {
		var tagName = node.tagName.toLowerCase();
		// The table of contents has a heading that gets erroneously detected as a section
		if ( node.id === 'toc' ) {
			return NodeFilter.FILTER_REJECT;
		}
		// Don't detect comments within quotes (T275881)
		if (
			tagName === 'blockquote' ||
			tagName === 'cite' ||
			tagName === 'q'
		) {
			return NodeFilter.FILTER_REJECT;
		}
		// Don't attempt to parse blocks marked 'mw-notalk'
		if ( node.classList.contains( 'mw-notalk' ) ) {
			return NodeFilter.FILTER_REJECT;
		}
		// Don't detect comments within references. We can't add replies to them without bungling up
		// the structure in some cases (T301213), and you're not supposed to do that anyway…
		if (
			// <ol class="references"> is the only reliably consistent thing between the two parsers
			tagName === 'ol' &&
			node.classList.contains( 'references' )
		) {
			return NodeFilter.FILTER_REJECT;
		}
	}
	var parentNode = node.parentNode;
	// Don't detect comments within headings (but don't reject the headings themselves)
	if ( parentNode instanceof HTMLElement && parentNode.tagName.match( /^h([1-6])$/i ) ) {
		return NodeFilter.FILTER_REJECT;
	}
	return NodeFilter.FILTER_ACCEPT;
}

/**
 * Find a timestamp in a given text node
 *
 * @private
 * @param {Text} node Text node
 * @param {string[]} timestampRegexps Timestamp regexps
 * @return {Object|null} Object with the following keys:
 *   - {number} offset Length of extra text preceding the node that was used for matching
 *   - {number} parserIndex Which of the regexps matched
 *   - {Array} matchData Regexp match data, which specifies the location of the match,
 *     and which can be parsed using #getLocalTimestampParsers
 *   - {Object} range Range-like object covering the timestamp
 */
Parser.prototype.findTimestamp = function ( node, timestampRegexps ) {
	var matchData, i,
		nodeText = '',
		offset = 0,
		// Searched nodes (reverse order)
		nodes = [];

	while ( node ) {
		nodeText = node.nodeValue + nodeText;
		nodes.push( node );

		// In Parsoid HTML, entities are represented as a 'mw:Entity' node, rather than normal HTML
		// entities. On Arabic Wikipedia, the "UTC" timezone name contains some non-breaking spaces,
		// which apparently are often turned into &nbsp; entities by buggy editing tools. To handle
		// this, we must piece together the text, so that our regexp can match those timestamps.
		if (
			node.previousSibling &&
			node.previousSibling.nodeType === Node.ELEMENT_NODE &&
			node.previousSibling.getAttribute( 'typeof' ) === 'mw:Entity'
		) {
			nodeText = node.previousSibling.firstChild.nodeValue + nodeText;
			offset += node.previousSibling.firstChild.nodeValue.length;
			nodes.push( node.previousSibling.firstChild );

			// If the entity is followed by more text, do this again
			if (
				node.previousSibling.previousSibling &&
				node.previousSibling.previousSibling.nodeType === Node.TEXT_NODE
			) {
				offset += node.previousSibling.previousSibling.nodeValue.length;
				node = node.previousSibling.previousSibling;
			} else {
				node = null;
			}
		} else {
			node = null;
		}
	}

	for ( i = 0; i < timestampRegexps.length; i++ ) {
		// Technically, there could be multiple matches in a single text node. However, the ultimate
		// point of this is to find the signatures which precede the timestamps, and any later
		// timestamps in the text node can't be directly preceded by a signature (as we require them to
		// have links), so we only concern ourselves with the first match.
		matchData = nodeText.match( timestampRegexps[ i ] );
		if ( matchData ) {
			var timestampLength = matchData[ 0 ].length;
			// Bytes at the end of the last node which aren't part of the match
			var tailLength = nodeText.length - timestampLength - matchData.index;
			// We are moving right to left, but we start to the right of the end of
			// the timestamp if there is trailing garbage, so that is a negative offset.
			var count = -tailLength;
			var endContainer = nodes[ 0 ];
			var endOffset = endContainer.nodeValue.length - tailLength;

			var startContainer, startOffset;
			// eslint-disable-next-line no-loop-func
			nodes.some( function ( n ) {
				count += n.nodeValue.length;
				// If we have counted to beyond the start of the timestamp, we are in the
				// start node of the timestamp
				if ( count >= timestampLength ) {
					startContainer = n;
					// Offset is how much we overshot the start by
					startOffset = count - timestampLength;
					return true;
				}
				return false;
			} );

			var range = {
				startContainer: startContainer,
				startOffset: startOffset,
				endContainer: endContainer,
				endOffset: endOffset
			};

			return {
				matchData: matchData,
				// Bytes at the start of the first node which aren't part of the match
				// TODO: Remove this and use 'range' instead
				offset: offset,
				range: range,
				parserIndex: i
			};
		}
	}
	return null;
};

/**
 * Given a link node (`<a>`), if it's a link to a user-related page, return their username.
 *
 * @param {HTMLElement} link
 * @return {Object|null} Object, or null:
 * - {string} username Username
 * - {string|null} displayName Display name (link text if link target was in the user namespace)
 */
Parser.prototype.getUsernameFromLink = function ( link ) {
	var title;
	// Selflink: use title of current page
	if ( link.classList.contains( 'mw-selflink' ) ) {
		title = this.title;
	} else {
		var titleString = utils.getTitleFromUrl( link.href ) || '';
		// Performance optimization, skip strings that obviously don't contain a namespace
		if ( !titleString || titleString.indexOf( ':' ) === -1 ) {
			return null;
		}
		title = mw.Title.newFromText( titleString );
	}
	if ( !title ) {
		return null;
	}

	var username;
	var displayName = null;
	var namespaceId = title.getNamespaceId();
	var mainText = title.getMainText();
	var namespaceIds = mw.config.get( 'wgNamespaceIds' );

	if (
		namespaceId === namespaceIds.user ||
		namespaceId === namespaceIds.user_talk
	) {
		username = mainText;
		if ( username.indexOf( '/' ) !== -1 ) {
			return null;
		}
		if ( namespaceId === namespaceIds.user ) {
			// Use regex trim for consistency with PHP implementation
			var text = link.textContent.replace( /^[\s]+/, '' ).replace( /[\s]+$/, '' );
			// Record the display name if it has been customised beyond changing case
			if ( text && text.toLowerCase() !== username.toLowerCase() ) {
				displayName = text;
			}
		}
	} else if ( namespaceId === namespaceIds.special ) {
		var parts = mainText.split( '/' );
		if ( parts.length === 2 && parts[ 0 ] === this.data.specialContributionsName ) {
			// Normalize the username: users may link to their contributions with an unnormalized name
			var userpage = mw.Title.makeTitle( namespaceIds.user, parts[ 1 ] );
			if ( !userpage ) {
				return null;
			}
			username = userpage.getMainText();
		}
	}
	if ( !username ) {
		return null;
	}
	if ( mw.util.isIPv6Address( username ) ) {
		// Bot-generated links "Preceding unsigned comment added by" have non-standard case
		username = username.toUpperCase();
	}
	return {
		username: username,
		displayName: displayName
	};
};

/**
 * Find a user signature preceding a timestamp.
 *
 * The signature includes the timestamp node.
 *
 * A signature must contain at least one link to the user's userpage, discussion page or
 * contributions (and may contain other links). The link may be nested in other elements.
 *
 * @private
 * @param {Text} timestampNode Text node
 * @param {Node} [until] Node to stop searching at
 * @return {Object} Result, an object with the following keys:
 *  - {Node[]} nodes Sibling nodes comprising the signature, in reverse order (with
 *    `timestampNode` or its parent node as the first element)
 *  - {string|null} username Username, null for unsigned comments
 */
Parser.prototype.findSignature = function ( timestampNode, until ) {
	var parser = this;
	var sigUsername = null;
	var sigDisplayName = null;
	var length = 0;
	var lastLinkNode = timestampNode;

	utils.linearWalkBackwards(
		timestampNode,
		function ( event, node ) {
			if ( event === 'enter' && node === until ) {
				return true;
			}
			if ( length >= SIGNATURE_SCAN_LIMIT ) {
				return true;
			}
			if ( utils.isBlockElement( node ) ) {
				// Don't allow reaching into preceding paragraphs
				return true;
			}

			if ( event === 'leave' && node !== timestampNode ) {
				length += node.nodeType === Node.TEXT_NODE ?
					codePointLength( utils.htmlTrim( node.textContent ) ) : 0;
			}

			// Find the closest link before timestamp that links to the user's user page.
			//
			// Support timestamps being linked to the diff introducing the comment:
			// if the timestamp node is the only child of a link node, use the link node instead
			//
			// Handle links nested in formatting elements.
			if ( event === 'leave' && node.nodeType === Node.ELEMENT_NODE && node.tagName.toLowerCase() === 'a' ) {
				if ( !node.classList.contains( 'ext-discussiontools-init-timestamplink' ) ) {
					var user = parser.getUsernameFromLink( node );
					if ( user ) {
						// Accept the first link to the user namespace, then only accept links to that user
						if ( sigUsername === null ) {
							sigUsername = user.username;
						}
						if ( user.username === sigUsername ) {
							lastLinkNode = node;
							if ( user.displayName ) {
								sigDisplayName = user.displayName;
							}
						}
					}
					// Keep looking if a node with links wasn't a link to a user page
					// "Doc James (talk · contribs · email)"
				}
			}
		}
	);

	var range = {
		startContainer: lastLinkNode.parentNode,
		startOffset: utils.childIndexOf( lastLinkNode ),
		endContainer: timestampNode.parentNode,
		endOffset: utils.childIndexOf( timestampNode ) + 1
	};
	var nativeRange = ThreadItem.prototype.getRange.call( { range: range } );

	// Expand the range so that it covers sibling nodes.
	// This will include any wrapping formatting elements as part of the signature.
	//
	// Helpful accidental feature: users whose signature is not detected in full (due to
	// text formatting) can just wrap it in a <span> to fix that.
	// "Ten Pound Hammer • (What did I screw up now?)"
	// "« Saper // dyskusja »"
	//
	// TODO Not sure if this is actually good, might be better to just use the range...
	var sigNodes = utils.getCoveredSiblings( nativeRange ).reverse();

	return {
		nodes: sigNodes,
		username: sigUsername,
		displayName: sigDisplayName
	};
};

/**
 * Return the next leaf node in the tree order that is likely a part of a discussion comment,
 * rather than some boring "separator" element.
 *
 * Currently, this can return a Text node with content other than whitespace, or an Element node
 * that is a "void element" or "text element", except some special cases that we treat as comment
 * separators (isCommentSeparator()).
 *
 * @private
 * @param {Node|null} node Node after which to start searching
 *   (if null, start at the beginning of the document).
 * @return {Node}
 */
Parser.prototype.nextInterestingLeafNode = function ( node ) {
	var rootNode = this.rootNode;

	var treeWalker = rootNode.ownerDocument.createTreeWalker(
		rootNode,
		// eslint-disable-next-line no-bitwise
		NodeFilter.SHOW_ELEMENT | NodeFilter.SHOW_TEXT,
		function ( n ) {
			// Skip past the starting node and its descendants
			if ( n === node || n.parentNode === node ) {
				return NodeFilter.FILTER_REJECT;
			}
			// Ignore some elements usually used as separators or headers (and their descendants)
			if ( utils.isCommentSeparator( n ) ) {
				return NodeFilter.FILTER_REJECT;
			}
			// Ignore nodes with no rendering that mess up our indentation detection
			if ( utils.isRenderingTransparentNode( n ) ) {
				return NodeFilter.FILTER_REJECT;
			}
			if ( utils.isCommentContent( n ) ) {
				return NodeFilter.FILTER_ACCEPT;
			}
			return NodeFilter.FILTER_SKIP;
		},
		false
	);
	if ( node ) {
		treeWalker.currentNode = node;
	}
	treeWalker.nextNode();
	if ( !treeWalker.currentNode ) {
		throw new Error( 'nextInterestingLeafNode not found' );
	}
	return treeWalker.currentNode;
};

/**
 * @param {Node[]} sigNodes
 * @param {Object} match
 * @param {Text} node
 * @return {Object} Range-like object
 */
function adjustSigRange( sigNodes, match, node ) {
	var firstSigNode = sigNodes[ sigNodes.length - 1 ];
	var lastSigNode = sigNodes[ 0 ];

	// TODO Document why this needs to be so complicated
	var lastSigNodeOffset = lastSigNode === node ?
		match.matchData.index + match.matchData[ 0 ].length - match.offset :
		utils.childIndexOf( lastSigNode ) + 1;
	var sigRange = {
		startContainer: firstSigNode.parentNode,
		startOffset: utils.childIndexOf( firstSigNode ),
		endContainer: lastSigNode === node ? node : lastSigNode.parentNode,
		endOffset: lastSigNodeOffset
	};
	return sigRange;
}

/**
 * @return {ThreadItemSet}
 */
Parser.prototype.buildThreadItems = function () {
	var result = new ThreadItemSet();

	var
		dfParsers = this.getLocalTimestampParsers(),
		timestampRegexps = this.getLocalTimestampRegexps();

	var treeWalker = this.rootNode.ownerDocument.createTreeWalker(
		this.rootNode,
		// eslint-disable-next-line no-bitwise
		NodeFilter.SHOW_ELEMENT | NodeFilter.SHOW_TEXT,
		acceptOnlyNodesAllowingComments,
		false
	);

	var curComment, range;
	var curCommentEnd = null;

	var node;
	while ( ( node = treeWalker.nextNode() ) ) {
		var match;
		if ( node.tagName && ( match = node.tagName.match( /^h([1-6])$/i ) ) ) {
			var headingNodeAndOffset = utils.getHeadlineNodeAndOffset( node );
			var headingNode = headingNodeAndOffset.node;
			var startOffset = headingNodeAndOffset.offset;
			range = {
				startContainer: headingNode,
				startOffset: startOffset,
				endContainer: headingNode,
				endOffset: headingNode.childNodes.length
			};
			curComment = new HeadingItem( range, +match[ 1 ] );
			curComment.rootNode = this.rootNode;
			result.addThreadItem( curComment );
			curCommentEnd = node;
		} else if ( node.nodeType === Node.TEXT_NODE && ( match = this.findTimestamp( node, timestampRegexps ) ) ) {
			var warnings = [];
			var foundSignature = this.findSignature( node, curCommentEnd );
			var author = foundSignature.username;

			if ( !author ) {
				// Ignore timestamps for which we couldn't find a signature. It's probably not a real
				// comment, but just a false match due to a copypasted timestamp.
				continue;
			}

			var sigRanges = [];
			var timestampRanges = [];

			sigRanges.push( adjustSigRange( foundSignature.nodes, match, node ) );
			timestampRanges.push( match.range );

			// Everything from the last comment up to here is the next comment
			var startNode = this.nextInterestingLeafNode( curCommentEnd );
			var endNode = foundSignature.nodes[ 0 ];

			// Skip to the end of the "paragraph". This only looks at tag names and can be fooled by CSS, but
			// avoiding that would be more difficult and slower.
			//
			// If this skips over another potential signature, also skip it in the main TreeWalker loop, to
			// avoid generating multiple comments when there is more than one signature on a single "line".
			// Often this is done when someone edits their comment later and wants to add a note about that.
			// (Or when another person corrects a typo, or strikes out a comment, etc.) Multiple comments
			// within one paragraph/list-item result in a confusing double "Reply" button, and we also have
			// no way to indicate which one you're replying to (this might matter in the future for
			// notifications or something).
			utils.linearWalk(
				endNode,
				// eslint-disable-next-line no-loop-func
				function ( event, n ) {
					var match2, foundSignature2;
					if ( utils.isBlockElement( n ) || utils.isCommentSeparator( n ) ) {
						// Stop when entering or leaving a block node
						return true;
					}
					if (
						event === 'leave' &&
						n.nodeType === Node.TEXT_NODE && n !== node &&
						( match2 = this.findTimestamp( n, timestampRegexps ) )
					) {
						// If this skips over another potential signature, also skip it in the main TreeWalker loop
						treeWalker.currentNode = n;
						// …and add it as another signature to this comment (regardless of the author and timestamp)
						foundSignature2 = this.findSignature( n, node );
						if ( foundSignature2.username ) {
							sigRanges.push( adjustSigRange( foundSignature2.nodes, match2, n ) );
							timestampRanges.push( match2.range );
						}
					}
					if ( event === 'leave' ) {
						// Take the last complete node which we skipped past
						endNode = n;
					}
				}.bind( this )
			);

			var length = endNode.nodeType === Node.TEXT_NODE ?
				endNode.textContent.replace( /[\t\n\f\r ]+$/, '' ).length :
				endNode.childNodes.length;
			range = {
				startContainer: startNode.parentNode,
				startOffset: utils.childIndexOf( startNode ),
				endContainer: endNode,
				endOffset: length
			};

			var startLevel = utils.getIndentLevel( startNode, this.rootNode ) + 1;
			var endLevel = utils.getIndentLevel( node, this.rootNode ) + 1;
			if ( startLevel !== endLevel ) {
				warnings.push( 'Comment starts and ends with different indentation' );
			}
			// Should this use the indent level of `startNode` or `node`?
			var level = Math.min( startLevel, endLevel );

			var parserResult = dfParsers[ match.parserIndex ]( match.matchData );
			if ( !parserResult ) {
				continue;
			}
			var dateTime = parserResult.date;
			if ( parserResult.warning ) {
				warnings.push( parserResult.warning );
			}

			curComment = new CommentItem(
				level,
				range,
				sigRanges,
				timestampRanges,
				dateTime,
				author,
				foundSignature.displayName
			);
			curComment.rootNode = this.rootNode;
			if ( warnings.length ) {
				curComment.warnings = warnings;
			}
			if ( result.isEmpty() ) {
				// Add a fake placeholder heading if there are any comments in the 0th section
				// (before the first real heading)
				range = {
					startContainer: this.rootNode,
					startOffset: 0,
					endContainer: this.rootNode,
					endOffset: 0
				};
				var fakeHeading = new HeadingItem( range, null );
				fakeHeading.rootNode = this.rootNode;
				result.addThreadItem( fakeHeading );
			}
			result.addThreadItem( curComment );
			curCommentEnd = curComment.range.endContainer;
		}
	}

	return result;
};

/**
 * Truncate user generated parts of IDs so full ID always fits within a database field of length 255
 *
 * @param {string} text Text
 * @return {string} Truncated text
 */
Parser.prototype.truncateForId = function ( text ) {
	return trimByteLength( '', text, 80 ).newVal;
};

/**
 * Given a thread item, return an identifier for it that is unique within the page.
 *
 * @param {ThreadItem} threadItem
 * @param {ThreadItemSet} previousItems
 * @return {string}
 */
Parser.prototype.computeId = function ( threadItem, previousItems ) {
	var id, headline;

	if ( threadItem instanceof HeadingItem && threadItem.placeholderHeading ) {
		// The range points to the root note, using it like below results in silly values
		id = 'h-';
	} else if ( threadItem instanceof HeadingItem ) {
		// <span class="mw-headline" …>, or <hN …> in Parsoid HTML
		headline = threadItem.range.startContainer;
		id = 'h-' + this.truncateForId( headline.getAttribute( 'id' ) || '' );
	} else if ( threadItem instanceof CommentItem ) {
		id = 'c-' + this.truncateForId( threadItem.author || '' ).replace( / /g, '_' ) + '-' + threadItem.getTimestampString();
	} else {
		throw new Error( 'Unknown ThreadItem type' );
	}

	// If there would be multiple comments with the same ID (i.e. the user left multiple comments
	// in one edit, or within a minute), append sequential numbers
	var threadItemParent = threadItem.parent;
	if ( threadItemParent instanceof HeadingItem && !threadItemParent.placeholderHeading ) {
		// <span class="mw-headline" …>, or <hN …> in Parsoid HTML
		headline = threadItemParent.range.startContainer;
		id += '-' + this.truncateForId( headline.getAttribute( 'id' ) || '' );
	} else if ( threadItemParent instanceof CommentItem ) {
		id += '-' + this.truncateForId( threadItemParent.author || '' ).replace( / /g, '_' ) + '-' + threadItemParent.getTimestampString();
	}

	if ( threadItem instanceof HeadingItem ) {
		// To avoid old threads re-appearing on popular pages when someone uses a vague title
		// (e.g. dozens of threads titled "question" on [[Wikipedia:Help desk]]: https://w.wiki/fbN),
		// include the oldest timestamp in the thread (i.e. date the thread was started) in the
		// heading ID.
		var oldestComment = threadItem.getOldestReply();
		if ( oldestComment ) {
			id += '-' + oldestComment.getTimestampString();
		}
	}

	if ( previousItems.findCommentById( id ) ) {
		// Well, that's tough
		threadItem.warnings.push( 'Duplicate comment ID' );
		// Finally, disambiguate by adding sequential numbers, to allow replying to both comments
		var number = 1;
		while ( previousItems.findCommentById( id + '-' + number ) ) {
			number++;
		}
		id = id + '-' + number;
	}

	return id;
};

/**
 * Given a thread item, return an identifier for it that is consistent across all pages and
 * revisions where this comment might appear.
 *
 * Multiple comments on a page can have the same name; use ID to distinguish them.
 *
 * @param {ThreadItem} threadItem
 * @return {string}
 */
Parser.prototype.computeName = function ( threadItem ) {
	var name, mainComment;

	if ( threadItem instanceof HeadingItem ) {
		name = 'h-';
		mainComment = threadItem.getOldestReply();
	} else if ( threadItem instanceof CommentItem ) {
		name = 'c-';
		mainComment = threadItem;
	} else {
		throw new Error( 'Unknown ThreadItem type' );
	}

	if ( mainComment ) {
		name += this.truncateForId( mainComment.author || '' ).replace( / /g, '_' ) +
			'-' + mainComment.getTimestampString();
	}

	return name;
};

/**
 * @param {ThreadItemSet} result
 */
Parser.prototype.buildThreads = function ( result ) {
	var lastHeading = null;
	var replies = [];

	var i, threadItem;
	for ( i = 0; i < result.threadItems.length; i++ ) {
		threadItem = result.threadItems[ i ];

		if ( replies.length < threadItem.level ) {
			// Someone skipped an indentation level (or several). Pretend that the previous reply
			// covers multiple indentation levels, so that following comments get connected to it.
			threadItem.warnings.push( 'Comment skips indentation level' );
			while ( replies.length < threadItem.level ) {
				replies[ replies.length ] = replies[ replies.length - 1 ];
			}
		}

		if ( threadItem instanceof HeadingItem ) {
			// New root (thread)
			// Attach as a sub-thread to preceding higher-level heading.
			// Any replies will appear in the tree twice, under the main-thread and the sub-thread.
			var maybeParent = lastHeading;
			while ( maybeParent && maybeParent.headingLevel >= threadItem.headingLevel ) {
				maybeParent = maybeParent.parent;
			}
			if ( maybeParent ) {
				threadItem.parent = maybeParent;
				maybeParent.replies.push( threadItem );
			}
			lastHeading = threadItem;
		} else if ( replies[ threadItem.level - 1 ] ) {
			// Add as a reply to the closest less-nested comment
			threadItem.parent = replies[ threadItem.level - 1 ];
			threadItem.parent.replies.push( threadItem );
		} else {
			threadItem.warnings.push( 'Comment could not be connected to a thread' );
		}

		replies[ threadItem.level ] = threadItem;
		// Cut off more deeply nested replies
		replies.length = threadItem.level + 1;
	}
};

/**
 * Set the IDs and names used to refer to comments and headings.
 * This has to be a separate pass because we don't have the list of replies before
 * this point.
 *
 * @param {ThreadItemSet} result
 */
Parser.prototype.computeIdsAndNames = function ( result ) {
	var i, threadItem;
	for ( i = 0; i < result.threadItems.length; i++ ) {
		threadItem = result.threadItems[ i ];

		var name = this.computeName( threadItem );
		threadItem.name = name;

		var id = this.computeId( threadItem, result );
		threadItem.id = id;

		result.updateIdAndNameMaps( threadItem );
	}
};

/**
 * @param {ThreadItem} threadItem
 * @return {CommentItem|null}
 */
Parser.prototype.getThreadStartComment = function ( threadItem ) {
	var oldest = null;
	if ( threadItem instanceof CommentItem ) {
		oldest = threadItem;
	}
	// Check all replies. This can't just use the first comment because threads are often summarized
	// at the top when the discussion is closed.
	for ( var i = 0; i < threadItem.replies.length; i++ ) {
		var comment = threadItem.replies[ i ];
		// Don't include sub-threads to avoid changing the ID when threads are "merged".
		if ( comment instanceof CommentItem ) {
			var oldestInReplies = this.getThreadStartComment( comment );
			if ( !oldest || oldestInReplies.timestamp.isBefore( oldest.timestamp ) ) {
				oldest = oldestInReplies;
			}
		}
	}
	return oldest;
};

module.exports = Parser;
