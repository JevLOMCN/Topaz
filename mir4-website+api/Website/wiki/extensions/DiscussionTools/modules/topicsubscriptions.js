/* global moment */
var
	api,
	seenAutoTopicSubPopup = !!+mw.user.options.get( 'discussiontools-seenautotopicsubpopup' ),
	STATE_UNSUBSCRIBED = 0,
	STATE_SUBSCRIBED = 1,
	STATE_AUTOSUBSCRIBED = 2,
	utils = require( './utils.js' ),
	CommentItem = require( './CommentItem.js' ),
	HeadingItem = require( './HeadingItem.js' ),
	linksByName = {},
	buttonsByName = {};

/**
 * Update a subscribe link
 *
 * @param {HTMLElement} element Subscribe link
 * @param {number|null} state State constant (STATE_UNSUBSCRIBED, STATE_SUBSCRIBED or STATE_AUTOSUBSCRIBED)
 * @param {HTMLElement|null} labelElement Subscribe link, if different to element
 * @param {boolean} isNewTopics Is a subscribe link for new topics subscriptions
 */
function updateSubscribeLink( element, state, labelElement, isNewTopics ) {
	labelElement = labelElement || element;
	if ( state !== null ) {
		element.setAttribute( 'data-mw-subscribed', String( state ) );
	}
	if ( state ) {
		labelElement.textContent = mw.msg( isNewTopics ?
			'discussiontools-newtopicssubscription-button-unsubscribe-label' :
			'discussiontools-topicsubscription-button-unsubscribe' );
		element.setAttribute( 'title', mw.msg( isNewTopics ?
			'discussiontools-newtopicssubscription-button-unsubscribe-tooltip' :
			'discussiontools-topicsubscription-button-unsubscribe-tooltip' )
		);
	} else {
		labelElement.textContent = mw.msg( isNewTopics ?
			'discussiontools-newtopicssubscription-button-subscribe-label' :
			'discussiontools-topicsubscription-button-subscribe' );
		element.setAttribute( 'title', mw.msg( isNewTopics ?
			'discussiontools-newtopicssubscription-button-subscribe-tooltip' :
			'discussiontools-topicsubscription-button-subscribe-tooltip' )
		);
	}
}

/**
 * Update a subscribe button
 *
 * @param {OO.ui.ButtonWidget} button Subscribe button
 * @param {number|null} state State constant (STATE_UNSUBSCRIBED, STATE_SUBSCRIBED or STATE_AUTOSUBSCRIBED)
 */
function updateSubscribeButton( button, state ) {
	if ( state !== null ) {
		button.$element[ 0 ].setAttribute( 'data-mw-subscribed', String( state ) );
	}
	if ( state ) {
		button.setIcon( 'bell' );
		button.setLabel( mw.msg( 'discussiontools-topicsubscription-button-unsubscribe-label' ) );
		button.setTitle( mw.msg( 'discussiontools-topicsubscription-button-unsubscribe-tooltip' ) );
	} else {
		button.setIcon( 'bellOutline' );
		button.setLabel( mw.msg( 'discussiontools-topicsubscription-button-subscribe-label' ) );
		button.setTitle( mw.msg( 'discussiontools-topicsubscription-button-subscribe-tooltip' ) );
	}
}

/**
 * Change the subscription state of a topic subscription
 *
 * @param {string} title Page title
 * @param {string} commentName Comment name
 * @param {boolean} subscribe Subscription state
 * @param {boolean} isNewTopics Subscription is for new topics
 * @return {jQuery.Promise} Promise which resolves after change of state
 */
function changeSubscription( title, commentName, subscribe, isNewTopics ) {
	var promise = api.postWithToken( 'csrf', {
		action: 'discussiontoolssubscribe',
		page: title,
		commentname: commentName,
		subscribe: subscribe
	} ).then( function ( response ) {
		return OO.getProp( response, 'discussiontoolssubscribe' ) || {};
	} );

	promise.then( function ( result ) {
		mw.notify(
			mw.msg(
				result.subscribe ?
					(
						isNewTopics ?
							'discussiontools-newtopicssubscription-notify-subscribed-body' :
							'discussiontools-topicsubscription-notify-subscribed-body'
					) :
					(
						isNewTopics ?
							'discussiontools-newtopicssubscription-notify-unsubscribed-body' :
							'discussiontools-topicsubscription-notify-unsubscribed-body'
					)
			),
			{
				title: mw.msg(
					result.subscribe ?
						(
							isNewTopics ?
								'discussiontools-newtopicssubscription-notify-subscribed-title' :
								'discussiontools-topicsubscription-notify-subscribed-title'
						) :
						(
							isNewTopics ?
								'discussiontools-newtopicssubscription-notify-unsubscribed-title' :
								'discussiontools-topicsubscription-notify-unsubscribed-title'
						)
				)
			}
		);
	}, function ( code, data ) {
		mw.notify( api.getErrorMessage( data ), { type: 'error' } );
	} );

	return promise;
}

function getSubscribedStateFromElement( element ) {
	return element.hasAttribute( 'data-mw-subscribed' ) ? Number( element.getAttribute( 'data-mw-subscribed' ) ) : null;
}

/**
 * Lazy load API to avoid circular dependency
 */
function initApi() {
	if ( !api ) {
		api = require( './controller.js' ).getApi();
	}
}

/**
 * Initialize topic subscriptions feature
 *
 * @param {jQuery} $container Page container
 * @param {ThreadItemSet} threadItemSet
 */
function initTopicSubscriptions( $container, threadItemSet ) {
	linksByName = {};
	buttonsByName = {};

	initApi();

	// Subscription buttons (visual enhancements)
	$container.find( '.ext-discussiontools-init-section-subscribeButton' ).each( function () {
		// These attributes will be lost when infusing
		// TODO: Could also be fixed by subclassing ButtonWidget in PHP
		var subscribedStateTemp = getSubscribedStateFromElement( this );

		var id = $( this ).closest( '.ext-discussiontools-init-section' )
			.find( '[data-mw-comment-start]' ).attr( 'id' );
		var headingItem = threadItemSet.findCommentById( id );

		if ( !( headingItem instanceof HeadingItem ) ) {
			// This should never happen
			return;
		}

		var name = headingItem.name;
		var button = OO.ui.infuse( this );
		buttonsByName[ name ] = button;

		// Restore data attribute
		if ( subscribedStateTemp !== null ) {
			button.$element[ 0 ].setAttribute( 'data-mw-subscribed', String( subscribedStateTemp ) );
		}

		var title = mw.config.get( 'wgRelevantPageName' ) + '#' + headingItem.getLinkableTitle();

		button.on( 'click', function () {
			// Get latest subscribedState
			var subscribedState = getSubscribedStateFromElement( button.$element[ 0 ] );

			button.setDisabled( true );
			changeSubscription( title, name, !subscribedState )
				.then( function ( result ) {
					updateSubscribeButton( button, result.subscribe ? STATE_SUBSCRIBED : STATE_UNSUBSCRIBED );
				} )
				.always( function () {
					button.setDisabled( false );
				} );
		} );
	} );

	// Subscription links (no visual enhancements)
	$container.find( '.ext-discussiontools-init-section-subscribe-link' ).each( function () {
		var $link = $( this );
		var id = $link.closest( '.ext-discussiontools-init-section' )
			.find( '[data-mw-comment-start]' ).attr( 'id' );
		var headingItem = threadItemSet.findCommentById( id );

		if ( !( headingItem instanceof HeadingItem ) ) {
			// This should never happen
			return;
		}

		var itemName = headingItem.name;
		var title = mw.config.get( 'wgRelevantPageName' ) + '#' + headingItem.getLinkableTitle();

		linksByName[ itemName ] = this;

		$link.on( 'click keypress', function ( e ) {
			if ( e.type === 'keypress' && e.which !== OO.ui.Keys.ENTER && e.which !== OO.ui.Keys.SPACE ) {
				// Only handle keypresses on the "Enter" or "Space" keys
				return;
			}
			if ( e.type === 'click' && !utils.isUnmodifiedLeftClick( e ) ) {
				// Only handle unmodified left clicks
				return;
			}

			e.preventDefault();

			// Get latest subscribedState
			var subscribedState = getSubscribedStateFromElement( $link[ 0 ] );

			$link.addClass( 'ext-discussiontools-init-section-subscribe-link-pending' );
			changeSubscription( title, itemName, !subscribedState )
				.then( function ( result ) {
					updateSubscribeLink( $link[ 0 ], result.subscribe ? STATE_SUBSCRIBED : STATE_UNSUBSCRIBED );
				} )
				.always( function () {
					$link.removeClass( 'ext-discussiontools-init-section-subscribe-link-pending' );
				} );
		} );
	} );

	initNewTopicsSubscription();
}

/**
 * Bind new topics subscription button
 *
 * Note: because this function can get called from `wikipage.content`,
 * and we're interacting with elements outside of $container, make
 * sure to account for this possibly being run multiple times on a
 * pageload. Calls from DT's own previews are filtered out, but other
 * page actions like live-preview can still reach this point.
 */
function initNewTopicsSubscription() {
	var $button, $label, $icon;

	initApi();

	if ( mw.config.get( 'skin' ) === 'minerva' ) {
		// eslint-disable-next-line no-jquery/no-global-selector
		$button = $( '.menu__item--page-actions-overflow-t-page-subscribe' );
		$label = $button.find( '.toggle-list-item__label' );
		$icon = $button.find( '.minerva-icon' );
		// HACK: We can't set data-mw-subscribed intially in Minerva, so work it out from the icon
		// eslint-disable-next-line no-jquery/no-class-state
		var initialState = $icon.hasClass( 'minerva-icon--bell' ) ? STATE_SUBSCRIBED : STATE_UNSUBSCRIBED;
		$button.attr( 'data-mw-subscribed', String( initialState ) );
	} else {
		// eslint-disable-next-line no-jquery/no-global-selector
		$button = $( '#ca-dt-page-subscribe > a' );
		$label = $button.find( 'span' );
		$icon = $( [] );
	}

	var titleObj = mw.Title.newFromText( mw.config.get( 'wgRelevantPageName' ) );
	var name = utils.getNewTopicsSubscriptionId( titleObj );

	$button.off( '.mw-dt-topicsubscriptions' ).on( 'click.mw-dt-topicsubscriptions', function ( e ) {
		e.preventDefault();
		// Get latest subscribedState
		var subscribedState = getSubscribedStateFromElement( $button[ 0 ] );

		changeSubscription( titleObj.getPrefixedText(), name, !subscribedState, true )
			.then( function ( result ) {
				updateSubscribeLink( $button[ 0 ], result.subscribe ? STATE_SUBSCRIBED : STATE_UNSUBSCRIBED, $label[ 0 ], true );
				$icon.toggleClass( 'minerva-icon--bell', !!result.subscribe );
				$icon.toggleClass( 'minerva-icon--bellOutline', !result.subscribe );
			} );
	} );
}

function initSpecialTopicSubscriptions() {
	api = require( './controller.js' ).getApi();

	// Unsubscribe links on special page
	// eslint-disable-next-line no-jquery/no-global-selector
	$( '.ext-discussiontools-special-unsubscribe-button' ).each( function () {
		var button = OO.ui.infuse( this );
		var data = button.getData();
		var subscribedState = STATE_SUBSCRIBED;

		button.on( 'click', function () {
			button.setDisabled( true );
			changeSubscription( data.title, data.item, !subscribedState )
				.then( function ( result ) {
					button.setLabel( mw.msg(
						result.subscribe ?
							'discussiontools-topicsubscription-button-unsubscribe-label' :
							'discussiontools-topicsubscription-button-subscribe-label'
					) );
					button.clearFlags();
					button.setFlags( [ result.subscribe ? 'destructive' : 'progressive' ] );
					subscribedState = result.subscribe ? STATE_SUBSCRIBED : STATE_UNSUBSCRIBED;
				} ).always( function () {
					button.setDisabled( false );
				} );
		} );
	} );
}

/**
 * Show the first time popup for auto topic subscriptions, if required
 */
function maybeShowFirstTimeAutoTopicSubPopup() {
	var lastHighlightComment = require( './highlighter.js' ).getLastHighlightedPublishedComment();

	if ( !lastHighlightComment || seenAutoTopicSubPopup ) {
		return;
	}

	seenAutoTopicSubPopup = true;
	mw.user.options.set( 'discussiontools-seenautotopicsubpopup', '1' );
	api.saveOption( 'discussiontools-seenautotopicsubpopup', '1' );

	var $popupContent, popup;

	function close() {
		popup.$element.removeClass( 'ext-discussiontools-autotopicsubpopup-fadein' );
		setTimeout( function () {
			popup.$element.detach();
		}, 1000 );
	}

	$popupContent = $( '<div>' )
		.append(
			$( '<strong>' )
				.addClass( 'ext-discussiontools-autotopicsubpopup-title' )
				.text( mw.msg( 'discussiontools-autotopicsubpopup-title' ) ),
			$( '<div>' )
				.addClass( 'ext-discussiontools-autotopicsubpopup-image' ),
			$( '<div>' )
				.addClass( 'ext-discussiontools-autotopicsubpopup-body' )
				.text( mw.msg( 'discussiontools-autotopicsubpopup-body' ) ),
			$( '<div>' )
				.addClass( 'ext-discussiontools-autotopicsubpopup-actions' )
				.append( new OO.ui.ButtonWidget( {
					label: mw.msg( 'discussiontools-autotopicsubpopup-dismiss' ),
					flags: [ 'primary', 'progressive' ]
				} ).on( 'click', close ).$element )
				.append( new OO.ui.ButtonWidget( {
					label: mw.msg( 'discussiontools-autotopicsubpopup-preferences' ),
					href: mw.util.getUrl( 'Special:Preferences#mw-prefsection-editing-discussion' ),
					flags: [ 'progressive' ],
					framed: false
				} ).$element )
		);

	popup = new OO.ui.PopupWidget( {
		// Styles and dimensions
		width: '',
		height: '',
		anchor: false,
		autoClose: false,
		head: false,
		padded: false,
		classes: [ 'ext-discussiontools-autotopicsubpopup' ],
		hideWhenOutOfView: false,
		// Content
		$content: $popupContent.contents()
	} );

	// Like in highlight()
	lastHighlightComment.getRange().insertNode( popup.$element[ 0 ] );
	// Pull it outside of headings to avoid silly fonts
	if ( popup.$element.closest( 'h1, h2, h3, h4, h5, h6' ).length ) {
		popup.$element.closest( 'h1, h2, h3, h4, h5, h6' ).after( popup.$element );
	}
	if ( popup.$element.closest( '.mw-heading' ).length ) {
		popup.$element.closest( '.mw-heading' ).after( popup.$element );
	}

	// Disable positioning, the popup is positioned in CSS, above the highlight
	popup.toggle( true ).toggleClipping( false ).togglePositioning( false );

	// If the page is very short, there might not be enough space above the highlight,
	// causing the popup to overlap the skin navigation or even be off-screen.
	// Position it on top of the highlight in that case...
	// eslint-disable-next-line no-jquery/no-global-selector
	if ( popup.$popup[ 0 ].getBoundingClientRect().top < $( '.mw-body' )[ 0 ].getBoundingClientRect().top ) {
		popup.$popup.addClass( 'ext-discussiontools-autotopicsubpopup-overlap' );
	}

	// Scroll into view, leave some space above to avoid overlapping .postedit-container
	OO.ui.Element.static.scrollIntoView(
		popup.$popup[ 0 ],
		{
			padding: {
				// Add padding to avoid overlapping the post-edit notification (above on desktop, below on mobile)
				top: OO.ui.isMobile() ? 10 : 60,
				bottom: OO.ui.isMobile() ? 85 : 10
			},
			// Specify scrollContainer for compatibility with MobileFrontend.
			// Apparently it makes `<dd>` elements scrollable and OOUI tried to scroll them instead of body.
			scrollContainer: OO.ui.Element.static.getRootScrollableElement( popup.$popup[ 0 ] )
		}
	);

	popup.$element.addClass( 'ext-discussiontools-autotopicsubpopup-fadein' );
}

/**
 * Update the subscription state of various topics
 *
 * @param {jQuery} $container Page container
 * @param {Object.<string, HeadingItem>} headingsToUpdate Headings of topics where subscription state has changed
 */
function updateSubscriptionStates( $container, headingsToUpdate ) {
	// This method is called when we recently edited this page, and auto-subscriptions might have been
	// added for some topics. It updates the [subscribe] buttons to reflect the new subscriptions.

	// If the topic is already marked as auto-subscribed, there's nothing to do.
	// (Except maybe show the first-time popup.)
	// If the topic is marked as having never been subscribed, check if they are auto-subscribed now.
	var topicsToCheck = [];
	var pendingLinks = [];
	var pendingButtons = [];
	for ( var headingName in headingsToUpdate ) {
		var link = linksByName[ headingName ];
		var button = buttonsByName[ headingName ];
		var subscribedState = getSubscribedStateFromElement( link || button.$element[ 0 ] );

		if ( subscribedState === STATE_AUTOSUBSCRIBED ) {
			maybeShowFirstTimeAutoTopicSubPopup();
		} else if ( subscribedState === null || subscribedState === STATE_UNSUBSCRIBED ) {
			topicsToCheck.push( headingName );
			if ( link ) {
				pendingLinks.push( link );
			}
			if ( button ) {
				pendingButtons.push( button );
			}
		}
	}
	$( pendingLinks ).addClass( 'ext-discussiontools-init-section-subscribe-link-pending' );
	pendingButtons.forEach( function ( b ) {
		b.setDisabled( true );
	} );

	if ( !topicsToCheck.length ) {
		return;
	}

	api.get( {
		action: 'discussiontoolsgetsubscriptions',
		commentname: topicsToCheck
	} ).then( function ( response ) {
		if ( $.isEmptyObject( response.subscriptions ) ) {
			// If none of the topics has an auto-subscription yet, wait a moment and check again.
			// updateSubscriptionStates() method is only called if we're really expecting one to be there.
			// (There are certainly neater ways to implement this, involving push notifications or at
			// least long-polling or something. But this is the simplest one!)
			var wait = $.Deferred();
			setTimeout( wait.resolve, 5000 );
			return wait.then( function () {
				return api.get( {
					action: 'discussiontoolsgetsubscriptions',
					commentname: topicsToCheck
				} );
			} );
		}
		return response;
	} ).then( function ( response ) {
		// Update state of each topic for which there is a subscription
		for ( var subItemName in response.subscriptions ) {
			var state = response.subscriptions[ subItemName ];
			if ( linksByName[ subItemName ] ) {
				updateSubscribeLink( linksByName[ subItemName ], state );
			}
			if ( buttonsByName[ subItemName ] ) {
				updateSubscribeButton( buttonsByName[ subItemName ], state );
			}
			if ( state === STATE_AUTOSUBSCRIBED ) {
				maybeShowFirstTimeAutoTopicSubPopup();
			}
		}
	} ).always( function () {
		$( pendingLinks ).removeClass( 'ext-discussiontools-init-section-subscribe-link-pending' );
		pendingButtons.forEach( function ( b ) {
			b.setDisabled( false );
		} );
	} );
}

/**
 * Update subscription state of just-posted new topics
 *
 * @param {jQuery} $container Page container
 * @param {ThreadItemSet} threadItemSet
 * @param {string} [threadItemId] Just-posted comment ID (or NEW_TOPIC_COMMENT_ID)
 */
function updateAutoSubscriptionStates( $container, threadItemSet, threadItemId ) {
	var recentComments = [];
	var headingsToUpdate = {};
	if ( threadItemId ) {
		// Edited by using the reply tool or new topic tool. Only check the edited topic.
		if ( threadItemId === utils.NEW_TOPIC_COMMENT_ID ) {
			recentComments.push( threadItemSet.threadItems[ threadItemSet.threadItems.length - 1 ] );
		} else {
			recentComments.push( threadItemSet.threadItemsById[ threadItemId ] );
		}
	} else if ( mw.config.get( 'wgPostEdit' ) ) {
		// Edited by using wikitext editor. Check topics with their own comments within last minute.
		for ( var i = 0; i < threadItemSet.threadItems.length; i++ ) {
			if (
				threadItemSet.threadItems[ i ] instanceof CommentItem &&
				threadItemSet.threadItems[ i ].author === mw.user.getName() &&
				threadItemSet.threadItems[ i ].timestamp.isSameOrAfter( moment().subtract( 1, 'minute' ), 'minute' )
			) {
				recentComments.push( threadItemSet.threadItems[ i ] );
			}
		}
	}
	recentComments.forEach( function ( recentComment ) {
		var headingItem = recentComment.getSubscribableHeading();
		if ( headingItem ) {
			// Use names as object keys to deduplicate if there are multiple comments in a topic.
			headingsToUpdate[ headingItem.name ] = headingItem;
		}
	} );
	updateSubscriptionStates( $container, headingsToUpdate );
}

module.exports = {
	initTopicSubscriptions: initTopicSubscriptions,
	initSpecialTopicSubscriptions: initSpecialTopicSubscriptions,
	initNewTopicsSubscription: initNewTopicsSubscription,
	updateAutoSubscriptionStates: updateAutoSubscriptionStates
};
