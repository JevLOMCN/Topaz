<?php

namespace MediaWiki\Extension\Notifications\Formatters;

/**
 * A formatter for the notification flyout popup. Just the bare data needed to
 * render everything client-side.
 */
class EchoModelFormatter extends EchoEventFormatter {
	/**
	 * @param EchoEventPresentationModel $model
	 * @return array
	 */
	protected function formatModel( EchoEventPresentationModel $model ) {
		$data = $model->jsonSerialize();
		$data['iconUrl'] = EchoIcon::getUrl( $model->getIconType(), $this->language->getDir() );

		if ( isset( $data['links']['primary']['url'] ) ) {
			$data['links']['primary']['url'] = wfExpandUrl( $data['links']['primary']['url'] );
		}

		// @phan-suppress-next-line PhanTypePossiblyInvalidDimOffset
		foreach ( $data['links']['secondary'] as &$link ) {
			// @phan-suppress-next-line PhanTypeMismatchDimAssignment
			$link['url'] = wfExpandUrl( $link['url'] );
		}
		unset( $link );

		$bundledIds = $model->getBundledIds();
		if ( $bundledIds ) {
			$data[ 'bundledIds' ] = $bundledIds;
		}

		return $data;
	}
}
