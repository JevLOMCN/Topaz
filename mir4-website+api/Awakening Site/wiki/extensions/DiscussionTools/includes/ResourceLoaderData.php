<?php
/**
 * Utilities for ResourceLoader modules used by DiscussionTools.
 *
 * @file
 * @ingroup Extensions
 * @license MIT
 */

namespace MediaWiki\Extension\DiscussionTools;

use ExtensionRegistry;
use MediaWiki\Config\Config;
use MediaWiki\Extension\DiscussionTools\Hooks\HookRunner;
use MediaWiki\MediaWikiServices;
use MediaWiki\ResourceLoader as RL;
use MediaWiki\Title\Title;
use MessageLocalizer;

class ResourceLoaderData {
	/**
	 * Used for the 'ext.discussionTools.init' module and the test module.
	 *
	 * We need all of this data *in content language*. Some of it is already available in JS, but only
	 * in client language, so it's useless for us (e.g. digit transform table, month name messages).
	 */
	public static function getLocalData(
		RL\Context $context, Config $config, ?string $langCode = null
	): array {
		$services = MediaWikiServices::getInstance();

		if ( $langCode === null ) {
			$langData = $services->getService( 'DiscussionTools.LanguageData' );
		} else {
			$langData = new LanguageData(
				$services->getMainConfig(),
				$services->getLanguageFactory()->getLanguage( $langCode ),
				$services->getLanguageConverterFactory(),
				$services->getSpecialPageFactory()
			);
		}

		return $langData->getLocalData();
	}

	/**
	 * Return messages in content language, for use in a ResourceLoader module.
	 */
	public static function getContentLanguageMessages(
		RL\Context $context, Config $config, array $messagesKeys = []
	): array {
		return array_combine(
			$messagesKeys,
			array_map( static function ( $key ) {
				return wfMessage( $key )->inContentLanguage()->text();
			}, $messagesKeys )
		);
	}

	/**
	 * Return information about terms-of-use messages.
	 *
	 * @param MessageLocalizer $context
	 * @param Config $config
	 * @return array[] Map from internal name to array of parameters for MessageLocalizer::msg()
	 * @phan-return non-empty-array[]
	 */
	private static function getTermsOfUseMessages(
		MessageLocalizer $context, Config $config
	): array {
		$messages = [
			'reply' => [ 'discussiontools-replywidget-terms-click',
				$context->msg( 'discussiontools-replywidget-reply' )->text() ],
			'newtopic' => [ 'discussiontools-replywidget-terms-click',
				$context->msg( 'discussiontools-replywidget-newtopic' )->text() ],
		];

		$hookRunner = new HookRunner( MediaWikiServices::getInstance()->getHookContainer() );
		$hookRunner->onDiscussionToolsTermsOfUseMessages( $messages, $context, $config );

		return $messages;
	}

	/**
	 * Return parsed terms-of-use messages, for use in a ResourceLoader module.
	 */
	public static function getTermsOfUseMessagesParsed(
		MessageLocalizer $context, Config $config
	): array {
		$messages = static::getTermsOfUseMessages( $context, $config );
		foreach ( $messages as &$msg ) {
			$msg = $context->msg( ...$msg )->parse();
		}
		return $messages;
	}

	/**
	 * Return information about terms-of-use messages, for use in a ResourceLoader module as
	 * 'versionCallback'. This is to avoid calling the parser from version invalidation code.
	 */
	public static function getTermsOfUseMessagesVersion(
		MessageLocalizer $context, Config $config
	): array {
		$messages = static::getTermsOfUseMessages( $context, $config );
		foreach ( $messages as &$msg ) {
			$message = $context->msg( ...$msg );
			$msg = [
				// Include the text of the message, in case the canonical translation changes
				$message->plain(),
				// Include the page touched time, in case the on-wiki override is invalidated
				Title::makeTitle( NS_MEDIAWIKI, ucfirst( $message->getKey() ) )->getTouched(),
			];
		}
		return $messages;
	}

	/**
	 * Add optional dependencies to a ResourceLoader module definition depending on loaded extensions.
	 */
	public static function addOptionalDependencies( array $info ): RL\Module {
		$extensionRegistry = ExtensionRegistry::getInstance();

		foreach ( $info['optionalDependencies'] as $ext => $deps ) {
			if ( $extensionRegistry->isLoaded( $ext ) ) {
				$info['dependencies'] = array_merge( $info['dependencies'], (array)$deps );
			}
		}

		$class = $info['class'] ?? RL\FileModule::class;
		return new $class( $info );
	}

	/**
	 * Generate the test module that includes all of the test data, based on the JSON files defining
	 * test cases.
	 */
	public static function makeTestModule( array $info ): RL\Module {
		// Some tests rely on PHP-only features or are too large for the Karma test runner.
		// Skip them here. They are still tested in the PHP version.
		$skipTests = [
			'cases/modified.json' => [
				// Too large, cause timeouts in Karma test runner
				'enwiki oldparser',
				'enwiki parsoid',
				'enwiki oldparser (bullet indentation)',
				'enwiki parsoid (bullet indentation)',
				// These tests depend on #getTranscludedFrom(), which we didn't implement in JS
				'arwiki no-paragraph parsoid',
				'enwiki parsoid',
				'Many comments consisting of a block template and a paragraph',
				'Comment whose range almost exactly matches a template, but is not considered transcluded (T313100)',
				'Accidental complex transclusion (T265528)',
				'Accidental complex transclusion (T313093)',
			],
		];
		$info['packageFiles'][] = [
			'name' => 'skip.json',
			'type' => 'data',
			'content' => $skipTests,
		];

		$keys = [ 'config', 'data', 'dom', 'expected' ];
		foreach ( $info['testData'] as $path ) {
			$info['packageFiles'][] = $path;
			$localPath = $info['localBasePath'] . '/' . $path;
			$data = json_decode( file_get_contents( $localPath ), true );
			foreach ( $data as $case ) {
				if ( isset( $case['name'] ) && in_array( $case['name'], $skipTests[$path] ?? [], true ) ) {
					continue;
				}
				foreach ( $case as $key => $val ) {
					if ( in_array( $key, $keys, true ) && is_string( $val ) ) {
						if ( str_ends_with( $val, '.json' ) ) {
							$info['packageFiles'][] = substr( $val, strlen( '../' ) );
						} elseif ( str_ends_with( $val, '.html' ) ) {
							$info['packageFiles'][] = [
								'name' => $val,
								'type' => 'data',
								'callback' => static function () use ( $info, $val ) {
									$localPath = $info['localBasePath'] . '/' . $val;
									return file_get_contents( $localPath );
								},
								'versionCallback' => static function () use ( $val ) {
									return new RL\FilePath( $val );
								},
							];
						}
					}
				}
			}
		}
		$class = $info['class'] ?? RL\FileModule::class;
		return new $class( $info );
	}
}
