/*!
 * VisualEditor progress bar widget
 *
 * @copyright See AUTHORS.txt
 * @license The MIT License (MIT); see LICENSE.txt
 */

mw.libs.ve = mw.libs.ve || {};

/**
 * Progress bar widget
 *
 * This widget can be used to show a progress bar
 * while VE libraries are still loading.
 *
 * It has a similar API to OO.ui.ProgressBarWidget, but is designed to
 * be loaded before any core VE code or dependencies, e.g. OOUI.
 *
 * @class
 * @constructor
 */
mw.libs.ve.ProgressBarWidget = function VeUiMwProgressBarWidget() {
	this.progressStep = 0;
	this.progressSteps = [
		// [ percentage, delay ]
		[ 30, 3000 ],
		[ 70, 2000 ],
		[ 100, 1000 ]
	];
	// Stylesheets might not have processed yet, so manually set starting width to 0
	this.$bar = $( '<div>' ).addClass( 've-init-mw-progressBarWidget-bar' ).css( 'width', 0 );
	this.$element = $( '<div>' ).addClass( 've-init-mw-progressBarWidget' ).append( this.$bar );
};

mw.libs.ve.ProgressBarWidget.prototype.setLoadingProgress = function ( target, duration ) {
	var $bar = this.$bar.stop();
	$bar.css( 'transition', 'width ' + duration + 'ms ease-in' );
	setTimeout( function () {
		$bar.css( 'width', target + '%' );
	} );
};

mw.libs.ve.ProgressBarWidget.prototype.incrementLoadingProgress = function () {
	var step = this.progressSteps[ this.progressStep ];
	if ( step ) {
		this.setLoadingProgress( step[ 0 ], step[ 1 ] );
		this.progressStep++;
	}
};

mw.libs.ve.ProgressBarWidget.prototype.clearLoading = function () {
	this.progressStep = 0;
	this.setLoadingProgress( 0, 0 );
};
