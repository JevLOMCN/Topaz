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

use MediaWiki\Rest\Handler;
use MediaWiki\Rest\Handler\Helper\ParsoidFormatHelper;
use MediaWiki\Rest\RequestInterface;
use MediaWiki\Rest\ResponseFactory;

/**
 * Trait for overriding methods in ParsoidHandler that control the redirect targets.
 * This is used by endpoints in the parsoid extensions to change the redirect targets
 * from endpoints defined in MediaWiki core to the endpoints defined by the parsoid
 * extension.
 */
trait EndpointRedirectTrait {

	/**
	 * Override the transform endpoint path.
	 * @see ParsoidHandler::getTransformEndpoint
	 *
	 * @param string $format The format the endpoint is expected to return.
	 *
	 * @return string
	 */
	protected function getTransformEndpoint( string $format = ParsoidFormatHelper::FORMAT_HTML ): string {
		return '/{domain}/v3/transform/{from}/to/{format}/{title}/{revision}';
	}

	/**
	 * Override the page content endpoint path.
	 * @see ParsoidHandler::getPageContentEndpoint
	 *
	 * @param string $format The format the endpoint is expected to return.
	 *
	 * @return string
	 */
	protected function getPageContentEndpoint( string $format = ParsoidFormatHelper::FORMAT_HTML ): string {
		return '/{domain}/v3/page/{format}/{title}';
	}

	/**
	 * Override the revision content endpoint path.
	 * @see ParsoidHandler::getRevisionContentEndpoint
	 *
	 * @param string $format The format the endpoint is expected to return.
	 *
	 * @return string
	 */
	protected function getRevisionContentEndpoint( string $format = ParsoidFormatHelper::FORMAT_HTML ): string {
		return '/{domain}/v3/page/{format}/{title}/{revision}';
	}

	/**
	 * @see Handler::getResponseFactory
	 * @return ResponseFactory
	 */
	abstract protected function getResponseFactory();

	/**
	 * @see Handler::getRequest
	 * @return RequestInterface
	 */
	abstract protected function getRequest();

}
