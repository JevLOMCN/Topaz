/*!
 * VisualEditor UserInterface AnnotationAction class.
 *
 * @copyright See AUTHORS.txt
 */

/**
 * Annotation action.
 *
 * @class
 * @extends ve.ui.Action
 *
 * @constructor
 * @param {ve.ui.Surface} surface Surface to act on
 * @param {string} [source]
 */
ve.ui.AnnotationAction = function VeUiAnnotationAction() {
	// Parent constructor
	ve.ui.AnnotationAction.super.apply( this, arguments );
};

/* Inheritance */

OO.inheritClass( ve.ui.AnnotationAction, ve.ui.Action );

/* Static Properties */

ve.ui.AnnotationAction.static.name = 'annotation';

ve.ui.AnnotationAction.static.methods = [ 'set', 'clear', 'toggle', 'clearAll' ];

/* Methods */

/**
 * Set an annotation.
 *
 * @param {string} name Annotation name, for example: 'textStyle/bold'
 * @param {Object} [data] Additional annotation data
 * @return {boolean} Action was executed
 */
ve.ui.AnnotationAction.prototype.set = function ( name, data ) {
	ve.track( 'activity.' + name, { action: 'set' } );
	return this.setInternal( name, data );
};

/**
 * Clear an annotation.
 *
 * @param {string} name Annotation name, for example: 'textStyle/bold'
 * @param {Object} [data] Additional annotation data
 * @return {boolean} Action was executed
 */
ve.ui.AnnotationAction.prototype.clear = function ( name, data ) {
	ve.track( 'activity.' + name, { action: 'clear' } );
	this.surface.getModel().getFragment().annotateContent( 'clear', name, data );
	return true;
};

/**
 * Toggle an annotation.
 *
 * If the selected text is completely covered with the annotation already the annotation will be
 * cleared. Otherwise the annotation will be set.
 *
 * @param {string} name Annotation name, for example: 'textStyle/bold'
 * @param {Object} [data] Additional annotation data
 * @return {boolean} Action was executed
 */
ve.ui.AnnotationAction.prototype.toggle = function ( name, data ) {
	var surfaceModel = this.surface.getModel(),
		fragment = surfaceModel.getFragment(),
		annotation = ve.dm.annotationFactory.create( name, data );

	if ( !fragment.getSelection().isCollapsed() ) {
		ve.track( 'activity.' + name, { action: 'toggle-selection' } );
		if ( !fragment.getAnnotations().containsComparable( annotation ) ) {
			this.setInternal( name, data );
		} else {
			fragment.annotateContent( 'clear', name );
		}
	} else if ( surfaceModel.sourceMode ) {
		return false;
	} else {
		ve.track( 'activity.' + name, { action: 'toggle-insertion' } );
		var insertionAnnotations = surfaceModel.getInsertionAnnotations();
		var existingAnnotations = insertionAnnotations.getAnnotationsByName( annotation.name );
		var removes = annotation.constructor.static.removes;
		if ( existingAnnotations.isEmpty() ) {
			var removesAnnotations = insertionAnnotations.filter( function ( ann ) {
				return removes.indexOf( ann.name ) !== -1;
			} );
			surfaceModel.removeInsertionAnnotations( removesAnnotations );
			surfaceModel.addInsertionAnnotations( annotation );
		} else {
			surfaceModel.removeInsertionAnnotations( existingAnnotations );
		}
	}
	return true;
};

/**
 * Clear all annotations.
 *
 * @return {boolean} Action was executed
 */
ve.ui.AnnotationAction.prototype.clearAll = function () {
	var surfaceModel = this.surface.getModel(),
		fragment = surfaceModel.getFragment(),
		annotations = fragment.getAnnotations( true );

	ve.track( 'activity.allAnnotations', { action: 'clear-all' } );

	var arr = annotations.get();
	// TODO: Allow multiple annotations to be set or cleared by ve.dm.SurfaceFragment, probably
	// using an annotation set and ideally building a single transaction
	for ( var i = 0, len = arr.length; i < len; i++ ) {
		fragment.annotateContent( 'clear', arr[ i ].name, arr[ i ].data );
	}
	surfaceModel.setInsertionAnnotations( null );
	return true;
};

/**
 * Internal implementation of set(). Do not use this, use set() instead.
 *
 * @private
 * @param {string} name Annotation name, for example: 'textStyle/bold'
 * @param {Object} [data] Additional annotation data
 * @return {boolean} Action was executed
 */
ve.ui.AnnotationAction.prototype.setInternal = function ( name, data ) {
	var fragment = this.surface.getModel().getFragment(),
		annotationClass = ve.dm.annotationFactory.lookup( name );

	if ( fragment.getSelection() instanceof ve.dm.LinearSelection ) {
		var trimmedFragment = fragment.trimLinearSelection();
		if ( !trimmedFragment.getSelection().isCollapsed() ) {
			fragment = trimmedFragment;
		}
	}

	var removes = annotationClass.static.removes;
	for ( var i = removes.length - 1; i >= 0; i-- ) {
		fragment.annotateContent( 'clear', removes[ i ] );
	}
	fragment.annotateContent( 'set', name, data );
	return true;
};

/* Registration */

ve.ui.actionFactory.register( ve.ui.AnnotationAction );
