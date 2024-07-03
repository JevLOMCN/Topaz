/*!
 * VisualEditor MediaWiki DiffPage init.
 *
 * @copyright See AUTHORS.txt
 * @license The MIT License (MIT); see LICENSE.txt
 */

/* eslint-disable no-jquery/no-global-selector */

( function () {
	var reviewModeButtonSelect, lastDiff,
		$wikitextDiffContainer, $wikitextDiffHeader, $wikitextDiffBody,
		$visualDiffContainer = $( '<div>' ),
		$visualDiff = $( '<div>' ),
		progress = new OO.ui.ProgressBarWidget( { classes: [ 've-init-mw-diffPage-loading' ] } ),
		originalUrl = new URL( location.href ),
		initMode = originalUrl.searchParams.get( 'diffmode' ) || mw.user.options.get( 'visualeditor-diffmode-historical' ) || 'source',
		conf = mw.config.get( 'wgVisualEditorConfig' ),
		pluginModules = conf.pluginModules.filter( mw.loader.getState ),
		diffTypeSwitch;

	if ( initMode !== 'visual' ) {
		// Enforce a valid mode, to avoid visual glitches in button-selection.
		initMode = 'source';
	}
	var mode = initMode;

	$visualDiffContainer.append(
		progress.$element.addClass( 'oo-ui-element-hidden' ),
		$visualDiff
	);

	function onReviewModeButtonSelectSelect( item ) {
		var oldPageName, newPageName;
		if ( mw.config.get( 'wgCanonicalSpecialPageName' ) !== 'ComparePages' ) {
			oldPageName = newPageName = mw.config.get( 'wgRelevantPageName' );
		} else {
			var params = new URLSearchParams( location.search );
			oldPageName = params.get( 'page1' );
			newPageName = params.get( 'page2' );
		}

		mode = item.getData();
		var isVisual = mode === 'visual';

		$visualDiffContainer.toggleClass( 'oo-ui-element-hidden', !isVisual );
		$wikitextDiffBody.toggleClass( 'oo-ui-element-hidden', isVisual );

		// If inline switch exists
		if ( typeof diffTypeSwitch !== 'undefined' ) {
			diffTypeSwitch.setDisabled( isVisual );
		}

		var $revSlider = $( '.mw-revslider-container' );
		$revSlider.toggleClass( 've-init-mw-diffPage-revSlider-visual', isVisual );
		if ( isVisual ) {
			// Highlight the headers using the same styles as the diff, to better indicate
			// the meaning of headers when not using two-column diff.
			$wikitextDiffHeader.find( '#mw-diff-otitle1' ).attr( 'data-diff-action', 'remove' );
			$wikitextDiffHeader.find( '#mw-diff-ntitle1' ).attr( 'data-diff-action', 'insert' );
		} else {
			$wikitextDiffHeader.find( '#mw-diff-otitle1' ).removeAttr( 'data-diff-action' );
			$wikitextDiffHeader.find( '#mw-diff-ntitle1' ).removeAttr( 'data-diff-action' );
		}

		var oldId = mw.config.get( 'wgDiffOldId' );
		var newId = mw.config.get( 'wgDiffNewId' );
		if ( isVisual && !(
			lastDiff && lastDiff.oldId === oldId && lastDiff.newId === newId &&
			lastDiff.oldPageName === oldPageName && lastDiff.newPageName === newPageName
		) ) {
			$visualDiff.empty();
			progress.$element.removeClass( 'oo-ui-element-hidden' );
			// TODO: Load a smaller subset of VE for computing the visual diff
			var modulePromise = mw.loader.using( [ 'ext.visualEditor.articleTarget', 'ext.visualEditor.mwmeta' ].concat( pluginModules ) );
			mw.libs.ve.diffLoader.getVisualDiffGeneratorPromise( oldId, newId, modulePromise, oldPageName, newPageName ).then( function ( visualDiffGenerator ) {
				// This class is loaded via modulePromise above
				// eslint-disable-next-line no-undef
				var diffElement = new ve.ui.DiffElement( visualDiffGenerator(), { classes: [ 've-init-mw-diffPage-diff' ] } );
				diffElement.$document.addClass( 'mw-parser-output content' );

				mw.libs.ve.fixFragmentLinks( diffElement.$document[ 0 ], mw.Title.newFromText( newPageName ), 'mw-diffpage-visualdiff-' );

				progress.$element.addClass( 'oo-ui-element-hidden' );
				$visualDiff.append( diffElement.$element );
				lastDiff = {
					oldId: oldId,
					newId: newId,
					oldPageName: oldPageName,
					newPageName: newPageName
				};

				diffElement.positionDescriptions();
			}, function ( code, data ) {
				mw.notify( new mw.Api().getErrorMessage( data ), { type: 'error' } );
				reviewModeButtonSelect.selectItemByData( 'source' );
			} ).catch( function ( error ) {
				mw.notify( error.message, { type: 'error' } );
				reviewModeButtonSelect.selectItemByData( 'source' );
				throw error;
			} );
		}
	}

	function onReviewModeButtonSelectChoose( item ) {
		mode = item.getData();
		if ( mode !== mw.user.options.get( 'visualeditor-diffmode-historical' ) ) {
			mw.user.options.set( 'visualeditor-diffmode-historical', mode );
			// Same as ve.init.target.getLocalApi()
			new mw.Api().saveOption( 'visualeditor-diffmode-historical', mode );
		}
	}

	mw.hook( 'wikipage.diff' ).add( function () {
		if ( mw.config.get( 'wgDiffOldId' ) === false || mw.config.get( 'wgDiffNewId' ) === false ) {
			// Don't offer visual diffs for "fake" diffs where the revision to compare to is not given,
			// e.g. when viewing a "diff" of page creation (T338388)
			return;
		}
		if ( !Object.prototype.hasOwnProperty.call( conf.contentModels, mw.config.get( 'wgPageContentModel' ) ) ) {
			// Don't offer visual diffs for non-wikitext pages.
			// TODO: This is wrong if we're comparing revisions of two different pages, or if the content
			// model of the page has changed between the revisions. The diff needs to give us more
			// information about the revisions being compared… (T346252)
			return;
		}

		$wikitextDiffContainer = $( 'table.diff[data-mw="interface"]' );
		$wikitextDiffHeader = $wikitextDiffContainer.find( 'tr.diff-title' )
			.add( $wikitextDiffContainer.find( 'td.diff-multi, td.diff-notice' ).parent() );
		$wikitextDiffBody = $wikitextDiffContainer.find( 'tr' ).not( $wikitextDiffHeader );
		$wikitextDiffContainer.after( $visualDiffContainer );

		// The PHP widget was a ButtonGroupWidget, so replace with a
		// ButtonSelectWidget instead of infusing.
		reviewModeButtonSelect = new OO.ui.ButtonSelectWidget( {
			items: [
				new OO.ui.ButtonOptionWidget( { data: 'visual', icon: 'eye', label: mw.msg( 'visualeditor-savedialog-review-visual' ) } ),
				new OO.ui.ButtonOptionWidget( { data: 'source', icon: 'wikiText', label: mw.msg( 'visualeditor-savedialog-review-wikitext' ) } )
			]
		} );
		// Choose is only emitted when the user interacts with the widget, whereas
		// select is emitted even when the mode is set programmatically (e.g. on load)
		reviewModeButtonSelect.on( 'select', onReviewModeButtonSelectSelect );
		reviewModeButtonSelect.on( 'choose', onReviewModeButtonSelectChoose );
		$( '.ve-init-mw-diffPage-diffMode' ).empty().append( reviewModeButtonSelect.$element );
		reviewModeButtonSelect.selectItemByData( mode );
	} );

	mw.hook( 'wikipage.diff.wikitextBodyUpdate' ).add( function ( $wikitextBody ) {
		$wikitextDiffBody = $wikitextBody;
	} );

	mw.hook( 'wikipage.diff.diffTypeSwitch' ).add( function ( inlineToggleSwitch ) {
		diffTypeSwitch = inlineToggleSwitch;
		diffTypeSwitch.setDisabled( mode === 'visual' );
	} );
}() );
