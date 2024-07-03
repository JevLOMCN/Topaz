var
	CommentItem = require( 'ext.discussionTools.init' ).CommentItem,
	HeadingItem = require( 'ext.discussionTools.init' ).HeadingItem;

QUnit.module( 'mw.dt.ThreadItem', QUnit.newMwEnvironment() );

QUnit.test( '#getAuthorsBelow/#getThreadItemsBelow', function ( assert ) {
	var cases = require( '../cases/authors.json' );

	function newFromJSON( json ) {
		var item;
		if ( json.type === 'heading' ) {
			item = new HeadingItem();
		} else {
			item = new CommentItem();
			item.author = json.author;
			item.displayName = json.displayName;
		}
		item.id = json.id;
		item.replies = json.replies.map( newFromJSON );
		return item;
	}

	cases.forEach( function ( caseItem ) {
		var threadItem = newFromJSON( caseItem.thread ),
			authors = threadItem.getAuthorsBelow();

		assert.deepEqual(
			authors,
			caseItem.expectedAuthorsBelow,
			'getAuthorsBelow'
		);

		assert.deepEqual(
			threadItem.getThreadItemsBelow().map( function ( item ) {
				return item.id;
			} ),
			caseItem.expectedThreadItemIdsBelow
		);
	} );
} );

// TODO:
// * getHeading (CommentItem+HeadingItem)
// * getLinkableTitle (HeadingItem)
// * newFromJSON (ThreadItem)
