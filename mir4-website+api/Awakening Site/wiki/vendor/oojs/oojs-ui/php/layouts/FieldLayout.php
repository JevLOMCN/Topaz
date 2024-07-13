<?php

namespace OOUI;

/**
 * Layout made of a field and optional label.
 *
 * Available label alignment modes include:
 *  - left: Label is before the field and aligned away from it, best for when the user will be
 *    scanning for a specific label in a form with many fields
 *  - right: Label is before the field and aligned toward it, best for forms the user is very
 *    familiar with and will tab through field checking quickly to verify which field they are in
 *  - top: Label is before the field and above it, best for when the user will need to fill out all
 *    fields from top to bottom in a form with few fields
 *  - inline: Label is after the field and aligned toward it, best for small boolean fields like
 *    checkboxes or radio buttons
 */
class FieldLayout extends Layout {
	use LabelElement;
	use TitledElement;

	/**
	 * Alignment.
	 *
	 * @var string
	 */
	protected $align;

	/**
	 * Field widget to be laid out.
	 *
	 * @var Widget
	 */
	protected $fieldWidget;

	/**
	 * Error messages.
	 *
	 * @var array
	 */
	protected $errors;

	/**
	 * Warning messages.
	 *
	 * @var array
	 */
	protected $warnings;

	/**
	 * Success messages.
	 *
	 * @var array
	 */
	protected $successMessages;

	/**
	 * Notice messages.
	 *
	 * @var array
	 */
	protected $notices;

	/**
	 * @var ButtonWidget|string
	 */
	protected $help;

	/** @var Tag */
	protected $field;
	/** @var Tag */
	protected $header;
	/** @var Tag */
	protected $body;
	/** @var Tag */
	protected $messages;
	/** @var string */
	protected $helpText;
	/** @var string|false */
	protected $helpInline;

	/**
	 * @param Widget $fieldWidget Field widget. An exception is thrown if no widget is specified.
	 * @param array $config Configuration options
	 *      - string $config['align'] Alignment mode, either 'left', 'right', 'top' or 'inline'
	 *          (default: 'left')
	 *      - string[]|HtmlSnippet[] $config['errors'] Error messages about the widget.
	 *      - string[]|HtmlSnippet[] $config['warnings'] Warning messages about the widget.
	 *      - string[]|HtmlSnippet[] $config['notices'] Notices about the widget.
	 *      - string|HtmlSnippet $config['help'] Explanatory text shown as a '?' icon, or inline.
	 *      - bool $config['helpInline'] Whether or not the help should be inline,
	 *          or shown when the "help" icon is clicked. (default: false)
	 */
	public function __construct( $fieldWidget, array $config = [] ) {
		// Allow passing positional parameters inside the config array
		if ( is_array( $fieldWidget ) && isset( $fieldWidget['fieldWidget'] ) ) {
			$config = $fieldWidget;
			$fieldWidget = $config['fieldWidget'];
		}

		// Make sure we have required constructor arguments
		if ( $fieldWidget === null ) {
			throw new Exception( 'Widget not found' );
		}

		// Config initialization
		if ( ( $config['help'] ?? '' ) !== '' && ( $config['label'] ?? '' ) === '' ) {
			// Add an empty label. For some combinations of 'helpInline' and 'align'
			// there would be no space in the interface to display the help text otherwise.
			$config['label'] = ' ';
		}

		// Parent constructor
		parent::__construct( $config );

		// Properties
		$this->fieldWidget = $fieldWidget;
		$this->errors = $config['errors'] ?? [];
		$this->warnings = $config['warnings'] ?? [];
		$this->successMessages = $config['successMessages'] ?? [];
		$this->notices = $config['notices'] ?? [];
		$this->field = $this->isFieldInline() ? new Tag( 'span' ) : new Tag( 'div' );
		$this->messages = new Tag( 'div' );
		$this->header = new Tag( 'span' );
		$this->body = new Tag( 'div' );
		$this->helpText = $config['help'] ?? '';
		$this->helpInline = $config['helpInline'] ?? false;

		// Traits
		$this->initializeLabelElement( array_merge( [
			'labelElement' => new Tag( 'label' )
		], $config ) );
		$this->initializeTitledElement(
			array_merge( [ 'titled' => $this->label ], $config )
		);

		// Initialization
		$this->help = $this->helpText === '' ? '' : $this->createHelpElement();
		if ( $this->fieldWidget->getInputId() ) {
			$this->label->setAttributes( [ 'for' => $this->fieldWidget->getInputId() ] );
			if ( $this->helpText !== '' && $this->helpInline ) {
				$this->help->setAttributes( [ 'for' => $this->fieldWidget->getInputId() ] );
			}
		} else {
			// We can't use `label for` with non-form elements, use `aria-labelledby` instead
			$id = Tag::generateElementId();
			$this->label->setAttributes( [ 'id' => $id ] );
			$this->fieldWidget->setLabelledBy( $id );
		}
		$this
			->addClasses( [ 'oo-ui-fieldLayout' ] )
			->toggleClasses( [ 'oo-ui-fieldLayout-disabled' ], $this->fieldWidget->isDisabled() )
			->appendContent( $this->body );
		if (
			count( $this->errors ) ||
			count( $this->warnings ) ||
			count( $this->successMessages ) ||
			count( $this->notices )
		) {
			$this->appendContent( $this->messages );
		}
		$this->body->addClasses( [ 'oo-ui-fieldLayout-body' ] );
		$this->header->addClasses( [ 'oo-ui-fieldLayout-header' ] );
		$this->messages->addClasses( [ 'oo-ui-fieldLayout-messages' ] );
		$this->field
			->addClasses( [ 'oo-ui-fieldLayout-field' ] )
			->appendContent( $this->fieldWidget );

		foreach ( $this->errors as $text ) {
			$this->messages->appendContent( $this->makeMessage( 'error', $text ) );
		}
		foreach ( $this->warnings as $text ) {
			$this->messages->appendContent( $this->makeMessage( 'warning', $text ) );
		}
		foreach ( $this->successMessages as $text ) {
			$this->messages->appendContent( $this->makeMessage( 'success', $text ) );
		}
		foreach ( $this->notices as $text ) {
			$this->messages->appendContent( $this->makeMessage( 'notice', $text ) );
		}

		$this->setAlignment( $config['align'] ?? 'left' );
		// Call this again to take into account the widget's accessKey
		$this->updateTitle();
	}

	/**
	 * @param string $kind 'error', 'warning', 'success' or 'notice'
	 * @param string|HtmlSnippet $text
	 * @return Tag
	 */
	private function makeMessage( $kind, $text ) {
		return new MessageWidget( [
			'type' => $kind,
			'inline' => true,
			'label' => $text,
		] );
	}

	/**
	 * Get the field.
	 *
	 * @return Widget Field widget
	 */
	public function getField() {
		return $this->fieldWidget;
	}

	/**
	 * Return `true` if the given field widget can be used with `'inline'` alignment (see
	 * setAlignment()). Return `false` if it can't or if this can't be determined.
	 *
	 * @return bool
	 */
	public function isFieldInline() {
		// This is very simplistic, but should be good enough. It's important to avoid false positives,
		// as that will cause the generated HTML to be invalid and go all out of whack when parsed.
		return strtolower( $this->getField()->getTag() ) === 'span';
	}

	/**
	 * Set the field alignment mode.
	 *
	 * @param string $value Alignment mode, either 'left', 'right', 'top' or 'inline'
	 * @return $this
	 */
	protected function setAlignment( $value ) {
		if ( $value !== $this->align ) {
			// Default to 'left'
			if ( !in_array( $value, [ 'left', 'right', 'top', 'inline' ] ) ) {
				$value = 'left';
			}
			// Validate
			if ( $value === 'inline' && !$this->isFieldInline() ) {
				$value = 'top';
			}
			// Reorder elements
			$this->body->clearContent();

			if ( $this->helpInline ) {
				if ( $value === 'top' ) {
					$this->header->appendContent( $this->label );
					$this->body->appendContent( $this->header, $this->field, $this->help );
				} elseif ( $value === 'inline' ) {
					$this->header->appendContent( $this->label, $this->help );
					$this->body->appendContent( $this->field, $this->header );
				} else {
					$this->header->appendContent( $this->label, $this->help );
					$this->body->appendContent( $this->header, $this->field );
				}
			} else {
				if ( $value === 'top' ) {
					$this->header->appendContent( $this->help, $this->label );
					$this->body->appendContent( $this->header, $this->field );
				} elseif ( $value === 'inline' ) {
					$this->header->appendContent( $this->help, $this->label );
					$this->body->appendContent( $this->field, $this->header );
				} else {
					$this->header->appendContent( $this->label );
					$this->body->appendContent( $this->header, $this->help, $this->field );
				}
			}
			// Set classes. The following classes can be used here:
			// * oo-ui-fieldLayout-align-left
			// * oo-ui-fieldLayout-align-right
			// * oo-ui-fieldLayout-align-top
			// * oo-ui-fieldLayout-align-inline
			if ( $this->align ) {
				$this->removeClasses( [ 'oo-ui-fieldLayout-align-' . $this->align ] );
			}
			$this->addClasses( [ 'oo-ui-fieldLayout-align-' . $value ] );
			$this->align = $value;
		}

		return $this;
	}

	/**
	 * Include information about the widget's accessKey in our title. TitledElement calls this method.
	 * (This is a bit of a hack.)
	 *
	 * @param string $title Tooltip label for 'title' attribute
	 * @return string
	 */
	protected function formatTitleWithAccessKey( $title ) {
		if ( $this->fieldWidget && method_exists( $this->fieldWidget, 'formatTitleWithAccessKey' ) ) {
			// @phan-suppress-next-line PhanUndeclaredMethod
			return $this->fieldWidget->formatTitleWithAccessKey( $title );
		}
		return $title;
	}

	/** @inheritDoc */
	public function getConfig( &$config ) {
		$config['fieldWidget'] = $this->fieldWidget;
		if ( $this->align !== 'left' ) {
			$config['align'] = $this->align;
		}
		if ( count( $this->errors ) ) {
			$config['errors'] = $this->errors;
		}
		if ( count( $this->warnings ) ) {
			$config['warnings'] = $this->warnings;
		}
		if ( count( $this->successMessages ) ) {
			$config['successMessages'] = $this->successMessages;
		}
		if ( count( $this->notices ) ) {
			$config['notices'] = $this->notices;
		}
		if ( $this->helpText !== '' ) {
			$config['help'] = $this->helpText;
		}
		if ( $this->helpInline ) {
			$config['helpInline'] = $this->helpInline;
		}
		$config['$overlay'] = true;
		return parent::getConfig( $config );
	}

	/**
	 * Creates and returns the help element.
	 *
	 * @return Widget The element that should become `$this->help`.
	 */
	private function createHelpElement() {
		if ( $this->helpInline ) {
			return new LabelWidget( [
				'classes' => [ 'oo-ui-inline-help' ],
				'label' => $this->helpText,
			] );
		} else {
			return new ButtonWidget( [
				'classes' => [ 'oo-ui-fieldLayout-help' ],
				'framed' => false,
				'icon' => 'info',
				'title' => $this->helpText,
				// TODO We have no way to use localisation messages in PHP
				// (and to use different languages when used from MediaWiki)
				// 'label' => msg( 'ooui-field-help' ),
				// 'invisibleLabel' => true,
			] );
		}
	}
}
