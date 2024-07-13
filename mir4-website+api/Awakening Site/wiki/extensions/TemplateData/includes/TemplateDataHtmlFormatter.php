<?php

namespace MediaWiki\Extension\TemplateData;

use MediaWiki\Html\Html;
use MediaWiki\Logger\LoggerFactory;
use MediaWiki\MediaWikiServices;
use MediaWiki\Title\Title;
use MessageLocalizer;
use stdClass;

/**
 * @license GPL-2.0-or-later
 */
class TemplateDataHtmlFormatter {

	private MessageLocalizer $localizer;
	private string $languageCode;

	public function __construct( MessageLocalizer $localizer, string $languageCode = 'en' ) {
		$this->localizer = $localizer;
		$this->languageCode = $languageCode;
	}

	/**
	 * @param TemplateDataBlob $templateData
	 * @param Title $frameTitle
	 * @param bool $showEditLink
	 *
	 * @return string HTML
	 */
	public function getHtml( TemplateDataBlob $templateData, Title $frameTitle, bool $showEditLink = true ): string {
		$data = $templateData->getDataInLanguage( $this->languageCode );

		$icon = null;
		$formatMsg = null;
		if ( isset( $data->format ) && is_string( $data->format ) ) {
			$format = $data->format;
			'@phan-var string $format';
			if ( isset( TemplateDataValidator::PREDEFINED_FORMATS[$format] ) ) {
				// The following icon names are used here:
				// * template-format-block
				// * template-format-inline
				$icon = 'template-format-' . $format;
				// Messages that can be used here:
				// * templatedata-doc-format-block
				// * templatedata-doc-format-inline
				$formatMsg = $this->localizer->msg( 'templatedata-doc-format-' . $format );
			}
			if ( !$formatMsg || $formatMsg->isDisabled() ) {
				$icon = 'settings';
				$formatMsg = $this->localizer->msg( 'templatedata-doc-format-custom' );
			}
		}

		$sorting = count( (array)$data->params ) > 1 ? ' sortable' : '';
		$html = '<header>'
			. Html::element( 'p',
				[
					'class' => [
						'mw-templatedata-doc-desc',
						'mw-templatedata-doc-muted' => $data->description === null,
					]
				],
				$data->description ??
					$this->localizer->msg( 'templatedata-doc-desc-empty' )->text()
			)
			. '</header>'
			. '<table class="wikitable mw-templatedata-doc-params' . $sorting . '">'
			. Html::rawElement( 'caption', [],
				Html::rawElement( 'p',
					[ 'class' => 'mw-templatedata-caption' ],
					$this->localizer->msg( 'templatedata-doc-params' )->escaped() .
					// Edit interface is only loaded in the template namespace (see Hooks::onEditPage)
					( $showEditLink && $frameTitle->inNamespace( NS_TEMPLATE ) ?
						Html::element( 'mw:edittemplatedata', [
							'page' => $frameTitle->getPrefixedText()
						] ) :
						''
					)
				)
				. ( $formatMsg ?
					Html::rawElement( 'p', [],
						new \OOUI\IconWidget( [ 'icon' => $icon ] )
						. Html::element(
							'span',
							[ 'class' => 'mw-templatedata-format' ],
							$formatMsg->text()
						)
					) :
					''
				)
			)
			. '<thead><tr>'
			. Html::element( 'th', [ 'colspan' => 2 ],
				$this->localizer->msg( 'templatedata-doc-param-name' )->text()
			)
			. Html::element( 'th', [],
				$this->localizer->msg( 'templatedata-doc-param-desc' )->text()
			)
			. Html::element( 'th', [],
				$this->localizer->msg( 'templatedata-doc-param-type' )->text()
			)
			. Html::element( 'th', [],
				$this->localizer->msg( 'templatedata-doc-param-status' )->text()
			)
			. '</tr></thead>'
			. '<tbody>';

		$paramNames = $data->paramOrder ?? array_keys( (array)$data->params );
		if ( !$paramNames ) {
			// Display no parameters message
			$html .= '<tr>'
			. Html::element( 'td',
				[
					'class' => 'mw-templatedata-doc-muted',
					'colspan' => 7
				],
				$this->localizer->msg( 'templatedata-doc-no-params-set' )->text()
			)
			. '</tr>';
		}

		foreach ( $paramNames as $paramName ) {
			$html .= $this->formatParameterTableRow( $paramName, $data->params->$paramName );
		}
		$html .= '</tbody></table>';

		return Html::rawElement( 'section', [ 'class' => 'mw-templatedata-doc-wrap' ], $html );
	}

	/**
	 * Replace <mw:edittemplatedata> markers with links
	 *
	 * @param string &$text
	 */
	public function replaceEditLink( string &$text ): void {
		$localizer = $this->localizer;
		$text = preg_replace_callback(
			// Based on EDITSECTION_REGEX in ParserOutput
			'#<mw:edittemplatedata page="(.*?)"></mw:edittemplatedata>#s',
			static function ( array $m ) use ( $localizer ): string {
				$editsectionPage = Title::newFromText( htmlspecialchars_decode( $m[1] ) );

				if ( !is_object( $editsectionPage ) ) {
					LoggerFactory::getInstance( 'Parser' )
						->error(
							'TemplateDataHtmlFormatter::replaceEditLink(): bad title in edittemplatedata placeholder',
							[
								'placeholder' => $m[0],
								'editsectionPage' => $m[1],
							]
						);
					return '';
				}

				$result = Html::openElement( 'span', [ 'class' => 'mw-editsection-like' ] );
				$result .= Html::rawElement( 'span', [ 'class' => 'mw-editsection-bracket' ], '[' );

				$linkRenderer = MediaWikiServices::getInstance()->getLinkRenderer();
				$result .= $linkRenderer->makeKnownLink(
					$editsectionPage,
					$localizer->msg( 'templatedata-editbutton' )->text(),
					[],
					[
						'action' => 'edit',
						'templatedata' => 'edit',
					]
				);

				$result .= Html::rawElement( 'span', [ 'class' => 'mw-editsection-bracket' ], ']' );
				$result .= Html::closeElement( 'span' );

				return $result;
			},
			$text
		);
	}

	/**
	 * @param int|string $paramName
	 * @param stdClass $param
	 *
	 * @return string HTML
	 */
	private function formatParameterTableRow( $paramName, stdClass $param ): string {
		'@phan-var object $param';

		$allParamNames = [ Html::element( 'code', [], $paramName ) ];
		foreach ( $param->aliases as $alias ) {
			$allParamNames[] = Html::element( 'code', [ 'class' => 'mw-templatedata-doc-param-alias' ],
				$alias
			);
		}

		$suggestedValues = [];
		foreach ( $param->suggestedvalues as $suggestedValue ) {
			$suggestedValues[] = Html::element( 'code', [], $suggestedValue );
		}

		if ( $param->deprecated ) {
			$status = 'deprecated';
		} elseif ( $param->required ) {
			$status = 'required';
		} elseif ( $param->suggested ) {
			$status = 'suggested';
		} else {
			$status = 'optional';
		}

		return '<tr>'
			// Label
			. Html::element( 'th', [], $param->label ?? $paramName )
			// Parameters and aliases
			. Html::rawElement( 'td', [ 'class' => 'mw-templatedata-doc-param-name' ],
				implode( ' ', $allParamNames )
			)
			// Description
			. Html::rawElement( 'td', [],
				Html::element( 'p',
					[
						'class' => $param->description ? null : 'mw-templatedata-doc-muted',
					],
					$param->description ??
						$this->localizer->msg( 'templatedata-doc-param-desc-empty' )->text()
				)
				. Html::rawElement( 'dl', [],
					// Suggested Values
					( $suggestedValues ? ( Html::element( 'dt', [],
						$this->localizer->msg( 'templatedata-doc-param-suggestedvalues' )->text()
					)
					. Html::rawElement( 'dd', [],
						implode( ' ', $suggestedValues )
					) ) : '' ) .
					// Default
					( $param->default !== null ? ( Html::element( 'dt', [],
						$this->localizer->msg( 'templatedata-doc-param-default' )->text()
					)
					. Html::element( 'dd', [],
						$param->default
					) ) : '' )
					// Example
					. ( $param->example !== null ? ( Html::element( 'dt', [],
						$this->localizer->msg( 'templatedata-doc-param-example' )->text()
					)
					. Html::element( 'dd', [],
						$param->example
					) ) : '' )
					// Auto value
					. ( $param->autovalue !== null ? ( Html::element( 'dt', [],
						$this->localizer->msg( 'templatedata-doc-param-autovalue' )->text()
					)
					. Html::rawElement( 'dd', [],
						Html::element( 'code', [], $param->autovalue )
					) ) : '' )
				)
			)
			// Type
			. Html::element( 'td',
				[
					'class' => [
						'mw-templatedata-doc-param-type',
						'mw-templatedata-doc-muted' => $param->type === 'unknown'
					]
				],
				// Known messages, for grepping:
				// templatedata-doc-param-type-boolean, templatedata-doc-param-type-content,
				// templatedata-doc-param-type-date, templatedata-doc-param-type-line,
				// templatedata-doc-param-type-number, templatedata-doc-param-type-string,
				// templatedata-doc-param-type-unbalanced-wikitext, templatedata-doc-param-type-unknown,
				// templatedata-doc-param-type-url, templatedata-doc-param-type-wiki-file-name,
				// templatedata-doc-param-type-wiki-page-name, templatedata-doc-param-type-wiki-template-name,
				// templatedata-doc-param-type-wiki-user-name
				$this->localizer->msg( 'templatedata-doc-param-type-' . $param->type )->text()
			)
			// Status
			. Html::element( 'td',
				[
					// CSS class names that can be used here:
					// mw-templatedata-doc-param-status-deprecated
					// mw-templatedata-doc-param-status-optional
					// mw-templatedata-doc-param-status-required
					// mw-templatedata-doc-param-status-suggested
					'class' => "mw-templatedata-doc-param-status-$status",
					'data-sort-value' => [
						'deprecated' => -1,
						'suggested' => 1,
						'required' => 2,
					][$status] ?? 0,
				],
				// Messages that can be used here:
				// templatedata-doc-param-status-deprecated
				// templatedata-doc-param-status-optional
				// templatedata-doc-param-status-required
				// templatedata-doc-param-status-suggested
				$this->localizer->msg( "templatedata-doc-param-status-$status" )->text()
			)
			. '</tr>';
	}

}
