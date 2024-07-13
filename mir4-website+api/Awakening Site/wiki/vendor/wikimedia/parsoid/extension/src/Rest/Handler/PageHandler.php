<?php
/**
 * Copyright (C) 2011-2020 Wikimedia Foundation and others.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
 */
declare( strict_types = 1 );

namespace MWParsoid\Rest\Handler;

use MediaWiki\Rest\Handler\Helper\ParsoidFormatHelper;
use MediaWiki\Rest\Handler\ParsoidHandler as CoreParsoidHandler;
use MediaWiki\Rest\HttpException;
use MediaWiki\Rest\Response;
use MediaWiki\Revision\SlotRecord;
use Wikimedia\ParamValidator\ParamValidator;
use Wikimedia\Parsoid\Config\PageConfig;

/**
 * Handler for displaying or rendering the content of a page:
 * - /{domain}/v3/page/{format}/{title}
 * - /{domain}/v3/page/{format}/{title}/{revision}
 * @see https://www.mediawiki.org/wiki/Parsoid/API#GET
 */
class PageHandler extends CoreParsoidHandler {

	// NOTE: this controls redirects by overriding methods!
	use EndpointRedirectTrait;

	/** @inheritDoc */
	public function getParamSettings() {
		return [
			'domain' => [
				self::PARAM_SOURCE => 'path',
				ParamValidator::PARAM_TYPE => 'string',
				ParamValidator::PARAM_REQUIRED => true,
			],
			'format' => [
				self::PARAM_SOURCE => 'path',
				ParamValidator::PARAM_TYPE => 'string',
				ParamValidator::PARAM_REQUIRED => true,
			],
			'title' => [
				self::PARAM_SOURCE => 'path',
				ParamValidator::PARAM_TYPE => 'string',
				ParamValidator::PARAM_REQUIRED => true,
			],
			'revision' => [
				self::PARAM_SOURCE => 'path',
				ParamValidator::PARAM_TYPE => 'string',
				ParamValidator::PARAM_REQUIRED => false,
			],
		];
	}

	/** @inheritDoc */
	public function execute(): Response {
		$request = $this->getRequest();
		$format = $request->getPathParam( 'format' );

		if ( !in_array( $format, ParsoidFormatHelper::VALID_PAGE, true ) ) {
			throw new HttpException(
				"Invalid page format: {$format}", 404
			);
		}

		$attribs = $this->getRequestAttributes();

		if ( !$this->acceptable( $attribs ) ) { // mutates $attribs
			throw new HttpException(
				'Not acceptable', 406
			);
		}

		$pageConfig = $this->tryToCreatePageConfig( $attribs );

		if ( $format === ParsoidFormatHelper::FORMAT_WIKITEXT ) {
			return $this->getPageContentResponse( $pageConfig, $attribs );
		} else {
			return $this->wt2html( $pageConfig, $attribs );
		}
	}

	/**
	 * Return the content of a page. This is the case when GET /page/ is
	 * called with format=wikitext.
	 *
	 * @param PageConfig $pageConfig
	 * @param array $attribs Request attributes from getRequestAttributes()
	 * @return Response
	 * @throws HttpException
	 */
	protected function getPageContentResponse(
		PageConfig $pageConfig, array $attribs
	) {
		$content = $pageConfig->getRevisionContent();
		if ( !$content ) {
			throw new HttpException(
				'The specified revision does not exist.', 404
			);
		}
		$response = $this->getResponseFactory()->create();
		$response->setStatus( 200 );
		$response->setHeader( 'X-ContentModel', $content->getModel( SlotRecord::MAIN ) );
		ParsoidFormatHelper::setContentType(
			$response, ParsoidFormatHelper::FORMAT_WIKITEXT,
			$attribs['envOptions']['outputContentVersion']
		);
		$response->getBody()->write( $content->getContent( SlotRecord::MAIN ) );
		return $response;
	}

}
