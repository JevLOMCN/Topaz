/**
 * Button widget to toggle unused fields in the template dialog sidebar, appears
 * at the top of any template with more than the threshold number of parameters.
 *
 * @class
 * @extends OO.ui.ButtonWidget
 *
 * @constructor
 */
ve.ui.MWTransclusionOutlineToggleUnusedWidget = function VeUiMWTransclusionOutlineToggleUnusedWidget() {
	// Parent constructor
	ve.ui.MWTransclusionOutlineToggleUnusedWidget.super.call( this, {
		label: ve.msg( 'visualeditor-dialog-transclusion-filter-hide-unused' ),
		flags: [ 'progressive' ],
		framed: false
	} );

	// Events
	this.connect( this, {
		toggle: 'onToggle',
		click: 'onClick'
	} );

	// Initialization
	this.$element.addClass( 've-ui-mwTransclusionOutlineToggleUnusedWidget' );
	this.showUnusedFields = true;
};

/* Inheritance */
OO.inheritClass( ve.ui.MWTransclusionOutlineToggleUnusedWidget, OO.ui.ButtonWidget );

/* Events */

/**
 * @event toggleUnusedFields
 * @param {boolean} visibility If unused fields should be shown or not.
 * @param {boolean} [fromClick]
 * Emitted when the visibility for unused fields should be (re)applied.
 */

/**
 * Handles clicks on the button by mouse or keyboard interaction.
 *
 * @private
 * @fires toggleUnusedFields
 */
ve.ui.MWTransclusionOutlineToggleUnusedWidget.prototype.onClick = function () {
	this.toggleUnusedParameters( !this.showUnusedFields, true );
};

/** @inheritdoc */
ve.ui.MWTransclusionOutlineToggleUnusedWidget.prototype.setDisabled = function ( disabled ) {
	ve.ui.MWTransclusionOutlineToggleUnusedWidget.super.prototype.setDisabled.call( this, disabled );
	this.updateState( this.showUnusedFields );
};

/**
 * @param {boolean} showUnused
 * @param {boolean} [fromClick]
 * @fires toggleUnusedFields
 */
ve.ui.MWTransclusionOutlineToggleUnusedWidget.prototype.toggleUnusedParameters = function ( showUnused, fromClick ) {
	if ( this.updateState( showUnused ) ) {
		this.emit( 'toggleUnusedFields', this.showUnusedFields, fromClick );
	}
};

/**
 * @private
 * @param {boolean} showUnused
 * @return {boolean} Returns true when state changed.
 */
ve.ui.MWTransclusionOutlineToggleUnusedWidget.prototype.updateState = function ( showUnused ) {
	showUnused = showUnused || this.isDisabled();
	if ( showUnused !== this.showUnusedFields ) {
		this.showUnusedFields = showUnused;
		this.setLabel( ve.msg( this.showUnusedFields ?
			'visualeditor-dialog-transclusion-filter-hide-unused' :
			'visualeditor-dialog-transclusion-filter-show-all'
		) );
		return true;
	}
	return false;
};

/**
 * Handles toggling the visibility of the button.
 *
 * @private
 * @param {boolean} visible
 * @fires toggleUnusedFields
 */
ve.ui.MWTransclusionOutlineToggleUnusedWidget.prototype.onToggle = function ( visible ) {
	if ( visible ) {
		this.emit( 'toggleUnusedFields', this.showUnusedFields );
	}
};
