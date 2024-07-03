/**
 * Configuration of Toolbar module for wikiEditor
 */
var configData = require( './data.json' ),
	fileNamespace = mw.config.get( 'wgFormattedNamespaces' )[ 6 ],
	specialCharacterGroups = require( 'mediawiki.language.specialCharacters' ),
	toolbarConfig;

/**
 * Replace link targets from example messages with hash
 * after a message has been parsed.
 *
 * @param {jQuery} $message an mw.message().parseDom() object
 * @return {string} HTML string
 */
function delink( $message ) {
	// dummy div to append the message to
	var $div = $( '<div>' );

	$div.append( $message );
	$div.find( 'a' ).attr( 'href', '#' );

	return $div.html();
}

toolbarConfig = {
	toolbar: {
		// Main section
		main: {
			type: 'toolbar',
			groups: {
				format: {
					tools: {
						bold: {
							label: mw.msg( 'wikieditor-toolbar-tool-bold' ),
							type: 'button',
							oouiIcon: 'bold',
							action: {
								type: 'encapsulate',
								options: {
									pre: "'''",
									peri: mw.msg( 'wikieditor-toolbar-tool-bold-example' ),
									post: "'''"
								}
							}
						},
						italic: {
							section: 'main',
							group: 'format',
							id: 'italic',
							label: mw.msg( 'wikieditor-toolbar-tool-italic' ),
							type: 'button',
							oouiIcon: 'italic',
							action: {
								type: 'encapsulate',
								options: {
									pre: "''",
									peri: mw.msg( 'wikieditor-toolbar-tool-italic-example' ),
									post: "''"
								}
							}
						}
					}
				},
				insert: {
					tools: {
						signature: {
							label: mw.msg( 'wikieditor-toolbar-tool-signature' ),
							type: 'button',
							oouiIcon: 'signature',
							action: {
								type: 'encapsulate',
								options: {
									pre: configData.signature
								}
							}
						}
					}
				}
			}
		},
		// Secondary section of the top toolbar (at right side when LTR).
		secondary: {
			type: 'toolbar',
			groups: {
				default: {
					tools: {}
				}
			}
		},
		// Format section
		advanced: {
			label: mw.msg( 'wikieditor-toolbar-section-advanced' ),
			type: 'toolbar',
			groups: {
				heading: {
					tools: {
						heading: {
							label: mw.msg( 'wikieditor-toolbar-tool-heading' ),
							type: 'select',
							list: {
								'heading-2': {
									label: mw.msg( 'wikieditor-toolbar-tool-heading-2' ),
									action: {
										type: 'encapsulate',
										options: {
											pre: '== ',
											peri: mw.msg( 'wikieditor-toolbar-tool-heading-example' ),
											post: ' ==',
											regex: /^(\s*)(={1,6})(.*?)\2(\s*)$/,
											regexReplace: '$1==$3==$4',
											ownline: true
										}
									}
								},
								'heading-3': {
									label: mw.msg( 'wikieditor-toolbar-tool-heading-3' ),
									action: {
										type: 'encapsulate',
										options: {
											pre: '=== ',
											peri: mw.msg( 'wikieditor-toolbar-tool-heading-example' ),
											post: ' ===',
											regex: /^(\s*)(={1,6})(.*?)\2(\s*)$/,
											regexReplace: '$1===$3===$4',
											ownline: true
										}
									}
								},
								'heading-4': {
									label: mw.msg( 'wikieditor-toolbar-tool-heading-4' ),
									action: {
										type: 'encapsulate',
										options: {
											pre: '==== ',
											peri: mw.msg( 'wikieditor-toolbar-tool-heading-example' ),
											post: ' ====',
											regex: /^(\s*)(={1,6})(.*?)\2(\s*)$/,
											regexReplace: '$1====$3====$4',
											ownline: true
										}
									}
								},
								'heading-5': {
									label: mw.msg( 'wikieditor-toolbar-tool-heading-5' ),
									action: {
										type: 'encapsulate',
										options: {
											pre: '===== ',
											peri: mw.msg( 'wikieditor-toolbar-tool-heading-example' ),
											post: ' =====',
											regex: /^(\s*)(={1,6})(.*?)\2(\s*)$/,
											regexReplace: '$1=====$3=====$4',
											ownline: true
										}
									}
								}
							}
						}
					}
				},
				format: {
					label: mw.msg( 'wikieditor-toolbar-group-format' ),
					tools: {
						ulist: {
							label: mw.msg( 'wikieditor-toolbar-tool-ulist' ),
							type: 'button',
							oouiIcon: 'listBullet',
							action: {
								type: 'encapsulate',
								options: {
									pre: '* ',
									peri: mw.msg( 'wikieditor-toolbar-tool-ulist-example' ),
									post: '',
									ownline: true,
									splitlines: true
								}
							}
						},
						olist: {
							label: mw.msg( 'wikieditor-toolbar-tool-olist' ),
							type: 'button',
							oouiIcon: 'listNumbered',
							action: {
								type: 'encapsulate',
								options: {
									pre: '# ',
									peri: mw.msg( 'wikieditor-toolbar-tool-olist-example' ),
									post: '',
									ownline: true,
									splitlines: true
								}
							}
						},
						nowiki: {
							label: mw.msg( 'wikieditor-toolbar-tool-nowiki' ),
							type: 'button',
							oouiIcon: 'noWikiText',
							action: {
								type: 'encapsulate',
								options: {
									pre: '<nowiki>',
									peri: mw.msg( 'wikieditor-toolbar-tool-nowiki-example' ),
									post: '</nowiki>'
								}
							}
						},
						newline: {
							label: mw.msg( 'wikieditor-toolbar-tool-newline' ),
							type: 'button',
							oouiIcon: 'newline',
							action: {
								type: 'encapsulate',
								options: {
									pre: '<br>\n'
								}
							}
						}
					}
				},
				size: {
					tools: {
						big: {
							label: mw.msg( 'wikieditor-toolbar-tool-big' ),
							type: 'button',
							oouiIcon: 'bigger',
							action: {
								type: 'encapsulate',
								options: {
									pre: '<big>',
									peri: mw.msg( 'wikieditor-toolbar-tool-big-example' ),
									post: '</big>'
								}
							}
						},
						small: {
							label: mw.msg( 'wikieditor-toolbar-tool-small' ),
							type: 'button',
							oouiIcon: 'smaller',
							action: {
								type: 'encapsulate',
								options: {
									pre: '<small>',
									peri: mw.msg( 'wikieditor-toolbar-tool-small-example' ),
									post: '</small>'
								}
							}
						},
						superscript: {
							label: mw.msg( 'wikieditor-toolbar-tool-superscript' ),
							type: 'button',
							oouiIcon: 'superscript',
							action: {
								type: 'encapsulate',
								options: {
									pre: '<sup>',
									peri: mw.msg( 'wikieditor-toolbar-tool-superscript-example' ),
									post: '</sup>'
								}
							}
						},
						subscript: {
							label: mw.msg( 'wikieditor-toolbar-tool-subscript' ),
							type: 'button',
							oouiIcon: 'subscript',
							action: {
								type: 'encapsulate',
								options: {
									pre: '<sub>',
									peri: mw.msg( 'wikieditor-toolbar-tool-subscript-example' ),
									post: '</sub>'
								}
							}
						}
					}
				},
				insert: {
					label: mw.msg( 'wikieditor-toolbar-group-insert' ),
					tools: {
						gallery: {
							label: mw.msg( 'wikieditor-toolbar-tool-gallery' ),
							type: 'button',
							oouiIcon: 'imageGallery',
							action: {
								type: 'encapsulate',
								options: {
									pre: '<gallery>\n',
									peri: mw.msg( 'wikieditor-toolbar-tool-gallery-example', fileNamespace ),
									post: '\n</gallery>',
									ownline: true
								}
							}
						},
						redirect: {
							label: mw.msg( 'wikieditor-toolbar-tool-redirect' ),
							type: 'button',
							oouiIcon: 'articleRedirect',
							action: {
								type: 'encapsulate',
								options: {
									pre: configData.magicWords.redirect[ 0 ] + ' [[',
									peri: mw.msg( 'wikieditor-toolbar-tool-redirect-example' ),
									post: ']]',
									ownline: true
								}
							}
						}
					}
				}
			}
		},
		characters: {
			label: mw.msg( 'wikieditor-toolbar-section-characters' ),
			type: 'booklet',
			deferLoad: true,
			pages: {
				latin: {
					label: mw.msg( 'special-characters-group-latin' ),
					layout: 'characters',
					characters: specialCharacterGroups.latin
				},
				latinextended: {
					label: mw.msg( 'special-characters-group-latinextended' ),
					layout: 'characters',
					characters: specialCharacterGroups.latinextended
				},
				ipa: {
					label: mw.msg( 'special-characters-group-ipa' ),
					layout: 'characters',
					characters: specialCharacterGroups.ipa
				},
				symbols: {
					label: mw.msg( 'special-characters-group-symbols' ),
					layout: 'characters',
					characters: specialCharacterGroups.symbols
				},
				greek: {
					label: mw.msg( 'special-characters-group-greek' ),
					layout: 'characters',
					language: 'el',
					characters: specialCharacterGroups.greek
				},
				greekextended: {
					label: mw.msg( 'special-characters-group-greekextended' ),
					layout: 'characters',
					characters: specialCharacterGroups.greekextended
				},
				cyrillic: {
					label: mw.msg( 'special-characters-group-cyrillic' ),
					layout: 'characters',
					characters: specialCharacterGroups.cyrillic
				},
				// The core 28-letter alphabet, special letters for the Arabic language,
				// vowels, punctuation, digits.
				// Names of letters are written as in the Unicode charts.
				arabic: {
					label: mw.msg( 'special-characters-group-arabic' ),
					layout: 'characters',
					language: 'ar',
					direction: 'rtl',
					characters: specialCharacterGroups.arabic
				},
				// Characters for languages other than Arabic.
				arabicextended: {
					label: mw.msg( 'special-characters-group-arabicextended' ),
					layout: 'characters',
					language: 'ar',
					direction: 'rtl',
					characters: specialCharacterGroups.arabicextended
				},
				hebrew: {
					label: mw.msg( 'special-characters-group-hebrew' ),
					layout: 'characters',
					direction: 'rtl',
					characters: specialCharacterGroups.hebrew
				},
				bangla: {
					label: mw.msg( 'special-characters-group-bangla' ),
					language: 'bn',
					layout: 'characters',
					characters: specialCharacterGroups.bangla
				},
				tamil: {
					label: mw.msg( 'special-characters-group-tamil' ),
					language: 'ta',
					layout: 'characters',
					characters: specialCharacterGroups.tamil
				},
				telugu: {
					label: mw.msg( 'special-characters-group-telugu' ),
					language: 'te',
					layout: 'characters',
					characters: specialCharacterGroups.telugu
				},
				sinhala: {
					label: mw.msg( 'special-characters-group-sinhala' ),
					language: 'si',
					layout: 'characters',
					characters: specialCharacterGroups.sinhala
				},
				devanagari: {
					label: mw.msg( 'special-characters-group-devanagari' ),
					layout: 'characters',
					characters: specialCharacterGroups.devanagari
				},
				gujarati: {
					label: mw.msg( 'special-characters-group-gujarati' ),
					language: 'gu',
					layout: 'characters',
					characters: specialCharacterGroups.gujarati
				},
				thai: {
					label: mw.msg( 'special-characters-group-thai' ),
					language: 'th',
					layout: 'characters',
					characters: specialCharacterGroups.thai
				},
				lao: {
					label: mw.msg( 'special-characters-group-lao' ),
					language: 'lo',
					layout: 'characters',
					characters: specialCharacterGroups.lao
				},
				khmer: {
					label: mw.msg( 'special-characters-group-khmer' ),
					language: 'km',
					layout: 'characters',
					characters: specialCharacterGroups.khmer
				},
				canadianaboriginal: {
					label: mw.msg( 'special-characters-group-canadianaboriginal' ),
					language: 'cr',
					layout: 'characters',
					characters: specialCharacterGroups.canadianaboriginal
				},
				runes: {
					label: mw.msg( 'special-characters-group-runes' ),
					layout: 'characters',
					characters: specialCharacterGroups.runes
				}
			}
		},
		help: {
			label: mw.msg( 'wikieditor-toolbar-section-help' ),
			type: 'booklet',
			deferLoad: true,
			pages: {
				format: {
					label: mw.msg( 'wikieditor-toolbar-help-page-format' ),
					layout: 'table',
					headings: [
						{ html: mw.message( 'wikieditor-toolbar-help-heading-description' ).parse() },
						{ html: mw.message( 'wikieditor-toolbar-help-heading-syntax' ).parse() },
						{ html: mw.message( 'wikieditor-toolbar-help-heading-result' ).parse() }
					],
					rows: [
						{
							description: {
								html: mw.message( 'wikieditor-toolbar-help-content-italic-description' ).parse()
							},
							syntax: {
								html: "''" + mw.message( 'wikieditor-toolbar-help-content-italic-example' ).escaped() + "''"
							},
							result: {
								html: '<em>' + mw.message( 'wikieditor-toolbar-help-content-italic-example' ).parse() + '</em>'
							}
						},
						{
							description: {
								html: mw.message( 'wikieditor-toolbar-help-content-bold-description' ).parse()
							},
							syntax: {
								html: "'''" + mw.message( 'wikieditor-toolbar-help-content-bold-example' ).escaped() + "'''"
							},
							result: {
								html: '<strong>' + mw.message( 'wikieditor-toolbar-help-content-bold-example' ).parse() + '</strong>'
							}
						},
						{
							description: {
								html: mw.message( 'wikieditor-toolbar-help-content-bolditalic-description' ).parse()
							},
							syntax: {
								html: "'''''" + mw.message( 'wikieditor-toolbar-help-content-bolditalic-example' ).escaped() + "'''''"
							},
							result: {
								html: '<strong><em>' + mw.message( 'wikieditor-toolbar-help-content-bolditalic-example' ).parse() + '</em></strong>'
							}
						}
					]
				},
				link: {
					label: mw.msg( 'wikieditor-toolbar-help-page-link' ),
					layout: 'table',
					headings: [
						{ html: mw.message( 'wikieditor-toolbar-help-heading-description' ).parse() },
						{ html: mw.message( 'wikieditor-toolbar-help-heading-syntax' ).parse() },
						{ html: mw.message( 'wikieditor-toolbar-help-heading-result' ).parse() }
					],
					rows: [
						{
							description: {
								html: mw.message( 'wikieditor-toolbar-help-content-ilink-description' ).parse()
							},
							syntax: {
								html: mw.message( 'wikieditor-toolbar-help-content-ilink-example' ).escaped()
							},
							result: {
								html: '<span class="pre-wrap">' + delink( mw.message( 'wikieditor-toolbar-help-content-ilink-example' ).parseDom() ) + '</span>'
							}
						},
						{
							description: {
								html: mw.message( 'wikieditor-toolbar-help-content-xlink-description' ).parse()
							},
							syntax: {
								html: mw.message(
									'wikieditor-toolbar-help-content-xlink-example1',
									mw.msg( 'wikieditor-toolbar-help-content-xlink-example-url' ),
									mw.msg( 'wikieditor-toolbar-help-content-xlink-example-label' )
								).escaped()
							},
							result: {
								html: '<span class="mw-parser-output pre-wrap">' +
									delink( mw.message(
										'wikieditor-toolbar-help-content-xlink-example2',
										mw.msg( 'wikieditor-toolbar-help-content-xlink-example-url' ),
										mw.msg( 'wikieditor-toolbar-help-content-xlink-example-label' ),
										mw.language.convertNumber( 1 )
									).parseDom() ) +
									'</span>'
							}
						}
					]
				},
				heading: {
					label: mw.msg( 'wikieditor-toolbar-help-page-heading' ),
					layout: 'table',
					headings: [
						{ html: mw.message( 'wikieditor-toolbar-help-heading-description' ).parse() },
						{ html: mw.message( 'wikieditor-toolbar-help-heading-syntax' ).parse() },
						{ html: mw.message( 'wikieditor-toolbar-help-heading-result' ).parse() }
					],
					rows: [
						{
							description: {
								html: mw.message( 'wikieditor-toolbar-help-content-heading2-description' ).parse()
							},
							syntax: {
								html: '== ' + mw.message( 'wikieditor-toolbar-help-content-heading2-example' ).escaped() + ' =='
							},
							result: {
								html: '<h2>' + mw.message( 'wikieditor-toolbar-help-content-heading2-example' ).parse() + '</h2>'
							}
						},
						{
							description: {
								html: mw.message( 'wikieditor-toolbar-help-content-heading3-description' ).parse()
							},
							syntax: {
								html: '=== ' + mw.message( 'wikieditor-toolbar-help-content-heading3-example' ).escaped() + ' ==='
							},
							result: {
								html: '<h3>' + mw.message( 'wikieditor-toolbar-help-content-heading3-example' ).parse() + '</h3>'
							}
						},
						{
							description: {
								html: mw.message( 'wikieditor-toolbar-help-content-heading4-description' ).parse()
							},
							syntax: {
								html: '==== ' + mw.message( 'wikieditor-toolbar-help-content-heading4-example' ).escaped() + ' ===='
							},
							result: {
								html: '<h4>' + mw.message( 'wikieditor-toolbar-help-content-heading4-example' ).parse() + '</h4>'
							}
						},
						{
							description: {
								html: mw.message( 'wikieditor-toolbar-help-content-heading5-description' ).parse()
							},
							syntax: {
								html: '===== ' + mw.message( 'wikieditor-toolbar-help-content-heading5-example' ).escaped() + ' ====='
							},
							result: {
								html: '<h5>' + mw.message( 'wikieditor-toolbar-help-content-heading5-example' ).parse() + '</h5>'
							}
						}
					]
				},
				list: {
					label: mw.msg( 'wikieditor-toolbar-help-page-list' ),
					layout: 'table',
					headings: [
						{ html: mw.message( 'wikieditor-toolbar-help-heading-description' ).parse() },
						{ html: mw.message( 'wikieditor-toolbar-help-heading-syntax' ).parse() },
						{ html: mw.message( 'wikieditor-toolbar-help-heading-result' ).parse() }
					],
					rows: [
						{
							description: {
								html: mw.message( 'wikieditor-toolbar-help-content-ulist-description' ).parse()
							},
							syntax: {
								html: '* ' + mw.message( 'wikieditor-toolbar-help-content-ulist-example' ).escaped() + '<br />' +
									'* ' + mw.message( 'wikieditor-toolbar-help-content-ulist-example' ).escaped()
							},
							result: {
								html: '<ul>' +
									'<li>' + mw.message( 'wikieditor-toolbar-help-content-ulist-example' ).parse() + '</li>' +
									'<li>' + mw.message( 'wikieditor-toolbar-help-content-ulist-example' ).parse() + '</li>' +
									'</ul>'
							}
						},
						{
							description: {
								html: mw.message( 'wikieditor-toolbar-help-content-olist-description' ).parse()
							},
							syntax: {
								html: '# ' + mw.message( 'wikieditor-toolbar-help-content-olist-example' ).escaped() + '<br />' +
									'# ' + mw.message( 'wikieditor-toolbar-help-content-olist-example' ).escaped()
							},
							result: {
								html: '<ol>' +
									'<li>' + mw.message( 'wikieditor-toolbar-help-content-olist-example' ).parse() + '</li>' +
									'<li>' + mw.message( 'wikieditor-toolbar-help-content-olist-example' ).parse() + '</li>' +
									'</ol>'
							}
						}
					]
				},
				file: {
					label: mw.msg( 'wikieditor-toolbar-help-page-file' ),
					layout: 'table',
					headings: [
						{ html: mw.message( 'wikieditor-toolbar-help-heading-description' ).parse() },
						{ html: mw.message( 'wikieditor-toolbar-help-heading-syntax' ).parse() },
						{ html: mw.message( 'wikieditor-toolbar-help-heading-result' ).parse() }
					],
					rows: [
						{
							description: {
								html: mw.message( 'wikieditor-toolbar-help-content-file-description' ).parse()
							},
							syntax: {
								html: mw.message(
									'wikieditor-toolbar-help-content-file-syntax',
									fileNamespace,
									configData.magicWords.img_thumbnail[ 0 ],
									mw.msg( 'wikieditor-toolbar-help-content-file-caption' )
								).escaped()
							},
							result: {
								html: '<div class="thumbinner" style="width: 102px;">' +
									'<a class="image">' +
									'<img alt="" src="' + $.wikiEditor.imgPath + 'toolbar/example-image.png" width="100" height="50" class="thumbimage"/>' +
									'</a>' +
									'<div class="thumbcaption"><div class="magnify">' +
									'<a title="' + mw.message( 'thumbnail-more' ).escaped() + '" class="internal"></a>' +
									'</div>' + mw.message( 'wikieditor-toolbar-help-content-file-caption' ).escaped() + '</div>' +
									'</div>'
							}
						}
					]
				},
				discussion: {
					label: mw.msg( 'wikieditor-toolbar-help-page-discussion' ),
					layout: 'table',
					headings: [
						{ html: mw.message( 'wikieditor-toolbar-help-heading-description' ).parse() },
						{ html: mw.message( 'wikieditor-toolbar-help-heading-syntax' ).parse() },
						{ html: mw.message( 'wikieditor-toolbar-help-heading-result' ).parse() }
					],
					rows: [
						{
							description: {
								html: mw.message( 'wikieditor-toolbar-help-content-signaturetimestamp-description' ).parse()
							},
							syntax: {
								html: mw.message( 'wikieditor-toolbar-help-content-signaturetimestamp-syntax' ).escaped()
							},
							result: {
								html: delink( mw.message(
									'wikieditor-toolbar-help-content-signaturetimestamp-example',
									mw.config.get( 'wgFormattedNamespaces' )[ 2 ],
									mw.config.get( 'wgFormattedNamespaces' )[ 3 ],
									mw.config.get( 'wgUserName' ) || mw.msg( 'wikieditor-toolbar-help-content-signature-username' )
								).parseDom() )
							}
						},
						{
							description: {
								html: mw.message( 'wikieditor-toolbar-help-content-signature-description' ).parse()
							},
							syntax: {
								html: mw.message( 'wikieditor-toolbar-help-content-signature-syntax' ).escaped()
							},
							result: {
								html: delink( mw.message(
									'wikieditor-toolbar-help-content-signature-example',
									mw.config.get( 'wgFormattedNamespaces' )[ 2 ],
									mw.config.get( 'wgFormattedNamespaces' )[ 3 ],
									mw.config.get( 'wgUserName' ) || mw.msg( 'wikieditor-toolbar-help-content-signature-username' )
								).parseDom() )
							}
						},
						{
							description: {
								html: mw.message( 'wikieditor-toolbar-help-content-indent-description' ).parse()
							},
							syntax: {
								html: mw.message( 'wikieditor-toolbar-help-content-indent1' ).escaped() +
									'<br />:' +
									mw.message( 'wikieditor-toolbar-help-content-indent2' ).escaped() +
									'<br />::' +
									mw.message( 'wikieditor-toolbar-help-content-indent3' ).escaped()
							},
							result: {
								html: mw.message( 'wikieditor-toolbar-help-content-indent1' ).parse() +
									'<dl><dd>' +
									mw.message( 'wikieditor-toolbar-help-content-indent2' ).parse() +
									'<dl><dd>' +
									mw.message( 'wikieditor-toolbar-help-content-indent3' ).parse() +
									'</dd></dl></dd></dl>'
							}
						}
					]
				}
			}
		}
	}
};

// Remove the signature button on non-signature namespaces
if ( !mw.Title.wantSignaturesNamespace( mw.config.get( 'wgNamespaceNumber' ) ) ) {
	delete toolbarConfig.toolbar.main.groups.insert.tools.signature;
}

module.exports = toolbarConfig;
