<?php

namespace MediaWiki\Extension\Notifications\Formatters;

use Language;
use MediaWiki\Parser\Sanitizer;
use MediaWiki\SpecialPage\SpecialPage;
use MediaWiki\User\User;

class EchoPlainTextDigestEmailFormatter extends EchoEventDigestFormatter {

	/**
	 * @var string 'daily' or 'weekly'
	 */
	protected $digestMode;

	public function __construct( User $user, Language $language, $digestMode ) {
		parent::__construct( $user, $language );
		$this->digestMode = $digestMode;
	}

	/**
	 * @param EchoEventPresentationModel[] $models
	 * @return string[] Array of the following format:
	 *               [ 'body'    => formatted email body,
	 *                 'subject' => formatted email subject ]
	 */
	protected function formatModels( array $models ) {
		$content = [];
		foreach ( $models as $model ) {
			$content[$model->getCategory()][] = Sanitizer::stripAllTags( $model->getHeaderMessage()->parse() );
		}

		ksort( $content );

		// echo-email-batch-body-intro-daily
		// echo-email-batch-body-intro-weekly
		$text = $this->msg( 'echo-email-batch-body-intro-' . $this->digestMode )
			->params( $this->user->getName() )->text();

		// Does this need to be a message?
		$bullet = $this->msg( 'echo-email-batch-bullet' )->text();

		foreach ( $content as $type => $items ) {
			$text .= "\n\n--\n\n";
			$text .= $this->getCategoryTitle( $type, count( $items ) );
			$text .= "\n";
			foreach ( $items as $item ) {
				$text .= "\n$bullet $item";
			}
		}

		$colon = $this->msg( 'colon-separator' )->text();
		$text .= "\n\n--\n\n";
		$viewAll = $this->msg( 'echo-email-batch-link-text-view-all-notifications' )->text();
		$link = SpecialPage::getTitleFor( 'Notifications' )->getFullURL( '', false, PROTO_CANONICAL );
		$text .= "$viewAll$colon <$link>";

		$plainTextFormatter = new EchoPlainTextEmailFormatter( $this->user, $this->language );

		$text .= "\n\n{$plainTextFormatter->getFooter()}";

		// echo-email-batch-subject-daily
		// echo-email-batch-subject-weekly
		$subject = $this->msg( 'echo-email-batch-subject-' . $this->digestMode )
			->numParams( count( $models ), count( $models ) )
			->text();

		return [
			'subject' => $subject,
			'body' => $text,
		];
	}

	/**
	 * @param string $type Notification type
	 * @param int $count Number of notifications in this type's section
	 * @return string Formatted category section title
	 */
	private function getCategoryTitle( $type, $count ) {
		return $this->msg( "echo-category-title-$type" )
			->numParams( $count )
			->text();
	}
}
