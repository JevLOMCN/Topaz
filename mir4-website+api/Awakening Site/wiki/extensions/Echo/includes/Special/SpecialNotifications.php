<?php

namespace MediaWiki\Extension\Notifications\Special;

use MediaWiki\Extension\Notifications\DataOutputFormatter;
use MediaWiki\Extension\Notifications\NotifUser;
use MediaWiki\Extension\Notifications\OOUI\LabelIconWidget;
use MediaWiki\Extension\Notifications\SeenTime;
use MediaWiki\Html\Html;
use MediaWiki\SpecialPage\SpecialPage;
use OOUI;

class SpecialNotifications extends SpecialPage {

	/**
	 * Number of notification records to display per page/load
	 */
	private const DISPLAY_NUM = 20;

	public function __construct() {
		parent::__construct( 'Notifications' );
	}

	/**
	 * @param string|null $par
	 */
	public function execute( $par ) {
		$this->setHeaders();

		$out = $this->getOutput();
		$out->setPageTitleMsg( $this->msg( 'echo-specialpage' ) );

		$this->addHelpLink( 'Help:Notifications/Special:Notifications' );

		$out->addJsConfigVars( 'wgNotificationsSpecialPageLinks', [
			'preferences' => SpecialPage::getTitleFor( 'Preferences', false, 'mw-prefsection-echo' )->getLinkURL(),
		] );

		$user = $this->getUser();
		if ( !$user->isRegistered() ) {
			// Redirect to login page and inform user of the need to login
			$this->requireLogin( 'echo-notification-loginrequired' );
			return;
		}

		$out->addSubtitle( $this->buildSubtitle() );

		$out->enableOOUI();

		$pager = new NotificationPager( $this->getContext() );
		$pager->setOffset( $this->getRequest()->getVal( 'offset' ) );
		$pager->setLimit( $this->getRequest()->getInt( 'limit', self::DISPLAY_NUM ) );
		$notifications = $pager->getNotifications();

		$noJSDiv = new OOUI\Tag();
		$noJSDiv->addClasses( [ 'mw-echo-special-nojs' ] );

		// If there are no notifications, display a message saying so
		if ( !$notifications ) {
			// Wrap this with nojs so it is still hidden if JS is loading
			$noJSDiv->appendContent(
				new OOUI\LabelWidget( [ 'label' => $this->msg( 'echo-none' )->text() ] )
			);
			$out->addHTML( $noJSDiv );
			$out->addModules( [ 'ext.echo.special' ] );
			return;
		}

		$notif = [];
		foreach ( $notifications as $notification ) {
			$output = DataOutputFormatter::formatOutput( $notification, 'special', $user, $this->getLanguage() );
			if ( $output ) {
				$notif[] = $output;
			}
		}

		// Add the notifications to the page (interspersed with date headers)
		$dateHeader = '';
		$anyUnread = false;
		$seenTime = SeenTime::newFromUser( $user )->getTime();
		$notifArray = [];
		foreach ( $notif as $row ) {
			if ( !$row['*'] ) {
				continue;
			}

			$classes = [ 'mw-echo-notification' ];

			if ( $seenTime !== null && $row['timestamp']['mw'] > $seenTime ) {
				$classes[] = 'mw-echo-notification-unseen';
			}

			// Output the date header if it has not been displayed
			if ( $dateHeader !== $row['timestamp']['date'] ) {
				$dateHeader = $row['timestamp']['date'];
			}
			// Collect unread IDs
			if ( !isset( $row['read'] ) ) {
				$classes[] = 'mw-echo-notification-unread';
				$anyUnread = true;
				$notifArray[ $dateHeader ][ 'unread' ][] = $row['id'];
			}

			$li = new OOUI\Tag( 'li' );
			$li
				->addClasses( $classes )
				->setAttributes( [
					'data-notification-category' => $row['category'],
					'data-notification-event' => $row['id'],
					'data-notification-type' => $row['type']
				] )
				->appendContent( new OOUI\HtmlSnippet( $row['*'] ) );

			// Store
			$notifArray[ $dateHeader ][ 'notices' ][] = $li;
		}

		$markAllAsReadFormWrapper = '';
		// Ensure there are some unread notifications
		if ( $anyUnread ) {
			$markReadSpecialPage = new SpecialNotificationsMarkRead();
			$markReadSpecialPage->setContext( $this->getContext() );
			$notifUser = NotifUser::newFromUser( $user );
			$unreadCount = $notifUser->getAlertCount() + $notifUser->getMessageCount();

			$markAllAsReadText = $this
				->msg( 'echo-mark-all-as-read' )
				->numParams( $unreadCount )
				->text();
			$markAllAsReadLabelIcon = new LabelIconWidget( [
				'label' => $markAllAsReadText,
				'icon' => 'checkAll',
			] );

			$markAllAsReadForm = $markReadSpecialPage->getMinimalForm(
				[ 'ALL' ],
				$markAllAsReadText,
				true,
				$markAllAsReadLabelIcon->toString()
			);

			// First submission attempt
			$formHtml = $markAllAsReadForm->prepareForm()->getHTML( false );

			$markAllAsReadFormWrapper = new OOUI\Tag();
			$markAllAsReadFormWrapper
				->addClasses( [ 'mw-echo-special-markAllReadButton' ] )
				->appendContent( new OOUI\HtmlSnippet( $formHtml ) );
		}

		// Build the list
		$notices = new OOUI\Tag( 'ul' );
		$notices->addClasses( [ 'mw-echo-special-notifications' ] );

		$markReadSpecialPage = new SpecialNotificationsMarkRead();
		$markReadSpecialPage->setContext( $this->getContext() );
		foreach ( $notifArray as $section => $data ) {
			$heading = ( new OOUI\Tag( 'li' ) )->addClasses( [ 'mw-echo-date-section' ] );

			$dateTitle = new OOUI\LabelWidget( [
				'classes' => [ 'mw-echo-date-section-text' ],
				'label' => $section
			] );

			$heading->appendContent( $dateTitle );

			// Mark all read button
			if ( isset( $data[ 'unread' ] ) ) {
				// tell the UI to show 'unread' notifications only (instead of 'all')
				$out->addJsConfigVars( 'wgEchoReadState', 'unread' );

				$markReadSectionText = $this->msg( 'echo-specialpage-section-markread' )->text();
				$markAsReadLabelIcon = new LabelIconWidget( [
					'label' => $markReadSectionText,
					'icon' => 'checkAll',
				] );

				// There are unread notices. Add the 'mark section as read' button
				$markSectionAsReadForm = $markReadSpecialPage->getMinimalForm(
					$data[ 'unread' ],
					$markReadSectionText,
					true,
					$markAsReadLabelIcon->toString()
				);

				// First submission attempt
				$formHtml = $markSectionAsReadForm->prepareForm()->getHTML( false );

				$formWrapper = new OOUI\Tag();
				$formWrapper
					->addClasses( [ 'mw-echo-markAsReadSectionButton' ] )
					->appendContent( new OOUI\HtmlSnippet( $formHtml ) );

				$heading->appendContent( $formWrapper );
			}

			// These two must be separate, because $data[ 'notices' ]
			// is an array
			$notices
				->appendContent( $heading )
				// @phan-suppress-next-line PhanTypePossiblyInvalidDimOffset https://github.com/phan/phan/issues/4735
				->appendContent( $data[ 'notices' ] );
		}

		$navBar = $pager->getNavigationBar();

		$navTop = new OOUI\Tag();
		$navBottom = new OOUI\Tag();
		$container = new OOUI\Tag();

		$navTop
			->addClasses( [ 'mw-echo-special-navbar-top' ] )
			->appendContent( new OOUI\HtmlSnippet( $navBar ) );
		$navBottom
			->addClasses( [ 'mw-echo-special-navbar-bottom' ] )
			->appendContent( new OOUI\HtmlSnippet( $navBar ) );

		// Put it all together
		$container
			->addClasses( [ 'mw-echo-special-container' ] )
			->appendContent(
				$navTop,
				$markAllAsReadFormWrapper,
				$notices,
				$navBottom
			);

		// Wrap with nojs div
		$noJSDiv->appendContent( $container );

		$out->addHTML( $noJSDiv );

		$out->addModules( [ 'ext.echo.special' ] );

		// For no-js support
		$out->addModuleStyles( [
			'ext.echo.styles.notifications',
			'ext.echo.styles.special',
			'oojs-ui.styles.icons-alerts',
			'oojs-ui.styles.icons-interactions',
		] );
	}

	/**
	 * Build the subtitle (more info and preference links)
	 * @return string HTML for the subtitle
	 */
	public function buildSubtitle() {
		$lang = $this->getLanguage();
		$subtitleLinks = [];
		// Preferences link
		$subtitleLinks[] = Html::element(
			'a',
			[
				'href' => SpecialPage::getTitleFor( 'Preferences', false, 'mw-prefsection-echo' )->getLinkURL(),
				'id' => 'mw-echo-pref-link',
				'class' => 'mw-echo-special-header-link',
				'title' => $this->msg( 'preferences' )->text()
			],
			$this->msg( 'preferences' )->text()
		);
		// using pipeList to make it easier to add some links in the future
		return $lang->pipeList( $subtitleLinks );
	}

	protected function getGroupName() {
		return 'login';
	}
}
