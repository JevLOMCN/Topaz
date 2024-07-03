<?php
/**
 * MonoBook hooks
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 * http://www.gnu.org/copyleft/gpl.html
 *
 * @file
 */

namespace MonoBook;

use MediaWiki\Hook\SkinTemplateNavigation__UniversalHook;
use MediaWiki\Output\Hook\OutputPageBodyAttributesHook;
use MediaWiki\Output\OutputPage;
use Skin;
use SkinTemplate;

/**
 * @phpcs:disable MediaWiki.NamingConventions.LowerCamelFunctionsName.FunctionName
 */
class Hooks implements
	OutputPageBodyAttributesHook,
	SkinTemplateNavigation__UniversalHook
{
	/**
	 * Add the "monobook-capitalize-all-nouns" CSS class to the <body> element for German
	 * (de) and various languages which have German set as a fallback language, such
	 * as Colognian (ksh) and others.
	 *
	 * @see https://phabricator.wikimedia.org/T97892
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/OutputPageBodyAttributes
	 * @param OutputPage $out
	 * @param Skin $skin
	 * @param array &$bodyAttrs Pre-existing attributes for the <body> element
	 */
	public function onOutputPageBodyAttributes( $out, $skin, &$bodyAttrs ): void {
		$lang = $skin->getLanguage();
		if (
			$skin->getSkinName() === 'monobook' && (
				$lang->getCode() === 'de' ||
				in_array( 'de', $lang->getFallbackLanguages() )
			)
		) {
			$bodyAttrs['class'] .= ' monobook-capitalize-all-nouns';
		}
	}

	/**
	 * SkinTemplateNavigationUniversal hook handler
	 *
	 * @param SkinTemplate $skin
	 * @param array &$content_navigation
	 */
	public function onSkinTemplateNavigation__Universal( $skin, &$content_navigation ): void {
		$title = $skin->getTitle();
		if ( $skin->getSkinName() === 'monobook' ) {
			$tabs = [];
			$namespaces = $content_navigation['namespaces'];
			foreach ( $namespaces as $nsid => $attribs ) {
				$id = $nsid . '-mobile';
				$tabs[$id] = [] + $attribs;
				$tabs[$id]['title'] = $attribs['text'];
				$tabs[$id]['id'] = $id;
			}

			if ( !$title->isSpecialPage() ) {
				$tabs['more'] = [
					'text' => $skin->msg( 'monobook-more-actions' )->text(),
					'href' => '#p-cactions',
					'id' => 'ca-more'
				];
			}

			$tabs['toolbox'] = [
				'text' => $skin->msg( 'toolbox' )->text(),
				'href' => '#p-tb',
				'id' => 'ca-tools',
				'title' => $skin->msg( 'toolbox' )->text()
			];

			$languages = $skin->getLanguages();
			if ( count( $languages ) > 0 ) {
				$tabs['languages'] = [
					'text' => $skin->msg( 'otherlanguages' )->text(),
					'href' => '#p-lang',
					'id' => 'ca-languages',
					'title' => $skin->msg( 'otherlanguages' )->text()
				];
			}

			$content_navigation['cactions-mobile'] = $tabs;
		}
	}
}
