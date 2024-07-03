'use strict';

const Page = require( 'wdio-mediawiki/Page' );

class ViewEditPage extends Page {
	// Here we avoid things depending on the config, e.g. group and global
	get filterId() {
		return $( '#mw-abusefilter-edit-id .mw-input' );
	}

	get name() {
		return $( 'input[name="wpFilterDescription"]' );
	}

	get rules() {
		return $( '#wpFilterRules' );
	}

	get comments() {
		return $( 'textarea[name="wpFilterNotes"]' );
	}

	get hidden() {
		return $( 'input[name="wpFilterHidden"]' );
	}

	get enabled() {
		return $( 'input[name="wpFilterEnabled"]' );
	}

	get deleted() {
		return $( 'input[name="wpFilterDeleted"]' );
	}

	// @todo This assumes that warn is enabled in the config, which is true by default
	get warnCheckbox() {
		return $( 'input[name="wpFilterActionWarn"]' );
	}

	get warnOtherMessage() {
		return $( 'input[name="wpFilterWarnMessageOther"]' );
	}

	get exportData() {
		return $( '#mw-abusefilter-export textarea' ).getValue();
	}

	get submitButton() {
		return $( '#mw-abusefilter-editing-form input[type="submit"]' );
	}

	get error() {
		return $( '.mw-message-box-error' );
	}

	get warning() {
		return $( '.mw-message-box-warning' );
	}

	async submit() {
		await this.submitButton.waitForClickable();
		await this.submitButton.click();
	}

	/**
	 * Conveniency: the ace editor is hard to manipulate, and working with
	 * the hidden textarea isn't great (sendKeys is not processed)
	 */
	async switchEditor() {
		const button = await $( '#mw-abusefilter-switcheditor' );
		if ( !await button.isExisting() ) {
			// CodeEditor not installed, nothing to do here.
			return;
		}
		await button.waitForClickable();
		await button.click();
	}

	async setWarningMessage( msg ) {
		await $( 'select[name="wpFilterWarnMessage"]' ).selectByAttribute( 'value', 'other' );
		await this.warnOtherMessage.setValue( msg );
	}

	async invalidateToken() {
		await $( '#mw-abusefilter-editing-form input[name="wpEditToken"]' ).setValue( '' );
	}

	async open( subpage ) {
		await super.openTitle( 'Special:AbuseFilter/' + subpage );
	}
}
module.exports = new ViewEditPage();
